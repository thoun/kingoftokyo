<?php

namespace Bga\Games\KingOfTokyo;

function debug(...$debugData) {
    if (Game::getBgaEnvironment() != 'studio') { 
        return;
    }die('debug data : <pre>'.substr(json_encode($debugData, JSON_PRETTY_PRINT), 1, -1).'</pre>');
}

trait DebugUtilTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function debugSetup($playersIds) {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

        if ($this->isOrigins()) {
            $this->debug_SetCardInHand(SCAVENGER_CARD, 2343493);
            //$this->setCardTokens(2343492, $this->debug_SetCardInHand(SMOKE_CLOUD_CARD, 2343492), 4);
            //$this->debug_SetCardInHand(ELECTRIC_ARMOR_CARD, 2343492);
            //$this->debug_SetCardInTable(120);
            //$this->debug_SetCardInTable(121);
            //$this->debug_SetCardInTable(122);
            //$this->debug_SetCardInTable(REGENERATION_CARD);
        }

        // base game

        $this->debug_SetPlayerInLocation(2343492, 1);
        //$this->debug_SetPlayerInLocation(2343494, 2);
        //$this->debug_SetPlayerInLocation($playersIds[0], 1);
        //$this->debug_SetPlayerInLocation($playersIds[1], 2);
        //$this->debug_SetPlayerEnergy(2343492, 9);
        $this->debug_SetEnergy(20);
        //$this->debug_SetPlayerPoints(2343493, 17);
        $this->debug_SetPoints(16);
        //$this->debug_SetPlayerPoints(2343493, 4);
        $this->debug_SetHealth(1);
        //$this->debug_SetPlayerHealth(2343492, 1);
        //$this->debug_SetPlayerHealth(2343493, 1);
        //$this->debug_SetPlayerHealth($playersIds[0], 7);
        //$this->debug_SetPlayerHealth($playersIds[1], 6);
        //$this->debug_SetPlayerHealth($playersIds[2], 3);
        //$this->debug_SetPlayerHealth($playersIds[3], 1);
        //$this->debug_SetPlayerHealth($playersIds[4], 5);
        //$this->DbQuery("UPDATE player SET `player_poison_tokens` = 2 where `player_id` = 2343493");
        //$this->DbQuery("UPDATE player SET `player_poison_tokens` = 1");        
        //$this->DbQuery("UPDATE player SET `player_shrink_ray_tokens` = 1");
        //$this->debug_SetCardInTable(FRENZY_CARD);
        //$this->debug_SetCardInTable(DEATH_FROM_ABOVE_CARD);
        //$this->debug_SetCardInTable(HEAL_CARD);
        //$this->debug_SetCardInTable(HIGH_ALTITUDE_BOMBING_CARD);
        //$this->debug_SetCardInTable(ENERGIZE_CARD);
        //$this->debug_SetCardInTable(JET_FIGHTERS_CARD);
        //$this->debug_SetCardInTable(SMOKE_CLOUD_CARD);
        //$this->debug_SetCardInTable(BATTERY_MONSTER_CARD);
        //$this->debug_SetCardInTable(ASTRONAUT_CARD);
        //$this->debug_SetCardInTable(ARMOR_PLATING_CARD);
        //$this->debug_SetCardInTable(EVEN_BIGGER_CARD);
        //$this->debug_SetCardInTable(EXTRA_HEAD_1_CARD);
        //$this->debug_SetCardInTable(TANK_CARD);
        //$this->debug_SetCardInTable(GAS_REFINERY_CARD);
        //$this->debug_SetCardInTable(FREEZE_TIME_CARD);
        //$this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".MIMIC_CARD);
        //$this->debug_SetCardInTable(MIMIC_CARD);
        //$this->debug_SetCardInTable(RAPID_HEALING_CARD);
        //$this->debug_SetCardInTable(MADE_IN_A_LAB_CARD);
        //$this->debug_SetCardInTable(MIMIC_CARD);
        //$this->debug_SetCardInTable(WINGS_CARD);
        //$this->debug_SetCardInTable(BATTERY_MONSTER_CARD);
        //$this->debug_SetCardInHand(MIMIC_CARD, 2343493);
        //$this->DbQuery("UPDATE card SET `card_location` = 'deck'");
        //foreach ($this->KEEP_CARDS_LIST['dark'] as $cardType) $this->debug_SetCardInTable($cardType);
        //foreach ($this->DISCARD_CARDS_LIST['dark'] as $cardType) $this->debug_SetCardInTable(100+$cardType);
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debug_SetCardInHand(REGENERATION_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debug_SetCardInHand(EVEN_BIGGER_CARD, 2343493));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debug_SetCardInHand(OPPORTUNIST_CARD, 2343494));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debug_SetCardInHand(RAPID_HEALING_CARD, 2343494));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debug_SetCardInHand(JETS_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343492, $this->debug_SetCardInHand(POISON_SPIT_CARD, 2343492));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debug_SetCardInHand(CAMOUFLAGE_CARD, 2343493));
        //$this->setMimickedCard(MIMIC_CARD, 2343493, $this->debug_SetCardInHand(PSYCHIC_PROBE_CARD, 2343494));
        //$this->setCardTokens(2343492, $this->debug_SetCardInHand(BATTERY_MONSTER_CARD, 2343492), 4);
        //$this->setCardTokens(2343492, $this->debug_SetCardInHand(SMOKE_CLOUD_CARD, 2343492), 4);
        //$this->setCardTokens(2343492, $this->debug_SetCardInHand(SMOKE_CLOUD_CARD, 2343492), 3, true);
        //$this->debug_SetCardInHand(BATTERY_MONSTER_CARD, 2343492);
        //$this->setCardTokens(2343493, $this->debug_SetCardInHand(BATTERY_MONSTER_CARD, 2343493), 2, true);
        //$this->setCardTokens(2343493, $this->debug_SetCardInHand(SMOKE_CLOUD_CARD, 2343493), 2, true);
        //$this->debug_SetCardInHand(ARMOR_PLATING_CARD, 2343493);
        //$this->debug_SetCardInHand(MEDIA_FRIENDLY_CARD, 2343492);
        //$this->debug_SetCardInHand(MADE_IN_A_LAB_CARD, 2343492);
        //$this->debug_SetCardInHand(METAMORPH_CARD, 2343492);
        //$this->debug_SetCardInHand(BACKGROUND_DWELLER_CARD, 2343493);
        //$this->debug_SetCardInHand(FRIEND_OF_CHILDREN_CARD, 2343492);
        //$this->debug_SetCardInHand(JETS_CARD, 2343493);
        //$this->debug_SetCardInHand(POISON_QUILLS_CARD, 2343492);
        //$this->debug_SetCardInHand(PARASITIC_TENTACLES_CARD, 2343492);
        //$this->debug_SetCardInHand(SOLAR_POWERED_CARD, 2343492);
        //$this->debug_SetCardInHand(FREEZE_TIME_CARD, 2343492);
        //$this->debug_SetCardInHand(OPPORTUNIST_CARD, 2343493);
        //$this->debug_SetCardInHand(CLOWN_CARD, 2343492);
        //$this->debug_SetCardInHand(STRETCHY_CARD, 2343492);
        //$this->debug_SetCardInHand(HERD_CULLER_CARD, 2343492);
        //$this->debug_SetCardInHand(HEALING_RAY_CARD, 2343492);
        //$this->debug_SetCardInHand(REGENERATION_CARD, 2343493);
        //$this->debug_SetCardInHand(ALPHA_MONSTER_CARD, 2343493);
        //$this->debug_SetCardInHand(EXTRA_HEAD_1_CARD, 2343492);
        //$this->debug_SetCardInHand(EXTRA_HEAD_2_CARD, 2343493);
        //$this->debug_SetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debug_SetCardInHand(IT_HAS_A_CHILD_CARD, 2343493);
        //$this->debug_SetCardInHand(EATER_OF_THE_DEAD_CARD, 2343493);
        //$this->debug_SetCardInHand(BURROWING_CARD, 2343493);
        //$this->debug_SetCardInHand(URBAVORE_CARD, 2343492);
        //$this->debug_SetCardInHand(DEVIL_CARD, 2343492);
        //$this->debug_SetCardInHand(CAMOUFLAGE_CARD, 2343493);
        //$this->debug_SetCardInHand(WINGS_CARD, 2343494);
        //$this->debug_SetCardInHand(ENERGY_DRINK_CARD, 2343492);
        //$this->debug_SetCardInHand(METAMORPH_CARD, 2343493);
        //$this->debug_SetCardInHand(RAPID_HEALING_CARD, 2343493);
        //$this->debug_SetCardInHand(SHRINK_RAY_CARD, 2343492);
        //$this->debug_SetCardInHand(POISON_SPIT_CARD, 2343492);
        //$this->debug_SetCardInHand(FIRE_BREATHING_CARD, 2343492);
        //$this->debug_SetCardInHand(ARMOR_PLATING_CARD, 2343493);
        //$this->debug_SetCardInHand(EVEN_BIGGER_CARD, 2343492);
        //$this->debug_SetPlayerHealth(2343492, 11);
        //$this->debug_SetCardInHand(NOVA_BREATH_CARD, 2343493);
        //$this->debug_SetCardInHand(PLOT_TWIST_CARD, 2343493);
        //$this->debug_SetCardInHand(PSYCHIC_PROBE_CARD, 2343492);
        //$this->debug_SetCardInHand(HERBIVORE_CARD, 2343492);
        //$this->debug_SetCardInHand(COMPLETE_DESTRUCTION_CARD, 2343492);
        //$this->debug_SetCardInHand(WE_RE_ONLY_MAKING_IT_STRONGER_CARD, 2343493);
        //$this->debug_SetCardInHand(EATER_OF_THE_DEAD_CARD, $playersIds[2]);
        //$this->debug_SetCardInHand(HEALING_RAY_CARD, $playersIds[2]);
        //$this->debug_SetCardInHand(BACKGROUND_DWELLER_CARD, $playersIds[4]);
        //$this->debug_SetCardInHand(CAMOUFLAGE_CARD, $playersIds[3]);

        // dark edition

        if ($this->isDarkEdition()) {
            //$this->debug_SetCardInHand(HIBERNATION_CARD, 2343492);
            //$this->debug_SetCardInHand(NANOBOTS_CARD, 2343492);
            //$this->debug_SetCardInHand(SUPER_JUMP_CARD, 2343493);
            $this->debug_SetCardInHand(UNSTABLE_DNA_CARD, 2343493);
            //$this->debug_SetCardInHand(ZOMBIFY_CARD, 2343493);
            //$this->debug_SetCardInHand(REFLECTIVE_HIDE_CARD, 2343493);
        }

        // halloween
        if ($this->isHalloweenExpansion()) {
            //$this->debug_SetCardInHand(ZOMBIE_CARD, 2343493);
            //$this->debug_SetCardInHand(GHOST_CARD, 2343494);
            $this->debug_SetCardInHand(ASTRONAUT_CARD, 2343492);
            //$this->debug_SetCardInHand(DEVIL_CARD, 2343492);
            //$this->debug_SetCardInHand(CHEERLEADER_CARD, 2343493);
            //$this->debug_SetCardInHand(ROBOT_CARD, 2343493);
            $this->debug_SetCardInHand(PRINCESS_CARD, 2343493);
            //$this->debug_SetCardInHand(WITCH_CARD, 2343493);
            //$this->debug_SetCardInHand(VAMPIRE_CARD, 2343492);
            $this->debug_SetCardInHand(PIRATE_CARD, 2343494);
        }

        // cthulhu
        if ($this->cthulhuExpansion->isActive()) {
            $this->debug_SetCultists(5);
            //$this->debug_SetPlayerCultists(2343492, 10);
            //$this->debug_SetPlayerCultists($playersIds[2], 1);
            //$this->debug_SetPlayerCultists($playersIds[3], 3);
            //$this->debug_SetPlayerCultists($playersIds[4], 2);
        }

        // anubis
        if ($this->anubisExpansion->isActive()) {
            //$this->debug_SetCurseCardInTable(INADEQUATE_OFFERING_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(SET_S_STORM_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(FALSE_BLESSING_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(RAGING_FLOOD_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(CONFUSED_SENSES_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(BODY_SPIRIT_AND_KA_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(SCRIBE_S_PERSEVERANCE_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(ORDEAL_OF_THE_MIGHTY_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(PHARAONIC_EGO_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(PHARAONIC_SKIN_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(ISIS_S_DISGRACE_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(GAZE_OF_THE_SPHINX_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(HOTEP_S_PEACE_CURSE_CARD);
            //$this->debug_SetCurseCardInTable(TUTANKHAMUN_S_CURSE_CURSE_CARD);
            //$this->DbQuery("UPDATE curse_card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".CONFUSED_SENSES_CURSE_CARD);
            //$this->DbQuery("UPDATE curse_card SET `card_location_arg` = card_location_arg + 200 where `card_type` = ".RAGING_FLOOD_CURSE_CARD);
            $this->anubisExpansion->changeGoldenScarabOwner(2343494);
        }

        // king kong
        if ($this->kingKongExpansion->isActive()) {
            $this->kingKongExpansion->changeTokyoTowerOwner(2343492, 1);
            $this->kingKongExpansion->changeTokyoTowerOwner(2343492, 2);
        }

        // cybertooth

        if ($this->cybertoothExpansion->isActive()) {
            //$this->setPlayerBerserk(2343492, true);
            //$this->setPlayerBerserk(2343493, true);
        }

        // mutant evolution variant
        if ($this->isMutantEvolutionVariant()) {
            //$this->DbQuery("UPDATE player SET `player_monster` = 12 where `player_id` = 2343492");
            //$this->setBeastForm(2343492, true);
        }

        // power up

        if ($this->powerUpExpansion->isActive() && !$this->powerUpExpansion->isPowerUpMutantEvolution()) {
            //$this->DbQuery("UPDATE player SET `ask_play_evolution` = 1");
            // set monster
            $this->DbQuery("UPDATE player SET `player_monster` = 1 where `player_id` = 2343495"); // space penguin
            //$this->DbQuery("UPDATE player SET `player_monster` = 2 where `player_id` = 2343493"); // alienoid
            $this->DbQuery("UPDATE player SET `player_monster` = 3 where `player_id` = 2343492"); // cyber kitty
            //$this->DbQuery("UPDATE player SET `player_monster` = 4 where `player_id` = 2343493"); // the king
            $this->DbQuery("UPDATE player SET `player_monster` = 5 where `player_id` = 2343493"); // gigazaur
            //$this->DbQuery("UPDATE player SET `player_monster` = 6 where `player_id` = 2343492"); // meka dragon
            $this->DbQuery("UPDATE player SET `player_monster` = 13 where `player_id` = 2343494"); // pandakai
            //$this->DbQuery("UPDATE player SET `player_monster` = 7 where `player_id` = 2343492"); // boogie woogie
            //$this->DbQuery("UPDATE player SET `player_monster` = 8 where `player_id` = 2343492"); // pumpkin jack
            //$this->DbQuery("UPDATE player SET `player_monster` = 14 where `player_id` = 2343493"); // cyber bunny
            //$this->DbQuery("UPDATE player SET `player_monster` = 15 where `player_id` = 2343492"); // kraken
            //$this->DbQuery("UPDATE player SET `player_monster` = 18 where `player_id` = 2343492"); // baby gigazaur

            // dummy card to avoid initial card selection
            $this->debug_SetEvolutionInHand(PRECISION_FIELD_SUPPORT_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(KING_OF_THE_GIZMO_EVOLUTION, 2343493, false);
            $this->debug_SetEvolutionInHand(HEAT_VISION_EVOLUTION, 2343493, false);
            $this->debug_SetEvolutionInHand(RADIOACTIVE_WASTE_EVOLUTION, 2343494, false);
            $this->debug_SetEvolutionInHand(ADAPTING_TECHNOLOGY_EVOLUTION, 2343495, false);
            $this->debug_SetEvolutionInHand(BAMBOOZLE_EVOLUTION, 2343496, false);
            /*$this->debug_SetEvolutionInHand(HEAT_VISION_EVOLUTION, 2343497, false);*/

            // cards to test
            //$this->debug_SetEvolutionInHand(CLIMB_TOKYO_TOWER_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(KING_OF_THE_GIZMO_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(BREATH_OF_DOOM_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(LIGHTNING_ARMOR_EVOLUTION, 2343493, true);
            //$this->debug_SetEvolutionInHand(24, 2343493, true);
            //$this->debug_SetEvolutionInHand(PANDA_EXPRESS_EVOLUTION, 2343493, true);
            //$this->debug_SetEvolutionInHand(ELECTRIC_CARROT_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(TUNE_UP_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(BAMBOO_SUPPLY_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(FREEZE_RAY_EVOLUTION, 2343493, true);
            //$this->debug_SetEvolutionInHand(DEEP_DIVE_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(MIRACULOUS_CATCH_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(BREATH_OF_DOOM_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(SO_SMALL_EVOLUTION, 2343493, true);
            //$this->debug_SetEvolutionInHand(TERROR_OF_THE_DEEP_EVOLUTION, 2343493, true);
            //$this->debug_SetEvolutionInHand(ICY_REFLECTION_EVOLUTION, 2343492, true);
            //$this->setMimickedEvolution(2343492, $this->debug_SetEvolutionInHand(SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, 2343492, true));
            //$this->debug_SetEvolutionInHand(ANGER_BATTERIES_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(BAMBOOZLE_EVOLUTION, 2343493, false);
            //$this->debug_SetEvolutionInHand(EATS_SHOOTS_AND_LEAVES_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(FREEZE_RAY_EVOLUTION, 2343492, true, 2343492);
            //$this->debug_SetEvolutionInHand(WORST_NIGHTMARE_EVOLUTION, 2343492, true, 2343493);
            //$this->debug_SetEvolutionInHand(TRICK_OR_THREAT_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(ENCASED_IN_ICE_EVOLUTION, 2343492, false);
            //$this->debug_SetEvolutionInHand(SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(MOTHERSHIP_SUPPORT_EVOLUTION, 2343492, true);
            //$this->debug_SetEvolutionInHand(SUNKEN_TEMPLE_EVOLUTION, 2343493, false);

            //$this->setGameStateValue(TARGETED_PLAYER, 2343493);
        }

        // player order

        $this->gamestate->changeActivePlayer(2343492);
        //$this->gamestate->changeActivePlayer($playersIds[1]);
        //$this->eliminatePlayer(2343493);
        //$this->eliminatePlayer(2343494);
        //$this->eliminatePlayer(2343495);
        //$this->eliminatePlayer(2343496);
    }

    function debug_SetupBeforePlaceCard() {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

        $this->debug_SetCardInHand(HIBERNATION_CARD, 2343492);
    }

    function debug_SetupAfterPlaceCard() {
        if ($this->getBgaEnvironment() != 'studio') { 
            return;
        } 

        //$this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 1000 where `card_type` = ".MIMIC_CARD);
        $this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 1000 where `card_type` = ".HIGH_ALTITUDE_BOMBING_CARD);
    }

    function debug_SetWickednessTileInTable(int $cardType) {
        $cards = $this->wickednessTiles->getItemsByFieldName('type', [$cardType]);
        $card = $cards[0];
        $this->wickednessTiles->moveItem($card, 'table');
    }

    function debug_SetWickednessTileInHand(int $cardType, int $playerId) {
        $cards = $this->wickednessTiles->getItemsByFieldName('type', [$cardType]);
        $card = $cards[0];
        $this->wickednessTiles->moveItem($card, 'hand', $playerId);
        return $card;
    }

    function debug_SetWickednessTilesInHand(int $playerId, int $side = 0) {
        for ($i = 1; $i <= 10; $i++) {
            $this->debug_SetWickednessTileInHand($side * 100 + $i, $playerId);
        }
    }

    function debug_SetCardInTable(int $cardType) {
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'table', 1);
    }

    // debug_SetCardInDiscard(110)
    function debug_SetCardInDiscard(int $cardType) {
        $this->cards->moveCard( $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0])->id, 'discard');
    }

    function debug_SetCardIn(int $cardId, bool $other = true) {
        $this->notifyAllPlayers("log", 'other = ${otherLog}', [
            'other' => $other,
            'otherLog' => $other,
        ]);

        $this->cards->moveCard($cardId, 'hand', $other ? 23 : 13);
        //return $card;
    }

    public function debug_SetCardInHand(int $cardType, int $playerId) {
        $card = $this->getCardFromDb(array_values($this->cards->getCardsOfType($cardType))[0]);
        $this->cards->moveCard($card->id, 'hand', $playerId);
        return $card;
    }

    function debug_SetEvolutionInHand(int $cardType, int $playerId, bool $visible, $owner = null) {
        $card = $this->getEvolutionCardById(intval(array_values($this->powerUpExpansion->evolutionCards->getCardsOfType($cardType))[0]['id']));
        $this->powerUpExpansion->evolutionCards->moveItem($card, $visible ? 'table' : 'hand', $playerId);
        $ownerId = $owner === null ? $playerId : $owner;
        $this->DbQuery("UPDATE evolution_card SET owner_id=$ownerId WHERE card_id = $card->id");
        return $card;
    }

    function debug_SetPlayerInLocation(int $playerId, int $location) {
        $this->DbQuery("UPDATE player SET `player_location` = $location where `player_id` = $playerId");
    }

    function debug_SetHealth($health) {
        $this->DbQuery("UPDATE player SET `player_health` = $health");
    }

    function debug_SetPlayerHealth(int $playerId, int $health) {
        $this->DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
    }

    function debug_SetPlayerEnergy(int $playerId, int $energy) {
        $this->DbQuery("UPDATE player SET `player_energy` = $energy where `player_id` = $playerId");
    }

    function debug_SetEnergy(int $energy) {
        $this->DbQuery("UPDATE player SET `player_energy` = $energy");
    }

    function debug_SetPlayerPoints(int $playerId, int $points) {
        $this->DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
    }

    function debug_SetPoints(int $points) {
        $this->DbQuery("UPDATE player SET `player_score` = $points");
    }

    function debug_SetCultists(int $cultists) {
        $this->DbQuery("UPDATE player SET `player_cultists` = $cultists");
    }

    function debug_SetPlayerCultists(int $playerId, int $cultists) {
        $this->DbQuery("UPDATE player SET `player_cultists` = $cultists where `player_id` = $playerId");
    }

    function debug_SetCurseCardInTable(int $cardType) {
        if ($this->anubisExpansion->isActive()) {
            $cards = $this->anubisExpansion->curseCards->getItemsByFieldName('type', [$cardType]);
            $card = $cards[0];
            $this->anubisExpansion->curseCards->moveAllItemsInLocation('table', 'discard');
            $this->anubisExpansion->curseCards->moveItem($card, 'table');
        }
    }

    // debug_SetDieOfFate(1)
    // debug_SetDieOfFate(2)
    // debug_SetDieOfFate(3)
    // debug_SetDieOfFate(4)
    function debug_SetDieOfFate(int $face) {
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 2");
    }
    
    // debug_SetDieFaces(1)
    // debug_SetDieFaces(2, 3)
    // debug_SetDieFaces(4, 3)
    // debug_SetDieFaces(6, 3)
    // debug_SetDieFaces(6)
    function debug_SetDieFaces(int $face, int $limit = 99) {
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 0 limit $limit");
    }

    function debugClownRoll() {
        for ($i=6;$i>0;$i--) {
            $this->debug_SetDieFaces($i, $i);
        }
    }

    function debug_AlmostClownRoll() {
        for ($i=6;$i>0;$i--) {
            $this->debug_SetDieFaces(7-$i, $i);
        }
        $this->debug_SetDieFaces(5, 2);
    }

    function debug_SetBerserkDie(int $face) {
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE `type` = 1");
    }

    function debug_SetTakeWickednessTile(int $playerId, int $level = 3) {
        $this->DbQuery("UPDATE player SET `player_take_wickedness_tiles` = '[$level]' where `player_id` = $playerId");
    }

    public function debug_goToState(int $state = ST_NEXT_PLAYER) {
      $this->gamestate->jumpToState($state);
    }
}
