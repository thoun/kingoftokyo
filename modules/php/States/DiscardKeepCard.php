<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class DiscardKeepCard extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_DISCARD_KEEP_CARD,
            type: StateType::ACTIVE_PLAYER,
            name: 'discardKeepCard',
            description: clienttranslate('${actplayer} must discard a [keep] card'),
            descriptionMyTurn: clienttranslate('${you} must discard a [keep] card'),
            transitions: [
                'next' => \ST_RESOLVE_DICE,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $cards = $this->game->powerCards->getPlayer($activePlayerId);

        $disabledIds = [];         
        foreach ($cards as $card) {
            if ($card->type >= 100) {
                $disabledIds[] = $card->id;
            }
        }

        return [
            'disabledIds' => $disabledIds,
        ];
    }

    #[PossibleAction]
    public function actDiscardKeepCard(
        #[IntParam(name: 'id')] int $cardId,
        int $currentPlayerId,
    ) {
        $card = $this->game->powerCards->getItemById($cardId);
        if (!$card || $card->location !== 'hand' || $card->location_arg != $currentPlayerId) {
            throw new \BgaUserException(\clienttranslate('You must select one of your cards.'));
        }
        if ($card->type >= 100) {
            throw new \BgaUserException(\clienttranslate('You can only discard keep cards.'));
        }

        $this->game->anubisExpansion->applyDiscardKeepCard($currentPlayerId, $card);

        return \ST_RESOLVE_DICE;
    }

    public function zombie(int $playerId) {
        $cards = $this->game->powerCards->getPlayer($playerId);
        $eligible = array_values(array_filter($cards, fn($card) => $card->type < 100 && $card->location === 'hand'));;

        if (!empty($eligible)) {
            $choice = $this->getRandomZombieChoice(array_map(fn($card) => (int)$card->id, $eligible));
            return $this->actDiscardKeepCard($choice, $playerId);
        }

        return \ST_RESOLVE_DICE;
    }
}
