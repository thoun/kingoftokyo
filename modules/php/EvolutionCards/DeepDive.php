<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class DeepDive extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        if ($context->game->powerCards->countItemsInLocation('deck') === 0) {
            throw new \BgaUserException("No cards in deck pile");
        }

        $context->game->powerCards->shuffle('discard');
        $cards = $context->game->powerCards->getCardsOnTop(3, 'deck');

        $question = new Question(
            'DeepDive',
            clienttranslate('${actplayer} can play a card from the bottom of the deck for free'),
            clienttranslate('${you} can play a card from the bottom of the deck for free'),
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
}
