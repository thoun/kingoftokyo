<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class ElectricCarrot extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    function electricCarrotQuestion(Context $context, array $smashedPlayersIds) {
        $question = new Question(
            'ElectricCarrot',
            clienttranslate('Smashed players can give 1[Energy] or lose 1 extra [Heart]'),
            clienttranslate('${you} can give 1[Energy] or lose 1 extra [Heart]'),
            $smashedPlayersIds,
            ST_AFTER_RESOLVE_DAMAGE,
            [],
            evolutionId: $this->id,
        );

        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive($smashedPlayersIds, 'next', true);
        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

}
