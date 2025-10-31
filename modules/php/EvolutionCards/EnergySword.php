<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class EnergySword extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $potentialEnergy = $context->game->getPlayerPotentialEnergy($context->currentPlayerId);

        if ($potentialEnergy >= 2) {
            $question = new Question(
                'EnergySword',
                clienttranslate('${actplayer} can pay 2[Energy] for ${card_name}'),
                clienttranslate('${you} can pay 2[Energy] for ${card_name}'),
                [$context->currentPlayerId],
                \ST_QUESTIONS_BEFORE_START_TURN,
                [
                    '_args' => [
                        'card_name' => 3000 + \ENERGY_SWORD_EVOLUTION,
                    ],
                ],
                evolutionId: $this->id,
            );
            $context->game->setQuestion($question);
            $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

            $context->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
            return true;
        } else {
            $context->game->setEvolutionTokens($context->currentPlayerId, $this, 0, true);
            return false;
        }
    }

}
