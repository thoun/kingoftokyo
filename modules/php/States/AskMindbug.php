<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class AskMindbug extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_MULTIPLAYER_ASK_MINDBUG,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,

            description: /*TODOMB clienttranslate*/('Player with Mindbug tokens can mindbug ${player_name}'),
            descriptionMyTurn: /*TODOMB clienttranslate*/('${you} can mindbug ${player_name}'),

            transitions: ['end' => ST_RESOLVE_DIE_OF_FATE],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $playerIds = $this->game->mindbugExpansion->getPlayersThatCanMindbug($activePlayerId);

        return [
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'playerIds' => $playerIds,
            '_no_notify' => count($playerIds) === 0,
        ];
    }

    function onEnteringState(array $args) {
        if ($args['_no_notify']) {
            return 'end';
        } else {
            $this->game->gamestate->setPlayersMultiactive($args['playerIds'], 'end', true);
        }
    }
     
    #[PossibleAction]
    public function actMindbug(int $currentPlayerId) {
        try {
            $this->game->mindbugExpansion->mindbugTokens->inc($currentPlayerId, -1);
        } catch (\BgaSystemException $e) { // TODO replace by the new exception
            throw new \BgaUserException('No Mindbug tokens');
        }

        $this->game->mindbugExpansion->setMindbuggedPlayer($currentPlayerId, (int)$this->game->getActivePlayerId());

        // first to click has the power!
        return ST_RESOLVE_DIE_OF_FATE;
    }

    #[PossibleAction]
    public function actPassMindbug(int $currentPlayerId) {
        $this->game->gamestate->setPlayerNonMultiactive($currentPlayerId, 'end');
    }

    public function zombie(int $playerId) {
        return $this->actPassMindbug($playerId);
    }
}
