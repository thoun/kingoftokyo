<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class CrabClaw extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }
    
    public function applyEffect(Context $context, array $woundedPlayers) {
        $playerCards = [];
        foreach ($woundedPlayers as $woundedPlayerId) {
            $woundedPlayerCards = $context->game->powerCards->getPlayerReal($woundedPlayerId);
            
            if (count($woundedPlayerCards) > 0) {
                $playerCards[$woundedPlayerId] = $woundedPlayerCards;
            }
        }

        if (count($playerCards) === 0) {
            return false;
        }


        $question = new Question(
            'CrabClaw',
            clienttranslate('Wounded players must discard Power cards or pay to keep them'),
            clienttranslate('${you} must discard Power cards or pay to keep them'),
            array_keys($playerCards),
            ST_AFTER_RESOLVE_DAMAGE,
            [ 
                'playerId' => $context->currentPlayerId,
                'playerCards' => $playerCards,
            ]
        );

        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive(array_keys($playerCards), 'next', true);
        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
        return true;
    }
}
