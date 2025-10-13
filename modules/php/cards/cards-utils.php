<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/card.php');
require_once(__DIR__.'/../Objects/damage.php');
require_once(__DIR__.'/../Objects/question.php');
require_once(__DIR__.'/../Objects/log.php');

use Bga\GameFramework\Actions\CheckAction;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;
use KOT\Objects\LoseHealthLog;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

/**
 * @mixin \Bga\Games\KingOfTokyo\Game
 */
trait CardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

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
            $mimicCard = $this->powerCards->getCardsOfType(MIMIC_CARD)[0];

            if ($mimicCard && $mimicCard->tokens > 0) {
                $this->setCardTokens($mimicCard->location_arg, $mimicCard, 0);
            }
        } else if ($mimicCardType == FLUXLING_WICKEDNESS_TILE) {
            $mimicCards = $this->wickednessTiles->getItemsByFieldName('type', [FLUXLING_WICKEDNESS_TILE]);
            $mimicCard = $mimicCards[0] ?? null;

            if ($mimicCard && $mimicCard->tokens > 0) {
                $this->wickednessExpansion->setTileTokens($mimicCard->location_arg, $mimicCard, 0);
            }
        }

        if ($mimicCard && $card && $card->type == EVEN_BIGGER_CARD) {
            $this->changeMaxHealth($mimicCard->location_arg);
        } 
    
        $this->toggleRapidHealing($mimicOwnerId, $countRapidHealingBefore);
    }

    function setMimickedCardId(int $mimicCard, int $mimicOwnerId, int $cardId) {
        $card = $this->powerCards->getItemById($cardId);
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
            'player_name' => $this->getPlayerNameById($mimicOwnerId),
            'card_name' => $card->type,
            'type' => $this->getMimicStringTypeFromMimicCardType($mimicCardType),
        ]);

        // no need to check for damage return, no discard card can be mimicked
        $this->powerCards->applyEffects($card, $mimicOwnerId, -1);

        $tokens = $this->getTokensByCardType($card->type);
        if ($tokens > 0) {
            if ($mimicCardType === MIMIC_CARD) {
                $mimicCard = $this->powerCards->getCardsOfType(MIMIC_CARD)[0];
                $this->setCardTokens($mimicOwnerId, $mimicCard, $tokens);
            } else if ($mimicCardType === FLUXLING_WICKEDNESS_TILE) {
                $mimicCards = $this->wickednessTiles->getItemsByFieldName('type', [FLUXLING_WICKEDNESS_TILE]);
                $mimicCard = $mimicCards[0] ?? null;
                $this->wickednessExpansion->setTileTokens($mimicOwnerId, $mimicCard, $tokens);
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

        $cards = $this->powerCards->getPlayerCardsOfType($cardType, $playerId);

        if ($cardType < 100 && $includeMimick && $cardType != MIMIC_CARD) { // don't search for mimick mimicking itself, nor discard/costume cards
            $mimickedCardType = $this->getMimickedCardType(MIMIC_CARD);
            $mimickedCardTypeWickednessTile = $this->getMimickedCardType(FLUXLING_WICKEDNESS_TILE);
            if ($mimickedCardType == $cardType) {
                $cards = array_merge($cards, $this->getCardsOfType($playerId, MIMIC_CARD, false)); // mimick
            }
            if ($mimickedCardTypeWickednessTile == $cardType) {
                $tile = $this->wickednessExpansion->getWickednessTileByType($playerId, FLUXLING_WICKEDNESS_TILE);
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
        $cardCost = $this->powerCards->getCardBaseCost($cardType);

        if ($cardType === NO_BRAIN_CARD) {
            $cardCost += $this->mindbugExpansion->mindbugTokens->get($playerId);
        }
        $nextPowerCardCordReduction = $this->globals->get(NEXT_POWER_CARD_COST_REDUCTION, 0);
        if ($nextPowerCardCordReduction != 0) {
            $cardCost -= $nextPowerCardCordReduction;
        }

        // alien origin
        $countAlienOrigin = $this->countCardOfType($playerId, ALIEN_ORIGIN_CARD);
        
        $wickenessTilesDec = $this->wickednessExpansion->isActive() ? $this->wickednessTiles->onIncPowerCardsReduction(new Context($this, currentPlayerId: $playerId)) : 0;
        // inadequate offering
        $inadequateOffering = $this->anubisExpansion->isActive() && $this->anubisExpansion->getCurseCardType() == INADEQUATE_OFFERING_CURSE_CARD ? 2 : 0;        
        // secret laboratory
        $countSecretLaboratory = 0;
        if ($this->powerUpExpansion->isActive()) {
            $countSecretLaboratory = $this->countEvolutionOfType($playerId, SECRET_LABORATORY_EVOLUTION);
        }

        return max($cardCost + $inadequateOffering - $countAlienOrigin - $wickenessTilesDec - $countSecretLaboratory, 0);
    }

    function canAffordCard(int $playerId, int $cost) {
        return $cost <= $this->getPlayerEnergy($playerId);
    }

    function applyResurrectCard(int $playerId, int | EvolutionCard $logCardType, string $message, bool $resetWickedness, bool $removeEvolutions, bool $removeEnergy, int $newHearts, /*int|null*/ $points) {
        if ($logCardType instanceof EvolutionCard) {
            $logCardType = 3000 + $logCardType->type;
        }

        $playerName = $this->getPlayerNameById($playerId);
        // discard all cards
        $zombified = $this->getPlayer($playerId)->zombified;
        $cards = $this->powerCards->getPlayerReal($playerId);
        if ($zombified) {
            $cards = array_filter($cards, fn($card) => $card->type != ZOMBIFY_CARD);
        }
        $this->removeCards($playerId, $cards);
        // discard all tiles
        if ($this->wickednessExpansion->isActive()) {
            $tiles = $this->wickednessTiles->getPlayerTiles($playerId);
            $this->wickednessExpansion->removeWickednessTiles($playerId, $tiles);
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
                'player_name' => $this->getPlayerNameById($playerId),
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

    function applyBatteryMonster(int $playerId, $card) {
        $energyOnBatteryMonster = $card->tokens - 2;
        if ($card->type == FLUXLING_WICKEDNESS_TILE) {
            $card->id = $card->id - 2000;
            $this->wickednessExpansion->setTileTokens($playerId, $card, $energyOnBatteryMonster);
        } else { // mimic or battery monster
            $this->setCardTokens($playerId, $card, $energyOnBatteryMonster);
        }

        $this->applyGetEnergyIgnoreCards($playerId, 2, BATTERY_MONSTER_CARD);

        if ($energyOnBatteryMonster <= 0 && ($card->type == BATTERY_MONSTER_CARD || $card->type == MIMIC_CARD)) {
            $this->removeCard($playerId, $card);
        }
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
            throw new \BgaUserException(clienttranslate('You cannot gain [Heart]'));
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
            throw new \BgaUserException(clienttranslate('You cannot gain [Heart]'));
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

        $this->powerCards->moveItem($card, $card->type < 300 ? 'discard' : 'void'); // we don't want transformation/golden scarab cards in the discard, for Miraculous Catch

        if ($this->powerUpExpansion->isActive() && $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION) > 0) {
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
        $card = $this->powerCards->getPlayerCardsOfType($cardType, $playerId)[0];

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

    function getPlayersWithOpportunist(int $playerId): array {
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

    function canChangeMimickedCard(int $playerId): bool {
        // check if player have mimic card
        if ($this->countCardOfType($playerId, MIMIC_CARD, false) == 0) {
            return false;
        }

        $playersIds = $this->getPlayersIds();
        $mimickedCardId = $this->getMimickedCardId(MIMIC_CARD);

        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->powerCards->getPlayerReal($playerId);
            foreach($cardsOfPlayer as $card) {
                if ($card->type != MIMIC_CARD && $card->type < 100 && $mimickedCardId != $card->id) {
                    return true;
                }
            }
        }
        
        return false;
    }

    function getTokensByCardType(int $cardType): int {
        switch($cardType) {
            case BATTERY_MONSTER_CARD: return 6;
            case SMOKE_CLOUD_CARD: return 3;
            default: return 0;
        }
    }

    
    function removeDiscardCards(int $playerId) {
        $cards = $this->powerCards->getPlayerReal($playerId);
        $discardCards = array_values(array_filter($cards, fn($card) => $card->type >= 100 && $card->type < 200));
        $this->removeCards($playerId, $discardCards);
    }

    function getDamageToCancelToSurvive(int $remainingDamage, int $playerHealth): int {
        return $remainingDamage - $playerHealth + 1;
    }

    function cancellableDamageWithRapidHealing(int $playerId): int {
        $hasRapidHealing = $this->countCardOfType($playerId, RAPID_HEALING_CARD) > 0;

        if ($hasRapidHealing) {
            return floor($this->getPlayerEnergy($playerId) / 2);
        }
        return 0;
    }

    function cancellableDamageWithSuperJump(int $playerId): int {
        $countSuperJump = $this->countUnusedCardOfType($playerId, SUPER_JUMP_CARD);

        if ($countSuperJump > 0) {
            return min($countSuperJump, $this->getPlayerEnergy($playerId));
        }
        return 0;
    }

    function isSureWin(int $playerId): bool {
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
        $ghostCards = $this->powerCards->getCardsOfType(GHOST_CARD);
        if (count($ghostCards) > 0) {
            $ghostCard = $ghostCards[0];
            if ($ghostCard->location == 'hand') {
                $playerId = intval($ghostCard->location_arg);

                if ($this->isDamageTakenThisTurn($playerId)) {
                    $this->applyGetHealth($playerId, 1, GHOST_CARD, $playerId);
                }
            }
        }
        
        $vampireCards = $this->powerCards->getCardsOfType(VAMPIRE_CARD);
        if (count($vampireCards) > 0) {
            $vampireCard = $vampireCards[0];
        
            if ($vampireCard->location == 'hand') {
                $playerId = intval($vampireCard->location_arg);

                if ($this->isDamageDealtToOthersThisTurn($playerId)) {
                    $this->applyGetHealth($playerId, 1, VAMPIRE_CARD, $playerId);
                }
            }
        }
        
        if ($this->powerUpExpansion->isActive()) {
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
                $dieOfFate = $this->anubisExpansion->getDieOfFate();
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
                    'player_name' => $this->getPlayerNameById($playerId),
                    'card_name' => ASTRONAUT_CARD,
                    'points' => 17,
                ]);
            }
        }
    }

    function getFormCard(int $playerId) {
        $playerCards = $this->powerCards->getPlayerReal($playerId);
        $formCard = Arrays::find($playerCards, fn($card) => $card->type == FORM_CARD);
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
                    'actplayer' => $this->getPlayerNameById($playerId) 
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

            $isPowerUpExpansion = $this->powerUpExpansion->isActive();

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
