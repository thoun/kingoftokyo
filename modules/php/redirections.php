<?php

namespace KOT\States;

require_once(__DIR__.'/objects/question.php');

use KOT\Objects\Question;

trait RedirectionTrait {
    function goToState(int $nextStateId, /*Damage[] | null*/ $damages = null) {
        $redirects = false;
        
        if ($damages != null && count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, $nextStateId);
        }

        if (!$redirects) {
            if ($nextStateId == -1) {
                $this->removeStackedStateAndRedirect();
            } else {
                $this->jumpToState($nextStateId);
            }
        }

        return $redirects;
    }

    function redirectAfterStart() {
        if ($this->canPickMonster()) {
            return ST_PLAYER_PICK_MONSTER; 
        } else {
            return $this->redirectAfterPickMonster();
        }
    }

    function redirectAfterPickMonster() {
        if ($this->isPowerUpMutantEvolution()) {
            return ST_NEXT_PICK_EVOLUTION_DECK; 
        } else {
            return $this->redirectAfterPickEvolutionDeck();
        }
    }

    function redirectAfterPickEvolutionDeck() {
        if ($this->isHalloweenExpansion() || $this->isPowerUpExpansion()) {
            return ST_PLAYER_CHOOSE_INITIAL_CARD; 
        } else {
            return $this->redirectAfterChooseInitialCard();
        }
    }

    function redirectAfterChooseInitialCard() {
        return ST_START_GAME; 
    }

    function redirectAfterBeforeStartTurn(int $playerId) {
        if ($this->isPowerUpExpansion()) {
            $unusedBambooSupplyCard = $this->getFirstUnusedBambooSupply($playerId);
            if ($unusedBambooSupplyCard != null) {
                $question = new Question(
                    'BambooSupply',
                    /* client TODOPU translate(*/'${actplayer} can put or take [Energy]'/*)*/,
                    /* client TODOPU translate(*/'${you} can put or take [Energy]'/*)*/,
                    [$playerId],
                    ST_START_TURN,
                    [ 'canTake' => $unusedBambooSupplyCard->tokens > 0 ]
                );
                $this->setQuestion($question);
                $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

                return ST_MULTIPLAYER_ANSWER_QUESTION;
            }
        }

        return ST_START_TURN;
    }

    function redirectAfterStartTurn(int $playerId) {
        if ($this->canChangeMimickedCardWickednessTile()) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD_WICKEDNESS_TILE;
        }
        return $this->redirectAfterChangeMimickWickednessTile($playerId);
    }

    function redirectAfterChangeMimickWickednessTile(int $playerId) {
        if ($this->canChangeMimickedCard()) {
            return ST_PLAYER_CHANGE_MIMICKED_CARD;
        }
        return $this->redirectAfterChangeMimick($playerId);
    }

    function redirectAfterChangeMimick(int $playerId) {
        $playerIdWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();
        if ($this->isAnubisExpansion() && $this->getCurseCardType() == KHEPRI_S_REBELLION_CURSE_CARD && $playerIdWithGoldenScarab != null && $playerId != $playerIdWithGoldenScarab) {
            return ST_MULTIPLAYER_GIVE_SYMBOL_TO_ACTIVE_PLAYER;
        }
        return ST_INITIAL_DICE_ROLL;
    }

    function redirectAfterResolveDice() {
        return ST_RESOLVE_NUMBER_DICE;
    }
    
}
