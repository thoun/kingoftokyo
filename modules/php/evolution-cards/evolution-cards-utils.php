<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/evolution-card.php');
require_once(__DIR__.'/../objects/damage.php');
require_once(__DIR__.'/../objects/question.php');

use KOT\Objects\EvolutionCard;
use KOT\Objects\Damage;
use KOT\Objects\Question;

trait EvolutionCardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getStackedStates() {
        $states = $this->getGlobalVariable(STACKED_STATES, true);
        return $states == null ? [] : $states;
    }

    function addStackedState($currentState = null) {
        $states = $this->getStackedStates();
        $states[] = $currentState ?? $this->gamestate->state_id();
        $this->setGlobalVariable(STACKED_STATES, $states);
    }

    function removeStackedStateAndRedirect() {
        $states = $this->getStackedStates();
        if (count($states) < 1) {
            throw new \Exception('No stacked state to remove');
        }
        $newState = array_pop($states);
        $this->setGlobalVariable(STACKED_STATES, $states);
        $this->goToState($newState);
    }

    function getStackedStateSuffix() {
        $states = $this->getStackedStates();
        return count($states) > 0 ? ''.count($states) : '';
    }

    function getQuestion() {
        return $this->getGlobalVariable(QUESTION.$this->getStackedStateSuffix());
    }

    function setQuestion(Question $question) {
        $this->setGlobalVariable(QUESTION.$this->getStackedStateSuffix(), $question);
    }

    function initEvolutionCards(array $affectedPlayersMonsters) {
        foreach($this->MONSTERS_WITH_POWER_UP_CARDS as $monster) {
            $cards = [];
            for($card=1; $card<=8; $card++) {
                $type = $monster * 10 + $card;
                $cards[] = ['type' => $type, 'type_arg' => 0, 'nbr' => 1];
            }
            $location = array_key_exists($monster, $affectedPlayersMonsters) ? 'deck'.$affectedPlayersMonsters[$monster] : 'monster'.$monster;
            $this->evolutionCards->createCards($cards, $location);
            $this->evolutionCards->shuffle($location); 
        }

        if (count($affectedPlayersMonsters) > 0) {
            $this->setOwnerIdForAllEvolutions();
        }
    }

    function getEvolutionCardById(int $id) {
        $sql = "SELECT * FROM `evolution_card` WHERE `card_id` = $id";
        $dbResults = $this->getCollectionFromDb($sql);
        return new EvolutionCard(array_values($dbResults)[0]);
    }

    function getEvolutionCardsByLocation(string $location, /*int|null*/ $location_arg = null, /*int|null*/ $type = null) {
        $sql = "SELECT * FROM `evolution_card` WHERE `card_location` = '$location'";
        if ($location_arg !== null) {
            $sql .= " AND `card_location_arg` = $location_arg";
        }
        if ($type !== null) {
            $sql .= " AND `card_type` = $type";
        }
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbCard) => new EvolutionCard($dbCard), array_values($dbResults));
    }

    function getEvolutionCardsByType(int $type) {
        $sql = "SELECT * FROM `evolution_card` WHERE `card_type` = $type";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbCard) => new EvolutionCard($dbCard), array_values($dbResults));
    }

    function getEvolutionCardsByOwner(int $ownerId) {
        $sql = "SELECT * FROM `evolution_card` WHERE `owner_id` = $ownerId";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbCard) => new EvolutionCard($dbCard), array_values($dbResults));
    }

    function getEvolutionCardsOnDeckTop(int $playerId, int $number) {
        $sql = "SELECT * FROM `evolution_card` WHERE `card_location` = 'deck$playerId' ORDER BY `card_location_arg` DESC LIMIT $number";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbCard) => new EvolutionCard($dbCard), array_values($dbResults));
    }

    function pickEvolutionCards(int $playerId, int $number = 2) {
        $remainingInDeck = intval($this->evolutionCards->countCardInLocation('deck'.$playerId));
        if ($remainingInDeck >= $number) {
            return $this->getEvolutionCardsOnDeckTop($playerId, $number);
        } else {
            $cards = $this->getEvolutionCardsOnDeckTop($playerId, $remainingInDeck);

            $this->evolutionCards->moveAllCardsInLocation('discard'.$playerId, 'deck'.$playerId);
            $this->evolutionCards->shuffle('deck'.$playerId);

            $cards = array_merge(
                $cards,
                $this->getEvolutionCardsOnDeckTop($playerId, $number - $remainingInDeck)
            );
            return $cards;
        }
    }

    function checkCanPlayEvolution(int $cardType, int $playerId) {
        $stateId = intval($this->gamestate->state_id());

        if ($stateId < 17) {
            throw new \BgaUserException(self::_("You can only play evolution cards when the game is started"));
        }

        // cards to player before starting turn
        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_BEFORE_START_TEMPORARY) && $stateId != ST_PLAYER_BEFORE_START_TURN) {
            throw new \BgaUserException(self::_("You can only play this evolution card before starting turn"));
        }

        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE) && $stateId != ST_PLAYER_BEFORE_RESOLVE_DICE) {
            throw new \BgaUserException(self::_("You can only play this evolution card when resolving dice"));
        }

        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO) && $stateId != ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO) {
            throw new \BgaUserException(self::_("You can only play this evolution card before entering Tokyo"));
        }

        if (in_array($cardType, [...$this->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO, ...$this->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO]) && $stateId != ST_PLAYER_AFTER_ENTERING_TOKYO) {
            throw new \BgaUserException(self::_("You can only play this evolution card after entering Tokyo"));
        }

        // cards to player when card is bought
        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT) && $stateId != ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT) {
            $canPlay = $cardType == BAMBOOZLE_EVOLUTION && $playerId != intval($this->getActivePlayerId());
            if (!$canPlay) {
                throw new \BgaUserException(self::_("You can only play this evolution card when another player is buying a card"));
            }
        }

        switch($cardType) {
            case NINE_LIVES_EVOLUTION:
            case SIMIAN_SCAMPER_EVOLUTION:
            case DETACHABLE_TAIL_EVOLUTION:
            case RABBIT_S_FOOT_EVOLUTION:
                throw new \BgaUserException(self::_("You can't play this Evolution now, you'll be asked to use it when you'll take damage"));
            case SAURIAN_ADAPTABILITY_EVOLUTION:
                throw new \BgaUserException(self::_("You can't play this Evolution now, you'll be asked to use it when you resolve your dice"));
            case FELINE_MOTOR_EVOLUTION:
                $startedTurnInTokyo = $this->getGlobalVariable(STARTED_TURN_IN_TOKYO, true);
                if (in_array($playerId, $startedTurnInTokyo)) {
                    throw new \BgaUserException(self::_("You started your turn in Tokyo"));
                }
                break;
            case TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION:
            case EATS_SHOOTS_AND_LEAVES_EVOLUTION:
                if (!$this->inTokyo($playerId)) {
                    throw new \BgaUserException(self::_("You can play this Evolution only if you are in Tokyo"));
                }
                break;
            case JUNGLE_FRENZY_EVOLUTION:
                if ($playerId != intval($this->getActivePlayerId())) {
                    throw new \BgaUserException(self::_("You must play this Evolution during your turn"));
                }
                if ($this->inTokyo($playerId)) {
                    throw new \BgaUserException(self::_("You can play this Evolution only if you are not in Tokyo"));
                }
                if (!$this->isDamageDealtThisTurn($playerId)) {
                    throw new \BgaUserException(self::_("You didn't deal damage to a player in Tokyo"));
                }
                break;
            case TUNE_UP_EVOLUTION:
                if ($this->inTokyo($playerId)) {
                    throw new \BgaUserException(self::_("You can play this Evolution only if you are not in Tokyo"));
                }
                break;
            case BLIZZARD_EVOLUTION:
                if ($playerId != intval($this->getActivePlayerId())) {
                    throw new \BgaUserException(self::_("You must play this Evolution during your turn"));
                }
                break;
            case ICY_REFLECTION_EVOLUTION:
                $playersIds = $this->getPlayersIds();
                $canPlayIcyReflection = false;
                foreach($playersIds as $playerId) {
                    $evolutions = $this->getEvolutionCardsByLocation('table', $playerId);
                    if ($this->array_some($evolutions, fn($evolution) => $evolution->type != ICY_REFLECTION_EVOLUTION && $this->EVOLUTION_CARDS_TYPES[$evolution->type] == 1)) {
                        $canPlayIcyReflection = true; // if there is a permanent evolution card in table
                    }
                }
                if (!$canPlayIcyReflection) {
                    throw new \BgaUserException(self::_("You can only play this evolution card when there is another permanent Evolution on the table"));
                }
                break;
        }
    }

    function playEvolutionToTable(int $playerId, EvolutionCard &$card, /*string | null*/ $message = null, $fromPlayerId = null) {
        if ($message === null) {
            $message = clienttranslate('${player_name} plays ${card_name}');
        }

        $this->evolutionCards->moveCard($card->id, 'table', $playerId);
        $card->location = 'table';

        $this->notifyAllPlayers("playEvolution", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $card,
            'card_name' => 3000 + $card->type,
            'fromPlayerId' => $fromPlayerId,
        ]);
        
        $this->incStat(1, 'played'.$this->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);
    }

    function getPlayersIdsWhoCouldPlayEvolutions(array $playersIds, array $stepCardsIds) { // return array of players able to play
        $isPowerUpMutantEvolution = $this->isPowerUpMutantEvolution();
        $playersIds = $isPowerUpMutantEvolution ? $this->getPlayersIds(true) : $playersIds;

        // ignore a player if its hand is empty
        $playersIds = array_values(array_filter($playersIds, fn($playerId) => intval($this->evolutionCards->countCardInLocation('hand', $playerId)) > 0));

        if (count($playersIds) == 0) {
            return [];
        }

        $playersAskPlayEvolution = [];
        foreach($playersIds as $playerId) {
            $player = $this->getPlayer($playerId);
            $playersAskPlayEvolution[$playerId] = $player->askPlayEvolution;
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
                    if ($this->array_some($playerHand, fn($evolutionCard) => in_array($evolutionCard->type, $playerPotentionStepCardsTypes))) {
                        $playersIdsWithPotentialCards[] = $playerId;
                    }
                } else {
                    $playersIdsWithPotentialCards[] = $playerId;
                }
            }
        }

        return $playersIdsWithPotentialCards;
    }

    function applyEvolutionEffectsRefreshBuyCardArgsIfNeeded(int $playerId) {
        // if the player is in buy phase, refresh args
        if ($playerId == intval($this->getActivePlayerId()) && intval($this->gamestate->state_id()) == ST_PLAYER_BUY_CARD) {
            $this->goToState(ST_PLAYER_BUY_CARD);
        }
    }

    function applyEvolutionEffects(EvolutionCard $card, int $playerId) { // return $damages
        if (!$this->keepAndEvolutionCardsHaveEffect() && $this->EVOLUTION_CARDS_TYPES[$card->type] == 1) {
            return; // TODOPU test
        }

        $cardType = $card->type;
        $logCardType = 3000 + $cardType;

        switch($cardType) {
            // Space Penguin
            case DEEP_DIVE_EVOLUTION:
                $this->applyDeepDive($playerId);
                break;
            case ICY_REFLECTION_EVOLUTION:
                $this->applyIcyReflection($playerId);
                break;
            // Alienoid
            case ALIEN_SCOURGE_EVOLUTION: 
                $this->applyGetPoints($playerId, 2, $logCardType);
                break;
            case PRECISION_FIELD_SUPPORT_EVOLUTION: 
                $this->applyPrecisionFieldSupport($playerId);
                break;
            case ANGER_BATTERIES_EVOLUTION:
                $damageCount = $this->getDamageTakenThisTurn($playerId);
                $this->applyGetEnergy($playerId, $damageCount, $logCardType);
                break;
            case ADAPTING_TECHNOLOGY_EVOLUTION:
                $this->setEvolutionTokens($playerId, $card, $this->getTokensByEvolutionType(ADAPTING_TECHNOLOGY_EVOLUTION));
                break;
            case SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION: 
                $this->applyEvolutionEffectsRefreshBuyCardArgsIfNeeded($playerId);
                break;
            // Cyber Kitty
            case MEGA_PURR_EVOLUTION:
                $this->applyMegaPurr($playerId, $card);
                break;
            case ELECTRO_SCRATCH_EVOLUTION:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 1, $playerId, $logCardType);
                }
                return $damages;
            case FELINE_MOTOR_EVOLUTION:
                $this->applyFelineMotor($playerId);
                break;
            // The King
            case MONKEY_RUSH_EVOLUTION:
                $this->moveToTokyoFreeSpot($playerId);
                if (!$this->tokyoHasFreeSpot()) {
                    $this->goToState($this->redirectAfterHalfMovePhase());
                }
                break;
            case JUNGLE_FRENZY_EVOLUTION:
                $this->setGameStateValue(JUNGLE_FRENZY_EXTRA_TURN, 1);
                break;
            case GIANT_BANANA_EVOLUTION:
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            // Gigazaur
            case RADIOACTIVE_WASTE_EVOLUTION:
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->applyGetHealth($playerId, 1, $logCardType, $playerId);
                break;
            case PRIMAL_BELLOW_EVOLUTION:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyLosePoints($otherPlayerId, 2, $logCardType);
                }
                break;
            // Meka Dragon
            case DESTRUCTIVE_ANALYSIS_EVOLUTION:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, false, false);
                $rolledSmashes = $diceCounts[6];
                if ($rolledSmashes > 0) {
                    $this->applyGetEnergy($playerId, $rolledSmashes, $logCardType);
                }
                break;
            case TUNE_UP_EVOLUTION:
                $this->applyGetHealth($playerId, 4, $logCardType, $playerId);
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->removeCard($playerId, $card, false, 5000);
                $this->goToState(ST_NEXT_PLAYER);
                break;
            // Boogie Woogie
            case WELL_OF_SHADOW_EVOLUTION:
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            case WORM_INVADERS_EVOLUTION:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 2, $playerId, $logCardType);
                }
                return $damages;
            // Pumpkin Jack
            case SMASHING_PUMPKIN_EVOLUTION:
                $players = $this->getPlayers();
                $damages = [];
                foreach ($players as $player) {
                    if ($player->score >= 12) {
                        $damages[] = new Damage($player->id, 2, $playerId, $logCardType);
                    }
                }
                return $damages;
            case BOBBING_FOR_APPLES_EVOLUTION: 
                $this->applyEvolutionEffectsRefreshBuyCardArgsIfNeeded($playerId);
                break;
            case FEAST_OF_CROWS_EVOLUTION:
                $this->applyFeastOfCrows($playerId, $card);
                break;
            case SCYTHE_EVOLUTION:
                $this->setEvolutionTokens($playerId, $card, 1);
                break;
            case CANDY_EVOLUTION:
                $this->applyGetHealth($playerId, 1, $logCardType, $playerId);
                break;
            // Cthulhu
            // Anubis
            // King Kong
            // Cybertooth
            // Pandakaï
            case PANDA_MONIUM_EVOLUTION:
                $this->applyGetEnergy($playerId, 6, $logCardType);
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->applyGetEnergy($otherPlayerId, 3, $logCardType);
                }
                break;
            case EATS_SHOOTS_AND_LEAVES_EVOLUTION:
                $outsideTokyoPlayersIds = $this->getPlayersIdsOutsideTokyo();
                $damages = [];
                foreach ($outsideTokyoPlayersIds as $outsideTokyoPlayerId) {
                    $damages[] = new Damage($outsideTokyoPlayerId, 2, $playerId, $logCardType);
                }

                $this->applyGetEnergy($playerId, 1, $logCardType);
                $this->leaveTokyo($playerId);
                return $damages;
            case BAMBOOZLE_EVOLUTION:
                $this->playBamboozleEvolution($playerId, $card);
                break;
            case BEAR_NECESSITIES_EVOLUTION:
                $this->applyLosePoints($playerId, 1, $logCardType);
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            // cyberbunny
            case STROKE_OF_GENIUS_EVOLUTION:
                $this->applyGetEnergy($playerId, $this->getPlayer($playerId)->turnEnergy, $logCardType);
                break;
            case EMERGENCY_BATTERY_EVOLUTION:
                $this->applyGetEnergy($playerId, 3, $logCardType);
                break;
            // kraken
            case HEALING_RAIN_EVOLUTION:
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            case DESTRUCTIVE_WAVE_EVOLUTION:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 2, $playerId, $logCardType);
                }
                return $damages;
            case CULT_WORSHIPPERS_EVOLUTION:
                $this->applyGetPoints($playerId, $this->getPlayer($playerId)->turnGainedHealth, $logCardType);
                break;
            case SUNKEN_TEMPLE_EVOLUTION:
                $this->applyGetHealth($playerId, 3, $logCardType, $playerId);
                $this->applyGetEnergy($playerId, 3, $logCardType);
                $this->removeCard($playerId, $card, false, 5000);
                $this->goToState(ST_NEXT_PLAYER);
                break;
            // Baby Gigazaur
            case MY_TOY_EVOLUTION:
                $this->myToyQuestion($playerId, $card);
                break;
            case NURTURE_THE_YOUNG_EVOLUTION:
                $dbResults = $this->getCollectionFromDb("SELECT `player_id` FROM `player` WHERE `player_score` > (SELECT `player_score` FROM `player` WHERE id = $playerId)");
                $playersIdsWithMorePoints = array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
                foreach ($playersIdsWithMorePoints as $playerIdWithMorePoints) {
                    $this->applyGiveSymbols([0], $playerIdWithMorePoints, $playerId, $logCardType);
                }
                break;
            case YUMMY_YUMMY_EVOLUTION:
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                $this->applyGetEnergy($playerId, 1, $logCardType);
                break;
        }
    }

    function notifNewEvolutionCard(int $playerId, EvolutionCard $evolution, $message = '', $args = []) {
        $this->notifyPlayer($playerId, "addEvolutionCardInHand", '', $args + [
            'playerId' => $playerId,
            'card' => $evolution,
        ]);    

        $this->notifyAllPlayers("addEvolutionCardInHand", $message, $args + [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    }

    function countEvolutionOfType(int $playerId, int $cardType, bool $fromTable = true, bool $fromHand = false) {
        return count($this->getEvolutionsOfType($playerId, $cardType, $fromTable, $fromHand));
    }

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

    function getGiftEvolutionOfType(int $playerId, int $cardType) {
        $cards = $this->getEvolutionsOfTypeInLocation($playerId, $cardType, 'table');
        $card = count($cards) > 0 ? $cards[0] : null;

        if ($card !== null && $card->ownerId === $playerId) {
            return null; // evolution owner is not affected by gift
        }

        return $card;
    }

    function removeEvolution(int $playerId, $card, bool $silent = false, int $delay = 0, bool $ignoreMimicToken = false) {
        $changeMaxHealth = $card->type == EATER_OF_SOULS_EVOLUTION;

        $countMothershipSupportBefore = $this->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);
        
        if ($card->type == ICY_REFLECTION_EVOLUTION) { // Mimic
            $changeMaxHealth = $this->getMimickedEvolutionType() == EATER_OF_SOULS_EVOLUTION;
            $this->removeMimicEvolutionToken($playerId);
        } else if ($card->id == $this->getMimickedEvolutionId() && !$ignoreMimicToken) {
            $this->removeMimicEvolutionToken($playerId);
        }
        $this->evolutionCards->moveCard($card->id, 'discard'.$playerId);

        if ($card->type == MY_TOY_EVOLUTION || ($card->type == ICY_REFLECTION_EVOLUTION && $this->getMimickedEvolutionType() == MY_TOY_EVOLUTION)) {
            // if My Toy is removed, reserved card is put to discard
            $reservedCards = $this->getCardsFromDb($this->cards->getCardsInLocation('reserve'.$playerId, $card->id));
            if (count($reservedCards) > 0) {
                $this->cards->moveCards(array_map(fn($reservedCard) => $reservedCard->id, $reservedCards), 'discard');
            }
        }

        if (!$silent) {
            $this->notifyAllPlayers("removeEvolutions", '', [
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
            $this->notifyAllPlayers("removeEvolutions", '', [
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
        $this->DbQuery("UPDATE player SET `player_score` = $points where `player_id` = $playerId");
        $this->notifyAllPlayers('points', clienttranslate('${player_name} left Tokyo when ${card_name} is played, and loses all [Star].'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'points' => $points,
            'card_name' => 3000 + TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION,
        ]);
    }

    function applyYinYang(int $playerId) {
        $dice = $this->getPlayerRolledDice($playerId, false, false, false);
        $YIN_YANG_OTHER_FACE = [
            1 => 3,
            2 => 4,
            3 => 1,
            4 => 2,
            5 => 6,
            6 => 5,
        ];

        $idToValue = [];
        $dieFacesBefore = '';
        $dieFacesAfter = '';

        foreach ($dice as $die) {
            $otherFace = $YIN_YANG_OTHER_FACE[$die->value];
            $this->DbQuery("UPDATE dice SET `rolled` = false, `dice_value` = ".$otherFace." where `dice_id` = ".$die->id);

            $idToValue[$die->id] = $otherFace;
            $dieFacesBefore .= $this->getDieFaceLogName($die->value, $die->type);
            $dieFacesAfter .= $this->getDieFaceLogName($otherFace, $die->type);
        }

        $message = clienttranslate('${player_name} uses ${card_name} and change ${die_face_before} to ${die_face_after}');
        $this->notifyAllPlayers("changeDice", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => 3000 + YIN_YANG_EVOLUTION,
            'dieIdsToValues' => $idToValue,
            'canHealWithDice' => $this->canHealWithDice($playerId),
            'frozenFaces' => $this->frozenFaces($playerId),
            'die_face_before' => $dieFacesBefore,
            'die_face_after' => $dieFacesAfter,
        ]);
    }

    function getFirstUnusedEvolution(int $playerId, int $evolutionType, bool $fromTable = true, bool $fromHand = false) /* returns first unused evolution, null if none */ {
        $evolutions = $this->getEvolutionsOfType($playerId, $evolutionType, $fromTable, $fromHand);
        $usedCards = $this->getUsedCard();
        return $this->array_find($evolutions, fn($card) => !in_array(3000 + $card->id, $usedCards));
    }

    function getFirstUnsetFreezeRay(int $playerId) {
        $freezeRayCards = $this->getEvolutionsOfType($playerId, FREEZE_RAY_EVOLUTION);
        $unsetFreezeRayCard = $this->array_find($freezeRayCards, fn($card) => $card->tokens == 0);
        return $unsetFreezeRayCard;
    }

    function setEvolutionTokens(int $playerId, $card, int $tokens, bool $silent = false) {
        $card->tokens = $tokens;
        $this->DbQuery("UPDATE `evolution_card` SET `card_type_arg` = $tokens where `card_id` = ".$card->id);

        if (!$silent) {
            /*TODOPU if ($card->type == MIMIC_CARD) {
                $card->mimicType = $this->getMimickedCardType(MIMIC_CARD);
            }*/
            $this->notifyAllPlayers("setEvolutionTokens", '', [
                'playerId' => $playerId,
                'card' => $card,
            ]);
        }
    }

    function playBamboozleEvolution(int $playerId, EvolutionCard $card) {
        $cardBeingBought = $this->getGlobalVariable(CARD_BEING_BOUGHT);
        $cardBeingBought->allowed = false;
        $this->setGlobalVariable(CARD_BEING_BOUGHT, $cardBeingBought);

        $buyCardArgs = $this->getArgBuyCard($cardBeingBought->playerId, false);
        $buyCardArgs['disabledIds'] = [...$buyCardArgs['disabledIds'], $cardBeingBought->cardId];

        $canBuyAnother = false;
        $playerEnergy = $this->getPlayerEnergy($cardBeingBought->playerId);
        foreach($buyCardArgs['cardsCosts'] as $cardId => $cost) {
            if (!in_array($cardId, $buyCardArgs['disabledIds']) && $cost <= $playerEnergy) {
                $canBuyAnother = true;
                break;
            }
        }

        if ($canBuyAnother) {

            $question = new Question(
                'Bamboozle',
                clienttranslate('${actplayer} must choose another card'),
                clienttranslate('${you} must choose another card'),
                [$playerId],
                ST_PLAYER_BUY_CARD,
                [ 
                    'cardBeingBought' => $cardBeingBought, 
                    'buyCardArgs' => $buyCardArgs,
                ]
            );
            $this->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
            $this->removeEvolution($playerId, $card);

            $this->jumpToState(ST_MULTIPLAYER_ANSWER_QUESTION);

        } else {
            $activePlayerId = $this->getActivePlayerId();

            $forbiddenCard = $this->getCardFromDb($this->cards->getCard($cardBeingBought->cardId));

            $this->notifyAllPlayers('log', clienttranslate('${player_name} prevents ${player_name2} to buy ${card_name}. ${player_name2} is not forced to buy another card, as player energy is too low to buy another card. '), [
                'player_name' => $this->getPlayerName($playerId),
                'player_name2' => $this->getPlayerName($activePlayerId),
                'card_name' => $forbiddenCard->type,
            ]);
    
            $this->skipCardIsBought(true);
        }
    }
    
    function applyPrecisionFieldSupport(int $playerId) {
        $topCard = $this->getCardsFromDb($this->cards->getCardsOnTop(1, 'deck'))[0];

        if ($topCard->type > 100) {

            $this->notifyAllPlayers('log500', clienttranslate('${player_name} draws ${card_name}. This card is discarded as it is not a Keep card.'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => $topCard->type,
            ]);
            $this->cards->moveCard($topCard->id, 'discard');
            $this->applyPrecisionFieldSupport($playerId);

        } else if ($this->getCardBaseCost($topCard->type) > 4) {

            $this->notifyAllPlayers('log500', clienttranslate('${player_name} draws ${card_name}. This card is discarded as it costs more than 4[Energy].'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => $topCard->type,
            ]);
            $this->cards->moveCard($topCard->id, 'discard');
            $this->applyPrecisionFieldSupport($playerId);

        } else {
            $this->drawCard($playerId);
        }
    }

    function drawEvolution(int $playerId) {
        $card = $this->pickEvolutionCards($playerId, 1)[0];

        $this->evolutionCards->moveCard($card->id, 'hand', $playerId);

        $this->notifNewEvolutionCard($playerId, $card);

        $this->incStat(1, 'picked'.$this->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);
    }

    function getEvolutionFromDiscard(int $playerId, int $evolutionId) {
        $card = $this->getEvolutionCardById($evolutionId);

        $this->evolutionCards->moveCard($card->id, 'hand', $playerId);

        $this->notifNewEvolutionCard($playerId, $card);

        $this->incStat(1, 'picked'.$this->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);
    }

    function getHighlightedEvolutions(array $stepCardsTypes) {
        $cards = [];

        foreach ($stepCardsTypes as $stepCardsType) {
            $stepCards = $this->getEvolutionCardsByType($stepCardsType);
            foreach ($stepCards as $stepCard) {
                if ($stepCard->location == 'hand') {
                    $cards[] = $stepCard;
                }
            }
        }

        return $cards;
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
                'player_name' => $this->getPlayerName($playerId),
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
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive($otherPlayersIds, 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function applyMegaPurr(int $playerId, EvolutionCard $card) {
        $otherPlayers = array_filter($this->getOtherPlayers($playerId), fn($player) => $player->energy > 0 || $player->score > 0);
        $this->applyGiveSymbolQuestion($playerId, $card, $otherPlayers, [5, 0]);
    }

    function applyFeastOfCrows(int $playerId, EvolutionCard $card) {
        $otherPlayers = array_filter($this->getOtherPlayers($playerId), fn($player) => $player->energy > 0 || $player->score > 0 || $player->health > 0);
        $this->applyGiveSymbolQuestion($playerId, $card, $otherPlayers, [4, 0, 5]);
    }

    function applyTrickOrThreat(int $playerId, EvolutionCard $card) {
        $otherPlayers = array_filter($this->getOtherPlayers($playerId), fn($player) => $player->energy > 0 || $player->health > 0);

        if (count($otherPlayers) == 0) {
            return;
        }

        $otherPlayersIds = array_map(fn($player) => $player->id, $otherPlayers);

        $canGiveEnergy = array_map(fn($player) => $player->id, array_values(array_filter($otherPlayers, fn($player) => $player->energy > 0)));

        $question = new Question(
            'TrickOrThreat',
            /*client TODOPUHA translate*/('Other monsters must give 1[Energy] or to ${player_name} or lose 2[Heart]'),
            /*client TODOPUHA translate*/('${you} must give 1[Energy] or to ${player_name} or lose 2[Heart]'),
            [$otherPlayersIds],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'card' => $card,
                'playerId' => $playerId,
                '_args' => [ 'player_name' => $this->getPlayerName($playerId) ],
                'canGiveEnergy' => $canGiveEnergy,
            ]
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive($otherPlayersIds, 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function applyDeepDive(int $playerId) {
        if (intval($this->cards->countCardInLocation('deck')) === 0) {
            throw new \BgaUserException("No cards in deck pile");
        }

        $this->cards->shuffle('discard');
        $cards = $this->getCardsFromDb(array_slice($this->cards->getCardsInLocation('deck', null, 'location_arg'), 0, 3));

        $question = new Question(
            'DeepDive',
            clienttranslate('${actplayer} can play a card from the bottom of the deck for free'),
            clienttranslate('${you} can play a card from the bottom of the deck for free'),
            [$playerId],
            -1,
            [
                'cards' => $cards,
            ]
        );
        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function applyIcyReflection(int $playerId) {
        $enabledEvolutions = [];
        $disabledEvolutions = [];
        $playersIds = $this->getPlayersIds();
        foreach($playersIds as $pId) {
            $evolutions = $this->getEvolutionCardsByLocation('table', $pId);
            foreach($evolutions as $evolution) {
                if ($evolution->type != ICY_REFLECTION_EVOLUTION && $this->EVOLUTION_CARDS_TYPES[$evolution->type] == 1) {
                    $enabledEvolutions[] = $evolution;
                } else {
                    $disabledEvolutions[] = $evolution;
                }
            }
        }
        
        $question = new Question(
            'IcyReflection',
            clienttranslate('${player_name} must choose an Evolution card to copy'),
            clienttranslate('${you} must choose an Evolution card to copy'),
            [$playerId],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'playerId' => $playerId,
                '_args' => [ 'player_name' => $this->getPlayerName($playerId) ],
                'enabledEvolutions' => $enabledEvolutions,
                'disabledEvolutions' => $disabledEvolutions,
            ]
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function setMimickedEvolution(int $mimicOwnerId, object $card) {
        $countMothershipSupportBefore = $this->countEvolutionOfType($mimicOwnerId, MOTHERSHIP_SUPPORT_EVOLUTION);

        $mimickedCard = new \stdClass();
        $mimickedCard->card = $card;
        $mimickedCard->playerId = $card->location_arg;
        $this->setGlobalVariable(MIMICKED_CARD . ICY_REFLECTION_EVOLUTION, $mimickedCard);
        $this->notifyAllPlayers("setMimicEvolutionToken", clienttranslate('${player_name} mimics ${card_name}'), [
            'card' => $card,
            'player_name' => $this->getPlayerName($mimicOwnerId),
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
            $this->notifyAllPlayers("removeMimicEvolutionToken", '', [
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

            $this->notifyAllPlayers('ownedEvolutions', '', [
                'playerId' => $playerId,
                'evolutions' => $evolutions,
            ]);
        }
    }

    function giveFreezeRay(int $fromPlayerId, int $toPlayerId, EvolutionCard $evolution) {
        $ownerId = $evolution->ownerId;
        if ($ownerId == $fromPlayerId) {
            $this->removeEvolution($fromPlayerId, $evolution, true, false, true);
            $this->evolutionCards->moveCard($evolution->id, 'table', $toPlayerId);
            $this->playEvolutionToTable($toPlayerId, $evolution, '', $fromPlayerId);
        }
    }

    function giveBackFreezeRay(int $activePlayerId, EvolutionCard $evolution) {
        $ownerId = $evolution->ownerId;
        if ($ownerId != $activePlayerId) {
            if ($this->getPlayer($ownerId)->eliminated) {
                $this->removeEvolution($activePlayerId, $evolution);
            } else {
                $this->removeEvolution($activePlayerId, $evolution, true, false, true);
                $this->evolutionCards->moveCard($evolution->id, 'table', $ownerId);
                $this->playEvolutionToTable($ownerId, $evolution, '', $activePlayerId);
            }

            // reset freeze ray disabled symbol
            $this->setEvolutionTokens($ownerId, $evolution, 0, true);
        }
    }

    function getSuperiorAlienTechnologyTokens(int $playerId) {
        $cardsIds = $this->getGlobalVariable(SUPERIOR_ALIEN_TECHNOLOGY_TOKENS.$playerId, true);
        return $cardsIds == null ? [] : $cardsIds;
    }

    function addSuperiorAlienTechnologyToken(int $playerId, int $cardId) {
        $cardsIds = $this->getSuperiorAlienTechnologyTokens($playerId);

        if (count($cardsIds) >= 3 * $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION)) {
            throw new \BgaUserException('You can only have 3 cards with tokens.');
        }

        $cardsIds[] = $cardId;
        $this->setGlobalVariable(SUPERIOR_ALIEN_TECHNOLOGY_TOKENS.$playerId, $cardsIds);

        $card = $this->cards->getCard($cardId);
        $this->notifyAllPlayers("addSuperiorAlienTechnologyToken", '', [
            'playerId' => $playerId,
            'card' => $card,
        ]);
    }

    function askTargetAcquired(array $allDamages) {
        $activePlayerId = intval($this->getActivePlayerId());
        // if damages is a smash from active player
        if (count($allDamages) > 0 && $allDamages[0]->cardType == 0 && $allDamages[0]->damageDealerId == $activePlayerId && intval($this->getGameStateValue(TARGETED_PLAYER)) != $activePlayerId) {
            $playersIds = array_unique(array_map(fn($damage) => $damage->playerId, $allDamages));
            $playersWithTargetAcquired = array_values(array_filter($playersIds, fn($playerId) => $this->countEvolutionOfType($playerId, TARGET_ACQUIRED_EVOLUTION) > 0));

            if (count($playersWithTargetAcquired) > 0) {
                $question = new Question(
                    'TargetAcquired',
                    clienttranslate('Player with ${card_name} can give target to ${player_name}'),
                    clienttranslate('${you} can give target to ${player_name}'),
                    $playersWithTargetAcquired,
                    ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE,
                    [ 
                        'playerId' => $activePlayerId,
                        '_args' => [ 
                            'player_name' => $this->getPlayerName($activePlayerId), 
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

            if ($this->array_some($damageAmountByPlayer, fn($damageAmount) => $damageAmount > 0)) {
                $question = new Question(
                    'LightningArmor',
                    clienttranslate('Player with ${card_name} can throw dice to backfire damage'),
                    clienttranslate('${you} can throw dice to backfire damage'),
                    $playersWithLightningArmor,
                    ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE,
                    [ 
                        'damageAmountByPlayer' => $damageAmountByPlayer,
                        'damageDealerIdByPlayer' => $damageDealerIdByPlayer,
                        'playerId' => $activePlayerId,
                        '_args' => [ 
                            'player_name' => $this->getPlayerName($activePlayerId), 
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

    function electricCarrotQuestion(array $smashedPlayersIds) {
        $question = new Question(
            'ElectricCarrot',
            /*client TODODE translate(*/'Smashed players can give 1[Energy] or lose 1 extra [Heart]'/*)*/,
            /*client TODODE translate(*/'${you} can give 1[Energy] or lose 1 extra [Heart]'/*)*/,
            $smashedPlayersIds,
            ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE,
            []
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive($smashedPlayersIds, 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function myToyQuestion(int $playerId, EvolutionCard $card) {
        $question = new Question(
            'MyToy',
            /*client TODOPUBG translate(*/'${player_name} must choose a card to reserve'/*)*/,
            /*client TODOPUBG translate(*/'${you} must choose a card to reserve'/*)*/,
            [$playerId],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'playerId' => $playerId,
                '_args' => [ 'player_name' => $this->getPlayerName($playerId) ],
                'card' => $card,
            ]
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function freezeRayChooseOpponentQuestion(int $playerId, array $smashedPlayersIds, EvolutionCard $card) {
        $question = new Question(
            'FreezeRayChooseOpponent',
            clienttranslate('${player_name} must choose an opponent to give ${card_name} to'),
            clienttranslate('${you} must choose an opponent to give ${card_name} to'),
            [$playerId],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'playerId' => $playerId,
                '_args' => [ 
                    'player_name' => $this->getPlayerName($playerId),
                    'card_name' => 3000 + $card->type,
                ],
                'card' => $card,
                'smashedPlayersIds' => $smashedPlayersIds,
            ]
        );

        $this->addStackedState();
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    function applyFelineMotor(int $playerId) {
        $this->moveToTokyoFreeSpot($playerId);
        $this->setGameStateValue(PREVENT_ENTER_TOKYO, 1);
        $this->goToState($this->redirectAfterHalfMovePhase());
    }
}