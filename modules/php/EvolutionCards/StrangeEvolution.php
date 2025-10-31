<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class StrangeEvolution extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $otherPlayerIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        $question = new Question(
            'StrangeEvolution',
            clienttranslate('${actplayer} must choose another Monster Evolution deck to draw from'),
            clienttranslate('${you} must choose another Monster Evolution deck to draw from'),
            [$context->currentPlayerId],
            -1,
            [ 
                'otherPlayerIds' => $otherPlayerIds,
                'evolutionId' => $this->id,
            ],
            evolutionId: $this->id,
        );
        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);
        $context->game->removeEvolution($context->currentPlayerId, $this);

        $context->game->jumpToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function applyEffect(Context $context) {
        $context->game->powerUpExpansion->drawEvolution($context->currentPlayerId, $context->targetPlayerId);
        $context->game->goToState(-1);
    }
}
