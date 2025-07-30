<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/wickedness-tile.php');
require_once(__DIR__.'/../framework-prototype/Helpers/Arrays.php');

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use Bga\Games\KingOfTokyo\Objects\Context;

use function Bga\Games\KingOfTokyo\debug;

trait WickednessTilesUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getPlayerWickedness(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_wickedness FROM `player` where `player_id` = $playerId"));
    }
    
    function applyGetWickedness(int $playerId, int $number) {
        $oldWickedness = $this->getPlayerWickedness($playerId);
        $newWickedness = min(10, $oldWickedness + $number);

        $levels = json_decode($this->getUniqueValueFromDB("SELECT player_take_wickedness_tiles FROM `player` where `player_id` = $playerId"), true);
        foreach ([3, 6, 10] as $level) {
            if ($oldWickedness < $level && $newWickedness >= $level) {
                $levels[] = $level;
            }
        }

        $levelsJson = json_encode($levels);
        $this->DbQuery("UPDATE player SET `player_wickedness` = $newWickedness, `player_take_wickedness_tiles` = '$levelsJson' where `player_id` = $playerId");

        $this->notifyAllPlayers('wickedness', clienttranslate('${player_name} gains ${delta_wickedness} wickedness points'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'wickedness' => $newWickedness,
            'delta_wickedness' => $number,
        ]);

        $this->incStat($number, 'gainedWickedness', $playerId);
    }

    function canTakeWickednessTile(int $playerId) {
        $levels = json_decode($this->getUniqueValueFromDB("SELECT player_take_wickedness_tiles FROM `player` where `player_id` = $playerId"), true);
        return count($levels) > 0 ? min($levels) : 0;
    }

    function applyWickednessTileEffect(object $tile, int $playerId) { // return $damages
        $tileType = $tile->type;
        $logTileType = 2000 + $tileType;
        switch($tileType) {
            case FULL_REGENERATION_WICKEDNESS_TILE:
                if ($this->canGainHealth($playerId)) {
                    $this->applyGetHealthIgnoreCards($playerId, $this->getPlayerMaxHealth($playerId), $logTileType, $playerId);
                }
                break;
            case WIDESPREAD_PANIC_WICKEDNESS_TILE:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 4, $logTileType);
                }
                $this->removeWickednessTiles($playerId, [$tile]);
                break;
            case ANTIMATTER_BEAM_WICKEDNESS_TILE:
                $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
                $diceCounts[6] *= 2;
                $this->setGlobalVariable(DICE_COUNTS, $diceCounts);
                break;
            case BARBS_WICKEDNESS_TILE:
                $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
                if ($diceCounts[6] >= 2) {
                    $diceCounts[6] += 1;
                    $this->setGlobalVariable(DICE_COUNTS, $diceCounts);
                }
                break;
            case HAVE_IT_ALL_WICKEDNESS_TILE:
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
                $keepCardsCount = count(array_filter($cardsOfPlayer, fn($card) => $card->type < 100));
                $this->applyGetPoints($playerId, $keepCardsCount, $logTileType);
                break;
            default:
                if (method_exists($tile, 'immediateEffect')) {
                    $tile->immediateEffect($this, new Context(currentPlayerId: $playerId));
                }
                break;
        }
    }

    function removeWickednessTiles(int $playerId, array $tiles) {
        $this->wickednessTiles->moveItems($tiles, 'discard');

        $this->notifyAllPlayers("removeWickednessTiles", '', [
            'playerId' => $playerId,
            'tiles' => $tiles,
        ]);
    }

    function getTableWickednessTiles(int $level): array {
        return $this->wickednessTiles->getItemsInLocation('table', $level);
    }

    function getWickednessTileByType(int $playerId, int $cardType): ?WickednessTile {
        $handTiles = $this->wickednessTiles->getItemsInLocation('hand', $playerId);
        return Arrays::find($handTiles, fn($tile) => $tile->type == $cardType);
    }

    function gotWickednessTile(int $playerId, int $cardType): bool {
        return $this->getWickednessTileByType($playerId, $cardType) !== null;
    }
    
    function applyDefenderOfTokyo(int $playerId, int $logCardType, int $count) {
        $otherPlayersIds = $this->getOtherPlayersIds($playerId);
        foreach ($otherPlayersIds as $otherPlayerId) {
            $this->applyLosePoints($otherPlayerId, $count, $logCardType);
        }
    }

    function canChangeMimickedCardWickednessTile(int $playerId) {
        // check if player have mimic card
        if (!$this->isWickednessExpansion() || !$this->gotWickednessTile($playerId, FLUXLING_WICKEDNESS_TILE)) {
            return false;
        }

        $playersIds = $this->getPlayersIds();
        $mimickedCardId = $this->getMimickedCardId(FLUXLING_WICKEDNESS_TILE);

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            foreach($cardsOfPlayer as $card) {
                if ($card->type != MIMIC_CARD && $card->type < 100 && $mimickedCardId != $card->id) {
                    return true;
                }
            }
        }
        
        return false;
    }

    function setTileTokens(int $playerId, $card, int $tokens, bool $silent = false) {
        $card->tokens = $tokens;
        $this->DbQuery("UPDATE `wickedness_tile` SET `card_type_arg` = $tokens where `card_id` = ".$card->id);

        if (!$silent) {
            if ($card->type == FLUXLING_WICKEDNESS_TILE) {
                $card->mimicType = $this->getMimickedCardType(FLUXLING_WICKEDNESS_TILE);
            }
            $this->notifyAllPlayers("setTileTokens", '', [
                'playerId' => $playerId,
                'card' => $card,
            ]);
        }
    }
}