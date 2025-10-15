<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class BambooSupply extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $question = new Question(
            'BambooSupply',
            clienttranslate('${actplayer} can put or take [Energy]'),
            clienttranslate('${you} can put or take [Energy]'),
            [$context->currentPlayerId],
            \ST_QUESTIONS_BEFORE_START_TURN,
            [ 'canTake' => $this->tokens > 0 ]
        );
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
        return true;
    }

}
