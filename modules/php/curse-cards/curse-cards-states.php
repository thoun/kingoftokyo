<?php

namespace KOT\States;

trait CurseCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stGiveSymbolToActivePlayer() {
        if ($this->getPlayer($this->getActivePlayerId())->eliminated) {
            $this->goToState(ST_INITIAL_DICE_ROLL);
            return;
        }

        $this->gamestate->setPlayersMultiactive([$this->getPlayerIdWithGoldenScarab()], '', true);
    }

    function stResolveDieOfFate() {
        if (!$this->isAnubisExpansion() || intval($this->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) == 2) { // no Die of Fate
            $this->gamestate->nextState('next');
            return;
        }

        $playerId = $this->getActivePlayerId();

        $dieOfFate = $this->getDieOfFate();

        $damagesOrState = null;
        $cardType = $dieOfFate->value > 1 ? $this->getCurseCardType() : null;
        switch($dieOfFate->value) {
            case 1: 
                $this->changeCurseCard($playerId);

                $this->incStat(1, 'dieOfFateEye', $playerId);
                break;
            case 2:
                $this->notifyAllPlayers('dieOfFateResolution', clienttranslate('Die of fate is on [dieFateRiver], ${card_name} is kept (with no effect except permanent effect)'), [
                    'card_name' => 1000 + $cardType,
                ]);

                $this->incStat(1, 'dieOfFateRiver', $playerId);
                break;
            case 3:
                $this->notifyAllPlayers('dieOfFateResolution', clienttranslate('Die of fate is on [dieFateSnake], Snake effect of ${card_name} is applied'), [
                    'card_name' => 1000 + $cardType,
                ]);

                $this->incStat(1, 'dieOfFateSnake', $playerId);

                $damagesOrState = $this->applySnakeEffect($playerId, $cardType);
                break;
            case 4:
                $this->notifyAllPlayers('dieOfFateResolution', clienttranslate('Die of fate is on [dieFateAnkh], Ankh effect of ${card_name} is applied'), [
                   'card_name' => 1000 + $cardType,
                ]);

                $this->incStat(1, 'dieOfFateAnkh', $playerId);

                $damagesOrState = $this->applyAnkhEffect($playerId, $cardType);
                break;
        }

        if (gettype($damagesOrState) === 'integer') {
            if ($damagesOrState != -1) {
                $this->goToState($damagesOrState);
            }
            return;
        }

        $nextState = ST_RESOLVE_DICE;
        $playerIdWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();
        if ($this->getCurseCardType() == CONFUSED_SENSES_CURSE_CARD && $playerIdWithGoldenScarab != null && $playerId != $playerIdWithGoldenScarab) {
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
        $playerId = $this->getRerollDicePlayerId();

        $this->gamestate->setPlayersMultiactive([$playerId], 'end', true);
    }
}
