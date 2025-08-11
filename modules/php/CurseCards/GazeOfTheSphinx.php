<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\CurseCards;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class GazeOfTheSphinx extends CurseCard {

    public function applyAnkhEffect(Context $context) {
        if ($context->game->isPowerUpExpansion()) {
            $question = new Question(
                'GazeOfTheSphinxAnkh',
                clienttranslate('${actplayer} must choose to draw an Evolution card or gain 3[Energy]'),
                clienttranslate('${you} must choose to draw an Evolution card or gain 3[Energy]'),
                [$context->currentPlayerId],
                ST_RESOLVE_DICE,
                []
            );
            $context->game->setQuestion($question);
            $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);
            return ST_MULTIPLAYER_ANSWER_QUESTION;
        } else {
            $context->game->applyGetEnergy($context->currentPlayerId, 3, $this);
        }
    }

    public function applySnakeEffect(Context $context) {
        $canLoseEvolution = false;
        if ($context->game->isPowerUpExpansion()) {
            $canLoseEvolution = (
                intval($context->game->evolutionCards->countCardInLocation('table', $context->currentPlayerId)) +
                intval($context->game->evolutionCards->countCardInLocation('hand', $context->currentPlayerId))
            ) > 0;
        }
        if ($canLoseEvolution) {
            $playerEnergy = $context->game->getPlayerEnergy($context->currentPlayerId);
            $question = new Question(
                'GazeOfTheSphinxSnake',
                clienttranslate('${actplayer} must choose to discard an Evolution card (from its hand or in play) or lose 3[Energy]'),
                clienttranslate('Click on an Evolution card (from your hand or in play) to discard it or lose 3[Energy]'),
                [$context->currentPlayerId],
                ST_RESOLVE_DICE,
                [
                    'canLoseEnergy' => $playerEnergy >= 3,
                ]
            );
            $context->game->setQuestion($question);
            $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);
            return ST_MULTIPLAYER_ANSWER_QUESTION;
        } else {
            $context->game->applyLoseEnergy($context->currentPlayerId, 3, $this);
        }
    }
}

?>