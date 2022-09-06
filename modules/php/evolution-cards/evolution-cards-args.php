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
            $chooseCardIn = $this->getEvolutionCardsByLocation('mutant'.(($index + $turn) % count($playersIds)));
            $inDeck = $this->getEvolutionCardsByLocation('deck'.$playerId);
            $privateArgs[$playerId] = [
                'chooseCardIn' => $chooseCardIn,
                'inDeck' => $inDeck,
            ];
        }

        return [
            '_private' => $privateArgs,
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

    function argBeforeStartTurn() {
        $isPowerUpExpansion = $this->isPowerUpExpansion();

        $highlighted = $isPowerUpExpansion ? $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_BEFORE_START) : [];

        return [
            'highlighted' => $highlighted,
        ];
    }

    function argBeforeResolveDice() {
        $isPowerUpExpansion = $this->isPowerUpExpansion();

        $highlighted = $isPowerUpExpansion ? $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE) : [];

        return [
            'highlighted' => $highlighted,
        ];
    }

    function argBeforeEnteringTokyo() {
        $isPowerUpExpansion = $this->isPowerUpExpansion();

        $highlighted = $isPowerUpExpansion && $this->tokyoHasFreeSpot() ? $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO) : [];


        $playerId = $this->getActivePlayerId();
        $otherPlayersIds = $this->getOtherPlayersIds($playerId);

        $felineMotorPlayersIds = array_values(array_filter($otherPlayersIds, fn($pId) => $this->countEvolutionOfType($pId, FELINE_MOTOR_EVOLUTION) > 0));

        return [
            'highlighted' => $highlighted,
            'canUseFelineMotor' => $felineMotorPlayersIds,
        ];
    }

    function argAfterEnteringTokyo() {
        $activePlayerId = $this->getActivePlayerId();

        $player = $this->getPlayer($activePlayerId);

        $highlighted = $player->location > 0 && $player->turnEnteredTokyo ?
            $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO) :
            $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO);

        return [
            'highlighted' => $highlighted,
        ];
    }

    function argCardIsBought() {
        $isPowerUpExpansion = $this->isPowerUpExpansion();

        $highlighted = $isPowerUpExpansion ? $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT) : [];

        return [
            'highlighted' => $highlighted,
        ];
    }

    function argAnswerQuestion() {
        $question = $this->getQuestion();

        $args = [
            'question' => $question,
        ];

        if (gettype($question->args) === 'object' && property_exists($question->args, '_args')) {
            $args = array_merge($args, (array)$question->args->{'_args'});
        }

        return $args;
    }

    function argBeforeEndTurn() {
        $isPowerUpExpansion = $this->isPowerUpExpansion();

        $highlighted = [];
        $privatePlayers = [];
        
        if ($isPowerUpExpansion) {
            $highlighted = $this->getHighlightedEvolutions($this->EVOLUTION_TO_PLAY_BEFORE_END);

            $players = $this->getPlayers();
            foreach ($players as $player) {
                $evolutionsWithEffectCounter = [];
                $handEvolutions = $this->getEvolutionCardsByLocation('hand', $player->id);
                // all $this->EVOLUTION_TO_PLAY_BEFORE_END_MULTI = [
                $angerBatteriesEvolution = $this->array_find($handEvolutions, fn($evolution) => $evolution->type == ANGER_BATTERIES_EVOLUTION);
                $strokeOfGeniusEvolution = $this->array_find($handEvolutions, fn($evolution) => $evolution->type == STROKE_OF_GENIUS_EVOLUTION);
                $cultWorshippersEvolution = $this->array_find($handEvolutions, fn($evolution) => $evolution->type == CULT_WORSHIPPERS_EVOLUTION);

                if ($angerBatteriesEvolution != null) {
                    $evolutionsWithEffectCounter[$angerBatteriesEvolution->id] = [$player->turnLostHealth, 5];
                }
                if ($strokeOfGeniusEvolution != null) {
                    $evolutionsWithEffectCounter[$strokeOfGeniusEvolution->id] = [$player->turnEnergy, 5];
                }
                if ($cultWorshippersEvolution != null) {
                    $evolutionsWithEffectCounter[$cultWorshippersEvolution->id] = [$player->turnGainedHealth, 0];
                }
                $privatePlayers[$player->id] = $evolutionsWithEffectCounter;
            }
        } 

        return [
            'highlighted' => $highlighted,
            '_private' => $privatePlayers,
        ];
    }

}
