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

        // return values:
        return [
            'disabledIds' => $disabledIds,
        ];
    }

}
