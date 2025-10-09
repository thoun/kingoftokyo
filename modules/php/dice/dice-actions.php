<?php

namespace KOT\States;

use Bga\GameFramework\Actions\Types\JsonParam;

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

}
