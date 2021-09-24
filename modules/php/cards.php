<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Damage;
use KOT\Objects\PlayersUsedDice;

trait CardsTrait {

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

    function applyEffects($cardType, $playerId) { // return $damages

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
            case 109: 
                $this->setGameStateValue(FRENZY_EXTRA_TURN, 1);
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
        $this->applyEffects($card->type, $mimicOwnerId);

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

    function removeCard(int $playerId, $card, bool $silent = false, bool $delay = false) {
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $changeMaxHealth = $card->type == EVEN_BIGGER_CARD;
        
        $removeMimickToken = false;
        if ($card->type == MIMIC_CARD) { // Mimic
            $changeMaxHealth = $this->getMimickedCardType() == EVEN_BIGGER_CARD;
            $this->removeMimicToken($playerId);
            $removeMimickToken = true;
        } else if ($card->id == $this->getMimickedCardId()) {
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

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function buyCard(int $id, int $from) {
        $this->checkAction('buyCard');

        $stateName = $this->gamestate->state()['name'];
        $opportunist = $stateName === 'opportunistBuyCard';
        $playerId = $opportunist ? self::getCurrentPlayerId() : self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        $cost = $this->getCardCost($playerId, $card->type);
        if (!$this->canBuyCard($playerId, $cost)) {
            throw new \Error('Not enough energy');
        }

        if ($from > 0 && $this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) == 0) {
            throw new \Error("You can't buy from other players without Parasitic Tentacles");
        }

        $this->updateKillPlayersScoreAux();        
        
        $this->removeDiscardCards($playerId);

        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        // media friendly
        $countMediaFriendly = $this->countCardOfType($playerId, MEDIA_FRIENDLY_CARD);
        if ($countMediaFriendly > 0) {
            $this->applyGetPoints($playerId, $countMediaFriendly, MEDIA_FRIENDLY_CARD);
        }
        
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $mimickedCardId = null;
        if ($from > 0) {
            $mimickedCardId = $this->getMimickedCardId();
            $this->removeCard($from, $card, true);
        }
        $this->cards->moveCard($id, 'hand', $playerId);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            $this->setCardTokens($playerId, $card, $tokens, true);
        }

        $newCard = null;

        if ($from > 0) {
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from ${player_name2} and pays ${player_name2} ${cost} [energy]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId),
                'from' => $from,
                'player_name2' => $this->getPlayerName($from),   
                'cost' => $cost,
            ]);

