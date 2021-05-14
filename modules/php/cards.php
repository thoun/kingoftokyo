<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player-intervention.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;

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
            if (!in_array($value, $this->KEEP_CARDS_TODO)) { // TODO remove filter       
                $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
            }
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

    function applyEffects($card, $playerId) {
        $type = $card->type;

        switch($type) {
            // KEEP
            case 12: 
                $this->applyGetHealth($playerId, 2, $type); 
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
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyDamage($otherPlayerId, 2, $playerId, $type);
                }
                break;
            case 109: 
                $this->setGameStateValue('playAgainAfterTurn', 1);
                break;
            case 110: 
                $this->applyGetPoints($playerId, 2, $type);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyDamage($otherPlayerId, 3, $playerId, $type);
                }
                break;
            case 111:
                $this->applyGetHealth($playerId, 2, $type);
                break;
            case 112: 
                $playersIds = $this->getPlayersIds();
                foreach ($playersIds as $pId) {
                    $this->applyDamage($pId, 3, $playerId, $type);
                }
                break;
            case 113: 
                $this->applyGetPoints($playerId, 5, $type);
                $this->applyDamage($playerId, 4, $playerId, $type);
                break;
            case 114:
                $this->applyGetPoints($playerId, 2, $type);
                $this->applyDamage($playerId, 2, $playerId, $type);
                break;
            case 115:
                $this->applyGetPoints($playerId, 2, $type);
                $this->applyGetHealth($playerId, 3, $type);
                break;
            case 116:
                $this->applyGetPoints($playerId, 4, $type);
                break;
            case 117:
                $this->applyGetPoints($playerId, 4, $type);
                $this->applyDamage($playerId, 3, $playerId, $type);
                break;
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
                $this->applyDamage($playerId, $count, $playerId, $type);
                
                break;
            case 120:
        }
    }

    function setMimickedCardId(int $cardId) {
        if ($cardId == 0) {
            $card = $this->getMimickedCard();
            if ($card) {
                $this->deleteGlobalVariable('MimickedCard');
                self::notifyAllPlayers("removeMimicToken", '', [
                    'card' => $card,
                ]);
            }
        } else {
            $card = $this->getCardFromDb($this->cards->getCard($cardId));
            $this->setMimickedCard($card);
        }
    }

    function setMimickedCard(object $card) {
        $oldCard = $this->getMimickedCard();
        if ($oldCard) {
            self::notifyAllPlayers("removeMimicToken", '', [
                'card' => $oldCard,
            ]);
        }

        $this->setGlobalVariable('MimickedCard', $card);
        self::notifyAllPlayers("setMimicToken", '', [
            'card' => $card,
        ]);
    }

    function getMimickedCard() {
        return $this->getGlobalVariable('MimickedCard');
    }

    function getMimickedCardId() {
        $card = $this->getGlobalVariable('MimickedCard');
        if ($card != null) {
            return $card->id;
        }
        return null;
    }

    function getMimickedCardType() {
        $card = $this->getGlobalVariable('MimickedCard');
        if ($card != null) {
            return $card->type;
        }
        return null;
    }

    function countCardOfType($playerId, $cardType, $includeMimick = true) {
        return count($this->getCardsOfType($playerId, $cardType, $includeMimick));
    }

    function getCardsOfType($playerId, $cardType, $includeMimick = true) {
        $cards = $this->getCardsFromDb($this->cards->getCardsOfTypeInLocation($cardType, null, 'hand', $playerId));

        if ($includeMimick && $cardType != 27) { // don't search for mimick mimicking itself
            $mimickedCardType = $this->getMimickedCardType();
            if ($mimickedCardType == $cardType) {
                $cards = array_merge($cards, $this->getCardsOfType($playerId, 27, $includeMimick)); // mimick
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

        $cards = $this->getCardsOfType($playerId, 41);

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

    function removeCard(int $playerId, $card, bool $silent = false) {
        $this->cards->moveCard($card->id, 'discard');

        $removeMimickToken = false;
        if ($card->type == 27) { // Mimic
            $this->deleteGlobalVariable('MimickedCard');
            $removeMimickToken = true;
        } else if ($card->id == $this->getMimickedCardId()) {
            $this->setMimickedCardId(0); // 0 means no mimicked card
            $removeMimickToken = true;
        }

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
                $countOpportunist = $this->countCardOfType($player->id, 31);
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
        if ($this->countCardOfType($playerId, 27, false) == 0) {
            return false;
        }

        $playersIds = $this->getPlayersIds();
        $mimickedCardId = $this->getMimickedCardId();

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
            foreach($cardsOfPlayer as $card) {
                if ($card->type != 27 && $card->type < 100 && $mimickedCardId != $card->id) {
                    return true;
                }
            }
        }
        
        return false;
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

        if ($from > 0) {
            $this->removeCard($from, $card, true);
        }
        $this->cards->moveCard($id, 'hand', $playerId);

        $tokens = 0;
        if ($card->type == 28) { $tokens = 6; }
        if ($card->type == 41) { $tokens = 3; }

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
        } else if ($id == self::getGameStateValue('madeInALabCard')) {
            
            self::notifyAllPlayers("buyCard", clienttranslate('${player_name} buy ${card_name} from top deck'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $this->getCardName($card->type),
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId),
            ]);

            self::setGameStateValue('madeInALabCard', 1001); // To not pick another one on same turn
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
            self::setGameStateValue('madeInALabCard', 1001);
        }

        $this->applyEffects($card, $playerId);

        // cards effects may eliminate players
        $endGame = $this->eliminatePlayers($playerId);

        if ($endGame) {
            $this->gamestate->nextState('endGame');
        } else {
            $mimic = $card->type == 27;

            $newCardId = 0;
            if ($newCard != null) {
                $newCardId = $newCard->id;
            }
            self::setGameStateValue('newCardId', $newCardId);

            $this->redirectAfterBuyCard($playerId, $newCardId, $mimic);
        }
    }

    function redirectAfterBuyCard($playerId, $newCardId, $mimic) {
        if ($this->getGlobalVariable('OpportunistIntervention') != null) {
            $opportunistIntervention = $this->getGlobalVariable('OpportunistIntervention');
            $opportunistIntervention->revealedCardsIds = [$newCardId];
            $this->setGlobalVariable('OpportunistIntervention', $opportunistIntervention);

            $this->setInterventionNextState('OpportunistIntervention', 'keep', null, $opportunistIntervention);
            $this->gamestate->nextState($mimic ? 'buyMimicCard' : 'stay');
        } else {
            $playersWithOpportunist = $this->getPlayersWithOpportunist($playerId);

            if (count($playersWithOpportunist) > 0) {
                $opportunistIntervention = new OpportunistIntervention($playersWithOpportunist, [$newCardId]);
                $this->setGlobalVariable('OpportunistIntervention', $opportunistIntervention);
                $this->gamestate->nextState($mimic ? 'buyMimicCard' : 'opportunist');
            } else {
                $this->gamestate->nextState($mimic ? 'buyMimicCard' : 'buyCard');
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
            $this->setGlobalVariable('OpportunistIntervention', $opportunistIntervention);
            $this->gamestate->nextState('opportunist');
        } else {
            $this->gamestate->nextState('renew');
        }
    }

    function opportunistSkip() {
        $playerId = self::getCurrentPlayerId();

        $this->setInterventionNextState('OpportunistIntervention', 'next', 'end');
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
        $playerId = self::getActivePlayerId();

        $this->setMimickedCardId($mimickedCardId);

        $this->redirectAfterBuyCard($playerId, self::getGameStateValue('newCardId'), false);
    }

    function changeMimickedCard(int $mimickedCardId) {
        $this->setMimickedCardId($mimickedCardId);

        $this->gamestate->nextState('next');
    }

    function skipChangeMimickedCard() {
        $this->gamestate->nextState('next');
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
        $canBuyFromPlayers = $this->countCardOfType($playerId, 32) > 0;

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
        $canPick = $this->countCardOfType($playerId, 25) > 0;

        $pickArgs = [];
        if ($canPick && self::getGameStateValue('madeInALabCard') < 1000) {
            $pickCard = $this->getCardFromDb($this->cards->getCardOnTop('deck'));
            self::setGameStateValue('madeInALabCard', $pickCard->id);

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
        $opportunistIntervention = $this->getGlobalVariable('OpportunistIntervention');
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
            $disabledCardsOfPlayer = array_values(array_filter($cardsOfPlayer, function ($card) { return $card->type == 27 && $card->type >= 100; }));
            $disabledIdsOfPlayer = array_map(function ($card) { return $card->id; }, $disabledCardsOfPlayer);
            
            $disabledIds = array_merge($disabledIds, $disabledIdsOfPlayer);
        }

        return [
            'disabledIds' => $disabledIds,
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stBuyCard() {
        $this->deleteGlobalVariable('OpportunistIntervention');
    }

    function stOpportunistBuyCard() {
        $this->stIntervention('OpportunistIntervention');
    }

    function stSellCard() {
        $playerId = self::getActivePlayerId();

        // metamorph
        $countMetamorph = $this->countCardOfType($playerId, 26);

        if ($countMetamorph < 1) { // no needto check remaining cards, if player got metamoph he got cards to sell
            $this->gamestate->nextState('endTurn');
        }
    }
}
