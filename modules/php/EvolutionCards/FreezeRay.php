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

    function freezeRayChooseOpponentQuestion(Context $context, array $smashedPlayersIds) {
        $question = new Question(
            'FreezeRayChooseOpponent',
            clienttranslate('${player_name} must choose an opponent to give ${card_name} to'),
            clienttranslate('${you} must choose an opponent to give ${card_name} to'),
            [$context->currentPlayerId],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'playerId' => $context->currentPlayerId,
                '_args' => [ 
                    'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
                    'card_name' => 3000 + $this->type,
                ],
                'card' => $this,
                'smashedPlayersIds' => $smashedPlayersIds,
            ]
        );

        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);
        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function giveFreezeRay(Context $context, int $fromPlayerId, int $toPlayerId) {
        $ownerId = $this->ownerId;
        if ($ownerId == $fromPlayerId) {
            $context->game->giveEvolution($fromPlayerId, $toPlayerId, $this);
        }
    }

    function giveBackFreezeRay(Context $context) {
        $ownerId = $this->ownerId;
        if ($ownerId != $context->currentPlayerId) {
            $context->game->giveEvolution($context->currentPlayerId, $ownerId, $this);

            // reset freeze ray disabled symbol
            $context->game->setEvolutionTokens($ownerId, $this, 0, true);
        }
    }

}
