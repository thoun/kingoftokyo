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

        //$playersIds = array_values(array_map(fn($player) => intval($player['player_id']), $this->getCollectionFromDb("SELECT player_id FROM player order by player_no ")));

        // base game

        $this->debugSetPlayerInLocation(2343493, 1);
        //$this->debugSetPlayerInLocation(2343494, 2);
        //$this->debugSetPlayerInLocation($playersIds[0], 1);
        //$this->debugSetPlayerInLocation($playersIds[1], 2);
        //$this->debugSetPlayerEnergy(2343492, 9);
        $this->debugSetEnergy(10);
        //$this->debugSetPlayerPoints(2343493, 17);
        //$this->debugSetPoints(5);
        //$this->debugSetPlayerPoints(2343493, 4);
        //$this->debugSetHealth(5);
        //$this->debugSetPlayerHealth(2343492, 1);
        //$this->debugSetPlayerHealth(2343493, 1);
        //$this->debugSetPlayerHealth($playersIds[0], 7);
        //$this->debugSetPlayerHealth($playersIds[1], 6);
        //$this->debugSetPlayerHealth($playersIds[2], 3);
        //$this->debugSetPlayerHealth($playersIds[3], 1);
        //$this->debugSetPlayerHealth($playersIds[4], 5);
        //$this->DbQuery("UPDATE player SET `player_poison_tokens` = 2 where `player_id` = 2343493");
        //$this->DbQuery("UPDATE player SET `player_poison_tokens` = 1");        
        //$this->DbQuery("UPDATE player SET `player_shrink_ray_tokens` = 1");
        //$this->debugSetCardInTable(FRENZY_CARD);
        //$this->debugSetCardInTable(DEATH_FROM_ABOVE_CARD);
        //$this->debugSetCardInTable(HEAL_CARD);
        //$this->debugSetCardInTable(HIGH_ALTITUDE_BOMBING_CARD);
        //$this->debugSetCardInTable(ENERGIZE_CARD);
        //$this->debugSetCardInTable(JET_FIGHTERS_CARD);
        //$this->debugSetCardInTable(SMOKE_CLOUD_CARD);
        //$this->debugSetCardInTable(BATTERY_MONSTER_CARD);
        //$this->debugSetCardInTable(ASTRONAUT_CARD);
        //$this->debugSetCardInTable(ARMOR_PLATING_CARD);
        //$this->debugSetCardInTable(EVEN_BIGGER_CARD);
        //$this->debugSetCardInTable(EXTRA_HEAD_1_CARD);
        //$this->debugSetCardInTable(TANK_CARD);
        //$this->debugSetCardInTable(GAS_REFINERY_CARD);
        //$this->debugSetCardInTable(FREEZE_TIME_CARD);
        //$this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".MIMIC_CARD);
        //$this->debugSetCardInTable(MIMIC_CARD);
        //$this->debugSetCardInTable(RAPID_HEALING_CARD);
        //$this->debugSetCardInTable(MADE_IN_A_LAB_CARD);
        //$this->debugSetCardInTable(MIMIC_CARD);
        //$this->debugSetCardInTable(WINGS_CARD);
        //$this->debugSetCardInTable(BATTERY_MONSTER_CARD);
        //$this->debugSetCardInHand(MIMIC_CARD, 2343493);
        //$this->DbQuery("UPDATE card SET `card_location` = 'deck'");
        //foreach ($this->KEEP_CARDS_LIST['dark'] as $cardType) $this->debugSetCardInTable($cardType);
        //foreach ($this->DISCARD_CARDS_LIST['dark'] as $cardType) $this->debugSetCardInTable(100+$cardType);
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(REGENERATION_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343493));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(OPPORTUNIST_CARD, 2343494));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(RAPID_HEALING_CARD, 2343494));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(JETS_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343492, $this->debugSetCardInHand(POISON_SPIT_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343493));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343494));
        //$this->setCardTokens(2343492, $this->debugSetCardInHand(BATTERY_MONSTER_CARD, 2343492), 4);
        //$this->setCardTokens(2343492, $this->debugSetCardInHand(SMOKE_CLOUD_CARD, 2343492), 4);
        //$this->setCardTokens(2343492, $this->debugSetCardInHand(SMOKE_CLOUD_CARD, 2343492), 3, true);
        //$this->debugSetCardInHand(BATTERY_MONSTER_CARD, 2343492);
        //$this->setCardTokens(2343493, $this->debugSetCardInHand(BATTERY_MONSTER_CARD, 2343493), 2, true);
        //$this->setCardTokens(2343493, $this->debugSetCardInHand(SMOKE_CLOUD_CARD, 2343493), 2, true);
        //$this->debugSetCardInHand(ARMOR_PLATING_CARD, 2343493);
        //$this->debugSetCardInHand(MEDIA_FRIENDLY_CARD, 2343492);
        //$this->debugSetCardInHand(MADE_IN_A_LAB_CARD, 2343492);
        //$this->debugSetCardInHand(METAMORPH_CARD, 2343492);
        //$this->debugSetCardInHand(BACKGROUND_DWELLER_CARD, 2343493);
        //$this->debugSetCardInHand(FRIEND_OF_CHILDREN_CARD, 2343492);
        //$this->debugSetCardInHand(JETS_CARD, 2343493);
        //$this->debugSetCardInHand(POISON_QUILLS_CARD, 2343492);
        //$this->debugSetCardInHand(PARASITIC_TENTACLES_CARD, 2343492);
        //$this->debugSetCardInHand(SOLAR_POWERED_CARD, 2343492);
        //$this->debugSetCardInHand(FREEZE_TIME_CARD, 2343492);
        //$this->debugSetCardInHand(OPPORTUNIST_CARD, 2343493);
        //$this->debugSetCardInHand(CLOWN_CARD, 2343492);
        //$this->debugSetCardInHand(STRETCHY_CARD, 2343492);
        //$this->debugSetCardInHand(HERD_CULLER_CARD, 2343492);
        //$this->debugSetCardInHand(HEALING_RAY_CARD, 2343492);
        //$this->debugSetCardInHand(REGENERATION_CARD, 2343493);
        //$this->debugSetCardInHand(ALPHA_MONSTER_CARD, 2343493);
        $this->debugSetCardInHand(EXTRA_HEAD_1_CARD, 2343492);
        $this->debugSetCardInHand(EXTRA_HEAD_2_CARD, 2343493);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debugSetCardInHand(IT_HAS_A_CHILD_CARD, 2343493);
        //$this->debugSetCardInHand(EATER_OF_THE_DEAD_CARD, 2343493);
        //$this->debugSetCardInHand(BURROWING_CARD, 2343493);
        //$this->debugSetCardInHand(URBAVORE_CARD, 2343492);
        //$this->debugSetCardInHand(DEVIL_CARD, 2343492);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, 2343493);
        //$this->debugSetCardInHand(WINGS_CARD, 2343494);
        //$this->debugSetCardInHand(ENERGY_DRINK_CARD, 2343492);
        //$this->debugSetCardInHand(METAMORPH_CARD, 2343493);
        //$this->debugSetCardInHand(RAPID_HEALING_CARD, 2343493);
        //$this->debugSetCardInHand(SHRINK_RAY_CARD, 2343492);
        //$this->debugSetCardInHand(POISON_SPIT_CARD, 2343492);
        //$this->debugSetCardInHand(FIRE_BREATHING_CARD, 2343492);
        //$this->debugSetCardInHand(ARMOR_PLATING_CARD, 2343493);
        //$this->debugSetCardInHand(EVEN_BIGGER_CARD, 2343492);
        //$this->debugSetPlayerHealth(2343492, 11);
        //$this->debugSetCardInHand(NOVA_BREATH_CARD, 2343493);
        //$this->debugSetCardInHand(PLOT_TWIST_CARD, 2343493);
        //$this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debugSetCardInHand(HERBIVORE_CARD, 2343492);
        //$this->debugSetCardInHand(COMPLETE_DESTRUCTION_CARD, 2343492);
        //$this->debugSetCardInHand(WE_RE_ONLY_MAKING_IT_STRONGER_CARD, 2343493);
        //$this->debugSetCardInHand(EATER_OF_THE_DEAD_CARD, $playersIds[2]);
        //$this->debugSetCardInHand(HEALING_RAY_CARD, $playersIds[2]);
        //$this->debugSetCardInHand(BACKGROUND_DWELLER_CARD, $playersIds[4]);
        //$this->debugSetCardInHand(CAMOUFLAGE_CARD, $playersIds[3]);

        // dark edition

        if ($this->isDarkEdition()) {
            //$this->debugSetCardInHand(HIBERNATION_CARD, 2343492);
            //$this->debugSetCardInHand(NANOBOTS_CARD, 2343492);
            //$this->debugSetCardInHand(SUPER_JUMP_CARD, 2343493);
            //$this->debugSetCardInHand(UNSTABLE_DNA_CARD, 2343493);
            //$this->debugSetCardInHand(ZOMBIFY_CARD, 2343493);
            //$this->debugSetCardInHand(REFLECTIVE_HIDE_CARD, 2343493);
        }

        // halloween
        if ($this->isHalloweenExpansion()) {
            //$this->debugSetCardInHand(ZOMBIE_CARD, 2343493);
            //$this->debugSetCardInHand(GHOST_CARD, 2343494);
            $this->debugSetCardInHand(CLOWN_CARD, 2343492);
            //$this->debugSetCardInHand(DEVIL_CARD, 2343492);
            //$this->debugSetCardInHand(CHEERLEADER_CARD, 2343493);
            //$this->debugSetCardInHand(ROBOT_CARD, 2343493);
            $this->debugSetCardInHand(PRINCESS_CARD, 2343493);
            //$this->debugSetCardInHand(WITCH_CARD, 2343493);
            //$this->debugSetCardInHand(VAMPIRE_CARD, 2343492);
            //$this->debugSetCardInHand(PIRATE_CARD, 2343494);
        }

        // cthulhu
        if ($this->isCthulhuExpansion()) {
            $this->debugSetCultists(5);
            //$this->debugSetPlayerCultists(2343492, 10);
            //$this->debugSetPlayerCultists($playersIds[2], 1);
            //$this->debugSetPlayerCultists($playersIds[3], 3);
            //$this->debugSetPlayerCultists($playersIds[4], 2);
        }

        // anubis
        if ($this->isAnubisExpansion()) {
            //$this->debugSetCurseCardInTable(INADEQUATE_OFFERING_CURSE_CARD);
            //$this->debugSetCurseCardInTable(SET_S_STORM_CURSE_CARD);
            //$this->debugSetCurseCardInTable(FALSE_BLESSING_CURSE_CARD);
            //$this->debugSetCurseCardInTable(RAGING_FLOOD_CURSE_CARD);
            //$this->debugSetCurseCardInTable(CONFUSED_SENSES_CURSE_CARD);
            //$this->debugSetCurseCardInTable(BODY_SPIRIT_AND_KA_CURSE_CARD);
            //$this->debugSetCurseCardInTable(SCRIBE_S_PERSEVERANCE_CURSE_CARD);
            //$this->debugSetCurseCardInTable(ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD);
            //$this->debugSetCurseCardInTable(ORDEAL_OF_THE_MIGHTY_CURSE_CARD);
            //$this->debugSetCurseCardInTable(PHARAONIC_EGO_CURSE_CARD);
            //$this->debugSetCurseCardInTable(PHARAONIC_SKIN_CURSE_CARD);
            $this->debugSetCurseCardInTable(ISIS_S_DISGRACE_CURSE_CARD);
            //$this->debugSetCurseCardInTable(GAZE_OF_THE_SPHINX_CURSE_CARD);
            //$this->debugSetCurseCardInTable(HOTEP_S_PEACE_CURSE_CARD);
            //$this->debugSetCurseCardInTable(TUTANKHAMUN_S_CURSE_CURSE_CARD);
            //$this->DbQuery("UPDATE curse_card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".CONFUSED_SENSES_CURSE_CARD);
            //$this->DbQuery("UPDATE curse_card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".RAGING_FLOOD_CURSE_CARD);
            $this->changeGoldenScarabOwner(2343494);
        }

        // king kong
        if ($this->isKingKongExpansion()) {
            //$this->changeTokyoTowerOwner(2343492, 1);
            //$this->changeTokyoTowerOwner(2343492, 2);
        }

        // cybertooth

        if ($this->isCybertoothExpansion()) {
            //$this->setPlayerBerserk(2343492, true);
            //$this->setPlayerBerserk(2343493, true);
        }

        // mutant evolution variant
        if ($this->isMutantEvolutionVariant()) {
            //$this->DbQuery("UPDATE player SET `player_monster` = 12 where `player_id` = 2343492");
            //$this->setBeastForm(2343492, true);
        }


        // wickedness
        if ($this->isWickednessExpansion()) {
            //$this->initWickednessTiles(3); // 2=orange, 3=green, 4=mix
            //$this->debugSetWickednessTileInTable(FLUXLING_WICKEDNESS_TILE);
            //$this->DbQuery("UPDATE player SET `player_wickedness` = 2 where `player_id` = 2343492");
            $this->DbQuery("UPDATE player SET `player_take_wickedness_tiles` = '[6]' where `player_id` = 2343492");
            //$this->debugSetWickednessTileInHand(FLUXLING_WICKEDNESS_TILE, 2343493);
            //$this->setMimickedCard(FLUXLING_WICKEDNESS_TILE, 2343492, $this->debugSetCardInHand(PSYCHIC_PROBE_CARD, 2343492));
            //$this->debugSetWickednessTileInHand(UNDERDOG_WICKEDNESS_TILE, 2343492);
            //$this->debugSetWickednessTileInHand(FINAL_ROAR_WICKEDNESS_TILE, 2343493);
            //$this->debugSetWickednessTileInHand(BARBS_WICKEDNESS_TILE, 2343492);
            //$this->debugSetWickednessTileInHand(DEFENDER_OF_TOKYO_WICKEDNESS_TILE, 2343492);
            //$this->debugSetWickednessTileInHand(TIRELESS_WICKEDNESS_TILE, 2343492);
            //$this->debugSetWickednessTileInHand(CYBERBRAIN_WICKEDNESS_TILE, 2343492);

            //$this->DbQuery("UPDATE player SET `player_wickedness` = 2 where `player_id` = 2343493");

            //$this->debugSetWickednessTileInHand(DEVIOUS_WICKEDNESS_TILE, 2343494);
            //$this->debugSetWickednessTileInHand(ETERNAL_WICKEDNESS_TILE, 2343494);
            //$this->debugSetWickednessTileInHand(SKULKING_WICKEDNESS_TILE, 2343494);
        }

        // power up

        if ($this->isPowerUpExpansion() && !$this->isPowerUpMutantEvolution()) {
            //$this->DbQuery("UPDATE player SET `ask_play_evolution` = 1");
            // set monster
            $this->DbQuery("UPDATE player SET `player_monster` = 1 where `player_id` = 2343495"); // space penguin
            //$this->DbQuery("UPDATE player SET `player_monster` = 2 where `player_id` = 2343493"); // alienoid
            $this->DbQuery("UPDATE player SET `player_monster` = 3 where `player_id` = 2343492"); // cyber kitty
            //$this->DbQuery("UPDATE player SET `player_monster` = 4 where `player_id` = 2343493"); // the king
            $this->DbQuery("UPDATE player SET `player_monster` = 5 where `player_id` = 2343494"); // gigazaur
            //$this->DbQuery("UPDATE player SET `player_monster` = 6 where `player_id` = 2343492"); // meka dragon
            $this->DbQuery("UPDATE player SET `player_monster` = 13 where `player_id` = 2343493"); // pandakai
            //$this->DbQuery("UPDATE player SET `player_monster` = 7 where `player_id` = 2343492"); // boogie woogie
            //$this->DbQuery("UPDATE player SET `player_monster` = 8 where `player_id` = 2343492"); // pumpkin jack
            //$this->DbQuery("UPDATE player SET `player_monster` = 14 where `player_id` = 2343493"); // cyber bunny
            //$this->DbQuery("UPDATE player SET `player_monster` = 15 where `player_id` = 2343492"); // kraken
            //$this->DbQuery("UPDATE player SET `player_monster` = 18 where `player_id` = 2343492"); // baby gigazaur

            // dummy card to avoid initial card selection
            $this->debugSetEvolutionInHand(PRECISION_FIELD_SUPPORT_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(KING_OF_THE_GIZMO_EVOLUTION, 2343493, false);
            $this->debugSetEvolutionInHand(ADAPTING_TECHNOLOGY_EVOLUTION, 2343493, false);
            $this->debugSetEvolutionInHand(RADIOACTIVE_WASTE_EVOLUTION, 2343494, false);
            $this->debugSetEvolutionInHand(ADAPTING_TECHNOLOGY_EVOLUTION, 2343495, false);
            $this->debugSetEvolutionInHand(BAMBOOZLE_EVOLUTION, 2343496, false);
            /*$this->debugSetEvolutionInHand(HEAT_VISION_EVOLUTION, 2343497, false);*/

            // cards to test
            //$this->debugSetEvolutionInHand(CLIMB_TOKYO_TOWER_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(KING_OF_THE_GIZMO_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(BREATH_OF_DOOM_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(LIGHTNING_ARMOR_EVOLUTION, 2343493, true);
            //$this->debugSetEvolutionInHand(24, 2343493, true);
            //$this->debugSetEvolutionInHand(PANDA_EXPRESS_EVOLUTION, 2343493, true);
            //$this->debugSetEvolutionInHand(ELECTRIC_CARROT_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(TUNE_UP_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(BAMBOO_SUPPLY_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(FREEZE_RAY_EVOLUTION, 2343493, true);
            //$this->debugSetEvolutionInHand(DEEP_DIVE_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(MIRACULOUS_CATCH_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(BREATH_OF_DOOM_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(SO_SMALL_EVOLUTION, 2343493, true);
            //$this->debugSetEvolutionInHand(TERROR_OF_THE_DEEP_EVOLUTION, 2343493, true);
            //$this->debugSetEvolutionInHand(ICY_REFLECTION_EVOLUTION, 2343492, true);
            //$this->setMimickedEvolution(2343492, $this->debugSetEvolutionInHand(SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, 2343492, true));
            //$this->debugSetEvolutionInHand(ANGER_BATTERIES_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(BAMBOOZLE_EVOLUTION, 2343493, false);
            //$this->debugSetEvolutionInHand(EATS_SHOOTS_AND_LEAVES_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(FREEZE_RAY_EVOLUTION, 2343492, true, 2343492);
            //$this->debugSetEvolutionInHand(WORST_NIGHTMARE_EVOLUTION, 2343492, true, 2343493);
            //$this->debugSetEvolutionInHand(TRICK_OR_THREAT_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(ENCASED_IN_ICE_EVOLUTION, 2343492, false);
            //$this->debugSetEvolutionInHand(SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, 2343492, true);
            //$this->debugSetEvolutionInHand(MOTHERSHIP_SUPPORT_EVOLUTION, 2343492, true);
            $this->debugSetEvolutionInHand(SUNKEN_TEMPLE_EVOLUTION, 2343493, false);

            //$this->setGameStateValue(TARGETED_PLAYER, 2343493);
        }

        // player order

        $this->gamestate->changeActivePlayer(2343493);
        //$this->gamestate->changeActivePlayer($playersIds[1]);
        //$this->eliminatePlayer(2343493);
        //$this->eliminatePlayer(2343494);
        //$this->eliminatePlayer(2343495);
        //$this->eliminatePlayer(2343496);
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

        //$this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 1000 where `card_type` = ".MIMIC_CARD);
        $this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 1000 where `card_type` = ".HIGH_ALTITUDE_BOMBING_CARD);
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
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'table', 1);
    }

    // debugSetCardInDiscard(110)
    function debugSetCardInDiscard($cardType) {
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'discard');
    }

    function debugSetCardInHand($cardType, $playerId) {
        $card = $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0]);
        $this->cards->moveCard($card->id, 'hand', $playerId);
        return $card;
    }

    function debugSetEvolutionInHand(int $cardType, int $playerId, bool $visible, $owner = null) {
        $card = $this->getEvolutionCardById(intval(array_values($this->evolutionCards->getCardsOfType($cardType))[0]['id']));
        $this->evolutionCards->moveCard($card->id, $visible ? 'table' : 'hand', $playerId);
        $ownerId = $owner === null ? $playerId : $owner;
        $this->DbQuery("UPDATE evolution_card SET owner_id=$ownerId WHERE card_id = $card->id");
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

    function debugSetCultists($cultists) {
        $this->DbQuery("UPDATE player SET `player_cultists` = $cultists");
    }

    function debugSetPlayerCultists($playerId, $cultists) {
        $this->DbQuery("UPDATE player SET `player_cultists` = $cultists where `player_id` = $playerId");
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
    // debugSetDieFaces(2, 3)
    // debugSetDieFaces(4, 3)
    // debugSetDieFaces(6, 3)
    // debugSetDieFaces(6)
    function debugSetDieFaces($face, $limit = 99) {
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 0 limit $limit");
    }

    function debugClownRoll() {
        for ($i=6;$i>0;$i--) {
            $this->debugSetDieFaces($i, $i);
        }
    }

    function debugAlmostClownRoll() {
        for ($i=6;$i>0;$i--) {
            $this->debugSetDieFaces(7-$i, $i);
        }
        $this->debugSetDieFaces(5, 2);
    }

    function debugSetBerserkDie($face) {
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 1");
    }

    public function debugReplacePlayersIds() {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

		// These are the id's from the BGAtable I need to debug.
        // SELECT JSON_ARRAYAGG(`player_id`) FROM `player`
		$ids = [85136910, 85358567, 86251986, 91307910];

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
			$this->DbQuery("UPDATE wickedness_tile SET card_location_arg=$sid WHERE card_location_arg = $id" );
			$this->DbQuery("UPDATE evolution_card SET card_location_arg=$sid WHERE card_location_arg = $id" );
			$this->DbQuery("UPDATE evolution_card SET owner_id=$sid WHERE owner_id = $id" );
			$this->DbQuery("UPDATE evolution_card SET card_location='deck$sid' WHERE card_location='deck$id'" );
			$this->DbQuery("UPDATE evolution_card SET card_location='discard$sid' WHERE card_location='discard$id'" );
            $this->DbQuery("UPDATE global_variables set `value` = REPLACE(`value`, '$id', '$sid')" );
			
			++$sid;
		}
	}

    function debug($debugData) {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        }die('debug data : '.json_encode($debugData));
    }

    function log(...$debugData) { // debug with infinite arguments
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        }die('debug data : '.implode(', ', array_map(fn($d) => json_encode($d), $debugData)));
    }
}
