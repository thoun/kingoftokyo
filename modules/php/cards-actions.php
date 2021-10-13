<?php

namespace KOT\States;

require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Card;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Damage;
use KOT\Objects\PlayersUsedDice;

trait CardsActionTrait {

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
        $cardLocationArg = $card->location_arg;

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
            // If card bought from player, when having mimic token, keep mimic token ? Considered yes
            $this->removeCard($from, $card, true, false, true);
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
            $newCard = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table', $cardLocationArg));
    
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
            // If card bought from player, we put back mimic token

            self::notifyAllPlayers("setMimicToken", '', [
                'card' => $card,
            ]);
        }

        $damages = $this->applyEffects($card->type, $playerId, $opportunist);

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
                return $mimic ? ST_PLAYER_CHOOSE_MIMICKED_CARD : ST_PLAYER_BUY_CARD;
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
        $cards = $this->placeNewCardsOnTable();

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
                $this->applyDamage($playerId, $remainingDamage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId(), $intervention->damages[0]->giveShrinkRayToken, $intervention->damages[0]->givePoisonSpitToken);
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
        
        $this->applyDamage($playerId, $intervention->damages[0]->damage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId(), $intervention->damages[0]->giveShrinkRayToken, $intervention->damages[0]->givePoisonSpitToken);

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

        $this->applyDamage($playerId, $totalDamage, $intervention->damages[0]->damageDealerId, $intervention->damages[0]->cardType, self::getActivePlayerId(), $intervention->damages[0]->giveShrinkRayToken, $intervention->damages[0]->givePoisonSpitToken);

        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION, 'next', null, $intervention);

        // we check we are still in cancelDamage (we could be redirected if player is eliminated)
        if ($this->gamestate->state()['name'] == 'cancelDamage') {
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

}
