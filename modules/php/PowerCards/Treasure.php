<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class Treasure extends PowerCard
{
    public function immediateEffect(Context $context) {
        $cards = $context->game->powerCards->items->getItemsInLocation('discard')
            ->filter(fn($card) => $card->type >= 400 && $card->type < 500)
            ->values();
        if (count($cards) === 0) {
            throw new \BgaUserException("No <CONSUMABLE> cards in discard pile");
        }

        $question = new Question(
            'Treasure',
            clienttranslate('${actplayer} can buy a <CONSUMABLE> card from the discard for 3[Energy] less'),
            clienttranslate('${you} can buy a <CONSUMABLE> card from the discard for 3[Energy] less'),
            [$context->currentPlayerId],
            $context->stateAfter ?? -1,
            [
                'cards' => $cards,
            ],
            cardId: $this->id,
        );
        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }
}
