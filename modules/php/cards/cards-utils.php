<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/card.php');
require_once(__DIR__.'/../objects/player-intervention.php');
require_once(__DIR__.'/../objects/damage.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Damage;
use KOT\Objects\PlayersUsedDice;

trait CardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function initCards(bool $isAnubisExpansion) {
        $cards = [];

        $gameVersion = $this->isDarkEdition() ? 'dark' : 'base';        
        
        foreach($this->KEEP_CARDS_LIST[$gameVersion] as $value) { // keep  
            $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
        }
        
        foreach($this->DISCARD_CARDS_LIST[$gameVersion] as $value) { // discard
            $type = 100 + $value;
            $cards[] = ['type' => $type, 'type_arg' => 0, 'nbr' => 1];
        }

        $this->cards->createCards($cards, 'deck');

        if ($this->isHalloweenExpansion()) { 
            $cards = [];

            for($value=1; $value<=12; $value++) { // costume
                $type = 200 + $value;
                $cards[] = ['type' => $type, 'type_arg' => 0, 'nbr' => 1];
            }

            $this->cards->createCards($cards, 'costumedeck');
            $this->cards->shuffle('costumedeck'); 
        }

        if ($this->isMutantEvolutionVariant()) {            
            $cards = [ // transformation
                ['type' => 301, 'type_arg' => 0, 'nbr' => 6]
            ];

            $this->cards->createCards($cards, 'mutantdeck');
        }
    }

    function initCurseCards() {
        for($value=1; $value<=24; $value++) { // curse cards
            $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
        }
        $this->curseCards->createCards($cards, 'deck');
        $this->curseCards->shuffle('deck'); 
    }

    function getCardFromDb(array $dbCard) {
        if (!$dbCard || !array_key_exists('id', $dbCard)) {
            throw new \Error('card doesn\'t exists '.json_encode($dbCard));
        }
        if (!$dbCard || !array_key_exists('location', $dbCard)) {
            throw new \Error('location doesn\'t exists '.json_encode($dbCard));
        }
        return new Card($dbCard);
    }

    function getCardsFromDb(array $dbCards) {
        return array_map(function($dbCard) { return $this->getCardFromDb($dbCard); }, array_values($dbCards));
    }

    function applyEffects(int $cardType, int $playerId, bool $opportunist) { // return $damages
        if ($cardType < 100 && !$this->keepAndEvolutionCardsHaveEffect()) {
            return;
        }

        switch($cardType) {
            // KEEP
            case EVEN_BIGGER_CARD: 
                $this->applyGetHealth($playerId, 2, $cardType, $playerId);
                $this->changeMaxHealth($playerId);
                break;
            
            // DISCARD
            case APPARTMENT_BUILDING_CARD: 
                $this->applyGetPoints($playerId, 3, $cardType);
                break;
            case COMMUTER_TRAIN_CARD:
                $this->applyGetPoints($playerId, 2, $cardType);
                break;
            case CORNER_STORE_CARD:
                $this->applyGetPoints($playerId, 1, $cardType);
                break;
            case DEATH_FROM_ABOVE_CARD: 
                $this->applyGetPoints($playerId, 2, $cardType);
                return $this->replacePlayersInTokyo($playerId);
            case ENERGIZE_CARD:
                $this->applyGetEnergy($playerId, 9, $cardType);
                break;
            case EVACUATION_ORDER_1_CARD: case EVACUATION_ORDER_2_CARD:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 5, $cardType);
                }
                break;
            case FLAME_THROWER_CARD: 
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
            case GAS_REFINERY_CARD: 
                $this->applyGetPoints($playerId, 2, $cardType);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 3, $playerId, $cardType);
                }
                return $damages;
            case HEAL_CARD:
                $this->applyGetHealth($playerId, 2, $cardType, $playerId);
                break;
            case HIGH_ALTITUDE_BOMBING_CARD: 
                $playersIds = $this->getPlayersIds();
                $damages = [];
                foreach ($playersIds as $pId) {
                    $damages[] = new Damage($pId, 3, $playerId, $cardType);
                }
                return $damages;
            case JET_FIGHTERS_CARD: 
                $this->applyGetPoints($playerId, 5, $cardType);
                return [new Damage($playerId, 4, $playerId, $cardType)];
            case NATIONAL_GUARD_CARD:
                $this->applyGetPoints($playerId, 2, $cardType);
                return [new Damage($playerId, 2, $playerId, $cardType)];
            case NUCLEAR_POWER_PLANT_CARD:
                $this->applyGetPoints($playerId, 2, $cardType);
                $this->applyGetHealth($playerId, 3, $cardType, $playerId);
                break;
            case SKYSCRAPER_CARD:
                $this->applyGetPoints($playerId, 4, $cardType);
                break;
            case TANK_CARD:
                $this->applyGetPoints($playerId, 4, $cardType);
                return [new Damage($playerId, 3, $playerId, $cardType)];
            case VAST_STORM_CARD: 
                $this->applyGetPoints($playerId, 2, $cardType);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $energy = $this->getPlayerEnergy($otherPlayerId);
                    $lostEnergy = floor($energy / 2);
                    $this->applyLoseEnergy($otherPlayerId, $lostEnergy, $cardType);
                }
                break;
            case MONSTER_PETS_CARD:
                $playersIds = $this->getPlayersIds();
                foreach ($playersIds as $pId) {
                    $this->applyLosePoints($pId, 3, $cardType);
                }
                break;
            /*case 120:
                $count = $this->cards->countCardInLocation('hand', $player_id);
                $this->applyGetPoints($playerId, $count, $cardType);
                return [new Damage($playerId, $count, $playerId, $cardType)];*/
        }
    }

    function removeMimicToken(int $mimicCardType, int $mimicOwnerId) {
        $countRapidHealingBefore = $this->countCardOfType($mimicOwnerId, RAPID_HEALING_CARD);
        
        $card = $this->getMimickedCard($mimicCardType);
        if ($card) {
            $this->deleteGlobalVariable(MIMICKED_CARD.$mimicCardType);
            self::notifyAllPlayers("removeMimicToken", '', [
                'card' => $card,
                'type' => $this->getMimicStringTypeFromMimicCardType($mimicCardType),
            ]);
        }

        $mimicCard = null;
        if ($mimicCardType == MIMIC_CARD) {
            $mimicCard = $this->getCardsFromDb($this->cards->getCardsOfType(MIMIC_CARD))[0]; // TODOWI if tile mimic mimic

            if ($mimicCard && $mimicCard->tokens > 0) {
                $this->setCardTokens($mimicCard->location_arg, $mimicCard, 0);
            }
        } else if ($mimicCardType == FLUXLING_WICKEDNESS_TILE) {
            $mimicCard = $this->getWickednessTilesFromDb($this->wickednessTiles->getCardsOfType(FLUXLING_WICKEDNESS_TILE))[0];

            if ($mimicCard && $mimicCard->tokens > 0) {
                $this->setTileTokens($mimicCard->location_arg, $mimicCard, 0);
            }
        }

        if ($mimicCard && $card && $card->type == EVEN_BIGGER_CARD) {
            $this->changeMaxHealth($mimicCard->location_arg);
        } 
    
        $this->toggleRapidHealing($mimicOwnerId, $countRapidHealingBefore);
    }

    function setMimickedCardId(int $mimicCard, int $mimicOwnerId, int $cardId) {
        $card = $this->getCardFromDb($this->cards->getCard($cardId));
        $this->setMimickedCard($mimicCard, $mimicOwnerId, $card);
    }

    function getMimicStringTypeFromMimicCardType(int $mimicCard) {
        if ($mimicCard == MIMIC_CARD) {
            return 'card';
        } else if ($mimicCard == FLUXLING_WICKEDNESS_TILE) {
            return 'tile';
        }
    }

    function setMimickedCard(int $mimicCardType, int $mimicOwnerId, object $card) {
        $countRapidHealingBefore = $this->countCardOfType($mimicOwnerId, RAPID_HEALING_CARD);

        $this->removeMimicToken($mimicCardType, $mimicOwnerId);

        $mimickedCard = new \stdClass();
        $mimickedCard->card = $card;
        $mimickedCard->playerId = $card->location_arg;
        $this->setGlobalVariable(MIMICKED_CARD . $mimicCardType, $mimickedCard);
        self::notifyAllPlayers("setMimicToken", clienttranslate('${player_name} mimics ${card_name}'), [
            'card' => $card,
            'player_name' => $this->getPlayerName($mimicOwnerId),
            'card_name' => $card->type,
            'type' => $this->getMimicStringTypeFromMimicCardType($mimicCardType),
        ]);

        // no need to check for damage return, no discard card can be mimicked
        $this->applyEffects($card->type, $mimicOwnerId, false);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            if ($mimicCardType === MIMIC_CARD) {
                $mimicCard = $this->getCardsFromDb($this->cards->getCardsOfType(MIMIC_CARD))[0];
                $this->setCardTokens($mimicOwnerId, $mimicCard, $tokens);
            } else if ($mimicCardType === FLUXLING_WICKEDNESS_TILE) {
                $mimicCard = $this->getWickednessTilesFromDb($this->wickednessTiles->getCardsOfType(FLUXLING_WICKEDNESS_TILE))[0];
                $this->setTileTokens($mimicOwnerId, $mimicCard, $tokens);
            }
        }
        
        $this->toggleRapidHealing($mimicOwnerId, $countRapidHealingBefore);
    }

    function getMimickedCard(int $mimicCard) {
        $mimickedCardObj = $this->getGlobalVariable(MIMICKED_CARD . $mimicCard);

        if ($mimickedCardObj != null) {
            return $mimickedCardObj->card;
        }
        return null;
    }

    function getMimickedCardId(int $mimicCard) {
        $mimickedCard = $this->getMimickedCard($mimicCard);
        if ($mimickedCard != null) {
            return $mimickedCard->id;
        }
        return null;
    }

    function getMimickedCardType(int $mimicCard) {
        $mimickedCard = $this->getMimickedCard($mimicCard);
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

     // TODOWI add parameter to count cards even if Gaze of the Sphinx is active, for Have it all
    function getCardsOfType($playerId, $cardType, $includeMimick = true) {
        if ($cardType < 100 && !$this->keepAndEvolutionCardsHaveEffect()) {
            return [];
        }

        $cards = $this->getCardsFromDb($this->cards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId));

        if ($cardType < 100 && $includeMimick && $cardType != MIMIC_CARD) { // don't search for mimick mimicking itself, nor discard/costume cards
            $mimickedCardType = $this->getMimickedCardType(MIMIC_CARD);
            $mimickedCardTypeWickednessTile = $this->getMimickedCardType(FLUXLING_WICKEDNESS_TILE);
            if ($mimickedCardType == $cardType) {
                $cards = array_merge($cards, $this->getCardsOfType($playerId, MIMIC_CARD, false)); // mimick
            }
            if ($mimickedCardTypeWickednessTile == $cardType) {
                $tile = $this->getWickednessTileByType($playerId, FLUXLING_WICKEDNESS_TILE);
                    if ($tile != null) {
                    $tile->id = 2000 + $tile->id; // To avoid id conflict with cards 
                    $cards = array_merge($cards, [$tile]);
                }
            }
        }

        return $cards;
    }

    function getCardCost(int $playerId, int $cardType) {
        $cardCost = $this->CARD_COST[$cardType];

        // alien origin
        $countAlienOrigin = $this->countCardOfType($playerId, ALIEN_ORIGIN_CARD);
        // evil lair
        $countEvilLair = ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, EVIL_LAIR_WICKEDNESS_TILE)) ? 1 : 0;

        // inadequate offering
        $inadequateOffering = $this->isAnubisExpansion() && $this->getCurseCardType() == INADEQUATE_OFFERING_CURSE_CARD ? 2 : 0;

        return max($cardCost + $inadequateOffering - $countAlienOrigin - $countEvilLair, 0);
    }

    function canBuyCard(int $playerId, int $cost) {
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
        if ($card->type == FLUXLING_WICKEDNESS_TILE) {
            $this->setTileTokens($playerId, $card, $energyOnBatteryMonster);
        } else { // mimic or battery monster
            $this->setCardTokens($playerId, $card, $energyOnBatteryMonster);
        }

        $this->applyGetEnergyIgnoreCards($playerId, 2, 28);

        if ($energyOnBatteryMonster <= 0 && $card->type == BATTERY_MONSTER_CARD) {
            $this->removeCard($playerId, $card);
        }
    }

    function buyEnergyDrink($diceIds) {
        $this->checkAction('buyEnergyDrink');

        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        $cardCount = $this->countCardOfType($playerId, ENERGY_DRINK_CARD);

        if ($cardCount == 0) {
            throw new \BgaUserException('No Energy Drink card');
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
            throw new \BgaUserException('No Smoke Cloud card');
        }

        // we choose mimic card first, if available
        $card = null;
        foreach($cards as $icard) {
            if (($icard->type == MIMIC_CARD || $icard->type == FLUXLING_WICKEDNESS_TILE) && $icard->tokens > 0) {
                $card = $icard;
            }
        }
        if ($card == null) {
            $card = $cards[0];
        }

        if ($card->tokens < 1) {
            throw new \BgaUserException('Not enough token');
        }

        $tokensOnCard = $card->tokens - 1;
        $this->setCardTokens($playerId, $card, $tokensOnCard);

        if ($tokensOnCard <= 0 && $card->type != MIMIC_CARD && $card->type != FLUXLING_WICKEDNESS_TILE) {
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
            throw new \BgaUserException('Not enough energy');
        }

        $health = $this->getPlayerHealth($playerId);

        if ($this->getPlayer($playerId)->eliminated) {
            throw new \BgaUserException('You can\'t heal when you\'re dead');
        }

        if ($health >= $this->getPlayerMaxHealth($playerId)) {
            throw new \BgaUserException('You can\'t heal when you\'re already at full life');
        }

        if (!$this->canGainHealth($playerId)) {
            throw new \BgaUserException(/* TODOAN self::_(*/'You cannot gain [Heart]'/*)*/);
        }

        if ($this->countCardOfType($playerId, RAPID_HEALING_CARD) == 0) {
            throw new \BgaUserException('No Rapid Healing card');
        }

        $this->applyGetHealth($playerId, 1, RAPID_HEALING_CARD, $playerId);
        $this->applyLoseEnergyIgnoreCards($playerId, 2, 0, $playerId);
    }

    function removeCard(int $playerId, $card, bool $silent = false, bool $delay = false, bool $ignoreMimicToken = false) {
        if ($card->id >= 2000) {
            // trying to remove mimic tile, but tile isn't removed when mimicked card is removed
            return;
        }

        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $changeMaxHealth = $card->type == EVEN_BIGGER_CARD;
        
        $removeMimickToken = false;
        $mimicCardType = null;
        if ($card->type == MIMIC_CARD) { // Mimic
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
        }

        $this->cards->moveCard($card->id, 'discard');

        if ($removeMimickToken) {
            self::notifyAllPlayers("removeMimicToken", '', [
                'card' => $card,
                'type' => $this->getMimicStringTypeFromMimicCardType($mimicCardType),
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
        // if trying to remove mimic tile, we stop, as tile isn't removed when mimicked card is removed
        $cards = array_values(array_filter($cards, function ($card) { return $card->id < 2000; }));

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
                $card->mimicType = $this->getMimickedCardType(MIMIC_CARD);
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
                if ($countOpportunist > 0 && $this->canBuyPowerCard($player->id)) {
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
        $mimickedCardId = $this->getMimickedCardId(MIMIC_CARD);

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
        $discardCards = array_values(array_filter($cards, function($card) { return ($card->type >= 100 && $card->type < 200); }));
        $this->removeCards($playerId, $discardCards);
    }

    function getDamageToCancelToSurvive(int $remainingDamage, int $playerHealth) {
        return $remainingDamage - $playerHealth + 1;
    }

    function cancellableDamageWithRapidHealing(int $playerId) {
        $hasRapidHealing = $this->countCardOfType($playerId, RAPID_HEALING_CARD) > 0;

        if ($hasRapidHealing) {
            return floor($this->getPlayerEnergy($playerId) / 2);
        }
        return 0;
    }

    function cancellableDamageWithCultists(int $playerId) {
        return $this->getPlayerCultists($playerId);
    }

    function isSureWin(int $playerId) {
        $eliminationWin = $this->getRemainingPlayers() === 1 && !$this->getPlayer($playerId)->eliminated;
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

    function applyEndOfEachMonsterCards() {
        $ghostCards = $this->getCardsFromDb($this->cards->getCardsOfType(GHOST_CARD));
        if (count($ghostCards) > 0) {
            $ghostCard = $ghostCards[0];
            if ($ghostCard->location == 'hand') {
                $playerId = intval($ghostCard->location_arg);

                if ($this->isDamageTakenThisTurn($playerId)) {
                    $this->applyGetHealth($playerId, 1, GHOST_CARD, $playerId);
                }
            }
        }
        
        $vampireCards = $this->getCardsFromDb($this->cards->getCardsOfType(VAMPIRE_CARD));
        if (count($vampireCards) > 0) {
            $vampireCard = $vampireCards[0];
        
            if ($vampireCard->location == 'hand') {
                $playerId = intval($vampireCard->location_arg);

                if ($this->isDamageDealtToOthersThisTurn($playerId)) {
                    $this->applyGetHealth($playerId, 1, VAMPIRE_CARD, $playerId);
                }
            }
        }
    }

    function getTopDeckCardBackType() {
        $topCardsDb = $this->cards->getCardsOnTop(1, 'deck');
        if (count($topCardsDb) > 0) {
            $topCard = $this->getCardsFromDb($topCardsDb)[0];
            return floor($topCard->type / 100) == 2 ? 'costume' : 'base';
        } else {
            return null;
        }
    }

    function willBeWounded(int $playerId, int $activePlayerId) {
        $activePlayerInTokyo = $this->inTokyo($activePlayerId);

        if ($playerId == $activePlayerId) {
            return false; // active player won't smash himself, even if he got nova breath
        }

        if ($this->countCardOfType($activePlayerId, NOVA_BREATH_CARD) == 0 && $this->inTokyo($playerId) == $activePlayerInTokyo) {
            return false; // same location & no Nova card for smashing player
        }

        $dice = $this->getPlayerRolledDice($activePlayerId, true, false, false); 
        $diceCounts = $this->getRolledDiceCounts($activePlayerId, $dice, false);
        $detail = $this->addSmashesFromCards($activePlayerId, $diceCounts, $activePlayerInTokyo);
        $diceCounts[6] += $detail->addedSmashes;

        $minDamage = $this->countCardOfType($playerId, ARMOR_PLATING_CARD) > 0 ? 2 : 1;
        
        return $diceCounts[6] >= $minDamage;
    }

    function applyAstronaut(int $playerId) {
        // Astronaut
        $countAstronaut = $this->countCardOfType($playerId, ASTRONAUT_CARD);
        if ($countAstronaut > 0) {
            $playerScore = $this->getPlayerScore($playerId);
            if ($playerScore >= 17) {
                $this->applyGetPoints($playerId, MAX_POINT - $playerScore, ASTRONAUT_CARD);
            }
        }
    }

    function getFormCard(int $playerId) {
        $playerCards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $formCard = $this->array_find($playerCards, function($card) { return $card->type == 301; });
        return $formCard;
    }

    function redirectAfterStealCostume() {
        if ($this->isMutantEvolutionVariant()) { 
            $this->gamestate->nextState('changeForm');
        } else {
            $this->gamestate->nextState('endStealCostume');
        }
    }

    function setWarningIcon(int $playerId, array &$warningIds, object $card) {
        if (in_array($card->type, [HEAL_CARD])) {
            if (!$this->canGainHealth($playerId) || $this->getPlayerHealth($playerId) >= $this->getPlayerMaxHealth($playerId)) {
                $warningIds[$card->id] = '[Heart]';
            }
        } else if (in_array($card->type, [ENERGIZE_CARD])) {
            if (!$this->canGainEnergy($playerId)) {
                $warningIds[$card->id] = '[Energy]';
            }
        } else if (in_array($card->type, [APPARTMENT_BUILDING_CARD, COMMUTER_TRAIN_CARD, CORNER_STORE_CARD, JET_FIGHTERS_CARD, NATIONAL_GUARD_CARD, NUCLEAR_POWER_PLANT_CARD, SKYSCRAPER_CARD, TANK_CARD])) {
            if (!$this->canGainPoints($playerId)) {
                $warningIds[$card->id] = '[Star]';
            }
        }
    }    
    
}