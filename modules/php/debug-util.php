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

        // base game

        //$this->debugSetPlayerInLocation(2343492, 1);
        //$this->debugSetPlayerInLocation(2343493, 2);
        //$this->debugSetEnergy(2);
        //$this->debugSetPoints(8);
        //$this->debugSetHealth(8);
        //$this->debugSetPlayerHealth(2343492, 12);
        //$this->debugSetPlayerHealth(2343493, 1);
        //$this->debugSetPlayerEnergy(2343493, 1);
        //$this->debugSetPlayerPoints(2343493, 1);
        //$this->DbQuery("UPDATE player SET `player_poison_tokens` = 1 where `player_id` = 2343492");
        //$this->DbQuery("UPDATE player SET `player_poison_tokens` = 2");
        //$this->debugSetCardInTable(FRENZY_CARD);
        //$this->debugSetCardInTable(HEAL_CARD);
        //$this->debugSetCardInTable(HIGH_ALTITUDE_BOMBING_CARD);
        //$this->debugSetCardInTable(ENERGIZE_CARD);
        //$this->debugSetCardInTable(JET_FIGHTERS_CARD);
        //$this->debugSetCardInTable(SMOKE_CLOUD_CARD);
        //$this->debugSetCardInTable(ASTRONAUT_CARD);
        //$this->debugSetCardInTable(EVEN_BIGGER_CARD);
        //$this->debugSetCardInTable(EXTRA_HEAD_1_CARD);
        //$this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".CHEERLEADER_CARD);
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
        //$this->setCardTokens(2343492, $this->debugSetCardInHand(BATTERY_MONSTER_CARD, 2343492), 4);
        //$this->setCardTokens(2343492, $this->debugSetCardInHand(SMOKE_CLOUD_CARD, 2343492), 4);
        //$this->debugSetCardInHand(ALIEN_ORIGIN_CARD, 2343493);
        //$this->debugSetCardInHand(MEDIA_FRIENDLY_CARD, 2343492);
        //$this->debugSetCardInHand(ACID_ATTACK_CARD, 2343492);
        //$this->debugSetCardInHand(BACKGROUND_DWELLER_CARD, 2343493);
        //$this->debugSetCardInHand(FRIEND_OF_CHILDREN_CARD, 2343492);
        //$this->debugSetCardInHand(WINGS_CARD, 2343492);
        //$this->debugSetCardInHand(POISON_QUILLS_CARD, 2343492);
        //$this->debugSetCardInHand(PARASITIC_TENTACLES_CARD, 2343492);
        //$this->debugSetCardInHand(FREEZE_TIME_CARD, 2343492);
        //$this->debugSetCardInHand(OPPORTUNIST_CARD, 2343493);
        //$this->debugSetCardInHand(CLOWN_CARD, 2343492);
        //$this->debugSetCardInHand(STRETCHY_CARD, 2343492);
        //$this->debugSetCardInHand(HERD_CULLER_CARD, 2343492);
        //$this->debugSetCardInHand(HEALING_RAY_CARD, 2343492);
        //$this->debugSetCardInHand(REGENERATION_CARD, 2343492);
        //$this->debugSetCardInHand(EXTRA_HEAD_1_CARD, 2343492);
        //$this->debugSetCardInHand(EXTRA_HEAD_2_CARD, 2343492);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343493);
        //$this->debugSetCardInHand(IT_HAS_A_CHILD_CARD, 2343493);
        //$this->debugSetCardInHand(EATER_OF_THE_DEAD_CARD, 2343493);
        //$this->debugSetCardInHand(BURROWING_CARD, 2343492);
        //$this->debugSetCardInHand(URBAVORE_CARD, 2343492);
        //$this->debugSetCardInHand(DEVIL_CARD, 2343492);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343493);
        //$this->debugSetCardInHand(ENERGY_DRINK_CARD, 2343492);
        //$this->debugSetCardInHand(METAMORPH_CARD, 2343492);
        //$this->debugSetCardInHand(RAPID_HEALING_CARD, 2343492);
        //$this->debugSetCardInHand(SHRINK_RAY_CARD, 2343492);
        //$this->debugSetCardInHand(POISON_SPIT_CARD, 2343492);
        //$this->debugSetCardInHand(FIRE_BREATHING_CARD, 2343492);
        //$this->debugSetCardInHand(ARMOR_PLATING_CARD, 2343492);
        //$this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343492);
        //$this->debugSetPlayerHealth(2343492, 11);
        //$this->debugSetCardInHand(NOVA_BREATH_CARD, 2343492);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debugSetCardInHand(HERBIVORE_CARD, 2343492);
        //$this->debugSetCardInHand(WE_RE_ONLY_MAKING_IT_STRONGER_CARD, 2343493);

        // dark edition

        //$this->debugSetCardInHand(NANOBOTS_CARD, 2343492);
        //$this->debugSetCardInHand(SUPER_JUMP_CARD, 2343493);
        //$this->debugSetCardInHand(UNSTABLE_DNA_CARD, 2343493);
        //$this->debugSetCardInHand(ZOMBIFY_CARD, 2343493);

        // halloween
        if ($this->isHalloweenExpansion()) {
            $this->debugSetCardInHand(ZOMBIE_CARD, 2343493);
            //$this->debugSetCardInHand(GHOST_CARD, 2343492);
            //$this->debugSetCardInHand(CLOWN_CARD, 2343492);
            $this->debugSetCardInHand(DEVIL_CARD, 2343494);
            //$this->debugSetCardInHand(ROBOT_CARD, 2343493);
            $this->debugSetCardInHand(PRINCESS_CARD, 2343492);
            //$this->debugSetCardInHand(WITCH_CARD, 2343494);
            //$this->debugSetCardInHand(VAMPIRE_CARD, 2343495);
            //$this->debugSetCardInHand(PIRATE_CARD, 2343492);
        }

        // cthulhu
        $this->DbQuery("UPDATE player SET `player_cultists` = 10");
        //$this->DbQuery("UPDATE player SET `player_cultists` = 10 where `player_id` = 2343492");

        // anubis
        if ($this->isAnubisExpansion()) {
            $this->debugSetCurseCardInTable(RAGING_FLOOD_CURSE_CARD);
            //$this->DbQuery("UPDATE curse_card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".FALSE_BLESSING_CURSE_CARD);
            //$this->changeGoldenScarabOwner(2343493);
        }

        // king kong

        //$this->changeTokyoTowerOwner(2343493, 1);
        //$this->changeTokyoTowerOwner(2343493, 2);

        // cybertooth

        //$this->setPlayerBerserk(2343492, true);

        // wickedness

        //$this->initWickednessTiles(3);
        //$this->debugSetWickednessTileInTable(FLUXLING_WICKEDNESS_TILE);
        //$this->DbQuery("UPDATE player SET `player_wickedness` = 4 where `player_id` = 2343493");
        //$this->DbQuery("UPDATE player SET `player_take_wickedness_tile` = 6 where `player_id` = 2343492");
        //$this->debugSetWickednessTileInHand(FLUXLING_WICKEDNESS_TILE, 2343493);
        //$this->setMimickedCard(FLUXLING_WICKEDNESS_TILE, 2343492, $this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343493));

        // power up
        if ($this->isPowerUpExpansion()) {
            $this->debugSetEvolutionInHand(11, 2343492, true);
            $this->debugSetEvolutionInHand(12, 2343492, false);
            $this->debugSetEvolutionInHand(22, 2343493, false);
            $this->debugSetEvolutionInHand(24, 2343493, true);
        }

        // player order

        $this->gamestate->changeActivePlayer(2343492);
        //$this->eliminatePlayer(2343493);
        //$this->eliminatePlayer(2343494);
        //$this->eliminatePlayer(2343495);
        //$this->eliminatePlayer(2343497);
    }

    function debugSetupBeforePlaceCard() {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

        $this->debugSetCardInHand(HIBERNATION_CARD, 2343492);
    }

    function debugSetupAfterPlaceCard() {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

        $this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 1000 where `card_type` = ".HIBERNATION_CARD);
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

    function debugSetEvolutionInHand(int $cardType, int $playerId, bool $visible) {
        $card = $this->getEvolutionCardFromDb(array_values($this->evolutionCards->getCardsOfType($cardType))[0]);
        $this->evolutionCards->moveCard($card->id, $visible ? 'table' : 'hand', $playerId);
        return $card;
    }

    function debugSetPlayerInLocation($playerId, $location) {
        $this->DbQuery("UPDATE player SET `player_location` = $location where `player_id` = $playerId");
    }

    function debugSetHealth($health) {
        $this->DbQuery("UPDATE player SET `player_health` = $health");
    }

    function debugSetPlayerHealth($playerId, $health) {
        $this->DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
    }

    function debugSetPlayerEnergy($playerId, $energy) {
        $this->DbQuery("UPDATE player SET `player_energy` = $energy where `player_id` = $playerId");
    }

    function debugSetEnergy($energy) {
        $this->DbQuery("UPDATE player SET `player_energy` = $energy");
    }

    function debugSetPlayerPoints($playerId, $points) {
        $this->DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
    }

    function debugSetPoints($points) {
        $this->DbQuery("UPDATE player SET `player_score` = $points");
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
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 2");
    }
    
    // debugSetDieFaces(1)
    // debugSetDieFaces(6, 3)
    function debugSetDieFaces($face, $limit = 99) {
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 0 limit $limit");
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
			$this->DbQuery("UPDATE player SET player_id=$sid WHERE player_id = $id" );
			$this->DbQuery("UPDATE global SET global_value=$sid WHERE global_value = $id" );
			$this->DbQuery("UPDATE stats SET stats_player_id=$sid WHERE stats_player_id = $id" );

			// 'other' game specific tables. example:
			// tables specific to your schema that use player_ids
			$this->DbQuery("UPDATE card SET card_location_arg=$sid WHERE card_location_arg = $id" );
			
			++$sid;
		}
	}

    function debug($debugData) {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        }die('debug data : '.json_encode($debugData));
    }
}
