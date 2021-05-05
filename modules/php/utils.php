<?php

namespace KOT\States;

require_once(__DIR__.'/../dice.php');
require_once(__DIR__.'/../card.php');
require_once(__DIR__.'/../player.php');

use KOT\Card;
use KOT\Dice;
use KOT\Player;

trait UtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////


    function initCards() {
        $cards = [];
        
        for( $value=1; $value<=48; $value++ ) { // keep
            $cards[] = ['type' => $value, 'type_arg' => $this->cardsCosts[$value], 'nbr' => 1];
        }
        
        for( $value=101; $value<=118; $value++ ) { // discard
            $cards[] = ['type' => $value, 'type_arg' => $this->cardsCosts[$value], 'nbr' => 1];
        }
            
        // $this->cards->createCards( array_slice($cards, count($cards) - 10, 10), 'deck' );
        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck'); 
    }

    function getCardFromDb(array $dbCard) {
        if (!$dbCard || !array_key_exists('id', $dbCard)) {
            throw new Error('card doesn\'t exists '.json_encode($dbCard));
        }
        if (!$dbCard || !array_key_exists('location', $dbCard)) {
            throw new Error('location doesn\'t exists '.json_encode($dbCard));
        }
        return new Card($dbCard);
    }

    function getCardsFromDb(array $dbCards) {
        return array_map(function($dbCard) { return $this->getCardFromDb($dbCard); }, array_values($dbCards));
    }

    function getMaxPlayerScore() {
        return intval(self::getUniqueValueFromDB("SELECT max(player_score) FROM player"));
    }

    function getPlayerName(int $playerId) {
        return self::getUniqueValueFromDb("SELECT player_name FROM player WHERE player_id = $playerId");
    }

    function getPlayerScore(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId"));
    }

    function getPlayerHealth(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_health FROM player where `player_id` = $playerId"));
    }

    function getPlayerEnergy(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_energy FROM player where `player_id` = $playerId"));
    }

    function getThrowNumber(int $playerId) {
        return 3; // TODO
    }

    function getPlayerMaxHealth(int $playerId) {
        // even bigger set to 12
        return $this->hasCardByType($playerId, 12) ? 12 : 10;
    }

    function getRemainingPlayers() {
        return intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_eliminated = 0"));
    }

    function tokyoBayUsed() {
        return $this->getRemainingPlayers() > 4;
    }

    function isTokyoEmpty(bool $bay) {
        $location = $bay ? 2 : 1;
        $players = intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_location = $location"));
        return $players == 0;
    }

    function moveToTokyo(int $playerId, bool $bay) {
        $location = $bay ? 2 : 1;
        $incScore = 1;
        self::DbQuery("UPDATE player SET player_score = player_score + $incScore, player_location = $location where `player_id` = $playerId");

        $locationName = $bay ? _('Tokyo Bay') : _('Tokyo City');
        self::notifyAllPlayers("playerEntersTokyo", clienttranslate('${player_name} enters ${locationName} !'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'location' => $location,
            'locationName' => $locationName,
        ]);
    }

    function leaveTokyo($playerId) {

        self::DbQuery("UPDATE player SET player_location = 0 where `player_id` = $playerId");

        self::notifyAllPlayers("leaveTokyo", clienttranslate('${player_name} chooses to leave Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    }

    function inTokyo(int $playerId) {
        $location = intval(self::getUniqueValueFromDB( "SELECT player_location FROM player WHERE player_id = $playerId"));
        return $location > 0;
    }

    // get players ids

    private function getPlayersIdsFromLocation(bool $inside) {
        $sign = $inside ? '>' : '=';
        $sql = "SELECT player_id FROM player WHERE player_location $sign 0 AND player_eliminated = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    function getPlayerIdInTokyoCity() {
        $sql = "SELECT player_id FROM player WHERE player_location = 1 AND player_eliminated = 0 ORDER BY player_no";
        return intval(self::getUniqueValueFromDB($sql));
    }

    function getPlayersIdsInTokyo() {
        return $this->getPlayersIdsFromLocation(true);
    }

    /*function getPlayersIdsOutsideTokyo() {
        return $this->getPlayersIdsFromLocation(false);
    }*/

    function getPlayersIds() {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    function getOtherPlayersIds(int $playerId) {
        $sql = "SELECT player_id FROM player WHERE player_id <> $playerId AND player_eliminated = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    // get players    
    function getPlayers(bool $includeEliminated = false) {
        $sql = "SELECT * FROM player";
        if (!$includeEliminated) {
            $sql .= " WHERE player_eliminated = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return new Player($dbResult); }, array_values($dbResults));
    }
    
    function eliminatePlayers(int $currentTurnPlayerId) {
        $players = $this->getPlayers(true);
        // TODO UnitTests

        $playerIndex = 0; 
        foreach($players as $player) {
            if ($player->id == $currentTurnPlayerId) {
                break;
            }
            $playerIndex++;
        }

        $orderedPlayers = $players;
        if ($playerIndex > 0) { // we start from $currentTurnPlayerId and then follow order
            $orderedPlayers = array_merge(array_slice($players, $playerIndex), array_slice($players, 0, $playerIndex));
        }

        $endGame = false;

        foreach($orderedPlayers as $player) {
            if ($player->health == 0 && !$player->eliminated) {
                $endGame = $this->eliminateAPlayer($player);
            }
        }

        return $endGame;
    }

    function eliminateAPlayer(object $player) { // return $endGame
        self::DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, player_location = 0 where `player_id` = $player->id");

        self::notifyAllPlayers("playerEliminated", clienttranslate('${player_name} is eliminated !'), [
            'playerId' => $player->id,
            'player_name' => $player->name,
        ]);

        $playersBeforeElimination = $this->getRemainingPlayers();

        self::eliminatePlayer($playerId);

        if ($this->isTokyoEmpty(false) && !$this->isTokyoEmpty(true)) {
            // TODO move from bay to tokyo
        }

        if (!$this->isTokyoEmpty(false) && !$this->isTokyoEmpty(true) && $playersBeforeElimination == 5) {
            // TODO move from bay to outside
        }

        return $this->getRemainingPlayers() <= 1;
    }

    function applyGetPoints($playerId, $points, $silent = false) {
        $maxPoints = 20;
        self::DbQuery("UPDATE player SET `player_score` = LEAST(`player_score` + $points, $maxPoints) where `player_id` = $playerId");

        if (!$silent) {
            self::notifyAllPlayers('points','', [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'points' => $this->getPlayerScore($playerId),
            ]);
        }
    }

    function applyLosePoints($playerId, $points, $silent = false) {
        self::DbQuery("UPDATE player SET `player_score` = GREATEST(`player_score` - $points, 0) where `player_id` = $playerId");

        if (!$silent) {
            self::notifyAllPlayers('points','', [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'points' => $this->getPlayerScore($playerId),
            ]);
        }
    }

    function applyGetHealth($playerId, $phealth, $silent = false) {
        // regeneration
        $health = $this->hasCardByType($playerId, 38) ? $phealth + 1 : $phealth;

        $maxHealth = $this->getPlayerMaxHealth($playerId);
        self::DbQuery("UPDATE player SET `player_health` = LEAST(`player_health` + $health, $maxHealth) where `player_id` = $playerId");

        if (!$silent) {
            self::notifyAllPlayers('health','', [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'health' => $this->getPlayerHealth($playerId),
            ]);
        }
    }

    function applyDamage($playerId, $health, $silent = false) {
        // Armor plating
        if ($this->hasCardByType($playerId, 4) && $points == 1) {
            return;
        }

        self::DbQuery("UPDATE player SET `player_health` = GREATEST(`player_health` - $health, 0) where `player_id` = $playerId");

        if (!$silent) {
            self::notifyAllPlayers('health','', [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'health' => $this->getPlayerHealth($playerId),
            ]);
        }

        if ($health >= 2 && $this->hasCardByType($playerId, 47)) {
            // we're only making it stronger
            $this->applyGetEnergy($playerId, 1, $silent);
        }
    }

    function applyGetEnergy($playerId, $pEnergy, $silent = false) {
        // friend of children
        $energy = $this->hasCardByType($playerId, 17) ? $pEnergy + 1 : $pEnergy;

        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` + $energy where `player_id` = $playerId");

        if (!$silent) {
            self::notifyAllPlayers('energy','', [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'energy' => $this->getPlayerEnergy($playerId),
            ]);
        }
    }

    function applyLoseEnergy($playerId, $energy, $silent = false) {
        self::DbQuery("UPDATE player SET `player_energy` = GREATEST(`player_energy` - $energy, 0) where `player_id` = $playerId");

        if (!$silent) {
            self::notifyAllPlayers('energy','', [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'energy' => $this->getPlayerEnergy($playerId),
            ]);
        }
    }
}
