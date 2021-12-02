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
        $activePlayerId = self::getActivePlayerId();

        return [
            'dice' => $this->getPlayerRolledDice($activePlayerId, true, true, true),
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
        ];
    }

    function argBuyCard() {
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

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
        
        $unbuyableIds = [];
        $disabledIds = []; // TODO remove
        $warningIds = [];
        foreach ($cards as $card) {
            $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
            if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                $canBuyOrNenew = true;
            }
            if (!$canBuyPowerCards || !$this->canBuyCard($playerId, $cardsCosts[$card->id])) {
                $disabledIds[] = $card->id;
            }
            if (!$canBuyPowerCards) {
                $unbuyableIds[] = $card->id;
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
                        $unbuyableIds[] = $card->id;
                    }
                }
            }

            $this->setWarningIcon($playerId, $warningIds, $card);
        }

        // made in a lab
        $canPick = $this->countCardOfType($playerId, MADE_IN_A_LAB_CARD);
        $pickArgs = [];
        if ($canPick > 0) {
            $madeInALabCardIds = $this->getMadeInALabCardIds($playerId);
            $pickCards = $this->getCardsFromDb($this->cards->getCardsOnTop($canPick, 'deck'));
            $this->setMadeInALabCardIds($playerId, array_map(function($card) { return $card->id; }, $pickCards));

            foreach ($pickCards as $card) {
                $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                    $canBuyOrNenew = true;
                }
                if (!$canBuyPowerCards || !$this->canBuyCard($playerId, $cardsCosts[$card->id])) {
                    $disabledIds[] = $card->id;
                }
                if (!$canBuyPowerCards) {
                    $unbuyableIds[] = $card->id;
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
            'unbuyableIds' => $unbuyableIds,
            'warningIds' => $warningIds,
        ] + $pickArgs;
    }

    function argOpportunistBuyCardWithPlayerId(int $playerId) {        
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        $revealedCardsIds = $opportunistIntervention ? $opportunistIntervention->revealedCardsIds : [];
        $canBuy = false;
        $canBuyPowerCards = $this->canBuyPowerCard($playerId);

        $playerEnergy = $this->getPlayerEnergy($playerId);

        $potentialEnergy = $this->getPlayerEnergy($playerId);
        if ($this->isCthulhuExpansion()) {
            $potentialEnergy += $this->getPlayerCultists($playerId);
        }

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $cardsCosts = [];
        
        $unbuyableIds = [];
        $disabledIds = []; // TODO remove
        $warningIds = [];
        foreach ($cards as $card) {
            if (in_array($card->id, $revealedCardsIds)) {
                $cardsCosts[$card->id] = $this->getCardCost($playerId, $card->type);
                if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                    $canBuy = true;
                }
                if (!$canBuyPowerCards || !$this->canBuyCard($playerId, $cardsCosts[$card->id])) {
                    $disabledIds[] = $card->id;
                }
                if (!$canBuyPowerCards) {
                    $unbuyableIds[] = $card->id;
                }

                $this->setWarningIcon($playerId, $warningIds, $card);
            } else {
                $disabledIds[] = $card->id;
                $unbuyableIds[] = $card->id;
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuy' => $canBuy,
            'cardsCosts' => $cardsCosts,
            'unbuyableIds' => $unbuyableIds,
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
        $playerId = self::getActivePlayerId();

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

    function getArgChooseMimickedCard(int $playerId, int $mimicCardType, int $selectionCost = null) {
        $canChange = $selectionCost !== null ? $this->getPlayerEnergy($playerId) >= $selectionCost : true;

        $playersIds = $this->getPlayersIds();

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(function ($card) { return $card->id; }, $cards);
        $mimickedCardId = $this->getMimickedCardId($mimicCardType);

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            $disabledCardsOfPlayer = $canChange ? 
                array_values(array_filter($cardsOfPlayer, function ($card) use ($mimickedCardId) { return $card->type == MIMIC_CARD || $card->id == $mimickedCardId || $card->type >= 100; })) :
                $cardsOfPlayer; // TODOWI ignore Mimic Tile ? for wickedness selection
            $disabledIdsOfPlayer = array_map(function ($card) { return $card->id; }, $disabledCardsOfPlayer);
            
            $disabledIds = array_merge($disabledIds, $disabledIdsOfPlayer);
        }

        return [
            'disabledIds' => $disabledIds,
            'canChange' => $canChange,
        ];
    }

    function argChangeMimickedCard() {
        $playerId = self::getActivePlayerId();
        return $this->getArgChooseMimickedCard($playerId, MIMIC_CARD, 1);
    }

    function argChooseMimickedCard() {
        $playerId = self::getActivePlayerId();
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
            foreach($intervention->damages as $damage) {
                if ($damage->playerId == $playerId) {
                    $remainingDamage += $damage->damage;
                }
            }

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;

            $rapidHealingHearts = $this->cancellableDamageWithRapidHealing($playerId);
            $rapidHealingCultists = $this->isCthulhuExpansion() ? $this->cancellableDamageWithCultists($playerId) : 0;
            $damageToCancelToSurvive = $this->getDamageToCancelToSurvive($remainingDamage, $this->getPlayerHealth($playerId));
            if (($rapidHealingHearts + $rapidHealingCultists) < $damageToCancelToSurvive) {
                $rapidHealingHearts = 0;
                $rapidHealingCultists = 0;
                $damageToCancelToSurvive = 0;
            }

            $canDoAction = $canThrowDices || $canUseWings || $canUseRobot || $rapidHealingHearts || $rapidHealingCultists || $hasDice3;

            return [
                'canThrowDices' => $canThrowDices,
                'canUseWings' => $canUseWings,
                'canUseRobot' => $canUseRobot,
                'rapidHealingHearts' => $rapidHealingHearts,
                'rapidHealingCultists' => $rapidHealingCultists,
                'damageToCancelToSurvive' => $damageToCancelToSurvive,
                'playerEnergy' => $this->getPlayerEnergy($playerId),
                'dice' => $dice,
                'damage' => $remainingDamage,
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
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

        $potentialEnergy = $this->getPlayerEnergy($playerId);
        if ($this->isCthulhuExpansion()) {
            $potentialEnergy += $this->getPlayerCultists($playerId);
        }

        $tableCards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(function ($card) { return $card->id; }, $tableCards); // can only take from other players, not table
        $unbuyableIds = $disabledIds; // copy
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
                    if (!$this->canBuyCard($playerId, $cardsCosts[$card->id])) {
                        $disabledIds[] = $card->id;
                    }
                } else {
                    $disabledIds[] = $card->id;
                        $unbuyableIds[] = $card->id;
                }
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
            'cardsCosts' => $cardsCosts,
            'unbuyableIds' => $unbuyableIds,
        ];
    }

}
