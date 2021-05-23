<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Damage;

trait CardsTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    

    function getCardName(int $cardTypeId) {
        switch($cardTypeId) {
            // KEEP
            case 1: return _("Acid Attack");
            case 2: return _("Alien Origin");
            case 3: return _("Alpha Monster");
            case 4: return _("Armor Plating");
            case 5: return _("Background Dweller");
            case 6: return _("Burrowing");
            case 7: return _("Camouflage");
            case 8: return _("Complete Destruction");
            case 9: return _("Media Friendly");
            case 10: return _("Eater of the Dead");
            case 11: return _("Energy Hoarder");
            case 12: return _("Even Bigger");
            case 13: case 14: return _("Extra Head");
            case 15: return _("Fire Breathing");
            case 16: return _("Freeze Time");
            case 17: return _("Friend of Children");
            case 18: return _("Giant Brain");
            case 19: return _("Gourmet");
            case 20: return _("Healing Ray");
            case 21: return _("Herbivore");
            case 22: return _("Herd Culler");
            case 23: return _("It Has a Child");
            case 24: return _("Jets");
            case 25: return _("Made in a Lab");
            case 26: return _("Metamorph");
            case 27: return _("Mimic");
            case 28: return _("Battery Monster");
            case 29: return _("Nova Breath");
            case 30: return _("Detritivore");
            case 31: return _("Opportunist");
            case 32: return _("Parasitic Tentacles");
            case 33: return _("Plot Twist");
            case 34: return _("Poison Quills");
            case 35: return _("Poison Spit");
            case 36: return _("Psychic Probe");
            case 37: return _("Rapid Healing");
            case 38: return _("Regeneration");
            case 39: return _("Rooting for the Underdog");
            case 40: return _("Shrink Ray");
            case 41: return _("Smoke Cloud");
            case 42: return _("Solar Powered");
            case 43: return _("Spiked Tail");
            case 44: return _("Stretchy");
            case 45: return _("Energy Drink");
            case 46: return _("Urbavore");
            case 47: return _("We're Only Making It Stronger");
            case 48: return _("Wings");
            //case 49: return _("Cannibalistic");
            //case 50: return _("Intimidating Roar");
            //case 51: return _("Monster Sidekick");
            //case 52: return _("Reflective Hide");
            //case 53: return _("Sleep Walker");
            //case 54: return _("Super Jump");
            //case 55: return _("Throw a Tanker");
            //case 56: return _("Thunder Stomp");
            //case 57: return _("Unstable DNA");
            // DISCARD
            case 101: return _("Apartment Building");
            case 102: return _("Commuter Train");
            case 103: return _("Corner Store");
            case 104: return _("Death From Above");
            case 105: return _("Energize");
            case 106: case 107: return _("Evacuation Orders");
            case 108: return _("Flame Thrower");
            case 109: return _("Frenzy");
            case 110: return _("Gas Refinery");
            case 111: return _("Heal");
            case 112: return _("High Altitude Bombing");
            case 113: return _("Jet Fighters");
            case 114: return _("National Guard");
            case 115: return _("Nuclear Power Plant");
            case 116: return _("Skyscraper");
            case 117: return _("Tank");
            case 118: return _("Vast Storm");
            //case 119: return _("Amusement Park");
            //case 120: return _("Army");
        }
        return null;
    }

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

    function applyEffects($card, $playerId) { // return $damages
        $type = $card->type;

        switch($type) {
            // KEEP
            case 12: 
                $this->applyGetHealth($playerId, 2, $type);

                $this->notifMaxHealth($playerId);
                break;
            
            // DISCARD
            case 101: 
                $this->applyGetPoints($playerId, 3, $type);
                break;
            case 102:
                $this->applyGetPoints($playerId, 2, $type);
                break;
            case 103:
                $this->applyGetPoints($playerId, 1, $type);
                break;
            case 104: 
                $this->applyGetPoints($playerId, 2, $type);
                if (!$this->inTokyo($playerId)) {
                    // take control of Tokyo
                    if ($this->isTokyoEmpty(false)) {
                        $this->moveToTokyo($playerId, false);
                    } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
                        $this->moveToTokyo($playerId, true);
                    } else {
                        // we force Tokyo city player out
                        // TOCHECK If 5-6 players and both spots used, we enter tokyo city ? Considered Yes
                        $this->leaveTokyo($this->getPlayerIdInTokyoCity());
                        $this->moveToTokyo($playerId, false);
                    }
                }
                break;
            case 105:
                $this->applyGetEnergy($playerId, 9, $type);
                break;
            case 106: case 107:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 5, $type);
                }
                break;
            case 108: 
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 2, $playerId, $type);
                }
                return $damages;
            case 109: 
                $this->setGameStateValue('playAgainAfterTurn', 1);
                break;
            case 110: 
                $this->applyGetPoints($playerId, 2, $type);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 3, $playerId, $type);
                }
                return $damages;
            case 111:
                $this->applyGetHealth($playerId, 2, $type);
                break;
            case 112: 
                $playersIds = $this->getPlayersIds();
                $damages = [];
                foreach ($playersIds as $pId) {
                    $damages[] = new Damage($pId, 3, $playerId, $type);
                }
                return $damages;
            case 113: 
                $this->applyGetPoints($playerId, 5, $type);
                return [new Damage($playerId, 4, $playerId, $type)];
            case 114:
                $this->applyGetPoints($playerId, 2, $type);
                return [new Damage($playerId, 2, $playerId, $type)];
            case 115:
                $this->applyGetPoints($playerId, 2, $type);
                $this->applyGetHealth($playerId, 3, $type);
                break;
            case 116:
                $this->applyGetPoints($playerId, 4, $type);
                break;
            case 117:
                $this->applyGetPoints($playerId, 4, $type);
                return [new Damage($playerId, 3, $playerId, $type)];
            case 118: 
                $this->applyGetPoints($playerId, 2, $type);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $energy = $this->getPlayerEnergy($otherPlayerId);
                    $lostEnergy = floor($energy / 2);
                    $this->applyLoseEnergy($otherPlayerId, $lostEnergy, $type);
                }
                break;
            case 119:
                $count = $this->cards->countCardInLocation('hand', $player_id);
                $this->applyGetPoints($playerId, $count, $type);
                return [new Damage($playerId, $count, $playerId, $type)];
        }
    }

    function setMimickedCardId(int $playerId, int $cardId) {
        if ($cardId == 0) {
            $mimickedCardPlayerId = $this->getMimickedCardPlayerId();
            $countRapidHealingBefore = $this->countCardOfType($mimickedCardPlayerId, RAPID_HEALING_CARD);
            
            $card = $this->getMimickedCard();
            if ($card) {
                $this->deleteGlobalVariable(MIMICKED_CARD);
                self::notifyAllPlayers("removeMimicToken", '', [
                    'card' => $card,
                ]);
            }
        
            $this->toggleRapidHealing($mimickedCardPlayerId, $countRapidHealingBefore);
        } else {
            $card = $this->getCardFromDb($this->cards->getCard($cardId));
            $this->setMimickedCard($playerId, $card);
        }
    }

    function setMimickedCard(int $playerId, object $card) {
        $mimickedCardPlayerId = $this->getMimickedCardPlayerId();
        $countRapidHealingBefore = $this->countCardOfType($mimickedCardPlayerId, RAPID_HEALING_CARD);

        $oldCard = $this->getMimickedCard();
        if ($oldCard) {
            self::notifyAllPlayers("removeMimicToken", '', [
                'card' => $oldCard,
            ]);
        }

        $mimickedCard = new \stdClass();
        $mimickedCard->card = $card;
        $mimickedCard->playerId = $playerId;
        $this->setGlobalVariable(MIMICKED_CARD, $mimickedCard);
        self::notifyAllPlayers("setMimicToken", '', [
            'card' => $card,
        ]);
        
        $this->toggleRapidHealing($mimickedCardPlayerId, $countRapidHealingBefore);
    }

    function getMimickedCardPlayerId() {
        $mimickedCardObj = $this->getGlobalVariable(MIMICKED_CARD);
        if ($mimickedCardObj != null && $mimickedCardObj->playerId != null) {
            return $mimickedCardObj->playerId;
        }
        return 0;
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

    function getMadeInALabCardId($playerId) {
        $madeInALab = $this->getGlobalVariable(MADE_IN_A_LAB, true);
        if (array_key_exists($playerId, $madeInALab)) {
            return $madeInALab[$playerId];
        }
        return 0;
    }

    function setMadeInALabCardId($playerId, $cardId) {
        $madeInALab = $this->getGlobalVariable(MADE_IN_A_LAB, true);
        $madeInALab[$playerId] = $cardId;
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
                $cards = array_merge($cards, $this->getCardsOfType($playerId, MIMIC_CARD, $includeMimick)); // mimick
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
        $countAlienOrigin = $this->countCardOfType($playerId, 2);

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
            'card_name' => $this->getCardName(23),
        ]);

        if ($this->inTokyo($playerId)) {
            $this->leaveTokyo($playerId);
        }
    }

    function applyBatteryMonster(int $playerId, $card) {
        $energyOnBatteryMonster = $card->tokens - 2;
        $this->setCardTokens($playerId, $card, $energyOnBatteryMonster);

        $this->applyGetEnergyIgnoreCards($playerId, 2, 28);

        if ($energyOnBatteryMonster <= 0) {
            $this->removeCard($playerId, $card);
        }
    }

    function buyEnergyDrink() {
        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \Error('Not enough energy');
        }

        $this->applyLoseEnergyIgnoreCards($playerId, 1, 0);
        
        $energyDrinks = intval(self::getGameStateValue('energyDrinks')) + 1;
        self::setGameStateValue('energyDrinks', $energyDrinks);

        $this->gamestate->nextState('buyEnergyDrink');        
    }

    function useSmokeCloud() {
        $playerId = self::getActivePlayerId();

        $cards = $this->getCardsOfType($playerId, SMOKE_CLOUD_CARD);

        if (count($cards) == 0) {
            throw new \Error('No Smoke Cloud card');
        }

        $card = $cards[0];

        if ($card->tokens < 1) {
            throw new \Error('Not enough token');
        }

        $tokensOnCard = $card->tokens - 1;
        $this->setCardTokens($playerId, $card, $tokensOnCard);

        if ($tokensOnCard <= 0) {
            $this->removeCard($playerId, $card);
        }

        $this->gamestate->nextState('useSmokeCloud');        
    }

    function useRapidHealing() {
        $playerId = self::getCurrentPlayerId(); // current, not active !

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

    function removeCard(int $playerId, $card, bool $silent = false) {
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $changeMaxHealth = $card->type == 12;
        
        $removeMimickToken = false;
        if ($card->type == MIMIC_CARD) { // Mimic
            $changeMaxHealth = $this->getMimickedCardType() == 12;
            $this->setMimickedCardId($playerId, 0); // 0 means no mimicked card
            $removeMimickToken = true;
        } else if ($card->id == $this->getMimickedCardId()) {
            $this->setMimickedCardId($playerId, 0); // 0 means no mimicked card
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
            ]);
        }
        if ($changeMaxHealth) {
            $this->notifMaxHealth($playerId);
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

    function canRedirectToCancelDamage(int $playerId) {
        return $this->countCardOfType($playerId, 7) > 0 || 
          ($this->countCardOfType($playerId, WINGS_CARD) > 0 && !$this->isInvincible($playerId));
    }

    function getTokensByCardType($cardType) {
        switch($cardType) {
            case BATTERY_MONSTER_CARD: return 6;
            case SMOKE_CLOUD_CARD: return 3;
            default: return 0;
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function buyCard(int $id, int $from) {
        $stateName = $this->gamestate->state()['name'];
        $opportunist = $stateName === 'opportunistBuyCard';
        $playerId = $opportunist ? self::getCurrentPlayerId() : self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        // TOCHECK can it buy with parasitic tentacles whith reduced price ? Considered Yes
        $cost = $this->getCardCost($playerId, $card->type);
        if (!$this->canBuyCard($playerId, $cost)) {
            throw new \Error('Not enough energy');
        }

        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        // media friendly
        $countMediaFriendly = $this->countCardOfType($playerId, 9);
        if ($countMediaFriendly > 0) {
            $this->applyGetPoints($playerId, $countMediaFriendly, 9);
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
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buy ${card_name} from ${player_name2}'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $this->getCardName($card->type),
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId),
                'from' => $from,
                'player_name2' => $this->getPlayerName($from),
            ]);
        } else if ($id == $this->getMadeInALabCardId($playerId)) {
            
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buy ${card_name} from top deck'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $this->getCardName($card->type),
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId),
            ]);

            $this->setMadeInALabCardId($playerId, 1001); // To not pick another one on same turn
        } else {
            $newCard = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table'));
    
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buy ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $this->getCardName($card->type),
                'newCard' => $newCard,
                'energy' => $this->getPlayerEnergy($playerId),
            ]);

            // if player doesn't pick card revealed by Made in a lab, we set it back to top deck and Made in a lab is ended for this turn
            $this->setMadeInALabCardId($playerId, 1001);
        }
        
        $this->toggleRapidHealing($playerId, $countRapidHealingBefore);

        if ($from > 0 && $mimickedCardId == $card->id) {
            // Is card bought from player, when having mimic token, keep mimic token ? Considered yes
            $this->setMimickedCard($playerId, $card);
        }

        $damages = $this->applyEffects($card, $playerId);

        $mimic = $card->type == MIMIC_CARD;

        $newCardId = 0;
        if ($newCard != null) {
            $newCardId = $newCard->id;
        }
        self::setGameStateValue('newCardId', $newCardId);

        $redirects = false;
        if ($damages != null && count($damages) > 0) {
            $redirectAfterBuyCard = $this->redirectAfterBuyCard($playerId, $newCardId, $mimic);
            $redirects = $this->resolveDamages($damages, $redirectAfterBuyCard); // TODO apply opportunist checks like redirectAfterBuyCard
        }

        if (!$redirects) {
            $this->gamestate->jumpToState($this->redirectAfterBuyCard($playerId, $newCardId, $mimic)); // TODO mimic only if cards available to mimic. same for MPOpportunist transition
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
                return $mimic ? ST_PLAYER_CHOOSE_MIMICKED_CARD : ST_PLAYER_BUY_CARD;
            }
        }
    }

    function renewCards() {
        $playerId = self::getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \Error('Not enough energy');
        }

        $cost = 2;
        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        $this->cards->moveAllCardsInLocation('table', 'discard');
        $cards = $this->getCardsFromDb($this->cards->pickCardsForLocation(3, 'deck', 'table'));

        self::notifyAllPlayers("renewCards", clienttranslate('${player_name} renew visible cards'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
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
        $playerId = self::getCurrentPlayerId();

        $this->setInterventionNextState(OPPORTUNIST_INTERVENTION, 'next', 'end');
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function goToSellCard() {
        $this->gamestate->nextState('goToSellCard');
    }

    
    function sellCard(int $id) {
        $playerId = self::getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        $fullCost = $this->CARD_COST[$card->type];

        $this->removeCard($playerId, $card, true);

        self::notifyAllPlayers("removeCards", clienttranslate('${player_name} sells ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'cards' => [$card],
            'card_name' =>$this->getCardName($card->type),
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        // TOCHECK can metamorph be chained with Friend of children ? Considered Yes
        $this->applyGetEnergy($playerId, $fullCost, 0);

        $this->gamestate->nextState('sellCard');
    }

    function chooseMimickedCard(int $mimickedCardId) {
        $stateName = $this->gamestate->state()['name'];
        $opportunist = $stateName === 'opportunistChooseMimicCard';
        $playerId = $opportunist ? self::getCurrentPlayerId() : self::getActivePlayerId();

        $this->setMimickedCardId($playerId, $mimickedCardId);

        $this->gamestate->jumpToState($this->redirectAfterBuyCard($playerId, self::getGameStateValue('newCardId'), false));
    }

    function changeMimickedCard(int $mimickedCardId) {
        $playerId = self::getActivePlayerId();

        $this->setMimickedCardId($playerId, $mimickedCardId);

        $this->gamestate->nextState('next');
    }

    function skipChangeMimickedCard() {
        $this->gamestate->nextState('next');
    }    

    function throwCamouflageDice() {
        $playerId = self::getCurrentPlayerId();

        if ($this->countCardOfType($playerId, 7) == 0) {
            throw new \Error('No Camouflage card');
        }

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        $dice = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $dice += $damage->damage;
            }
        }

        $diceValues = [];
        for ($i=0; $i<$dice; $i++) {
            $diceValues[] = bga_rand(1, 6);
        }


        $cancelledDamage = count(array_values(array_filter($diceValues, function($face) { return $face === 4; }))); // heart dices

        $intervention->playersUsedDice->{$playerId} = $diceValues;

        $remainingDamage = $dice - $cancelledDamage;

        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention); // we use this to save changes to $intervention

        $args = null;

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = $remainingDamage > 0 && $this->countCardOfType($playerId, WINGS_CARD) > 0;

        if ($stayOnState) {
            $args = $this->argCancelDamage();
        }

        $diceStr = '';
        foreach($diceValues as $dieValue) {
            $diceStr .= $this->getDieFaceLogName($dieValue);
        }

        self::notifyAllPlayers("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and reduce [Heart] loss by ${cancelledDamage}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $this->getCardName(7),
            'cancelledDamage' => $cancelledDamage,
            'diceValues' => $diceValues,
            'cancelDamageArgs' => $args,
            'dice' => $diceStr,
        ]);

        // TOCHECK can a player leaves tokyo even if he cancelled all damage with Camonflage or Wings ? Considered Yes

        if ($stayOnState) {
            $intervention->damages[0]->damage -= $cancelledDamage;
        } else {
            if ($remainingDamage > 0) {
                $this->applyDamage($playerId, $remainingDamage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId());
            }
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }
    
    function useWings() {
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

        self::notifyAllPlayers("useWings", clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $this->getCardName(WINGS_CARD),
        ]);

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);
        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function skipWings() {
        $playerId = self::getCurrentPlayerId();

        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        $totalDamage = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $totalDamage += $damage->damage;
            }
        }

        $this->applyDamage($playerId, $totalDamage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId());

        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
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

        // parasitic tentacles
        $canBuyFromPlayers = $this->countCardOfType($playerId, PARASITIC_TENTACLES) > 0;

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        
        $disabledCards = array_values(array_filter($cards, function ($card) use ($playerId) { return !$this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type)); }));
        $disabledIds = array_map(function ($card) { return $card->id; }, $disabledCards);

        if ($canBuyFromPlayers) {
            $otherPlayersIds = $this->getOtherPlayersIds($playerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $otherPlayerId));
                // TOCHECK can it buy with parasitic tentacles whith reduced price ? Considered Yes
                $disabledCardsOfPlayer = array_values(array_filter($cardsOfPlayer, function ($card) use ($playerId) { return !$this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type)); }));
                $disabledIdsOfPlayer = array_map(function ($card) { return $card->id; }, $disabledCards);
                
                $disabledIds = array_merge($disabledIds, $disabledIdsOfPlayer);
            }
        }

        // made in a lab
        $canPick = $this->countCardOfType($playerId, MADE_IN_A_LAB_CARD) > 0;
        $pickArgs = [];
        if ($canPick && $this->getMadeInALabCardId($playerId) < 1000) {
            $pickCard = $this->getCardFromDb($this->cards->getCardOnTop('deck'));
            $this->setMadeInALabCardId($playerId, $pickCard->id);

            if (!$this->canBuyCard($playerId, $this->getCardCost($playerId, $pickCard->type))) {
                $disabledIds[] = $pickCard->id;
            }

            $pickArgs = [
                '_private' => [          // Using "_private" keyword, all data inside this array will be made private
                    'active' => [       // Using "active" keyword inside "_private", you select active player(s)
                        'pickCard' => $pickCard,   // will be send only to active player(s)
                    ]
                ],
            ];
        }
    
        // return values:
        return [
            'disabledIds' => $disabledIds,
            'canBuyFromPlayers' => $canBuyFromPlayers,
        ] + $pickArgs;
    }

    function argOpportunistBuyCard() {
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        $revealedCardsIds = $opportunistIntervention ? $opportunistIntervention->revealedCardsIds : [];

        $playerId = $opportunistIntervention && count($opportunistIntervention->remainingPlayersId) > 0 ? $opportunistIntervention->remainingPlayersId[0] : null;
        if ($playerId != null) {

            $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
            
            $disabledCards = array_values(array_filter($cards, function ($card) use ($playerId, $revealedCardsIds) { return !in_array($card->id, $revealedCardsIds) || !$this->canBuyCard($playerId, $this->getCardCost($playerId, $card->type)); }));
            $disabledIds = array_map(function ($card) { return $card->id; }, $disabledCards);

            return [
                'disabledIds' => $disabledIds,
            ];
        } else {
            return [];
        }
    }

    function argChooseMimickedCard() {
        $playersIds = $this->getPlayersIds();

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));
        $disabledIds = array_map(function ($card) { return $card->id; }, $cards);

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            $disabledCardsOfPlayer = array_values(array_filter($cardsOfPlayer, function ($card) { return $card->type == MIMIC_CARD && $card->type >= 100; }));
            $disabledIdsOfPlayer = array_map(function ($card) { return $card->id; }, $disabledCardsOfPlayer);
            
            $disabledIds = array_merge($disabledIds, $disabledIdsOfPlayer);
        }

        return [
            'disabledIds' => $disabledIds,
        ];
    }

    function argCancelDamage() {
        $intervention = $this->getGlobalVariable(CANCEL_DAMAGE_INTERVENTION);

        $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;
        if ($playerId != null) {
            $dice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->$playerId : null; //$playerId must stay with $ !

            $canThrowDices = $this->countCardOfType($playerId, 7) > 0 && $dice == null;
            $canUseWings = $this->countCardOfType($playerId, WINGS_CARD) > 0;
            $canSkipWings = $canUseWings && !$canThrowDices;

            $remainingDamage = 0;
            foreach($intervention->damages as $damage) {
                if ($damage->playerId == $playerId) {
                    $remainingDamage += $damage->damage;
                }
            }

            return [
                'canThrowDices' => $canThrowDices,
                'canUseWings' => $canUseWings,
                'canSkipWings' => $canSkipWings,
                'playerEnergy' => $this->getPlayerEnergy($playerId),
                'dice' => $dice,
                'damage' => $remainingDamage,
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

    function stBuyCard() {
        $this->deleteGlobalVariable(OPPORTUNIST_INTERVENTION);
    }

    function stOpportunistBuyCard() {
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

        if ($countMetamorph < 1) { // no needto check remaining cards, if player got metamoph he got cards to sell
            $this->gamestate->nextState('endTurn');
        }
    }

    function stCancelDamage() {
        $this->stIntervention(CANCEL_DAMAGE_INTERVENTION);
    }
}
