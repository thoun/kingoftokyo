<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class MyToy extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $question = new Question(
            'MyToy',
            /*client TODOPUBG translate(*/'${player_name} must choose a card to reserve'/*)*/,
            /*client TODOPUBG translate(*/'${you} must choose a card to reserve'/*)*/,
            [$context->currentPlayerId],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'playerId' => $context->currentPlayerId,
                '_args' => [ 'player_name' => $context->game->getPlayerName($context->currentPlayerId) ],
                'card' => $this,
            ]
        );

        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);
        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }
}
