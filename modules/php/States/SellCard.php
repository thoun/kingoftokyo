<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

class SellCard extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_SELL_CARD,
            type: StateType::ACTIVE_PLAYER,
            name: 'sellCard',
            description: clienttranslate('${actplayer} can sell a card'),
            descriptionMyTurn: clienttranslate('${you} can sell a card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $countMetamorph = $this->game->countCardOfType($activePlayerId, \METAMORPH_CARD);

        $canSell = $countMetamorph > 0;
        $cards = [];
        $disabledIds = [];
        if ($canSell) {
            $cards = $this->game->powerCards->getPlayerReal($activePlayerId);
            if (Arrays::count($cards, fn($card) => $card->type < 100) === 0) {
                $canSell = false;
            }
        }

        if ($canSell) {        
            foreach ($cards as $card) {
                if ($card->type > 100) {
                    $disabledIds[] = $card->id;
                }
            }
        }
    
        return [
            'disabledIds' => $disabledIds,
            '_no_notify' => !$canSell,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        if ($args['_no_notify']) {
            return $this->actEndSell($activePlayerId);
        }
    }

    #[PossibleAction]
    function actSellCard(int $id, int $activePlayerId) {
        if ($this->game->countCardOfType($activePlayerId, METAMORPH_CARD) == 0) {
            throw new \BgaUserException("You can't sell cards without Metamorph");
        }

        $card = $this->game->powerCards->getCardById($id);
        
        if ($card->location != 'hand' || $card->location_arg != $activePlayerId) {
            throw new \BgaUserException("You can't sell cards that you don't own");
        }
        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only sell Keep cards");
        }

        $fullCost = $this->game->powerCards->getCardBaseCost($card->type);

        $this->game->removeCard($activePlayerId, $card, true);

        $this->notify->all("removeCards", clienttranslate('${player_name} sells ${card_name}'), [
            'playerId' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'cards' => [$card],
            'card_name' =>$card->type,
            'energy' => $this->game->getPlayerEnergy($activePlayerId),
        ]);

        $this->game->applyGetEnergy($activePlayerId, $fullCost, 0);

        return SellCard::class;
    }

    #[PossibleAction]
    function actEndSell(int $activePlayerId) {
        $this->game->removeDiscardCards($activePlayerId);

        $damages = $this->game->mindbugExpansion->applyEndFrenzy($activePlayerId);
        $this->game->goToState($this->game->redirectAfterSellCard(), $damages);
    }

    public function zombie(int $playerId) {
        return $this->actEndSell($playerId);
    }
}

