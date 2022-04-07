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

    function addStackedState() {
        $states = $this->getStackedStates();
        $states[] = $this->gamestate->state_id();
        $this->setGlobalVariable(STACKED_STATES, $states);
    }

    function removeStackedStateAndRedirect() {
        $states = $this->getStackedStates();
        if (count($states) < 1) {
            throw new \Exception('No stacked state to remove');
        }
        $this->goToState(array_pop($states));
        $this->setGlobalVariable(STACKED_STATES, $states);
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
    }

    function getEvolutionCardFromDb(array $dbCard) {
        if (!$dbCard || !array_key_exists('id', $dbCard)) {
            throw new \Error('card doesn\'t exists '.json_encode($dbCard));
        }
        if (!$dbCard || !array_key_exists('location', $dbCard)) {
            throw new \Error('location doesn\'t exists '.json_encode($dbCard));
        }
        return new EvolutionCard($dbCard);
    }

    function getEvolutionCardsFromDb(array $dbCards) {
        return array_map(fn($dbCard) => $this->getEvolutionCardFromDb($dbCard), array_values($dbCards));
    }

    function pickEvolutionCards(int $playerId, int $number = 2) {
        $remainingInDeck = intval($this->evolutionCards->countCardInLocation('deck'.$playerId));
        if ($remainingInDeck >= $number) {
            return $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOnTop($number, 'deck'.$playerId));
        } else {
            $cards = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOnTop($remainingInDeck, 'deck'.$playerId));

            $this->evolutionCards->moveAllCardsInLocation('discard'.$playerId, 'deck'.$playerId);
            $this->evolutionCards->shuffle('deck'.$playerId);

            $cards = array_merge(
                $cards,
                $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOnTop($number - $remainingInDeck, 'deck'.$playerId))
            );
            return $cards;
        }
    }

    function canPlayEvolution(int $cardType, int $playerId) {
        $stateId = intval($this->gamestate->state_id());

        if ($stateId < 17) {
            return false;
        }

        // cards to player before starting turn
        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_BEFORE_START) && $stateId != ST_PLAYER_BEFORE_START_TURN) {
            return false;
        }

        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_AT_HALF_MOVE_PHASE) && $stateId != ST_MULTIPLAYER_HALF_MOVE_PHASE) {
            return false;
        }

        // cards to player when card is bought
        if (in_array($cardType, $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT) && $stateId != ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT) {
            if ($cardType == BAMBOOZLE_EVOLUTION) {
                return $playerId != intval($this->getActivePlayerId());
            }
            return false;
        }

        switch($cardType) {
            case NINE_LIVES_EVOLUTION:
            case SIMIAN_SCAMPER_EVOLUTION:
            case DETACHABLE_TAIL_EVOLUTION:
                return false;
            case FELINE_MOTOR_EVOLUTION:
                $startedTurnInTokyo = $this->getGlobalVariable(STARTED_TURN_IN_TOKYO, true);
                if (in_array($playerId, $startedTurnInTokyo)) {
                    throw new \BgaUserException(self::_("You started your turn in Tokyo"));
                }
                break;
            case TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION:
                return $this->inTokyo($playerId); // TODOPU use only when you enter Tokyo
            case EATS_SHOOTS_AND_LEAVES_EVOLUTION:
                return $this->inTokyo($playerId); // TODOPU use only when you enter Tokyo
            case JUNGLE_FRENZY_EVOLUTION: // TODOPU use only after move to Tokyo if you didn't enter
                return $playerId == intval($this->getActivePlayerId()) && !$this->inTokyo($playerId) && $this->isDamageDealtThisTurn($playerId);
            case TUNE_UP_EVOLUTION:
                return !$this->inTokyo($playerId);
            case ICY_REFLECTION_EVOLUTION:
                $playersIds = $this->getPlayersIds();
                foreach($playersIds as $playerId) {
                    $evolutions = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('table', $playerId));
                    if ($this->array_some($evolutions, fn($evolution) => $evolution->type != ICY_REFLECTION_EVOLUTION && $this->EVOLUTION_CARDS_TYPES[$evolution->type] == 1)) {
                        return true; // if there is a permanent evolution card in table
                    }
                }
                return false;
        }

        return true;
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
    }

    function canPlayStepEvolution(array $playersIds, array $stepCardsIds) {
        $playersIds = $this->isPowerUpMutantEvolution() ? $this->getPlayersIds(true) : $playersIds;
        $dbResults = $this->getCollectionFromDb("SELECT distinct player_monster FROM player WHERE player_id IN (".implode(',', $playersIds).")");
        $monsters = array_map(fn($dbResult) => intval($dbResult['player_monster']), array_values($dbResults));

        // TODOPU ignore cards on table, or on discard?
        $stepCardsMonsters = array_values(array_unique(array_map(fn($cardId) => floor($cardId / 10), $stepCardsIds)));

        return $this->array_some($monsters, fn($monster) => in_array($monster, $stepCardsMonsters));
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
            // Cyber Kitty
            case MEGA_PURR_EVOLUTION:
                $this->applyMegaPurr($playerId);
                break;
            case ELECTRO_SCRATCH_EVOLUTION:
                $otherPlayersIds = $this->getOtherPlayersIds($playerId);
                $damages = [];
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $damages[] = new Damage($otherPlayerId, 1, $playerId, 3000 + ELECTRO_SCRATCH_EVOLUTION);
                }
                return $damages;
            case FELINE_MOTOR_EVOLUTION:
                $this->moveToTokyoFreeSpot($playerId);
                $this->setGameStateValue(PREVENT_ENTER_TOKYO, 1);
                break;
            // The King
            case MONKEY_RUSH_EVOLUTION:
                $this->moveToTokyoFreeSpot($playerId);
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
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
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
            // Pumpkin Jack
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
                $this->leaveTokyo($playerId, false); // TODOPU confirm
                return $damages;
            case BAMBOOZLE_EVOLUTION:
                $this->playBamboozleEvolution($playerId, $card);
                break;
            case BEAR_NECESSITIES_EVOLUTION:
                $this->applyLosePoints($playerId, 1, $logCardType);
                $this->applyGetEnergy($playerId, 2, $logCardType);
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            case BAMBOO_SUPPLY_EVOLUTION:
                // TODOPU $this->goToState(ST_START_TURN);
                break;
            // cyberbunny
            // kraken
            // Baby Gigazaur
        }
    }

    function notifNewEvolutionCard(int $playerId, EvolutionCard $card, $message = '') {
        $this->notifyPlayer($playerId, "addEvolutionCardInHand", '', [
            'playerId' => $playerId,
            'card' => $card,
        ]);    

        $this->notifyAllPlayers("addEvolutionCardInHand", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    }

    function countEvolutionOfType(int $playerId, int $cardType, bool $fromTable = true, bool $fromHand = false) {
        return count($this->getEvolutionsOfType($playerId, $cardType, $fromTable, $fromHand));
    }

    function getEvolutionsOfTypeInLocation(int $playerId, int $cardType, string $location) {
        $evolutions = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfTypeInLocation($cardType, null, $location, $playerId));
        
        if ($this->EVOLUTION_CARDS_TYPES[$cardType] == 1 && $cardType != ICY_REFLECTION_EVOLUTION) { // don't search for mimick mimicking itself, nor temporary/surprise evolutions
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

    function removeEvolution(int $playerId, $card, bool $silent = false, int $delay = 0, bool $ignoreMimicToken = false) {
        $changeMaxHealth = $card->type == EVEN_BIGGER_CARD; // TODOPU

        $countMothershipSupportBefore = $this->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);
        
        if ($card->type == ICY_REFLECTION_EVOLUTION) { // Mimic
            $changeMaxHealth = false;// TODOPU $this->getMimickedEvolutionType() == EATER_OF_SOULS_EVOLUTION;
            $this->removeMimicEvolutionToken($playerId);
        } else if ($card->id == $this->getMimickedEvolutionId() && !$ignoreMimicToken) {
            $this->removeMimicEvolutionToken($playerId);
        }
        $this->evolutionCards->moveCard($card->id, 'discard'.$playerId);

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
        $cards = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfType($type));
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
        $this->notifyAllPlayers('points', /*client TODOPU translate(*/'${player_name} left Tokyo when ${card_name} is played, and loses all [Star].'/*)*/, [
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

        foreach ($dice as $die) {
            $otherFace = $YIN_YANG_OTHER_FACE[$die->value];
            $this->DbQuery("UPDATE dice SET `rolled` = false, `dice_value` = ".$otherFace." where `dice_id` = ".$die->id);

            $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
            $this->notifyAllPlayers("changeDie", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => 3000 + YIN_YANG_EVOLUTION,
                'dieId' => $die->id,
                'canHealWithDice' => $this->canHealWithDice($playerId),
                'toValue' => $otherFace,
                'die_face_before' => $this->getDieFaceLogName($die->value, $die->type),
                'die_face_after' => $this->getDieFaceLogName($otherFace, $die->type),
            ]);
        }
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

        $question = new Question(
            'Bamboozle',
            /* client TODOPU translate(*/'${actplayer} must choose another card'/*)*/,
            /* client TODOPU translate(*/'${you} must choose another card'/*)*/,
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
    }
    
    function applyPrecisionFieldSupport(int $playerId) {
        $topCard = $this->getCardsFromDb($this->cards->getCardsOnTop(1, 'deck'))[0];

        if ($topCard->type > 100) {

            $this->notifyAllPlayers('log500', /*client TODOPU translate(*/'${player_name} draws ${card_name}. This card is discarded as it is not a Keep card.'/*)*/, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => $topCard->type,
            ]);
            $this->cards->moveCard($topCard->id, 'discard');
            $this->applyPrecisionFieldSupport($playerId);

        } else if ($this->CARD_COST[$topCard->type] > 4) {

            $this->notifyAllPlayers('log500', /*client TODOPU translate(*/'${player_name} draws ${card_name}. This card is discarded as it costs more than 4[Energy].'/*)*/, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'card_name' => $topCard->type,
            ]);
            $this->cards->moveCard($topCard->id, 'discard');
            $this->applyPrecisionFieldSupport($playerId);

        } else {
            // TODOPU handle mimic
            $this->drawCard($playerId, false);
        }
    }

    function drawEvolution(int $playerId) {
        $card = $this->pickEvolutionCards($playerId, 1)[0];

        $this->evolutionCards->moveCard($card->id, 'hand', $playerId);

        $this->notifNewEvolutionCard($playerId, $card);
    }

    function getHighlightedEvolutions(array $stepCardsTypes) {
        $cards = [];

        foreach ($stepCardsTypes as $stepCardsType) {
            $stepCards = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfType($stepCardsType));
            foreach ($stepCards as $stepCard) {
                if ($stepCard->location == 'hand') {
                    $cards[] = $stepCard;
                }
            }
        }

        return $cards;
    }

    function applyMegaPurr(int $playerId) {
        $otherPlayers = array_filter($this->getOtherPlayers($playerId), fn($player) => $player->energy > 0 || $player->score > 0);

        if (count($otherPlayers) == 0) {
            return;
        }

        $otherPlayersIds = array_map(fn($player) => $player->id, $otherPlayers);

        $canGiveEnergy = array_map(fn($player) => $player->id, array_values(array_filter($otherPlayers, fn($player) => $player->energy > 0)));
        $canGiveStar = array_map(fn($player) => $player->id, array_values(array_filter($otherPlayers, fn($player) => $player->score > 0)));

        $question = new Question(
            'MegaPurr',
            /* client TODOPU translate(*/'Other monsters must give [Energy] or [Star] to ${player_name}'/*)*/,
            /* client TODOPU translate(*/'${you} must give [Energy] or [Star] to ${player_name}'/*)*/,
            [$otherPlayersIds],
            ST_AFTER_ANSWER_QUESTION,
            [ 
                'playerId' => $playerId,
                '_args' => [ 'player_name' => $this->getPlayerName($playerId) ],
                'canGive5' => $canGiveEnergy,
                'canGive0' => $canGiveStar,
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
            /* client TODOPU translate(*/'${actplayer} can play a card from the bottom of the deck for free'/*)*/,
            /* client TODOPU translate(*/'${you} can play a card from the bottom of the deck for free'/*)*/,
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
            $evolutions = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('table', $pId));
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
            /* client TODOPU translate(*/'${player_name} must choose an Evolution card to copy'/*)*/,
            /* client TODOPU translate(*/'${you} must choose an Evolution card to copy'/*)*/,
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
            $mimicCard = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfType(ICY_REFLECTION_EVOLUTION))[0];
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

        $mimicCard = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfType(ICY_REFLECTION_EVOLUTION))[0]; // TODOWI if tile mimic mimic

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
            $evolutions = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('deck'.$playerId));
            $ids = array_map(fn($evolution) => $evolution->id, $evolutions);
            if(count($ids) > 0) {
                $this->DbQuery("UPDATE `evolution_card` SET `owner_id` = $playerId where `card_id` IN (" . implode(',', $ids) . ")");
            }
        }
    }

    function getEvolutionOwnerId(EvolutionCard $evolution) {
        return intval($this->getUniqueValueFromDB("SELECT `owner_id` FROM `evolution_card` WHERE `card_id` = $evolution->id"));
    }

    function giveFreezeRay(int $fromPlayerId, int $toPlayerId) {
        $evolution = $this->getEvolutionsOfType($fromPlayerId, FREEZE_RAY_EVOLUTION)[0];
        $this->removeEvolution($fromPlayerId, $evolution, true, false, true);
        $this->evolutionCards->moveCard($evolution->id, 'table', $toPlayerId);
        $this->playEvolutionToTable($toPlayerId, $evolution, '', $fromPlayerId);
    }

    function giveBackFreezeRay(int $activePlayerId, EvolutionCard $evolution) {
        $ownerId = $this->getEvolutionOwnerId($evolution);
        if ($ownerId != $activePlayerId) {
            $this->removeEvolution($activePlayerId, $evolution, true, false, true);
            $this->evolutionCards->moveCard($evolution->id, 'table', $ownerId);
            $this->playEvolutionToTable($ownerId, $evolution, '', $activePlayerId);

            // reset freeze ray disabled symbol
            $this->setEvolutionTokens($ownerId, $evolution, 0, true);
        }
    }
}