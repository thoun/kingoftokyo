<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use KOT\Objects\Damage;
use KOT\Objects\PlayersUsedDice;

class CancelDamage extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_CANCEL_DAMAGE,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'cancelDamage',
            transitions: [
                'stay' => \ST_MULTIPLAYER_CANCEL_DAMAGE,
                'enterTokyo' => \ST_ENTER_TOKYO_APPLY_BURROWING,
                'enterTokyoAfterBurrowing' => \ST_ENTER_TOKYO,
                'smashes' => \ST_MULTIPLAYER_LEAVE_TOKYO,
                'endTurn' => \ST_END_TURN,
                'zombiePass' => \ST_END_TURN,
            ],
        );
    }

    public function getArgs(): array {
        return $this->game->argCancelDamage();
    }

    public function onEnteringState(): void {
        $intervention = $this->game->getDamageIntervention();
        if ($intervention === null) {
            throw new \Exception('No damage informations found');
        }

        $remainingPlayers = $intervention->remainingPlayersId ?? [];
        if (empty($remainingPlayers)) {
            return;
        }

        $this->gamestate->setPlayersMultiactive([$remainingPlayers[0]], 'stay', true);
    }
    
    #[PossibleAction]
    function actThrowCamouflageDice(int $currentPlayerId) {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();
        $countSoSmall = $isPowerUpExpansion ? $this->game->countEvolutionOfType($currentPlayerId, SO_SMALL_EVOLUTION, true, true) : 0;
        $countCamouflage = $this->game->countCardOfType($currentPlayerId, CAMOUFLAGE_CARD);
        $countTerrorOfTheDeep = $isPowerUpExpansion ? $this->game->countEvolutionOfType($currentPlayerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true) : 0;
        
        if ($countSoSmall + $countCamouflage + $countTerrorOfTheDeep == 0) {
            throw new \BgaUserException('No card to roll dice and cancel damage');
        }

        $intervention = $this->game->getDamageIntervention();

        $diceNumber = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $currentPlayerId) {
                $diceNumber += $damage->damage;
            }
        }

        $dice = [];
        for ($i=0; $i<$diceNumber; $i++) {
            $face = bga_rand(1, 6);
            $newDie = new \stdClass(); // Dice-like
            $newDie->value = $face;
            $newDie->rolled = true;
            $dice[] = $newDie;
        }

        $this->endThrowCamouflageDice($currentPlayerId, $intervention, $dice, true);
    }
    
    #[PossibleAction]
    function actUseWings(int $currentPlayerId) {
        if ($this->game->getPlayerEnergy($currentPlayerId) < 2) {
            throw new \BgaUserException('Not enough energy');
        }

        if ($this->game->countCardOfType($currentPlayerId, WINGS_CARD) == 0) {
            throw new \BgaUserException('No Wings card');
        }

        if ($this->game->canLoseHealth($currentPlayerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->game->applyLoseEnergyIgnoreCards($currentPlayerId, 2, 0);
        $this->setInvincible($currentPlayerId, USED_WINGS);

        $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);

        $this->notify->all("log", clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'card_name' => WINGS_CARD,
        ]);

        $intervention = $this->game->getDamageIntervention();
        $this->reduceInterventionDamages($currentPlayerId, $intervention, -1);
        $this->game->resolveRemainingDamages($intervention, true, false);
    }

    #[PossibleAction]
    function actSkipWings(int $currentPlayerId) {
        $this->applySkipCancelDamage($currentPlayerId);
    }

    #[PossibleAction]
    function actUseRobot(int $energy, int $currentPlayerId) { 
        $countRobot = $this->game->countCardOfType($currentPlayerId, ROBOT_CARD);
        if ($countRobot == 0) {
            throw new \BgaUserException('No Robot card');
        }

        if ($this->game->getPlayerEnergy($currentPlayerId) < $energy) {
            throw new \BgaUserException('Not enough energy');
        }

        $intervention = $this->game->getDamageIntervention();

        $remainingDamage = $this->createRemainingDamage($currentPlayerId, $intervention->damages)->damage - $energy;

        $this->game->applyLoseEnergy($currentPlayerId, $energy, 0);

        $this->reduceInterventionDamages($currentPlayerId, $intervention, $energy);
        $this->game->setDamageIntervention($intervention);

        $args = $this->game->argCancelDamage($currentPlayerId, $intervention);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $this->notify->all('updateCancelDamage', clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'card_name' => ROBOT_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            if ($this->game->applyDamages($intervention, $currentPlayerId) === 0) {
                $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);
            }
        }
        
        $this->game->resolveRemainingDamages($intervention, !$stayOnState, false);
    }

    #[PossibleAction]
    function actUseElectricArmor(int $energy, int $currentPlayerId) {
        $countElectricArmor = $this->game->countCardOfType($currentPlayerId, ELECTRIC_ARMOR_CARD);
        if ($countElectricArmor == 0) {
            throw new \BgaUserException('No Electric Armor card');
        }

        if ($energy > 1) {
            throw new \BgaUserException('You can only save 1 Heart with Electric Armor');
        }

        if ($this->game->getPlayerEnergy($currentPlayerId) < $energy) {
            throw new \BgaUserException('Not enough energy');
        }

        $intervention = $this->game->getDamageIntervention();
        $intervention->electricArmorUsed = true;

        $remainingDamage = $this->createRemainingDamage($currentPlayerId, $intervention->damages)->damage - $energy;

        $this->game->applyLoseEnergy($currentPlayerId, $energy, 0);

        $this->reduceInterventionDamages($currentPlayerId, $intervention, $energy);
        $this->game->setDamageIntervention($intervention);

        $args = $this->game->argCancelDamage($currentPlayerId, $intervention);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canCancelDamage'];
        }

        $this->notify->all('updateCancelDamage', clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'card_name' => ELECTRIC_ARMOR_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            if ($this->game->applyDamages($intervention, $currentPlayerId) === 0) {
                $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);
            }
        }
        
        $this->game->resolveRemainingDamages($intervention, !$stayOnState, false);
    }

    #[PossibleAction]
    function actUseSuperJump(int $energy, int $currentPlayerId) { 
        $superJumpCards = $this->game->getUnusedCardOfType($currentPlayerId, SUPER_JUMP_CARD);
        if (count($superJumpCards) < $energy) {
            throw new \BgaUserException('No unused Super Jump');
        }

        if ($this->game->getPlayerEnergy($currentPlayerId) < $energy) {
            throw new \BgaUserException('Not enough energy');
        }

        $intervention = $this->game->getDamageIntervention();

        $remainingDamage = $this->createRemainingDamage($currentPlayerId, $intervention->damages)->damage - $energy;

        $this->game->applyLoseEnergy($currentPlayerId, $energy, 0);

        for ($i=0;$i<$energy;$i++) {
            $this->game->setUsedCard($superJumpCards[$i]->id);
        }

        $this->reduceInterventionDamages($currentPlayerId, $intervention, $energy);

        $args = $this->game->argCancelDamage($currentPlayerId, $intervention);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $this->game->setDamageIntervention($intervention);

        $this->notify->all('updateCancelDamage', clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'card_name' => SUPER_JUMP_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            if ($this->game->applyDamages($intervention, $currentPlayerId) === 0) {
                $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);
            }
        }
        $this->game->resolveRemainingDamages($intervention, !$stayOnState, false);
    }

    #[PossibleAction]
    function actUseRapidHealingSync(int $cultistCount, int $rapidHealingCount, int $currentPlayerId) {
        $intervention = $this->game->getDamageIntervention();

        $remainingDamage = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $currentPlayerId) {
                $remainingDamage += $damage->damage;
            }
        }

        for ($i=0; $i<$cultistCount; $i++) {
            if ($this->game->cthulhuExpansion->getPlayerCultists($currentPlayerId) >= 1) {
                $this->game->cthulhuExpansion->applyUseRapidCultist($currentPlayerId, 4);
                $remainingDamage--;
            } else {
                break;
            }
        }
        for ($i=0; $i<$rapidHealingCount; $i++) {
            if ($this->game->getPlayerEnergy($currentPlayerId) >= 2) {
                $this->game->applyRapidHealing($currentPlayerId);
                $remainingDamage--;
            } else {
                break;
            }
        }
        
        if ($this->game->applyDamages($intervention, $currentPlayerId) === 0) {
            $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);
        }
        $this->game->resolveRemainingDamages($intervention, true, false);
    }

    #[PossibleAction]
    public function actRethrow3Camouflage(int $currentPlayerId) {
        $countBackgroundDweller = $this->game->countCardOfType($currentPlayerId, BACKGROUND_DWELLER_CARD);
        if ($countBackgroundDweller == 0) {
            throw new \BgaUserException('No Background Dweller card');
        }

        $intervention = $this->game->getDamageIntervention();

        $dice = $intervention->playersUsedDice->{$currentPlayerId}->dice;
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

        $this->endThrowCamouflageDice($currentPlayerId, $intervention, $dice, false);
    }

    function endThrowCamouflageDice(int $currentPlayerId, object $intervention, array $dice, bool $incCamouflageRolls) {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();
        $countSoSmall = $isPowerUpExpansion ? $this->game->countEvolutionOfType($currentPlayerId, SO_SMALL_EVOLUTION, true, true) : 0;
        $countCamouflage = $this->game->countCardOfType($currentPlayerId, CAMOUFLAGE_CARD);
        $countTerrorOfTheDeep = $isPowerUpExpansion ? $this->game->countEvolutionOfType($currentPlayerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true) : 0;
        
        $rolledDice = array_values(array_filter($dice, fn($d) => $d->rolled));
        $diceValues = array_map(fn($die) => $die->value, $dice);
        $rolledDiceValues = array_map(fn($die) => $die->value, $rolledDice);

        $playerUsedDice = property_exists($intervention->playersUsedDice, (string)$currentPlayerId) ? $intervention->playersUsedDice->{$currentPlayerId} : new PlayersUsedDice($dice, $countSoSmall + $countCamouflage + $countTerrorOfTheDeep);
        
        $cardLogType = CAMOUFLAGE_CARD;
        if ($countSoSmall > 0 && $playerUsedDice->rolls < $countSoSmall) {
            $cardLogType = 3000 + SO_SMALL_EVOLUTION;
        } else if ($countTerrorOfTheDeep > 0 && $playerUsedDice->rolls < $countTerrorOfTheDeep) {
            $cardLogType = 3000 + TERROR_OF_THE_DEEP_EVOLUTION;
        }

        if ($incCamouflageRolls) {
            $playerUsedDice->rolls = $playerUsedDice->rolls + 1;
        } 
        $intervention->playersUsedDice->{$currentPlayerId} = $playerUsedDice;

        if ($cardLogType === 3000 + SO_SMALL_EVOLUTION) {
            $soSmallCards = $this->game->getEvolutionsOfType($currentPlayerId, SO_SMALL_EVOLUTION, true, true);

            if (Arrays::every($soSmallCards, fn($soSmallCard) => $soSmallCard->location === 'hand')) {
                $this->game->playEvolutionToTable($currentPlayerId, $soSmallCards[0]);
            }
        }
        if ($cardLogType === 3000 + TERROR_OF_THE_DEEP_EVOLUTION) {
            $terrorOfTheDeepCards = $this->game->getEvolutionsOfType($currentPlayerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true);

            if (Arrays::every($terrorOfTheDeepCards, fn($terrorOfTheDeepCard) => $terrorOfTheDeepCard->location === 'hand')) {
                $this->game->playEvolutionToTable($currentPlayerId, $terrorOfTheDeepCards[0]);
            }
        }

        $cancelledDamage = count(array_values(array_filter($rolledDiceValues, fn($face) => $face === 4))); // heart dices
        if ($cardLogType === 3000 + SO_SMALL_EVOLUTION && $cancelledDamage > 0) {
            $cancelledDamage = count($rolledDice);
        }

        $remainingDamage = $this->createRemainingDamage($currentPlayerId, $intervention->damages)->damage - $cancelledDamage;

        $canRethrow3 = false;
        if ($remainingDamage > 0) {
            $canRethrow3 = $this->game->countCardOfType($currentPlayerId, BACKGROUND_DWELLER_CARD) > 0 && in_array(3, $diceValues);
        }

        $this->reduceInterventionDamages($currentPlayerId, $intervention, $cancelledDamage);

        $args = $this->game->argCancelDamage($currentPlayerId, $intervention);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $diceStr = '';
        foreach ($diceValues as $dieValue) {
            $diceStr .= $this->game->getDieFaceLogName($dieValue, 0);
        }

        if ($canRethrow3) {
            $this->notify->all("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and can rethrow [dice3]'), [
                'playerId' => $currentPlayerId,
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'card_name' => $cardLogType,
                'diceValues' => $dice,
                'cancelDamageArgs' => $args,
                'dice' => $diceStr,
            ]);
        } else {
            $this->notify->all("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and reduce [Heart] loss by ${cancelledDamage}'), [
                'playerId' => $currentPlayerId,
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'card_name' => $cardLogType,
                'cancelledDamage' => $cancelledDamage,
                'diceValues' => $dice,
                'cancelDamageArgs' => $args,
                'dice' => $diceStr,
            ]);
        }

        if (!$stayOnState) {
            $damage = $this->createRemainingDamage($currentPlayerId, $intervention->damages);
            if ($damage != null) {
                $this->game->applyDamage($damage);
            } else {
                $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);
            }
        }
        $this->game->resolveRemainingDamages($intervention, !$stayOnState, false);
    }
    
    #[PossibleAction]
    function actUseInvincibleEvolution(int $evolutionType, int $currentPlayerId) {
        if (!in_array($evolutionType, [DETACHABLE_TAIL_EVOLUTION, RABBIT_S_FOOT_EVOLUTION]) || $this->game->countEvolutionOfType($currentPlayerId, $evolutionType, false, true) == 0) {
            throw new \BgaUserException('No Detachable Tail / Rabbits Foot Evolution');
        }

        if ($this->game->canLoseHealth($currentPlayerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);

        $card = $this->game->getEvolutionsOfType($currentPlayerId, $evolutionType, true, true)[0];

        $this->game->powerUpExpansion->evolutionCards->moveItem($card, 'table', $currentPlayerId);

        $this->game->playEvolutionToTable($currentPlayerId, $card, clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'));

        $intervention = $this->game->getDamageIntervention();
        $this->reduceInterventionDamages($currentPlayerId, $intervention, -1);
        $this->game->resolveRemainingDamages($intervention, true, false);
    }
    
    #[PossibleAction]
    function actUseCandyEvolution(int $currentPlayerId) {
        if ($this->game->countEvolutionOfType($currentPlayerId, CANDY_EVOLUTION, true, true) == 0) {
            throw new \BgaUserException('No Candy Evolution');
        }

        if ($this->game->canLoseHealth($currentPlayerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->game->removePlayerFromSmashedPlayersInTokyo($currentPlayerId);

        $evolution = $this->game->getEvolutionsOfType($currentPlayerId, CANDY_EVOLUTION, true, true)[0];

        $this->game->powerUpExpansion->evolutionCards->moveItem($evolution, 'table', $currentPlayerId);

        $this->game->playEvolutionToTable($currentPlayerId, $evolution, clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'));

        $intervention = $this->game->getDamageIntervention();
        $damageDealerId = null;
        $clawDamage = null;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $currentPlayerId) {
                $damageDealerId = $damage->damageDealerId;
                $clawDamage = $damage->clawDamage;
            }
        }

        if ($clawDamage === null || $damageDealerId === null || $damageDealerId === 0) {
            throw new \BgaUserException('You can only use it when wounded');
        }

        $fromPlayerId = $currentPlayerId;
        $toPlayerId = $damageDealerId;
        $this->game->removeEvolution($fromPlayerId, $evolution);
        $this->game->powerUpExpansion->evolutionCards->moveItem($evolution, 'hand', $toPlayerId);
        $message = /*client TODOPUHA translate*/('${player_name2} use ${card_name} to avoid damages and gives ${card_name} to ${player_name}');
        $this->game->notifNewEvolutionCard($toPlayerId, $evolution, $message, [
            'card_name' => 3000 + $evolution->type,
            'player_name2' => $this->game->getPlayerNameById($fromPlayerId),
        ]);

        $this->reduceInterventionDamages($currentPlayerId, $intervention, -1);
        $this->game->resolveRemainingDamages($intervention, true, false);
    }

    public function zombie(int $playerId): void {
        $this->applySkipCancelDamage($playerId);
    }

    function applySkipCancelDamage(int $playerId, $intervention = null, bool $ignoreRedirect = false) {
        if ($intervention === null) {
            $intervention = $this->game->getDamageIntervention();
        }

        $this->game->applyDamages($intervention, $playerId);
        $this->game->resolveRemainingDamages($intervention, true, false);

        // we check we are still in cancelDamage (we could be redirected if player is eliminated)
        if (!$ignoreRedirect && $this->gamestate->getCurrentMainState()->name == 'cancelDamage') {
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

    function setInvincible(int $playerId, string $varName) {        
        $usedWings = $this->game->getGlobalVariable($varName, true);
        $usedWings[] = $playerId;
        $this->game->setGlobalVariable($varName, $usedWings);
    }

    function reduceInterventionDamages(int $playerId, &$intervention, int $reduceBy /* -1 for remove all for player*/) {
        $damageIndex = Arrays::findKey($intervention->damages, fn($d) => $d->playerId == $playerId);
        $allDamageIndex = Arrays::findKey($intervention->allDamages, fn($d) => $d->playerId == $playerId);

        if ($reduceBy === -1 || $reduceBy >= $intervention->damages[$damageIndex]->remainingDamage) {
            // damage is fully cancelled, we remove it
            array_splice($intervention->damages, $damageIndex, 1);
            array_splice($intervention->allDamages, $allDamageIndex, 1);
        } else {
            $intervention->damages[$damageIndex]->damage -= $reduceBy;
            $intervention->damages[$damageIndex]->remainingDamage -= $reduceBy;
            $intervention->allDamages[$allDamageIndex]->remainingDamage -= $reduceBy;
        }
        
        $this->game->setDamageIntervention($intervention);
    }

    function createRemainingDamage(int $playerId, array $damages): ?Damage {
        $damageNumber = 0;
        $damageDealerId = 0;
        $cardType = 0;
        $giveShrinkRayToken = 0;
        $givePoisonSpitToken = 0;
        $smasherPoints = null;
        $clawDamage = null;

        foreach($damages as $damage) {
            if ($damage->playerId == $playerId) {
                $damageNumber += $damage->damage;
                if ($damageDealerId == 0) {
                    $damageDealerId = $damage->damageDealerId;
                }
                if ($cardType == 0) {
                    $cardType = $damage->cardType;
                }
                if ($smasherPoints === null) {
                    $smasherPoints = $damage->smasherPoints;
                }
                $giveShrinkRayToken += $damage->giveShrinkRayToken;
                $givePoisonSpitToken += $damage->givePoisonSpitToken;
                if ($clawDamage === null) {
                    $clawDamage = $damage->clawDamage;
                }
            }
        }
        return $damageNumber == 0 ? null : new Damage($playerId, $damageNumber, $damageDealerId, $cardType, $clawDamage);
    }
}

