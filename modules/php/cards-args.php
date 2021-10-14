<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

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
        $diceNumber = $this->getDiceNumber($activePlayerId);

        return [
            'dice' => $this->getDice($diceNumber),
            'inTokyo' => $this->inTokyo($activePlayerId),
        ];
    }

    function argBuyCard() {
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

        $canBuyOrNenew = $playerEnergy >= 2;
        $canSell = $this->countCardOfType($playerId, METAMORPH_CARD) > 0;

        // parasitic tentacles
        $canBuyFromPlayers = $this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) > 0;

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        
        $disabledIds = [];
        foreach ($cards as $card) {
            if ($this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                $canBuyOrNenew = true;
            } else {
                $disabledIds[] = $card->id;
            }
        }

        if ($canBuyFromPlayers) {
            $otherPlayersIds = $this->getOtherPlayersIds($playerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));

                foreach ($cardsOfPlayer as $card) {
                    if ($this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                        $canBuyOrNenew = true;
                    } else {
                        $disabledIds[] = $card->id;
                    }
                }
            }
        }

        // made in a lab
        $canPick = $this->countCardOfType($playerId, MADE_IN_A_LAB_CARD);
        $pickArgs = [];
        if ($canPick > 0) {
            $madeInALabCardIds = $this->getMadeInALabCardIds($playerId);
            $pickCards = $this->getCardsFromDb($this->cards->getCardsOnTop($canPick, 'deck'));
            $this->setMadeInALabCardIds($playerId, array_map(function($card) { return $card->id; }, $pickCards));

            foreach ($pickCards as $card) {
                if ($this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                    $canBuyOrNenew = true;
                } else {
                    $disabledIds[] = $card->id;
                }
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
        ] + $pickArgs;
    }

    function argOpportunistBuyCardWithPlayerId(int $playerId) {        
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        $revealedCardsIds = $opportunistIntervention ? $opportunistIntervention->revealedCardsIds : [];

        $canBuy = false;

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));

        $disabledIds = [];
        foreach ($cards as $card) {
            if (in_array($card->id, $revealedCardsIds) && $this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                $canBuy = true;
            } else {
                $disabledIds[] = $card->id;
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuy' => $canBuy,
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

    function argChangeMimickedCard() {
        return $this->argChooseMimickedCard(true);
    }

    function argChooseMimickedCard($limitToOneEnergy = false) {
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

        $canChange = $playerEnergy >= 1;

        $playersIds = $this->getPlayersIds();

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(function ($card) { return $card->id; }, $cards);
        $mimickedCardId = $this->getMimickedCardId();

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            $disabledCardsOfPlayer = $canChange || !$limitToOneEnergy ? 
                array_values(array_filter($cardsOfPlayer, function ($card) use ($mimickedCardId) { return $card->type == MIMIC_CARD || $card->id == $mimickedCardId || ($card->type >= 100 && $card->type < 200); })) :
                $cardsOfPlayer;
            $disabledIdsOfPlayer = array_map(function ($card) { return $card->id; }, $disabledCardsOfPlayer);
            
            $disabledIds = array_merge($disabledIds, $disabledIdsOfPlayer);
        }

        return [
            'disabledIds' => $disabledIds,
            'canChange' => $canChange,
        ];
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

            $remainingDamage = 0;
            foreach($intervention->damages as $damage) {
                if ($damage->playerId == $playerId) {
                    $remainingDamage += $damage->damage;
                }
            }

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;

            $rapidHealingHearts = $this->showRapidHealingOnDamage($playerId, $remainingDamage);

            return [
                'canThrowDices' => $canThrowDices,
                'canUseWings' => $canUseWings,
                'rapidHealingHearts' => $rapidHealingHearts,
                'playerEnergy' => $this->getPlayerEnergy($playerId),
                'dice' => $dice,
                'damage' => $remainingDamage,
                'rethrow3' => [
                    'hasCard' => $hasBackgroundDweller,
                    'hasDice3' => $hasDice3,
                ],
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

        $tableCards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(function ($card) { return $card->id; }, $tableCards); // can only take from other players, not table

        $canBuyFromPlayers = false;

        $woundedPlayersIds = $this->playersWoundedByActivePlayerThisTurn($playerId);
        $otherPlayersIds = $this->getOtherPlayersIds($playerId);
        foreach($otherPlayersIds as $otherPlayerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));
            $isWoundedPlayer = in_array($otherPlayerId, $woundedPlayersIds);

            foreach ($cardsOfPlayer as $card) {
                if ($isWoundedPlayer && $card->type > 200 && $card->type < 300 && $this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                    $canBuyFromPlayers = true;
                } else {
                    $disabledIds[] = $card->id;
                }
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
        ];
    }

}