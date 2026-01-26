<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/question.php');

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerUpExpansion;
use KOT\Objects\Question;

/**
 * @mixin \Bga\Games\KingOfTokyo\Game
 */
trait EvolutionCardsUtilTrait {
    public PowerUpExpansion $powerUpExpansion;

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getEvolutionCardsByLocation(string $location, ?int $location_arg = null, ?int $type = null) {
        $cards = $this->powerUpExpansion->evolutionCards->getCardsInLocation($location, $location_arg, true, sortByField: 'location_arg');
        if ($type !== null) {
            $cards = Arrays::filter($cards, fn($card) => $card->type === $type);
        }
        return $cards;
    }

    function getEvolutionCardsByType(int $type) {
        return $this->powerUpExpansion->evolutionCards->getCardsByFieldName('type', [$type]);
    }

    function getEvolutionCardsByOwner(int $ownerId) {
        return $this->powerUpExpansion->evolutionCards->getCardsByFieldName('ownerId', [$ownerId]);
    }

    function getEvolutionCardsOnDeckTop(int $playerId, int $number) {
        return $this->powerUpExpansion->evolutionCards->getCardsInLocation("deck$playerId", null, true, $number, sortByField: 'location_arg');
    }

    function playEvolutionToTable(int $playerId, EvolutionCard &$card, /*string | null*/ $message = null, $fromPlayerId = null) {
        if ($message === null) {
            $message = clienttranslate('${player_name} plays ${card_name}');
        }

        $this->powerUpExpansion->evolutionCards->moveCard($card, 'table', $playerId);
        $card->location = 'table';

        $this->notify->all("playEvolution", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card' => $card,
            'card_name' => 3000 + $card->type,
            'fromPlayerId' => $fromPlayerId,
        ]);
        
