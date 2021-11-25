<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Dice;
use KOT\Objects\ChangeActivePlayerDieIntervention;
use KOT\Objects\Damage;


function getDieFace($die) {
    if ($die->type === 2) {
        return 10;
    } else if ($die->type === 1) {
        if ($die->value <= 2) {
            return 5;
        } else if ($die->value <= 5) {
            return 6;
        } else {
            return 7;
        }
    } else {
        return $die->value;
    }
}

trait DiceStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stThrowDice() {
        // disabled so player can see last roll
        /*if ($this->autoSkipImpossibleActions() && !$this->argThrowDice()['hasActions']) {
            // skip state
            $this->goToChangeDie(true);
        }*/
    }

    function stChangeDie() {
        $playerId = self::getActivePlayerId();

        $canChangeWithCards = $this->canChangeDie($this->getChangeDieCards($playerId));
        $canRetrow3 = intval(self::getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0 && $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
        
        if (!$canChangeWithCards && !$canRetrow3) {
            $this->gamestate->nextState('resolve');
        }
    }

    function stChangeActivePlayerDie() {
        $this->stIntervention(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
    }

    function stPrepareResolveDice() {
        if ($this->isHalloweenExpansion()) {
            $this->gamestate->nextState('askCheerleaderSupport');
        } else {
            $this->gamestate->nextState('resolve');
        }
        
    }

    function stResolveDice() {
        $this->updateKillPlayersScoreAux();
        
        $playerId = self::getActivePlayerId();
        self::giveExtraTime($playerId);

        self::DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");

        $playerInTokyo = $this->inTokyo($playerId);
        $dice = $this->getPlayerRolledDice($playerId, true, true, false);
        usort($dice, "static::sortDieFunction");

        $diceStr = '';
        foreach($dice as $die) {
            $diceStr .= $this->getDieFaceLogName($die->value, $die->type);
        }

        self::notifyAllPlayers("resolvePlayerDice", clienttranslate('${player_name} resolves dice ${dice}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'dice' => $diceStr,
        ]);

        $smashTokyo = false;

        $diceCounts = $this->getRolledDiceCounts($playerId, $dice, false);

        $detail = $this->addSmashesFromCards($playerId, $diceCounts, $playerInTokyo);
        $diceCounts[6] += $detail->addedSmashes;

        if ($detail->addedSmashes > 0) {
            $diceStr = '';
            for ($i=0; $i<$detail->addedSmashes; $i++) { 
                $diceStr .= $this->getDieFaceLogName(6, 0); 
            }
            
            $cardNamesStr = implode(', ', $detail->cardsAddingSmashes);

            self::notifyAllPlayers("resolvePlayerDiceAddedDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'dice' => $diceStr,
                'card_name' => $cardNamesStr,
            ]);
        }

        // detritivore
        if ($diceCounts[1] >= 1 && $diceCounts[2] >= 1 && $diceCounts[3] >= 1) {
            $countDetritivore = $this->countCardOfType($playerId, DETRITIVORE_CARD);
            if ($countDetritivore > 0) {
                $this->applyGetPoints($playerId, 2 * $countDetritivore, DETRITIVORE_CARD);
            }

            // complete destruction
            if ($diceCounts[4] >= 1 && $diceCounts[5] >= 1 && $diceCounts[6] >= 1) { // dice 1-2-3 check with previous if
                $countCompleteDestruction = $this->countCardOfType($playerId, COMPLETE_DESTRUCTION_CARD);
                if ($countCompleteDestruction > 0) {
                    $this->applyGetPoints($playerId, 9 * $countCompleteDestruction, COMPLETE_DESTRUCTION_CARD);
                }
            }
        }

        $fireBreathingDamages = [];
        // fire breathing
        if ($diceCounts[6] >= 1) {
            $countFireBreathing = $this->countCardOfType($playerId, FIRE_BREATHING_CARD);
            if ($countFireBreathing > 0) {
                $playersIds = $this->getPlayersIds();
                $playerIndex = array_search($playerId, $playersIds);
                $playerCount = count($playersIds);
                
                $leftPlayerId = $playersIds[($playerIndex + 1) % $playerCount];
                $rightPlayerId = $playersIds[($playerIndex + $playerCount - 1) % $playerCount];

                if ($leftPlayerId != $playerId) {
                    $fireBreathingDamages[$leftPlayerId] = $countFireBreathing;
                }
                if ($rightPlayerId != $playerId && $rightPlayerId != $leftPlayerId) {
                    $fireBreathingDamages[$rightPlayerId] = $countFireBreathing;
                }
            }
        }

        if ($diceCounts[1] >= 4 && $this->isKingKongExpansion()) {
            $this->getNewTokyoTowerLevel($playerId);
        }

        if ($diceCounts[6] >= 4 && $this->isCybertoothExpansion() && !$this->isPlayerBerserk($playerId)) {
            $this->setPlayerBerserk($playerId, true);
        }
        
        if ($this->isCthulhuExpansion()) {
            for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
                if ($diceCounts[$diceFace] >= 4) {
                    $this->applyGetCultist($playerId, $diceFace);
                }
            }
        }

        $this->setGlobalVariable(FIRE_BREATHING_DAMAGES, $fireBreathingDamages);
        $this->setGlobalVariable(DICE_COUNTS, $diceCounts);

        if ($this->isAnubisExpansion()) {
            $this->gamestate->nextState('resolveDieOfFate');
        } else {
            $this->gamestate->nextState('resolveNumberDice');
        }
    }

    function stResolveDieOfFate() {
        $playerId = self::getActivePlayerId();

        $dieOfFate = $this->getDieOfFate();

        // TODO notif for each case
        $damages = null;
        switch($dieOfFate->value) {
            case 1: 
                $this->changeCurseCard();
                break;
            case 2:
                self::notifyAllPlayers('dieOfFateResolution', /*client TODOAN translate(*/'Die of fate is on [dieFateRiver], Curse card is kept (with no effect except permanent effect)'/*)*/, []);
                break;
            case 3:
                self::notifyAllPlayers('dieOfFateResolution', /*client TODOAN translate(*/'Die of fate is on [dieFateSnake], Snake effect is applied'/*)*/, []);
                $damages = $this->applySnakeEffect($playerId);
                break;
            case 4:
                self::notifyAllPlayers('dieOfFateResolution', /*client TODOAN translate(*/'Die of fate is on [dieFateAnkh], Ankh effect is applied'/*)*/, []);
                $this->applyAnkhEffect($playerId);
                break;
        }

        $redirects = false;
        if ($damages != null && count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, ST_RESOLVE_NUMBER_DICE);
        }

        if (!$redirects) {
            $this->gamestate->nextState('next');
        }
    }

    function stResolveNumberDice() {
        $playerId = self::getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        for ($diceFace = 1; $diceFace <= 3; $diceFace++) {
            $diceCount = $diceCounts[$diceFace];
            $this->resolveNumberDice($playerId, $diceFace, $diceCount);
        }

        $canSelectHeartDiceUse = false;
        if ($diceCounts[4] > 0) {
            $selectHeartDiceUse = $this->getSelectHeartDiceUse($playerId);
            $inTokyo = $this->inTokyo($playerId);

            $canRemoveToken = ($selectHeartDiceUse['shrinkRayTokens'] > 0 || $selectHeartDiceUse['poisonTokens'] > 0) && !$inTokyo;

            $canSelectHeartDiceUse = ($selectHeartDiceUse['hasHealingRay'] && count($selectHeartDiceUse['healablePlayers']) > 0) || $canRemoveToken;
        }

        if ($canSelectHeartDiceUse) {
            $this->gamestate->nextState('nextAction');
        } else {
            $this->gamestate->nextState('next');
        }
    }

    function stResolveHeartDice() {
        $playerId = self::getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $diceCounts[4];
        if ($diceCount > 0) {
            $this->resolveHealthDice($playerId, $diceCount);
        }
        $this->gamestate->nextState('next');
    }

    function stResolveEnergyDice() {
        $playerId = self::getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $diceCounts[5];
        if ($diceCount > 0) {
            $this->resolveEnergyDice($playerId, $diceCount);
        }

        $this->gamestate->nextState('next');
    }

    function stResolveSmashDice() {
        $playerId = self::getActivePlayerId();
        
        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        if ($this->canUseSymbol($playerId, 6)) {
            $diceCount = $diceCounts[6];
        } else {
            $diceCount = 0;
        }
        
        /* TODOCY
        $redirects = false;
        if ($diceCount > 0) {
            $playerId = self::getActivePlayerId();
            $redirects = $this->resolveSmashDice($playerId, $diceCount);
        } else {
            self::setGameStateValue(STATE_AFTER_RESOLVE, ST_ENTER_TOKYO_APPLY_BURROWING);
        }

        if (!$redirects) {    
            $this->gamestate->nextState('next');
        }*/

        $nextState = 'enterTokyo';
        $smashTokyo = false;

        if ($diceCount > 0) {
            $nextState = $this->resolveSmashDice($playerId, $diceCount);
        }
        
        if ($nextState != null) {
            $this->gamestate->nextState($nextState);
        }
    }

    function stResolveSkullDice() {   
        $playerId = self::getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);

        $nextState = intval(self::getGameStateValue(STATE_AFTER_RESOLVE));
        $redirects = false;

        $damages = [];

        if ($this->isCybertoothExpansion() && $diceCounts[7] > 0) {
            $damages[] = new Damage($playerId, $diceCounts[7], $playerId, 0, 0, 0);
        }

        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == FALSE_BLESSING_CURSE_CARD) {
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceFaces = [];        
                foreach ($dice as $die) {
                    if ($die->type === 0 || $die->type === 1) {
                        $diceFaces[$this->getDiceFaceType($die)] = true; 
                    }
                }

                $facesCount = count(array_keys($diceFaces));
                
                $damages[] = new Damage($playerId, $facesCount, $playerId, 2000 + FALSE_BLESSING_CURSE_CARD, 0, 0);
            }
        }

        if (count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, $nextState);
        }
        
        if (!$redirects) {
            $this->gamestate->jumpToState($nextState);
        }
    }
}
