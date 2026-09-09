<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFramework\VisibleSystemException;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;

use function Bga\Games\KingOfTokyo\debug;

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
                $evolutionId = (int)($question->evolutionId ?? $question->args->cardId ?? $question->args->card->id);
                $evolution = $this->game->powerUpExpansion->evolutionCards->items->getItemById($evolutionId);
                if ($evolution === null) {
                    throw new VisibleSystemException('Evolution card not found');
                }
                $this->game->removeEvolution($question->args->playerId, $evolution);
            }

            $this->game->removeStackedStateAndRedirect();
            return;
        }

        if (in_array($question->code, ['TargetAcquired', 'LightningArmor', 'CrabClaw'])) {
            $this->game->goToState(\ST_AFTER_RESOLVE_DAMAGE);
            return;
        }

        if ($question->code === 'MindbugsOverlord') {
            $evolution = $this->game->powerUpExpansion->evolutionCards->items->getItemById($question->args->evolutionId);
            $this->game->setUsedCard(3000 + $evolution->id);
            $evolution->onMonstersDeclaredAllegiance(new Context($this->game, $question->args->askingPlayerId), $question);
            return;
        }

        throw new VisibleSystemException("Question code not handled: ".$question->code);
    }
}
