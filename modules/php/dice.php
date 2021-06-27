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

    function fixDices() {
        self::DbQuery( "UPDATE dice SET `rolled` = false");
    }

    function getDiceNumber(int $playerId) {
        $remove = intval($this->getGameStateValue(FREEZE_TIME_CURRENT_TURN)) + $this->getPlayerShrinkRayTokens($playerId);

        return max(6 + $this->countExtraHead($playerId) - $remove, 0);
    }

    function resolveNumberDice(int $playerId, int $number, int $diceCount) {
        // number
        if ($diceCount >= 3) {
            $points = $number + $diceCount - 3;

            $this->applyGetPoints($playerId, $points, -1);

            self::incStat($points, 'pointsWonWith'.$number.'Dice', $playerId);

            self::notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} gains ${deltaPoints}[Star] with ${dice_value} dice'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
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
                    $this->incGameStateValue(FREEZE_TIME_MAX_TURNS, 1);
                }
                
            }
        }
    }

    function resolveHealthDice(int $playerId, int $diceCount) {
        if ($this->inTokyo($playerId)) {
            self::notifyAllPlayers( "resolveHealthDiceInTokyo", clienttranslate('${player_name} gains no [Heart] (player in Tokyo)'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
            ]);
        } else {
            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth) {
                $this->applyGetHealth($playerId, $diceCount, -1);
                $newHealth = $this->getPlayerHealth($playerId);

                self::notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} gains ${deltaHealth} [Heart]'), [
                    'playerId' => $playerId,
                    'player_name' => $this->getPlayerName($playerId),
                    'health' => $newHealth,
                    'deltaHealth' => $diceCount,
                ]);
            }
        }
    }

    function resolveEnergyDice(int $playerId, int $diceCount) {
        $this->applyGetEnergy($playerId, $diceCount, -1);

        self::notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} gains ${deltaEnergy} [Energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'deltaEnergy' => $diceCount,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);
    }

    
    function resolveSmashDice(int $playerId, int $diceCount) { // return nextState / null
        // Nova breath
        $countNovaBreath = $this->countCardOfType($playerId, NOVA_BREATH_CARD);

        $message = null;
        $smashedPlayersIds = null;
        $inTokyo = $this->inTokyo($playerId);
        $nextState = "enterTokyo";

        $damages = [];

        if ($countNovaBreath) {
            $message = clienttranslate('${player_name} smashes all other Monsters with ${number} [diceSmash]');
            $smashedPlayersIds = $this->getOtherPlayersIds($playerId);
        } else {
            $smashTokyo = !$inTokyo;
            $message = $smashTokyo ? 
                clienttranslate('${player_name} smashes Monsters in Tokyo with ${number} [diceSmash]') :
                clienttranslate('${player_name} smashes Monsters outside Tokyo with ${number} [diceSmash]');
            $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);
        }

        $fireBreathingDamages = $this->getGlobalVariable(FIRE_BREATHING_DAMAGES, true);

        $jetsDamages = [];
        $smashedPlayersInTokyo = [];
        foreach($smashedPlayersIds as $smashedPlayerId) {
            $smashedPlayerIsInTokyo = $this->inTokyo($smashedPlayerId);
            if ($smashedPlayerIsInTokyo) {
                $smashedPlayersInTokyo[] = $smashedPlayerId;
            }

            $fireBreathingDamage = array_key_exists($smashedPlayerId, $fireBreathingDamages) ? $fireBreathingDamages[$smashedPlayerId] : 0;
            $damageAmount = $diceCount + $fireBreathingDamage;

            // Jets
            $countJets = $this->countCardOfType($smashedPlayerId, JETS_CARD);

            if ($countJets > 0 && $smashedPlayerIsInTokyo) {                
                $jetsDamages[] = new Damage($smashedPlayerId, $damageAmount, $playerId, 0);
            } else {
                $damages[] = new Damage($smashedPlayerId, $damageAmount, $playerId, 0);
            }
        }

        if (count($smashedPlayersInTokyo) > 0) {
            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, $smashedPlayersInTokyo);
            $nextState = "smashes";
        }

        $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);      

        self::notifyAllPlayers("resolveSmashDice", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'number' => $diceCount,
            'smashedPlayersIds' => $smashedPlayersIds,
        ]);

        // Alpha Monster
        $countAlphaMonster = $this->countCardOfType($playerId, ALPHA_MONSTER_CARD);
        if ($countAlphaMonster > 0) {
            $this->applyGetPoints($playerId, $countAlphaMonster, ALPHA_MONSTER_CARD);
        }

        // Shrink Ray
        $countShrinkRay = $this->countCardOfType($playerId, SHRINK_RAY_CARD);
        if ($countShrinkRay > 0) {
            foreach($smashedPlayersIds as $smashedPlayerId) {
                $this->applyGetShrinkRayToken($smashedPlayerId, $countShrinkRay);
            }
        }

        // Poison Spit
        $countPoisonSpit = $this->countCardOfType($playerId, POISON_SPIT_CARD);
        if ($countPoisonSpit > 0) {
            foreach($smashedPlayersIds as $smashedPlayerId) {
                $this->applyGetPoisonToken($smashedPlayerId, $countPoisonSpit);
            }
        }

        // fire breathing
        foreach ($fireBreathingDamages as $damagePlayerId => $fireBreathingDamage) {
            self::notifyAllPlayers("fireBreathingExtraDamage", '${player_name} loses ${number} extra [Heart] with ${card_name}', [
                'playerId' => $damagePlayerId,
                'player_name' => $this->getPlayerName($damagePlayerId),
                'number' => 1,
                'card_name' => FIRE_BREATHING_CARD,
            ]);

            // we add damage only if it's not already counted in smashed players
            if (array_search($damagePlayerId, $smashedPlayersIds) === false) {
                $damages[] = new Damage($damagePlayerId, $fireBreathingDamage, $damagePlayerId, 0);
            }
        }

        if (count($damages) > 0) {
            if ($this->resolveDamages($damages, $nextState)) {
                return null; // no redirect on stResolveSmashDice, handled by resolveDamages
            }
        }
        return $nextState;
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
        $hasPlotTwist = $this->countCardOfType($playerId, PLOT_TWIST_CARD) > 0;
        // Stretchy
        $hasStretchy = $this->countCardOfType($playerId, STRETCHY_CARD) > 0 && $this->getPlayerEnergy($playerId) >= 2;
        
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
  	
    function rethrowDice(string $diceIds) {
        $playerId = self::getActivePlayerId();
        self::DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");
        self::DbQuery("UPDATE dice SET `locked` = false, `rolled` = true where `dice_id` IN ($diceIds)");

        $diceCount = count(explode(',', $diceIds));
        self::incStat($diceCount, 'rethrownDice', $playerId);

        $this->throwDice($playerId);

        $throwNumber = intval(self::getGameStateValue('throwNumber')) + 1;
        self::setGameStateValue('throwNumber', $throwNumber);

        $this->gamestate->nextState('rethrow');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
  	
    public function actionRethrowDice(string $diceIds) {
        $this->checkAction('rethrow');

        $playerId = self::getActivePlayerId();

        $throwNumber = intval(self::getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->getThrowNumber($playerId);

        if ($throwNumber >= $maxThrowNumber) {
            throw new \Error("You can't throw dices (max throw)");
        }
        if (!$diceIds || $diceIds == '') {
            throw new \Error("No selected dice to throw");
        }

        $this->rethrowDice($diceIds);
    }

    public function rethrow3() {
        $this->checkAction('rethrow3');

        $playerId = self::getActivePlayerId();
        $die = $this->getFirst3Dice($this->getDiceNumber($playerId));

        if ($die == null) {
            throw new \Error('No 3 die');
        }

        $newValue = bga_rand(1, 6);
        self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$die->id);
        self::DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$die->id);

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        self::notifyAllPlayers('rethrow3', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => BACKGROUND_DWELLER_CARD,
            'dieId' => $die->id,
            'die_face_before' => $this->getDieFaceLogName($die->value),
            'die_face_after' => $this->getDieFaceLogName($newValue),
        ]);

        $this->gamestate->nextState('rethrow');
    }

    public function rethrow3camouflage() {
        $this->checkAction('rethrow3camouflage');

        $playerId = self::getCurrentPlayerId();

        $countBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD);
        if ($countBackgroundDweller == 0) {
            throw new \Error('No Background Dweller card');
        }

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        $dice = $intervention->playersUsedDice->{$playerId}->dice;
        $rethrown = false;
        for ($i=0; $i<count($dice); $i++) {
            if (!$rethrown && $dice[$i]->value == 3) {
                $dice[$i]->value = bga_rand(1, 6);
                $dice[$i]->rolled = true;
                $rethrown = true;
            } else {                
                $dice[$i]->rolled = false;
            }
        }

        $this->endThrowCamouflageDice($playerId, $intervention, $dice);
    }

    public function changeDie(int $id, int $value, int $cardType) {
        $this->checkAction('changeDie');

        $playerId = self::getCurrentPlayerId();

        $die = $this->getDieById($id);

        if ($die == null) {
            throw new \Error('No selected die');
        }

        self::DbQuery("UPDATE dice SET `dice_value` = ".$value." where `dice_id` = ".$id);

        if ($cardType == HERD_CULLER_CARD) {
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
        } else if ($cardType == PLOT_TWIST_CARD) {
            $cards = $this->getCardsOfType($playerId, PLOT_TWIST_CARD);
            // we remove Plot Twist and Mimic if user mimicked it
            $this->removeCards($playerId, $cards);
        } else if ($cardType == STRETCHY_CARD) {
            $this->applyLoseEnergyIgnoreCards($playerId, 2, 0);
        }

        $inTokyo = $this->inTokyo(self::getActivePlayerId());

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        self::notifyAllPlayers("changeDie", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $cardType,
            'dieId' => $die->id,
            'inTokyo' => $inTokyo,
            'toValue' => $value,
            'die_face_before' => $this->getDieFaceLogName($die->value),
            'die_face_after' => $this->getDieFaceLogName($value),
        ]);

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
        $this->checkAction('psychicProbeRollDie');

        $intervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);
        $playerId = $intervention->remainingPlayersId[0];

        if ($this->countCardOfType($playerId, PSYCHIC_PROBE_CARD) == 0) {
            throw new \Error('No Psychic Probe card');
        }

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
            $currentPlayerCards = array_values(array_filter($intervention->cards, function ($card) use ($playerId) { return $card->location_arg == $playerId; }));
            if (count($currentPlayerCards) > 0) {

                // we remove Plot Twist and Mimic if user mimicked it
                foreach($currentPlayerCards as $card) {
                    $this->removeCard($playerId, $card);

                    if ($card->type == PSYCHIC_PROBE_CARD) { // real Psychic Probe
                        // in case we had a mimic player to play after current player, we clear array because he can't anymore 
                        $intervention->remainingPlayersId = []; // intervention will be saved on setInterventionNextState
                    }
                }                
            }
        }

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        if ($value == 4) {
            $message .= ' ' . clienttranslate('(${card_name} is discarded)');
        }
        self::notifyAllPlayers("changeDie", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => PSYCHIC_PROBE_CARD,
            'dieId' => $die->id,
            'toValue' => $value,
            'roll' => true,
            'die_face_before' => $this->getDieFaceLogName($die->value),
            'die_face_after' => $this->getDieFaceLogName($value),
        ]);

        $this->setInterventionNextState(PSYCHIC_PROBE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    public function goToChangeDie($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('goToChangeDie');
        }

        $playerId = self::getActivePlayerId();

        $playersWithPsychicProbe = $this->getPlayersWithPsychicProbe($playerId);

        $this->fixDices();

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
        $this->checkAction('psychicProbeSkip');

        $playerId = self::getCurrentPlayerId();

        $intervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);
        $this->setInterventionNextState(PSYCHIC_PROBE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function applyHeartDieChoices(array $heartDieChoices) {
        $this->checkAction('applyHeartDieChoices');

        $playerId = self::getActivePlayerId();

        $heal = 0;
        $healPlayer = [];

        $healPlayerCount = 0;
        foreach ($heartDieChoices as $heartDieChoice) {
            if ($heartDieChoice->action == 'heal-player') {
                $healPlayerCount++;
            }
        }
        if ($healPlayerCount > 0 && $this->countCardOfType($playerId, HEALING_RAY_CARD) == 0) {
            throw new \Error('No Healing Ray card');
        }
        
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
            $this->applyGetHealth($healPlayerId, $healNumber, 0);
            
            $playerEnergy = $this->getPlayerEnergy($healPlayerId);
            $theoricalEnergyLoss = $healNumber * 2;
            $energyLoss = min($playerEnergy, $theoricalEnergyLoss);
            
            $this->applyLoseEnergy($healPlayerId, $energyLoss, 0);
            $this->applyGetEnergy($playerId, $energyLoss, 0);

            self::notifyAllPlayers("resolveHealingRay", clienttranslate('${player_name2} gains ${healNumber} [Heart] with ${card_name} and pays ${player_name} ${energy} [Energy]'), [
                'player_name' => $this->getPlayerName($playerId),
                'player_name2' => $this->getPlayerName($healPlayerId),
                'energy' => $energyLoss,
                'healNumber' => $healNumber,
                'card_name' => HEALING_RAY_CARD
            ]);
        }

        $this->gamestate->nextState('next');
    }

    public function resolveDice() {
        $this->checkAction('resolve');

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

        $smokeCloudsTokens = 0;
        $smokeCloudCards = $this->getCardsOfType($playerId, SMOKE_CLOUD_CARD); // Smoke Cloud
        foreach($smokeCloudCards as $smokeCloudCard) {
            $smokeCloudsTokens += $smokeCloudCard->tokens;
        }
        $hasSmokeCloud = $smokeCloudsTokens > 0;

        $hasActions = $throwNumber < $maxThrowNumber || ($hasEnergyDrink && $playerEnergy >= 1) || $hasDice3 || $hasSmokeCloud;
    
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
            'hasSmokeCloud' => $hasSmokeCloud,
            'hasActions' => $hasActions,
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

            $inTokyo = $this->inTokyo($playerId);

            $canSelectHeartDiceUse = $selectHeartDiceUseArg['hasHealingRay'] || (($selectHeartDiceUseArg['shrinkRayTokens'] > 0 || $selectHeartDiceUseArg['poisonTokens'] > 0) && !$inTokyo);

            $diceArg = $canSelectHeartDiceUse ? [
                'dice' => $dice,
                'inTokyo' => $inTokyo,
            ] : [ 'skipped' => true ];
    
            return $selectHeartDiceUseArg + $diceArg;
        }
        return [ 'skipped' => true ];
    }

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

        $cards = $this->getChangeDieCards($playerId);

        if (!$this->canChangeDie($cards)) {
            $this->gamestate->nextState('resolve');
        }
    }

    function stPsychicProbeRollDie() {
        $this->stIntervention(PSYCHIC_PROBE_INTERVENTION);
    }

    function stResolveDice() {
        $playerId = self::getActivePlayerId();
        self::giveExtraTime($playerId);

        self::DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");

        $playerInTokyo = $this->inTokyo($playerId);
        $dice = $this->getDice($this->getDiceNumber($playerId));
        $diceValues = array_map(function($idie) { return $idie->value; }, $dice);
        sort($diceValues);

        $diceStr = '';
        foreach($diceValues as $dieValue) {
            $diceStr .= $this->getDieFaceLogName($dieValue);
        }

        self::notifyAllPlayers("resolvePlayerDice", clienttranslate('${player_name} resolves dice ${dice}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'dice' => $diceStr,
        ]);

        $smashTokyo = false;

        $diceCounts = [];
        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $diceCounts[$diceFace] = count(array_values(array_filter($diceValues, function($dice) use ($diceFace) { return $dice == $diceFace; })));
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
            $countSpikedTail = $this->countCardOfType($playerId, SPIKED_TAIL_CARD);
            if ($countSpikedTail > 0) {
                $diceCounts[6] += $countSpikedTail;
                $addedSmashes += $countSpikedTail;
                
                for ($i=0; $i<$countSpikedTail; $i++) { $cardsAddingSmashes[] = SPIKED_TAIL_CARD; }
            }

            // urbavore
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
            
            $cardNamesStr = implode(', ', $cardsAddingSmashes);

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

        $diceCount = $diceCounts[6];

        $nextState = 'enterTokyo';
        $smashTokyo = false;

        if ($diceCount > 0) {
            $nextState = $this->resolveSmashDice($playerId, $diceCount);
        }
        
        if ($nextState != null) {
            $this->gamestate->nextState($nextState);
        }
    }
}
