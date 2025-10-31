<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class SunkenTemple extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        if ($context->game->inTokyo($context->currentPlayerId)) {
            return false;
        }

        $question = new Question(
            'SunkenTemple',
            clienttranslate('${actplayer} can pass turn to gain 3[Heart] and 3[Energy]'),
            clienttranslate('${you} can pass your turn to gain 3[Heart] and 3[Energy]'),
            [$context->currentPlayerId],
            \ST_QUESTIONS_BEFORE_START_TURN,
            [],
            evolutionId: $this->id,
        );
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
        return true;
    }

}
