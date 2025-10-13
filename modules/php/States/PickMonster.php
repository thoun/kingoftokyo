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
            'availableMonsters' => $this->game->getAvailableMonsters(),
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        $remainingToPick = (int)$this->game->getUniqueValueFromDB("SELECT count(*) FROM player WHERE player_monster = 0");
        if ($remainingToPick === 0) {
            return $this->game->redirectAfterPickMonster();
        }

        $availableMonsters = $this->game->getAvailableMonsters();
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
        $this->game->setMonster($currentPlayerId, $monsterId);
        $this->game->saveMonsterStat($currentPlayerId, $monsterId, $automatic);

        return ST_PICK_MONSTER_NEXT_PLAYER;
    }

    public function zombie(int $playerId) {
        $availableMonsters = $this->game->getAvailableMonsters();
        if (!empty($availableMonsters)) {
            $zombieChoice = $this->getRandomZombieChoice($availableMonsters);
            $this->actPickMonster($playerId, $zombieChoice, true);
        } else {
            return $this->game->redirectAfterPickMonster();
        }
    }
}

