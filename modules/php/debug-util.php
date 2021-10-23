<?php

namespace KOT\States;

trait DebugUtilTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function debugSetup() {
        global $g_config;
        if (!$g_config['debug_from_chat']) { 
            return;
        } 

        //$mimickedCard = $this->getCardFromDb(array_values($this->cards->getCardsOfType(RAPID_HEALING_CARD))[0]);
        //$this->setMimickedCard(2343492, $mimickedCard);
        //$this->cards->moveCard( $mimickedCard->id, 'hand', 2343493);
        //$this->setMimickedCard(2343492, $this->debugSetCardInHand(31, 2343493));
        $this->debugSetPlayerInLocation(2343493, 1);
        //$this->debugSetPlayerInLocation(2343493, 2);
        //$this->debugSetEnergy(20);
        //$this->debugSetPoints(18);
        //$this->debugSetHealth(1);
        //$this->debugSetPlayerHealth(2343492, 1);
        //self::DbQuery("UPDATE player SET `player_poison_tokens` = 1 where `player_id` = 2343492");
        //$this->debugSetCardInTable(FRENZY_CARD);
        //$this->debugSetCardInTable(HIGH_ALTITUDE_BOMBING_CARD);
        //$this->debugSetCardInTable(JET_FIGHTERS_CARD);
        //$this->debugSetCardInTable(SMOKE_CLOUD_CARD);
        //$this->debugSetCardInTable(ASTRONAUT_CARD);
        self::DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".CHEERLEADER_CARD);
        //$this->debugSetCardInTable(MIMIC_CARD);
        //$this->debugSetCardInTable(RAPID_HEALING_CARD);
        //$this->debugSetCardInTable(MADE_IN_A_LAB_CARD);
        //$this->debugSetCardInHand(MIMIC_CARD, 2343493);
        //$this->setMimickedCard(2343493, $this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343493));
        //$this->setMimickedCard(2343493, $this->debugSetCardInHand(OPPORTUNIST_CARD, 2343494));
        //$this->setMimickedCard(2343493, $this->debugSetCardInHand(RAPID_HEALING_CARD, 2343494));
        //$this->setMimickedCard(2343493, $this->debugSetCardInHand(JETS_CARD, 2343492));
        //$this->setMimickedCard(2343492, $this->debugSetCardInHand(POISON_SPIT_CARD, 2343492));
        //$this->setMimickedCard(2343493, $this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343494));
        //$this->debugSetCardInHand(ALIEN_ORIGIN_CARD, 2343492);
        //$this->debugSetCardInHand(ACID_ATTACK_CARD, 2343493);
        //$this->debugSetCardInHand(BACKGROUND_DWELLER_CARD, 2343492);
        //$this->debugSetCardInHand(FRIEND_OF_CHILDREN_CARD, 2343492);
        //$this->debugSetCardInHand(WINGS_CARD, 2343492);
        //$this->debugSetCardInHand(POISON_QUILLS_CARD, 2343492);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343493);
        //$this->debugSetCardInHand(FREEZE_TIME_CARD, 2343492);
        //$this->debugSetCardInHand(OPPORTUNIST_CARD, 2343492);
        //$this->debugSetCardInHand(CLOWN_CARD, 2343492);
        //$this->debugSetCardInHand(STRETCHY_CARD, 2343492);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debugSetCardInHand(IT_HAS_A_CHILD_CARD, 2343492);
        //$this->debugSetCardInHand(EATER_OF_THE_DEAD_CARD, 2343492);
        //$this->debugSetCardInHand(BURROWING_CARD, 2343492);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343495);
        //$this->debugSetCardInHand(ENERGY_DRINK_CARD, 2343492);
        //$this->debugSetCardInHand(METAMORPH_CARD, 2343492);
        //$this->debugSetCardInHand(RAPID_HEALING_CARD, 2343493);
        //$this->debugSetCardInHand(SHRINK_RAY_CARD, 2343492);
        //$this->debugSetCardInHand(POISON_SPIT_CARD, 2343492);
        //$this->debugSetCardInHand(FIRE_BREATHING_CARD, 2343492);
        //$this->debugSetCardInHand(ARMOR_PLATING_CARD, 2343493);
        //$this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343493);
        //$this->debugSetCardInHand(NOVA_BREATH_CARD, 2343493);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debugSetCardInHand(HERBIVORE_CARD, 2343492);

        $this->gamestate->changeActivePlayer(2343493);

        //$this->eliminatePlayer(2343493);
        //$this->eliminatePlayer(2343494);
        //$this->eliminatePlayer(2343495);
        //$this->eliminatePlayer(2343497);
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
