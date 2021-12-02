<?php

namespace KOT\States;

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
            // TODOAN
            case PHARAONIC_EGO_CURSE_CARD:
                $this->leaveTokyo($playerId);
                break;
            case ISIS_S_DISGRACE_CURSE_CARD: 
            case THOT_S_BLINDNESS_CURSE_CARD: 
            case TUTANKHAMUN_S_CURSE_CURSE_CARD: 
            case FORBIDDEN_LIBRARY_CURSE_CARD: 
            case CONFUSED_SENSES_CURSE_CARD: 
            case PHARAONIC_SKIN_CURSE_CARD:
                $this->changeGoldenScarabOwner($playerId);
                break;
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
                $this->drawCard($playerId, $logCardType);
                break;
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
                return $this->replacePlayersInTokyo($playerId);
            case GAZE_OF_THE_SPHINX_CURSE_CARD:
                $this->applyGetEnergy($playerId, 3, $logCardType);
                break;
            case SCRIBE_S_PERSEVERANCE_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolled1s = $diceCounts[1];
                if ($rolled1s > 0) {
                    $this->applyGetEnergy($playerId, $rolled1s, $logCardType);
                }
                break;
        }
    }
    
    function applySnakeEffect(int $playerId, int $cardType) { // return damages or state
        $logCardType = 1000 + $cardType;

        switch($cardType) {
            // TODOAN
            case PHARAONIC_EGO_CURSE_CARD:
                return $this->replacePlayersInTokyo($playerId);
            case ISIS_S_DISGRACE_CURSE_CARD: 
            case SET_S_STORM_CURSE_CARD:
                return [new Damage($playerId, 1, $playerId, $logCardType)];
            case THOT_S_BLINDNESS_CURSE_CARD: 
                $this->applyLoseEnergy($playerId, 2, $logCardType);
                break;
            case TUTANKHAMUN_S_CURSE_CURSE_CARD: 
                $this->applyLosePoints($playerId, 2, $logCardType);
                break;
            case RAGING_FLOOD_CURSE_CARD: 
                return ST_PLAYER_DISCARD_DIE;
                break;
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
                $this->snakeEffectDiscardKeepCard($playerId);
                break;
            case BOW_BEFORE_RA_CURSE_CARD:
                return [new Damage($playerId, 2, $playerId, $logCardType)];
            case VENGEANCE_OF_HORUS_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                if ($rolledSmashes > 0) {
                    return [new Damage($playerId, $rolledSmashes, $playerId, $logCardType)];
                } else {
                    return null;
                }
            case ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                $playersIds = $this->getPlayersIdsWithMaxColumn('player_health');
                $damages = [];
                foreach ($playersIds as $pId) {
                    $damages[] = new Damage($pId, 1, $playerId, $logCardType); // TODOAN TOCHECK confirm the player is the damage dealer ? or 0 ?
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
                $this->snakeEffectDiscardKeepCard($playerId);
                break;
            case KHEPRI_S_REBELLION_CURSE_CARD:
                $this->changeGoldenScarabOwner($playerId);
                break;
            case GAZE_OF_THE_SPHINX_CURSE_CARD:
                $this->applyLoseEnergy($playerId, 3, $logCardType);
                break;
            case SCRIBE_S_PERSEVERANCE_CURSE_CARD:
                $first1die = $this->getFirstDieOfValue($playerId, 1);
                if ($first1die != null) {
                    $this->applyDiscardDie($first1die->id);
                }
                break;

        }

        return null;
    }

    function snakeEffectDiscardKeepCard(int $playerId) {
        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $keepCards = array_values(array_filter($cards, function($card) { return $card->type < 100; }));
        $count = count($keepCards);
        if ($count > 1) {
            $this->jumpToState(ST_PLAYER_DISCARD_KEEP_CARD);
        } else if ($count === 1) {
            $this->applyDiscardKeepCard($playerId, $keepCards[0]);
        }
    }

    function applyDiscardDie(int $dieId) {
        self::DbQuery("UPDATE dice SET `discarded` = true WHERE `dice_id` = $dieId");

        $die = $this->getDieById($dieId);

        self::notifyAllPlayers("discardedDie", /*client TODOAN translate(*/'Die ${dieFace} is discarded'/*)*/, [
            'die' => $die,
            'dieFace' => $this->getDieFaceLogName($die->value, $die->type),
        ]);
    }

    function applyDiscardKeepCard(int $playerId, object $card) {

        self::notifyAllPlayers("discardedDie", /*client TODOAN translate(*/'${player_name} discards ${card_name}'/*)*/, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $card->type,
        ]);
        
        $this->removeCard($playerId, $card);
    }
}