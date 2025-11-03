<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ChangeMimickedCard extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_CHANGE_MIMICKED_CARD,
            type: StateType::ACTIVE_PLAYER,
            name: 'changeMimickedCard',
            description: clienttranslate('${actplayer} can change mimicked card for 1[Energy]'),
            descriptionMyTurn: clienttranslate('${you} can change mimicked card for 1[Energy]'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        return $this->game->getArgChooseMimickedCard($activePlayerId, MIMIC_CARD, 1);
    }

    public function onEnteringState(int $activePlayerId) {
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            return $this->actSkipChangeMimickedCard($activePlayerId);
        }

        if ($this->game->autoSkipImpossibleActions()) {
            $args = $this->getArgs($activePlayerId);
            if (!($args['canChange'] ?? false)) {
                return $this->actSkipChangeMimickedCard($activePlayerId);
            }
        }
    }

    #[PossibleAction]
    public function actChangeMimickedCard(
        int $currentPlayerId,
        #[IntParam(name: 'id')] int $cardId,
    ) {
        if ($this->game->getPlayerEnergy($currentPlayerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        $card = $this->game->powerCards->getCardById($cardId);
        if ($card->type > 100 || $card->type == \MIMIC_CARD) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->location != 'hand') {
            throw new \BgaUserException("You must select a player card");
        }

        $this->game->applyLoseEnergyIgnoreCards($currentPlayerId, 1, 0);
        $this->game->setMimickedCardId(\MIMIC_CARD, $currentPlayerId, $cardId);

        return $this->game->redirectAfterChangeMimick($currentPlayerId);
    }

    #[PossibleAction]
    public function actSkipChangeMimickedCard(int $currentPlayerId) {
        return $this->game->redirectAfterChangeMimick($currentPlayerId);
    }

    public function zombie(int $playerId) {
        return $this->actSkipChangeMimickedCard($playerId);
    }
}

