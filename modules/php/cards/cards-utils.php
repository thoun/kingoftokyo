<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/card.php');
require_once(__DIR__.'/../Objects/evolution-card.php');
require_once(__DIR__.'/../Objects/damage.php');
require_once(__DIR__.'/../Objects/question.php');
require_once(__DIR__.'/../Objects/log.php');

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Card;
use KOT\Objects\EvolutionCard;
use KOT\Objects\Damage;
use KOT\Objects\Question;
use KOT\Objects\LoseHealthLog;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

trait CardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getCardBaseCost(int $cardType) {
        $cost = $this->CARD_COST[$cardType];

        // new costs for the Dark Edition
        if (($cardType ===  16 || $cardType ===  19) && $this->isDarkEdition()) {
            return 6;
        }
        if ($cardType ===  22 && $this->isDarkEdition()) {
            return 5;
        }
        if ($cardType ===  42 && $this->isDarkEdition()) {
            return 3;
        }

        return $cost;
    }

    function initCards(bool $isOrigins, bool $isDarkEdition) {
        $cards = [];

        $gameVersion = $isOrigins ? 'origins' : ($isDarkEdition ? 'dark' : 'base');
        
        foreach($this->KEEP_CARDS_LIST[$gameVersion] as $value) { // keep  
            $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
        }
        
        foreach($this->DISCARD_CARDS_LIST[$gameVersion] as $value) { // discard
            $type = ($isOrigins ? 0 : 100) + $value;
            $cards[] = ['type' => $type, 'type_arg' => 0, 'nbr' => 1];
        }

        if (!$isOrigins && !$isDarkEdition && intval($this->getGameStateValue(ORIGINS_EXCLUSIVE_CARDS_OPTION)) == 2) {        
            foreach($this->ORIGINS_CARDS_EXCLUSIVE_KEEP_CARDS_LIST as $value) { // keep  
                $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
            }            
            foreach($this->ORIGINS_CARDS_EXCLUSIVE_DISCARD_CARDS_LIST as $value) { // discard
                $cards[] = ['type' => $value, 'type_arg' => 0, 'nbr' => 1];
            }
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
        return array_map(fn($dbCard) => $this->getCardFromDb($dbCard), array_values($dbCards));
    }

    function applyEffects(int $cardType, int $playerId) { // return $damages
        if ($cardType < 100 && !$this->keepAndEvolutionCardsHaveEffect()) {
            return;
        }

        switch($cardType) {
            // KEEP
            case EVEN_BIGGER_CARD: 
                $this->applyGetHealth($playerId, 2, $cardType, $playerId);
                $this->changeMaxHealth($playerId);
                break;
            case FREEZE_TIME_CARD:
                if ($playerId == intval($this->getActivePlayerId())) {
                    $diceCounts = $this->getGlobalVariable(DICE_COUNTS, true);
                    if ($diceCounts[1] >= 3) {
                        $this->incGameStateValue(FREEZE_TIME_MAX_TURNS, 1);
                    }
                }
                break;
            case NATURAL_SELECTION_CARD:
                $this->applyGetEnergy($playerId, 4, $cardType);
                $this->applyGetHealth($playerId, 4, $cardType, $playerId);
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
                $this->replacePlayersInTokyo($playerId);
                break;
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
                $activePlayerId = intval($this->getActivePlayerId());
                if ($activePlayerId != $playerId) {
                    $this->setGameStateValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, $playerId);
                    $this->setGameStateValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, $activePlayerId);
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
            case BARRICADES_CARD:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 3, $cardType);
                }
                break;
            case ICE_CREAM_TRUCK_CARD:
                $this->applyGetPoints($playerId, 1, $cardType);
                $this->applyGetHealth($playerId, 2, $cardType, $playerId);
                break;
            case SUPERTOWER_CARD:
                $this->applyGetPoints($playerId, 5, $cardType);
                break;
        }
    }

    function removeMimicToken(int $mimicCardType, int $mimicOwnerId) {
        $countRapidHealingBefore = $this->countCardOfType($mimicOwnerId, RAPID_HEALING_CARD);
        
        $card = $this->getMimickedCard($mimicCardType);
        if ($card) {
            $this->deleteGlobalVariable(MIMICKED_CARD.$mimicCardType);
            $this->notifyAllPlayers("removeMimicToken", '', [
                'card' => $card,
                'type' => $this->getMimicStringTypeFromMimicCardType($mimicCardType),
            ]);
        }

        $mimicCard = null;
        if ($mimicCardType == MIMIC_CARD) {
            $mimicCard = $this->getCardsFromDb($this->cards->getCardsOfType(MIMIC_CARD))[0];

            if ($mimicCard && $mimicCard->tokens > 0) {
                $this->setCardTokens($mimicCard->location_arg, $mimicCard, 0);
            }
        } else if ($mimicCardType == FLUXLING_WICKEDNESS_TILE) {
            $mimicCards = $this->wickednessTiles->getItemsByFieldName('type', [FLUXLING_WICKEDNESS_TILE]);
            $mimicCard = $mimicCards[0] ?? null;

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
        $this->notifyAllPlayers("setMimicToken", clienttranslate('${player_name} mimics ${card_name}'), [
            'card' => $card,
            'player_name' => $this->getPlayerName($mimicOwnerId),
            'card_name' => $card->type,
            'type' => $this->getMimicStringTypeFromMimicCardType($mimicCardType),
        ]);

        // no need to check for damage return, no discard card can be mimicked
        $this->applyEffects($card->type, $mimicOwnerId);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            if ($mimicCardType === MIMIC_CARD) {
                $mimicCard = $this->getCardsFromDb($this->cards->getCardsOfType(MIMIC_CARD))[0];
                $this->setCardTokens($mimicOwnerId, $mimicCard, $tokens);
            } else if ($mimicCardType === FLUXLING_WICKEDNESS_TILE) {
                $mimicCards = $this->wickednessTiles->getItemsByFieldName('type', [FLUXLING_WICKEDNESS_TILE]);
                $mimicCard = $mimicCards[0] ?? null;
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

    function countUnusedCardOfType($playerId, $cardType, $includeMimick = true) {
        return count($this->getUnusedCardOfType($playerId, $cardType, $includeMimick));
    }

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

    function getUnusedCardOfType($playerId, $cardType, $includeMimick = true) {
        $cards = $this->getCardsOfType($playerId, $cardType, $includeMimick);
        $usedCardsIds = $this->getUsedCard();
        return array_values(array_filter($cards, fn($card) => !in_array($card->id, $usedCardsIds)));
    }

    function getCardCost(int $playerId, int $cardType) {
        $cardCost = $this->getCardBaseCost($cardType);

        // alien origin
        $countAlienOrigin = $this->countCardOfType($playerId, ALIEN_ORIGIN_CARD);
        
        $wickenessTilesDec = $this->isWickednessExpansion() ? $this->wickednessTiles->onIncPowerCardsReduction(new Context($this, currentPlayerId: $playerId)) : 0;
        // inadequate offering
        $inadequateOffering = $this->isAnubisExpansion() && $this->getCurseCardType() == INADEQUATE_OFFERING_CURSE_CARD ? 2 : 0;        
        // secret laboratory
        $countSecretLaboratory = 0;
        if ($this->isPowerUpExpansion()) {
            $countSecretLaboratory = $this->countEvolutionOfType($playerId, SECRET_LABORATORY_EVOLUTION);
        }

        return max($cardCost + $inadequateOffering - $countAlienOrigin - $wickenessTilesDec - $countSecretLaboratory, 0);
    }

    function canBuyCard(int $playerId, int $cardType, int $cost) {
        if ($cardType === HIBERNATION_CARD && $this->inTokyo($playerId)) {
            return false;
        }
        return $cost <= $this->getPlayerEnergy($playerId);
    }

    function applyResurrectCard(int $playerId, int $logCardType, string $message, bool $resetWickedness, bool $removeEvolutions, bool $removeEnergy, int $newHearts, /*int|null*/ $points) {
        $playerName = $this->getPlayerName($playerId);
        // discard all cards
        $zombified = $this->getPlayer($playerId)->zombified;
        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        if ($zombified) {
            $cards = array_filter($cards, fn($card) => $card->type != ZOMBIFY_CARD);
        }
        $this->removeCards($playerId, $cards);
        // discard all tiles
        if ($this->isWickednessExpansion()) {
            $tiles = $this->wickednessTiles->getPlayerTiles($playerId);
            $this->removeWickednessTiles($playerId, $tiles);
        }

        if ($removeEvolutions) {
            $visibleEvolutions = $this->getEvolutionCardsByLocation('table', $playerId);
            $hiddenEvolutions = $this->getEvolutionCardsByLocation('hand', $playerId);
            $this->removeEvolutions($playerId, array_merge($visibleEvolutions, $hiddenEvolutions));
        }

        // reset wickedness
        if ($resetWickedness) {
            $this->DbQuery("UPDATE player SET `player_wickedness` = 0, player_take_wickedness_tiles = '[]' where `player_id` = $playerId");
            $this->notifyAllPlayers('wickedness', '', [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'wickedness' => 0,
            ]);
        }

        // remove energy
        if ($removeEnergy) {
            $this->DbQuery("UPDATE player SET `player_energy` = 0 where `player_id` = $playerId");
            $this->notifyAllPlayers('energy','', [
                'playerId' => $playerId,
                'player_name' => $playerName,
                'energy' => 0,
            ]);
        }

        if ($points !== null) {
            // go back to $points stars
            $this->DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
            $this->notifyAllPlayers('points','', [
                'playerId' => $playerId,
                'player_name' => $playerName,
                'points' => $points,
            ]);
        }

        // get back to $newHearts heart
        $this->DbQuery("UPDATE player SET `player_health` = $newHearts where `player_id` = $playerId");
        $this->notifyAllPlayers('health', '', [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'health' => $newHearts,
        ]);

        $this->notifyAllPlayers('resurrect', $message, [
            'playerId' => $playerId,
            'player_name' => $playerName,
            'health' => $newHearts,
            'card_name' => $logCardType,
            'zombified' => $zombified,
        ]);

        if ($this->inTokyo($playerId)) {
            $this->leaveTokyo($playerId);
        }
    }

    function applyItHasAChild(int $playerId) {
        $this->applyResurrectCard(
            $playerId, 
            IT_HAS_A_CHILD_CARD, 
            clienttranslate('${player_name} reached 0 [Heart]. With ${card_name}, all cards and [Star] are lost but player gets back 10 [Heart]'),
            $this->isDarkEdition(), 
            false,
            false,
            10,
            0
        );
    }

    function applyZombify(int $playerId) {
        $this->DbQuery("UPDATE player SET `player_zombified` = 1 where `player_id` = $playerId");

        $this->applyResurrectCard(
            $playerId, 
            ZOMBIFY_CARD, 
            clienttranslate('${player_name} reached 0 [Heart]. With ${card_name}, all cards, tiles, wickedness and [Star] are lost but player gets back 12 [Heart] and is now a Zombie!'),
            true, 
            false,
            false,
            12,
            0
        );
    }

    function applyNineLives(int $playerId, EvolutionCard &$card) {
        $this->playEvolutionToTable($playerId, $card, '');

        $this->applyResurrectCard(
            $playerId, 
            3000 + $card->type, 
            clienttranslate('${player_name} reached 0 [Heart]. With ${card_name}, all [Energy], [Star], cards and Evolutions are lost but player gets back 9[Heart] and 9[Star]'),
            false, 
            true,
            true,
            9,
            9
        );
    }

    function applySonOfKongKiko(int $playerId, EvolutionCard &$card) {
        $this->playEvolutionToTable($playerId, $card, '');
        $this->removeEvolution($playerId, $card, false, 5000);

        $this->applyResurrectCard(
            $playerId, 
            3000 + $card->type, 
            /*client TODOPUKK translate*/('${player_name} reached 0 [Heart]. With ${card_name}, ${player_name} gets back to 4[Heart], leave Tokyo, and continue playing'),
            false, 
            false,
            false,
            4,
            null
        );
    }

    function applyBatteryMonster(int $playerId, $card) {
        $energyOnBatteryMonster = $card->tokens - 2;
        if ($card->type == FLUXLING_WICKEDNESS_TILE) {
            $card->id = $card->id - 2000;
            $this->setTileTokens($playerId, $card, $energyOnBatteryMonster);
        } else { // mimic or battery monster
            $this->setCardTokens($playerId, $card, $energyOnBatteryMonster);
        }

        $this->applyGetEnergyIgnoreCards($playerId, 2, BATTERY_MONSTER_CARD);

        if ($energyOnBatteryMonster <= 0 && ($card->type == BATTERY_MONSTER_CARD || $card->type == MIMIC_CARD)) {
            $this->removeCard($playerId, $card);
        }
    }

    function buyEnergyDrink($diceIds) {
        $this->checkAction('buyEnergyDrink');

        $playerId = $this->getActivePlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        $cardCount = $this->countCardOfType($playerId, ENERGY_DRINK_CARD);

        if ($cardCount == 0) {
            throw new \BgaUserException('No Energy Drink card');
        }

        $this->applyLoseEnergyIgnoreCards($playerId, 1, 0);
        
        $extraRolls = intval($this->getGameStateValue(EXTRA_ROLLS)) + 1;
        $this->setGameStateValue(EXTRA_ROLLS, $extraRolls);

        $this->rethrowDice($diceIds);    
    }

    function useSmokeCloud($diceIds) {
        $this->checkAction('useSmokeCloud');

        $playerId = $this->getActivePlayerId();

        $cards = $this->getCardsOfType($playerId, SMOKE_CLOUD_CARD);

        if (count($cards) == 0) {
            throw new \BgaUserException('No Smoke Cloud card');
        }

        // we choose mimic card first, if available
        $card = $this->array_find($cards, fn($icard) => $icard->type == FLUXLING_WICKEDNESS_TILE && $icard->tokens > 0) ??
                $this->array_find($cards, fn($icard) => $icard->type == MIMIC_CARD && $icard->tokens > 0) ??
                $cards[0];

        if ($card->tokens < 1) {
            throw new \BgaUserException('Not enough token');
        }

        $tokensOnCard = $card->tokens - 1;
        if ($card->type == FLUXLING_WICKEDNESS_TILE) {
            $card->id = $card->id - 2000;
            $this->setTileTokens($playerId, $card, $tokensOnCard);
        } else {
            $this->setCardTokens($playerId, $card, $tokensOnCard);
        }

        if ($tokensOnCard <= 0 && $card->type != MIMIC_CARD && $card->type != FLUXLING_WICKEDNESS_TILE) {
            $this->removeCard($playerId, $card);
        }
        
        $extraRolls = intval($this->getGameStateValue(EXTRA_ROLLS)) + 1;
        $this->setGameStateValue(EXTRA_ROLLS, $extraRolls);

        $this->notifyAllPlayers('log', clienttranslate('${player_name} uses ${card_name} to gain 1 extra roll'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => SMOKE_CLOUD_CARD,
        ]);

        $this->rethrowDice($diceIds);
    }

    function useRapidHealing() {
        $playerId = $this->getCurrentPlayerId(); // current, not active !

        $this->applyRapidHealing($playerId);

        $this->updateCancelDamageIfNeeded($playerId);
    }

    function useMothershipSupport() {
        $playerId = $this->getCurrentPlayerId(); // current, not active !

        $this->applyMothershipSupport($playerId);

        $this->updateCancelDamageIfNeeded($playerId);
    }

    function applyRapidHealing(int $playerId) {
        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \BgaUserException('Not enough energy');
        }

        if ($this->getPlayer($playerId)->eliminated) {
            throw new \BgaUserException('You can\'t heal when you\'re dead');
        }

        $health = $this->getPlayerHealth($playerId);

        if ($health >= $this->getPlayerMaxHealth($playerId)) {
            throw new \BgaUserException('You can\'t heal when you\'re already at full life');
        }

        if (!$this->canGainHealth($playerId)) {
            throw new \BgaUserException(self::_('You cannot gain [Heart]'));
        }

        if ($this->countCardOfType($playerId, RAPID_HEALING_CARD) == 0) {
            throw new \BgaUserException('No Rapid Healing card');
        }

        $this->applyGetHealth($playerId, 1, RAPID_HEALING_CARD, $playerId);
        $this->applyLoseEnergyIgnoreCards($playerId, 2, 0, $playerId);
    }

    function applyMothershipSupport(int $playerId) {
        if ($playerId != $this->getActivePlayerId()) {
            throw new \BgaUserException('This is not your turn');
        }

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        if ($this->getPlayer($playerId)->eliminated) {
            throw new \BgaUserException('You can\'t heal when you\'re dead');
        }

        $health = $this->getPlayerHealth($playerId);

        if ($health >= $this->getPlayerMaxHealth($playerId)) {
            throw new \BgaUserException('You can\'t heal when you\'re already at full life');
        }

        if (!$this->canGainHealth($playerId)) {
            throw new \BgaUserException(self::_('You cannot gain [Heart]'));
        }

        $cards = $this->getEvolutionsOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);
        $unusedCards = array_values(array_filter($cards, fn($card) => !$this->isUsedCard(3000 + $card->id)));
        $card = count($unusedCards) > 0 ? $unusedCards[0] : null;
        if ($card == null) {
            throw new \BgaUserException('No Mothership Support Evolution');
        }
        if ($this->isUsedCard(3000 + $card->id)) {
            throw new \BgaUserException('You already used Mothership Support this turn');
        }

        $this->setUsedCard(3000 + $card->id);
        $this->applyGetHealth($playerId, 1, 3000 + MOTHERSHIP_SUPPORT_EVOLUTION, $playerId);
        $this->applyLoseEnergyIgnoreCards($playerId, 1, 0, $playerId);

        if (count($unusedCards) == 1) {
            $this->notifyPlayer($playerId, 'toggleMothershipSupportUsed', '', [
                'playerId' => $playerId,
                'used' => true,
            ]);
        }
    }

    function removeCard(int $playerId, $card, bool $silent = false, bool $delay = false, bool $ignoreMimicToken = false) {
        if ($card->id >= 2000) {
            // trying to remove mimic tile, but tile isn't removed when mimicked card is removed
            return;
        }

        $countRapidHealingBefore = $this->countCardOfType($playerId, RAPID_HEALING_CARD);

        $changeMaxHealth = $card->type == EVEN_BIGGER_CARD;
        
        if ($card->type == MIMIC_CARD) { // Mimic
            $changeMaxHealth = $this->getMimickedCardType(MIMIC_CARD) == EVEN_BIGGER_CARD;
            $this->removeMimicToken(MIMIC_CARD, $playerId);
        } else if ($card->id == $this->getMimickedCardId(MIMIC_CARD) && !$ignoreMimicToken) {
            $this->removeMimicToken(MIMIC_CARD, $playerId);
        }
        if ($card->id == $this->getMimickedCardId(FLUXLING_WICKEDNESS_TILE) && !$ignoreMimicToken) {
            $this->removeMimicToken(FLUXLING_WICKEDNESS_TILE, $playerId);
        }

        $this->cards->moveCard($card->id, $card->type < 300 ? 'discard' : 'void'); // we don't want transformation/golden scarab cards in the discard, for Miraculous Catch

        if ($this->isPowerUpExpansion() && $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION) > 0) {
            $superiorAlienTechnologyTokens = $this->getSuperiorAlienTechnologyTokens($playerId);
            $superiorAlienTechnologyTokens = array_values(array_filter($superiorAlienTechnologyTokens, fn($token) => $token != $card->id));
            $this->setGlobalVariable(SUPERIOR_ALIEN_TECHNOLOGY_TOKENS.$playerId, $superiorAlienTechnologyTokens);
        }

        if (!$silent) {
            $this->notifyAllPlayers("removeCards", '', [
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

            $this->notifyPlayer($playerId, 'toggleRapidHealing', '', [
                'playerId' => $playerId,
                'active' => $active,
                'playerEnergy' => $playerEnergy,
                'isMaxHealth' => $this->getPlayerHealth($playerId) >= $this->getPlayerMaxHealth($playerId),
            ]);
        }
    }

    function toggleMothershipSupport(int $playerId, int $countMothershipSupportBefore) {
        $countMothershipSupportAfter = $this->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);
        
        if (boolval($countMothershipSupportBefore) != boolval($countMothershipSupportAfter)) {
            $active = $countMothershipSupportAfter > $countMothershipSupportBefore;

            $playerEnergy = null;
            if ($active) {
                $playerEnergy = $this->getPlayerEnergy($playerId);
            }            

            $this->notifyPlayer($playerId, 'toggleMothershipSupport', '', [
                'playerId' => $playerId,
                'active' => $active,
                'playerEnergy' => $playerEnergy,
                'isMaxHealth' => $this->getPlayerHealth($playerId) >= $this->getPlayerMaxHealth($playerId),
            ]);
        }
    }

    function removeCards(int $playerId, array $cards, bool $silent = false) {
        // if trying to remove mimic tile, we stop, as tile isn't removed when mimicked card is removed
        $cards = array_values(array_filter($cards, fn($card) => $card->id < 2000));

        foreach($cards as $card) {
            $this->removeCard($playerId, $card, true);
        }

        if (!$silent && count($cards) > 0) {
            $this->notifyAllPlayers("removeCards", '', [
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
        $this->DbQuery("UPDATE `card` SET `card_type_arg` = $tokens where `card_id` = ".$card->id);

        if (!$silent) {
            if ($card->type == MIMIC_CARD) {
                $card->mimicType = $this->getMimickedCardType(MIMIC_CARD);
            }
            $this->notifyAllPlayers("setCardTokens", '', [
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

    function canChangeMimickedCard(int $playerId) {
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
        $discardCards = array_values(array_filter($cards, fn($card) => $card->type >= 100 && $card->type < 200));
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

    function cancellableDamageWithSuperJump(int $playerId) {
        $countSuperJump = $this->countUnusedCardOfType($playerId, SUPER_JUMP_CARD);

        if ($countSuperJump > 0) {
            return min($countSuperJump, $this->getPlayerEnergy($playerId));
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

        if (intval($this->getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_score >= ".MAX_POINT)) > 1) {
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
        
        if ($this->isPowerUpExpansion()) {
            $twasBeautyKilledTheBeastCards = $this->getEvolutionCardsByType(TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION);
            foreach($twasBeautyKilledTheBeastCards as $twasBeautyKilledTheBeastCard) {
                if ($twasBeautyKilledTheBeastCard->location == 'table') {
                    $playerId = intval($twasBeautyKilledTheBeastCard->location_arg);

                    $this->applyGetPoints($playerId, 1, 3000 + TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION);
                }
            }

            $simianScamperCards = $this->getEvolutionCardsByType(SIMIAN_SCAMPER_EVOLUTION);
            foreach($simianScamperCards as $simianScamperCard) {
                if ($simianScamperCard->location == 'table') {
                    $this->removeEvolution($simianScamperCard->location_arg, $simianScamperCard);
                }
            }

            $detachableTailCards = $this->getEvolutionCardsByType(DETACHABLE_TAIL_EVOLUTION);
            foreach($detachableTailCards as $detachableTailCard) {
                if ($detachableTailCard->location == 'table') {
                    $this->removeEvolution($detachableTailCard->location_arg, $detachableTailCard);
                }
            }

            $rabbitsFootCards = $this->getEvolutionCardsByType(RABBIT_S_FOOT_EVOLUTION);
            foreach($rabbitsFootCards as $rabbitsFootCard) {
                if ($rabbitsFootCard->location == 'table') {
                    $this->removeEvolution($rabbitsFootCard->location_arg, $rabbitsFootCard);
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

    function getTopDeckCard() {
        return Card::onlyId($this->getCardFromDb($this->cards->getCardOnTop('deck')));
    }

    function getDeckCardCount() {
        return intval($this->cards->countCardInLocation('deck'));
    }

    function getTopCurseDeckCard() {
        return Card::onlyId($this->getCardFromDb($this->curseCards->getCardOnTop('deck')));
    }

    function willBeWounded(int $playerId, int $activePlayerId) {
        $activePlayerInTokyo = $this->inTokyo($activePlayerId);

        if ($playerId == $activePlayerId) {
            return false; // active player won't smash himself, even if he got nova breath
        }

        if ($this->countCardOfType($activePlayerId, NOVA_BREATH_CARD) == 0 && $this->inTokyo($playerId) == $activePlayerInTokyo) {
            return false; // same location & no Nova card for smashing player
        }

        if (!$this->canUseSymbol($activePlayerId, 6)) {
            $willBeAbleToSmashBecauseOfAnkh = false;
            if ($this->isHalloweenExpansion()) {
                $dieOfFate = $this->getDieOfFate();
                $willBeAbleToSmashBecauseOfAnkh = $dieOfFate->value == 4;                
            }

            if (!$willBeAbleToSmashBecauseOfAnkh) {
                return false; // curse card preventing smashes from activePlayerId
            }
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
                $this->applyGetPointsIgnoreCards($playerId, WIN_GAME, 0);
            
                $this->notifyAllPlayers("log", clienttranslate('${player_name} reached ${points} [Star] and wins the game with ${card_name}'), [
                    'playerId' => $playerId,
                    'player_name' => $this->getPlayerName($playerId),
                    'card_name' => ASTRONAUT_CARD,
                    'points' => 17,
                ]);
            }
        }
    }

    function getFormCard(int $playerId) {
        $playerCards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $formCard = $this->array_find($playerCards, fn($card) => $card->type == FORM_CARD);
        return $formCard;
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
            if ($this->canGainPoints($playerId) !== null) {
                $warningIds[$card->id] = '[Star]';
            }
        }
    } 
    
    function goToMimicSelection(int $playerId, int $mimicCardType, $endState = null) {
        $question = new Question(
            'ChooseMimickedCard',
            clienttranslate('${actplayer} must select a card to mimic'),
            clienttranslate('${you} must select a card to mimic'),
            [$playerId],
            -1,
            [ 
                'playerId' => $playerId,
                '_args' => [ 
                    'player_id' => $playerId,
                    'actplayer' => $this->getPlayerName($playerId) 
                ],
                'mimicCardType' => $mimicCardType,
                'mimicArgs' => $this->getArgChooseMimickedCard($playerId, $mimicCardType),
            ]
        );

        if ($endState == null && in_array(intval($this->gamestate->state_id()), [ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT, ST_AFTER_WHEN_CARD_IS_BOUGHT])) {
            $endState = ST_PLAYER_BUY_CARD;
        }

        $this->addStackedState($endState);
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function getEffectiveDamage(int $damageAmount, int $playerId, int $damageDealerId, /*ClawDamage|null*/$clawDamage) {
        $effectiveDamage = $damageAmount;
        $logs = [];
        $damageByActivePlayer = $playerId != $damageDealerId && intval($this->getActivePlayerId()) == $damageDealerId;

        if ($damageAmount > 0) {
            // devil
            $countDevil = $damageByActivePlayer ? $this->countCardOfType($damageDealerId, DEVIL_CARD) : 0;
            if ($countDevil > 0) {
                $effectiveDamage += $countDevil;
                $logs[] = new LoseHealthLog($this, $playerId, $countDevil, DEVIL_CARD);
            }

            $isPowerUpExpansion = $this->isPowerUpExpansion();

            // target acquired
            $countTargetAcquired = 0;
            if ($isPowerUpExpansion && $playerId == intval($this->getGameStateValue(TARGETED_PLAYER))) {
                $countTargetAcquired = $this->countEvolutionOfType($damageDealerId, TARGET_ACQUIRED_EVOLUTION);

                if ($countTargetAcquired > 0) {
                    $effectiveDamage += $countTargetAcquired;
                    $logs[] = new LoseHealthLog($this, $playerId, $countTargetAcquired, 3000 + TARGET_ACQUIRED_EVOLUTION);
                }
            }

            if ($clawDamage !== null && $isPowerUpExpansion) {
                // detachable head
                $detachableHeadEvolution = $this->getGiftEvolutionOfType($damageDealerId, DETACHABLE_HEAD_EVOLUTION);
                if ($detachableHeadEvolution !== null && $detachableHeadEvolution->ownerId == $damageDealerId) {
                    $effectiveDamage += 1;
                    $logs[] = new LoseHealthLog($this, $playerId, 1, 3000 + DETACHABLE_HEAD_EVOLUTION);
                }

                // mecha blash
                $countMechaBlast = $this->countEvolutionOfType($damageDealerId, MECHA_BLAST_EVOLUTION);
                if ($countMechaBlast > 0) {
                    $effectiveDamage += $countMechaBlast * 2;
                    $logs[] = new LoseHealthLog($this, $playerId, $countMechaBlast * 2, 3000 + MECHA_BLAST_EVOLUTION);
                }
            }
            
            // electric carrot
            if ($clawDamage !== null && $clawDamage->electricCarrotChoice !== null && array_key_exists($playerId, (array)($clawDamage->electricCarrotChoice)) && ((array)($clawDamage->electricCarrotChoice))[$playerId] == 4) {
                $effectiveDamage += 1;
                $logs[] = new LoseHealthLog($this, $playerId, 1, 3000 + ELECTRIC_CARROT_EVOLUTION);
            }

            // claws of steel 
            // last effect so it can be cummulative with previous ones
            $countClawsOfSteel = 0;
            if ($isPowerUpExpansion && $damageByActivePlayer) {
                $countClawsOfSteel = $this->countEvolutionOfType($damageDealerId, CLAWS_OF_STEEL_EVOLUTION);

                if ($countClawsOfSteel > 0) {
                    $damageBefore = $this->damageDealtToAnotherPlayerThisTurn($damageDealerId, $playerId);
                    if (($damageBefore + $effectiveDamage) >= 3) {
                        $effectiveDamage += $countClawsOfSteel;
                        $logs[] = new LoseHealthLog($this, $playerId, $countClawsOfSteel, 3000 + CLAWS_OF_STEEL_EVOLUTION);
                    }
                }
            }
        }

        $result = new \stdClass();
        $result->effectiveDamage = $effectiveDamage;
        $result->logs = $logs;
        return $result;
    }
    
}
