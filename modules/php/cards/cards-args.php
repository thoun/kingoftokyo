<?php

namespace KOT\States;

trait CardsArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argCheerleaderSupport() {
        $activePlayerId = $this->getActivePlayerId();

        return [
            'dice' => $this->getPlayerRolledDice($activePlayerId, true, true, true),
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
        ];
    }

    function getArgBuyCard(int $playerId, bool $includeCultistsEnergy) {
        $potentialEnergy = $includeCultistsEnergy ? $this->getPlayerPotentialEnergy($playerId) : $this->getPlayerEnergy($playerId);

        $canBuyPowerCards = $this->canBuyPowerCard($playerId);
        $canBuyOrNenew = $potentialEnergy >= 2;
        $canSell = $this->countCardOfType($playerId, METAMORPH_CARD) > 0;

        // parasitic tentacles
        $canBuyFromPlayers = $this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) > 0;

        // superior alien technology
        $isPowerUpExpansion = $this->isPowerUpExpansion();
        $canUseSuperiorAlienTechnology = $isPowerUpExpansion && $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, true, true) > 0;

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $cardsCosts = [];
        $cardsCostsSuperiorAlienTechnology = [];
        
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
            if ($canUseSuperiorAlienTechnology && $card->type < 100) {
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

        if ($canBuyFromPlayers) {
            $otherPlayersIds = $this->getOtherPlayersIds($playerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));
                $buyableCardsOfPlayer = array_values(array_filter($cardsOfPlayer, fn($card) => $card->type < 300));

                foreach ($buyableCardsOfPlayer as $card) {
                    $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                    if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                        $canBuyOrNenew = true;
                    }
                    if ($canUseSuperiorAlienTechnology && $card->type < 100) {
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
        $pickArgs = [];
        if ($canPick > 0) {
            $pickCards = $this->getCardsFromDb($this->cards->getCardsOnTop($canPick, 'deck'));
            $this->setMadeInALabCardIds($playerId, array_map(fn($card) => $card->id, $pickCards));

            foreach ($pickCards as $card) {
                $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                    $canBuyOrNenew = true;
                }
                if ($canUseSuperiorAlienTechnology && $card->type < 100) {
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

            $pickArgs = [
                '_private' => [          // Using "_private" keyword, all data inside this array will be made private
                    'active' => [       // Using "active" keyword inside "_private", you select active player(s)
                        'pickCards' => $pickCards,   // will be send only to active player(s)
                    ]
                ],
            ];
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
            'warningIds' => $warningIds,
            'canUseAdaptingTechnology' => $canUseAdaptingTechnology,
            'canUseMiraculousCatch' => $canUseMiraculousCatch,
            'unusedMiraculousCatch' => $unusedMiraculousCatch,
            'canUseSuperiorAlienTechnology' => $canUseSuperiorAlienTechnology,
        ] + $pickArgs;
    }

    function argBuyCard() {
        $playerId = $this->getActivePlayerId();

        return $this->getArgBuyCard($playerId, true);
    }

    function argOpportunistBuyCardWithPlayerId(int $playerId) {        
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        $revealedCardsIds = $opportunistIntervention ? $opportunistIntervention->revealedCardsIds : [];
        $canBuy = false;
        $canBuyPowerCards = $this->canBuyPowerCard($playerId);

        $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $cardsCosts = [];
        
        $disabledIds = [];
        $warningIds = [];
        foreach ($cards as $card) {
            if (in_array($card->id, $revealedCardsIds)) {
                $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                    $canBuy = true;
                }
                if (!$canBuyPowerCards) {
                    $disabledIds[] = $card->id;
                }

                $this->setWarningIcon($playerId, $warningIds, $card);
            } else {
                $disabledIds[] = $card->id;
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuy' => $canBuy,
            'cardsCosts' => $cardsCosts,
            'warningIds' => $warningIds,
        ];
    }

    function argOpportunistBuyCard() {
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);

        $playerId = $opportunistIntervention && count($opportunistIntervention->remainingPlayersId) > 0 ? $opportunistIntervention->remainingPlayersId[0] : null;
        if ($playerId != null) {
            return $this->argOpportunistBuyCardWithPlayerId($playerId);
        } else {
            return [
                'canBuy' => false,
            ];
        }
    }

    function argSellCard() {
        $playerId = $this->getActivePlayerId();

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        
        $disabledIds = [];
        foreach ($cards as $card) {
            if ($card->type > 100) {
                $disabledIds[] = $card->id;
            }
        }
    
        return [
            'disabledIds' => $disabledIds,
        ];
    }

    function getArgChooseMimickedCard(int $playerId, int $mimicCardType, int $selectionCost = 0) {
        $potentialEnergy = 0;
        if ($selectionCost > 0) {
            $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);
        }

        $canChange = $potentialEnergy >= $selectionCost;

        $playersIds = $this->getPlayersIds();

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(fn($card) => $card->id, $cards);
        $mimickedCardId = $this->getMimickedCardId($mimicCardType);
        $cardsCosts = [];

        foreach($playersIds as $iPlayerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $iPlayerId));
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

    function argChangeMimickedCard() {
        $playerId = $this->getActivePlayerId();
        return $this->getArgChooseMimickedCard($playerId, MIMIC_CARD, 1);
    }

    function argChooseMimickedCard() {
        $playerId = $this->getActivePlayerId();
        return $this->getArgChooseMimickedCard($playerId, MIMIC_CARD);
    }

    function argCancelDamage($playerId = null, $hasDice3 = false, $intervention = null) {
        if ($intervention == null) {
            $intervention = $this->getDamageIntervention();
        }

        if ($playerId == null) {
            $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;
        }
        
        if ($playerId != null && $intervention !== null) {
            $playersUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : null;
            $dice = $playersUsedDice != null ? $playersUsedDice->dice : null;

            $canThrowDices = $this->countCardOfType($playerId, CAMOUFLAGE_CARD) > 0 && ($playersUsedDice == null || $playersUsedDice->rolls < $playersUsedDice->maxRolls);
            $canUseWings = $this->countCardOfType($playerId, WINGS_CARD) > 0;
            $canUseDetachableTail = $this->isPowerUpExpansion() && $this->countEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, false, true) > 0;
            $canUseRabbitsFoot = $this->isPowerUpExpansion() && $this->countEvolutionOfType($playerId, RABBIT_S_FOOT_EVOLUTION, false, true) > 0;
            $canUseRobot = $this->countCardOfType($playerId, ROBOT_CARD) > 0;

            $remainingDamage = 0;
            $damageDealerId = 0;
            foreach($intervention->damages as $damage) {
                if ($damage->playerId == $playerId) {
                    $remainingDamage += $damage->remainingDamage ?? $damage->damage;
                    $damageDealerId = $damage->damageDealerId;
                }
            }

            $effectiveDamageDetail = $this->getEffectiveDamage($remainingDamage, $playerId, $damageDealerId);
            $effectiveDamage = $effectiveDamageDetail->effectiveDamage;

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;

            $rapidHealingHearts = $this->cancellableDamageWithRapidHealing($playerId);
            $countSuperJump = $this->countUnusedCardOfType($playerId, SUPER_JUMP_CARD);
            $superJumpHearts = $this->cancellableDamageWithSuperJump($playerId); // TODODE use potentialEnergy
            $rapidHealingCultists = $this->isCthulhuExpansion() ? $this->cancellableDamageWithCultists($playerId) : 0;
            $damageToCancelToSurvive = $this->getDamageToCancelToSurvive($effectiveDamage, $this->getPlayerHealth($playerId));
            $canHeal = $rapidHealingHearts + $rapidHealingCultists + $superJumpHearts;
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
                $damageToCancelToSurvive = 0;
            }

            $replaceHeartByEnergyCost = [];
            if ($canUseRobot || $this->countUnusedCardOfType($playerId, SUPER_JUMP_CARD) > 0) {
                for ($damageReducedBy=1; $damageReducedBy<=$remainingDamage; $damageReducedBy++) {
                    $replaceHeartByEnergyCost[$damageReducedBy] = $this->getEffectiveDamage($remainingDamage - $damageReducedBy, $playerId, $damageDealerId)->effectiveDamage;
                }
            }

            $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);

            $canDoAction = 
                $canThrowDices || 
                $hasDice3 ||
                ($canUseWings && $potentialEnergy >= 2) || 
                $canUseDetachableTail || 
                $canUseRabbitsFoot || 
                ($canUseRobot && $potentialEnergy >= 1) || 
                $rapidHealingHearts || 
                ($superJumpHearts && $potentialEnergy >= 1) || 
                $rapidHealingCultists;

            return [
                'canThrowDices' => $canThrowDices,
                'canUseWings' => $canUseWings,
                'canUseDetachableTail' => $canUseDetachableTail,
                'canUseRabbitsFoot' => $canUseRabbitsFoot,
                'canUseRobot' => $canUseRobot,
                'countSuperJump' => $countSuperJump,
                'rapidHealingHearts' => $rapidHealingHearts,
                'superJumpHearts' => $superJumpHearts,
                'rapidHealingCultists' => $rapidHealingCultists,
                'damageToCancelToSurvive' => $damageToCancelToSurvive,
                'canHeal' => $canHeal,
                'playerEnergy' => $this->getPlayerEnergy($playerId),
                'dice' => $dice,
                'damage' => $remainingDamage,
                'remainingDamage' => $remainingDamage,
                'replaceHeartByEnergyCost' => $replaceHeartByEnergyCost,
                'rethrow3' => [
                    'hasCard' => $hasBackgroundDweller,
                    'hasDice3' => $hasDice3,
                ],
                'canDoAction' => $canDoAction,
            ];
        } else {
            return [
                'damage' => '', // for state message
                'canDoAction' => false,
            ];
        }
    }

    function argStealCostumeCard() {
        $playerId = $this->getActivePlayerId();

        $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);

        $tableCards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(fn($card) => $card->id, $tableCards); // can only take from other players, not table
        $cardsCosts = [];

        $canBuyFromPlayers = false;

        $woundedPlayersIds = $this->playersWoundedByActivePlayerThisTurn($playerId);
        $otherPlayersIds = $this->getOtherPlayersIds($playerId);
        foreach($otherPlayersIds as $otherPlayerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));
            $isWoundedPlayer = in_array($otherPlayerId, $woundedPlayersIds);

            foreach ($cardsOfPlayer as $card) {
                if ($isWoundedPlayer && $card->type > 200 && $card->type < 300) {
                    $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);

                    if ($cardsCosts[$card->id] <= $potentialEnergy) {
                        $canBuyFromPlayers = true;
                    }
                } else {
                    $disabledIds[] = $card->id;
                }
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
            'cardsCosts' => $cardsCosts,
        ];
    }

    function argLeaveTokyoExchangeCard() {
        $playerId = intval($this->getActivePlayerId());

        $leaversWithUnstableDNA = $this->getLeaversWithUnstableDNA();  
        $currentPlayerId = $leaversWithUnstableDNA[0];

        $canExchange = false;
        $tableCards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(fn($card) => $card->id, $tableCards); // can only take from other players, not table

        $otherPlayersIds = $this->getOtherPlayersIds($currentPlayerId);
        foreach($otherPlayersIds as $otherPlayerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));
            $isSmashingPlayer = $playerId === $otherPlayerId; // TODODE check it's not currentPlayer, else skip (if player left Tokyo with anubis card)

            foreach ($cardsOfPlayer as $card) {
                if ($isSmashingPlayer && $card->type < 300) {
                    // all cards can be stolen : keep, discard, costume. Ignore transformation & golden scarab
                    $canExchange = true;
                } else {
                    $disabledIds[] = $card->id;
                }
            }
        }

        return [
            'canExchange' => $canExchange,
            'disabledIds' => $disabledIds,
        ];
    }

}
