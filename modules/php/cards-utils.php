<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Damage;
use KOT\Objects\PlayersUsedDice;

trait CardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function initCards() {
        $cards = [];
        
        for($value=1; $value<=48; $value++) { // keep  
            $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
        }
        
        for($value=101; $value<=118; $value++) { // discard
            $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
        }

        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck'); 

        if ($this->isHalloweenExpansion()) { 
            $cards = [];

            for($value=201; $value<=212; $value++) { // costume
                $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
            }

            $this->cards->createCards($cards, 'costumedeck');
            $this->cards->shuffle('costumedeck'); 
        }
    }

    function getCardFromDb(array $dbCard) {
        if (!$dbCard || !array_key_exists('id', $dbCard)) {
            throw new Error('card doesn\'t exists '.json_encode($dbCard));
        }
        if (!$dbCard || !array_key_exists('location', $dbCard)) {
            throw new Error('location doesn\'t exists '.json_encode($dbCard));
        }
        return new Card($dbCard);
    }

    function getCardsFromDb(array $dbCards) {
        return array_map(function($dbCard) { return $this->getCardFromDb($dbCard); }, array_values($dbCards));
    }

    function applyEffects(int $cardType, int $playerId, bool $opportunist) { // return $damages

        switch($cardType) {
            // KEEP
            case EVEN_BIGGER_CARD: 
                $this->applyGetHealth($playerId, 2, $cardType);
                $this->changeMaxHealth($playerId);
                break;
            
            // DISCARD
            case 101: 
                $this->applyGetPoints($playerId, 3, $cardType);
                break;
            case 102:
                $this->applyGetPoints($playerId, 2, $cardType);
                break;
            case 103:
                $this->applyGetPoints($playerId, 1, $cardType);
                break;
            case 104: 
                $this->applyGetPoints($playerId, 2, $cardType);

                // remove other players in Tokyo
                $damages = [];
                $playerInTokyoCity = $this->getPlayerIdInTokyoCity();
                $playerInTokyoBay = $this->getPlayerIdInTokyoBay();
                if ($playerInTokyoBay != null && $playerInTokyoBay > 0 && $playerInTokyoBay != $playerId) {
                    $this->leaveTokyo($playerInTokyoBay);
        
                    // burrowing
                    $countBurrowing = $this->countCardOfType($playerInTokyoBay, BURROWING_CARD);
                    if ($countBurrowing > 0) {
                        $damages[] = new Damage($playerId, $countBurrowing, $playerInTokyoBay, BURROWING_CARD);
                    }
                }
                if ($playerInTokyoCity != null && $playerInTokyoCity > 0 && $playerInTokyoCity != $playerId) {
                    $this->leaveTokyo($playerInTokyoCity);
        
                    // burrowing
                    $countBurrowing = $this->countCardOfType($playerInTokyoCity, BURROWING_CARD);
                    if ($countBurrowing > 0) {
                        $damages[] = new Damage($playerId, $countBurrowing, $playerInTokyoCity, BURROWING_CARD);
                    }
                }

                if ($playerInTokyoBay == $playerId) {
                    $this->moveFromTokyoBayToCity($playerId);
                } else if ($playerInTokyoCity != $playerId) {
                    // take control of Tokyo
                    $this->moveToTokyo($playerId, false);
                }
            
                return $damages;
            case 105:
                $this->applyGetEnergy($playerId, MEDIA_FRIENDLY_CARD, $cardType);
                break;
            case 106: case 107:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 5, $cardType);
                }
                break;
            case 108: 
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 2, $playerId, $cardType);
                }
                return $damages;
            case FRENZY_CARD: 
                if ($opportunist) {
                    $this->setGameStateValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, $playerId);
                    $this->setGameStateValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, self::getActivePlayerId());
                } else {
                    $this->setGameStateValue(FRENZY_EXTRA_TURN, 1);
                }
                break;
            case 110: 
                $this->applyGetPoints($playerId, 2, $cardType);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 3, $playerId, $cardType);
                }
                return $damages;
            case 111:
                $this->applyGetHealth($playerId, 2, $cardType);
                break;
            case 112: 
                $playersIds = $this->getPlayersIds();
                $damages = [];
                foreach ($playersIds as $pId) {
                    $damages[] = new Damage($pId, 3, $playerId, $cardType);
                }
                return $damages;
            case 113: 
                $this->applyGetPoints($playerId, 5, $cardType);
                return [new Damage($playerId, 4, $playerId, $cardType)];
            case 114:
                $this->applyGetPoints($playerId, 2, $cardType);
                return [new Damage($playerId, 2, $playerId, $cardType)];
            case 115:
                $this->applyGetPoints($playerId, 2, $cardType);
                $this->applyGetHealth($playerId, 3, $cardType);
                break;
            case 116:
                $this->applyGetPoints($playerId, 4, $cardType);
                break;
            case 117:
                $this->applyGetPoints($playerId, 4, $cardType);
                return [new Damage($playerId, 3, $playerId, $cardType)];
            case 118: 
                $this->applyGetPoints($playerId, 2, $cardType);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $energy = $this->getPlayerEnergy($otherPlayerId);
                    $lostEnergy = floor($energy / 2);
                    $this->applyLoseEnergy($otherPlayerId, $lostEnergy, $cardType);
                }
                break;
            case 119:
                $count = $this->cards->countCardInLocation('hand', $player_id);
                $this->applyGetPoints($playerId, $count, $cardType);
                return [new Damage($playerId, $count, $playerId, $cardType)];
        }
    }

    function removeMimicToken(int $mimicOwnerId) {
        $countRapidHealingBefore = $this->countCardOfType($mimicOwnerId, RAPID_HEALING_CARD);
        
        $card = $this->getMimickedCard();
        if ($card) {
            $this->deleteGlobalVariable(MIMICKED_CARD);
            self::notifyAllPlayers("removeMimicToken", '', [
                'card' => $card,
            ]);
        }

        $mimicCard = $this->getCardFromDb(array_values($this->cards->getCardsOfType(MIMIC_CARD))[0]);
        if ($mimicCard && $mimicCard->tokens > 0) {
            $this->setCardTokens($mimicCard->location_arg, $mimicCard, 0);
        }

        if ($mimicCard && $card && $card->type == EVEN_BIGGER_CARD) {
            $this->changeMaxHealth($mimicCard->location_arg);
        } 
    
        $this->toggleRapidHealing($mimicOwnerId, $countRapidHealingBefore);
    }

    function setMimickedCardId(int $mimicOwnerId, int $cardId) {
        $card = $this->getCardFromDb($this->cards->getCard($cardId));
        $this->setMimickedCard($mimicOwnerId, $card);
    }

    function setMimickedCard(int $mimicOwnerId, object $card) {
        $countRapidHealingBefore = $this->countCardOfType($mimicOwnerId, RAPID_HEALING_CARD);

        $this->removeMimicToken($mimicOwnerId);

        $mimickedCard = new \stdClass();
        $mimickedCard->card = $card;
        $mimickedCard->playerId = $card->location_arg;
        $this->setGlobalVariable(MIMICKED_CARD, $mimickedCard);
        self::notifyAllPlayers("setMimicToken", clienttranslate('${player_name} mimics ${card_name}'), [
            'card' => $card,
            'player_name' => $this->getPlayerName($mimicOwnerId),
            'card_name' => $card->type,
        ]);

        // no need to check for damage return, no discard card can be mimicked
        $this->applyEffects($card->type, $mimicOwnerId, false);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            $mimicCard = $this->getCardFromDb(array_values($this->cards->getCardsOfType(MIMIC_CARD))[0]);
            $this->setCardTokens($mimicOwnerId, $mimicCard, $tokens);
        }
        
        $this->toggleRapidHealing($mimicOwnerId, $countRapidHealingBefore);
    }

    function getMimickedCard() {
        $mimickedCardObj = $this->getGlobalVariable(MIMICKED_CARD);
        if ($mimickedCardObj != null) {
            return $mimickedCardObj->card;
        }
        return null;
    }

    function getMimickedCardId() {
        $mimickedCard = $this->getMimickedCard();
        if ($mimickedCard != null) {
            return $mimickedCard->id;
        }
        return null;
    }

    function getMimickedCardType() {
        $mimickedCard = $this->getMimickedCard();
        if ($mimickedCard != null) {
            return $mimickedCard->type;
        }
        return null;
    }

    function getMadeInALabCardIds($playerId) {
        $madeInALab = $this->getGlobalVariable(MADE_IN_A_LAB, true);
        if (array_key_exists($playerId, $madeInALab)) {
            return $madeInALab[$playerId];
        }
        return [];
    }

    function setMadeInALabCardIds(int $playerId, array $cardIds) {
        $madeInALab = $this->getGlobalVariable(MADE_IN_A_LAB, true);
        $madeInALab[$playerId] = $cardIds;
        $this->setGlobalVariable(MADE_IN_A_LAB, $madeInALab);
    }

    function countCardOfType($playerId, $cardType, $includeMimick = true) {
        return count($this->getCardsOfType($playerId, $cardType, $includeMimick));
    }

    function getCardsOfType($playerId, $cardType, $includeMimick = true) {
        $cards = $this->getCardsFromDb($this->cards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId));

        if ($includeMimick && $cardType != MIMIC_CARD) { // don't search for mimick mimicking itself
            $mimickedCardType = $this->getMimickedCardType();
            if ($mimickedCardType == $cardType) {
                $cards = array_merge($cards, $this->getCardsOfType($playerId, MIMIC_CARD, false)); // mimick
            }
        }

        return $cards;
    }

    function countExtraHead($playerId) {
        return $this->countCardOfType($playerId, 13) + $this->countCardOfType($playerId, 14);
    }

    function getCardCost($playerId, $cardType) {
        $cardCost = $this->CARD_COST[$cardType];

        // alien origin
        $countAlienOrigin = $this->countCardOfType($playerId, ALIEN_ORIGIN_CARD);

        return max($cardCost - $countAlienOrigin, 0);
    }

    function canBuyCard($playerId, $cost) {
        return $cost <= $this->getPlayerEnergy($playerId);
    }

    function applyItHasAChild($playerId) {
        $playerName = $this->getPlayerName($playerId);
        // discard all cards
        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $this->removeCards($playerId, $cards);

        // lose all stars
        $points = 0;
        self::DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
        self::notifyAllPlayers('points','', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'points' => $points,
        ]);

        // get back to 10 heart
        $health = 10;
        self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");
        self::notifyAllPlayers('health', '', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'health' => $health,
        ]);

        self::notifyAllPlayers('applyItHasAChild', clienttranslate('${player_name} reached 0 [Heart]. With ${card_name}, all cards and [Star] are lost but player gets back 10 [Heart]'), [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'health' => $health,
            'card_name' => IT_HAS_A_CHILD_CARD,
        ]);

        if ($this->inTokyo($playerId)) {
            $this->leaveTokyo($playerId);
        }
    }

    function applyBatteryMonster(int $playerId, $card) {
        $energyOnBatteryMonster = $card->tokens - 2;
        $this->setCardTokens($playerId, $card, $energyOnBatteryMonster);

        $this->applyGetEnergyIgnoreCards($playerId, 2, 28);

        if ($energyOnBatteryMonster <= 0 && $card->type != MIMIC_CARD) {
            $this->removeCard($playerId, $card);
        }
    }

    function buyEnergyDrink($diceIds) {
        $this->checkAction('buyEnergyDrink');

        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \Error('Not enough energy');
        }

        $cards = $this->getCardsOfType($playerId, ENERGY_DRINK_CARD);

        if (count($cards) == 0) {
            throw new \Error('No Energy Drink card');
        }

        $this->applyLoseEnergyIgnoreCards($playerId, 1, 0);
        
        $extraRolls = intval(self::getGameStateValue(EXTRA_ROLLS)) + 1;
        self::setGameStateValue(EXTRA_ROLLS, $extraRolls);

        $this->rethrowDice($diceIds);    
    }

    function useSmokeCloud($diceIds) {
        $this->checkAction('useSmokeCloud');

        $playerId = self::getActivePlayerId();

        $cards = $this->getCardsOfType($playerId, SMOKE_CLOUD_CARD);

        if (count($cards) == 0) {
            throw new \Error('No Smoke Cloud card');
        }

        // we choose mimic card first, if available
        $card = null;
        foreach($cards as $icard) {
            if ($icard->type == MIMIC_CARD && $icard->tokens > 0) {
                $card = $icard;
            }
        }
        if ($card == null) {
            $card = $cards[0];
        }

        if ($card->tokens < 1) {
            throw new \Error('Not enough token');
        }

        $tokensOnCard = $card->tokens - 1;
        $this->setCardTokens($playerId, $card, $tokensOnCard);

        if ($tokensOnCard <= 0 && $card->type != MIMIC_CARD) {
            $this->removeCard($playerId, $card);
        }
        
        $extraRolls = intval(self::getGameStateValue(EXTRA_ROLLS)) + 1;
        self::setGameStateValue(EXTRA_ROLLS, $extraRolls);

        $this->rethrowDice($diceIds);
    }

    function useRapidHealing() {
        $playerId = self::getCurrentPlayerId(); // current, not active !

        $this->applyRapidHealing($playerId);
    }

    function applyRapidHealing(int $playerId) {
        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \Error('Not enough energy');
        }

        $health = $this->getPlayerHealth($playerId);

        if ($health <= 0) {
            throw new \Error('You can\'t heal when you\'re dead');
        }

        if ($health >= $this->getPlayerMaxHealth($playerId)) {
            throw new \Error('You can\'t heal when you\'re already at full life');
        }

        if ($this->countCardOfType($playerId, RAPID_HEALING_CARD) == 0) {
            throw new \Error('No Rapid Healing card');
        }

        $this->applyGetHealth($playerId, 1, RAPID_HEALING_CARD);
        $this->applyLoseEnergyIgnoreCards($playerId, 2, 0);
    }

    function removeCard(int $playerId, $card, bool $silent = false, bool $delay = false, bool $ignoreMimicToken = false) {
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $changeMaxHealth = $card->type == EVEN_BIGGER_CARD;
        
        $removeMimickToken = false;
        if ($card->type == MIMIC_CARD) { // Mimic
            $changeMaxHealth = $this->getMimickedCardType() == EVEN_BIGGER_CARD;
            $this->removeMimicToken($playerId);
            $removeMimickToken = true;
        } else if ($card->id == $this->getMimickedCardId() && !$ignoreMimicToken) {
            $this->removeMimicToken($playerId);
            $removeMimickToken = true;
        }

        $this->cards->moveCard($card->id, 'discard');

        if ($removeMimickToken) {
            self::notifyAllPlayers("removeMimicToken", '', [
                'card' => $card,
            ]);
        }

        if (!$silent) {
            self::notifyAllPlayers("removeCards", '', [
                'playerId' => $playerId,
                'cards' => [$card],
                'delay' => $delay,
            ]);
        }
        if ($changeMaxHealth) {
            $this->changeMaxHealth($playerId);
        }        
        
        $this->toggleRapidHealing($playerId, $countRapidHealingBefore);
    }

    function toggleRapidHealing(int $playerId, int $countRapidHealingBefore) {
        $countRapidHealingAfter = $this->countCardOfType($playerId, RAPID_HEALING_CARD);
        
        if ($countRapidHealingBefore != $countRapidHealingAfter) {
            $active = $countRapidHealingAfter > $countRapidHealingBefore;

            $playerEnergy = null;
            if ($active) {
                $playerEnergy = $this->getPlayerEnergy($playerId);
            }            

            self::notifyPlayer($playerId, 'toggleRapidHealing', '', [
                'playerId' => $playerId,
                'active' => $active,
                'playerEnergy' => $playerEnergy,
                'isMaxHealth' => $this->getPlayerHealth($playerId) >= $this->getPlayerMaxHealth($playerId),
            ]);
        }
    }

    function removeCards(int $playerId, array $cards, bool $silent = false) {
        foreach($cards as $card) {
            $this->removeCard($playerId, $card, true);
        }

        if (!$silent && count($cards) > 0) {
            self::notifyAllPlayers("removeCards", '', [
                'playerId' => $playerId,
                'cards' => $cards,
            ]);
        }
    }

    function removeCardByType(int $playerId, int $cardType, bool $silent = false) {
        $card = $this->getCardFromDb(array_values($this->cards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId))[0]);

        $this->removeCard($playerId, $card, $silent);
    }

    function setCardTokens(int $playerId, $card, int $tokens, bool $silent = false) {
        $card->tokens = $tokens;
        self::DbQuery("UPDATE `card` SET `card_type_arg` = $tokens where `card_id` = ".$card->id);

        if (!$silent) {
            if ($card->type == MIMIC_CARD) {
                $card->mimicType = $this->getMimickedCardType();
            }
            self::notifyAllPlayers("setCardTokens", '', [
                'playerId' => $playerId,
                'card' => $card,
            ]);
        }
    }

    function getPlayersWithOpportunist(int $playerId) {
        $orderedPlayers = $this->getOrderedPlayers($playerId);
        $opportunistPlayerIds = [];

        foreach($orderedPlayers as $player) {
            if ($player->id != $playerId) {
                $countOpportunist = $this->countCardOfType($player->id, OPPORTUNIST_CARD);
                if ($countOpportunist > 0) {
                    $opportunistPlayerIds[] = $player->id;
                }   
            }         
        }

        return $opportunistPlayerIds;
    }

    function canChangeMimickedCard() {
        $playerId = self::getActivePlayerId();

        // check if player have mimic card
        if ($this->countCardOfType($playerId, MIMIC_CARD, false) == 0) {
            return false;
        }

        $playersIds = $this->getPlayersIds();
        $mimickedCardId = $this->getMimickedCardId();

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            foreach($cardsOfPlayer as $card) {
                if ($card->type != MIMIC_CARD && $card->type < 100 && $mimickedCardId != $card->id) {
                    return true;
                }
            }
        }
        
        return false;
    }

    function getTokensByCardType(int $cardType) {
        switch($cardType) {
            case BATTERY_MONSTER_CARD: return 6;
            case SMOKE_CLOUD_CARD: return 3;
            default: return 0;
        }
    }

    
    function removeDiscardCards(int $playerId) {
        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $discardCards = array_values(array_filter($cards, function($card) { return $card->type >= 100; }));
        $this->removeCards($playerId, $discardCards);
    }

    function getDamageToCancelToSurvive(int $remainingDamage, int $playerHealth) {
        return $remainingDamage - $playerHealth + 1;
    }

    function showRapidHealingOnDamage(int $playerId, int $remainingDamage) {
        $hasRapidHealing = $this->countCardOfType($playerId, RAPID_HEALING_CARD) > 0;

        $playerHealth = $this->getPlayerHealth($playerId);
        $canUseRapidHealing = $hasRapidHealing && $playerHealth <= $remainingDamage;
        if ($canUseRapidHealing) {
            $damageToCancelToSurvive = $this->getDamageToCancelToSurvive($remainingDamage, $playerHealth);
            if ($this->getPlayerEnergy($playerId) >= (2 * $damageToCancelToSurvive)) {
                return $damageToCancelToSurvive;
            } else {
                return 0;
            }
        }
        return 0;
    }

    function isSureWin(int $playerId) {
        $eliminationWin = $this->getRemainingPlayers() === 1 && $this->getPlayerHealth($playerId) > 0;
        $scoreWin = $this->getPlayerScore($playerId) >= MAX_POINT;

        if (!$eliminationWin && !$scoreWin) {
            return false; // player is not winning
        }

        if ($this->getPlayerHealth($playerId) <= $this->getPlayerPoisonTokens($playerId)) {
            // can't skip, must try to heal poison
            return false;
        }

        if (intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_score >= ".MAX_POINT)) > 1) {
            // can't skip, can try to eliminate other 20 points player to not share tie
            return false;
        }

        return true;
    }

}
