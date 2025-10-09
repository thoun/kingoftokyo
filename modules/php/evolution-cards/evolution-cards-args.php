<?php

namespace KOT\States;

trait EvolutionCardsArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argAfterEnteringTokyo() {
        $activePlayerId = $this->getActivePlayerId();

        $player = $this->getPlayer($activePlayerId);

        $highlighted = $player->location > 0 && $player->turnEnteredTokyo ?
            $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO) :
            $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO);

        return [
            'highlighted' => $highlighted,
            'noExtraTurnWarning' => $this->mindbugExpansion->canGetExtraTurn() ? [] : [JUNGLE_FRENZY_EVOLUTION],
        ];
    }

    function argCardIsBought() {
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

        $highlighted = $isPowerUpExpansion ? $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT) : [];

        return [
            'highlighted' => $highlighted,
        ];
    }

}
