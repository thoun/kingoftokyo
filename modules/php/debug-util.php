<?php

namespace KOT\States;

trait DebugUtilTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function debugSetup() {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

        //$mimickedCard = $this->getCardFromDb(array_values($this->cards->getCardsOfType(RAPID_HEALING_CARD))[0]);
        //$this->setMimickedCard(MIMIC_CARD, 2343492, $mimickedCard);
        //$this->cards->moveCard( $mimickedCard->id, 'hand', 2343493);
        //$this->setMimickedCard(MIMIC_CARD, 2343492, $this->debugSetCardInHand(31, 2343493));
        $this->debugSetPlayerInLocation(2343492, 1);
        //$this->debugSetPlayerInLocation(2343493, 2);
        $this->debugSetEnergy(15);
        $this->debugSetPoints(5);
        $this->debugSetHealth(5);
        //$this->debugSetPlayerHealth(2343492, 1);
        //$this->debugSetPlayerEnergy(2343493, 1);
        //self::DbQuery("UPDATE player SET `player_poison_tokens` = 1 where `player_id` = 2343492");
        //self::DbQuery("UPDATE player SET `player_poison_tokens` = 2");
        //self::DbQuery("UPDATE player SET `player_cultists` = 3 where `player_id` = 2343492");
        //$this->debugSetCardInTable(FRENZY_CARD);
        //$this->debugSetCardInTable(HEAL_CARD);
        //$this->debugSetCardInTable(HIGH_ALTITUDE_BOMBING_CARD);
        //$this->debugSetCardInTable(ENERGIZE_CARD);
        //$this->debugSetCardInTable(JET_FIGHTERS_CARD);
        //$this->debugSetCardInTable(SMOKE_CLOUD_CARD);
        //$this->debugSetCardInTable(ASTRONAUT_CARD);
        //$this->debugSetCardInTable(EVEN_BIGGER_CARD);
        $this->debugSetCardInTable(EXTRA_HEAD_1_CARD, 2343492);
        //self::DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".CHEERLEADER_CARD);
        //$this->debugSetCardInTable(MIMIC_CARD);
        //$this->debugSetCardInTable(RAPID_HEALING_CARD);
        //$this->debugSetCardInTable(MADE_IN_A_LAB_CARD);
        //$this->debugSetCardInHand(MIMIC_CARD, 2343492);
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343493));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(OPPORTUNIST_CARD, 2343494));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(RAPID_HEALING_CARD, 2343494));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(JETS_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343492, $this->debugSetCardInHand(POISON_SPIT_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343492, $this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343494));
        //$this->debugSetCardInHand(ALIEN_ORIGIN_CARD, 2343493);
        //$this->debugSetCardInHand(MEDIA_FRIENDLY_CARD, 2343492);
        //$this->debugSetCardInHand(ACID_ATTACK_CARD, 2343493);
        //$this->debugSetCardInHand(BACKGROUND_DWELLER_CARD, 2343492);
        //$this->debugSetCardInHand(FRIEND_OF_CHILDREN_CARD, 2343492);
        //$this->debugSetCardInHand(WINGS_CARD, 2343493);
        //$this->debugSetCardInHand(POISON_QUILLS_CARD, 2343492);
        //$this->debugSetCardInHand(PARASITIC_TENTACLES_CARD, 2343492);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343492);
        //$this->debugSetCardInHand(FREEZE_TIME_CARD, 2343492);
        //$this->debugSetCardInHand(OPPORTUNIST_CARD, 2343493);
        //$this->debugSetCardInHand(CLOWN_CARD, 2343492);
        //$this->debugSetCardInHand(STRETCHY_CARD, 2343492);
        //$this->debugSetCardInHand(EXTRA_HEAD_1_CARD, 2343492);
        //$this->debugSetCardInHand(EXTRA_HEAD_2_CARD, 2343492);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343494);
        //$this->debugSetCardInHand(IT_HAS_A_CHILD_CARD, 2343492);
        //$this->debugSetCardInHand(EATER_OF_THE_DEAD_CARD, 2343493);
        //$this->debugSetCardInHand(BURROWING_CARD, 2343493);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343492);
        //$this->debugSetCardInHand(ENERGY_DRINK_CARD, 2343492);
        //$this->debugSetCardInHand(METAMORPH_CARD, 2343492);
        //$this->debugSetCardInHand(RAPID_HEALING_CARD, 2343492);
        //$this->debugSetCardInHand(SHRINK_RAY_CARD, 2343492);
        //$this->debugSetCardInHand(POISON_SPIT_CARD, 2343492);
        //$this->debugSetCardInHand(FIRE_BREATHING_CARD, 2343492);
        //$this->debugSetCardInHand(ARMOR_PLATING_CARD, 2343493);
        //$this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343492);
        //$this->debugSetPlayerHealth(2343492, 11);
        //$this->debugSetCardInHand(NOVA_BREATH_CARD, 2343492);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debugSetCardInHand(HERBIVORE_CARD, 2343492);
        //$this->setPlayerBerserk(2343492, true);
        $this->debugSetCurseCardInTable(RAGING_FLOOD_CURSE_CARD);
        //self::DbQuery("UPDATE curse_card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".FALSE_BLESSING_CURSE_CARD);
        //$this->initWickednessTiles(3);
        //$this->debugSetWickednessTileInTable(FLUXLING_WICKEDNESS_TILE);
        //self::DbQuery("UPDATE player SET `player_take_wickedness_tile` = 6 where `player_id` = 2343492");
        //$this->debugSetWickednessTileInHand(FLUXLING_WICKEDNESS_TILE, 2343492);
        //$this->setMimickedCard(FLUXLING_WICKEDNESS_TILE, 2343492, $this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343493));

        $this->gamestate->changeActivePlayer(2343492);

        //$this->eliminatePlayer(2343493);
        //$this->eliminatePlayer(2343494);
        //$this->eliminatePlayer(2343495);
        //$this->eliminatePlayer(2343497);
    }

    function debugSetWickednessTileInTable($cardType) {
        $this->wickednessTiles->moveCard( $this->getCardFromDb(array_values($this->wickednessTiles->getCardsOfType($cardType))[0])->id, 'table');
    }

    function debugSetWickednessTileInHand($cardType, $playerId) {
        $card = $this->getCardFromDb(array_values($this->wickednessTiles->getCardsOfType($cardType))[0]);
        $this->wickednessTiles->moveCard($card->id, 'hand', $playerId);
        return $card;
    }

    function debugSetCardInTable($cardType) {
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'table');
    }

    function debugSetCardInHand($cardType, $playerId) {
        $card = $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0]);
        $this->cards->moveCard($card->id, 'hand', $playerId);
        return $card;
    }

    function debugSetPlayerInLocation($playerId, $location) {
        self::DbQuery("UPDATE player SET `player_location` = $location where `player_id` = $playerId");
    }

    function debugSetHealth($health) {
        self::DbQuery("UPDATE player SET `player_health` = $health");
    }

    function debugSetPlayerHealth($playerId, $health) {
        self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
    }

    function debugSetPlayerEnergy($playerId, $energy) {
        self::DbQuery("UPDATE player SET `player_energy` = $energy where `player_id` = $playerId");
    }

    function debugSetEnergy($energy) {
        self::DbQuery("UPDATE player SET `player_energy` = $energy");
    }

    function debugSetPlayerPoints($playerId, $points) {
        self::DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
    }

    function debugSetPoints($points) {
        self::DbQuery("UPDATE player SET `player_score` = $points");
    }

    function debugSetCurseCardInTable($cardType) {
        if ($this->isAnubisExpansion()) {
            $this->curseCards->moveAllCardsInLocation('table', 'discard');
            $this->curseCards->moveCard($this->getCardFromDb(array_values($this->curseCards->getCardsOfType($cardType))[0])->id, 'table');
        }
    }

    // debugSetDieOfFate(1)
    // debugSetDieOfFate(2)
    // debugSetDieOfFate(3)
    // debugSetDieOfFate(4)
    function debugSetDieOfFate($face) {
        self::DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 2");
    }
    
    // debugSetDieFaces(1)
    // debugSetDieFaces(6)
    function debugSetDieFaces($face) {
        self::DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 0");
    }

    public function debugReplacePlayersIds() {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

		// These are the id's from the BGAtable I need to debug.
		$ids = [
			83846198,
            84582251,
            86175279,
            86769394,
		];

		// Id of the first player in BGA Studio
		$sid = 2343492;
		
		foreach ($ids as $id) {
			// basic tables
			self::DbQuery("UPDATE player SET player_id=$sid WHERE player_id = $id" );
			self::DbQuery("UPDATE global SET global_value=$sid WHERE global_value = $id" );
			self::DbQuery("UPDATE stats SET stats_player_id=$sid WHERE stats_player_id = $id" );

			// 'other' game specific tables. example:
			// tables specific to your schema that use player_ids
			self::DbQuery("UPDATE card SET card_location_arg=$sid WHERE card_location_arg = $id" );
			
			++$sid;
		}
	}

    function debug($debugData) {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        }die('debug data : '.json_encode($debugData));
    }
}
