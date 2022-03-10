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

    function applyEvolutionEffects(int $cardType, int $playerId) { // return $damages
        if (!$this->keepAndEvolutionCardsHaveEffect()) {
            return;
        }

        $logCardType = 3000 + $cardType;

        switch($cardType) {
            // Space Penguin
            // Alienoid
            case ALIEN_SCOURGE_EVOLUTION: 
                $this->applyGetPoints($playerId, 2, $logCardType);
                break;
            case ANGER_BATTERIES_EVOLUTION:
                $damageCount = $this->getDamageTakenThisTurn($playerId);
                $this->applyGetEnergy($playerId, $damageCount, $logCardType);
                break;
            // Cyber Kitty
            // The King
            case GIANT_BANANA_EVOLUTION:
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            // Gigazaur
            case RADIOACTIVE_WASTE_EVOLUTION:
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->applyGetHealth($playerId, 1, $logCardType, $playerId);
                break;
            case PRIMAL_BELLOW_EVOLUTION:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 2, $logCardType);
                }
                break;
            // Meka Dragon
            case DESTRUCTIVE_ANALYSIS_EVOLUTION:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                if ($rolledSmashes > 0) {
                    $this->applyGetEnergy($playerId, $rolledSmashes, $logCardType);
                }
                break;
            // Boogie Woogie
            // Pumpkin Jack
            // Cthulhu
            // Anubis
            // King Kong
            // Cybertooth
            // Pandakaï
            case PANDA_MONIUM_EVOLUTION:
                $this->applyGetEnergy($playerId, 6, $logCardType);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyGetEnergy($otherPlayerId, 3, $logCardType);
                }
                break;
            case BEAR_NECESSITIES_EVOLUTION:
                $this->applyLosePoints($playerId, 1, $logCardType);
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            // cyberbunny
            // kraken
            // Baby Gigazaur
        }
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

    function removeEvolution(int $playerId, $card, bool $silent = false, int $delay = 0, bool $ignoreMimicToken = false) {
        $changeMaxHealth = $card->type == EVEN_BIGGER_CARD; // TODOPU

        $countMothershipSupportBefore = $this->hasEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION) ? 1 : 0;
        
        $mimicCardType = null;
        /*if ($card->type == MIMIC_CARD) { // Mimic
            $changeMaxHealth = $this->getMimickedCardType(MIMIC_CARD) == EVEN_BIGGER_CARD;
            $this->removeMimicToken(MIMIC_CARD, $playerId);
            $removeMimickToken = true;
            $mimicCardType = MIMIC_CARD;
        } else if ($card->id == $this->getMimickedCardId(MIMIC_CARD) && !$ignoreMimicToken) {
            $this->removeMimicToken(MIMIC_CARD, $playerId);
            $removeMimickToken = true;
            $mimicCardType = MIMIC_CARD;
        }
        if ($card->id == $this->getMimickedCardId(FLUXLING_WICKEDNESS_TILE) && !$ignoreMimicToken) {
            $this->removeMimicToken(FLUXLING_WICKEDNESS_TILE, $playerId);
            $removeMimickToken = true;
            $mimicCardType = FLUXLING_WICKEDNESS_TILE;
        }*/

        $this->evolutionCards->moveCard($card->id, 'discard'.$playerId);

        if (!$silent) {
            $this->notifyAllPlayers("removeEvolutions", '', [
                'playerId' => $playerId,
                'cards' => [$card],
                'delay' => $delay,
            ]);
        }
        if ($changeMaxHealth) {
            $this->changeMaxHealth($playerId);
        }
        
        $this->toggleMothershipSupport($playerId, $countMothershipSupportBefore);
    }

    function isEvolutionOnTable(int $type) { // owner id | null
        $cards = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfType($type));
        if (count($cards) > 0) {
            $card = $cards[0];
        
            if ($card->location == 'table') {
                return $card->location_arg;
            }
        }  
        return null;          
    }
}