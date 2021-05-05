<?php

namespace KOT\States;

require_once(__DIR__.'/../dice.php');

use KOT\Dice;

trait DicesTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////
    
    function getDices(int $number) {
        $sql = "SELECT `dice_id`, `dice_value`, `extra`, `locked` FROM dice ORDER BY dice_id limit $number";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices));
    }

    public function throwDices($playerId) {
        $dices = $this->getDices($this->getDicesNumber($playerId));

        foreach ($dices as &$dice) {
            if (!$dice->locked) {
                $dice->value = bga_rand(1, 6);
                self::DbQuery( "UPDATE dice SET `dice_value`=".$dice->value." where `dice_id`=".$dice->id );
            }
        }
        
        $throwNumber = intval(self::getGameStateValue('throwNumber')) + 1;
        $maxThrowNumber = $this->getThrowNumber($playerId);

        // force lock on last throw
        if ($throwNumber == $maxThrowNumber) {
            self::DbQuery( "UPDATE dice SET `locked` = true" );
        }
    }

    function getDicesNumber(int $playerId) {
        return 6 + $this->countExtraHead($playerId);
    }

    function resolveNumberDices(int $playerId, int $number, int $diceCount) {
        // number
        if ($diceCount >= 3) {
            $points = $number + $diceCount - 3;

            if ($number == 1 && $this->hasCardByType($playerId, 19)) {
                $points += 2;
            }

            $this->applyGetPoints($playerId, $points, true);

            self::notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} wins ${deltaPoints} with ${diceValue} dices'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'deltaPoints' => $points,
                'points' => $this->getPlayerScore($playerId),
                'diceValue' => $number,
            ]);
        }
    }

    function resolveHealthDices(int $playerId, int $diceCount) {
        if ($this->inTokyo($playerId)) {
            self::notifyAllPlayers( "resolveHealthDiceInTokyo", clienttranslate('${player_name} wins no health (player in Tokyo)'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
            ]);
        } else {
            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth) {
                $this->applyGetHealth($playerId, $diceCount, true);
                $newHealth = $this->getPlayerHealth($playerId);

                self::notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} wins ${deltaHealth} health'), [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'health' => $newHealth,
                    'deltaHealth' => $newHealth - $health,
                ]);
            }
        }
    }

    function resolveEnergyDices(int $playerId, int $diceCount) {
        $this->applyGetEnergy($playerId, $diceCount, true);

        self::notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} wins ${deltaEnergy} energy cubes'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'deltaEnergy' => $diceCount,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);
    }

    
    function resolveSmashDices(int $playerId, int $diceCount) {
        $smashTokyo = !$this->inTokyo($playerId);

        $message = $smashTokyo ? 
            clienttranslate('${player_name} give ${number} smash(es) to players in Tokyo') :
            clienttranslate('${player_name} give ${number} smash(es) to players outside Tokyo');
        $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);

        $eliminatedPlayersIds = [];
        foreach($smashedPlayersIds as $smashedPlayerId) {
            $this->applyDamage($smashedPlayerId, $diceCount, $playerId);
        }

        self::notifyAllPlayers("resolveSmashDice", $message, [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'number' => $diceCount,
            'smashedPlayersIds' => $smashedPlayersIds,
        ]);

        // Alpha Monster
        if ($this->hasCardByType($playerId, 3)) {
            // TOCHECK does Alpha Monster applies after other cards adding Smashes ? considered Yes
            $this->applyGetPoints($playerId, 1);
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
  	
    public function rethrowDices(string $dicesIds) {
        $playerId = self::getActivePlayerId();
        self::DbQuery("UPDATE dice SET `locked` = true");
        self::debug('dicesIds='.$dicesIds.'!');
        self::DbQuery("UPDATE dice SET `locked` = false where `dice_id` IN ($dicesIds)");
        $this->throwDices($playerId);

        $throwNumber = intval(self::getGameStateValue('throwNumber')) + 1;
        self::setGameStateValue('throwNumber', $throwNumber);

        $this->gamestate->nextState('rethrow');
    }

    public function resolveDices() {
        $this->gamestate->nextState('resolve');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argThrowDices() {
        $playerId = self::getActivePlayerId();
        $dices = $this->getDices($this->getDicesNumber($playerId));

        $throwNumber = intval(self::getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->getThrowNumber($playerId);
    
        // return values:
        return [
            'throwNumber' => $throwNumber,
            'maxThrowNumber' => $maxThrowNumber,
            'dices' => $dices,
            'inTokyo' => $this->inTokyo($playerId),
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stResolveDices() {
        $playerId = self::getActivePlayerId();
        $dices = $this->getDices($this->getDicesNumber($playerId));

        $smashTokyo = false;

        $diceCounts = [];
        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $diceCounts[$diceFace] = count(array_values(array_filter($dices, function($dice) use ($diceFace) { return $dice->value == $diceFace; })));
        }

        // acid attack
        if ($this->hasCardByType($playerId, 1)) {
            $diceCounts[6]++;
        }

        // poison quills
        if ($this->hasCardByType($playerId, 34) && $diceCounts[2] >= 3) {
            $diceCounts[6] += 2;
        }

        // spiked tail
        // TOCHECK can it be chained with Acid attack ? Considered yes
        if ($this->hasCardByType($playerId, 43) && $diceCounts[6] >= 1) {
            $diceCounts[6]++;
        }

        // urbavore
        // TOCHECK can it be chained with Acid attack ? Considered yes
        if ($this->hasCardByType($playerId, 46) && $diceCounts[6] >= 1 && $this->inTokyo($playerId)) {
            $diceCounts[6]++;
        }

        // detritivore
        if ($this->hasCardByType($playerId, 30) && $diceCounts[1] >= 1 && $diceCounts[2] >= 1 && $diceCounts[3] >= 1) {
            $this->applyGetPoints($playerId, 2);
        }

        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $diceCount = $diceCounts[$diceFace];
            // number
            if ($diceFace <= 3) { 
                $this->resolveNumberDices($playerId, $diceFace, $diceCount);
            }

            // health
            if ($diceFace == 4 && $diceCount > 0) {
                $this->resolveHealthDices($playerId, $diceCount);
            }

            // energy
            if ($diceFace == 5 && $diceCount > 0) {
                $this->resolveEnergyDices($playerId, $diceCount);
            }

            // smash
            if ($diceFace == 6 && $diceCount > 0) {
                $this->resolveSmashDices($playerId, $diceCount);
            }
        }

        // dice resolve may eliminate players
        $endGame = $this->eliminatePlayers($playerId);

        if ($endGame) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState($smashTokyo ? 'smashes' : 'enterTokyo');
        }
    }
}
