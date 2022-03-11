<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/evolution-card.php');
require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\EvolutionCard;
use KOT\Objects\Damage;

trait EvolutionCardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////


    function initEvolutionCards(array $affectedPlayersMonsters) {
        foreach($this->MONSTERS_WITH_POWER_UP_CARDS as $monster) {
            $cards = [];
            for($card=1; $card<=8; $card++) {
                $type = $monster * 10 + $card;
                $cards[] = ['type' => $type, 'type_arg' => 0, 'nbr' => 1];
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

    function canPlayEvolution(int $cardType, int $playerId) {

        // cards to player before starting turn
        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_BEFORE_START) && intval($this->gamestate->state_id()) != ST_PLAYER_BEFORE_START_TURN) {
            return false;
        }

        switch($cardType) {
            case SIMIAN_SCAMPER_EVOLUTION:
            case DETACHABLE_TAIL_EVOLUTION:
                return false;
            case TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION:
                return $this->inTokyo($playerId); // TODOPU use only when you enter Tokyo
            case EATS_SHOOTS_AND_LEAVES_EVOLUTION:
                return $this->inTokyo($playerId); // TODOPU use only when you enter Tokyo
            case TUNE_UP_EVOLUTION:
                return !$this->inTokyo($playerId);
        }

        return true;
    }

    function playEvolutionToTable(int $playerId, EvolutionCard $card, /*string | null*/ $message = null) {
        if ($message == null) {
            $message = clienttranslate('${player_name} plays ${card_name}');
        }

        $this->evolutionCards->moveCard($card->id, 'table', $playerId);

        $this->notifyAllPlayers("playEvolution", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $card,
            'card_name' => 3000 + $card->type,
        ]);
    }

    function applyEvolutionEffects(EvolutionCard $card, int $playerId) { // return $damages
        if (!$this->keepAndEvolutionCardsHaveEffect()) {
            return;
        }

        $cardType = $card->type;
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
            case TUNE_UP_EVOLUTION:
                $this->applyGetHealth($playerId, 4, $logCardType, $playerId);
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->removeCard($playerId, $card, false, 5000);
                $this->goToState(ST_NEXT_PLAYER);
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
            case EATS_SHOOTS_AND_LEAVES_EVOLUTION:
                $outsideTokyoPlayersIds = $this->getPlayersIdsOutsideTokyo();
                $damages = [];
                foreach ($outsideTokyoPlayersIds as $outsideTokyoPlayerId) {
                    $damages[] = new Damage($outsideTokyoPlayerId, 2, $playerId, $logCardType);
                }

                $this->applyGetEnergy($playerId, 1, $logCardType);
                $this->leaveTokyo($playerId, false); // TODOPU confirm

                return $damages;
            case BEAR_NECESSITIES_EVOLUTION:
                $this->applyLosePoints($playerId, 1, $logCardType);
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            case BAMBOO_SUPPLY_EVOLUTION:
                // TODOPU $this->goToState(ST_START_TURN);
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
            $cards = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfTypeInLocation($cardType, null, 'table', $playerId));
            if (count($cards) > 0) {
                return $cards[0];
            }
        }

        if ($fromHand) {
            $cards = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId));
            if (count($cards) > 0) {
                return $cards[0];
            }
        }

        return null;
    }

    function getEvolutionsOfType(int $playerId, int $cardType, bool $fromTable = true, bool $fromHand = false) {
        $card = $this->getEvolutionOfType($playerId, $cardType, $fromTable, $fromHand);

        if ($card != null) {
            return [$card];
        }
        
        return [];
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

    function applyLeaveWithTwasBeautyKilledTheBeast(int $playerId, EvolutionCard $card) {
        $this->removeEvolution($playerId, $card);

        // lose all stars
        $points = 0;
        $this->DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
        $this->notifyAllPlayers('points', /*client TODOPU translate(*/'${player_name} left Tokyo when ${card_name} is played, and loses all [Star].'/*)*/, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'points' => $points,
            'card_name' => 3000 + TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION,
        ]);
    }

    function applyYinYang(int $playerId) {
        $dice = $this->getPlayerRolledDice($playerId, false, false, false);
        $YIN_YANG_OTHER_FACE = [
            1 => 3,
            2 => 4,
            3 => 1,
            4 => 2,
            5 => 6,
            6 => 5,
        ];

        foreach ($dice as $die) {
            $otherFace = $YIN_YANG_OTHER_FACE[$die->value];
            $this->DbQuery("UPDATE dice SET `rolled` = false, `dice_value` = ".$otherFace." where `dice_id` = ".$die->id);

            $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
            $this->notifyAllPlayers("changeDie", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => 3000 + YIN_YANG_EVOLUTION,
                'dieId' => $die->id,
                'canHealWithDice' => $this->canHealWithDice($playerId),
                'toValue' => $otherFace,
                'die_face_before' => $this->getDieFaceLogName($die->value, $die->type),
                'die_face_after' => $this->getDieFaceLogName($otherFace, $die->type),
            ]);
        }
    }

    function getFirstUnusedBambooSupply(int $playerId) {
        $bambooSupplyCards = $this->getEvolutionsOfType($playerId, BAMBOO_SUPPLY_EVOLUTION);
        $usedCards = $this->getUsedCard();
        $unusedBambooSupplyCard = $this->array_find($bambooSupplyCards, fn($card) => !in_array(3000 + $card->id, $usedCards));
        return $unusedBambooSupplyCard;
    }

    function setEvolutionTokens(int $playerId, $card, int $tokens, bool $silent = false) {
        $card->tokens = $tokens;
        $this->DbQuery("UPDATE `evolution_card` SET `card_type_arg` = $tokens where `card_id` = ".$card->id);

        if (!$silent) {
            /*TODOPU if ($card->type == MIMIC_CARD) {
                $card->mimicType = $this->getMimickedCardType(MIMIC_CARD);
            }*/
            $this->notifyAllPlayers("setEvolutionTokens", '', [
                'playerId' => $playerId,
                'card' => $card,
            ]);
        }
    }
}