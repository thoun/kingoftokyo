<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class Treasure extends PowerCard
{
    public function immediateEffect(Context $context) {
        $discardCards = $context->game->powerCards->getCardsInLocation('discard');
        $cards = Arrays::filter($discardCards, fn($card) => $card->type >= 400 && $card->type < 500);
        if (count($cards) === 0) {
            throw new \BgaUserException("No [Consumable] cards in discard pile");
        }

        $question = new Question(
            'Treasure',
            /*TODOMB clienttranslate*/('${actplayer} can buy a [Consumable] card from the discard for 3[Energy] less'),
            /*TODOMB clienttranslate*/('${you} can buy a [Consumable] card from the discard for 3[Energy] less'),
            [$context->currentPlayerId],
            $context->stateAfter ?? -1,
            [
                'cards' => $cards,
            ]
        );
        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }
}