            $this->applyGetEnergy($from, $cost, 0);
            
        } else if (array_search($id, $this->getMadeInALabCardIds($playerId)) !== false) {
            
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from top deck for ${cost} [energy]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId), 
                'cost' => $cost,
            ]);

            $this->setMadeInALabCardIds($playerId, [0]); // To not pick another one on same turn
        } else {
            $newCard = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table'));
    
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} for ${cost} [energy]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => $newCard,
                'energy' => $this->getPlayerEnergy($playerId), 
                'cost' => $cost,
            ]);

            // if player doesn't pick card revealed by Made in a lab, we set it back to top deck and Made in a lab is ended for this turn
            $this->setMadeInALabCardIds($playerId, [0]);
        }

        if ($card->type < 100) {
            self::incStat(1, 'keepBoughtCards', $playerId);
        } else {
            self::incStat(1, 'discardBoughtCards', $playerId);
        }
        
        $this->toggleRapidHealing($playerId, $countRapidHealingBefore);

        if ($from > 0 && $mimickedCardId == $card->id) {
            // Is card bought from player, when having mimic token, keep mimic token ? Considered yes
            $this->setMimickedCard($playerId, $card);
        }

        $damages = $this->applyEffects($card->type, $playerId);

        $mimic = false;
        if ($card->type == MIMIC_CARD) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $playerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
                $countAvailableCardsForMimic += count(array_values(array_filter($cardsOfPlayer, function ($card) use ($mimickedCardId) { return $card->type != MIMIC_CARD && $card->type < 100; })));
            }

            $mimic = $countAvailableCardsForMimic > 0;
        }

        $newCardId = 0;
        if ($newCard != null) {
            $newCardId = $newCard->id;
        }
        self::setGameStateValue('newCardId', $newCardId);

        $redirects = false;
        $redirectAfterBuyCard = $this->redirectAfterBuyCard($playerId, $newCardId, $mimic);

        if ($damages != null && count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, $redirectAfterBuyCard); // TODO apply opportunist checks like redirectAfterBuyCard
        }

        if (!$redirects) {
            $this->jumpToState($redirectAfterBuyCard, $playerId);
        }
    }

    function redirectAfterBuyCard($playerId, $newCardId, $mimic) { // return whereToRedirect
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        if ($opportunistIntervention) {
            $opportunistIntervention->revealedCardsIds = [$newCardId];
            $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);

            $this->setInterventionNextState(OPPORTUNIST_INTERVENTION, 'keep', null, $opportunistIntervention);
            return $mimic ? ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD : ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD;
        } else {
            $playersWithOpportunist = $this->getPlayersWithOpportunist($playerId);

            if (count($playersWithOpportunist) > 0) {
                $opportunistIntervention = new OpportunistIntervention($playersWithOpportunist, [$newCardId]);
                $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);
                return $mimic ? ST_PLAYER_CHOOSE_MIMICKED_CARD : ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD;
            } else {
                return $mimic ? ST_PLAYER_CHOOSE_MIMICKED_CARD : ST_END_TURN;
            }
        }
    }

    function renewCards() {
        $this->checkAction('renew');

        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \Error('Not enough energy');
        }

        $this->removeDiscardCards($playerId);

        $cost = 2;
        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        $this->cards->moveAllCardsInLocation('table', 'discard');
        $cards = $this->getCardsFromDb($this->cards->pickCardsForLocation(3, 'deck', 'table'));

        self::notifyAllPlayers("renewCards", clienttranslate('${player_name} renews visible cards'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'cards' => $cards,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        $playersWithOpportunist = $this->getPlayersWithOpportunist($playerId);

        if (count($playersWithOpportunist) > 0) {
            $renewedCardsIds = array_map(function($card) { return $card->id; }, $cards);
            $opportunistIntervention = new OpportunistIntervention($playersWithOpportunist, $renewedCardsIds);
            $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);
            $this->gamestate->nextState('opportunist');
        } else {
            $this->gamestate->nextState('renew');
        }
    }

    function opportunistSkip() {
        $this->checkAction('opportunistSkip');
   
        $playerId = self::getCurrentPlayerId();

        $this->applyOpportunistSkip($playerId);
    }

    function applyOpportunistSkip(int $playerId) {
        $this->removeDiscardCards($playerId);

        $this->setInterventionNextState(OPPORTUNIST_INTERVENTION, 'next', 'end');
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function goToSellCard($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('goToSellCard');
        }
   
        $playerId = self::getActivePlayerId();  
           
        $this->removeDiscardCards($playerId);

        $this->gamestate->nextState('goToSellCard');
    }

    
    function sellCard(int $id) {
        $this->checkAction('sellCard');
   
        $playerId = self::getActivePlayerId();
        
        if ($this->countCardOfType($playerId, METAMORPH_CARD) == 0) {
            throw new \Error("You can't sell cards without Metamorph");
        }

        $card = $this->getCardFromDb($this->cards->getCard($id));

        $fullCost = $this->CARD_COST[$card->type];

        $this->removeCard($playerId, $card, true);

        self::notifyAllPlayers("removeCards", clienttranslate('${player_name} sells ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'cards' => [$card],
            'card_name' =>$card->type,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        $this->applyGetEnergy($playerId, $fullCost, 0);

        $this->gamestate->nextState('sellCard');
    }

    function chooseMimickedCard(int $mimickedCardId) {
        $this->checkAction('chooseMimickedCard');

        $stateName = $this->gamestate->state()['name'];
        $opportunist = $stateName === 'opportunistChooseMimicCard';
        $playerId = $opportunist ? self::getCurrentPlayerId() : self::getActivePlayerId();

        $this->setMimickedCardId($playerId, $mimickedCardId);

        $this->jumpToState($this->redirectAfterBuyCard($playerId, self::getGameStateValue('newCardId'), false));
    }

    function changeMimickedCard(int $mimickedCardId) {
        $this->checkAction('changeMimickedCard');

        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \Error('Not enough energy');
        }
        $this->applyLoseEnergyIgnoreCards($playerId, 1, 0);

        $this->setMimickedCardId($playerId, $mimickedCardId);

        // we throw dices now, in case dice count has been changed by mimic
        $this->throwDice($playerId, true);

        $this->gamestate->nextState('next');
    }

    function skipChangeMimickedCard($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('skipChangeMimickedCard');
        }

        $playerId = self::getActivePlayerId();

        // we throw dices now, in case dice count has been changed by mimic
        $this->throwDice($playerId, true);

        $this->gamestate->nextState('next');
    }    

    function throwCamouflageDice() {
        $this->checkAction('throwCamouflageDice');

        $playerId = self::getCurrentPlayerId();

        $countCamouflage = $this->countCardOfType($playerId, CAMOUFLAGE_CARD);
        if ($countCamouflage == 0) {
            throw new \Error('No Camouflage card');
        }

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        $diceNumber = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $diceNumber += $damage->damage;
            }
        }

        $dice = [];
        for ($i=0; $i<$diceNumber; $i++) {
            $face = bga_rand(1, 6);
            $newDie = new \stdClass(); // Dice-like
            $newDie->value = $face;
            $newDie->rolled = true;
            $dice[] = $newDie;
        }

        $this->endThrowCamouflageDice($playerId, $intervention, $dice, true);
    }

    function endThrowCamouflageDice(int $playerId, object $intervention, array $dice, bool $incCamouflageRolls) {
        $countCamouflage = $this->countCardOfType($playerId, CAMOUFLAGE_CARD);
        $diceValues = array_map(function ($die) { return $die->value; }, $dice);

        $cancelledDamage = count(array_values(array_filter($diceValues, function($face) { return $face === 4; }))); // heart dices

        $playerUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : new PlayersUsedDice($dice, $countCamouflage);
        if ($incCamouflageRolls) {
            $playerUsedDice->rolls = $playerUsedDice->rolls + 1;
        } 
        $intervention->playersUsedDice->{$playerId} = $playerUsedDice;

        $remainingDamage = count($dice) - $cancelledDamage;

        $args = null;

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        $canRethrow3 = false;
        if ($remainingDamage > 0) {
            $canRethrow3 = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0 && array_search(3, $diceValues) !== false;
            $stayOnState = $this->countCardOfType($playerId, WINGS_CARD) > 0 || $canRethrow3 ||
                ($playerUsedDice->rolls < $countCamouflage);
        }

        $diceStr = '';
        foreach ($diceValues as $dieValue) {
            $diceStr .= $this->getDieFaceLogName($dieValue);
        }

        $this->setGlobalVariable(CANCEL_DAMAGE_INTERVENTION, $intervention);

        $args = $this->argCancelDamage($playerId, $canRethrow3 ? (array_search(3, $diceValues) !== false) : false);

        if ($canRethrow3) {
            $this->setGlobalVariable(CANCEL_DAMAGE_INTERVENTION, $intervention);
            self::notifyAllPlayers("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and can rethrow [dice3]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => CAMOUFLAGE_CARD,
                'diceValues' => $dice,
                'cancelDamageArgs' => $args,
                'dice' => $diceStr,
            ]);
        } else {
            if ($stayOnState) {
                $intervention->damages[0]->damage -= $cancelledDamage;
                $this->setGlobalVariable(CANCEL_DAMAGE_INTERVENTION, $intervention);
            }

            self::notifyAllPlayers("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and reduce [Heart] loss by ${cancelledDamage}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => CAMOUFLAGE_CARD,
                'cancelledDamage' => $cancelledDamage,
                'diceValues' => $dice,
                'cancelDamageArgs' => $args,
                'dice' => $diceStr,
            ]);
        }

        if (!$stayOnState) {
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);

            if ($remainingDamage > 0) {
                $this->applyDamage($playerId, $remainingDamage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId());
            } else {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }

            // we check we are still in cancelDamage (we could be redirected if camouflage roll kills player)
            if ($this->gamestate->state()['name'] == 'cancelDamage') {
                $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
            }
        } else {            
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'stay', null, $intervention);
        }
    }

    function useRapidHealingSync() {
        $this->checkAction('useRapidHealingSync');

        $playerId = self::getCurrentPlayerId();
        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        $remainingDamage = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $remainingDamage += $damage->damage;
            }
        }
    
        $playerHealth = $this->getPlayerHealth($playerId);
        $damageToCancelToSurvive = $this->getDamageToCancelToSurvive($remainingDamage, $playerHealth);

        for ($i=0; $i<$damageToCancelToSurvive; $i++) {
            if ($this->getPlayerEnergy($playerId) >= 2) {
                $this->applyRapidHealing($playerId);
                $remainingDamage--;
            } else {
                break;
            }
        }
        
        $this->applyDamage($playerId, $intervention->damages[0]->damage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId());

        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }
    
    function useWings() {
        $this->checkAction('useWings');

        $playerId = self::getCurrentPlayerId();

        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \Error('Not enough energy');
        }

        if ($this->countCardOfType($playerId, WINGS_CARD) == 0) {
            throw new \Error('No Wings card');
        }

        if ($this->isInvincible($playerId)) {
            throw new \Error('You already used Wings in this turn');
        }

        $this->applyLoseEnergyIgnoreCards($playerId, 2, 0);
        $this->setInvincible($playerId);

        $this->removePlayerFromSmashedPlayersInTokyo($playerId);

        self::notifyAllPlayers("useWings", clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => WINGS_CARD,
        ]);

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);
        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function skipWings() {
        $this->checkAction('skipWings');

        $playerId = self::getCurrentPlayerId();

        $this->applySkipWings($playerId);
    }

    function applySkipWings(int $playerId) {

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        $totalDamage = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $totalDamage += $damage->damage;
            }
        }

        $this->applyDamage($playerId, $totalDamage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId());

        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);

        // we check we are still in cancelDamage (we could be redirected if player is eliminated)
        if ($this->gamestate->state()['name'] == 'cancelDamage') {
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */
    function argBuyCard() {
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

        $canBuyOrNenew = $playerEnergy >= 2;
        $canSell = $this->countCardOfType($playerId, METAMORPH_CARD) > 0;

        // parasitic tentacles
        $canBuyFromPlayers = $this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) > 0;

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        
        $disabledIds = [];
        foreach ($cards as $card) {
            if ($this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                $canBuyOrNenew = true;
            } else {
                $disabledIds[] = $card->id;
            }
        }

        if ($canBuyFromPlayers) {
            $otherPlayersIds = $this->getOtherPlayersIds($playerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));

                foreach ($cardsOfPlayer as $card) {
                    if ($this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                        $canBuyOrNenew = true;
                    } else {
                        $disabledIds[] = $card->id;
                    }
                }
            }
        }

        // made in a lab
        $canPick = $this->countCardOfType($playerId, MADE_IN_A_LAB_CARD);
        $pickArgs = [];
        if ($canPick > 0) {
            $madeInALabCardIds = $this->getMadeInALabCardIds($playerId);
            $pickCards = $this->getCardsFromDb($this->cards->getCardsOnTop($canPick, 'deck'));
            $this->setMadeInALabCardIds($playerId, array_map(function($card) { return $card->id; }, $pickCards));

            foreach ($pickCards as $card) {
                if ($this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                    $canBuyOrNenew = true;
                } else {
                    $disabledIds[] = $card->id;
                }
            }

            $pickArgs = [
                '_private' => [          // Using "_private" keyword, all data inside this array will be made private
                    'active' => [       // Using "active" keyword inside "_private", you select active player(s)
                        'pickCards' => $pickCards,   // will be send only to active player(s)
                    ]
                ],
            ];
        }
    
        // return values:
        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
            'canBuyOrNenew' => $canBuyOrNenew || $canPick, // if a player can see 1st deck card, we don't skip his turn or add a timer
            'canSell' => $canSell,
        ] + $pickArgs;
    }

    function argOpportunistBuyCardWithPlayerId(int $playerId) {        
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        $revealedCardsIds = $opportunistIntervention ? $opportunistIntervention->revealedCardsIds : [];

        $canBuy = false;

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));

        $disabledIds = [];
        foreach ($cards as $card) {
            if (in_array($card->id, $revealedCardsIds) && $this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type))) {
                $canBuy = true;
            } else {
                $disabledIds[] = $card->id;
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuy' => $canBuy,
        ];
    }

    function argOpportunistBuyCard() {
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);

        $playerId = $opportunistIntervention && count($opportunistIntervention->remainingPlayersId) > 0 ? $opportunistIntervention->remainingPlayersId[0] : null;
        if ($playerId != null) {
            return $this->argOpportunistBuyCardWithPlayerId($playerId);
        } else {
            return [
                'canBuy' => false,
            ];
        }
    }

    function argChangeMimickedCard() {
        return $this->argChooseMimickedCard(true);
    }

    function argChooseMimickedCard($limitToOneEnergy = false) {
        $playerId = self::getActivePlayerId();
        $playerEnergy = $this->getPlayerEnergy($playerId);

        $canChange = $playerEnergy >= 1;

        $playersIds = $this->getPlayersIds();

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(function ($card) { return $card->id; }, $cards);
        $mimickedCardId = $this->getMimickedCardId();

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            $disabledCardsOfPlayer = $canChange || !$limitToOneEnergy ? 
                array_values(array_filter($cardsOfPlayer, function ($card) use ($mimickedCardId) { return $card->type == MIMIC_CARD || $card->id == $mimickedCardId || $card->type >= 100; })) :
                $cardsOfPlayer;
            $disabledIdsOfPlayer = array_map(function ($card) { return $card->id; }, $disabledCardsOfPlayer);
            
            $disabledIds = array_merge($disabledIds, $disabledIdsOfPlayer);
        }

        return [
            'disabledIds' => $disabledIds,
            'canChange' => $canChange,
        ];
    }

    function argCancelDamage($playerId = null, $hasDice3 = false) {
        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        if ($playerId == null) {
            $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;
        }
        
        if ($playerId != null) {
            $playersUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : null;
            $dice = $playersUsedDice != null ? $playersUsedDice->dice : null;

            $canThrowDices = $this->countCardOfType($playerId, CAMOUFLAGE_CARD) > 0 && ($playersUsedDice == null || $playersUsedDice->rolls < $playersUsedDice->maxRolls);
            $canUseWings = $this->countCardOfType($playerId, WINGS_CARD) > 0;

            $remainingDamage = 0;
            foreach($intervention->damages as $damage) {
                if ($damage->playerId == $playerId) {
                    $remainingDamage += $damage->damage;
                }
            }

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;

            $rapidHealingHearts = $this->showRapidHealingOnDamage($playerId, $remainingDamage);

            return [
                'canThrowDices' => $canThrowDices,
                'canUseWings' => $canUseWings,
                'rapidHealingHearts' => $rapidHealingHearts,
                'playerEnergy' => $this->getPlayerEnergy($playerId),
                'dice' => $dice,
                'damage' => $remainingDamage,
                'rethrow3' => [
                    'hasCard' => $hasBackgroundDweller,
                    'hasDice3' => $hasDice3,
                ],
            ];
        } else {
            return [
                'damage' => '',
            ];
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stChooseMimickedCard() {
        if ($this->autoSkipImpossibleActions() && !$this->argChooseMimickedCard(true)['canChange']) {
            // skip state
            $this->skipChangeMimickedCard(true);
        }
    }

    function stBuyCard() {
        $this->deleteGlobalVariable(OPPORTUNIST_INTERVENTION);

        $playerId = intval(self::getActivePlayerId()); 

        // if player is dead async, he can't buy or sell
        if ($this->getPlayerHealth($playerId) === 0) {
            $this->endTurn(true);
            return;
        }

        $args = $this->argBuyCard();
        if (($this->autoSkipImpossibleActions() && !$args['canBuyOrNenew']) || $this->isSureWin($playerId)) {
            // skip state
            if ($args['canSell']) {
                $this->goToSellCard(true);
            } else {
                $this->endTurn(true);
            }
        }
    }

    function stOpportunistBuyCard() {
        if ($this->autoSkipImpossibleActions()) { // in turn based, we remove players when they can't buy anything
            $intervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
            $remainingPlayersId = [];
            foreach($intervention->remainingPlayersId as $playerId) {
                if ($this->argOpportunistBuyCardWithPlayerId($playerId)['canBuy']) {
                    $remainingPlayersId[] = $playerId;
                } else {
                    $this->removeDiscardCards($playerId);
                }
            }
            $intervention->remainingPlayersId = $remainingPlayersId;
            $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $intervention);
        }

        $this->stIntervention(OPPORTUNIST_INTERVENTION);
    }

    function stOpportunistChooseMimicCard() {
        $intervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);

        $this->gamestate->setPlayersMultiactive([$intervention->remainingPlayersId[0]], 'stay', true);
    }

    function stSellCard() {
        $playerId = self::getActivePlayerId();

        // metamorph
        $countMetamorph = $this->countCardOfType($playerId, METAMORPH_CARD);

        if ($countMetamorph < 1) { // no need to check remaining cards, if player got metamoph he got cards to sell
            $this->gamestate->nextState('endTurn');
        }
    }

    function stCancelDamage() {
        $this->stIntervention(CANCEL_DAMAGE_INTERVENTION);

        if ($this->autoSkipImpossibleActions()) {
            
            $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);
            
            $playerId = null;
            if ($intervention != null && $intervention->remainingPlayersId != null && count($intervention->remainingPlayersId) > 0) {
                $playerId = $intervention->remainingPlayersId[0];
            } else {
                return;
            }

            $playersUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : null;
            $dice = $playersUsedDice != null ? $playersUsedDice->dice : null;

            $hasBackgroundDweller = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0; // Background Dweller

            $hasDice3 = $hasBackgroundDweller && $dice != null ? (array_search(3, $diceValues) !== false) : false;

            $arg = $this->argCancelDamage($playerId, $hasDice3);

            if (!$arg['canThrowDices'] && $arg['playerEnergy'] < 2 && !$arg['rethrow3']['hasDice3']) {
                $this->applySkipWings($playerId);
            }
        }
    }
}
