<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

class CthulhuExpansion {

    function __construct(
        protected Game $game,
    ) {}

    public function isActive(): bool {
        return $this->game->tableOptions->get(CTHULHU_EXPANSION_OPTION) === 2;
    }

    public function getPlayerCultists(int $playerId): int {
        return intval($this->game->getUniqueValueFromDB("SELECT player_cultists FROM `player` where `player_id` = $playerId"));
    }
    
    public function applyGetCultist(int $playerId, int $dieValue): void {
        $this->game->DbQuery("UPDATE player SET `player_cultists` = `player_cultists` + 1 where `player_id` = $playerId");

        $diceStr = $this->game->getDieFaceLogName($dieValue, 0);

        $message = clienttranslate('${player_name} gains 1 cultist with 4 or more ${dice}');
        $this->game->notify->all('cultist', $message, [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'cultists' => $this->getPlayerCultists($playerId),
            'isMaxHealth' => $this->game->getPlayerHealth($playerId) >= $this->game->getPlayerMaxHealth($playerId),
            'dice' => $diceStr,
        ]);

        $this->game->incStat(1, 'gainedCultists', $playerId);
    }

    public function applyLoseCultist(int $playerId, string $message): void {
        $this->game->DbQuery("UPDATE player SET `player_cultists` = `player_cultists` - 1 where `player_id` = $playerId");

        $this->game->notify->all('cultist', $message, [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'cultists' => $this->getPlayerCultists($playerId),
            'isMaxHealth' => $this->game->getPlayerHealth($playerId) >= $this->game->getPlayerMaxHealth($playerId),
        ]);
    }

    function applyUseRapidCultist(int $playerId, int $type) {

        if ($type != 4 && $type != 5) {
            throw new \BgaUserException('Wrong type for cultist');
        }

        if ($this->getPlayerCultists($playerId) == 0) {
            throw new \BgaUserException('No cultist');
        }

        if ($this->game->getPlayer($playerId)->eliminated) {
            throw new \BgaUserException('You can\'t heal when you\'re dead');
        }

        if ($type == 4 && $this->game->getPlayerHealth($playerId) >= $this->game->getPlayerMaxHealth($playerId)) {
            throw new \BgaUserException('You can\'t heal when you\'re already at full life');
        }

        if ($type == 4 && !$this->game->canGainHealth($playerId)) {
            throw new \BgaUserException(clienttranslate('You cannot gain [Heart]'));
        }

        if ($type == 5 && !$this->game->canGainEnergy($playerId)) {
            throw new \BgaUserException(clienttranslate('You cannot gain [Energy]'));
        }

        if ($type == 4) {
            $this->game->applyGetHealth($playerId, 1, 0, $playerId);
            $this->applyLoseCultist($playerId, clienttranslate('${player_name} use a Cultist to gain 1[Heart]'));
            $this->game->incStat(1, 'cultistHeal', $playerId);
        } else if ($type == 5) {
            $this->game->applyGetEnergy($playerId, 1, 0);
            $this->applyLoseCultist($playerId, clienttranslate('${player_name} use a Cultist to gain 1[Energy]'));
            $this->game->incStat(1, 'cultistEnergy', $playerId);
        }
    }

    function cancellableDamageWithCultists(int $playerId): int {
        return $this->getPlayerCultists($playerId);
    }
}
