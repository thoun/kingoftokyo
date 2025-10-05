<?php

namespace KOT\States;

trait CurseCardsArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argGiveSymbolToActivePlayer() {
        $playerId = $this->anubisExpansion->getPlayerIdWithGoldenScarab();

        $canGiveHeart = $this->getPlayerHealth($playerId) > 0;
        $canGiveEnergy = $this->getPlayerEnergy($playerId) > 0;
        $canGivePoint = $this->getPlayerScore($playerId) > 0;

        return [
            'canGive' => [
                4 => $canGiveHeart,
                5 => $canGiveEnergy,
                0 => $canGivePoint,
            ],
        ];
    }

    function argDiscardDie() {
        $playerId = $this->getActivePlayerId();
        
        $dice = $this->getPlayerRolledDice($playerId, true, true, false);
        $selectableDice = $this->getSelectableDice($dice, false, false);

        return [
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->canHealWithDice($playerId),
            'frozenFaces' => $this->frozenFaces($playerId),
        ];
    }

    function argSelectExtraDie() {
        $playerId = $this->getActivePlayerId();

        return [
            'dice' => $this->getPlayerRolledDice($playerId, true, true, false),
            'canHealWithDice' => $this->canHealWithDice($playerId),
            'frozenFaces' => $this->frozenFaces($playerId),
        ];
    }

    function argDiscardKeepCard() {
        $playerId = $this->getActivePlayerId();

        $cards = $this->powerCards->getPlayer($playerId);

        $disabledIds = [];         
        foreach ($cards as $card) {
            if ($card->type >= 100) {
                $disabledIds[] = $card->id;
            }
        }

        return [
            'disabledIds' => $disabledIds,
        ];
    }

    function argGiveGoldenScarab() {
        return [
            'playersIds' => $this->getPlayersIds(),
        ];
    }

    function argGiveSymbols() {
        $playerId = $this->getActivePlayerId();

        $MAPPING = [
            0 => 4,
            1 => 5,
            2 => 0,
        ];

        $resources = [
            $this->getPlayerHealth($playerId),
            $this->getPlayerEnergy($playerId),
            $this->getPlayerScore($playerId),
        ];

        $combinations = [];

        $sum = array_reduce($resources, fn($carry, $item) => $carry + $item);

        // ($sum === 0) { => return empty array
        if ($sum === 1) {
            foreach($resources as $index => $resource) {
                if ($resource > 0) {
                    $combinations[] = [$MAPPING[$index]];
                }
            }
        } else {
            foreach ($resources as $index1 => $resource1) {
                if ($resource1 > 0) {
                    foreach($resources as $index2 => $resource2) {
                        if (($index1 == $index2 && $resource2 >= 2) || ($index2 > $index1 && $resource2 > 0)) {
                            $combinations[] = [$MAPPING[$index1], $MAPPING[$index2]];
                        }
                    }
                }
            }
        }

        return [
            'combinations' => $combinations,
        ];
    }

    function argRerollOrDiscardDie() {
        $activePlayerId = $this->getActivePlayerId();
        $activePlayerDice = $this->getPlayerRolledDice($activePlayerId, true, true, false);
        $selectableDice = $this->getSelectableDice($activePlayerDice, false, false);
        
        return [
            'dice' => $activePlayerDice,
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->frozenFaces($activePlayerId),
            'selectableDice' => $selectableDice,
        ];
    }

    function argRerollDice() {
        $activePlayerId = $this->getActivePlayerId();
        $activePlayerDice = $this->getPlayerRolledDice($activePlayerId, true, true, false);
        $selectableDice = $this->getSelectableDice($activePlayerDice, false, false);

        $playerId = $this->anubisExpansion->getRerollDicePlayerId();

        $diceCount = count(array_filter($activePlayerDice, fn($die) => $die->type < 2));

        $forceRerollTwoDice = $this->anubisExpansion->getCurseCardType() == FALSE_BLESSING_CURSE_CARD;
        $min = min($forceRerollTwoDice ? 2 : 0, $diceCount);
        $max = min(2, $diceCount);

        return [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'dice' => $activePlayerDice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->frozenFaces($activePlayerId),
            'min' => $min,
            'max' => $max,
        ];
    }

}
