<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

class CybertoothExpansion {

    function __construct(
        protected Game $game,
    ) {}

    public function isActive(): bool {
        return $this->game->tableOptions->get(CYBERTOOTH_EXPANSION_OPTION) === 2;
    }

    public function setup(): void {
        $this->game->DbQuery("INSERT INTO dice (`dice_value`, `type`) VALUES (0, 1)");
    }

    public function onPlayerStartTurn(int $playerId): void {
        if ($this->isPlayerBerserk($playerId)) {
            $this->game->incStat(1, 'turnsInBerserk', $playerId);
        }
    }

    public function onPlayerHealHimself(int $playerId): void {
        if ($this->isPlayerBerserk($playerId)) {
            $this->setPlayerBerserk($playerId, false);
        }
    }

    public function onPlayerResolveSmashDice(int $playerId, int $diceCount): void {
        if ($diceCount >= 4 && $this->isActive() && !$this->isPlayerBerserk($playerId) && $this->game->canUseFace($playerId, 6)) {
            $this->setPlayerBerserk($playerId, true);
        }
    }

    public function isPlayerBerserk(int $playerId): bool {
        return boolval($this->game->getUniqueValueFromDB("SELECT player_berserk FROM `player` where `player_id` = $playerId"));
    }

    public function setPlayerBerserk(int $playerId, bool $active): void {
        $this->game->DbQuery("UPDATE player SET `player_berserk` = ".intval($active)." where `player_id` = $playerId");

        $message = $active ? 
          clienttranslate('${player_name} is now in Berserk mode!') :
          clienttranslate('${player_name} is no longer in Berserk mode');

        $this->game->notify->all('setPlayerBerserk', $message, [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'berserk' => $active,
        ]);
        
        $this->game->incStat(1, 'berserkActivated', $playerId);
    }
}
