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
            [
                'evolutionId' => $this->id,
                'askingPlayerId' => $context->currentPlayerId,
                'declaredAllegiance' => [],
            ],
            evolutionId: $this->id,
        );
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive($otherPlayerIds, 'next', true);

        $context->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
        return true;
    }

    public function onMonstersDeclaredAllegiance(Context $context, /*object as Question*/ $firstQuestion) {
        $declaredAllegiance = $firstQuestion->args->declaredAllegiance;
        if (count($declaredAllegiance) === 0) {
            $context->game->notify->all('log', clienttranslate('No Monster declared allegiance, Mindbugs Overlord effect is ignored'));
            $context->game->goToState($firstQuestion->stateIdAfter);
            return;
        }

        $question = new Question(
            'MindbugsOverlordChoosePlayer',
        /* TODOMB clienttranslate*/('${actplayer} must choose of the Monsters who declared allegiance'),
        /* TODOMB clienttranslate*/('${you} must choose of the Monsters who declared allegiance'),
            [$context->currentPlayerId],
            \ST_QUESTIONS_BEFORE_START_TURN,
            [
                'evolutionId' => $this->id,
                'declaredAllegiance' => $declaredAllegiance,
            ],
            evolutionId: $this->id,
        );
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function onChosenMonster(Context $context, int $targetPlayerId) {
        $giveSymbols = [];
        $targetPoints = $context->game->getPlayerScore($targetPlayerId);
        for ($i = 0; $i < 4 && $i < $targetPoints; $i++) {
            $giveSymbols[] = 0;
        }
        $context->game->applyGiveSymbols($giveSymbols, $targetPlayerId, $context->currentPlayerId, $this);
        $context->game->mindbugExpansion->applyGetMindbugTokens($targetPlayerId, 1, $this);
    }
}
