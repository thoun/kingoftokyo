<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class MindbugsOverlord extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $context->game->globals->set(MINDBUGS_OVERLORD, []);
        $otherPlayerIds = $context->game->getOtherPlayersIds($context->currentPlayerId);
        $question = new Question(
            'MindbugsOverlord',
            /* TODOMB clienttranslate*/('Other players can declare allegiance'),
            /* TODOMB clienttranslate*/('${you} can declare allegiance'),
            $otherPlayerIds,
            \ST_QUESTIONS_BEFORE_START_TURN,
            []
        );
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive($otherPlayerIds, 'next', true);

        $context->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
        return true;
    }
}
