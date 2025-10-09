<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/player-intervention.php');
require_once(__DIR__.'/../Objects/card-being-bought.php');

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\PlayersUsedDice;
use KOT\Objects\CardBeingBought;

use function Bga\Games\KingOfTokyo\debug;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

trait CardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
    
    function actStealCostumeCard(int $id) {
        $playerId = $this->getActivePlayerId();

        $card = $this->powerCards->getItemById($id);
        if (!$card) {
            throw new \BgaUserException('Invalid card id (stealCostumeCard)');
        }

        $from = $card->location_arg;

        if ($card->type < 200 || $card->type > 300) {
            throw new \BgaUserException('Not a Costume card');
        }

        $cost = $this->getCardCost($playerId, $card->type);
        if (!$this->canAffordCard($playerId, $card->type, $cost)) {
            throw new \BgaUserException('Not enough energy');
        }


        $args = $this->argStealCostumeCard();
        if (!$args['canStealCostumes']) {
            throw new \BgaUserException("You can't steal Costume cards");
        }
        if (in_array($id, $args['disabledIds'])) {
            throw new \BgaUserException("You can't steal this card");
        }

        if ($this->getPlayerEnergy($playerId) < $cost) {
            throw new \BgaUserException('Not enough energy');
        }
        $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        $this->removeCard($from, $card, true, false, true);
        $this->powerCards->moveItem($card, 'hand', $playerId);

        $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from ${player_name2} and pays ${player_name2} ${cost} [energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card' => $card,
            'card_name' => $card->type,
            'newCard' => null,
            'energy' => $this->getPlayerEnergy($playerId),
            'from' => $from,
            'player_name2' => $this->getPlayerNameById($from),   
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

        $this->goToState(ST_PLAYER_STEAL_COSTUME_CARD_OR_GIVE_GIFT_EVOLUTION);
    }

    function actEndStealCostume() {
        $playerId = $this->getActivePlayerId();
     
        $this->goToState($this->redirectAfterStealCostume($playerId));
    }

    function applyBuyCard(int $playerId, int $id, ?int $from, $buyCost = null, $useSuperiorAlienTechnology = false, $useBobbingForApples = false) {
        $card = $this->powerCards->getItemById($id);
        $cardLocation = $card->location;
        $cardLocationArg = $card->location_arg;
        $cost = $buyCost === null ? $this->getCardCost($playerId, $card->type) : $buyCost;

        $this->updateKillPlayersScoreAux();        
        
        $this->removeDiscardCards($playerId);

        if ($this->getPlayerEnergy($playerId) < $cost) {
            throw new \BgaUserException('Not enough energy');
        }
        $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $playerId");

        // media friendly
        $countMediaFriendly = $this->countCardOfType($playerId, MEDIA_FRIENDLY_CARD);
        if ($countMediaFriendly > 0) {
            $this->applyGetPoints($playerId, $countMediaFriendly, MEDIA_FRIENDLY_CARD);
        }

        if ($this->wickednessExpansion->isActive()) {
            $this->wickednessTiles->onBuyCard(new Context(
                $this, 
                currentPlayerId: $playerId,
            ));
        }
        // King of the gizmo
        $countKingOfTheGizmo = $this->countEvolutionOfType($playerId, KING_OF_THE_GIZMO_EVOLUTION);
        if ($countKingOfTheGizmo > 0) {
            $this->applyGetPoints($playerId, $countKingOfTheGizmo, 3000 + KING_OF_THE_GIZMO_EVOLUTION);
        }
        
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $mimickedCardId = $this->getMimickedCardId(MIMIC_CARD);
        $mimickedCardIdTile = $this->getMimickedCardId(FLUXLING_WICKEDNESS_TILE);
            
        if ($from > 0) {
            if ($card->location_arg != $from) {
                throw new \BgaUserException("This player doesn't own this card");
            }

            if ($card->type >= 100) { // You can only buy Keep cards with Parasitic Tentacles
                throw new \BgaUserException("Not a Keep card");
            }

            if ($from != $playerId) {
                // If card bought from player, when having mimic token, card keep mimic token
                $this->removeCard($from, $card, true, false, true);
            }
        }
        $this->powerCards->moveItem($card, 'hand', $playerId);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            $this->setCardTokens($playerId, $card, $tokens, true);
        }
        
        // astronaut
        $this->applyAstronaut($playerId);

        $newCard = null;

        if ($cardLocation == 'discard') { // scavenger
            $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from the discard'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId), 
                'cost' => $cost,
            ]);

            if ($card->type >= 100 && $card->type <= 200) {
                $this->removeCard($playerId, $card, false, true);
                
                $this->DbQuery("UPDATE card SET `card_location_arg` = card_location_arg + 1 WHERE `card_location` = 'deck'");
                $this->powerCards->moveItem($card, 'deck', 0);
            }
            
        } else if ($from > 0) {
            $message = $from == $playerId ? /*client TODOPUBG translate(*/'${player_name} buys ${card_name} from reserved cards ${cost} [energy]'/*)*/ : 
            clienttranslate('${player_name} buys ${card_name} from ${player_name2} and pays ${player_name2} ${cost} [energy]');
            $this->notifyAllPlayers("buyCard", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId),
                'from' => $from,
                'player_name2' => $this->getPlayerNameById($from),   
                'cost' => $cost,
            ]);

            $this->applyGetEnergy($from, $cost, 0);
            
        } else if (in_array($id, $this->getMadeInALabCardIds($playerId))) {            
            $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} from top deck for ${cost} [energy]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => null,
                'energy' => $this->getPlayerEnergy($playerId), 
                'cost' => $cost,
                'deckCardsCount' => $this->powerCards->getDeckCount(),
                'topDeckCard' => $this->powerCards->getTopDeckCard(),
            ]);

            $this->setMadeInALabCardIds($playerId, [0]); // To not pick another one on same turn
        } else {
            $numberOfCardsInTable = $this->powerCards->countItemsInLocation('table');

            $newCard = $numberOfCardsInTable < 3 ?
                $this->powerCards->pickCardForLocation('deck', 'table', $cardLocationArg) :
                null;
    
            $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} buys ${card_name} for ${cost} [energy]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card' => $card,
                'card_name' => $card->type,
                'newCard' => $newCard,
                'energy' => $this->getPlayerEnergy($playerId), 
                'cost' => $cost,
                'deckCardsCount' => $this->powerCards->getDeckCount(),
                'topDeckCard' => $this->powerCards->getTopDeckCard(),
            ]);

            if ($useBobbingForApples) {
                $evolution = $this->getFirstUnusedEvolution($playerId, BOBBING_FOR_APPLES_EVOLUTION);
                if ($evolution === null) {
                    throw new \BgaUserException("No unused evolution");
                }
                $this->setUsedCard(3000 + $evolution->id);

                if ($newCard == null) {
                    throw new \BgaUserException("You can't buy with Bobbing for Apples when there is no new card revealed");
                } else {
                    $newCardCost = $this->powerCards->getCardBaseCost($newCard->type);

                    if ($newCardCost % 2 == 0) {
                        $this->notifyAllPlayers("log", /*client TODOPUHA translate*/('The newly revealed card has an even cost, ${player_name} can keep ${card_name}'), [
                            'player_name' => $this->getPlayerNameById($playerId),
                            'card_name' => $card->type,
                        ]);
                    } else {
                        $this->notifyAllPlayers("log500", /*client TODOPUHA translate*/('The newly revealed card has an odd cost, ${player_name} discard ${card_name} and regain [Energy] spent'), [
                            'player_name' => $this->getPlayerNameById($playerId),
                            'card_name' => $card->type,
                        ]);
                        $this->removeCard($playerId, $card);
                        $this->applyGetEnergyIgnoreCards($playerId, $cost, 0);
                    }
                }
            }

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

        $newCardId = 0;
        if ($newCard != null) {
            $newCardId = $newCard->id;
        }
        $this->setGameStateValue('newCardId', $newCardId);

        $redirectAfterBuyCard = $this->redirectAfterBuyCard($playerId, $newCardId);

        $damages = $this->powerCards->applyEffects($card, $playerId, $redirectAfterBuyCard);

        $mimic = false;
        if ($card->type == MIMIC_CARD) {
            $mimic = $this->canChangeMimickedCard($playerId);
        }

        
        if ($useSuperiorAlienTechnology) {
            $this->addSuperiorAlienTechnologyToken($playerId, $id);
        }

        if ($mimic) {
            $this->goToMimicSelection($playerId, MIMIC_CARD);
            return;
        }
        if ($from === $playerId && $this->powerUpExpansion->isActive()) {
            $myToyEvolutions = $this->getEvolutionCardsByLocation('table', $playerId);
            if (count($myToyEvolutions) > 0) {
                $myToyEvolutions = array_values(array_filter($myToyEvolutions, fn($myToyEvolution) => $this->powerCards->countItemsInLocation('reserved'.$playerId, $myToyEvolution->id) === 0));

                if (count($myToyEvolutions) > 0) {
                    $this->myToyQuestion($playerId, $myToyEvolutions[0]);
                    return;
                }
            }
        }

        if ($this->gamestate->getCurrentMainStateId() !== ST_MULTIPLAYER_ANSWER_QUESTION) {
            $this->goToState($redirectAfterBuyCard, $damages);
        }
    }

    function actBuyCard(int $id, int $from, bool $useSuperiorAlienTechnology = false, bool $useBobbingForApples = false) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->powerCards->getItemById($id);
        if (!$card) {
            throw new \BgaUserException('Invalid card id (buyCard)');
        }

        $cost = $this->getCardCost($playerId, $card->type);
        if ($useSuperiorAlienTechnology) {
            $cost = ceil($cost / 2);

            $cardsIds = $this->getSuperiorAlienTechnologyTokens($playerId);
            if (count($cardsIds) >= 3 * $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION)) {
                throw new \BgaUserException('You can only have 3 cards with tokens.');
            }
        }
        if ($useBobbingForApples) {
            $cost = max(0, $cost - 2);
        }

        $unmetConditionRequirement = $this->powerCards->getUnmetConditionRequirement($card, new Context($this, $playerId, $this->inTokyo($playerId)));
        if ($unmetConditionRequirement) {
            throw new \BgaUserException($unmetConditionRequirement->message);
        }
        if (!$this->canAffordCard($playerId, $card->type, $cost)) {
            throw new \BgaUserException('Not enough energy');
        }

        if ($from > 0) {
            if ($from != $playerId) {
                if ($this->countCardOfType($playerId, PARASITIC_TENTACLES_CARD) == 0) {
                    throw new \BgaUserException("You can't buy from other players without Parasitic Tentacles");
                }
            } else if ($from == $playerId) {
                if ($card->location !== 'reserved'.$playerId) {
                    throw new \BgaUserException("You can't buy this card");
                }
            }
        }

        if (!$this->canBuyPowerCard($playerId)) {
            throw new \BgaUserException("You can't buy Power cards");
        }        

        $cardsIds = $this->getSuperiorAlienTechnologyTokens($playerId);
        
        $canPreventBuying = ($playerId == intval($this->getActivePlayerId())) && $this->powerUpExpansion->isActive()
            && count($this->getPlayersIdsWhoCouldPlayEvolutions($this->getOtherPlayersIds($playerId), $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT)) > 0;

        if ($canPreventBuying && (
            $this->getGlobalVariable(CARD_BEING_BOUGHT) == null || $this->getGlobalVariable(CARD_BEING_BOUGHT)->allowed
        )) { // To avoid ask twice in the same turn if it has been played on first
            $this->notifyAllPlayers("log", /*clienttranslate(*/'${player_name} wants to buy ${card_name}'/*)*/, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card' => $card,
                'card_name' => $card->type,
            ]);
            $this->setGlobalVariable(CARD_BEING_BOUGHT, new CardBeingBought($id, $playerId, $from, $cost, $useSuperiorAlienTechnology, $useBobbingForApples));
            $this->jumpToState(ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT);
        } else {
            // applyBuyCard do the redirection
            $this->applyBuyCard($playerId, $id, $from, $cost, $useSuperiorAlienTechnology, $useBobbingForApples);
        }
    }

    function applyPlayCard(int $playerId, PowerCard $card) {
        $this->updateKillPlayersScoreAux();        
        
        $this->removeDiscardCards($playerId);
        
        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $this->powerCards->moveItem($card, 'hand', $playerId);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            $this->setCardTokens($playerId, $card, $tokens, true);
        }
        
        // astronaut
        $this->applyAstronaut($playerId);

        $this->notifyAllPlayers("buyCard", clienttranslate('${player_name} draws ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card' => $card,
            'card_name' => $card->type,
            'deckCardsCount' => $this->powerCards->getDeckCount(),
            'topDeckCard' => $this->powerCards->getTopDeckCard(),
        ]);

        if ($card->type === HIBERNATION_CARD && $this->inTokyo($playerId)) {

            $this->notifyAllPlayers("drawCardHibernationInTokyo", clienttranslate('${player_name} draws ${card_name} while in Tokyo, the card is discarded'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card_name' => $card->type,
            ]);

            $this->removeCard($playerId, $card, false, true);

            return null;
        }
        
        $this->toggleRapidHealing($playerId, $countRapidHealingBefore);

        $damages = $this->powerCards->applyEffects($card, $playerId, -1);

        $this->setGameStateValue('newCardId', 0);

        return $damages;
    }

    function drawCard(int $playerId, $stateAfter = null) {
        $card = $this->powerCards->getTopDeckCard(false);

        $damages = $this->applyPlayCard($playerId, $card);

        $mimic = false;
        if ($card->type == MIMIC_CARD) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $pId) {
                $cardsOfPlayer = $this->powerCards->getPlayer($pId);
                $countAvailableCardsForMimic += Arrays::count($cardsOfPlayer, fn($card) => $card->type != MIMIC_CARD && $card->type < 100);
            }

            $mimic = $countAvailableCardsForMimic > 0;
        }

        if ($mimic) {
            $this->goToMimicSelection($playerId, MIMIC_CARD, $stateAfter);
        } else {
            $this->goToState($stateAfter, $damages);
        }
    }

    function redirectAfterBuyCard(int $playerId, $newCardId) { // return whereToRedirect
        $opportunistIntervention = $this->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        if ($opportunistIntervention) {
            $opportunistIntervention->revealedCardsIds = [$newCardId];
            $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);

            $this->setInterventionNextState(OPPORTUNIST_INTERVENTION, 'keep', null, $opportunistIntervention);
            return ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD;
        } else {
            $playersWithOpportunist = $this->getPlayersWithOpportunist($playerId);

            if (count($playersWithOpportunist) > 0) {
                $opportunistIntervention = new OpportunistIntervention($playersWithOpportunist, [$newCardId]);
                $this->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);
                return ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD;
            } else {
                return ST_PLAYER_BUY_CARD;
            }
        }
    }

    function actRenewPowerCards(?int $cardType) {
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
                $this->applyEvolutionEffects($adaptiveTechnologyCard, $playerId);
                $adaptiveTechnologyCard = $this->getEvolutionCardById($adaptiveTechnologyCard->id);
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

        $this->powerCards->moveAllItemsInLocation('table', 'discard');
        $cards = $this->placeNewCardsOnTable();

        $notifArgs = [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'cards' => $cards,
            'energy' => $this->getPlayerEnergy($playerId),
            'deckCardsCount' => $this->powerCards->getDeckCount(),
            'topDeckCard' => $this->powerCards->getTopDeckCard(),
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

    function actOpportunistSkip() {
        $playerId = $this->getCurrentPlayerId();

        $this->applyOpportunistSkip($playerId);
    }

    function applyOpportunistSkip(int $playerId) {
        $this->removeDiscardCards($playerId);

        $this->setInterventionNextState(OPPORTUNIST_INTERVENTION, 'next', ST_PLAYER_BUY_CARD);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }

    function actGoToSellCard() {
        $playerId = $this->getActivePlayerId();  
           
        $this->removeDiscardCards($playerId);

        $this->gamestate->nextState('goToSellCard');
    }

    
    function actSellCard(int $id) {
        $playerId = $this->getActivePlayerId();
        
        if ($this->countCardOfType($playerId, METAMORPH_CARD) == 0) {
            throw new \BgaUserException("You can't sell cards without Metamorph");
        }

        $card = $this->powerCards->getItemById($id);
        
        if ($card->location != 'hand' || $card->location_arg != $playerId) {
            throw new \BgaUserException("You can't sell cards that you don't own");
        }
        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only sell Keep cards");
        }

        $fullCost = $this->powerCards->getCardBaseCost($card->type);

        $this->removeCard($playerId, $card, true);

        $this->notifyAllPlayers("removeCards", clienttranslate('${player_name} sells ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'cards' => [$card],
            'card_name' =>$card->type,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);

        $this->applyGetEnergy($playerId, $fullCost, 0);

        $this->gamestate->nextState('sellCard');
    }
    
    function actThrowCamouflageDice() {
        $playerId = $this->getCurrentPlayerId();

        $isPowerUpExpansion = $this->powerUpExpansion->isActive();
        $countSoSmall = $isPowerUpExpansion ? $this->countEvolutionOfType($playerId, SO_SMALL_EVOLUTION, true, true) : 0;
        $countCamouflage = $this->countCardOfType($playerId, CAMOUFLAGE_CARD);
        $countTerrorOfTheDeep = $isPowerUpExpansion ? $this->countEvolutionOfType($playerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true) : 0;
        
        if ($countSoSmall + $countCamouflage + $countTerrorOfTheDeep == 0) {
            throw new \BgaUserException('No card to roll dice and cancel damage');
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
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();
        $countSoSmall = $isPowerUpExpansion ? $this->countEvolutionOfType($playerId, SO_SMALL_EVOLUTION, true, true) : 0;
        $countCamouflage = $this->countCardOfType($playerId, CAMOUFLAGE_CARD);
        $countTerrorOfTheDeep = $isPowerUpExpansion ? $this->countEvolutionOfType($playerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true) : 0;
        
        $rolledDice = array_values(array_filter($dice, fn($d) => $d->rolled));
        $diceValues = array_map(fn($die) => $die->value, $dice);
        $rolledDiceValues = array_map(fn($die) => $die->value, $rolledDice);

        $playerUsedDice = property_exists($intervention->playersUsedDice, $playerId) ? $intervention->playersUsedDice->{$playerId} : new PlayersUsedDice($dice, $countSoSmall + $countCamouflage + $countTerrorOfTheDeep);
        
        $cardLogType = CAMOUFLAGE_CARD;
        if ($countSoSmall > 0 && $playerUsedDice->rolls < $countSoSmall) {
            $cardLogType = 3000 + SO_SMALL_EVOLUTION;
        } else if ($countTerrorOfTheDeep > 0 && $playerUsedDice->rolls < $countTerrorOfTheDeep) {
            $cardLogType = 3000 + TERROR_OF_THE_DEEP_EVOLUTION;
        }

        if ($incCamouflageRolls) {
            $playerUsedDice->rolls = $playerUsedDice->rolls + 1;
        } 
        $intervention->playersUsedDice->{$playerId} = $playerUsedDice;

        if ($cardLogType === 3000 + SO_SMALL_EVOLUTION) {
            $soSmallCards = $this->getEvolutionsOfType($playerId, SO_SMALL_EVOLUTION, true, true);

            if ($this->array_every($soSmallCards, fn($soSmallCard) => $soSmallCard->location === 'hand')) {
                $this->playEvolutionToTable($playerId, $soSmallCards[0]);
            }
        }
        if ($cardLogType === 3000 + TERROR_OF_THE_DEEP_EVOLUTION) {
            $terrorOfTheDeepCards = $this->getEvolutionsOfType($playerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true);

            if ($this->array_every($terrorOfTheDeepCards, fn($terrorOfTheDeepCard) => $terrorOfTheDeepCard->location === 'hand')) {
                $this->playEvolutionToTable($playerId, $terrorOfTheDeepCards[0]);
            }
        }

        $cancelledDamage = count(array_values(array_filter($rolledDiceValues, fn($face) => $face === 4))); // heart dices
        if ($cardLogType === 3000 + SO_SMALL_EVOLUTION && $cancelledDamage > 0) {
            $cancelledDamage = count($rolledDice);
        }

        $remainingDamage = $this->createRemainingDamage($playerId, $intervention->damages)->damage - $cancelledDamage;

        $canRethrow3 = false;
        if ($remainingDamage > 0) {
            $canRethrow3 = $this->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0 && in_array(3, $diceValues);
        }

        $this->reduceInterventionDamages($playerId, $intervention, $cancelledDamage);

        $args = $this->argCancelDamage($playerId, $intervention);

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
            $this->notifyAllPlayers("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and can rethrow [dice3]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card_name' => $cardLogType,
                'diceValues' => $dice,
                'cancelDamageArgs' => $args,
                'dice' => $diceStr,
            ]);
        } else {
            $this->notifyAllPlayers("useCamouflage", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and reduce [Heart] loss by ${cancelledDamage}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card_name' => $cardLogType,
                'cancelledDamage' => $cancelledDamage,
                'diceValues' => $dice,
                'cancelDamageArgs' => $args,
                'dice' => $diceStr,
            ]);
        }

        if (!$stayOnState) {
            $damage = $this->createRemainingDamage($playerId, $intervention->damages);
            if ($damage != null) {
                $this->applyDamage($damage);
            } else {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }
        }
        $this->resolveRemainingDamages($intervention, !$stayOnState, false);
    }

    function actUseRapidHealingSync(int $cultistCount, int $rapidHealingCount) {
        $playerId = $this->getCurrentPlayerId();
        $intervention = $this->getDamageIntervention();

        $remainingDamage = 0;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $remainingDamage += $damage->damage;
            }
        }

        for ($i=0; $i<$cultistCount; $i++) {
            if ($this->cthulhuExpansion->getPlayerCultists($playerId) >= 1) {
                $this->cthulhuExpansion->applyUseRapidCultist($playerId, 4);
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
        
        if ($this->applyDamages($intervention, $playerId) === 0) {
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);
        }
        $this->resolveRemainingDamages($intervention, true, false);
    }
    
    function actUseWings() {
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
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => WINGS_CARD,
        ]);

        $intervention = $this->getDamageIntervention();
        $this->reduceInterventionDamages($playerId, $intervention, -1);
        $this->resolveRemainingDamages($intervention, true, false);
    }

    function actSkipWings() {
        $playerId = $this->getCurrentPlayerId();

        $this->applySkipCancelDamage($playerId);
    }

    function applyDamages(object &$intervention, int $playerId) {
        $appliedDamages = 0;
        foreach ($intervention->damages as &$damage) {
            if ($damage->playerId == $playerId) {
                $this->applyDamage($damage);
                $appliedDamages += $damage->effectiveDamage;
            }
        }
        return $appliedDamages;
    }

    function applySkipCancelDamage(int $playerId, $intervention = null, bool $ignoreRedirect = false) {
        if ($intervention === null) {
            $intervention = $this->getDamageIntervention();
        }

        $this->applyDamages($intervention, $playerId);
        $this->resolveRemainingDamages($intervention, true, false);

        // we check we are still in cancelDamage (we could be redirected if player is eliminated)
        if (!$ignoreRedirect && $this->gamestate->state()['name'] == 'cancelDamage') {
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

    function actUseRobot(int $energy) { 
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
        $this->setDamageIntervention($intervention);

        $args = $this->argCancelDamage($playerId, $intervention);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $this->notifyAllPlayers('updateCancelDamage', clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => ROBOT_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            if ($this->applyDamages($intervention, $playerId) === 0) {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }
        }
        
        $this->resolveRemainingDamages($intervention, !$stayOnState, false);
    }

    function actUseElectricArmor(int $energy) {
        $playerId = $this->getCurrentPlayerId();

        $countElectricArmor = $this->countCardOfType($playerId, ELECTRIC_ARMOR_CARD);
        if ($countElectricArmor == 0) {
            throw new \BgaUserException('No Electric Armor card');
        }

        if ($energy > 1) {
            throw new \BgaUserException('You can only save 1 Heart with Electric Armor');
        }

        if ($this->getPlayerEnergy($playerId) < $energy) {
            throw new \BgaUserException('Not enough energy');
        }

        $intervention = $this->getDamageIntervention();
        $intervention->electricArmorUsed = true;

        $remainingDamage = $this->createRemainingDamage($playerId, $intervention->damages)->damage - $energy;

        $this->applyLoseEnergy($playerId, $energy, 0);

        $this->reduceInterventionDamages($playerId, $intervention, $energy);
        $this->setDamageIntervention($intervention);

        $args = $this->argCancelDamage($playerId, $intervention);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canCancelDamage'];
        }

        $this->notifyAllPlayers('updateCancelDamage', clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => ELECTRIC_ARMOR_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            if ($this->applyDamages($intervention, $playerId) === 0) {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }
        }
        
        $this->resolveRemainingDamages($intervention, !$stayOnState, false);
    }

    function actUseSuperJump(int $energy) {  
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

        $args = $this->argCancelDamage($playerId, $intervention);

        // if player also have wings, and some damages aren't cancelled, we stay on state and reduce remaining damages
        $stayOnState = false;
        if ($remainingDamage > 0) {
            $stayOnState = $args['canDoAction'];
        }

        $this->setDamageIntervention($intervention);

        $this->notifyAllPlayers('updateCancelDamage', clienttranslate('${player_name} uses ${card_name}, and reduce [Heart] loss by losing ${energy} [energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => SUPER_JUMP_CARD,
            'energy' => $energy,
            'cancelDamageArgs' => $args,
        ]);

        if (!$stayOnState) {
            if ($this->applyDamages($intervention, $playerId) === 0) {
                $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            }
        }
        $this->resolveRemainingDamages($intervention, !$stayOnState, false);
    }

    function actExchangeCard(int $exchangedCardId) {
        $args = $this->argLeaveTokyoExchangeCard();
        if (in_array($exchangedCardId, $args['disabledIds'])) {
            throw new \BgaUserException("You can't exchange this card");
        }

        $playerId = intval($this->getCurrentPlayerId());
        
        $unstableDnaCards = $this->getCardsOfType($playerId, UNSTABLE_DNA_CARD); // TODODE unstable DNA can be mimicked. create an intervention for this.
        $unstableDnaCards = Arrays::filter($unstableDnaCards, fn($card) => $card->id < 2000); // to remove mimic tile, as you can't exchange a cand with a tile
        $unstableDnaCard = $unstableDnaCards[0];

        $exchangedCard = $this->powerCards->getItemById($exchangedCardId);
        $exchangedCardOwner = $exchangedCard->location_arg;

        if ($exchangedCard->type > 300) {
            throw new \BgaUserException("You cannot exchange this card");
        }

        $countRapidHealingBeforeCurrentPlayer = $this->countCardOfType($playerId, RAPID_HEALING_CARD);
        $countRapidHealingBeforeOtherPlayer = $this->countCardOfType($exchangedCardOwner, RAPID_HEALING_CARD);
        $countEvenBiggerBeforeOtherPlayer = $this->countCardOfType($exchangedCardOwner, EVEN_BIGGER_CARD);

        $this->powerCards->moveItem($unstableDnaCard, 'hand', $exchangedCardOwner);
        $this->powerCards->moveItem($exchangedCard, 'hand', $playerId);

        $this->toggleRapidHealing($playerId, $countRapidHealingBeforeCurrentPlayer);
        $this->toggleRapidHealing($exchangedCardOwner, $countRapidHealingBeforeOtherPlayer);
        if ($countEvenBiggerBeforeOtherPlayer > 0) {
            $this->changeMaxHealth($exchangedCardOwner);
        }

        $this->notifyAllPlayers("exchangeCard", clienttranslate('${player_name} exchange ${card_name} with ${card_name2} taken from ${player_name2}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'previousOwner' => $exchangedCardOwner,
            'player_name2' => $this->getPlayerNameById($exchangedCardOwner),
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

    function actSkipExchangeCard() {
        $playerId = $this->getCurrentPlayerId();

        $this->applySkipExchangeCard($playerId);
    } 
}
