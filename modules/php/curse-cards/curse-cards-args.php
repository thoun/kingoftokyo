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
        $playerId = $this->getPlayerIdWithGoldenScarab();

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
        $playerId = self::getActivePlayerId();

        return [
            'dice' => $this->getPlayerRolledDice($playerId, true, true, false),
            'canHealWithDice' => $this->canHealWithDice($playerId),
        ];
    }

    function argDiscardKeepCard() {
        $playerId = self::getActivePlayerId();

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));

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
        $playerId = self::getActivePlayerId();

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

        $sum = array_reduce($resources, function ($carry, $item) { return $carry + $item; });

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
        $activePlayerId = self::getActivePlayerId();
        
        return [
            'dice' => $this->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
        ];
    }

    function argRerollDice() {
        $activePlayerId = self::getActivePlayerId();
        $activePlayerDice = $this->getPlayerRolledDice($activePlayerId, true, true, false);

        $playerId = $this->getRerollDicePlayerId();

        $diceCount = count(array_filter($activePlayerDice, function ($die) { return $die->type < 2; }));

        $forceRerollTwoDice = $this->getCurseCardType() == FALSE_BLESSING_CURSE_CARD;
        $min = min($forceRerollTwoDice ? 2 : 0, $diceCount);
        $max = min(2, $diceCount);

        return [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'dice' => $activePlayerDice,
            'canHealWithDice' => $this->canHealWithDice($activePlayerId),
            'min' => $min,
            'max' => $max,
        ];
    }

}
