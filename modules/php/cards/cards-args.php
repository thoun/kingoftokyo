<?php

namespace KOT\States;

use Bga\GameFrameworkPrototype\Helpers\Arrays;

use function Bga\Games\KingOfTokyo\debug;

use const Bga\Games\KingOfTokyo\PowerCards\MINDBUG_KEYWORDS_WOUNDED;
use const Bga\Games\KingOfTokyo\PowerCards\TOUGH;

trait CardsArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function getArgBuyCard(int $playerId, bool $includeCultistsEnergy) {
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

        $potentialEnergy = $includeCultistsEnergy ? $this->getPlayerPotentialEnergy($playerId) : $this->getPlayerEnergy($playerId);

        $canBuyPowerCards = $this->canBuyPowerCard($playerId);
        $canBuyOrNenew = $potentialEnergy >= 2;
        $canSell = $this->countCardOfType($playerId, METAMORPH_CARD) > 0;

        
        if ($isPowerUpExpansion && (
            $this->getEvolutionsOfType($playerId, ADAPTING_TECHNOLOGY_EVOLUTION, true, true) || // allow to replace cards with Adapting technology even without energy
            $this->getEvolutionsOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION, true, true) // allow to heal once on your turn. If not here, turn will be skipped if the player has exactly 1 energy
        )) {
            $canBuyOrNenew = true;
        }

        // parasitic tentacles
        $canBuyFromPlayers = $this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) > 0;

        // superior alien technology
        $gotSuperiorAlienTechnology = $isPowerUpExpansion && $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, true, true) > 0;
        $canUseSuperiorAlienTechnology = $gotSuperiorAlienTechnology && (count($this->getSuperiorAlienTechnologyTokens($playerId)) < 3 * $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION));
        $canUseBobbingForApples = $isPowerUpExpansion && $this->getFirstUnusedEvolution($playerId, BOBBING_FOR_APPLES_EVOLUTION) != null;

        $cards = $this->powerCards->getTable();
        if ($isPowerUpExpansion) {
            $cards = array_merge($cards, $this->powerCards->getReserved($playerId));
        }
        $cardsCosts = [];
        $cardsCostsSuperiorAlienTechnology = [];
        $cardsCostsBobbingForApples = [];
        
        $disabledIds = [];

        $cardBeingBought = $this->getGlobalVariable(CARD_BEING_BOUGHT);
        if ($cardBeingBought != null && !$cardBeingBought->allowed) {
            $disabledIds[] = $cardBeingBought->cardId;
        }

        $warningIds = [];

        foreach ($cards as $card) {
            $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
            if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                $canBuyOrNenew = true;
            }
            if ($gotSuperiorAlienTechnology && $card->type < 100) {
                $cardsCostsSuperiorAlienTechnology[$card->id] = ceil($cardsCosts[$card->id] / 2);

                if ($canBuyPowerCards && $cardsCostsSuperiorAlienTechnology[$card->id] <= $potentialEnergy) {
                    $canBuyOrNenew = true;
                }
            }
            if ($canUseBobbingForApples) {
                $cardsCostsBobbingForApples[$card->id] = max(0, $cardsCosts[$card->id] - 2);

                if ($canBuyPowerCards && $cardsCostsBobbingForApples[$card->id] <= $potentialEnergy) {
                    $canBuyOrNenew = true;
                }
            }
            if (!$canBuyPowerCards) {
                $disabledIds[] = $card->id;
            }

            $this->setWarningIcon($playerId, $warningIds, $card);
        }

        if ($canBuyFromPlayers) {
            $otherPlayersIds = $this->getOtherPlayersIds($playerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $cardsOfPlayer = $this->powerCards->getPlayerReal($otherPlayerId);
                $buyableCardsOfPlayer = Arrays::filter($cardsOfPlayer, fn($card) => $card->type < 300);

                foreach ($buyableCardsOfPlayer as $card) {
                    $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                    if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                        $canBuyOrNenew = true;
                    }
                    if ($gotSuperiorAlienTechnology && $card->type < 100) {
                        $cardsCostsSuperiorAlienTechnology[$card->id] = ceil($cardsCosts[$card->id] / 2);
        
                        if ($canBuyPowerCards && $cardsCostsSuperiorAlienTechnology[$card->id] <= $potentialEnergy) {
                            $canBuyOrNenew = true;
                        }
                    }
                    if (!$canBuyPowerCards || $card->type > 100) {
                        $disabledIds[] = $card->id;
                    }
                }
            }

            $this->setWarningIcon($playerId, $warningIds, $card);
        }

        // made in a lab
        $canPick = $this->countCardOfType($playerId, MADE_IN_A_LAB_CARD);
        $privateArgs = [
            '_private' => [          // Using "_private" keyword, all data inside this array will be made private
                'active' => [       // Using "active" keyword inside "_private", you select active player(s)
                ]
            ],
        ];
        if ($canPick > 0) {
            $pickCards = $this->powerCards->getCardsOnTop($canPick, 'deck');
            $this->setMadeInALabCardIds($playerId, array_map(fn($card) => $card->id, $pickCards));

            foreach ($pickCards as $card) {
                $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                    $canBuyOrNenew = true;
                }
                if ($gotSuperiorAlienTechnology && $card->type < 100) {
                    $cardsCostsSuperiorAlienTechnology[$card->id] = ceil($cardsCosts[$card->id] / 2);
    
                    if ($canBuyPowerCards && $cardsCostsSuperiorAlienTechnology[$card->id] <= $potentialEnergy) {
                        $canBuyOrNenew = true;
                    }
                }
                if (!$canBuyPowerCards) {
                    $disabledIds[] = $card->id;
                }

                $this->setWarningIcon($playerId, $warningIds, $card);
            }

            $privateArgs['_private']['active']['pickCards'] = $pickCards;
        }

        // scavenger
        $canBuyFromDiscard = $this->countCardOfType($playerId, SCAVENGER_CARD);
        if ($canBuyFromDiscard > 0) {
            $discardCards = $this->powerCards->getCardsInLocation('discard');

            foreach ($discardCards as $card) {
                $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                    $canBuyOrNenew = true;
                }
                if ($gotSuperiorAlienTechnology && $card->type < 100) {
                    $cardsCostsSuperiorAlienTechnology[$card->id] = ceil($cardsCosts[$card->id] / 2);
    
                    if ($canBuyPowerCards && $cardsCostsSuperiorAlienTechnology[$card->id] <= $potentialEnergy) {
                        $canBuyOrNenew = true;
                    }
                }
                if (!$canBuyPowerCards) {
                    $disabledIds[] = $card->id;
                }

                $this->setWarningIcon($playerId, $warningIds, $card);
            }

            $privateArgs['_private']['active']['discardCards'] = $discardCards;
        }

        $canUseAdaptingTechnology = $isPowerUpExpansion && $this->countEvolutionOfType($playerId, ADAPTING_TECHNOLOGY_EVOLUTION, true, true) > 0;
        $canUseMiraculousCatch = $isPowerUpExpansion && $this->countEvolutionOfType($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true) > 0;
        $unusedMiraculousCatch = $canUseMiraculousCatch && $this->getFirstUnusedEvolution($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true) != null;
    
        // return values:
        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
            'canBuyOrNenew' => $canBuyOrNenew || $canPick, // if a player can see 1st deck card, we don't skip his turn or add a timer
            'canSell' => $canSell,
            'cardsCosts' => $cardsCosts,
            'cardsCostsSuperiorAlienTechnology' => $cardsCostsSuperiorAlienTechnology,
            'cardsCostsBobbingForApples' => $cardsCostsBobbingForApples,
            'warningIds' => $warningIds,
            'noExtraTurnWarning' => $this->mindbugExpansion->canGetExtraTurn() ? [] : [FRENZY_CARD],
            'canUseAdaptingTechnology' => $canUseAdaptingTechnology,
            'canUseMiraculousCatch' => $canUseMiraculousCatch,
            'unusedMiraculousCatch' => $unusedMiraculousCatch,
            'gotSuperiorAlienTechnology' => $gotSuperiorAlienTechnology,
            'canUseSuperiorAlienTechnology' => $canUseSuperiorAlienTechnology,
            'canUseBobbingForApples' => $canUseBobbingForApples,
        ] + $privateArgs;
    }

    function getArgChooseMimickedCard(int $playerId, int $mimicCardType, int $selectionCost = 0) {
        $potentialEnergy = 0;
        if ($selectionCost > 0) {
            $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);
        }

        $canChange = $potentialEnergy >= $selectionCost;

        $playersIds = $this->getPlayersIds();

        $cards = $this->powerCards->getTable();
        $disabledIds = array_map(fn($card) => $card->id, $cards);
        $mimickedCardId = $this->getMimickedCardId($mimicCardType);
        $cardsCosts = [];

        foreach($playersIds as $iPlayerId) {
            $cardsOfPlayer = $this->powerCards->getPlayerReal($iPlayerId);
            foreach($cardsOfPlayer as $card) {
                $canMimickCard = false;
                if ($canChange) {
                    $canMimickCard = !($card->type === HIBERNATION_CARD && $this->inTokyo($playerId)) && !($card->type == MIMIC_CARD || $card->id == $mimickedCardId || $card->type >= 100);
                }
                
                if ($canMimickCard) {
                    $cardsCosts[$card->id] = $selectionCost;
                } else {
                    $disabledIds[] = $card->id;
                }
            }
        }

        return [
            'cardsCosts' => $cardsCosts,
            'disabledIds' => $disabledIds,
            'canChange' => $canChange,
        ];
    }

    function argChooseMimickedCard() {
        $playerId = $this->getActivePlayerId();
        return $this->getArgChooseMimickedCard($playerId, MIMIC_CARD);
    }

    function argCancelDamage($playerId = null, $intervention = null) {
        if ($intervention == null) {
            $intervention = $this->getDamageIntervention();
        }

        if ($playerId == null) {
            $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;
        }
        
        if ($playerId != null && $intervention !== null) {
            $isPowerUpExpansion = $this->powerUpExpansion->isActive();
            
            $playersUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : null;
            $dice = $playersUsedDice != null ? $playersUsedDice->dice : null;
            $hasDice3 = $dice ? $this->array_some($dice, fn($die) => $die->value == 3) : false;

            $remainingDamage = 0;
            $damageDealerId = 0;
            $clawDamage = null;
            foreach($intervention->damages as $damage) {
                if ($damage->playerId == $playerId) {
                    $remainingDamage += $damage->remainingDamage ?? $damage->damage;
                    $damageDealerId = $damage->damageDealerId;
                    $clawDamage = $damage->clawDamage;
                }
            }

            $canThrowDices = ($this->countCardOfType($playerId, CAMOUFLAGE_CARD) > 0 || ($isPowerUpExpansion && ($this->countEvolutionOfType($playerId, SO_SMALL_EVOLUTION, true, true) > 0 || $this->countEvolutionOfType($playerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true) > 0))) && ($playersUsedDice == null || $playersUsedDice->rolls < $playersUsedDice->maxRolls);
            $canUseWings = $this->countCardOfType($playerId, WINGS_CARD) > 0;
            $canUseDetachableTail = $isPowerUpExpansion && $this->countEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, false, true) > 0;
            $canUseRabbitsFoot = $isPowerUpExpansion && $this->countEvolutionOfType($playerId, RABBIT_S_FOOT_EVOLUTION, false, true) > 0;
            $canUseCandy = $isPowerUpExpansion && $clawDamage !== null && $this->countEvolutionOfType($playerId, CANDY_EVOLUTION, true, true) > 0;
            $canUseRobot = $this->countCardOfType($playerId, ROBOT_CARD) > 0;
            $playersUsedElectricArmor = property_exists($intervention, 'electricArmorUsed') ? $intervention->electricArmorUsed : false;
            $canUseElectricArmor = !$playersUsedElectricArmor && $this->countCardOfType($playerId, ELECTRIC_ARMOR_CARD) > 0;

            $effectiveDamageDetail = $this->getEffectiveDamage($remainingDamage, $playerId, $damageDealerId, $clawDamage);
            $effectiveDamage = $effectiveDamageDetail->effectiveDamage;

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;

            $rapidHealingHearts = $this->cancellableDamageWithRapidHealing($playerId);
            $countSuperJump = $this->countUnusedCardOfType($playerId, SUPER_JUMP_CARD);
            $superJumpHearts = $this->cancellableDamageWithSuperJump($playerId);
            $rapidHealingCultists = $this->cthulhuExpansion->isActive() ? $this->cthulhuExpansion->cancellableDamageWithCultists($playerId) : 0;
            $damageToCancelToSurvive = $this->getDamageToCancelToSurvive($effectiveDamage, $this->getPlayerHealth($playerId));
            $healWithEvolutions = 0;
            if ($damageToCancelToSurvive > 0 && $this->powerUpExpansion->isActive()) {
                foreach($this->EVOLUTIONS_TO_HEAL as $evolutionType => $amount) {
                    $count = $this->countEvolutionOfType($playerId, $evolutionType, false, true);

                    if ($count > 0) {
                        $healWithEvolutions += $count * ($amount === null ? 999 : $amount); 
                    } 
                }
            }
            $canHeal = $rapidHealingHearts + $rapidHealingCultists + $superJumpHearts + $healWithEvolutions;
            $gotRegeneration = $this->countCardOfType($playerId, REGENERATION_CARD) > 0;
            $cancelHealWithEnergyCards = false;
            if ($gotRegeneration) {
                $cancelHealWithEnergyCards = $canHeal*2 < $damageToCancelToSurvive;
            } else {
                $cancelHealWithEnergyCards = $canHeal < $damageToCancelToSurvive;
            }

            if ($cancelHealWithEnergyCards) {
                $canHeal = 0;
                $rapidHealingHearts = 0;
                $rapidHealingCultists = 0;
            }

            $replaceHeartByEnergyCost = [];
            if ($canUseRobot || $canUseElectricArmor || $this->countUnusedCardOfType($playerId, SUPER_JUMP_CARD) > 0) {
                for ($damageReducedBy=1; $damageReducedBy<=$remainingDamage; $damageReducedBy++) {
                    $replaceHeartByEnergyCost[$damageReducedBy] = $this->getEffectiveDamage($remainingDamage - $damageReducedBy, $playerId, $damageDealerId, $clawDamage)->effectiveDamage;
                }
            }

            $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);

            $consumableCards = $clawDamage !== null ? $this->mindbugExpansion->getConsumableCards($playerId, MINDBUG_KEYWORDS_WOUNDED) : [];
            $consumableToughCards = Arrays::filter($consumableCards, fn($card) => in_array(TOUGH, $card->mindbugKeywords));
            $canPlayConsumable = count($consumableCards) > 0;

            $canCancelDamage = 
                $canThrowDices || 
                ($hasDice3 && $hasBackgroundDweller) ||
                ($canUseWings && $potentialEnergy >= 2) || 
                $canUseDetachableTail || 
                $canUseRabbitsFoot || 
                $canUseCandy ||
                ($canUseRobot && $potentialEnergy >= 1) ||
                ($canUseElectricArmor && $potentialEnergy >= 1) ||
                ($superJumpHearts && $potentialEnergy >= 1) ||
                count($consumableToughCards) > 0;

            $canHealToAvoidDeath = !$cancelHealWithEnergyCards || count($consumableToughCards) > 0;

            $canDoAction = 
                $canCancelDamage || $canHealToAvoidDeath || $canPlayConsumable;

            $damageText = "$remainingDamage";
            $effectiveDamage = $this->getEffectiveDamage($remainingDamage, $playerId, $damageDealerId, $clawDamage)->effectiveDamage;
            if ($effectiveDamage > $remainingDamage) {
                $damageText .= "<small>(+".($effectiveDamage - $remainingDamage).")</small>";
            }

            return [
                'canThrowDices' => $canThrowDices,
                'canUseWings' => $canUseWings,
                'canUseDetachableTail' => $canUseDetachableTail,
                'canUseRabbitsFoot' => $canUseRabbitsFoot,
                'canUseCandy' => $canUseCandy,
                'canUseRobot' => $canUseRobot,
                'canUseElectricArmor' => $canUseElectricArmor,
                'countSuperJump' => $countSuperJump,
                'rapidHealingHearts' => $rapidHealingHearts,
                'superJumpHearts' => $superJumpHearts,
                'rapidHealingCultists' => $rapidHealingCultists,
                'damageToCancelToSurvive' => $damageToCancelToSurvive,
                'canHeal' => $canHeal,
                'playerEnergy' => $this->getPlayerEnergy($playerId),
                'dice' => $dice,
                'damage' => $damageText,
                'remainingDamage' => $remainingDamage,
                'replaceHeartByEnergyCost' => $replaceHeartByEnergyCost,
                'rethrow3' => [
                    'hasCard' => $hasBackgroundDweller,
                    'hasDice3' => $hasDice3,
                ],
                'canCancelDamage' => $canCancelDamage,
                'canHealToAvoidDeath' => $canHealToAvoidDeath,
                'skipMeansDeath' => $damageToCancelToSurvive > 0,
                'canDoAction' => $canDoAction,
                'consumableCards' => $consumableCards,
                'canPlayConsumable' => $canPlayConsumable,
            ];
        } else {
            return [
                'damage' => '', // for state message
                'canDoAction' => false,
            ];
        }
    }

}
