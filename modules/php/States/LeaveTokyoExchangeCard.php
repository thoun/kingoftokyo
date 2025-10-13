<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;

class LeaveTokyoExchangeCard extends GameState {
    public function __construct(protected \Bga\Games\KingOfTokyo\Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_LEAVE_TOKYO_EXCHANGE_CARD,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'leaveTokyoExchangeCard',
            description: clienttranslate('Players with Unstable DNA can exchange this card'),
            descriptionMyTurn: clienttranslate('${you} can exchange Unstable DNA'),
            transitions: [
                'next' => \ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $leaversWithUnstableDNA = $this->game->getLeaversWithUnstableDNA();  
        $currentPlayerId = $leaversWithUnstableDNA[0];

        $canExchange = false;
        $tableCards = $this->game->powerCards->getTable();
        $disabledIds = array_map(fn($card) => $card->id, $tableCards); // can only take from other players, not table

        $playersIds = $this->game->getPlayersIds();
        foreach($playersIds as $otherPlayerId) {
            $cardsOfPlayer = $this->game->powerCards->getPlayerReal($otherPlayerId);
            $isSmashingPlayer = $activePlayerId === $otherPlayerId && $otherPlayerId != $currentPlayerId; // TODODE check it's not currentPlayer, else skip (if player left Tokyo with anubis card)

            foreach ($cardsOfPlayer as $card) {
                if ($isSmashingPlayer && $card->type < 300) {
                    // all cards can be stolen : keep, discard, costume. Ignore transformation & golden scarab
                    $canExchange = true;
                } else {
                    $disabledIds[] = $card->id;
                }
            }
        }

        return [
            'canExchange' => $canExchange,
            'disabledIds' => $disabledIds,
        ];
    }

    public function onEnteringState(array $args): ?int {
        if ($this->game->autoSkipImpossibleActions() && empty($args['canExchange'])) {
            return \ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO;
        }

        $leaversWithUnstableDNA = $this->game->getLeaversWithUnstableDNA();
        $this->gamestate->setPlayersMultiactive($leaversWithUnstableDNA, 'transitionError', true);
        return null;
    }

    #[PossibleAction]
    function actExchangeCard(#[IntParam('id')] int $exchangedCardId, int $currentPlayerId, array $args) {
        if (in_array($exchangedCardId, $args['disabledIds'])) {
            throw new \BgaUserException("You can't exchange this card");
        }
        
        $unstableDnaCards = $this->game->getCardsOfType($currentPlayerId, UNSTABLE_DNA_CARD); // TODODE unstable DNA can be mimicked. create an intervention for this.
        $unstableDnaCards = Arrays::filter($unstableDnaCards, fn($card) => $card->id < 2000); // to remove mimic tile, as you can't exchange a cand with a tile
        $unstableDnaCard = $unstableDnaCards[0];

        $exchangedCard = $this->game->powerCards->getItemById($exchangedCardId);
        $exchangedCardOwner = $exchangedCard->location_arg;

        if ($exchangedCard->type > 300) {
            throw new \BgaUserException("You cannot exchange this card");
        }

        $countRapidHealingBeforeCurrentPlayer = $this->game->countCardOfType($currentPlayerId, RAPID_HEALING_CARD);
        $countRapidHealingBeforeOtherPlayer = $this->game->countCardOfType($exchangedCardOwner, RAPID_HEALING_CARD);
        $countEvenBiggerBeforeOtherPlayer = $this->game->countCardOfType($exchangedCardOwner, EVEN_BIGGER_CARD);

        $this->game->powerCards->moveItem($unstableDnaCard, 'hand', $exchangedCardOwner);
        $this->game->powerCards->moveItem($exchangedCard, 'hand', $currentPlayerId);

        $this->game->toggleRapidHealing($currentPlayerId, $countRapidHealingBeforeCurrentPlayer);
        $this->game->toggleRapidHealing($exchangedCardOwner, $countRapidHealingBeforeOtherPlayer);
        if ($countEvenBiggerBeforeOtherPlayer > 0) {
            $this->game->changeMaxHealth($exchangedCardOwner);
        }

        $this->notify->all("exchangeCard", clienttranslate('${player_name} exchange ${card_name} with ${card_name2} taken from ${player_name2}'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'previousOwner' => $exchangedCardOwner,
            'player_name2' => $this->game->getPlayerNameById($exchangedCardOwner),
            'unstableDnaCard' => $unstableDnaCard,
            'card_name' => UNSTABLE_DNA_CARD,
            'exchangedCard' => $exchangedCard, 
            'card_name2' => $exchangedCard->type,
        ]);

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    function actSkipExchangeCard(int $currentPlayerId) {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    } 

    public function zombie(int $playerId) {
        return $this->actSkipExchangeCard($playerId);
    }
}
