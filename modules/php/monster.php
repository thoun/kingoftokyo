<?php

namespace KOT\States;

trait MonsterTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function canPickMonster() {
        return intval(self::getGameStateValue(PICK_MONSTER_OPTION)) === 2;
    }

    function getAvailableMonsters() {
        $dbResults = self::getCollectionFromDB("SELECT distinct player_monster FROM player WHERE player_monster > 0");
        $pickedMonsters = array_map(function($dbResult) { return intval($dbResult['player_monster']); }, array_values($dbResults));

        $availableMonsters = [];

        for ($i = 1; $i <= 6; $i++) {
            if (array_search($i, $pickedMonsters) === false) {
                $availableMonsters[] = $i;
            }
        }

        // TODO and test zombie in pick monster
        return $availableMonsters;
    }

    function setMonster(int $playerId, int $monsterId) {
        self::DbQuery("UPDATE player SET `player_monster` = $monsterId where `player_id` = $playerId");

        self::notifyAllPlayers('pickMonster', '', [
            'playerId' => $playerId,
            'monster' => $monsterId,
        ]);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function pickMonster(int $monsterId) {
        $playerId = self::getActivePlayerId();

        $this->setMonster($playerId, $monsterId);

        $this->gamestate->nextState('next');
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

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stPickMonster() {
        if (intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_monster = 0")) == 0) {
            $this->gamestate->nextState('start');
        }
    }

    function stPickMonsterNextPlayer() {
        $playerId = self::activeNextPlayer();
        self::giveExtraTime($playerId);

        if (intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_monster = 0")) == 0) {
            $this->gamestate->nextState('start');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }

}