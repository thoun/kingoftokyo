<?php

namespace KOT\States;

trait PlayerTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */

    function endTurn() {
        $this->gamestate->nextState('endTurn');
    }

    function stayInTokyo() {
        $playerId = self::getCurrentPlayerId();

        self::notifyAllPlayers("stayInTokyo", clienttranslate('${player_name} chooses to stay in Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function actionLeaveTokyo() {
        $playerId = self::getCurrentPlayerId();

        $this->leaveTokyo($playerId);
    
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stStartTurn() {
        $playerId = self::getActivePlayerId();

        self::setGameStateValue('damageDoneByActivePlayer', 0);

        // apply monster effects

        // battery monster
        if ($this->countCardOfType($playerId, 28) > 0) { // TODO what if mimick
            $this->applyBatteryMonster($playerId);
        }

        // apply in tokyo at start

        if ($this->inTokyo($playerId)) {
            // start turn in tokyo
            $incScore = 2;

            $this->applyGetPointsIgnoreCards($playerId, $incScore, -1);
            self::notifyAllPlayers('points', _('${player_name} starts turn in Tokyo and wins ${deltaPoints} [Star]'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'points' => $this->getPlayerScore($playerId),
                'deltaPoints' => $incScore,
            ]);

            // urbavore
            $countUrbavore = $this->countCardOfType($playerId, 46);
            if ($countUrbavore > 0) {
                $this->applyGetPoints($playerId, $countUrbavore, 46);
            }
        }

        // throw dices

        self::setGameStateValue('throwNumber', 1);
        self::DbQuery( "UPDATE dice SET `dice_value` = 0, `locked` = false" );

        $this->throwDices($playerId);

        $this->gamestate->nextState('throw');
    }

    function stLeaveTokyo() {
        $this->gamestate->setPlayersMultiactive($this->getPlayersIdsInTokyo(), 'resume');
    }

    function stEnterTokyo() {
        $playerId = self::getActivePlayerId();
        if ($this->isTokyoEmpty(false)) {
            $this->moveToTokyo($playerId, false);
        } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
            $this->moveToTokyo($playerId, true);
        }

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('next');
        }
    }

    function stEndTurn() {
        $playerId = self::getActivePlayerId();

        // apply end of turn effects

        // rooting for the underdog
        // TOCHECK is it applied before other end of turn monsters (it may change the fewest Stars) ? considered Yes
        $countRootingForTheUnderdog = $this->countCardOfType($playerId, 39);
        if ($countRootingForTheUnderdog > 0 && $this->isFewestStars($playerId)) {
            $this->applyGetPoints($playerId, $countRootingForTheUnderdog, 39);
        }

        // energy hoarder
        $countEnergyHoarder = $this->countCardOfType($playerId, 11);
        if ($countEnergyHoarder > 0) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
            $points = floor($playerEnergy / 6);
            $this->applyGetPoints($playerId, $points * $countEnergyHoarder, 11);
        }

        // herbivore
        $countHerbivore = $this->countCardOfType($playerId, 21);
        if ($countHerbivore > 0 && intval(self::getGameStateValue('damageDoneByActivePlayer')) == 0) {
            $this->applyGetPoints($playerId, $countHerbivore, 21);
        }

        // solar powered
        $countSolarPowered = $this->countCardOfType($playerId, 42);
        if ($countSolarPowered > 0 && $this->getPlayerEnergy($playerId) == 0) {
            $this->applyGetEnergy($playerId, $countSolarPowered, 42);
        }

        // remove discard cards

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));
        $discardCards = array_filter($cards, function($card) { return $card->type >= 100; });
        $discardCardsIds = array_map(function ($card) { return $card->id; }, $discardCards);
        $this->cards->moveCards($discardCardsIds, 'discard');

        $this->gamestate->nextState('nextPlayer');
    }

    function stNextPlayer() {        
        $player_id = self::getActivePlayerId();

        self::incStat(1, 'turns_number');
        self::incStat(1, 'turns_number', $player_id);

        if (intval($this->getGameStateValue('playAgainAfterTurn')) == 1) { // extra turn for current player              
            $this->setGameStateValue('playAgainAfterTurn', 0);
        } else {
            $player_id = self::activeNextPlayer();
        }
        self::giveExtraTime($player_id);

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }
}
