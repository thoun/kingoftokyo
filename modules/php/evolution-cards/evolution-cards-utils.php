<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/evolution-card.php');
require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\EvolutionCard;

trait EvolutionCardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////


    function initEvolutionCards() {
        foreach($this->MONSTERS_WITH_POWER_UP_CARDS as $monster) {
            for($card=1; $card<=8; $card++) {
                $type = $monster * 10 + $card;
                $cards[] = ['type' => $type, 'type_arg' => 1 /* TODOPU $this->EVOLUTION_CARDS_TYPES[$type]*/, 'nbr' => 1];
            }
            $location = 'deck'.$monster;
            $this->evolutionCards->createCards($cards, $location);
            $this->evolutionCards->shuffle($location); 
        }
    }

    function getEvolutionCardFromDb(array $dbCard) {
        if (!$dbCard || !array_key_exists('id', $dbCard)) {
            throw new \Error('card doesn\'t exists '.json_encode($dbCard));
        }
        if (!$dbCard || !array_key_exists('location', $dbCard)) {
            throw new \Error('location doesn\'t exists '.json_encode($dbCard));
        }
        return new EvolutionCard($dbCard);
    }

    function getEvolutionCardsFromDb(array $dbCards) {
        return array_map(fn($dbCard) => $this->getEvolutionCardFromDb($dbCard), array_values($dbCards));
    }
}