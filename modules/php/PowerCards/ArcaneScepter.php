<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class ArcaneScepter extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [SNEAKY];
    }
    public function immediateEffect(Context $context) {
        $discardCards = $context->game->powerCards->getCardsInLocation('discard');
        $cards = Arrays::filter($discardCards, fn($card) => $card->type >= 400 && $card->type < 500);
        if (count($cards) === 0) {
            $context->game->notify->all(/*TODOMB clienttranslate*/('There is no <CONSUMABLE> card in the discard pile, Arcane Scepter effect is skipped'));
            return;
        }

        $question = new Question(
            'ArcaneScepter',
            /*TODOMB clienttranslate*/('${actplayer} must take any <CONSUMABLE> card from the discard for free'),
            /*TODOMB clienttranslate*/('${you} must take any <CONSUMABLE> card from the discard for free'),
            [$context->currentPlayerId],
            -1,
            [
                'cards' => $cards,
            ]
        );
        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function applyEffect(Context $context) {
        $context->game->removeCard($context->currentPlayerId, $this);
    }
}
