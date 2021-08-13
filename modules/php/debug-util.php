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
        //$this->debugSetEnergy(5);
        //$this->debugSetPlayerHealth(2343493, 1);
        //$this->debugSetPlayerHealth(2343494, 3);
        //self::DbQuery("UPDATE player SET `player_poison_tokens` = 4 where `player_id` = 2343492");
        //$this->debugSetCardInTable(109);
        //$this->debugSetCardInTable(118);
        //self::DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 200 where `card_type` = 117");
        //$this->debugSetCardInTable(MIMIC_CARD);
        //$this->debugSetCardInTable(MADE_IN_A_LAB_CARD);
        //$this->debugSetEnergy(20);
        //$this->debugSetCardInHand(MIMIC_CARD, 2343492);
        //$this->debugSetCardInHand(13, 2343492);
        //$this->setMimickedCard(2343493, $this->debugSetCardInHand(BURROWING_CARD, 2343493));
        //$this->debugSetCardInHand(BACKGROUND_DWELLER_CARD, 2343493);
        //$this->debugSetCardInHand(WINGS_CARD, 2343493);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343494);
        //$this->debugSetCardInHand(FREEZE_TIME_CARD, 2343492);
        //$this->debugSetCardInHand(STRETCHY_CARD, 2343492);
        //$this->debugSetPlayerPoints(2343493,10);
        //$this->debugSetCardInHand(BURROWING_CARD, 2343492);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343493);
        //$this->debugSetCardInHand(ENERGY_DRINK_CARD, 2343492);
        //$this->debugSetCardInHand(METAMORPH_CARD, 2343492);
        $this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343493);

        $this->gamestate->changeActivePlayer(2343492);
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

    private function debugSetHealth($health) {
        self::DbQuery("UPDATE player SET `player_health` = $health");
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
