<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class AfterAnswerQuestion extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_AFTER_ANSWER_QUESTION,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $question = $this->game->getQuestion();

        if ($question->code === 'GiveSymbol' || $question->code === 'GiveEnergyOrLoseHearts' || $question->code === 'ElectricCarrot') {
            if ($question->code === 'GiveSymbol' || $question->code === 'GiveEnergyOrLoseHearts') {
                $this->game->removeEvolution($question->args->playerId, $question->args->card);
            }

            $this->game->removeStackedStateAndRedirect();
            return;
        }

        if ($question->code === 'TargetAcquired' || $question->code === 'LightningArmor') {
            $this->game->goToState(\ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE);
            return;
        }

        throw new \BgaVisibleSystemException("Question code not handled: ".$question->code);
    }
}

