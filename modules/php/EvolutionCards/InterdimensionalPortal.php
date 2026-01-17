<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFramework\UserException;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class InterdimensionalPortal extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $question = new Question(
            'InterdimensionalPortal',
            clienttranslate('${actplayer} can apply Interdimensional Portal effect'),
            clienttranslate('${you} can apply Interdimensional Portal effect'),
            [$context->currentPlayerId],
            $context->stateAfter ?? -1,
            [
                'card_name' => 3000 + $this->type,
            ],
            evolutionId: $this->id,
        );
        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);

    }

    public function actAnswerInterdimensionalPortal(Context $context, int $type) {
        if ($type === 5) {
            $context->game->applyGetEnergy($context->currentPlayerId, 2, $this);
        } else if ($type === 4) {
            $context->game->applyGetHealth($context->currentPlayerId, 2, $this, $context->currentPlayerId);
        } else {
            throw new UserException('Invalid answer');
        }
    }

}
