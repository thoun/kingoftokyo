<?php

namespace KOT\States;

trait MonsterTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////


    private function getGameMonsters() {
        $bonusMonsters = intval($this->getGameStateValue(BONUS_MONSTERS_OPTION)) == 2;
        $isDarkEdition = $this->isDarkEdition();
        $isOrigins = $this->isOrigins();

        // Base game monsters : Space Penguin, Alienoid, Cyber Kitty, The King, Gigazaur, Meka Dragon
        $monsters = $isOrigins ? [51,52,53,54] : ($isDarkEdition ? [102,104,105,106,114,115] : [1,2,3,4,5,6]);

        // Boogie Woogie, Pumpkin Jack
        if ($bonusMonsters || $this->isHalloweenExpansion()) {
            $monsters = [...$monsters, 7, 8];
        }

        // Cthulhu, Anubis
        if ($bonusMonsters || $this->isCthulhuExpansion() || $this->isAnubisExpansion()) {
            $monsters = [...$monsters, 9, 10];
        }

        // King Kong, Cybertooth
        if ($bonusMonsters || $this->isKingKongExpansion() || $this->isCybertoothExpansion()) {
            $monsters = [...$monsters, 11, 12];
        }

        // Pandakaï
        if ($bonusMonsters || $this->isPowerUpExpansion()) {
            if ($isDarkEdition) {
                if ($bonusMonsters) {
                    $monsters = [...$monsters, 13];
                }
            } else {
                $monsters = [...$monsters, 13];
            }
        }

        // Kookie, X-Smash Tree
        if ($bonusMonsters) {
            $monsters = [...$monsters, 16, 17];
        }

        // Baby Gigazaur
        if ($bonusMonsters) {
            $monsters = [...$monsters, 18];
        }

        // Lollybot
        if ($bonusMonsters/* && $this->releaseDatePassed("2022-04-17T11:00:00", 2)*/) {
            $monsters = [...$monsters, 19];
        }

        // Rob
        if ($bonusMonsters/* && $this->releaseDatePassed("2022-05-04T11:00:00", 2)*/) {
            $monsters = [...$monsters, 21];
        }

        if ($bonusMonsters) {
            // World tour
            /*if ($this->releaseDatePassed("2022-07-01T00:00:00", 2) && !$this->releaseDatePassed("2022-07-08T00:00:00", 2)) {
                $monsters = [...$monsters, 31];
            }
            if ($this->releaseDatePassed("2022-07-08T00:00:00", 2) && !$this->releaseDatePassed("2022-07-15T00:00:00", 2)) {
                $monsters = [...$monsters, 32];
            }
            if ($this->releaseDatePassed("2022-07-15T00:00:00", 2) && !$this->releaseDatePassed("2022-07-22T00:00:00", 2)) {
                $monsters = [...$monsters, 33];
            }
            if ($this->releaseDatePassed("2022-07-22T00:00:00", 2) && !$this->releaseDatePassed("2022-07-29T00:00:00", 2)) {
                $monsters = [...$monsters, 34];
            }
            if ($this->releaseDatePassed("2022-07-29T00:00:00", 2) && !$this->releaseDatePassed("2022-08-05T00:00:00", 2)) {
                $monsters = [...$monsters, 35];
            }
            if ($this->releaseDatePassed("2022-08-05T00:00:00", 2) && !$this->releaseDatePassed("2022-08-12T00:00:00", 2)) {
                $monsters = [...$monsters, 36];
            }
            if ($this->releaseDatePassed("2022-08-12T00:00:00", 2) && !$this->releaseDatePassed("2022-08-19T00:00:00", 2)) {
                $monsters = [...$monsters, 37];
            }
            if ($this->releaseDatePassed("2022-08-19T00:00:00", 2) && !$this->releaseDatePassed("2022-08-26T00:00:00", 2)) {
                $monsters = [...$monsters, 38];
            }*/
            // KoMI
            /*if ($this->releaseDatePassed("2022-11-18T00:00:00", 1) && !$this->releaseDatePassed("2023-01-02T00:00:00", 1)) {
                $monsters = [...$monsters, 41, 42, 43, 44, 45];
            }*/
        }

        if ($this->isWickednessExpansion()) {
            $monsters = array_values(array_filter($monsters, fn($monster) => in_array($monster, $this->MONSTERS_WITH_ICON)));            
        }

        if ($this->isPowerUpExpansion()) {
            $monsters = array_values(array_filter($monsters, fn($monster) => in_array($monster % 100, $this->MONSTERS_WITH_POWER_UP_CARDS)));            
        }
        
        return $monsters;
    }

    function canPickMonster() {
        return intval($this->getGameStateValue(PICK_MONSTER_OPTION)) === 2;
    }

    function getAvailableMonsters() {
        $dbResults = $this->getCollectionFromDb("SELECT distinct player_monster FROM player WHERE player_monster > 0");
        $pickedMonsters = array_map(fn($dbResult) => intval($dbResult['player_monster']), array_values($dbResults));

        $availableMonsters = [];

        $monsters = $this->getGameMonsters();
        foreach ($monsters as $number) {
            if (!in_array($number, $pickedMonsters)) {
                $availableMonsters[] = $number;
            }
        }

        return $availableMonsters;
    }

    function getPlayerMonster(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_monster FROM player WHERE player_id = $playerId"));
    }

    function setMonster(int $playerId, int $monsterId) {
        $this->DbQuery("UPDATE player SET `player_monster` = $monsterId where `player_id` = $playerId");

        $this->notifyAllPlayers('pickMonster', '', [
            'playerId' => $playerId,
            'monster' => $monsterId,
        ]);

        if ($this->isPowerUpExpansion()) {
            $this->evolutionCards->moveAllCardsInLocationKeepOrder('monster'.($monsterId % 100), 'deck'.$playerId);
            $this->evolutionCards->shuffle('deck'.$playerId);
        }
    }

    function saveMonsterStat(int $playerId, int $monsterId, bool $automatic) {
        $this->setStat($monsterId, 'monster', $playerId);
        $this->setStat($monsterId, $automatic ? 'monsterAutomatic': 'monsterPick', $playerId);
    }

    function isBeastForm(int $playerId) {
        $formCard = $this->getFormCard($playerId);
        return $formCard != null && $formCard->side == 1;
    }

    function setBeastForm(int $playerId, bool $beast) {
        $formCard = $this->getFormCard($playerId);
        $side = $beast ? 1 : 0;
        $this->DbQuery("UPDATE `card` SET `card_type_arg` = $side where `card_id` = ".$formCard->id);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function pickMonster(int $monsterId, $skipActionCheck = false, $automatic = false) {
        if (!$skipActionCheck) {
            $this->checkAction('pickMonster');
        }        

        $playerId = $this->getActivePlayerId();

        $this->setMonster($playerId, $monsterId);
        $this->saveMonsterStat($playerId, $monsterId, $automatic);

        $this->gamestate->nextState('next');
    }

    
  	
    public function changeForm() {
        $this->checkAction('changeForm');

        $playerId = $this->getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        $isBeastForm = !$this->isBeastForm($playerId);
        $this->setBeastForm($playerId, $isBeastForm);

        $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` - 1 where `player_id` = $playerId");

        $message = clienttranslate('${player_name} changes form to ${newForm}');
        $newForm = $isBeastForm ? clienttranslate('Beast form') : clienttranslate('Biped form');
        $this->notifyAllPlayers('changeForm', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $this->getFormCard($playerId),
            'energy' => $this->getPlayerEnergy($playerId),
            'newForm' => $newForm,
            'i18n' => ['newForm'],
        ]);

        $this->incStat(1, 'formChanged', $playerId);

        $this->gamestate->nextState('buyCard');
    }
  	
    public function skipChangeForm($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('skipChangeForm');
        }

        $this->gamestate->nextState('buyCard');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPickMonster() {
        return [
            'availableMonsters' => $this->getAvailableMonsters(),
        ];
    }

    function argChangeForm() {
        $playerId = $this->getActivePlayerId();

        $isBeastForm = $this->isBeastForm($playerId);
        $otherForm = $isBeastForm ? clienttranslate('Biped form') : clienttranslate('Beast form');

        $canChangeForm = $this->getPlayerEnergy($playerId) >= 1;

        return [
            'canChangeForm' => $canChangeForm,
            'otherForm' => $otherForm,
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stPickMonster() {
        if (intval($this->getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_monster = 0")) == 0) {
            $this->goToState($this->redirectAfterPickMonster());
        } else {
            $availableMonsters = $this->getAvailableMonsters();
            if (count($availableMonsters) == 1) {
                $this->pickMonster($availableMonsters[0], true, true);
            }
        }
    }

    function stPickMonsterNextPlayer() {
        $playerId = $this->activeNextPlayer();
        $this->giveExtraTime($playerId);

        if (intval($this->getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_monster = 0")) == 0) {

            if ($this->isPowerUpExpansion() && !$this->isPowerUpMutantEvolution()) {
                $this->setOwnerIdForAllEvolutions();
            }

            $this->goToState($this->redirectAfterPickMonster());
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stChangeForm() {
        $playerId = $this->getActivePlayerId();
        
        if (($this->autoSkipImpossibleActions() && $this->getPlayerEnergy($playerId) < 1) || $this->isSureWin($playerId)) {
            // skip state, can't change form
            $this->gamestate->nextState('buyCard');
            return;
        }

    }

}
