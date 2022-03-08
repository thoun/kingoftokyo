<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/evolution-card.php');
require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\EvolutionCard;

trait EvolutionCardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////


    function initEvolutionCards(array $affectedPlayersMonsters) {
        foreach($this->MONSTERS_WITH_POWER_UP_CARDS as $monster) {
            $cards = [];
            for($card=1; $card<=8; $card++) {
                $type = $monster * 10 + $card;
                $cards[] = ['type' => $type, 'type_arg' => 1 /* TODOPU $this->EVOLUTION_CARDS_TYPES[$type]*/, 'nbr' => 1];
            }
            $location = array_key_exists($monster, $affectedPlayersMonsters) ? 'deck'.$affectedPlayersMonsters[$monster] : 'monster'.$monster;
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

    function pickEvolutionCards(int $playerId) {
        // TODOPU shuffle and use discard if necessary
        return $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOnTop(2, 'deck'.$playerId));
    }

    function notifNewEvolutionCard(int $playerId, EvolutionCard $card) {
        $this->notifyPlayer($playerId, "addEvolutionCardInHand", '', [
            'playerId' => $playerId,
            'card' => $card,
        ]);    

        $this->notifyAllPlayers("addEvolutionCardInHand", '', [
            'playerId' => $playerId,
            'card' => EvolutionCard::createBackCard($card->id),
        ]);
    }

    

    function hasEvolutionOfType(int $playerId, int $cardType, bool $fromTable = true, bool $fromHand = false) {
        return $this->getEvolutionOfType($playerId, $cardType, $fromTable, $fromHand) != null;
    }

    function getEvolutionOfType(int $playerId, int $cardType, bool $fromTable = true, bool $fromHand = false) {
        if (!$this->keepAndEvolutionCardsHaveEffect()) {
            return null;
        }

        if ($fromTable) {
            $cards = $this->getCardsFromDb($this->evolutionCards->getCardsOfTypeInLocation($cardType, null, 'table', $playerId));
            if (count($cards) > 0) {
                return $cards[0];
            }
        }

        if ($fromHand) {
            $cards = $this->getCardsFromDb($this->evolutionCards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId));
            if (count($cards) > 0) {
                return $cards[0];
            }
        }

        return null;
    }
}