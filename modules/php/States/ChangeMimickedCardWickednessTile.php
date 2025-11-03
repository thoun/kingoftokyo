<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

class ChangeMimickedCardWickednessTile extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_CHANGE_MIMICKED_CARD_WICKEDNESS_TILE,
            type: StateType::ACTIVE_PLAYER,
            name: 'changeMimickedCardWickednessTile',
            description: clienttranslate('${actplayer} can change mimicked card'),
            descriptionMyTurn: clienttranslate('${you} can change mimicked card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        return $this->game->getArgChooseMimickedCard($activePlayerId, FLUXLING_WICKEDNESS_TILE);
    }

    public function onEnteringState(int $activePlayerId) {
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            return $this->actSkipChangeMimickedCardWickednessTile($activePlayerId);
        }

        if ($this->game->autoSkipImpossibleActions()) {
            $args = $this->getArgs($activePlayerId);
            if (!($args['canChange'] ?? false)) {
                return $this->actSkipChangeMimickedCardWickednessTile($activePlayerId);
            }
        }
    }

    #[PossibleAction]
    public function actChangeMimickedCardWickednessTile(
        int $currentPlayerId,
        #[IntParam(name: 'id')] int $cardId,
    ) {
        $card = $this->game->powerCards->getCardById($cardId);
        if ($card->type > 100) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->type == \MIMIC_CARD) {
            throw new \BgaUserException("You cannot mimic Mimic cards");
        }

        $this->game->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $currentPlayerId, $cardId);

        return $this->game->redirectAfterChangeMimickWickednessTile($currentPlayerId);
    }

    #[PossibleAction]
    public function actSkipChangeMimickedCardWickednessTile(int $currentPlayerId) {
        return $this->game->redirectAfterChangeMimickWickednessTile($currentPlayerId);
    }

    public function zombie(int $playerId) {
        return $this->actSkipChangeMimickedCardWickednessTile($playerId);
    }
}

