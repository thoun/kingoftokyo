<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;

class WickednessExpansion {

    function __construct(
        protected Game $game,
    ) {}

    public function isActive(): bool {
        return !$this->game->isOrigins() && ($this->game->tableOptions->get(WICKEDNESS_EXPANSION_OPTION) > 1 || $this->game->isDarkEdition());
    }

    private function getPlayerWickedness(int $playerId): int {
        return (int)$this->game->getUniqueValueFromDB("SELECT player_wickedness FROM `player` where `player_id` = $playerId");
    }
    
    public function applyGetWickedness(int $playerId, int $number) {
        $oldWickedness = $this->getPlayerWickedness($playerId);
        $newWickedness = min(10, $oldWickedness + $number);

        $levels = json_decode($this->game->getUniqueValueFromDB("SELECT player_take_wickedness_tiles FROM `player` where `player_id` = $playerId"), true);
        foreach ([3, 6, 10] as $level) {
            if ($oldWickedness < $level && $newWickedness >= $level) {
                $levels[] = $level;
            }
        }

        $levelsJson = json_encode($levels);
        $this->game->DbQuery("UPDATE player SET `player_wickedness` = $newWickedness, `player_take_wickedness_tiles` = '$levelsJson' where `player_id` = $playerId");

        $this->game->notify->all('wickedness', clienttranslate('${player_name} gains ${delta_wickedness} wickedness points'), [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'wickedness' => $newWickedness,
            'delta_wickedness' => $number,
        ]);

        $this->game->incStat($number, 'gainedWickedness', $playerId);
    }

    public function canTakeWickednessTile(int $playerId): int {
        $levels = json_decode($this->game->getUniqueValueFromDB("SELECT player_take_wickedness_tiles FROM `player` where `player_id` = $playerId"), true);
        return count($levels) > 0 ? min($levels) : 0;
    }

    public function removeWickednessTiles(int $playerId, array $tiles) {
        $this->game->wickednessTiles->moveItems($tiles, 'discard');

        $this->game->notify->all("removeWickednessTiles", '', [
            'playerId' => $playerId,
            'tiles' => $tiles,
        ]);
    }

    public function getWickednessTileByType(int $playerId, int $cardType): ?WickednessTile {
        $handTiles = $this->game->wickednessTiles->getPlayerTiles($playerId);
        return Arrays::find($handTiles, fn($tile) => $tile->type == $cardType);
    }

    private function gotWickednessTile(int $playerId, int $cardType): bool {
        return $this->getWickednessTileByType($playerId, $cardType) !== null;
    }

    function canChangeMimickedCardWickednessTile(int $playerId): bool {
        // check if player have mimic card
        if (!$this->isActive() || !$this->gotWickednessTile($playerId, FLUXLING_WICKEDNESS_TILE)) {
            return false;
        }

        $playersIds = $this->game->getPlayersIds();
        $mimickedCardId = $this->game->getMimickedCardId(FLUXLING_WICKEDNESS_TILE);

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->game->powerCards->getPlayerReal($playerId);
            foreach($cardsOfPlayer as $card) {
                if ($card->type != MIMIC_CARD && $card->type < 100 && $mimickedCardId != $card->id) {
                    return true;
                }
            }
        }
        
        return false;
    }

    function setTileTokens(int $playerId, $card, int $tokens, bool $silent = false): void {
        $card->tokens = $tokens;
        $this->game->DbQuery("UPDATE `wickedness_tile` SET `card_type_arg` = $tokens where `card_id` = ".$card->id);

        if (!$silent) {
            if ($card->type == FLUXLING_WICKEDNESS_TILE) {
                $card->mimicType = $this->game->getMimickedCardType(FLUXLING_WICKEDNESS_TILE);
            }
            $this->game->notify->all("setTileTokens", '', [
                'playerId' => $playerId,
                'card' => $card,
            ]);
        }
    }
}
