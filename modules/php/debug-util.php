<?php

namespace KOT\States;

trait DebugUtilTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function debugSetup() {
        //$mimickedCard = $this->getCardFromDb(array_values($this->cards->getCardsOfType(RAPID_HEALING_CARD))[0]);
        //$this->setMimickedCard(2343492, $mimickedCard);
        //$this->cards->moveCard( $mimickedCard->id, 'hand', 2343493);
        $this->debugSetCardInHand(MIMIC_CARD, 2343492);
        $this->debugSetCardInTable(102);
        $this->debugSetCardInTable(106);
        $this->debugSetCardInTable(107);
        $this->debugSetCardInTable(111);
        $this->debugSetCardInTable(112);
        $this->debugSetCardInTable(113);
        $this->debugSetCardInTable(114);
        $this->debugSetCardInTable(115);
        //$this->debugSetPlayerInLocation(2343492, 1);
    }

    private function debugSetCardInTable($cardType) {
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'table');
    }

    private function debugSetCardInHand($cardType, $playerId) {
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'hand', $playerId);
    }

    private function debugSetPlayerInLocation($playerId, $location) {
        self::DbQuery("UPDATE player SET `player_location` = $location where `player_id` = $playerId");
    }
}
