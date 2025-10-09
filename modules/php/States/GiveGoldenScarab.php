<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class GiveGoldenScarab extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_GIVE_GOLDEN_SCARAB,
            type: StateType::ACTIVE_PLAYER,
            name: 'giveGoldenScarab',
            description: clienttranslate('${actplayer} must give Golden Scarab'),
            descriptionMyTurn: clienttranslate('${you} must give Golden Scarab'),
        );
    }

    public function getArgs(): array {
        return [
            'playersIds' => $this->game->getPlayersIds(),
        ];
    }

    #[PossibleAction]
    public function actGiveGoldenScarab(
        #[IntParam(name: 'playerId')] int $targetPlayerId,
    ) {
        $this->game->anubisExpansion->changeGoldenScarabOwner($targetPlayerId);

        return \ST_RESOLVE_DICE;
    }

    public function zombie(int $playerId, array $args) {
        $playersIds = $args['playersIds'];
        $eligible = array_filter($playersIds, fn($pId) => $pId !== $playerId && !$this->game->getPlayer($pId)->eliminated);

        if (!empty($eligible)) {
            $choice = $this->getRandomZombieChoice($eligible);
            return $this->actGiveGoldenScarab($choice);
        }

        return \ST_RESOLVE_DICE;
    }
}
