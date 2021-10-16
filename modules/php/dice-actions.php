<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Dice;
use KOT\Objects\PsychicProbeIntervention;
use KOT\Objects\Damage;

trait DiceActionTrait {

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
            throw new \BgaUserException("You can't throw dices (max throw)");
        }
        if (!$diceIds || $diceIds == '') {
            throw new \BgaUserException("No selected dice to throw");
        }

        $this->rethrowDice($diceIds);
    }

    public function rethrow3(string $diceIds = null) {
        $this->checkAction('rethrow3');

        if ($diceIds !== null) {
            self::DbQuery("UPDATE dice SET `locked` = false");
            if ($diceIds != '') {
                self::DbQuery("UPDATE dice SET `locked` = true where `dice_id` IN ($diceIds)");
            }
        }

        $playerId = self::getActivePlayerId();
        $die = $this->getFirst3Dice($this->getDiceNumber($playerId));

        if ($die == null) {
            throw new \BgaUserException('No 3 die');
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
            throw new \BgaUserException('No Background Dweller card');
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

        $this->endThrowCamouflageDice($playerId, $intervention, $dice, false);
    }

    public function rethrow3psychicProbe() {
        $this->checkAction('rethrow3psychicProbe');

        $playerId = self::getCurrentPlayerId();

        $countBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD);
        if ($countBackgroundDweller == 0) {
            throw new \BgaUserException('No Background Dweller card');
        }

        $intervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);

        $die = $intervention->lastRolledDie;
        if ($die == null) {
            throw new \BgaUserException('No 3 die');
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

        $this->endPsychicProbeRollDie($intervention, $playerId, $die, $newValue);
    }

    public function rethrow3changeDie() {
        $this->checkAction('rethrow3changeDie');

        $playerId = self::getActivePlayerId();
        $dieId = intval(self::getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3));

        if ($dieId == 0) {
            throw new \BgaUserException('No 3 die');
        }

        self::setGameStateValue(PSYCHIC_PROBE_ROLLED_A_3, 0);

        $newValue = bga_rand(1, 6);
        self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
        self::DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$dieId);

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        self::notifyAllPlayers('rethrow3changeDie', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => BACKGROUND_DWELLER_CARD,
            'dieId' => $dieId,
            'die_face_before' => $this->getDieFaceLogName(3),
            'die_face_after' => $this->getDieFaceLogName($newValue),
        ]);

        $psychicProbeIntervention = $this->getPsychicProbeIntervention($playerId);
        if ($psychicProbeIntervention != null) {
            $this->setGlobalVariable(PSYCHIC_PROBE_INTERVENTION, $psychicProbeIntervention);
            $this->gamestate->nextState('changeDieWithPsychicProbe');
        } else {
            $this->gamestate->nextState('changeDie');
        }
    }

    public function changeDie(int $id, int $value, int $cardType) {
        $this->checkAction('changeDie');

        $playerId = self::getCurrentPlayerId();

        $die = $this->getDieById($id);

        if ($die == null) {
            throw new \BgaUserException('No selected die');
        }

        self::DbQuery("UPDATE dice SET `rolled` = false, `dice_value` = ".$value." where `dice_id` = ".$id);

        if ($cardType == HERD_CULLER_CARD) {
            $usedCards = $this->getUsedCard();
            $herdCullerCards = $this->getCardsOfType($playerId, HERD_CULLER_CARD);
            $usedCardOnThisTurn = null;
            foreach($herdCullerCards as $herdCullerCard) {
                if (!in_array($herdCullerCard->id, $usedCards)) {
                    $usedCardOnThisTurn = $herdCullerCard->id;
                }
            }
            if ($usedCardOnThisTurn == null) {
                throw new \BgaUserException('No unused Herd Culler for this player');
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

        // psychic probe should not be called after change die (or only after a Background Dweller roll ?)
        /*$psychicProbeIntervention = $this->getPsychicProbeIntervention($playerId);
        if ($psychicProbeIntervention != null) {
            $this->setGlobalVariable(PSYCHIC_PROBE_INTERVENTION, $psychicProbeIntervention);
            $this->gamestate->nextState('changeDieWithPsychicProbe');
        } else {
            $this->gamestate->nextState('changeDie');
        }*/
        $this->gamestate->nextState('changeDie');
    }

    public function psychicProbeRollDie(int $id) {
        $this->checkAction('psychicProbeRollDie');

        $intervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);
        $playerId = $intervention->remainingPlayersId[0];

        if ($this->countCardOfType($playerId, PSYCHIC_PROBE_CARD) == 0) {
            throw new \BgaUserException('No Psychic Probe card');
        }

        $die = $this->getDieById($id);
        $value = bga_rand(1, 6);
        self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$id);
        self::DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$id);
        
        $usedCards = $this->getUsedCard();
        $psychicProbeCards = $this->getCardsOfType($playerId, PSYCHIC_PROBE_CARD);
        $usedCardOnThisTurn = null;
        foreach($psychicProbeCards as $psychicProbeCard) {
            if (!in_array($psychicProbeCard->id, $usedCards)) {
                $usedCardOnThisTurn = $psychicProbeCard->id;
            }
        }
        if ($usedCardOnThisTurn == null) {
            throw new \BgaUserException('No unused Psychic Probe for this player');
        } else {
            $this->setUsedCard($usedCardOnThisTurn);
        }

        $this->endPsychicProbeRollDie($intervention, $playerId, $die, $value);
    }

    public function endPsychicProbeRollDie(object $intervention, int $playerId, object $die, int $value) {

        if ($value == 4) {
            $currentPlayerCards = array_values(array_filter($intervention->cards, function ($card) use ($playerId) { return $card->location_arg == $playerId; }));
            if (count($currentPlayerCards) > 0) {

                // we remove Plot Twist and Mimic if user mimicked it
                foreach($currentPlayerCards as $card) {
                    $this->removeCard($playerId, $card, false, true);

                    if ($card->type == PSYCHIC_PROBE_CARD) { // real Psychic Probe
                        // in case we had a mimic player to play after current player, we clear array because he can't anymore 
                        $intervention->remainingPlayersId = []; // intervention will be saved on setInterventionNextState
                    }
                }                
            }
        }

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        if ($value == 4) {
            $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after} (${card_name} is discarded)');
        }

        $stayForRethrow3 = $value == 3 && $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
        $oldValue = $die->value;

        $die->value = $value;
        $intervention->lastRolledDie = $die;

        if ($value == 3) {
            self::setGameStateValue(PSYCHIC_PROBE_ROLLED_A_3, $die->id);
        }

        self::notifyAllPlayers("changeDie", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => PSYCHIC_PROBE_CARD,
            'dieId' => $die->id,
            'toValue' => $value,
            'roll' => true,
            'die_face_before' => $this->getDieFaceLogName($oldValue),
            'die_face_after' => $this->getDieFaceLogName($value),
            'psychicProbeRollDieArgs' => $stayForRethrow3 ? $this->argPsychicProbeRollDie($intervention) : null,
        ]);

        if ($stayForRethrow3) {
            $this->setGlobalVariable(PSYCHIC_PROBE_INTERVENTION, $intervention);
        } else {
            $this->setInterventionNextState(PSYCHIC_PROBE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

    public function goToChangeDie($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('goToChangeDie');
        }

        $playerId = self::getActivePlayerId();

        $playersWithPsychicProbe = $this->getPlayersWithPsychicProbe($playerId);

        $this->fixDices();

        $psychicProbeIntervention = $this->getPsychicProbeIntervention($playerId);
        if ($psychicProbeIntervention != null) {
            $this->setGlobalVariable(PSYCHIC_PROBE_INTERVENTION, $psychicProbeIntervention);
            $this->gamestate->nextState('psychicProbe');
        } else {
            $this->gamestate->nextState('goToChangeDie');
        }
    }

    function psychicProbeSkip() {
        $this->checkAction('psychicProbeSkip');

        $playerId = self::getCurrentPlayerId();

        $this->applyPsychicProbeSkip($playerId);
    }

    function applyPsychicProbeSkip(int $playerId) {
        $intervention = $this->getGlobalVariable(PSYCHIC_PROBE_INTERVENTION);
        $this->setInterventionNextState(PSYCHIC_PROBE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function applyHeartDieChoices(array $heartDieChoices) {
        $this->checkAction('applyHeartDieChoices');

        $playerId = self::getActivePlayerId();

        $heal = 0;
        $removeShrinkRayToken = 0;
        $removePoisonToken = 0;
        $healPlayer = [];

        $healPlayerCount = 0;
        foreach ($heartDieChoices as $heartDieChoice) {
            if ($heartDieChoice->action == 'heal-player') {
                $healPlayerCount++;
            }
        }
        if ($healPlayerCount > 0 && $this->countCardOfType($playerId, HEALING_RAY_CARD) == 0) {
            throw new \BgaUserException('No Healing Ray card');
        }
        
        foreach ($heartDieChoices as $heartDieChoice) {
            switch ($heartDieChoice->action) {
                case 'heal':
                    $heal++;
                    break;
                case 'shrink-ray':
                    $removeShrinkRayToken++;
                    break;
                case 'poison':
                    $removePoisonToken++;
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

        if ($removeShrinkRayToken > 0) {
            $this->removeShrinkRayToken($playerId, $removeShrinkRayToken);
        }
        if ($removePoisonToken > 0) {
            $this->removePoisonToken($playerId, $removePoisonToken);
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
                'healedPlayerId' => $healPlayerId,
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

}
