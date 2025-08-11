<?php

namespace KOT\States;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;

trait CurseCardsUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function getDieOfFate() {
        return $this->getDiceByType(2)[0];
    }

    function applyAnkhEffect(int $playerId, CurseCard $curseCard) {
        return $this->curseCards->applyAnkhEffect($curseCard, new Context($this, currentPlayerId: $playerId));
    }
    
    function applySnakeEffect(int $playerId, CurseCard $curseCard) { // return damages or state
        return $this->curseCards->applySnakeEffect($curseCard, new Context($this, currentPlayerId: $playerId));
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
        if ($this->anubisExpansion->isActive()) {
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