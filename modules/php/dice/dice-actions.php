<?php

namespace KOT\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\Actions\Types\IntParam;
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
  	
    public function actRethrow(#[IntArrayParam] array $diceIds) {
        $playerId = $this->getActivePlayerId();

        $throwNumber = intval($this->getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->getRollNumber($playerId);

        if ($throwNumber >= $maxThrowNumber) {
            throw new \BgaUserException("You can't throw dices (max throw)");
        }
        if (!$diceIds || $diceIds == '') {
            throw new \BgaUserException("No selected dice to throw");
        }

        $this->rethrowDice($diceIds);
    }

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

    public function actRerollDie(int $id, #[IntArrayParam] array $diceIds) {
        $playerId = $this->getActivePlayerId();
        $die = $this->getDieById($id);

        if ($die == null) {
            throw new \BgaUserException('No die');
        }

        $formCard = $this->getFormCard($playerId);
        $this->setUsedCard($formCard->id);

        $this->applyRerollDie($playerId, $die, $diceIds, FORM_CARD);

    }

    public function actRethrow3(#[IntArrayParam] array $diceIds) {
        $playerId = $this->getActivePlayerId();
        $die = $this->getFirst3Die($playerId);

        if ($die == null) {
            throw new \BgaUserException('No 3 die');
        }

        $this->applyRerollDie($playerId, $die, $diceIds, BACKGROUND_DWELLER_CARD);
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

    public function actRethrow3ChangeDie() {
        $playerId = $this->getActivePlayerId();
        $dieId = intval($this->getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3));

        if ($dieId == 0) {
            throw new \BgaUserException('No 3 die');
        }

        $this->setGameStateValue(PSYCHIC_PROBE_ROLLED_A_3, 0);

        $newValue = bga_rand(1, 6);
        $this->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
        $this->DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$dieId);

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        $this->notifyAllPlayers('rethrow3changeDie', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => BACKGROUND_DWELLER_CARD,
            'dieId' => $dieId,
            'die_face_before' => $this->getDieFaceLogName(3, 0),
            'die_face_after' => $this->getDieFaceLogName($newValue, 0),
        ]);

        $changeActivePlayerDieIntervention = $this->getChangeActivePlayerDieIntervention($playerId);
        if ($changeActivePlayerDieIntervention != null) {
            $this->setGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, $changeActivePlayerDieIntervention);
            $this->gamestate->nextState('changeDieWithPsychicProbe');
        } else {
            $this->gamestate->nextState('changeDie');
        }
    }

    public function actChangeDie(int $id,int $value, #[IntParam(name: 'card')] int $cardType) {
        $playerId = $this->getCurrentPlayerId();

        $selectedDie = $this->getDieById($id);

        if ($selectedDie == null) {
            throw new \BgaUserException('No selected die');
        }

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
        } else if ($cardType == BIOFUEL_CARD) {
            if ($selectedDie->value != 4) {
                throw new \BgaUserException('You can only change a Heart die');
            }
        } else if ($cardType == SHRINKY_CARD) {
            if ($selectedDie->value != 2) {
                throw new \BgaUserException('You can only change a 2 die');
            }
        } else if ($cardType == 3000 + SAURIAN_ADAPTABILITY_EVOLUTION) {
            $saurianAdaptabilityCard = $this->getEvolutionsOfType($playerId, SAURIAN_ADAPTABILITY_EVOLUTION, false, true)[0];
            $this->playEvolutionToTable($playerId, $saurianAdaptabilityCard, '');
            $this->removeEvolution($playerId, $saurianAdaptabilityCard, false, 5000);
        } else if ($cardType == 3000 + GAMMA_BREATH_EVOLUTION) {
            $gammaBreathCards = $this->getEvolutionsOfType($playerId, GAMMA_BREATH_EVOLUTION, true, true);

            // we use in priority Icy Reflection
            $gammaBreathCard = $this->array_find($gammaBreathCards, fn($card) => $card->type == ICY_REFLECTION_EVOLUTION);
            if ($gammaBreathCard === null) {
                $gammaBreathCard = $gammaBreathCards[0];
            }

            if ($gammaBreathCard->location === 'hand') {
                $this->playEvolutionToTable($playerId, $gammaBreathCard);
            }
            $this->setUsedCard(3000 + $gammaBreathCard->id);
        } else if ($cardType == 3000 + TAIL_SWEEP_EVOLUTION) {
            $tailSweepCards = $this->getEvolutionsOfType($playerId, TAIL_SWEEP_EVOLUTION, true, true);

            // we use in priority Icy Reflection
            $tailSweepCard = $this->array_find($tailSweepCards, fn($card) => $card->type == ICY_REFLECTION_EVOLUTION);
            if ($tailSweepCard === null) {
                $tailSweepCard = $tailSweepCards[0];
            }

            if ($tailSweepCard->location === 'hand') {
                $this->playEvolutionToTable($playerId, $tailSweepCard);
            }
            $this->setUsedCard(3000 + $tailSweepCard->id);
        } else if ($cardType == 3000 + TINY_TAIL_EVOLUTION) {
            $tinyTailCards = $this->getEvolutionsOfType($playerId, TINY_TAIL_EVOLUTION, true, true);

            // we use in priority Icy Reflection
            $tinyTailCard = $this->array_find($tinyTailCards, fn($card) => $card->type == ICY_REFLECTION_EVOLUTION);
            if ($tinyTailCard === null) {
                $tinyTailCard = $tinyTailCards[0];
            }

            if ($tinyTailCard->location === 'hand') {
                $this->playEvolutionToTable($playerId, $tinyTailCard);
            }
            $this->setUsedCard(3000 + $tinyTailCard->id);
        } else if ($cardType != CLOWN_CARD) {
            throw new \BgaUserException('Invalid card to change die');
        }

        $activePlayerId = $this->getActivePlayerId();

        $dice = [$selectedDie];
        if ($cardType == 3000 + SAURIAN_ADAPTABILITY_EVOLUTION) {
            $allDice = $this->getPlayerRolledDice($playerId, false, false, false);
            $dice = array_values(array_filter($allDice, fn($d) => $d->value == $selectedDie->value));
        }

        foreach($dice as $die) {
            $this->DbQuery("UPDATE dice SET `rolled` = false, `dice_value` = ".$value." where `dice_id` = ".$die->id);

            $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
            $this->notifyAllPlayers("changeDie", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card_name' => $cardType,
                'dieId' => $die->id,
                'canHealWithDice' => $this->canHealWithDice($activePlayerId),
                'frozenFaces' => $this->frozenFaces($activePlayerId),
                'toValue' => $value,
                'die_face_before' => $this->getDieFaceLogName($die->value, $die->type),
                'die_face_after' => $this->getDieFaceLogName($value, $die->type),
            ]);
        }

        // psychic probe should not be called after change die (or only after a Background Dweller roll ?)
        /*$changeActivePlayerDieIntervention = $this->getChangeActivePlayerDieIntervention($playerId);
        if ($changeActivePlayerDieIntervention != null) {
            $this->setGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, $changeActivePlayerDieIntervention);
            $this->gamestate->nextState('changeDieWithPsychicProbe');
        } else {
            $this->gamestate->nextState('changeDie');
        }*/
        $this->gamestate->nextState('changeDie');
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

    public function actGoToChangeDie() {
        $playerId = $this->getActivePlayerId();

        $this->fixDices();

        $changeActivePlayerDieIntervention = $this->getChangeActivePlayerDieIntervention($playerId);
        if ($changeActivePlayerDieIntervention != null) {
            $this->setGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, $changeActivePlayerDieIntervention);
            $this->gamestate->nextState('psychicProbe');
        } else {
            $this->gamestate->nextState('goToChangeDie');
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

        $this->stResolveSmashDice($playersSmashesWithReducedDamage);
    }

    public function actResolve() {
        $this->gamestate->nextState('resolve');
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
