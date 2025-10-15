<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class IcyReflection extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function immediateEffect(Context $context) {
        $enabledEvolutions = [];
        $disabledEvolutions = [];
        $playersIds = $context->game->getPlayersIds();
        foreach($playersIds as $pId) {
            $evolutions = $context->game->getEvolutionCardsByLocation('table', $pId);
            foreach($evolutions as $evolution) {
                if ($evolution->type != ICY_REFLECTION_EVOLUTION && $context->game->EVOLUTION_CARDS_TYPES[$evolution->type] == 1) {
                    $enabledEvolutions[] = $evolution;
                } else {
                    $disabledEvolutions[] = $evolution;
                }
            }
        }
        
        $question = new Question(
            'IcyReflection',
            clienttranslate('${player_name} must choose an Evolution card to copy'),
            clienttranslate('${you} must choose an Evolution card to copy'),
            [$context->currentPlayerId],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'playerId' => $context->currentPlayerId,
                '_args' => [ 'player_name' => $context->game->getPlayerNameById($context->currentPlayerId) ],
                'enabledEvolutions' => $enabledEvolutions,
                'disabledEvolutions' => $disabledEvolutions,
            ]
        );

        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);
        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }
}