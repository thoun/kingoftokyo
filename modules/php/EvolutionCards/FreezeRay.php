<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class FreezeRay extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
            $ownerId = $this->ownerId;
            if ($context->currentPlayerId != $ownerId && !$context->game->getPlayer($ownerId)->eliminated) {
                $question = new Question(
                    'FreezeRay',
                    clienttranslate('${actplayer} must choose a die face that will have no effect that turn'),
                    clienttranslate('${you} must choose a die face that will have no effect that turn'),
                    [$ownerId],
                    \ST_QUESTIONS_BEFORE_START_TURN,
                    [ 'card' => $this ]
                );
                $context->game->setQuestion($question);
                $context->game->gamestate->setPlayersMultiactive([$ownerId], 'next', true);

                $context->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
                return;
            } else {
                $context->game->setUsedCard(3000 + $this->id);
            }
    }

}
