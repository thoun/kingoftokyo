<?php

namespace KOT\States;

require_once(__DIR__.'/../dice.php');
require_once(__DIR__.'/../card.php');

use KOT\Card;
use KOT\Dice;

trait UtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////


    function initCards() {
        $cards = [];
        
        for( $value=1; $value<=15; $value++ ) { // 1-15 green
            $cards[] = ['type' => 0+$value, 'type_arg' => $value, 'nbr' => 1, 'id' => $value];
        }
        
        for( $value=2; $value<=14; $value++ ) { // 2-14 yellow
            $cards[] = ['type' => 100+$value, 'type_arg' => $value, 'nbr' => 1, 'id' => 100 + $value];
        }
        
        for( $value=3; $value<=13; $value++ ) { // 3-13 orange
            $cards[] = ['type' => 200+$value, 'type_arg' => $value, 'nbr' => 1, 'id' => 200 + $value];
        }
        
        for( $value=7; $value<=9; $value++ ) { // 7,8,9 red
            $cards[] = ['type' => 300+$value, 'type_arg' => $value, 'nbr' => 1, 'id' => 300 + $value];
        }
            
        // $this->cards->createCards( array_slice($cards, count($cards) - 10, 10), 'deck' );
        $this->cards->createCards($cards, 'deck');
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

    function getDices(int $number) {
        $sql = "SELECT `dice_id`, `dice_value`, `extra` FROM dice limit $number";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices));
    }

    function getMaxPlayerScore() {
        return intval(self::getUniqueValueFromDB("SELECT max(player_score) FROM player"));
    }

    function getPlayerName(int $playerId) {
        return self::getUniqueValueFromDb("SELECT player_name FROM player WHERE player_id = $playerId");
    }

    function getPlayerHealth(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_health FROM player where `player_id` = $playerId"));
    }

    function getDicesNumber(int $playerId) {
        return 6; // TODO
    }

    function getPlayerMaxHealth(int $playerId) {
        return 10; // TODO
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

    function inTokyo(int $playerId) {
        $location = intval(self::getUniqueValueFromDB( "SELECT player_location FROM player WHERE player_id = $playerId"));
        return $location > 0;
    }

    private function getPlayersIdsFromLocation(bool $inside) {
        $sign = $inside ? '>' : '=';
        $sql = "SELECT player_id FROM player WHERE player_location $sign 0 AND player_eliminated = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    /*function getPlayersIdsInTokyo() {
        return $this->getPlayersIdsFromLocation(true);
    }

    function getPlayersIdsOutsideTokyo() {
        return $this->getPlayersIdsFromLocation(false);
    }*/

    function eliminateAPlayer(int $playerId) {
        self::DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, player_location = 0 where `player_id` = $playerId");

        self::notifyAllPlayers("playerEliminated", clienttranslate('${player_name} is eliminated !'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);

        self::eliminatePlayer($playerId);
        
        // TODO move from bay to tokyo if needed

        if ($this->getRemainingPlayers() <= 1) {
            $this->gamestate->nextState('endGame');
        }
    }
}