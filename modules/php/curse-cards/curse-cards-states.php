<?php

namespace KOT\States;

use Bga\Games\KingOfTokyo\Objects\Context;

trait CurseCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stGiveSymbolToActivePlayer() {
        if ($this->getPlayer($this->getActivePlayerId())->eliminated) {
            $this->goToState(ST_INITIAL_DICE_ROLL);
            return;
        }

        $this->gamestate->setPlayersMultiactive([$this->anubisExpansion->getPlayerIdWithGoldenScarab()], '', true);
    }

    function stResolveDieOfFate() {
        if (!$this->anubisExpansion->isActive() || intval($this->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) == 2) { // no Die of Fate
            $this->goToState(ST_RESOLVE_DICE);
            return;
        }

        $playerId = (int)$this->getActivePlayerId();

        $damagesOrState = $this->anubisExpansion->resolveDieOfFate($playerId);

        if (gettype($damagesOrState) === 'integer') {
            if ($damagesOrState != -1) {
                $this->goToState($damagesOrState);
            }
            return;
        }

        $nextState = ST_RESOLVE_DICE;

        $playerIdWithGoldenScarab = $this->anubisExpansion->getPlayerIdWithGoldenScarab();
        if ($this->anubisExpansion->getCurseCardType() == CONFUSED_SENSES_CURSE_CARD && $playerIdWithGoldenScarab != null && $playerId != $playerIdWithGoldenScarab) {
            $nextState = ST_MULTIPLAYER_REROLL_DICE;
        }

        $this->goToState($nextState, $damagesOrState);
    }

    function stDiscardDie() {
        $playerId = $this->getActivePlayerId();
        
        if (count($this->getPlayerRolledDice($playerId, true, false, false)) == 0) {
            $this->gamestate->nextState('next');
        }
    }

    function stRerollDice() {
        $playerId = $this->anubisExpansion->getRerollDicePlayerId();

        $this->gamestate->setPlayersMultiactive([$playerId], 'end', true);
    }
}
