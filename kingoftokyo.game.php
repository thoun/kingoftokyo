<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * KingOfTokyo implementation : © <Your name here> <Your email address here>
  *
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  *
  * kingoftokyo.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php');

require_once('modules/dice.php');


class KingOfTokyo extends Table {
	function __construct(){


        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::initGameStateLabels([
            "throwNumber" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ]);

	}

    protected function getGameName() {
        return "kingoftokyo";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = []) {

        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql );

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "ff0000", "008000", "0000ff", "ffa500", "773300" );


        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player ) {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();

        // Create dices
        self::DbQuery("INSERT INTO dice (`dice_value`) VALUES (0), (0), (0), (0), (0), (0)");
        self::DbQuery("INSERT INTO dice (`dice_value`, `extra`) VALUES (0, true), (0, true)");

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('throwNumber', 0);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here


        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas() {
        $result = ['players' => []];

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_health health, player_energy energy, player_location `location` FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression() {
        $maxPoints = intval(self::getUniqueValueFromDB( "SELECT max(player_score) FROM player"));
        return $maxPoints * 5;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getDices(int $number) {
        $sql = "SELECT `dice_id`, `dice_value`, `extra` FROM dice limit $number";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices));
    }

    function getPlayerName(int $playerId) {
        return self::getUniqueValueFromDb("SELECT player_name FROM player WHERE player_id = $playerId");
    }

    function getPlayerHealth(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_health FROM player where `player_id` = $playerId"));
    }

    function getDicesNumber(int $playerId) {
        return 6; // TODO
    }

    function inTokyo(int $playerId) {
        $location = intval(self::getUniqueValueFromDB( "SELECT player_location FROM player WHERE player_id = $playerId"));
        return $location > 0;
    }

    private function getPlayersIdsFromLocation(bool $inside) {
        $sign = $inside ? '>' : '=';
        $sql = "SELECT player_id FROM player WHERE player_location $sign 0 AND player_elimination = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    /*function getPlayersIdsInsideTokyo() {
        return $this->getPlayersIdsFromLocation(true);
    }

    function getPlayersIdsOutsideTokyo() {
        return $this->getPlayersIdsFromLocation(false);
    }*/

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
  	
    public function rethrowDices(string $dicesIds) {
        self::DbQuery("UPDATE dice SET `dice_value` = 0 where `dice_id` IN ($dicesIds)");

        $throwNumber = intval(self::getGameStateValue('throwNumber')) + 1;
        self::setGameStateValue('throwNumber', $throwNumber);

        $this->gamestate->nextState('rethrow');
    }

    public function resolveDices() {
        $this->gamestate->nextState('resolve');
    }

    /*

    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' );

        $player_id = self::getActivePlayerId();

        // Add your game logic to play a card there
        ...

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} played ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );

    }

    */


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

        foreach ($dices as &$dice) {
            if ($dice->value == 0) {
                $dice->value = bga_rand(1, 6);
                self::DbQuery( "UPDATE dice SET `dice_value`=".$dice->value." where `dice_id`=".$dice->id );
            }
        }

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

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stStartTurn() {
        self::setGameStateValue('throwNumber', 1);

        $this->gamestate->nextState('throw');
    }

    function stResolveDices() {
        $playerId = self::getActivePlayerId();
        $dices = $this->getDices($this->getDicesNumber($playerId));

        for ($i = 1; $i <= 6; $i++) {
            $number = count(array_values(array_filter($dices, function($dice) use ($i) { return $dice->value == $i; })));

            // number
            if ($i <= 3 && $number >= 3) {
                $points = $i + $number - 3;
                self::DbQuery("UPDATE player SET `player_score` = `player_score` + $points where `player_id` = $playerId");

                self::notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} wins ${points} with ${dice_value} dices'), [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'points' => $points,
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
                    $maxHealth = 10;
                    $newHealth = min($health + $number, $maxHealth);
                    $deltaHealth = $newHealth - $health;
                    if ($deltaHealth > 0) {
                        self::DbQuery("UPDATE player SET `player_health` = $health where `player_id` = $playerId");

                        self::notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} wins ${health} health'), [
                            'playerId' => $playerId,
                            'player_name' => self::getActivePlayerName(),
                            'health' => $deltaHealth,
                        ]);
                    }
                }
            }

            // energy
            if ($i == 5 && $number > 0) {
                self::DbQuery("UPDATE player SET `player_energy` = `player_energy` + $number where `player_id` = $playerId");

                self::notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} wins ${number} energy cubes'), [
                    'playerId' => $playerId,
                    'player_name' => self::getActivePlayerName(),
                    'number' => $number,
                ]);
            }

            // smash
            if ($i == 6 && $number > 0) {
                $smashTokyo = !$this->inTokyo($playerId);

                $message = $smashTokyo ? 
                    clienttranslate('${player_name} give ${number} smash(es) to players inside Tokyo') :
                    clienttranslate('${player_name} give ${number} smash(es) to players outside Tokyo');
                $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);

                foreach($smashedPlayersIds as $smashedPlayerId) {
                    $health = $this->getPlayerHealth($smashedPlayerId);
                    $newHealth = max($health - $number, 0);

                    if ($newHealth == 0) {
                        self::DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0 where `player_id` = $smashedPlayerId");
                    } else {
                        self::DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $smashedPlayerId");
                    }

                    self::notifyAllPlayers("resolveSmashDice", $message, [
                        'playerId' => $playerId,
                        'player_name' => self::getActivePlayerName(),
                        'number' => $number,
                        'smashedPlayersIds' => $smashedPlayersIds,
                    ]);

                    if ($newHealth == 0) {
                        self::notifyAllPlayers("playerEliminated", clienttranslate('${player_name} is eliminated !'), [
                            'playerId' => $smashedPlayerId,
                            'player_name' => $this->getPlayerName($smashedPlayerId),
                        ]);
                        
                        self::eliminatePlayer($smashedPlayerId);
                    }
                }
            }
        }

        $this->gamestate->nextState('pickCard');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];

        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
}
