<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Dice;
use KOT\Objects\PsychicProbeIntervention;
use KOT\Objects\Damage;

trait DiceTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////
    
    function getDice(int $number) {
        $sql = "SELECT `dice_id`, `dice_value`, `extra`, `locked`, `rolled` FROM dice ORDER BY dice_id limit $number";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices));
    }

    function getDieById(int $id) {
        $sql = "SELECT `dice_id`, `dice_value`, `extra`, `locked`, `rolled` FROM dice WHERE `dice_id` = $id";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices))[0];
    }

    function getFirst3Dice(int $number) {
        $dice = $this->getDice($number);
        foreach ($dice as $dice) {
            if ($dice->value === 3) {
                return $dice;
            }
        }
        return null;
    }

    public function throwDice($playerId) {
        $dice = $this->getDice($this->getDiceNumber($playerId));

        self::DbQuery( "UPDATE dice SET `rolled` = false");
        
        foreach ($dice as &$dice) {
            if (!$dice->locked) {
                $dice->value = bga_rand(1, 6);
                self::DbQuery( "UPDATE dice SET `dice_value` = ".$dice->value.", `rolled` = true where `dice_id` = ".$dice->id );
            }
        }
    }

    function getDiceNumber(int $playerId) {
        $remove = intval($this->getGameStateValue('lessDiceForNextTurn')) + $this->getPlayerShrinkRayTokens($playerId);

        return max(6 + $this->countExtraHead($playerId) - $remove, 0);
    }

    function resolveNumberDice(int $playerId, int $number, int $diceCount) {
        // number
        if ($diceCount >= 3) {
            $points = $number + $diceCount - 3;

            $this->applyGetPoints($playerId, $points, -1);

            self::notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} wins ${deltaPoints} with ${dice_value} dice'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'deltaPoints' => $points,
                'points' => $this->getPlayerScore($playerId),
                'diceValue' => $number,
                'dice_value' => "[dice$number]",
            ]);

            if ($number == 1) {
                // gourmet
                $countGourmet = $this->countCardOfType($playerId, GOURMET_CARD);
                if ($countGourmet > 0) {
                    $this->applyGetPoints($playerId, 2 * $countGourmet, GOURMET_CARD);
                }

                // Freeze Time
                $countFreezeTime = $this->countCardOfType($playerId, FREEZE_TIME_CARD);
                if ($countFreezeTime > 0) {
                    // TOCHECK Can Freeze Time be cloned and win 2 new turn ? If yes, 1 or 2 less die next turn ? Considered No
                    $this->setGameStateValue('playAgainAfterTurnOneLessDie', 1);
                }
                
            }
        }
    }

    function resolveHealthDice(int $playerId, int $diceCount) {
        if ($this->inTokyo($playerId)) {
            self::notifyAllPlayers( "resolveHealthDiceInTokyo", clienttranslate('${player_name} wins no [Heart] (player in Tokyo)'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
            ]);
        } else {
            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth) {
                $this->applyGetHealth($playerId, $diceCount, -1);
                $newHealth = $this->getPlayerHealth($playerId);

                self::notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} wins ${deltaHealth} [Heart]'), [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'health' => $newHealth,
                    'deltaHealth' => $diceCount,
                ]);
            }
        }
    }

    function resolveEnergyDice(int $playerId, int $diceCount) {
        $this->applyGetEnergy($playerId, $diceCount, -1);

        self::notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} wins ${deltaEnergy} [Energy]'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'deltaEnergy' => $diceCount,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);
    }

    
    function resolveSmashDice(int $playerId, int $diceCount) {
        // Nova breath
        $countNovaBreath = $this->countCardOfType($playerId, NOVA_BREATH_CARD);

        $message = null;
        $smashedPlayersIds = null;
        $inTokyo = $this->inTokyo($playerId);

        $damages = [];

        if ($countNovaBreath) {
            $message = clienttranslate('${player_name} give ${number} [diceSmash] to all other Monsters');
            $smashedPlayersIds = $this->getOtherPlayersIds($playerId);
        } else {
            $smashTokyo = !$inTokyo;
            $message = $smashTokyo ? 
                clienttranslate('${player_name} give ${number} [diceSmash] to Monsters in Tokyo') :
                clienttranslate('${player_name} give ${number} [diceSmash] to Monsters outside Tokyo');
            $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);
        }

        $jetsDamages = [];
        foreach($smashedPlayersIds as $smashedPlayerId) {
            // Jets
            $countJets = $this->countCardOfType($smashedPlayerId, JETS_CARD);

            if ($countJets > 0) {
                $jetsDamages[] = new Damage($smashedPlayerId, $diceCount, $playerId, 0);
            } else {
                $damages[] = new Damage($smashedPlayerId, $diceCount, $playerId, 0);
            }
        }
        $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);      

        self::notifyAllPlayers("resolveSmashDice", $message, [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'number' => $diceCount,
            'smashedPlayersIds' => $smashedPlayersIds,
        ]);

        // Alpha Monster
        $countAlphaMonster = $this->countCardOfType($playerId, ALPHA_MONSTER_CARD);
        if ($countAlphaMonster > 0) {
            // TOCHECK does Alpha Monster applies after other cards adding Smashes ? considered Yes
            $this->applyGetPoints($playerId, $countAlphaMonster, 3ALPHA_MONSTER_CARD);
        }

        // Shrink Ray
        // TOCHECK can Shrink Ray be mimicked and give 2 tokens ? considered No
        $countShrinkRay = $this->countCardOfType($playerId, SHRINK_RAY_CARD);
        if ($countShrinkRay > 0) {
            foreach($smashedPlayersIds as $smashedPlayerId) {
                $this->applyGetShrinkRayToken($smashedPlayerId);
            }
        }

        // Poison Spit
        // TOCHECK can Poison Spit be mimicked and give 2 tokens ? considered No
        $countPoisonSpit = $this->countCardOfType($playerId, POISON_SPIT_CARD);
        if ($countPoisonSpit > 0) {
            foreach($smashedPlayersIds as $smashedPlayerId) {
                $this->applyGetPoisonToken($smashedPlayerId);
            }
        }

        // TOCHECK Can a player leave tokyo if he cancel all damage while in tokyo (with wings or camouflage) ? Considered Yes
        if (count($damages) > 0) {
            $cancelDamageEndState = !$inTokyo && count($this->getPlayersIdsInTokyo()) > 0 ? "smashes" : "enterTokyo";
            return $this->resolveDamages($damages, $cancelDamageEndState);
        } else {
            return false;
        }
    }

    function getChangeDieCards(int $playerId) {
        // Herd Culler
        $herdCullerCards = $this->getCardsOfType($playerId, HERD_CULLER_CARD);
        $availableHerdCullers = 0;
        $herdCullerCount = count($herdCullerCards);
        if ($herdCullerCount > 0) {
            $usedCards = $this->getUsedCard();
            foreach ($herdCullerCards as $herdCullerCard) {
                if (array_search($herdCullerCard->id, $usedCards) === false) {
                    $availableHerdCullers++;
                }
            }
        }
        $hasHerdCuller = $herdCullerCount > 0 && $availableHerdCullers > 0;
        // Plot Twist
        $hasPlotTwist = $this->countCardOfType($playerId, PLOT_TWIST) > 0;
        // Stretchy
        $hasStretchy = $this->countCardOfType($playerId, STRETCHY_CARD) > 0 && $this->getPlayerEnergy($playerId) >= 2;
        // TOCHECK is Stretchy only once per turn ? Considered No

        return [
            'hasHerdCuller' => $hasHerdCuller,
            'hasPlotTwist' => $hasPlotTwist,
            'hasStretchy' => $hasStretchy,
        ];
    }

    function canChangeDie(array $cards) {
        return $cards['hasHerdCuller'] || $cards['hasPlotTwist'] || $cards['hasStretchy'];
    }

    function getSelectHeartDiceUse(int $playerId) {        
        // Healing Ray
        $countHealingRay = $this->countCardOfType($playerId, HEALING_RAY_CARD);
        $healablePlayers = [];
        if ($countHealingRay > 0) {
            $otherPlayers = $this->getOtherPlayers($playerId);
    
            foreach($otherPlayers as $otherPlayer) {
                $missingHearts = $this->getPlayerMaxHealth($otherPlayer->id) - $this->getPlayerHealth($otherPlayer->id);

                // TOCHECK Healing ray can be user on player with 0 energy ? Considered Yes

                if ($missingHearts > 0) {
                    $playerHealInformations = new \stdClass();
                    $playerHealInformations->id = $otherPlayer->id;
                    $playerHealInformations->name = $otherPlayer->name;
                    $playerHealInformations->color = $otherPlayer->color;
                    $playerHealInformations->energy = $otherPlayer->energy;
                    $playerHealInformations->missingHearts = $missingHearts;

                    $healablePlayers[] = $playerHealInformations;
                }
            }
        }

        return [
            'hasHealingRay' => $countHealingRay > 0,
            'healablePlayers' => $healablePlayers,
            'shrinkRayTokens' => $this->getPlayerShrinkRayTokens($playerId),
            'poisonTokens' => $this->getPlayerPoisonTokens($playerId),
        ];
    }

    

    function getPlayersWithPsychicProbe(int $playerId) {
        $orderedPlayers = $this->getOrderedPlayers($playerId);
        $psychicProbePlayerIds = [];

        foreach($orderedPlayers as $player) {
            if ($player->id != $playerId) {

                $psychicProbeCards = $this->getCardsOfType($player->id, PSYCHIC_PROBE_CARD);
                $unusedPsychicProbeCards = 0;
                $usedCards = $this->getUsedCard();
                foreach($psychicProbeCards as $psychicProbeCard) {
                    if (array_search($psychicProbeCard->id, $usedCards) === false) {
                        $unusedPsychicProbeCards++;
                    }
                }
                if ($unusedPsychicProbeCards > 0) {
                    $psychicProbePlayerIds[] = $player->id;
                }            
            }
        }

        return $psychicProbePlayerIds;
    }

    function getPsychicProbeInterventionEndState($intervention) {
        $backToChangeDie = $this->canChangeDie($this->getChangeDieCards($intervention->activePlayerId));
        return $backToChangeDie ? 'endAndChangeDieAgain' : 'end';
    }

    function getDieFaceLogName(int $number) {
        switch($number) {
            case 1:
            case 2:
            case 3: return "[dice$number]";
            case 4: return "[diceHeart]";
            case 5: return "[diceEnergy]";
            case 6: return "[diceSmash]";
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
  	
    public function rethrowDice(string $diceIds) {
        $playerId = self::getActivePlayerId();
        self::DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");
        self::DbQuery("UPDATE dice SET `locked` = false, `rolled` = true where `dice_id` IN ($diceIds)");
        $this->throwDice($playerId);

        $throwNumber = intval(self::getGameStateValue('throwNumber')) + 1;
        self::setGameStateValue('throwNumber', $throwNumber);

        $this->gamestate->nextState('rethrow');
    }

    public function rethrow3() {
        $playerId = self::getActivePlayerId();
        $dice = $this->getFirst3Dice($this->getDiceNumber($playerId));

        if ($dice == null) {
            throw new \Error('No dice 3');
        }

        $dice->value = bga_rand(1, 6);
        self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dice->id);
        self::DbQuery("UPDATE dice SET `dice_value` = ".$dice->value.", `rolled` = true where `dice_id` = ".$dice->id);

        $this->gamestate->nextState('rethrow3');
    }

    public function changeDie(int $id, int $value, int $card) {
        $playerId = self::getActivePlayerId();

        self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$id);
        self::DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$id);

        if ($card == HERD_CULLER_CARD) {
            $usedCards = $this->getUsedCard();
            $herdCullerCards = $this->getCardsOfType($playerId, HERD_CULLER_CARD);
            $usedCardOnThisTurn = null;
            foreach($herdCullerCards as $herdCullerCard) {
                if (array_search($herdCullerCard->id, $usedCards) === false) {
                    $usedCardOnThisTurn = $herdCullerCard->id;
                }
            }
            if ($usedCardOnThisTurn == null) {
                throw new \Error('No unused Herd Culler for this player');
            } else {
                $this->setUsedCard($usedCardOnThisTurn);
            }
        } else {
            if ($card == PLOT_TWIST) {
                $this->removeCardByType($playerId, PLOT_TWIST);
            } else if ($card == STRETCHY_CARD) {
                $this->applyLoseEnergyIgnoreCards($playerId, 2, 0);
            }
        }

        $playersWithPsychicProbe = $this->getPlayersWithPsychicProbe($playerId);

        if (count($playersWithPsychicProbe) > 0) {
            $cards = [];
            foreach ($playersWithPsychicProbe as $playerWithPsychicProbe) {
                $cards = array_merge($cards, $this->getCardsOfType($playerWithPsychicProbe, PSYCHIC_PROBE_CARD));
            }
            $psychicProbeIntervention = new PsychicProbeIntervention($playersWithPsychicProbe, $playerId, $cards);
            $this->setGlobalVariable(PSYCHIC_PROBE_INTERVENTION, $psychicProbeIntervention);
            $this->gamestate->nextState('changeDieWithPsychicProbe');
        } else {
            $this->gamestate->nextState('changeDie');
        }
    }

    public function psychicProbeRollDie(int $id) {
        $intervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);
        $playerId = $intervention->remainingPlayersId[0];

        $die = $this->getDieById($id);
        $value = bga_rand(1, 6);
        self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$id);
        self::DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$id);
        
        $usedCards = $this->getUsedCard();
        $psychicProbeCards = $this->getCardsOfType($playerId, PSYCHIC_PROBE_CARD);
        $usedCardOnThisTurn = null;
        foreach($psychicProbeCards as $psychicProbeCard) {
            if (array_search($psychicProbeCard->id, $usedCards) === false) {
                $usedCardOnThisTurn = $psychicProbeCard->id;
            }
        }
        if ($usedCardOnThisTurn == null) {
            throw new \Error('No unused Psychic Probe for this player');
        } else {
            $this->setUsedCard($usedCardOnThisTurn);
        }

        if ($value == 4) {
            $currentPlayerCards = array_values(array_filter($intervention->cards, function ($card) use ($playerId) { return $card->location_arg == $currentPlayerId; }));
            if (count($currentPlayerCards) > 0) {
                $card = $currentPlayerCards[0];
                $this->removeCard($playerId, $card);

                if ($card->type == PSYCHIC_PROBE_CARD) { // real Psychic Probe             
                    // TOCHECK If Mimic is set on Psychic Probe and Psychic Probe is discarded, is Mimick disarded ? Considered no but token removed
                    // TOCHECK If Mimic is set on Psychic Probe and Psychic Probe is discarded before Mimic turn, Can Mimic player still do Psychic Probe roll during this turn ? Considered Yes but TODO probably change to No
                }
            }
        }

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        if ($value == 4) {
            $message .= ' ' . clienttranslate('(${card_name} is discarded)');
        }
        self::notifyAllPlayers("psychicProbeRollDie", $message, [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $this->getCardName(PSYCHIC_PROBE_CARD),
            'die_face_before' => $this->getDieFaceLogName($die->value),
            'die_face_after' => $this->getDieFaceLogName($value),
        ]);

        $this->setInterventionNextState(PSYCHIC_PROBE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    public function goToChangeDie() {
        $playerId = self::getActivePlayerId();

        $playersWithPsychicProbe = $this->getPlayersWithPsychicProbe($playerId);

        if (count($playersWithPsychicProbe) > 0) {
            $cards = [];
            foreach ($playersWithPsychicProbe as $playerWithPsychicProbe) {
                $cards = array_merge($cards, $this->getCardsOfType($playerWithPsychicProbe, PSYCHIC_PROBE_CARD));
            }
            $psychicProbeIntervention = new PsychicProbeIntervention($playersWithPsychicProbe, $playerId, $cards);
            $this->setGlobalVariable(PSYCHIC_PROBE_INTERVENTION, $psychicProbeIntervention);
            $this->gamestate->nextState('psychicProbe');
        } else {
            $this->gamestate->nextState('goToChangeDie');
        }
    }

    function psychicProbeSkip() {
        $playerId = self::getCurrentPlayerId();

        $intervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);
        $this->setInterventionNextState(PSYCHIC_PROBE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function applyHeartDieChoices(array $heartDieChoices) {
        $playerId = self::getActivePlayerId();

        $heal = 0;
        $healPlayer = [];
        
        foreach ($heartDieChoices as $heartDieChoice) {
            switch ($heartDieChoice->action) {
                case 'heal':
                    $heal++;
                    break;
                case 'shrink-ray':
                    $this->removeShrinkRayToken($playerId);
                    break;
                case 'poison':
                    $this->removePoisonToken($playerId);
                    break;
                case 'heal-player':
                    if (array_key_exists($heartDieChoice->playerId, $healPlayer)) {
                        $healPlayer[$heartDieChoice->playerId] = $healPlayer[$heartDieChoice->playerId] + 1;
                    } else {
                        $healPlayer[$heartDieChoice->playerId] = 1;
                    }
                    break;
            }
        }

        if ($heal > 0) {
            $this->resolveHealthDice($playerId, $heal);
        }
        foreach ($healPlayer as $healPlayerId => $healNumber) {
            $this->applyGetHealth($healPlayerId, $healNumber, HEALING_RAY_CARD);
            $this->applyLoseEnergy($healPlayerId, $healNumber * 2, 0);
        }

        $this->gamestate->nextState('next');
    }

    public function resolveDice() {
        $this->gamestate->nextState('resolve');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argThrowDice() {
        $playerId = self::getActivePlayerId();
        $diceNumber = $this->getDiceNumber($playerId);
        $dice = $this->getDice($diceNumber);

        $throwNumber = intval(self::getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->getThrowNumber($playerId);

        $hasEnergyDrink = $this->countCardOfType($playerId, ENERGY_DRINK_CARD) > 0; // Energy drink
        $playerEnergy = null;
        if ($hasEnergyDrink) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
        }

        $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0; // Background Dweller
        $hasDice3 = null;
        if ($hasBackgroundDweller) {
            $hasDice3 = $this->getFirst3Dice($diceNumber) != null;
        }

        $hasSmokeCloud = $this->countCardOfType($playerId, SMOKE_CLOUD_CARD) > 0; // Smoke Cloud
    
        // return values:
        return [
            'throwNumber' => $throwNumber,
            'maxThrowNumber' => $maxThrowNumber,
            'dice' => $dice,
            'inTokyo' => $this->inTokyo($playerId),
            'energyDrink' => [
                'hasCard' => $hasEnergyDrink,
                'playerEnergy' => $playerEnergy,
            ],
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
            'hasSmokeCloud' => $hasSmokeCloud
        ];
    }

    function argChangeDie() {
        $playerId = self::getActivePlayerId();

        $cardsArg = $this->getChangeDieCards($playerId);

        $diceArg = [];
        if ($this->canChangeDie($cardsArg)) {
            $diceNumber = $this->getDiceNumber($playerId);
            $diceArg = [
                'dice' => $this->getDice($diceNumber),
                'inTokyo' => $this->inTokyo($playerId),
            ];
        }

        return $cardsArg + $diceArg;
    }

    function argPsychicProbeRollDie() {
        $psychicProbeIntervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);
        $activePlayerId = $psychicProbeIntervention->activePlayerId;

        $diceNumber = $this->getDiceNumber($activePlayerId);
        return [
            'dice' => $this->getDice($diceNumber),
            'inTokyo' => $this->inTokyo($activePlayerId),
        ];
    }

    function argResolveHeartDiceAction() {
        $playerId = self::getActivePlayerId();

        $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
        $diceCount = $diceCounts[4];

        if ($diceCount > 0) {  

            $diceNumber = $this->getDiceNumber($playerId);
            $dice = $this->getDice($diceNumber);
    
            $selectHeartDiceUseArg = $this->getSelectHeartDiceUse($playerId);  

            $canSelectHeartDiceUse = false;
            if ($this->inTokyo($playerId)) {
                $canSelectHeartDiceUse = $selectHeartDiceUseArg['hasHealingRay'];
            } else {
                $canSelectHeartDiceUse = $selectHeartDiceUseArg['hasHealingRay'] || $selectHeartDiceUseArg['shrinkRayTokens'] > 0 || $selectHeartDiceUseArg['poisonTokens'] > 0;
            }

            $diceArg = $canSelectHeartDiceUse ? [
                'dice' => $dice,
                'inTokyo' => $this->inTokyo($playerId),
            ] : [ 'skipped' => true ];
    
            return $selectHeartDiceUseArg + $diceArg;
        }
        return [ 'skipped' => true ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stChangeDie() {
        $playerId = self::getActivePlayerId();

        $cards = $this->getChangeDieCards($playerId);

        if (!$this->canChangeDie($cards)) {
            $this->gamestate->nextState('resolve');
        }
    }

    function stPsychicProbeRollDie() {
        $this->stIntervention(PSYCHIC_PROBE_INTERVENTION);
    }

    function stResolveDice() {
        self::DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");

        $playerId = self::getActivePlayerId();
        $playerInTokyo = $this->inTokyo($playerId);
        $dice = $this->getDice($this->getDiceNumber($playerId));

        $diceStr = '';
        foreach($dice as $idie) {
            $diceStr .= $this->getDieFaceLogName($idie->value);
        }

        self::notifyAllPlayers("resolvePlayerDice", clienttranslate('${player_name} resolve dice ${dice}'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'dice' => $diceStr,
        ]);

        $smashTokyo = false;

        $diceCounts = [];
        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $diceCounts[$diceFace] = count(array_values(array_filter($dice, function($dice) use ($diceFace) { return $dice->value == $diceFace; })));
        }

        $addedSmashes = 0;
        $cardsAddingSmashes = [];

        // acid attack
        $countAcidAttack = $this->countCardOfType($playerId, ACID_ATTACK_CARD);
        if ($countAcidAttack > 0) {
            $diceCounts[6] += $countAcidAttack;
            $addedSmashes += $countAcidAttack;

            for ($i=0; $i<$countAcidAttack; $i++) { $cardsAddingSmashes[] = ACID_ATTACK_CARD; }
        }

        // burrowing
        if ($playerInTokyo) {
            $countBurrowing = $this->countCardOfType($playerId, BURROWING_CARD);
            if ($countBurrowing > 0) {
                $diceCounts[6] += $countBurrowing;
                $addedSmashes += $countBurrowing;

                for ($i=0; $i<$countBurrowing; $i++) { $cardsAddingSmashes[] = BURROWING_CARD; }
            }
        }

        // poison quills
        if ($diceCounts[2] >= 3) {
            $countPoisonQuills = $this->countCardOfType($playerId, POISON_QUILLS_CARD);
            if ($countPoisonQuills > 0) {
                $diceCounts[6] += 2 * $countPoisonQuills;
                $addedSmashes += 2 * $countPoisonQuills;
                
                for ($i=0; $i<$countPoisonQuills; $i++) { $cardsAddingSmashes[] = POISON_QUILLS_CARD; }
            }
        }

        if ($diceCounts[6] >= 1) {
            // spiked tail
            // TOCHECK can Spiked Tail be chained with Acid attack ? Considered yes
            $countSpikedTail = $this->countCardOfType($playerId, SPIKED_TAIL_CARD);
            if ($countSpikedTail > 0) {
                $diceCounts[6] += $countSpikedTail;
                $addedSmashes += $countSpikedTail;
                
                for ($i=0; $i<$countSpikedTail; $i++) { $cardsAddingSmashes[] = SPIKED_TAIL_CARD; }
            }

            // urbavore
            // TOCHECK can Urbavore be chained with Acid attack ? Considered yes
            if ($playerInTokyo) {
                $countUrbavore = $this->countCardOfType($playerId, URBAVORE_CARD);
                if ($countUrbavore > 0) {
                    $diceCounts[6] += $countUrbavore;
                    $addedSmashes += $countUrbavore;
                
                    for ($i=0; $i<$countUrbavore; $i++) { $cardsAddingSmashes[] = URBAVORE_CARD; }
                }
            }
        }

        if ($addedSmashes > 0) {
            $diceStr = '';
            for ($i=0; $i<$addedSmashes; $i++) { 
                $diceStr .= $this->getDieFaceLogName(6); 
            }
            
            $cardNames = array_map(function($cardType) { return $this->getCardName($cardType); }, $cardsAddingSmashes);
            $cardNamesStr = implode(', ', $cardNames);

            self::notifyAllPlayers("resolvePlayerDice", clienttranslate('${player_name} adds ${dice} with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
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
                // TOCHECK we ignore in/out of tokyo ? Considered Yes
                $leftPlayerId = $playersIds[($playerIndex + 1) % $playerCount];
                $rightPlayerId = $playersIds[($playerIndex + $playerCount - 1) % $playerCount];

                if ($leftPlayerId != $playerId) {
                    $fireBreathingDamages[] = new Damage($leftPlayerId, $countFireBreathing, $playerId, FIRE_BREATHING_CARD);
                }
                if ($rightPlayerId != $playerId && $rightPlayerId != $leftPlayerId) {
                    $fireBreathingDamages[] = new Damage($rightPlayerId, $countFireBreathing, $playerId, FIRE_BREATHING_CARD);
                }
            }
        }

        $this->setGlobalVariable(FIRE_BREATHING_DAMAGES, $fireBreathingDamages);
        $this->setGlobalVariable(DICE_COUNTS, $diceCounts);

        $this->gamestate->nextState('next');
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

            // TOCHECK remove Shrink Ray & Poison tokens is impossible in Tokyo, but healing other players (even if other player is in Tokyo ?) ? Considered Yes and Yes
            if ($this->inTokyo($playerId)) {
                $canSelectHeartDiceUse = $selectHeartDiceUse['hasHealingRay'] && count($selectHeartDiceUse['healablePlayers']) > 0;
            } else {
                $canSelectHeartDiceUse = ($selectHeartDiceUse['hasHealingRay'] && count($selectHeartDiceUse['healablePlayers']) > 0) || $selectHeartDiceUse['shrinkRayTokens'] > 0 || $selectHeartDiceUse['poisonTokens'] > 0;
            }
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
        $this->resolveHealthDice($playerId, $diceCount);
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

        $diceCount = $diceCounts[6];

        $redirects = null;
        $smashTokyo = null;
        $cancelDamageEndState = null;

        if ($diceCount > 0) {
            $smashTokyo = !$this->inTokyo($playerId) && count($this->getPlayersIdsInTokyo()) > 0;
            $redirects = $this->resolveSmashDice($playerId, $diceCount);
        } else {
            $fireBreathingDamages = $this->getGlobalVariable(FIRE_BREATHING_DAMAGES, true);

            $smashTokyo = false;
            foreach($fireBreathingDamages as $fireBreathingDamage) {
                if ($this->inTokyo($fireBreathingDamage->playerId)) {
                    $smashTokyo = true;
                    break;
                }
            }

            $cancelDamageEndState = $smashTokyo ? "smashes" : "enterTokyo";
            $redirects = $this->resolveDamages($fireBreathingDamages, $cancelDamageEndState);
        }
        
        if (!$redirects) {
            $this->gamestate->nextState($smashTokyo ? "smashes" : "enterTokyo");
        }
    }
}
