<?php

namespace KOT\States;

require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Damage;

trait PlayerTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function isInvincible(int $playerId) {
        return array_search($playerId, $this->getGlobalVariable('UsedWings', true)) !== false;
    }

    function setInvincible(int $playerId) {        
        $usedWings = $this->getGlobalVariable('UsedWings', true);
        $usedWings[] = $playerId;
        $this->setGlobalVariable('UsedWings', $usedWings);
    }

    function isFewestStars(int $playerId) {
        $sql = "SELECT count(*) FROM `player` where `player_id` = $playerId AND `player_score` = (select min(`player_score`) from `player`) AND (SELECT count(*) FROM `player` where `player_score` = (select min(`player_score`) from `player`)) = 1";
        return intval(self::getUniqueValueFromDB($sql)) > 0;
    }

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

        // Jets
        $countJets = $this->countCardOfType($playerId, 24);
        if ($countJets > 0) {
            $delayedDamage = intval($this->getGameStateValue('damageForJetsIfStayingInTokyo'));
            $this->applyDamage($playerId, $delayedDamage, 0, 0, self::getActivePlayerId()); // TODO replace by resolveDamages but in Multi-player ?
        }
        
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, "resume");
    }

    function actionLeaveTokyo() {
        $playerId = self::getCurrentPlayerId();

        $this->leaveTokyo($playerId);
        
        // burrowing
        $countBurrowing = $this->countCardOfType($playerId, 6);
        if ($countBurrowing > 0) {
            self::setGameStateValue('loseHeartEnteringTokyo', $countBurrowing);
        }
    
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
        self::setGameStateValue('energyDrinks', 0);
        self::setGameStateValue('madeInALabCard', 0);
        $this->resetUsedCards();
        $this->setGlobalVariable('UsedWings', []);

        // apply monster effects

        // battery monster
        $batteryMonsterCards = $this->getCardsOfType($playerId, 28);
        foreach($batteryMonsterCards as $batteryMonsterCard) {
            $this->applyBatteryMonster($playerId, $batteryMonsterCard);
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

        // throw dice

        self::setGameStateValue('throwNumber', 1);
        self::DbQuery("UPDATE dice SET `dice_value` = 0, `locked` = false, `rolled` = true");

        $this->throwDice($playerId);

        if ($this->canChangeMimickedCard()) {
            $this->gamestate->nextState('changeMimickedCard');
        } else {
            $this->gamestate->nextState('throw');
        }
    }

    function stLeaveTokyo() {
        $this->gamestate->setPlayersMultiactive($this->getPlayersIdsInTokyo(), 'resume');
    }

    
    function stEnterTokyoApplyBurrowing() {
        $playerId = self::getActivePlayerId();

        $redirects = false;
        // burrowing
        $loseHeartForBurrowing = intval(self::getGameStateValue('loseHeartEnteringTokyo'));
        if ($loseHeartForBurrowing > 0) {
            $damage = new Damage($playerId, $loseHeartForBurrowing, 0, 6);
            $redirects = $this->resolveDamages([$damage], 'enterTokyoAfterBurrowing');

            self::setGameStateValue('loseHeartEnteringTokyo', 0);
        }        

        if (!$redirects) {
            $this->gamestate->nextState('next');
        }
    }

        
    function stEnterTokyo() {
        $playerId = self::getActivePlayerId();

        if ($this->getPlayerHealth($playerId) > 0) { // enter only if burrowing doesn't kill player
            if ($this->isTokyoEmpty(false)) {
                $this->moveToTokyo($playerId, false);
            } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
                $this->moveToTokyo($playerId, true);
            }
        }

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('next');
        }
    }

    function stResolveEndTurn() {
        $playerId = self::getActivePlayerId();

        // apply end of turn effects (after Selling Cards)

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

        // apply poison
        $redirects = false;
        $countPoison = $this->getPlayerPoisonTokens($playerId);
        if ($countPoison > 0) {
            $damage = new Damage($playerId, $countPoison, 0, 35);
            $redirects = $this->resolveDamages([$damage], 'endTurn');
        }

        if (!$redirects) {
            $this->gamestate->nextState('endTurn');
        }
    }

    function stEndTurn() {
        //$playerId = self::getActivePlayerId();

        // clean game state values
        $this->setGameStateValue('lessDiceForNextTurn', 0);

        // poison may eliminate players
        /*$endGame = $this->eliminatePlayers($playerId);

        if ($endGame) {
            $this->gamestate->nextState('endGame');
        } else {*/
            $this->gamestate->nextState('nextPlayer');
        /*}*/
    }

    function stNextPlayer() {        
        $playerId = self::getActivePlayerId();

        self::incStat(1, 'turns_number');
        self::incStat(1, 'turns_number', $playerId);

        $anotherTimeWithCard = 0;

        if (intval($this->getGameStateValue('playAgainAfterTurnOneLessDie')) == 1) { // extra turn for current player with one less die
            $anotherTimeWithCard = 16;
            $this->setGameStateValue('playAgainAfterTurnOneLessDie', 0);
            $this->setGameStateValue('lessDiceForNextTurn', intval($this->getGameStateValue('lessDiceForNextTurn')) + 1);
            
            // TOCHECK if we chain Freeze Time, is it always just one less dice or are they added ? Considered Juste one less
            // TOCHECK can Freeze Time be added to Frenzy ? Considered Yes and Freeze Time before Frenzy
        } else {
            $this->setGameStateValue('lessDiceForNextTurn', 0);
        }

        if ($anotherTimeWithCard == 0 && intval($this->getGameStateValue('playAgainAfterTurn')) == 1) { // extra turn for current player
            $anotherTimeWithCard = 109;
            $this->setGameStateValue('playAgainAfterTurn', 0);            
        }
        
        if ($anotherTimeWithCard > 0) {
            self::notifyAllPlayers('playAgain', _('${player_name} take another turn with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $this->getCardName($anotherTimeWithCard),
            ]);
        } else {
            $playerId = self::activeNextPlayer();
        }

        self::giveExtraTime($playerId);

        if ($this->getMaxPlayerScore() >= MAX_POINT) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
    }
}
