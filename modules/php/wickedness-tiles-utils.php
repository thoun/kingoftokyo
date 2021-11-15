<?php

namespace KOT\States;

require_once(__DIR__.'/objects/wickedness-tile.php');

use KOT\Objects\WickednessTile;

trait WickednessTilesUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function initWickednessTiles(int $side) {
        for($value=1; $value<=10; $value++) { // curse cards
            $cardSide = $side === 4 ? bga_rand(0, 1) : $side - 2;
            $cards[] = ['type' => $value + 100 * $cardSide, 'type_arg' => 0, 'nbr' => 1];
        }
        $this->wickednessTiles->createCards($cards, 'deck');

        $allTiles = $this->getWickednessTilesFromDb($this->wickednessTiles->getCardsInLocation('deck'));

        foreach ([3, 6, 10] as $level) {
            $levelTiles = array_values(array_filter($allTiles, function ($tile) use ($level) { 
                $tileLevel = $tile->type > 8 ? 10 : ($tile->type > 4 ? 6 : 3);
                return $tileLevel === $level; 
            }));
            $levelTilesIds = array_map(function ($tile) { return $tile->id; }, $levelTiles);
            $this->wickednessTiles->moveCards($levelTilesIds, 'table', $level);
        }
    }

    function getWickednessTileFromDb(array $dbCard) {
        if (!$dbCard || !array_key_exists('id', $dbCard)) {
            throw new Error('card doesn\'t exists '.json_encode($dbCard));
        }
        if (!$dbCard || !array_key_exists('location', $dbCard)) {
            throw new Error('location doesn\'t exists '.json_encode($dbCard));
        }
        return new WickednessTile($dbCard);
    }

    function getWickednessTilesFromDb(array $dbCards) {
        return array_map(function($dbCard) { return $this->getWickednessTileFromDb($dbCard); }, array_values($dbCards));
    }

    function getPlayerWickedness(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_wickedness FROM `player` where `player_id` = $playerId"));
    }
    
    function applyGetWickedness(int $playerId, int $number) {
        $oldWickedness = $this->getPlayerWickedness($playerId);
        $newWickedness = min(10, $this->getPlayerWickedness($playerId) + $number);

        $canTake = 0;
        foreach ([3, 6, 10] as $level) {
            if ($oldWickedness < $level && $newWickedness >= $level) {
                $canTake = $level;
            }
        }

        self::DbQuery("UPDATE player SET `player_wickedness` = $newWickedness, player_take_wickedness_tile = $canTake where `player_id` = $playerId");

        self::notifyAllPlayers('wickedness', clienttranslate('${player_name} gains ${delta_wickedness} wickedness points'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'wickedness' => $newWickedness,
            'delta_wickedness' => $number,
        ]);

        self::incStat($number, 'gainedWickedness', $playerId);
    }

    function canTakeWickednessTile(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_take_wickedness_tile FROM `player` where `player_id` = $playerId"));
    }

    function applyWickednessTileEffect(object $tile, int $playerId) { // return $damages
        $tileType = $tile->type;
        $logTileType = 2000 + $tileType;
        switch($tileType) {
            // TODOWI
            case FINAL_PUSH_WICKEDNESS_TILE:
                $this->applyGetHealth($playerId, 2, $logTileType, $playerId);
                $this->applyGetEnergy($playerId, 2, $logTileType);
                $this->setGameStateValue(FINAL_PUSH_EXTRA_TURN, 1);
                break;
            case STARBURST_WICKEDNESS_TILE:
                $this->applyGetEnergy($playerId, 12, $logTileType);
                $this->removeWickednessTile($playerId, $tile);
                break;
        }
    }

    function removeWickednessTile(int $playerId, object $tile) {

        $this->wickednessTiles->moveCard($tile->id, 'discard');

        self::notifyAllPlayers("removeWickednessTiles", '', [
            'playerId' => $playerId,
            'tiles' => [$tile],
        ]);
    }

    function getWickednessTileByType(int $playerId, int $cardType) {
        $tiles = $this->getWickednessTilesFromDb($this->wickednessTiles->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId));

        return count($tiles) > 0 ? $tiles[0] : null;
    }
}