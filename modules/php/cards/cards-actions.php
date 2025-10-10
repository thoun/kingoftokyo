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
}
