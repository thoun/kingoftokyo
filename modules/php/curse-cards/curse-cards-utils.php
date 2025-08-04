<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');
require_once(__DIR__.'/../Objects/question.php');

use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;
use KOT\Objects\Question;

trait CurseCardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getDieOfFate() {
        return $this->getDiceByType(2)[0];
    }

    function applyAnkhEffect(int $playerId, int $cardType) {
        $logCardType = 1000 + $cardType;

        switch($cardType) {
            case PHARAONIC_EGO_CURSE_CARD:
                $this->leaveTokyo($playerId);
                break;
            case ISIS_S_DISGRACE_CURSE_CARD: 
            case THOT_S_BLINDNESS_CURSE_CARD: 
            case TUTANKHAMUN_S_CURSE_CURSE_CARD: 
            case HOTEP_S_PEACE_CURSE_CARD:
            case FORBIDDEN_LIBRARY_CURSE_CARD: 
            case CONFUSED_SENSES_CURSE_CARD: 
            case PHARAONIC_SKIN_CURSE_CARD:
                $this->changeGoldenScarabOwner($playerId);
                break;
            case RAGING_FLOOD_CURSE_CARD:
                $this->setGameStateValue(RAGING_FLOOD_EXTRA_DIE, 1);
                return ST_PLAYER_SELECT_EXTRA_DIE;
            case SET_S_STORM_CURSE_CARD:
            case BOW_BEFORE_RA_CURSE_CARD: 
            case ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            case BUILDERS_UPRISING_CURSE_CARD:
                if (!$this->inTokyo($playerId)) {
                    $this->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 1);
                }
                break;
            case INADEQUATE_OFFERING_CURSE_CARD:
                $this->drawCard($playerId, ST_RESOLVE_DICE);
                return -1;
            case VENGEANCE_OF_HORUS_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                if ($rolledSmashes > 0) {
                    $this->applyGetPoints($playerId, $rolledSmashes, $logCardType);
                }
                break;
            case ORDEAL_OF_THE_WEALTHY_CURSE_CARD:
                $this->applyGetPoints($playerId, 2, $logCardType);
                break;
            case ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD:
                $this->applyGetEnergy($playerId, 2, $logCardType);
                break;
            case RESURRECTION_OF_OSIRIS_CURSE_CARD:
                $this->replacePlayersInTokyo($playerId);
                break;
            case KHEPRI_S_REBELLION_CURSE_CARD:
                return ST_PLAYER_GIVE_GOLDEN_SCARAB;
            case FALSE_BLESSING_CURSE_CARD:
                $this->setGameStateValue(FALSE_BLESSING_USED_DIE, 0);
                return ST_PLAYER_REROLL_OR_DISCARD_DICE;
            case GAZE_OF_THE_SPHINX_CURSE_CARD:
                if ($this->isPowerUpExpansion()) {
                    $question = new Question(
                        'GazeOfTheSphinxAnkh',
                        clienttranslate('${actplayer} must choose to draw an Evolution card or gain 3[Energy]'),
                        clienttranslate('${you} must choose to draw an Evolution card or gain 3[Energy]'),
                        [$playerId],
                        ST_RESOLVE_DICE,
                        []
                    );
                    $this->setQuestion($question);
                    $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
                    return ST_MULTIPLAYER_ANSWER_QUESTION;
                } else {
                    $this->applyGetEnergy($playerId, 3, $logCardType);
                }
                break;
            case SCRIBE_S_PERSEVERANCE_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolled1s = $diceCounts[1];
                if ($rolled1s > 0) {
                    $this->applyGetEnergy($playerId, $rolled1s, $logCardType);
                }
                break;
            default:
                $this->curseCards->applyAnkhEffect(new Context($this, currentPlayerId: $playerId));
        }
    }
    
    function applySnakeEffect(int $playerId, int $cardType) { // return damages or state
        $logCardType = 1000 + $cardType;

        switch($cardType) {
            case PHARAONIC_EGO_CURSE_CARD:
                $this->replacePlayersInTokyo($playerId);
                break;
            case ISIS_S_DISGRACE_CURSE_CARD: 
            case SET_S_STORM_CURSE_CARD:
                return [new Damage($playerId, 1, 0, $logCardType)];
            case THOT_S_BLINDNESS_CURSE_CARD: 
                $this->applyLoseEnergy($playerId, 2, $logCardType);
                break;
            case TUTANKHAMUN_S_CURSE_CURSE_CARD: 
                $this->applyLosePoints($playerId, 2, $logCardType);
                break;
            case RAGING_FLOOD_CURSE_CARD: 
                return ST_PLAYER_DISCARD_DIE;
            case HOTEP_S_PEACE_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                if ($rolledSmashes > 0) {
                    $this->applyLoseEnergy($playerId, $rolledSmashes, $logCardType);
                }
                break;
            case BUILDERS_UPRISING_CURSE_CARD: 
                $this->applyLosePoints($playerId, 2, $logCardType);
                break;
            case INADEQUATE_OFFERING_CURSE_CARD: 
                return $this->snakeEffectDiscardKeepCard($playerId);
            case BOW_BEFORE_RA_CURSE_CARD:
                return [new Damage($playerId, 2, 0, $logCardType)];
            case VENGEANCE_OF_HORUS_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                if ($rolledSmashes > 0) {
                    return [new Damage($playerId, $rolledSmashes, 0, $logCardType)];
                } else {
                    return null;
                }
            case ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                $playersIds = $this->getPlayersIdsWithMaxColumn('player_health');
                $damages = [];
                foreach ($playersIds as $pId) {
                    $damages[] = new Damage($pId, 1, 0, $logCardType);
                }
                return $damages;
            case ORDEAL_OF_THE_WEALTHY_CURSE_CARD:
                $playersIds = $this->getPlayersIdsWithMaxColumn('player_score');
                foreach ($playersIds as $pId) {
                    $this->applyLosePoints($pId, 1, $logCardType);
                }
                break;
            case ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD:
                $playersIds = $this->getPlayersIdsWithMaxColumn('player_energy');
                foreach ($playersIds as $pId) {
                    $this->applyLoseEnergy($pId, 1, $logCardType);
                }
                break;
            case RESURRECTION_OF_OSIRIS_CURSE_CARD:
                $this->leaveTokyo($playerId);
                break;
            case FORBIDDEN_LIBRARY_CURSE_CARD: 
                return $this->snakeEffectDiscardKeepCard($playerId);
            case PHARAONIC_SKIN_CURSE_CARD:
                $playerIdWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();
                if ($playerIdWithGoldenScarab != null && $playerId != $playerIdWithGoldenScarab && count($this->argGiveSymbols()['combinations']) > 0) {
                    return ST_PLAYER_GIVE_SYMBOLS;
                }
                break;
            case KHEPRI_S_REBELLION_CURSE_CARD:
                $this->changeGoldenScarabOwner($playerId);
                break;
            case FALSE_BLESSING_CURSE_CARD:
                return ST_MULTIPLAYER_REROLL_DICE;
                break;
            case GAZE_OF_THE_SPHINX_CURSE_CARD:
                $canLoseEvolution = false;
                if ($this->isPowerUpExpansion()) {
                    $canLoseEvolution = (intval($this->evolutionCards->countCardInLocation('table', $playerId)) + intval($this->evolutionCards->countCardInLocation('hand', $playerId))) > 0;
                }
                if ($canLoseEvolution) {
                    $playerEnergy = $this->getPlayerEnergy($playerId);
                    $question = new Question(
                        'GazeOfTheSphinxSnake',
                        clienttranslate('${actplayer} must choose to discard an Evolution card (from its hand or in play) or lose 3[Energy]'),
                        clienttranslate('Click on an Evolution card (from your hand or in play) to discard it or lose 3[Energy]'),
                        [$playerId],
                        ST_RESOLVE_DICE,
                        [
                            'canLoseEnergy' => $playerEnergy >= 3,
                        ]
                    );
                    $this->setQuestion($question);
                    $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);
                    return ST_MULTIPLAYER_ANSWER_QUESTION;
                } else {
                    $this->applyLoseEnergy($playerId, 3, $logCardType);
                }
                break;
            case SCRIBE_S_PERSEVERANCE_CURSE_CARD:
                $first1die = $this->getFirstDieOfValue($playerId, 1);
                if ($first1die != null) {
                    $this->applyDiscardDie($first1die->id);
                }
                break;
            default:
                $this->curseCards->applySnakeEffect(new Context($this, currentPlayerId: $playerId));

        }

        return null;
    }

    function snakeEffectDiscardKeepCard(int $playerId) {
        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $keepCards = array_values(array_filter($cards, fn($card) => $card->type < 100));
        $count = count($keepCards);
        if ($count > 1) {
            return ST_PLAYER_DISCARD_KEEP_CARD;
        } else if ($count === 1) {
            $this->applyDiscardKeepCard($playerId, $keepCards[0]);
            return null;
        }
    }

    function getCurseCardType() {
        return $this->curseCards->getCurrent()->type;
    }

    function changeGoldenScarabOwner(int $playerId) {
        $previousOwner = $this->getPlayerIdWithGoldenScarab(true);

        if ($previousOwner == $playerId) {
            return;
        }

        $this->setGameStateValue(PLAYER_WITH_GOLDEN_SCARAB, $playerId);

        $this->notifyAllPlayers('changeGoldenScarabOwner', clienttranslate('${player_name} gets Golden Scarab'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'previousOwner' => $previousOwner,
        ]);
    }

    function getPlayerIdWithGoldenScarab(bool $ignoreElimination = false) {
        $playerId = intval($this->getGameStateValue(PLAYER_WITH_GOLDEN_SCARAB));

        if ($playerId == 0 || (!$ignoreElimination && $this->getPlayer($playerId)->eliminated)) {
            return null;
        }

        return $playerId;
    }

    function getPlayersIdsWithoutGoldenScarab() {
        $playerIds = $this->getPlayersIds();
        $playerWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();
        return array_values(array_filter($playerIds, fn($playerId) => $playerId != $playerWithGoldenScarab));
    }

    function keepAndEvolutionCardsHaveEffect() {
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == GAZE_OF_THE_SPHINX_CURSE_CARD) {
                return false;
            }
        }

        return true;
    }

    function changeAllPlayersMaxHealth() {
        $playerIds = $this->getPlayersIds();
        foreach($playerIds as $playerId) {
            $this->changeMaxHealth($playerId);
        }
    }

    function removeCursePermanentEffectOnReplace() {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == BOW_BEFORE_RA_CURSE_CARD) {
            $this->changeAllPlayersMaxHealth();
        }
    }

    function applyDiscardDie(int $dieId) {
        $this->DbQuery("UPDATE dice SET `discarded` = true WHERE `dice_id` = $dieId");

        $die = $this->getDieById($dieId);

        $this->notifyAllPlayers("discardedDie", clienttranslate('Die ${dieFace} is discarded'), [
            'die' => $die,
            'dieFace' => $this->getDieFaceLogName($die->value, $die->type),
        ]);
    }

    function applyDiscardKeepCard(int $playerId, object $card) {

        $this->notifyAllPlayers("log", clienttranslate('${player_name} discards ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $card->type,
        ]);
        
        $this->removeCard($playerId, $card);
    }

    function getRerollDicePlayerId() {
        if ($this->getCurseCardType() == FALSE_BLESSING_CURSE_CARD) {
            // player on the left
            $playersIds = $this->getPlayersIds();
            $playerIndex = array_search($this->getActivePlayerId(), $playersIds);
            $playerCount = count($playersIds);
            
            $leftPlayerId = $playersIds[($playerIndex + 1) % $playerCount];
            return $leftPlayerId;
        } else {
            // player with golden scarab
            return $this->getPlayerIdWithGoldenScarab();
        }
    }
}