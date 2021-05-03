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
        $maxThrowNumber = 3;
    
        // return values:
        return [
            'throwNumber' => $throwNumber,
            'maxThrowNumber' => $maxThrowNumber,
            'dices' => $dices,
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stResolveDices() {
        $playerId = self::getActivePlayerId();
        $dices = $this->getDices($this->getDicesNumber($playerId));

        $smashTokyo = false;

        for ($i = 1; $i <= 6; $i++) {
            $number = count(array_values(array_filter($dices, function($dice) use ($i) { return $dice->value == $i; })));

            // number
            if ($i <= 3 && $number >= 3) {
                $points = $i + $number - 3;
                self::DbQuery("UPDATE player SET `player_score` = `player_score` + $points where `player_id` = $playerId");

                self::notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} wins ${deltaPoints} with ${diceValue} dices'), [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'deltaPoints' => $points,
                    'points' => $this->getPlayerScore($playerId),
                    'diceValue' => $i,
                ]);
            }

            // health
            if ($i == 4 && $number > 0) {
                if ($this->inTokyo($playerId)) {
                    self::notifyAllPlayers( "resolveHealthDiceInTokyo", clienttranslate('${player_name} wins no health (player in Tokyo)'), [
                        'playerId' => $playerId,
                        'player_name' => self::getActivePlayerName(),
                    ]);
                } else {
                    $health = $this->getPlayerHealth($playerId);
                    $maxHealth = $this->getPlayerMaxHealth($playerId);
                    $newHealth = min($health + $number, $maxHealth);
                    $deltaHealth = $newHealth - $health;
                    if ($deltaHealth != 0) {
                        self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");

                        self::notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} wins ${deltaHealth} health'), [
                            'playerId' => $playerId,
                            'player_name' => self::getActivePlayerName(),
                            'health' => $newHealth,
                            'deltaHealth' => $deltaHealth,
                        ]);
                    }
                }
            }

            // energy
            if ($i == 5 && $number > 0) {
                self::DbQuery("UPDATE player SET `player_energy` = `player_energy` + $number where `player_id` = $playerId");

                self::notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} wins ${deltaEnergy} energy cubes'), [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'deltaEnergy' => $number,
                    'energy' => $this->getPlayerEnergy($playerId),
                ]);
            }

            // smash
            if ($i == 6 && $number > 0) {
                $smashTokyo = !$this->inTokyo($playerId);

                $message = $smashTokyo ? 
                    clienttranslate('${player_name} give ${number} smash(es) to players in Tokyo') :
                    clienttranslate('${player_name} give ${number} smash(es) to players outside Tokyo');
                $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);

                $eliminatedPlayersIds = [];
                foreach($smashedPlayersIds as $smashedPlayerId) {
                    $health = $this->getPlayerHealth($smashedPlayerId);
                    $newHealth = max($health - $number, 0);

                    if ($newHealth > 0) {
                        self::DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $smashedPlayerId");
                    }

                    if ($newHealth == 0) {
                        $eliminatedPlayersIds[] = $smashedPlayerId;
                    }
                }

                self::notifyAllPlayers("resolveSmashDice", $message, [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'number' => $number,
                    'smashedPlayersIds' => $smashedPlayersIds,
                ]);

                $eliminatedPlayersIds = [];
                foreach($eliminatedPlayersIds as $eliminatedPlayerId) {
                    $this->eliminateAPlayer($eliminatedPlayerId);
                }
            }
        }

        $this->gamestate->nextState($smashTokyo ? 'smashes' : 'enterTokyo');
    }
}
