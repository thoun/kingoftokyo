<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFramework\UserException;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class MindControl extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $activePlayerId = (int)$context->game->getActivePlayerId();
        if($context->currentPlayerId === $activePlayerId) {
            throw new UserException('You cannot use this Evolution on yourself');
        }

        $activePlayerDice = $context->game->getPlayerRolledDice($activePlayerId, true, true, false);
        $selectableDice = $context->game->getSelectableDice($activePlayerDice, false, false);

        $playerId = $context->currentPlayerId;

        $diceCount = Arrays::count($activePlayerDice, fn($die) => $die->type < 2);
        $min = min(3, $diceCount);
        $max = min(3, $diceCount);

        $args = [
            'playerId' => $playerId,
            'player_name' => $context->game->getPlayerNameById($playerId),
            'dice' => $activePlayerDice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $context->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $context->game->frozenFaces($activePlayerId),
            'min' => $min,
            'max' => $max,
        ];

        $question = new Question(
            'MindControl',
            clienttranslate('${actplayer} must choose 3 dice to reroll'),
            clienttranslate('${you} must choose 3 dice to reroll'),
            [$context->currentPlayerId],
            -1,
            $args,
            evolutionId: $this->id,
        );
        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }
}
