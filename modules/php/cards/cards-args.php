<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/card.php');
require_once(__DIR__.'/../objects/player-intervention.php');
require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Damage;
use KOT\Objects\PlayersUsedDice;

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

    function argBuyCard() {
        $playerId = $this->getActivePlayerId();

        $potentialEnergy = $this->getPlayerEnergy($playerId);
        if ($this->isCthulhuExpansion()) {
            $potentialEnergy += $this->getPlayerCultists($playerId);
        }

        $canBuyPowerCards = $this->canBuyPowerCard($playerId);
        $canBuyOrNenew = $potentialEnergy >= 2;
        $canSell = $this->countCardOfType($playerId, METAMORPH_CARD) > 0;

        // parasitic tentacles
        $canBuyFromPlayers = $this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) > 0;

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $cardsCosts = [];
        
        $disabledIds = [];
        $warningIds = [];
        foreach ($cards as $card) {
            $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
            if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                $canBuyOrNenew = true;
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

                foreach ($cardsOfPlayer as $card) {
                    $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                    if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                        $canBuyOrNenew = true;
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
    
        // return values:
        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
            'canBuyOrNenew' => $canBuyOrNenew || $canPick, // if a player can see 1st deck card, we don't skip his turn or add a timer
            'canSell' => $canSell,
            'cardsCosts' => $cardsCosts,
            'unbuyableIds' => $disabledIds, // TODO remove
            'warningIds' => $warningIds,
        ] + $pickArgs;
    }

    function argOpportunistBuyCardWithPlayerId(int $playerId) {        
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        $revealedCardsIds = $opportunistIntervention ? $opportunistIntervention->revealedCardsIds : [];
        $canBuy = false;
        $canBuyPowerCards = $this->canBuyPowerCard($playerId);

        $potentialEnergy = $this->getPlayerEnergy($playerId);
        if ($this->isCthulhuExpansion()) {
            $potentialEnergy += $this->getPlayerCultists($playerId);
        }

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
            'unbuyableIds' => $disabledIds, // TODO remove
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
            $potentialEnergy = $this->getPlayerEnergy($playerId);
            if ($this->isCthulhuExpansion()) {
                $potentialEnergy += $this->getPlayerCultists($playerId);
            }
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

    function argCancelDamage($playerId = null, $hasDice3 = false) {
        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        if ($playerId == null) {
            $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;
        }
        
        if ($playerId != null) {
            $playersUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : null;
            $dice = $playersUsedDice != null ? $playersUsedDice->dice : null;

            $canThrowDices = $this->countCardOfType($playerId, CAMOUFLAGE_CARD) > 0 && ($playersUsedDice == null || $playersUsedDice->rolls < $playersUsedDice->maxRolls);
            $canUseWings = $this->countCardOfType($playerId, WINGS_CARD) > 0;
            $canUseRobot = $this->countCardOfType($playerId, ROBOT_CARD) > 0;

            $remainingDamage = 0;
            $devilCard = false;
            foreach($intervention->damages as $damage) {
                if ($damage->playerId == $playerId) {
                    $remainingDamage += $damage->damage;

                    if ($this->countCardOfType($damage->damageDealerId, DEVIL_CARD)) {
                        $remainingDamage += 1;
                        $devilCard = true;
                    }
                }
            }

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;

            $rapidHealingHearts = $this->cancellableDamageWithRapidHealing($playerId);
            $superJumpHearts = $this->cancellableDamageWithSuperJump($playerId);
            $rapidHealingCultists = $this->isCthulhuExpansion() ? $this->cancellableDamageWithCultists($playerId) : 0;
            $damageToCancelToSurvive = $this->getDamageToCancelToSurvive($remainingDamage, $this->getPlayerHealth($playerId));
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

            $canDoAction = $canThrowDices || $canUseWings || $canUseRobot || $rapidHealingHearts || $superJumpHearts || $rapidHealingCultists || $hasDice3;

            return [
                'canThrowDices' => $canThrowDices,
                'canUseWings' => $canUseWings,
                'canUseRobot' => $canUseRobot,
                'rapidHealingHearts' => $rapidHealingHearts,
                'superJumpHearts' => $superJumpHearts,
                'rapidHealingCultists' => $rapidHealingCultists,
                'damageToCancelToSurvive' => $damageToCancelToSurvive,
                'canHeal' => $canHeal,
                'playerEnergy' => $this->getPlayerEnergy($playerId),
                'dice' => $dice,
                'damage' => $remainingDamage,
                'devilCard' => $devilCard,
                'rethrow3' => [
                    'hasCard' => $hasBackgroundDweller,
                    'hasDice3' => $hasDice3,
                ],
                'canDoAction' => $canDoAction,
            ];
        } else {
            return [
                'damage' => '',
            ];
        }
    }

    function argStealCostumeCard() {
        $playerId = $this->getActivePlayerId();

        $potentialEnergy = $this->getPlayerEnergy($playerId);
        if ($this->isCthulhuExpansion()) {
            $potentialEnergy += $this->getPlayerCultists($playerId);
        }

        $tableCards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(fn($card) => $card->id, $tableCards); // can only take from other players, not table
        $disabledIds = $disabledIds; // copy
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
                    if (!$this->canBuyCard($playerId, $card->type, $cardsCosts[$card->id])) {
                        $disabledIds[] = $card->id;
                    }
                } else {
                    $disabledIds[] = $card->id;
                    $disabledIds[] = $card->id;
                }
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
            'cardsCosts' => $cardsCosts,
            'unbuyableIds' => $disabledIds,
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
