<?php

namespace KOT\States;

trait DebugUtilTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function debugSetup() {
        //$this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType(27))[0])->id, /*'table'*/ 'hand', 2343492);
        //$mimickedCard = $this->getCardFromDb(array_values($this->cards->getCardsOfType(37))[0]);
        //$this->setMimickedCard(2343492, $mimickedCard);
        //$this->cards->moveCard( $mimickedCard->id, 'hand', 2343493);
        /*$this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType(8))[0])->id, 'hand', 2343493);
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType(9))[0])->id, 'hand', 2343492);
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType(32))[0])->id, 'hand', 2343493);*/
        //$this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType(26))[0])->id, 'hand', 2343493);
        //$this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType(37))[0])->id, 'table');
        //$this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType(7))[0])->id, 'hand', 2343492);
        $this->debugSetCardInHand(24, 2343492);
        $this->debugSetCardInHand(48, 2343492);
        $this->debugSetPlayerInLocation(2343492, 1);
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
