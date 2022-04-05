<?php

namespace KOT\States;

trait EvolutionCardsArgTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPickEvolutionForDeck() {
        $turn = intval($this->getGameStateValue(MUTANT_EVOLUTION_TURN));
        $playersIds = $this->getPlayersIds();
        $privateArgs = [];
        foreach($playersIds as $index => $playerId) {
            $chooseCardIn = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('mutant'.(($index + $turn) % count($playersIds))));
            $inDeck = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('deck'.$playerId));
            $privateArgs[$playerId] = [
                'chooseCardIn' => $chooseCardIn,
                'inDeck' => $inDeck,
            ];
        }

        return [
            '_private' => $privateArgs,
        ];
    }

    function argBeforeStartTurn() {
        $isPowerUpExpansion = $this->isPowerUpExpansion();

        return [
            'canPlayEvolution' => $isPowerUpExpansion, // TODOPU
            'highlighted' => $isPowerUpExpansion ? $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_BEFORE_START) : [],
        ];
    }
    
    function argChooseEvolutionCard() {
        $activePlayerId = $this->getActivePlayerId();

        return [
            '_private' => [
                $activePlayerId => [
                    'evolutions' => $this->pickEvolutionCards($activePlayerId),
                ],
            ],
        ];
    }

    function argCardIsBought() {
        return [
            'highlighted' => $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT),
        ];
    }

    function argAnswerQuestion() {
        $question = $this->getQuestion();

        $args = [
            'question' => $question,
        ];

        if ($question->args->{'_args'}) {
            $args = array_merge($args, (array)$question->args->{'_args'});
        }

        return $args;
    }

}
