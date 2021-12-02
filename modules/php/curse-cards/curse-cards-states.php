<?php

namespace KOT\States;

trait CurseCardsStateTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stGiveSymbolToActivePlayer() {
        $this->gamestate->setPlayersMultiactive([$this->getPlayerIdWithGoldenScarab()], '', true);
    }

    function stResolveDieOfFate() {
        if (!$this->isAnubisExpansion() || intval($this->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) == 2) { // no Die of Fate
            $this->gamestate->nextState('next');
            return;
        }

        $playerId = self::getActivePlayerId();

        $dieOfFate = $this->getDieOfFate();

        $damages = null;
        $cardType = $dieOfFate->value > 1 ? $this->getCurseCardType() : null;
        switch($dieOfFate->value) {
            case 1: 
                $this->changeCurseCard();
                break;
            case 2:
                self::notifyAllPlayers('dieOfFateResolution', /*client TODOAN translate(*/'Die of fate is on [dieFateRiver], ${card_name} is kept (with no effect except permanent effect)'/*)*/, [
                    'card_name' => 1000 + $cardType,
                ]);
                break;
            case 3:
                self::notifyAllPlayers('dieOfFateResolution', /*client TODOAN translate(*/'Die of fate is on [dieFateSnake], Snake effect of ${card_name} is applied'/*)*/, [
                    'card_name' => 1000 + $cardType,
                ]);
                $damagesOrState = $this->applySnakeEffect($playerId, $cardType);
                if (gettype($damagesOrState) === 'integer') {
                    $this->jumpToState($damagesOrState);
                    return;
                }
                break;
            case 4:
                self::notifyAllPlayers('dieOfFateResolution', /*client TODOAN translate(*/'Die of fate is on [dieFateAnkh], Ankh effect of ${card_name} is applied'/*)*/, [
                   'card_name' => 1000 + $cardType,
                ]);
                $this->applyAnkhEffect($playerId, $cardType);
                break;
        }

        $redirects = false;
        if ($damages != null && count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, ST_RESOLVE_DICE);
        }

        if (!$redirects && $this->gamestate->state()['name'] === 'resolveDieOfFate') { // in case draw cards or other die of fate state already did redirection
            $this->gamestate->nextState('next');
        }
    }

    function stDiscardDie() {
        $playerId = self::getActivePlayerId();
        
        if (count($this->getPlayerRolledDice($playerId, true, false, false)) == 0) {
            $this->gamestate->nextState('next');
        }
    }
}
