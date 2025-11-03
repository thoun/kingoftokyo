<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class PickMonster extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: ST_PLAYER_PICK_MONSTER,
            type: StateType::ACTIVE_PLAYER,
            name: 'pickMonster',
            description: clienttranslate('${actplayer} must pick a monster'),
            descriptionMyTurn: clienttranslate('${you} must pick a monster'),
        );
    }

    public function getArgs(): array {
        return [
            'availableMonsters' => $this->getAvailableMonsters(),
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        $remainingToPick = (int)$this->game->getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_monster = 0");
        if ($remainingToPick === 0) {
            return $this->game->redirectAfterPickMonster();
        }

        $availableMonsters = $this->getAvailableMonsters();
        if (count($availableMonsters) === 1) {
            return $this->actPickMonster($activePlayerId, $availableMonsters[0], true);
        }
    }

    #[PossibleAction]
    public function actPickMonster(
        int $currentPlayerId,
        #[IntParam(name: 'monster')] int $monsterId,
        bool $automatic = false,
    ) {
        $this->setMonster($currentPlayerId, $monsterId);
        $this->game->saveMonsterStat($currentPlayerId, $monsterId, $automatic);

        return ST_PICK_MONSTER_NEXT_PLAYER;
    }

    public function zombie(int $playerId) {
        $availableMonsters = $this->getAvailableMonsters();
        if (!empty($availableMonsters)) {
            $zombieChoice = $this->getRandomZombieChoice($availableMonsters);
            $this->actPickMonster($playerId, $zombieChoice, true);
        } else {
            return $this->game->redirectAfterPickMonster();
        }
    }

    private function getAvailableMonsters() {
        $dbResults = $this->game->getCollectionFromDb("SELECT distinct player_monster FROM player WHERE player_monster > 0");
        $pickedMonsters = array_map(fn($dbResult) => intval($dbResult['player_monster']), array_values($dbResults));

        $availableMonsters = [];

        $monsters = $this->game->getGameMonsters();
        foreach ($monsters as $number) {
            if (!in_array($number, $pickedMonsters)) {
                $availableMonsters[] = $number;
            }
        }

        return $availableMonsters;
    }

    private function setMonster(int $playerId, int $monsterId) {
        $this->game->DbQuery("UPDATE player SET `player_monster` = $monsterId where `player_id` = $playerId");

        $this->notify->all('pickMonster', '', [
            'playerId' => $playerId,
            'monster' => $monsterId,
        ]);

        if ($this->game->powerUpExpansion->isActive()) {
            $this->game->powerUpExpansion->evolutionCards->moveAllCardsInLocation('monster'.($monsterId % 100), 'deck'.$playerId);
            $this->game->powerUpExpansion->evolutionCards->shuffle('deck'.$playerId);
        }
    }
}