        $this->incStat(1, 'played'.$this->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);
    }

    function getPlayersIdsWhoCouldPlayEvolutions(array $playersIds, array $stepCardsIds) { // return array of players able to play
        $isPowerUpMutantEvolution = $this->powerUpExpansion->isPowerUpMutantEvolution();
        $playersIds = $isPowerUpMutantEvolution ? $this->getPlayersIds(true) : $playersIds;

        // ignore a player if its hand is empty
        $playersIds = array_values(array_filter($playersIds, fn($playerId) => $this->powerUpExpansion->evolutionCards->countCardsInLocation('hand', $playerId) > 0));

        if (count($playersIds) == 0) {
            return [];
        }

        $playersAskPlayEvolution = [];
        foreach($playersIds as $playerId) {
            $player = $this->getPlayer($playerId);
            // ignore dead players
            $playersAskPlayEvolution[$playerId] = $this->getPlayer($playerId)->eliminated ? 99 : $player->askPlayEvolution;
        }
        // ignore a player if he don't want to be asked
        $playersIds = array_values(array_filter($playersIds, fn($playerId) => $playersAskPlayEvolution[$playerId] < 2));

        if (count($playersIds) == 0) {
            return [];
        }
        
        $dbResults = $this->getCollectionFromDb("SELECT player_id, player_monster FROM player WHERE player_id IN (".implode(',', $playersIds).")");
        $monsters = array_map(fn($dbResult) => intval($dbResult['player_monster']) % 100, $dbResults);
        
        $playersIdsWithPotentialCards = [];
        foreach ($playersIds as $playerId) {
            $playerPotentialMonsters = $isPowerUpMutantEvolution ? array_values($monsters) : [$monsters[$playerId]];
            $playerPotentionStepCardsTypes = array_values(array_filter($stepCardsIds, fn($cardType) => in_array(floor($cardType / 10), $playerPotentialMonsters)));

            if (count($playerPotentionStepCardsTypes) > 0) {
                // TODOPU ignore cards on table, or on discard?

                if ($playersAskPlayEvolution[$playerId] == 1) {
                    $playerHand = $this->getEvolutionCardsByLocation('hand', $playerId);
                    if (Arrays::some($playerHand, fn($evolutionCard) => in_array($evolutionCard->type, $playerPotentionStepCardsTypes))) {
                        $playersIdsWithPotentialCards[] = $playerId;
                    }
                } else {
                    $playersIdsWithPotentialCards[] = $playerId;
                }
            }
        }

        return $playersIdsWithPotentialCards;
    }

    function applyEvolutionEffectsRefreshBuyCardArgsIfNeeded(int $playerId, bool $refreshForAnyPlayer = false) {
        // if the player is in buy phase, refresh args
        if (($refreshForAnyPlayer || $playerId == intval($this->getActivePlayerId())) && $this->gamestate->getCurrentMainStateId() == ST_PLAYER_BUY_CARD) {
            $this->goToState(ST_PLAYER_BUY_CARD);
        }
    }

    function applyEvolutionEffects(EvolutionCard $card, int $playerId) { // return $damages
        if (!$this->keepAndEvolutionCardsHaveEffect() && $this->EVOLUTION_CARDS_TYPES[$card->type] == 1 && !in_array($card->type, [ICY_REFLECTION_EVOLUTION, ADAPTING_TECHNOLOGY_EVOLUTION])) {
            return; // TODOPU test
        }

        return $this->powerUpExpansion->evolutionCards->immediateEffect($card, new Context($this, currentPlayerId: $playerId));
    }

    function notifNewEvolutionCard(int $playerId, EvolutionCard $evolution, $message = '', $args = []) {
        $this->notify->player($playerId, "addEvolutionCardInHand", '', $args + [
            'playerId' => $playerId,
            'card' => $evolution,
        ]);    

        $this->notify->all("addEvolutionCardInHand", $message, $args + [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
        ]);
    }

    /**
     * @deprecated
     */
    function countEvolutionOfType(int $playerId, int $cardType, bool $fromTable = true, bool $fromHand = false) {
        return count($this->getEvolutionsOfType($playerId, $cardType, $fromTable, $fromHand));
    }

    /**
     * @deprecated
     */
    function getEvolutionsOfTypeInLocation(int $playerId, int $cardType, string $location) {
        $evolutions = $this->getEvolutionCardsByLocation($location, $playerId, $cardType);
        if ($location === 'table' && $this->EVOLUTION_CARDS_TYPES[$cardType] == 1 && $cardType != ICY_REFLECTION_EVOLUTION) { // don't search for mimick mimicking itself, nor temporary/surprise evolutions
            $mimickedCardType = $this->getMimickedEvolutionType();
            if ($mimickedCardType == $cardType) {
                $evolutions = array_merge($evolutions, $this->getEvolutionsOfTypeInLocation($playerId, ICY_REFLECTION_EVOLUTION, 'table')); // mimick
            }
        }

        return $evolutions;
    }

    /**
     * @deprecated
     */
    function getEvolutionsOfType(int $playerId, int $cardType, bool $fromTable = true, bool $fromHand = false) {
        if (!$this->keepAndEvolutionCardsHaveEffect() && $this->EVOLUTION_CARDS_TYPES[$cardType] == 1) {
            return [];
        }

        $evolutions = [];

        if ($fromTable) {
            $cards = $this->getEvolutionsOfTypeInLocation($playerId, $cardType, 'table');
            if (count($cards) > 0) {
                $evolutions = array_merge($evolutions, $cards);
            }
        }

        if ($fromHand) {
            $cards = $this->getEvolutionsOfTypeInLocation($playerId, $cardType, 'hand');
            if (count($cards) > 0) {
                $evolutions = array_merge($evolutions, $cards);
            }
        }

        return $evolutions;
    }

    function removeEvolution(int $playerId, $card, bool $silent = false, int $delay = 0, bool $ignoreMimicToken = false) {
        $changeMaxHealth = $card->type == EATER_OF_SOULS_EVOLUTION;

        $countMothershipSupportBefore = count($this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION, true, false));
        
        if ($card->type == ICY_REFLECTION_EVOLUTION) { // Mimic
            $changeMaxHealth = $this->getMimickedEvolutionType() == EATER_OF_SOULS_EVOLUTION;
            $this->removeMimicEvolutionToken($playerId);
        } else if ($card->id == $this->getMimickedEvolutionId() && !$ignoreMimicToken) {
            $this->removeMimicEvolutionToken($playerId);
        }
        $card->activated = null;
        $this->powerUpExpansion->evolutionCards->updateCard($card, ['activated']);
        $this->powerUpExpansion->evolutionCards->moveCard($card, 'discard'.$playerId);

        if ($card->type == MY_TOY_EVOLUTION || ($card->type == ICY_REFLECTION_EVOLUTION && $this->getMimickedEvolutionType() == MY_TOY_EVOLUTION)) {
            // if My Toy is removed, reserved card is put to discard
            $reservedCards = $this->powerCards->getReserved($playerId, $card->id);
            if (count($reservedCards) > 0) {
                $this->powerCards->moveCards($reservedCards, 'discard');
            }
        }

        if (!$silent) {
            $this->notify->all("removeEvolutions", '', [
                'playerId' => $playerId,
                'cards' => [$card],
                'delay' => $delay,
            ]);
        }
        if ($changeMaxHealth) {
            $this->changeMaxHealth($playerId);
        }
        
        $this->toggleMothershipSupport($playerId, $countMothershipSupportBefore);
    }

    function removeEvolutions(int $playerId, array $cards, bool $silent = false, int $delay = 0) {
        foreach($cards as $card) {
            $this->removeEvolution($playerId, $card, true);
        }

        if (!$silent && count($cards) > 0) {
            $this->notify->all("removeEvolutions", '', [
                'playerId' => $playerId,
                'cards' => $cards,
            ]);
        }
    }

    function isEvolutionOnTable(int $type) { // owner id | null
        $cards = $this->getEvolutionCardsByType($type);
        if (count($cards) > 0) {
            $card = $cards[0];
        
            if ($card->location == 'table') {
                return $card->location_arg;
            }
        }  
        return null;          
    }

    function applyLeaveWithTwasBeautyKilledTheBeast(int $playerId, array $cards) {
        $this->removeEvolutions($playerId, $cards);

        // lose all stars
        $points = 0;
        $this->bga->playerScore->set($playerId, $points, null);
        $this->notify->all('points', clienttranslate('${player_name} left Tokyo when ${card_name} is played, and loses all [Star].'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'points' => $points,
            'card_name' => 3000 + TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION,
        ]);
    }

    function getFirstUnusedEvolution(int $playerId, int $evolutionType, bool $fromTable = true, bool $fromHand = false) /* returns first unused evolution, null if none */ {
        $evolutions = $this->getEvolutionsOfType($playerId, $evolutionType, $fromTable, $fromHand);
        $usedCards = $this->getUsedCard();
        return Arrays::find($evolutions, fn($card) => !in_array(3000 + $card->id, $usedCards));
    }

    function getFirstUnsetFreezeRay(int $playerId) {
        $freezeRayCards = $this->getEvolutionsOfType($playerId, FREEZE_RAY_EVOLUTION);
        $unsetFreezeRayCard = Arrays::find($freezeRayCards, fn($card) => $card->tokens == 0);
        return $unsetFreezeRayCard;
    }

    function setEvolutionTokens(int $playerId, $card, int $tokens, bool $silent = false) {
        $card->tokens = $tokens;
        $this->powerUpExpansion->evolutionCards->updateCard($card, ['tokens']);

        if (!$silent) {
            /*TODOPU if ($card->type == MIMIC_CARD) {
                $card->mimicType = $this->getMimickedCardType(MIMIC_CARD);
            }*/
            $this->notify->all("setEvolutionTokens", '', [
                'playerId' => $playerId,
                'card' => $card,
            ]);
        }
    }
    
    function applyPrecisionFieldSupport(int $playerId) {
        $topCard = $this->powerCards->getTopDeckCard(false);

        if ($topCard->type > 100) {

            $this->notify->all('log500', clienttranslate('${player_name} draws ${card_name}. This card is discarded as it is not a Keep card.'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card_name' => $topCard->type,
            ]);
            $this->powerCards->moveCard($topCard, 'discard');
            $this->applyPrecisionFieldSupport($playerId);

        } else if ($this->powerCards->getCardBaseCost($topCard->type) > 4) {

            $this->notify->all('log500', clienttranslate('${player_name} draws ${card_name}. This card is discarded as it costs more than 4[Energy].'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'card_name' => $topCard->type,
            ]);
            $this->powerCards->moveCard($topCard, 'discard');
            $this->applyPrecisionFieldSupport($playerId);

        } else {
            $this->drawCard($playerId);
        }
    }

    function applyGiveSymbolQuestion(int $playerId, EvolutionCard $card, array $otherPlayers, array $symbols) {

        if (count($otherPlayers) == 0) {
            return;
        }

        $otherPlayersIds = array_map(fn($player) => $player->id, $otherPlayers);

        $canGiveSymbols = [];
        foreach($symbols as $symbol) {
            $canGiveSymbol = [];
            $playerField = '';
            switch ($symbol) {
                case 0: 
                    $playerField = 'score';
                    break;
                case 4: 
                    $playerField = 'health';
                    break;
                case 5: 
                    $playerField = 'energy';
                    break;
            }

            $canGiveSymbol = array_map(fn($player) => $player->id, array_values(array_filter($otherPlayers, fn($player) => $player->{$playerField} > 0)));

            $canGiveSymbols[$symbol] = $canGiveSymbol;
        }

        $args = [ 
            'card' => $card,
            'playerId' => $playerId,
            '_args' => [ 
                'player_name' => $this->getPlayerNameById($playerId),
                'symbolsToGive' => $symbols,
            ],
            'symbols' => $symbols,
        ];

        foreach($canGiveSymbols as $symbol => $canGiveSymbol) {
            $args['canGive'.$symbol] = $canGiveSymbol;
        }

        $question = new Question(
            'GiveSymbol',
            clienttranslate('Other monsters must give ${symbolsToGive} to ${player_name}'),
            clienttranslate('${you} must give ${symbolsToGive} to ${player_name}'),
            [$otherPlayersIds],
            ST_AFTER_ANSWER_QUESTION,
            $args,
            evolutionId: $card->id,
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive($otherPlayersIds, 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function applyGiveEnergyOrLoseHeartsQuestion(int $playerId, array $otherPlayers, EvolutionCard $card, int $heartNumber) {
        $otherPlayersIds = array_map(fn($player) => $player->id, $otherPlayers);

        $canGiveEnergy = array_map(fn($player) => $player->id, array_values(array_filter($otherPlayers, fn($player) => $player->energy > 0)));

        $question = new Question(
            'GiveEnergyOrLoseHearts',
            /*client TODOPUHA translate*/('Other monsters must give 1[Energy] or to ${player_name} or lose ${heartNumber}[Heart]'),
            /*client TODOPUHA translate*/('${you} must give 1[Energy] or to ${player_name} or lose ${heartNumber}[Heart]'),
            [$otherPlayersIds],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'card' => $card,
                'playerId' => $playerId,
                '_args' => [ 
                    'player_name' => $this->getPlayerNameById($playerId),
                    'heartNumber' => $heartNumber,
                 ],
                'canGiveEnergy' => $canGiveEnergy,
                'heartNumber' => $heartNumber,
            ],
            evolutionId: $card->id,
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive($otherPlayersIds, 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function applyTrickOrThreat(int $playerId, EvolutionCard $card) {
        $givers = array_filter($this->getOtherPlayers($playerId), fn($player) => $player->energy > 0 || $player->health > 0);

        if (count($givers) == 0) {
            return;
        }
        $receiving = $playerId;
        $this->applyGiveEnergyOrLoseHeartsQuestion($receiving, $givers, $card, 2);
    }


    function setMimickedEvolution(int $mimicOwnerId, object $card) {
        $countMothershipSupportBefore = $this->countEvolutionOfType($mimicOwnerId, MOTHERSHIP_SUPPORT_EVOLUTION);

        $mimickedCard = new \stdClass();
        $mimickedCard->card = $card;
        $mimickedCard->playerId = $card->location_arg;
        $this->setGlobalVariable(MIMICKED_CARD . ICY_REFLECTION_EVOLUTION, $mimickedCard);
        $this->notify->all("setMimicEvolutionToken", clienttranslate('${player_name} mimics ${card_name}'), [
            'card' => $card,
            'player_name' => $this->getPlayerNameById($mimicOwnerId),
            'card_name' => 3000 + $card->type,
        ]);

        // $this->applyEvolutionEffects($card, $mimicOwnerId, false);

        $tokens = $this->getTokensByEvolutionType($card->type);
        if ($tokens > 0) {
            $mimicCard = $this->getEvolutionCardsByType(ICY_REFLECTION_EVOLUTION)[0];
            $this->setCardTokens($mimicOwnerId, $mimicCard, $tokens);
        }
        
        $this->toggleMothershipSupport($mimicOwnerId, $countMothershipSupportBefore);
    }

    function removeMimicEvolutionToken(int $mimicOwnerId) {
        $countMothershipSupportBefore = $this->countEvolutionOfType($mimicOwnerId, MOTHERSHIP_SUPPORT_EVOLUTION);
        
        $card = $this->getMimickedEvolution();
        if ($card) {
            $this->deleteGlobalVariable(MIMICKED_CARD.ICY_REFLECTION_EVOLUTION);
            $this->notify->all("removeMimicEvolutionToken", '', [
                'card' => $card,
            ]);
        }

        $mimicCard = $this->getEvolutionCardsByType(ICY_REFLECTION_EVOLUTION)[0];

        if ($mimicCard && $mimicCard->tokens > 0) {
            $this->setCardTokens($mimicCard->location_arg, $mimicCard, 0);
        }

        /* TODOPU if ($mimicCard && $card && $card->type == EATER_OF_SOULS_EVOLUTION) {
            $this->changeMaxHealth($mimicCard->location_arg);
        } */
    
        $this->toggleMothershipSupport($mimicOwnerId, $countMothershipSupportBefore);
    }

    function getMimickedEvolution() {
        $mimickedCardObj = $this->getGlobalVariable(MIMICKED_CARD . ICY_REFLECTION_EVOLUTION);

        if ($mimickedCardObj != null) {
            return $mimickedCardObj->card;
        }
        return null;
    }

    function getMimickedEvolutionId() {
        $mimickedCard = $this->getMimickedEvolution();
        if ($mimickedCard != null) {
            return $mimickedCard->id;
        }
        return null;
    }

    function getMimickedEvolutionType() {
        $mimickedCard = $this->getMimickedEvolution();
        if ($mimickedCard != null) {
            return $mimickedCard->type;
        }
        return null;
    }

    function getTokensByEvolutionType(int $cardType) {
        switch($cardType) {
            case ADAPTING_TECHNOLOGY_EVOLUTION: return 3;
            default: return 0;
        }
    }

    function setOwnerIdForAllEvolutions() {
        $playersIds = $this->getPlayersIds();
        foreach($playersIds as $playerId) {
            $evolutions = $this->getEvolutionCardsByLocation('deck'.$playerId);
            $ids = array_map(fn($evolution) => $evolution->id, $evolutions);
            if(count($ids) > 0) {
                $this->DbQuery("UPDATE `evolution_card` SET `owner_id` = $playerId where `card_id` IN (" . implode(',', $ids) . ")");
            }

            $this->notify->all('ownedEvolutions', '', [
                'playerId' => $playerId,
                'evolutions' => $evolutions,
            ]);
        }
    }

    function giveEvolution(int $fromPlayerId, int $toPlayerId, EvolutionCard $evolution) {
        if ($toPlayerId == $fromPlayerId) {
            return;
        }

        if ($this->getPlayer($toPlayerId)->eliminated) {
            $this->removeEvolution($fromPlayerId, $evolution);
        }

        $this->removeEvolution($fromPlayerId, $evolution, true, false, true);
        $this->powerUpExpansion->evolutionCards->moveCard($evolution, 'table', $toPlayerId);
        $movedEvolution = $this->powerUpExpansion->evolutionCards->getCardById($evolution->id); // so we relaad location
        $this->playEvolutionToTable($toPlayerId, $movedEvolution, '', $fromPlayerId);

        if ($evolution->id == $this->getMimickedEvolutionId()) {
            $this->notify->all("setMimicEvolutionToken", '', [
                'card' => $movedEvolution,
            ]);
        }
    }

    function getSuperiorAlienTechnologyTokens(int $playerId) {
        $cardsIds = $this->getGlobalVariable(SUPERIOR_ALIEN_TECHNOLOGY_TOKENS.$playerId, true);
        return $cardsIds == null ? [] : $cardsIds;
    }

    function addSuperiorAlienTechnologyToken(int $playerId, int $cardId) {
        $cardsIds = $this->getSuperiorAlienTechnologyTokens($playerId);

        if (count($cardsIds) >= 3 * count($this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, true, false))) {
            throw new \BgaUserException('You can only have 3 cards with tokens.');
        }

        $cardsIds[] = $cardId;
        $this->setGlobalVariable(SUPERIOR_ALIEN_TECHNOLOGY_TOKENS.$playerId, $cardsIds);

        $card = $this->powerCards->getCardById($cardId);
        $this->notify->all("addSuperiorAlienTechnologyToken", '', [
            'playerId' => $playerId,
            'card' => $card,
        ]);
    }

    function askTargetAcquired(array $allDamages) {
        $activePlayerId = intval($this->getActivePlayerId());
        // if damages is a smash from active player
        if (count($allDamages) > 0 && gettype($allDamages[0]->cardType) == 'integer' &&  $allDamages[0]->cardType == 0 && $allDamages[0]->damageDealerId == $activePlayerId && intval($this->getGameStateValue(TARGETED_PLAYER)) != $activePlayerId) {
            $playersIds = array_unique(array_map(fn($damage) => $damage->playerId, $allDamages));
            $playersWithTargetAcquired = array_values(array_filter($playersIds, fn($playerId) => $this->countEvolutionOfType($playerId, TARGET_ACQUIRED_EVOLUTION) > 0));

            if (count($playersWithTargetAcquired) > 0) {
                $question = new Question(
                    'TargetAcquired',
                    clienttranslate('Player with ${card_name} can give target to ${player_name}'),
                    clienttranslate('${you} can give target to ${player_name}'),
                    $playersWithTargetAcquired,
                    ST_AFTER_RESOLVE_DAMAGE,
                    [ 
                        'playerId' => $activePlayerId,
                        '_args' => [ 
                            'player_name' => $this->getPlayerNameById($activePlayerId), 
                            'card_name' => 3000 + TARGET_ACQUIRED_EVOLUTION,
                        ],
                    ]
                );

                $this->setQuestion($question);
                $this->gamestate->setPlayersMultiactive($playersWithTargetAcquired, 'next', true);
                $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
                return true;
            }
        }
        return false;
    }

    function askLightningArmor(array $allDamages) {
        $activePlayerId = intval($this->getActivePlayerId());
        $playersIds = array_unique(array_map(fn($damage) => $damage->playerId, $allDamages));
        $playersWithLightningArmor = array_values(array_filter($playersIds, fn($playerId) => $this->countEvolutionOfType($playerId, LIGHTNING_ARMOR_EVOLUTION) > 0));

        if (count($playersWithLightningArmor) > 0) {

            $damageAmountByPlayer = [];

            foreach($playersWithLightningArmor as $playerId) {
                $damageAmountByPlayer[$playerId] = 0;
                $damageDealerIdByPlayer[$playerId] = 0;
                foreach($allDamages as $damage) {
                    if ($damage->playerId == $playerId && $damage->damageDealerId != $playerId && $damage->damageDealerId != 0) {
                        $damageAmountByPlayer[$playerId] += $damage->damage;
                        $damageDealerIdByPlayer[$playerId] += $damage->damageDealerId;
                    }
                }
            }

            if (Arrays::some($damageAmountByPlayer, fn($damageAmount) => $damageAmount > 0)) {
                $question = new Question(
                    'LightningArmor',
                    clienttranslate('Player with ${card_name} can throw dice to backfire damage'),
                    clienttranslate('${you} can throw dice to backfire damage'),
                    $playersWithLightningArmor,
                    ST_AFTER_RESOLVE_DAMAGE,
                    [ 
                        'damageAmountByPlayer' => $damageAmountByPlayer,
                        'damageDealerIdByPlayer' => $damageDealerIdByPlayer,
                        'playerId' => $activePlayerId,
                        '_args' => [ 
                            'player_name' => $this->getPlayerNameById($activePlayerId), 
                            'card_name' => 3000 + LIGHTNING_ARMOR_EVOLUTION,
                        ],
                    ]
                );

                $this->setQuestion($question);
                $this->gamestate->setPlayersMultiactive($playersWithLightningArmor, 'next', true);
                $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
                return true;
            }
        }
        return false;
    }
}