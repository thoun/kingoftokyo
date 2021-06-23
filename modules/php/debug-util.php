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
        //$this->setMimickedCard(2343492, $this->debugSetCardInHand(31, 2343493));
        $this->debugSetPlayerInLocation(2343492, 1);
        //$this->debugSetPlayerInLocation(2343493, 2);
        //$this->debugSetPlayerHealth(2343492, 1);
        //$this->debugSetCardInTable(113);
        //$this->debugSetCardInTable(SMOKE_CLOUD_CARD);
        $this->debugSetPlayerEnergy(2343492, 10);
        //$this->setMimickedCard(2343492, $this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343493));
        //$this->debugSetCardInHand(PLOT_TWIST_CARD, 2343492);
        //$this->debugSetCardInHand(BACKGROUND_DWELLER_CARD, 2343492);
        //$this->debugSetPlayerPoints(2343493,10);

        // Activate first player must be commented in setup if this is used
        $this->gamestate->changeActivePlayer(2343493);
    }

    private function debugSetCardInTable($cardType) {
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'table');
    }

    private function debugSetCardInHand($cardType, $playerId) {
        $card = $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0]);
        $this->cards->moveCard($card->id, 'hand', $playerId);
        return $card;
    }

    private function debugSetPlayerInLocation($playerId, $location) {
        self::DbQuery("UPDATE player SET `player_location` = $location where `player_id` = $playerId");
    }

    private function debugSetPlayerHealth($playerId, $health) {
        self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
    }

    private function debugSetPlayerEnergy($playerId, $energy) {
        self::DbQuery("UPDATE player SET `player_energy` = $energy where `player_id` = $playerId");
    }

    private function debugSetEnergy($energy) {
        self::DbQuery("UPDATE player SET `player_energy` = $energy");
    }

    private function debugSetPlayerPoints($playerId, $points) {
        self::DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
    }

    private function debugSetPoints($points) {
        self::DbQuery("UPDATE player SET `player_score` = $points");
    }
}
