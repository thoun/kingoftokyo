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

    function getFirst3Dice(int $number) {
        $dices = $this->getDices($number);
        foreach ($dices as $dice) {
            if ($dice->value === 3) {
                return $dice;
            }
        }
        return null;
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
        $remove = intval($this->getGameStateValue('lessDiesForNextTurn'));

        return 6 + $this->countExtraHead($playerId) - $remove;
    }

    function resolveNumberDices(int $playerId, int $number, int $diceCount) {
        // number
        if ($diceCount >= 3) {
            $points = $number + $diceCount - 3;

            $this->applyGetPoints($playerId, $points, -1);

            self::notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} wins ${deltaPoints} with ${dice_value} dices'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'deltaPoints' => $points,
                'points' => $this->getPlayerScore($playerId),
                'diceValue' => $number,
                'dice_value' => "[dice$number]",
            ]);

            if ($number == 1) {
                // gourmet
                $countGourmet = $this->countCardOfType($playerId, 19);
                if ($countGourmet > 0) {
                    $this->applyGetPoints($playerId, 2 * $countGourmet, 19);
                }

                // Freeze Time
                $countFreezeTime = $this->countCardOfType($playerId, 16);
                if ($countFreezeTime > 0) {
                    // TOCHECK Can Freeze Time be cloned and win 2 new turn ? If yes, 1 or 2 less die next turn ? Considered No
                    $this->setGameStateValue('playAgainAfterTurnOneLessDie', 1);
                }
                
            }
        }
    }

    function resolveHealthDices(int $playerId, int $diceCount) {
        if ($this->inTokyo($playerId)) {
            self::notifyAllPlayers( "resolveHealthDiceInTokyo", clienttranslate('${player_name} wins no [Heart] (player in Tokyo)'), [
                'playerId' => $playerId,
                'player_name' => self::getActivePlayerName(),
            ]);
        } else {
            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth) {
                $this->applyGetHealth($playerId, $diceCount, -1);
                $newHealth = $this->getPlayerHealth($playerId);

                self::notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} wins ${deltaHealth} [Heart]'), [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'health' => $newHealth,
                    'deltaHealth' => $diceCount,
                ]);
            }
        }
    }

    function resolveEnergyDices(int $playerId, int $diceCount) {
        $this->applyGetEnergy($playerId, $diceCount, -1);

        self::notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} wins ${deltaEnergy} [Energy]'), [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'deltaEnergy' => $diceCount,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);
    }

    
    function resolveSmashDices(int $playerId, int $diceCount) {
        // Nova breath
        $countNovaBreath = $this->countCardOfType($playerId, 29);

        $message = null;
        $smashedPlayersIds = null;

        if ($countNovaBreath) {
            $message = clienttranslate('${player_name} give ${number} [diceSmash] to all other Monsters');
            $smashedPlayersIds = $this->getOtherPlayersIds($playerId);
        } else {
            $smashTokyo = !$this->inTokyo($playerId);
            $message = $smashTokyo ? 
                clienttranslate('${player_name} give ${number} [diceSmash] to Monsters in Tokyo') :
                clienttranslate('${player_name} give ${number} [diceSmash] to Monsters outside Tokyo');
            $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);
        }

        foreach($smashedPlayersIds as $smashedPlayerId) {
            $this->applyDamage($smashedPlayerId, $diceCount, $playerId, 0);
        }

        self::notifyAllPlayers("resolveSmashDice", $message, [
            'playerId' => $playerId,
            'player_name' => self::getActivePlayerName(),
            'number' => $diceCount,
            'smashedPlayersIds' => $smashedPlayersIds,
        ]);

        // Alpha Monster
        $countAlphaMonster = $this->countCardOfType($playerId, 3);
        if ($countAlphaMonster > 0) {
            // TOCHECK does Alpha Monster applies after other cards adding Smashes ? considered Yes
            $this->applyGetPoints($playerId, $countAlphaMonster, 3);
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
        self::DbQuery("UPDATE dice SET `locked` = false where `dice_id` IN ($dicesIds)");
        $this->throwDices($playerId);

        $throwNumber = intval(self::getGameStateValue('throwNumber')) + 1;
        self::setGameStateValue('throwNumber', $throwNumber);

        $this->gamestate->nextState('rethrow');
    }

    public function rethrow3() {
        $playerId = self::getActivePlayerId();
        $dice = $this->getFirst3Dice($this->getDicesNumber($playerId));

        if ($dice == null) {
            throw new \Error('No dice 3');
        }

        $dice->value = bga_rand(1, 6);
        self::DbQuery( "UPDATE dice SET `dice_value`=".$dice->value." where `dice_id`=".$dice->id );

        $this->gamestate->nextState('rethrow3');
    }

    public function resolveDices() {
        $this->gamestate->nextState('changeDie');
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
        $diceNumber = $this->getDicesNumber($playerId);
        $dices = $this->getDices($diceNumber);

        $throwNumber = intval(self::getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->getThrowNumber($playerId);

        $hasEnergyDrink = $this->countCardOfType($playerId, 45) > 0; // Energy drink
        $playerEnergy = null;
        if ($hasEnergyDrink) {
            $playerEnergy = $this->getPlayerEnergy($playerId);
        }

        $hasBackgroundDweller = $this->countCardOfType($playerId, 5) > 0; // Background Dweller
        $hasDice3 = null;
        if ($hasBackgroundDweller) {
            $hasDice3 = $this->getFirst3Dice($diceNumber) != null;
        }
    
        // return values:
        return [
            'throwNumber' => $throwNumber,
            'maxThrowNumber' => $maxThrowNumber,
            'dices' => $dices,
            'inTokyo' => $this->inTokyo($playerId),
            'energyDrink' => [
                'hasCard' => $hasEnergyDrink,
                'playerEnergy' => $playerEnergy,
            ],
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ]
        ];
    }

    function argChangeDie() {
        // TODO
        return [];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stChangeDie() {
        // TODO        
        $this->gamestate->nextState('resolve');
    }

    function stResolveDices() {
        $playerId = self::getActivePlayerId();
        $playerInTokyo = $this->inTokyo($playerId);
        $dices = $this->getDices($this->getDicesNumber($playerId));

        $smashTokyo = false;

        $diceCounts = [];
        for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
            $diceCounts[$diceFace] = count(array_values(array_filter($dices, function($dice) use ($diceFace) { return $dice->value == $diceFace; })));
        }

        // acid attack
        $countAcidAttack = $this->countCardOfType($playerId, 1);
        if ($countAcidAttack > 0) {
            $diceCounts[6] += $countAcidAttack;
        }

        // burrowing
        if ($playerInTokyo) {
            $countBurrowing = $this->countCardOfType($playerId, 6);
            if ($countBurrowing > 0) {
                $diceCounts[6] += $countBurrowing;
            }
        }

        // poison quills
        if ($diceCounts[2] >= 3) {
            $countPoisonQuills = $this->countCardOfType($playerId, 34);
            if ($countPoisonQuills > 0) {
                $diceCounts[6] += 2 * $countPoisonQuills;
            }
        }

        if ($diceCounts[6] >= 1) {
            // spiked tail
            // TOCHECK can it be chained with Acid attack ? Considered yes
            $countSpikedTail = $this->countCardOfType($playerId, 43);
            if ($countSpikedTail > 0) {
                $diceCounts[6] += $countSpikedTail;
            }

            // urbavore
            // TOCHECK can it be chained with Acid attack ? Considered yes
            if ($playerInTokyo) {
                $countUrbavore = $this->countCardOfType($playerId, 46);
                if ($countUrbavore > 0) {
                    $diceCounts[6] += $countUrbavore;
                }
            }
        }

        // detritivore
        if ($diceCounts[1] >= 1 && $diceCounts[2] >= 1 && $diceCounts[3] >= 1) {
            $countDetritivore = $this->countCardOfType($playerId, 30);
            if ($countDetritivore > 0) {
                $this->applyGetPoints($playerId, 2 * $countDetritivore, 30);
            }

            // complete destruction
            if ($diceCounts[4] >= 1 && $diceCounts[5] >= 1 && $diceCounts[6] >= 1) { // dices 1-2-3 check with previous if
                $countCompleteDestruction = $this->countCardOfType($playerId, 8);
                if ($countCompleteDestruction > 0) {
                    $this->applyGetPoints($playerId, 9 * $countCompleteDestruction, 8);
                }
            }
        }

        // fire breathing
        if ($diceCounts[6] >= 1) {
            $countFireBreathing = $this->countCardOfType($playerId, 15);
            if ($countFireBreathing > 0) {
                $playersIds = $this->getPlayersIds();
                $playerIndex = array_search($playerId, $playersIds);
                $playerCount = count($playersIds);
                // TOCHECK we ignore in/out of tokyo ? Considered Yes
                $leftPlayerId = $playersIds[($playerIndex + 1) % $playerCount];
                $rightPlayerId = $playersIds[($playerIndex + $playerCount - 1) % $playerCount];
                
                if ($leftPlayerId != $playerId) {
                    $this->applyDamage($leftPlayerId, $countFireBreathing, $playerId, 15);
                }
                if ($rightPlayerId != $playerId && $rightPlayerId != $leftPlayerId) {
                    $this->applyDamage($rightPlayerId, $countFireBreathing, $playerId, 15);
                }
            }
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
            $smashTokyo = $diceCounts[6] >= 1 && !$playerInTokyo && count($this->getPlayersIdsInTokyo()) > 0;
            $this->gamestate->nextState($smashTokyo ? 'smashes' : 'enterTokyo');
        }
    }
}
