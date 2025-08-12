<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

class KingKongExpansion {

    function __construct(
        protected Game $game,
    ) {}

    public function isActive(): bool {
        return $this->game->tableOptions->get(KINGKONG_EXPANSION_OPTION) === 2;
    }

    public function setup(): void {
        $this->game->DbQuery("INSERT INTO tokyo_tower(`level`) VALUES (1), (2), (3)");
    }

    public function fillResult(array &$result): void {
        $result['tokyoTowerLevels'] = $this->getTokyoTowerLevels(0);
        
        foreach ($result['players'] as $playerId => &$playerDb) {
            $playerDb['tokyoTowerLevels'] = $this->getTokyoTowerLevels($playerId);
        }
    }

    function getTokyoTowerLevels(int $playerId) {
        $dbResults = $this->game->getCollectionFromDb("SELECT `level` FROM `tokyo_tower` WHERE `owner` = $playerId order by `level`");
        return array_map(fn($dbResult) => intval($dbResult['level']), array_values($dbResults));
    }

    function changeTokyoTowerOwner(int $playerId, int $level): void {
        $this->game->DbQuery("UPDATE `tokyo_tower` SET  `owner` = $playerId where `level` = $level");

        $message = $playerId == 0 ? '' : clienttranslate('${player_name} claims Tokyo Tower level ${level}');
        $this->game->notify->all("changeTokyoTowerOwner", $message, [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'level' => $level,
        ]);

        if ($playerId > 0) {
            $this->game->incStat(1, 'tokyoTowerLevel'.$level.'claimed', $playerId);
        }
    }

    function getNewTokyoTowerLevel(int $playerId): void {
        $levels = $this->getTokyoTowerLevels($playerId);
        $newLevel = 1;
        for ($i=1; $i<3;$i++) {
            if (in_array($newLevel, $levels)) {
                $newLevel++;
            }
        }

        $this->changeTokyoTowerOwner($playerId, $newLevel);

        if ($newLevel === 3) {
            $this->game->applyGetPointsIgnoreCards($playerId, WIN_GAME, 0);
            
            $this->game->notify->all("fullTokyoTower", clienttranslate('${player_name} claims Tokyo Tower top level and wins the game'), [
                'playerId' => $playerId,
                'player_name' => $this->game->getPlayerNameById($playerId),
            ]);
        }
    }

    public function onPlayerStartTurn(int $playerId): void {
        $towerLevels = $this->getTokyoTowerLevels($playerId);

        foreach ($towerLevels as $level) {
            if ($level == 1 || $level == 2) {
                $playerGettingHearts = $this->game->getPlayerGettingEnergyOrHeart($playerId);

                if ($this->game->canGainHealth($playerGettingHearts)) {
                    
                    if ($playerId == $playerGettingHearts) {
                        $this->game->notify->all('log', clienttranslate('${player_name} starts turn with Tokyo Tower level ${level} and gains 1[Heart]'), [
                            'playerId' => $playerId,
                            'player_name' => $this->game->getPlayerNameById($playerId),
                            'level' => $level,
                        ]);
                    }

                    $this->game->applyGetHealth($playerGettingHearts, 1, 0, $playerId);
                }
            }

            if ($level == 2) {
                $playerGettingEnergy = $this->game->getPlayerGettingEnergyOrHeart($playerId);

                if ($this->game->canGainEnergy($playerGettingEnergy)) {
    
                    if ($playerId == $playerGettingEnergy) {
                        $this->game->notify->all('log', clienttranslate('${player_name} starts turn with Tokyo Tower level ${level} and gains 1[Energy]'), [
                            'playerId' => $playerId,
                            'player_name' => $this->game->getPlayerNameById($playerId),
                            'level' => $level,
                        ]);
                    }

                    $this->game->applyGetEnergy($playerGettingEnergy, 1, 0);
                }
            }

            if ($level == 1) {
                $this->game->incStat(1, 'bonusFromTokyoTowerLevel1applied', $playerId);
            }
            if ($level == 2) {
                $this->game->incStat(1, 'bonusFromTokyoTowerLevel2applied', $playerId);
            }
        }
    }

    public function onPlayerEliminated(int $playerId): void {
        // Tokyo Tower levels go back to the table
        $levels = $this->getTokyoTowerLevels($playerId);
        foreach($levels as $level) {
            $this->changeTokyoTowerOwner(0, $level);
        }
    }
}
