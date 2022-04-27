<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/player-intervention.php');
require_once(__DIR__.'/../objects/card-being-bought.php');

use KOT\Objects\OpportunistIntervention;
use KOT\Objects\PlayersUsedDice;
use KOT\Objects\CardBeingBought;

trait CardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
   
  	
    public function support() {
        $this->checkAction('support');        
        $this->setGameStateValue(CHEERLEADER_SUPPORT, 1);

        $playerId = $this->getCurrentPlayerId();

        $this->notifyAllPlayers("cheerleaderChoice", clienttranslate('${player_name} chooses to support ${player_name2} and adds [diceSmash]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'player_name2' => $this->getPlayerName($this->getActivePlayerId()),
        ]);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'end');
    }

    function applyDontSupport(int $playerId) {
        $this->notifyAllPlayers("cheerleaderChoice", clienttranslate('${player_name} chooses to not support ${player_name2}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'player_name2' => $this->getPlayerName($this->getActivePlayerId()),
        ]);
        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'end');
    }
  	
    public function dontSupport() {
        $this->checkAction('dontSupport');

        $playerId = $this->getCurrentPlayerId();

        $this->applyDontSupport($playerId);
    }
    
    function stealCostumeCard(int $id) {
        $this->checkAction('stealCostumeCard');

        $playerId = $this->getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));
        $from = $card->location_arg;

        if ($card->type < 200 || $card->type > 300) {
            throw new \BgaUserException('Not a Costume card');
        }

        $cost = $this->getCardCost($playerId, $card->type);
        if (!$this->canBuyCard($playerId, $card->type, $cost)) {
            throw new \BgaUserException('Not enough energy');
        }

        $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        $this->removeCard($from, $card, true, false, true);
        $this->cards->moveCard($id, 'hand', $playerId);

        $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from ${player_name2} and pays ${player_name2} ${cost} [energy]'), [
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

        // astronaut
        $this->applyAstronaut($playerId);

        $this->incStat(1, 'costumeStolenCards', $playerId);
     
        // no damage to handle on costume cards

        // if player steal Zombie, it can eliminate the previous owner
        $this->updateKillPlayersScoreAux();
        $this->eliminatePlayers($playerId);

        $this->gamestate->nextState('stealCostumeCard');
    }

    function endStealCostume() {
        $this->checkAction('endStealCostume');

        $playerId = $this->getActivePlayerId();
     
        $this->goToState($this->redirectAfterStealCostume($playerId));
    }

    function applyBuyCard(int $playerId, int $id, int $from, bool $opportunist, $buyCost = null) {
        $card = $this->getCardFromDb($this->cards->getCard($id));
        $cardLocationArg = $card->location_arg;
        $cost = $buyCost === null ? $this->getCardCost($playerId, $card->type) : $buyCost;

        $this->updateKillPlayersScoreAux();        
        
        $this->removeDiscardCards($playerId);

        $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        // media friendly
        $countMediaFriendly = $this->countCardOfType($playerId, MEDIA_FRIENDLY_CARD);
        if ($countMediaFriendly > 0) {
            $this->applyGetPoints($playerId, $countMediaFriendly, MEDIA_FRIENDLY_CARD);
        }
        // have it all!
        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, HAVE_IT_ALL_WICKEDNESS_TILE)) {
            $this->applyGetPoints($playerId, 1, 2000 + HAVE_IT_ALL_WICKEDNESS_TILE);
        }
        // KING_OF_THE_GIZMO_EVOLUTION
        $countKING_OF_THE_GIZMO_EVOLUTION = $this->countEvolutionOfType($playerId, KING_OF_THE_GIZMO_EVOLUTION);
        if ($countKING_OF_THE_GIZMO_EVOLUTION > 0) {
            $this->applyGetPoints($playerId, $countKING_OF_THE_GIZMO_EVOLUTION, 3000 + KING_OF_THE_GIZMO_EVOLUTION);
        }
        
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $mimickedCardId = $this->getMimickedCardId(MIMIC_CARD);
        $mimickedCardIdTile = $this->getMimickedCardId(FLUXLING_WICKEDNESS_TILE);
            
        if ($from > 0) {
            // If card bought from player, when having mimic token, card keep mimic token
            $this->removeCard($from, $card, true, false, true);
        }
        $this->cards->moveCard($id, 'hand', $playerId);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            $this->setCardTokens($playerId, $card, $tokens, true);
        }
        
        // astronaut
        $this->applyAstronaut($playerId);

        $newCard = null;

        if ($from > 0) {
            $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from ${player_name2} and pays ${player_name2} ${cost} [energy]'), [
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
            
        } else if (in_array($id, $this->getMadeInALabCardIds($playerId))) {
            $topDeckCardBackType = $this->getTopDeckCardBackType();
            
            $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from top deck for ${cost} [energy]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId), 
                'cost' => $cost,
                'topDeckCardBackType' => $topDeckCardBackType,
            ]);

            $this->setMadeInALabCardIds($playerId, [0]); // To not pick another one on same turn
        } else {
            $newCard = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table', $cardLocationArg));
            $topDeckCardBackType = $this->getTopDeckCardBackType();
    
            $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} for ${cost} [energy]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => $newCard,
                'energy' => $this->getPlayerEnergy($playerId), 
                'cost' => $cost,
                'topDeckCardBackType' => $topDeckCardBackType,
            ]);

            // if player doesn't pick card revealed by Made in a lab, we set it back to top deck and Made in a lab is ended for this turn
            $this->setMadeInALabCardIds($playerId, [0]);
        }

        if ($card->type < 100) {
            $this->incStat(1, 'keepBoughtCards', $playerId);
        } else if ($card->type < 200) {
            $this->incStat(1, 'discardBoughtCards', $playerId);
        } else if ($card->type < 300) {
            $this->incStat(1, 'costumeBoughtCards', $playerId);
        }
        
        $this->toggleRapidHealing($playerId, $countRapidHealingBefore);

        // If card bought from player, we put back mimic token
        if ($from > 0 && $mimickedCardId == $card->id) {
            $this->notifyAllPlayers("setMimicToken", '', [
                'card' => $card,
                'type' => $this->getMimicStringTypeFromMimicCardType(MIMIC_CARD),
            ]);
        }
        if ($from > 0 && $mimickedCardIdTile == $card->id) {
            $this->notifyAllPlayers("setMimicToken", '', [
                'card' => $card,
                'type' => $this->getMimicStringTypeFromMimicCardType(FLUXLING_WICKEDNESS_TILE),
            ]);
        }

        $damages = $this->applyEffects($card->type, $playerId, $opportunist);

        $mimic = false;
        if ($card->type == MIMIC_CARD) {
            $mimic = $this->canChangeMimickedCard($playerId);
        }

        $newCardId = 0;
        if ($newCard != null) {
            $newCardId = $newCard->id;
        }
        $this->setGameStateValue('newCardId', $newCardId);

        if ($mimic) {
            $this->goToMimicSelection($playerId, MIMIC_CARD);
            return;
        }

        $redirectAfterBuyCard = $this->redirectAfterBuyCard($playerId, $newCardId, $mimic);

        $this->goToState($redirectAfterBuyCard, $damages);
    }

    function buyCard(int $id, int $from, bool $useSuperiorAlienTechnology = false) {
        $this->checkAction('buyCard');

        $stateName = $this->gamestate->state()['name'];
        $opportunist = $stateName === 'opportunistBuyCard';
        $playerId = $opportunist ? $this->getCurrentPlayerId() : $this->getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($id));

        $cost = $this->getCardCost($playerId, $card->type);
        if ($useSuperiorAlienTechnology) {
            $cost = ceil($cost / 2);
        }

        if (!$this->canBuyCard($playerId, $card->type, $cost)) {
            throw new \BgaUserException('Not enough energy');
        }

        if ($from > 0 && $this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) == 0) {
            throw new \BgaUserException("You can't buy from other players without Parasitic Tentacles");
        }

        if (!$this->canBuyPowerCard($playerId)) {
            throw new \BgaUserException("You can't buy Power cards");
        }
        
        $canPreventBuying = !$opportunist && $this->isPowerUpExpansion()
            && $this->canPlayStepEvolution($this->getOtherPlayersIds($playerId), $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT);

        if ($canPreventBuying && $this->getGlobalVariable(CARD_BEING_BOUGHT) == null) { // To avoid ask twice in the same turn if it has been played on first
            $this->notifyAllPlayers("log", /*clienttranslate(*/'${player_name} wants to buy ${card_name}'/*)*/, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card' => $card,
                'card_name' => $card->type,
            ]);
            $this->setGlobalVariable(CARD_BEING_BOUGHT, new CardBeingBought($id, $playerId, $from, $cost, $useSuperiorAlienTechnology));
            $this->jumpToState(ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT);
        } else {
            // applyBuyCard do the redirection
            $this->applyBuyCard($playerId, $id, $from, $opportunist, $cost);
            if ($useSuperiorAlienTechnology) {
                $this->addSuperiorAlienTechnologyToken($playerId, $id);
            }
        }
    }

    function applyPlayCard(int $playerId, object $card) {
        $this->updateKillPlayersScoreAux();        
        
        $this->removeDiscardCards($playerId);
        
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $this->cards->moveCard($card->id, 'hand', $playerId);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            $this->setCardTokens($playerId, $card, $tokens, true);
        }
        
        // astronaut
        $this->applyAstronaut($playerId);

        $topDeckCardBackType = $this->getTopDeckCardBackType();

        $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} draws ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $card,
            'card_name' => $card->type,
            'topDeckCardBackType' => $topDeckCardBackType,
        ]);

        if ($card->type === HIBERNATION_CARD && $this->inTokyo($playerId)) {

            $this->notifyAllPlayers("drawCardHibernationInTokyo", /*client TODODE translate(*/'${player_name} draws ${card_name} while in Tokyo, the card is discarded'/*)*/, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => $card->type,
            ]);

            $this->removeCard($playerId, $card, false, true);

            return null;
        }
        
        $this->toggleRapidHealing($playerId, $countRapidHealingBefore);

        $damages = $this->applyEffects($card->type, $playerId, false);

        $this->setGameStateValue('newCardId', 0);

        return $damages;
    }

    function drawCard(int $playerId, bool $redirectAfter = true) {
        $card = $this->getCardFromDb($this->cards->getCardOnTop('deck'));

        $damages = $this->applyPlayCard($playerId, $card);

        $mimic = false;
        if ($card->type == MIMIC_CARD) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $playerId) {
                $cardsOfPlayer = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
                $countAvailableCardsForMimic += count(array_values(array_filter($cardsOfPlayer, fn($card) => $card->type != MIMIC_CARD && $card->type < 100)));
            }

            $mimic = $countAvailableCardsForMimic > 0;
        }

        if (!$redirectAfter) {
            return false;
        }

        $this->setGameStateValue(STATE_AFTER_MIMIC_CHOOSE, ST_RESOLVE_DICE);        
        $redirectAfterDrawCard = $mimic ? ST_PLAYER_CHOOSE_MIMICKED_CARD : ST_RESOLVE_DICE;

        $this->goToState($redirectAfterDrawCard, $damages);
    }

    function redirectAfterBuyCard(int $playerId, $newCardId, bool $mimic) { // return whereToRedirect
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        if ($opportunistIntervention) {
            $opportunistIntervention->revealedCardsIds = [$newCardId];
            $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);

            $this->setInterventionNextState(OPPORTUNIST_INTERVENTION, 'keep', null, $opportunistIntervention);
            return $mimic ? ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD : ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD;
        } else {
            $playersWithOpportunist = $this->getPlayersWithOpportunist($playerId);
            if ($mimic) {
                $this->setGameStateValue(STATE_AFTER_MIMIC_CHOOSE, 0);  
            }

            if (count($playersWithOpportunist) > 0) {
                $opportunistIntervention = new OpportunistIntervention($playersWithOpportunist, [$newCardId]);
                $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);
                return $mimic ? ST_PLAYER_CHOOSE_MIMICKED_CARD : ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD;
            } else {
                return $mimic ? ST_PLAYER_CHOOSE_MIMICKED_CARD : ST_PLAYER_BUY_CARD;
            }
        }
    }

    function renewCards($cardType) {
        $this->checkAction('renew');

        $playerId = $this->getActivePlayerId();

        if ($cardType == 3024) {
            $adaptiveTechnologyCards = $this->getEvolutionsOfType($playerId, ADAPTING_TECHNOLOGY_EVOLUTION, true, true);

            if (count($adaptiveTechnologyCards) == 0) {
                throw new \BgaUserException('No matching card');
            }

            // we use in priority Icy Reflection
            $adaptiveTechnologyCard = $this->array_find($adaptiveTechnologyCards, fn($card) => $card->type == ICY_REFLECTION_EVOLUTION);
            if ($adaptiveTechnologyCard === null) {
                $adaptiveTechnologyCard = $adaptiveTechnologyCards[0];
            }

            if ($adaptiveTechnologyCard->location === 'hand') {
                $this->applyPlayEvolution($playerId, $adaptiveTechnologyCard);
            }
            $tokens = $adaptiveTechnologyCard->tokens - 1;
            $this->setEvolutionTokens($playerId, $adaptiveTechnologyCard, $tokens);
            if ($tokens == 0) {
                $this->removeEvolution($playerId, $adaptiveTechnologyCard);
            }
        } else {
            if ($this->getPlayerEnergy($playerId) < 2) {
                throw new \BgaUserException('Not enough energy');
            }

            $cost = 2;
            $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");
        }

        $this->removeDiscardCards($playerId);

        $this->cards->moveAllCardsInLocation('table', 'discard');
        $cards = $this->placeNewCardsOnTable();
        
        $topDeckCardBackType = $this->getTopDeckCardBackType();

        $notifArgs = [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'cards' => $cards,
            'energy' => $this->getPlayerEnergy($playerId),
            'topDeckCardBackType' => $topDeckCardBackType,
        ];        
        if ($cardType == 3024) {
            $this->notifyAllPlayers("renewCards", /*client TODOPUtranslate(*/'${player_name} renews visible cards using ${card_name}'/*)*/, 
                array_merge($notifArgs, ['card_name' => 3000 + ADAPTING_TECHNOLOGY_EVOLUTION]));
        } else {
            $this->notifyAllPlayers("renewCards", clienttranslate('${player_name} renews visible cards'), 
                $notifArgs);
        }

        $playersWithOpportunist = $this->getPlayersWithOpportunist($playerId);

        if (count($playersWithOpportunist) > 0) {
            $renewedCardsIds = array_map(fn($card) => $card->id, $cards);
            $opportunistIntervention = new OpportunistIntervention($playersWithOpportunist, $renewedCardsIds);
            $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);
            $this->gamestate->nextState('opportunist');
        } else {
            $this->gamestate->nextState('renew');
        }
    }

    function opportunistSkip() {
        $this->checkAction('opportunistSkip');
   
        $playerId = $this->getCurrentPlayerId();

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
   
        $playerId = $this->getActivePlayerId();  
           
        $this->removeDiscardCards($playerId);

        $this->gamestate->nextState('goToSellCard');
    }

    
    function sellCard(int $id) {
        $this->checkAction('sellCard');
   
        $playerId = $this->getActivePlayerId();
        
        if ($this->countCardOfType($playerId, METAMORPH_CARD) == 0) {
            throw new \BgaUserException("You can't sell cards without Metamorph");
        }

        $card = $this->getCardFromDb($this->cards->getCard($id));
        
        if ($card->location != 'hand' || $card->location_arg != $playerId) {
            throw new \BgaUserException("You can't sell cards that you don't own");
        }
        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only sell Keep cards");
        }

        $fullCost = $this->CARD_COST[$card->type];

        $this->removeCard($playerId, $card, true);

        $this->notifyAllPlayers("removeCards", clienttranslate('${player_name} sells ${card_name}'), [
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

        if (intval($this->gamestate->state_id()) == ST_MULTIPLAYER_ANSWER_QUESTION) { // TODOWI keep only if content
            $playerId = $this->getCurrentPlayerId();

            $card = $this->getCardFromDb($this->cards->getCard($mimickedCardId));
            if ($card->type > 100 || $card->type == MIMIC_CARD) {
                throw new \BgaUserException("You can only mimic Keep cards");
            }

            $this->setMimickedCardId(MIMIC_CARD, $playerId, $mimickedCardId);

            $this->removeStackedStateAndRedirect();
        } else { // TODOWI to remove
            $stateName = $this->gamestate->state()['name'];
            $opportunist = $stateName === 'opportunistChooseMimicCard';
            $playerId = $opportunist ? $this->getCurrentPlayerId() : $this->getActivePlayerId();

            $card = $this->getCardFromDb($this->cards->getCard($mimickedCardId));
            if ($card->type > 100 || $card->type == MIMIC_CARD) {
                throw new \BgaUserException("You can only mimic Keep cards");
            }

            $this->setMimickedCardId(MIMIC_CARD, $playerId, $mimickedCardId);

            $forceStateAfter = intval($this->getGameStateValue(STATE_AFTER_MIMIC_CHOOSE));
            if ($forceStateAfter) {
                $this->jumpToState($forceStateAfter);
            } else {
                $this->jumpToState($this->redirectAfterBuyCard($playerId, $this->getGameStateValue('newCardId'), false));
            }
        }
    }

    function changeMimickedCard(int $mimickedCardId) {
        $this->checkAction('changeMimickedCard');

        $playerId = $this->getActivePlayerId();

        $card = $this->getCardFromDb($this->cards->getCard($mimickedCardId));        
        if ($card->type > 100 || $card->type == MIMIC_CARD) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }
        $this->applyLoseEnergyIgnoreCards($playerId, 1, 0);

        $this->setMimickedCardId(MIMIC_CARD, $playerId, $mimickedCardId);

        $this->jumpToState($this->redirectAfterChangeMimick($playerId));
    }

    function skipChangeMimickedCard($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('skipChangeMimickedCard');
        }

        $playerId = $this->getActivePlayerId();

        $this->jumpToState($this->redirectAfterChangeMimick($playerId));
    }    

    function throwCamouflageDice() {
        $this->checkAction('throwCamouflageDice');

        $playerId = $this->getCurrentPlayerId();

        $countCamouflage = $this->countCardOfType($playerId, CAMOUFLAGE_CARD);
        if ($countCamouflage == 0) {
            throw new \BgaUserException('No Camouflage card');
        }

        $intervention = $this->getDamageIntervention();

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
        $rolledDice = array_values(array_filter($dice, fn($d) => $d->rolled));
        $diceValues = array_map(fn($die) => $die->value, $dice);
        $rolledDiceValues = array_map(fn($die) => $die->value, $rolledDice);

        $cancelledDamage = count(array_values(array_filter($rolledDiceValues, fn($face) => $face === 4))); // heart dices

        $playerUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : new PlayersUsedDice($dice, $countCamouflage);
        if ($incCamouflageRolls) {
            $playerUsedDice->rolls = $playerUsedDice->rolls + 1;
        } 
        $intervention->playersUsedDice->{$playerId} = $playerUsedDice;

        $remainingDamage = $this->createRemainingDamage($playerId, $intervention->damages)->damage - $cancelledDamage;

        $canRethrow3 = false;
        if ($remainingDamage > 0) {
            $canRethrow3 = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0 && in_array(3, $diceValues);
        }

        $this->reduceInterventionDamages($playerId, $intervention, $cancelledDamage);

        $args = $this->argCancelDamage($playerId, $canRethrow3);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $diceStr = '';
        foreach ($diceValues as $dieValue) {
            $diceStr .= $this->getDieFaceLogName($dieValue, 0);
        }

        if ($canRethrow3) {
            $this->setDamageIntervention($intervention);
            $this->notifyAllPlayers("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and can rethrow [dice3]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => CAMOUFLAGE_CARD,
                'diceValues' => $dice,
                'cancelDamageArgs' => $args,
                'dice' => $diceStr,
            ]);
        } else {
            $this->notifyAllPlayers("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and reduce [Heart] loss by ${cancelledDamage}'), [
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
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'next', null, $intervention);

            $damage = $this->createRemainingDamage($playerId, $intervention->damages);
            if ($damage != null) {
                $this->applyDamage($damage->playerId, $damage->damage, $damage->damageDealerId, $damage->cardType, $damage->damageDealerId, $damage->giveShrinkRayToken, $damage->givePoisonSpitToken, $damage->smasherPoints);
            } else {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }

            // we check we are still in cancelDamage (we could be redirected if camouflage roll kills player)
            if ($this->gamestate->state()['name'] == 'cancelDamage') {
                $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
            }
        } else {            
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'stay', null, $intervention);
        }
    }

    function useRapidHealingSync(int $cultistCount, int $rapidHealingCount) {
        $this->checkAction('useRapidHealingSync');

        $playerId = $this->getCurrentPlayerId();
        $intervention = $this->getDamageIntervention();

        $remainingDamage = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $remainingDamage += $damage->damage;
            }
        }

        for ($i=0; $i<$cultistCount; $i++) {
            if ($this->getPlayerCultists($playerId) >= 1) {
                $this->applyUseRapidCultist($playerId, 4);
                $remainingDamage--;
            } else {
                break;
            }
        }
        for ($i=0; $i<$rapidHealingCount; $i++) {
            if ($this->getPlayerEnergy($playerId) >= 2) {
                $this->applyRapidHealing($playerId);
                $remainingDamage--;
            } else {
                break;
            }
        }
        
        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'next', null, $intervention); // must be set before apply damage, in case this player die
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $this->applyDamage($playerId, $damage->damage, $damage->damageDealerId, $damage->cardType, $this->getActivePlayerId(), $damage->giveShrinkRayToken, $damage->givePoisonSpitToken, $damage->smasherPoints);
            }
        }
        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }
    
    function useWings() {
        $this->checkAction('useWings');

        $playerId = $this->getCurrentPlayerId();

        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \BgaUserException('Not enough energy');
        }

        if ($this->countCardOfType($playerId, WINGS_CARD) == 0) {
            throw new \BgaUserException('No Wings card');
        }

        if ($this->canLoseHealth($playerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->applyLoseEnergyIgnoreCards($playerId, 2, 0);
        $this->setInvincible($playerId, USED_WINGS);

        $this->removePlayerFromSmashedPlayersInTokyo($playerId);

        $this->notifyAllPlayers("log", clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => WINGS_CARD,
        ]);

        $intervention = $this->getDamageIntervention();
        $this->reduceInterventionDamages($playerId, $intervention, -1);
        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'next', null, $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function skipWings() {
        $this->checkAction('skipWings');

        $playerId = $this->getCurrentPlayerId();

        $this->applySkipCancelDamage($playerId);
    }

    function applySkipCancelDamage(int $playerId) {

        $intervention = $this->getDamageIntervention();

        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $this->applyDamage($playerId, $damage->damage, $damage->damageDealerId, $damage->cardType, $this->getActivePlayerId(), $damage->giveShrinkRayToken, $damage->givePoisonSpitToken, $damage->smasherPoints);
            }
        }

        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'next', null, $intervention);

        // we check we are still in cancelDamage (we could be redirected if player is eliminated)
        if ($this->gamestate->state()['name'] == 'cancelDamage') {
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

    function useRobot(int $energy) {        
        $this->checkAction('useRobot');

        $playerId = $this->getCurrentPlayerId();

        $countRobot = $this->countCardOfType($playerId, ROBOT_CARD);
        if ($countRobot == 0) {
            throw new \BgaUserException('No Robot card');
        }

        if ($this->getPlayerEnergy($playerId) < $energy) {
            throw new \BgaUserException('Not enough energy');
        }

        $intervention = $this->getDamageIntervention();

        $remainingDamage = $this->createRemainingDamage($playerId, $intervention->damages)->damage - $energy;

        $this->applyLoseEnergy($playerId, $energy, 0);

        $this->reduceInterventionDamages($playerId, $intervention, $energy);

        $args = $this->argCancelDamage($playerId, false);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $this->setDamageIntervention($intervention);

        $this->notifyAllPlayers("useRobot", clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => ROBOT_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'next', null, $intervention);

            $damage = $this->createRemainingDamage($playerId, $intervention->damages);
            if ($damage != null) {
                $this->applyDamage($damage->playerId, $damage->damage, $damage->damageDealerId, $damage->cardType, $damage->damageDealerId, $damage->giveShrinkRayToken, $damage->givePoisonSpitToken, $damage->smasherPoints);
            } else {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }

            // we check we are still in cancelDamage (we could be redirected if camouflage roll kills player)
            if ($this->gamestate->state()['name'] == 'cancelDamage') {
                $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
            }
        } else {            
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'stay', null, $intervention);
        }
    }

    function useSuperJump(int $energy) {        
        $this->checkAction('useSuperJump');

        $playerId = $this->getCurrentPlayerId();

        $superJumpCards = $this->getUnusedCardOfType($playerId, SUPER_JUMP_CARD);
        if (count($superJumpCards) < $energy) {
            throw new \BgaUserException('No unused Super Jump');
        }

        if ($this->getPlayerEnergy($playerId) < $energy) {
            throw new \BgaUserException('Not enough energy');
        }

        $intervention = $this->getDamageIntervention();

        $remainingDamage = $this->createRemainingDamage($playerId, $intervention->damages)->damage - $energy;

        $this->applyLoseEnergy($playerId, $energy, 0);

        for ($i=0;$i<$energy;$i++) {
            $this->setUsedCard($superJumpCards[$i]->id);
        }

        $this->reduceInterventionDamages($playerId, $intervention, $energy);

        $args = $this->argCancelDamage($playerId, false);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $this->setDamageIntervention($intervention);

        $this->notifyAllPlayers("useRobot", clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => SUPER_JUMP_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'next', null, $intervention);

            $damage = $this->createRemainingDamage($playerId, $intervention->damages);
            if ($damage != null) {
                $this->applyDamage($damage->playerId, $damage->damage, $damage->damageDealerId, $damage->cardType, $damage->damageDealerId, $damage->giveShrinkRayToken, $damage->givePoisonSpitToken, $damage->smasherPoints);
            } else {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }

            // we check we are still in cancelDamage (we could be redirected if camouflage roll kills player)
            if ($this->gamestate->state()['name'] == 'cancelDamage') {
                $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
            }
        } else {            
            $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'stay', null, $intervention);
        }
    }

    function exchangeCard(int $exchangedCardId) {
        $this->checkAction('exchangeCard');

        $playerId = intval($this->getCurrentPlayerId());
        
        $unstableDnaCards = $this->getCardsOfType($playerId, UNSTABLE_DNA_CARD); // TODOWI unstable DNA can be mimicked. create an intervention for this.
        $unstableDnaCards = array_values(array_filter($unstableDnaCards, fn($card) => $card->id < 2000)); // to remove mimic tile, as you can't exchange a cand with a tile
        $unstableDnaCard = $unstableDnaCards[0];

        $exchangedCard = $this->getCardFromDb($this->cards->getCard($exchangedCardId));
        $exchangedCardOwner = $exchangedCard->location_arg;

        if ($exchangedCard->type > 300) {
            throw new \BgaUserException("You cannot exchange this card");
        }

        $countRapidHealingBeforeCurrentPlayer = $this->countCardOfType($playerId, RAPID_HEALING_CARD);
        $countRapidHealingBeforeOtherPlayer = $this->countCardOfType($exchangedCardOwner, RAPID_HEALING_CARD);
        $countEvenBiggerBeforeOtherPlayer = $this->countCardOfType($exchangedCardOwner, EVEN_BIGGER_CARD);

        $this->cards->moveCard($unstableDnaCard->id, 'hand', $exchangedCardOwner);
        $this->cards->moveCard($exchangedCard->id, 'hand', $playerId);

        $this->toggleRapidHealing($playerId, $countRapidHealingBeforeCurrentPlayer);
        $this->toggleRapidHealing($exchangedCardOwner, $countRapidHealingBeforeOtherPlayer);
        if ($countEvenBiggerBeforeOtherPlayer > 0) {
            $this->changeMaxHealth($exchangedCardOwner);
        }

        $this->notifyAllPlayers("exchangeCard", /*client TODODE translate(*/'${player_name} exchange ${card_name} with ${card_name2} taken from ${player_name2}'/*)*/, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'previousOwner' => $exchangedCardOwner,
            'player_name2' => $this->getPlayerName($exchangedCardOwner),
            'unstableDnaCard' => $unstableDnaCard,
            'card_name' => UNSTABLE_DNA_CARD,
            'exchangedCard' => $exchangedCard, 
            'card_name2' => $exchangedCard->type,
        ]);

        $this->applySkipExchangeCard($playerId);
    }

    function applySkipExchangeCard(int $playerId) {        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function skipExchangeCard($skipActionCheck = false) {
        if (!$skipActionCheck) {
            $this->checkAction('skipExchangeCard');
        }

        $playerId = $this->getCurrentPlayerId();

        $this->applySkipExchangeCard($playerId);
    } 
}
