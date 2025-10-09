<?php

namespace KOT\States;

use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\Actions\Types\StringParam;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

trait DiceActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
  	
    public function applyRerollDie(int $playerId, object $die, array $diceIds, int $cardName) {
        $this->DbQuery("UPDATE dice SET `locked` = false");
        if (count($diceIds) > 0) {
            $this->DbQuery("UPDATE dice SET `locked` = true where `dice_id` IN (".implode(',', $diceIds).")");
        }

        $oldValue = $die->value;
        $newValue = bga_rand(1, 6);
        $die->value = $newValue;
        $this->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$die->id);
        $this->DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$die->id);

        if (!$this->canRerollSymbol($playerId, getDieFace($die))) {
            $die->locked = true;
            $this->DbQuery( "UPDATE dice SET `locked` = true where `dice_id` = ".$die->id );
        }

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        $this->notifyAllPlayers('rethrow3', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => $cardName,
            'dieId' => $die->id,
            'die_face_before' => $this->getDieFaceLogName($oldValue, 0),
            'die_face_after' => $this->getDieFaceLogName($newValue, 0),
        ]);

        $this->goToState(ST_PLAYER_THROW_DICE);
    }

    public function actRethrow3Camouflage() {
        $playerId = $this->getCurrentPlayerId();

        $countBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD);
        if ($countBackgroundDweller == 0) {
            throw new \BgaUserException('No Background Dweller card');
        }

        $intervention = $this->getDamageIntervention();

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

    public function actRethrow3PsychicProbe() {
        $playerId = $this->getCurrentPlayerId();

        $countBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD);
        if ($countBackgroundDweller == 0) {
            throw new \BgaUserException('No Background Dweller card');
        }

        $intervention = $this->getGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);

        $die = $intervention->lastRolledDie;
        if ($die == null) {
            throw new \BgaUserException('No 3 die');
        }

        $newValue = bga_rand(1, 6);
        $this->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$die->id);
        $this->DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$die->id);

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        $this->notifyAllPlayers('rethrow3', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => BACKGROUND_DWELLER_CARD,
            'dieId' => $die->id,
            'die_face_before' => $this->getDieFaceLogName($die->value, 0),
            'die_face_after' => $this->getDieFaceLogName($newValue, 0),
        ]);

        $this->endChangeActivePlayerDie($intervention, $playerId, $die, BACKGROUND_DWELLER_CARD, $newValue);
    }

    public function actChangeActivePlayerDie(int $id) {
        $intervention = $this->getGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
        $playerId = $intervention->remainingPlayersId[0];

        $unusedCards = $this->getUnusedChangeActivePlayerDieCards($playerId);
        if (count($unusedCards) == 0) {
            throw new \BgaUserException('No card allowing to throw a die from active player');
        }
        $usedCardOnThisTurn = $unusedCards[0];

        $die = $this->getDieById($id);
        $value = bga_rand(1, 6);
        $this->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$id);
        $this->DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$id);
        $this->setUsedCard($usedCardOnThisTurn->id);

        $this->endChangeActivePlayerDie($intervention, $playerId, $die, $usedCardOnThisTurn->type, $value);
    }

    public function endChangeActivePlayerDie(object $intervention, int $playerId, object $die, int $cardType, int $value) {
        $discardBecauseOfHeart = $die->type == 0 && $value == 4 && ($cardType == PSYCHIC_PROBE_CARD || $cardType == MIMIC_CARD);

        if ($discardBecauseOfHeart) {
            $currentPlayerCards = array_values(array_filter($intervention->cards, fn($card) => $card->location_arg == $playerId));
            if (count($currentPlayerCards) > 0) {

                // we remove Psychic Probe and Mimic if user mimicked it
                foreach($currentPlayerCards as $card) {
                    if ($card->type == PSYCHIC_PROBE_CARD || $card->type == MIMIC_CARD) {
                        $this->removeCard($playerId, $card, false, true);
                    }

                    if ($card->type == PSYCHIC_PROBE_CARD) { // real Psychic Probe

                        $mimicCard = $this->array_find($intervention->cards, fn($card) => $card->type == MIMIC_CARD);
                        if ($mimicCard != null) {
                            $this->setUsedCard($mimicCard->id);
                        }

                        if ($mimicCard != null && count(array_filter($intervention->cards, fn($card) => $card->location_arg == $mimicCard->location_arg)) == 1) {
                            // in case we had a mimic player to play after current player, we remove him from array because he can't anymore 
                            // (if he only have mimic as card for this state)
                            $intervention->remainingPlayersId = array_values(array_filter($intervention->remainingPlayersId, fn($remainingId) => $remainingId != $mimicCard->location_arg));
                        }

                        // TODO mimicTile
                    }
                }                
            }
        }

        if ($cardType == 3000 + HEART_OF_THE_RABBIT_EVOLUTION) {
            $heartOfTheRabbitEvolutions = $this->getEvolutionsOfType($playerId, HEART_OF_THE_RABBIT_EVOLUTION, false, true);
            $this->applyPlayEvolution($playerId, $heartOfTheRabbitEvolutions[0]);
            // we store evolution as played with type_arg. To know if the evolution is in the discard after being played, or just discarded when choosing between 2 new evolutions
            $this->setEvolutionTokens($playerId, $heartOfTheRabbitEvolutions[0], 1, true);
        }

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        if ($discardBecauseOfHeart) {
            $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after} (${card_name} is discarded)');
        }

        $stayForRethrow3 = $die->type == 0 && $value == 3 && $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
        $oldValue = $die->value;

        $die->value = $value;
        $intervention->lastRolledDie = $die;

        if ($die->type == 0 && $value == 3) {
            $this->setGameStateValue(PSYCHIC_PROBE_ROLLED_A_3, $die->id);
        }

        $this->notifyAllPlayers("changeDie", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => $cardType === FLUXLING_WICKEDNESS_TILE ? 2000 + $cardType : $cardType,
            'dieId' => $die->id,
            'toValue' => $value,
            'roll' => true,
            'die_face_before' => $this->getDieFaceLogName($oldValue, $die->type),
            'die_face_after' => $this->getDieFaceLogName($value, $die->type),
            'psychicProbeRollDieArgs' => $stayForRethrow3 ? $this->argChangeActivePlayerDie($intervention) : null,
            'canHealWithDice' => $this->canHealWithDice($this->getActivePlayerId()),
        ]);

        $unusedCards = $this->getUnusedChangeActivePlayerDieCards($playerId);

        if ($stayForRethrow3 || count($unusedCards) > 0) {
            $this->setGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, $intervention);
        } else {
            $this->setInterventionNextState(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

    public function actChangeActivePlayerDieSkip() {
        $playerId = $this->getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function applyChangeActivePlayerDieSkip(int $playerId) {
        $intervention = $this->getGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
        $this->setInterventionNextState(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, 'next', $this->getPsychicProbeInterventionEndState($intervention), $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function actApplyHeartDieChoices(#[JsonParam(associative: false)] array $heartDieChoices) {
        $playerId = $this->getActivePlayerId();

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
            $this->applyGetHealth($healPlayerId, $healNumber, 0, $playerId);
            
            $playerEnergy = $this->getPlayerEnergy($healPlayerId);
            $theoricalEnergyLoss = $healNumber * 2;
            $energyLoss = min($playerEnergy, $theoricalEnergyLoss);
            
            $this->applyLoseEnergy($healPlayerId, $energyLoss, 0);
            $this->applyGetEnergy($playerId, $energyLoss, 0);

            $this->notifyAllPlayers("resolveHealingRay", clienttranslate('${player_name2} gains ${healNumber} [Heart] with ${card_name} and pays ${player_name} ${energy} [Energy]'), [
                'player_name' => $this->getPlayerNameById($playerId),
                'player_name2' => $this->getPlayerNameById($healPlayerId),
                'energy' => $energyLoss,
                'healedPlayerId' => $healPlayerId,
                'healNumber' => $healNumber,
                'card_name' => HEALING_RAY_CARD
            ]);
        }

        $this->gamestate->nextState('next');
    }

    public function actApplySmashDieChoices(#[JsonParam(associative: false)] $smashDieChoices) {
        $activePlayerId = $this->getActivePlayerId();

        $playersSmashesWithReducedDamage = [];

        foreach($smashDieChoices as $playerId => $smashDieChoice) {
            if ($smashDieChoice == 'steal') {
                $this->applyGiveSymbols([0, 5], $playerId, $activePlayerId, 3000 + PLAY_WITH_YOUR_FOOD_EVOLUTION);
                $playersSmashesWithReducedDamage[$playerId] = 2;
            }
        }

        $this->resolveSmashDiceState($playersSmashesWithReducedDamage);
    }

  	
    public function actStayInHibernation() {
        $playerId = $this->getActivePlayerId();

        $this->applyResolveDice($playerId);
        
        $this->goToState($this->redirectAfterResolveDice());
    }
  	
    public function actLeaveHibernation() {
        $playerId = $this->getActivePlayerId();

        $cards = $this->powerCards->getCardsOfType(HIBERNATION_CARD);
        $this->removeCards($playerId, $cards);

        $this->applyResolveDice($playerId);

        $this->goToState($this->redirectAfterResolveDice());
    }

}
