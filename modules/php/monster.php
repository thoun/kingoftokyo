<?php

namespace KOT\States;

trait MonsterTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////


    private function getGameMonsters() {
        $bonusMonsters = intval(self::getGameStateValue(BONUS_MONSTERS_OPTION)) == 2;

        $monsters = [1,2,3,4,5,6];

        if ($bonusMonsters || $this->isHalloweenExpansion()) {
            $monsters = array_merge($monsters, [7,8]);
        }

        /* TODOKK if ($bonusMonsters || $this->isKingKongExpansion()) {
            $monsters = array_merge($monsters, [9]);
        }*/

        /* TODOCY if ($bonusMonsters || $this->isCybertoothExpansion()) {
            $monsters = array_merge($monsters, [10]);
        }*/
        
        return $monsters;
    }

    function canPickMonster() {
        return intval(self::getGameStateValue(PICK_MONSTER_OPTION)) === 2;
    }

    function getAvailableMonsters() {
        $dbResults = self::getCollectionFromDB("SELECT distinct player_monster FROM player WHERE player_monster > 0");
        $pickedMonsters = array_map(function($dbResult) { return intval($dbResult['player_monster']); }, array_values($dbResults));

        $availableMonsters = [];

        $monsters = $this->getGameMonsters();
        foreach ($monsters as $number) {
            if (!in_array($number, $pickedMonsters)) {
                $availableMonsters[] = $number;
            }
        }

        return $availableMonsters;
    }

    function setMonster(int $playerId, int $monsterId) {
        self::DbQuery("UPDATE player SET `player_monster` = $monsterId where `player_id` = $playerId");

        self::notifyAllPlayers('pickMonster', '', [
            'playerId' => $playerId,
            'monster' => $monsterId,
        ]);
    }

    function saveMonsterStat(int $playerId, int $monsterId, bool $automatic) {
        self::setStat($monsterId, 'monster', $playerId);
        self::setStat($monsterId, $automatic ? 'monsterAutomatic': 'monsterPick', $playerId);
    }

    function isBeastForm(int $playerId) {
        $formCard = $this->getFormCard($playerId);
        return $formCard != null && $formCard->side == 1;
    }

    function setBeastForm(int $playerId, bool $beast) {
        $formCard = $this->getFormCard($playerId);
        $side = $beast ? 1 : 0;
        self::DbQuery("UPDATE `card` SET `card_type_arg` = $side where `card_id` = ".$formCard->id);
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

        $playerId = self::getActivePlayerId();

        $this->setMonster($playerId, $monsterId);
        $this->saveMonsterStat($playerId, $monsterId, $automatic);

        $this->gamestate->nextState('next');
    }

    
  	
    public function changeForm() {
        $this->checkAction('changeForm');

        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        $isBeastForm = !$this->isBeastForm($playerId);
        $this->setBeastForm($playerId, $isBeastForm);

        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - 1 where `player_id` = $playerId");

        $message = /*client TODOME translate(*/'${player_name} changes form to ${newForm}'/*)*/;
        $newForm = $isBeastForm ? /*client TODOME translate(*/'Beast form'/*)*/ : /*client TODOME translate(*/'Biped form'/*)*/;
        self::notifyAllPlayers('changeForm', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $this->getFormCard($playerId),
            'energy' => $this->getPlayerEnergy($playerId),
            'newForm' => $newForm,
            'i18n' => ['newForm'],
        ]);

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
        $playerId = self::getActivePlayerId();

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
        if (intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_monster = 0")) == 0) {
            $this->gamestate->nextState('start');
        } else {
            $availableMonsters = $this->getAvailableMonsters();
            if (count($availableMonsters) == 1) {
                $this->pickMonster($availableMonsters[0], true, true);
            }
        }
    }

    function stPickMonsterNextPlayer() {
        $playerId = self::activeNextPlayer();
        self::giveExtraTime($playerId);

        if (intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_monster = 0")) == 0) {
            $this->gamestate->nextState($this->isHalloweenExpansion() ? 'chooseInitialCard' : 'start');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stChangeForm() {
        $playerId = self::getActivePlayerId();
        
        if ($this->autoSkipImpossibleActions() && $this->getPlayerEnergy($playerId) < 1) {
            // skip state, can't change form
            $this->gamestate->nextState('buyCard');
            return;
        }

    }

}
