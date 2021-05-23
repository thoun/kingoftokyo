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

require_once('modules/php/objects/dice.php');
require_once('modules/php/objects/card.php');
require_once('modules/php/utils.php');
require_once('modules/php/player.php');
require_once('modules/php/dice.php');
require_once('modules/php/cards.php');
require_once('modules/php/intervention.php');
require_once('modules/php/debug-util.php');

class KingOfTokyo extends Table {
    use KOT\States\UtilTrait;
    use KOT\States\PlayerTrait;
    use KOT\States\DiceTrait;
    use KOT\States\CardsTrait;
    use KOT\States\InterventionTrait;
    use KOT\States\DebugUtilTrait;

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
            "playAgainAfterTurn" => 11,
            "damageDoneByActivePlayer" => 12,
            "energyDrinks" => 13,
            "loseHeartEnteringTokyo" => 14,
            "playAgainAfterTurnOneLessDie" => 15,
            "lessDiceForNextTurn" => 16,
            "madeInALabCard" => 19,
            "newCardId" => 20,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ]);      
		
        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
        $this->cards->autoreshuffle = true;
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
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_score_aux, player_monster) VALUES ";
        $values = [];
        $affectedMonsters = [];
        $eliminationRank = count($players) - 1;
        foreach( $players as $player_id => $player ) {
            $playerMonster = bga_rand(0, 5);
            while (array_search($playerMonster, $affectedMonsters) !== false) {
                $playerMonster = bga_rand(0, 5);
            }
            $affectedMonsters[] = $playerMonster;

            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."', $eliminationRank, $playerMonster)";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();

        // Create dice
        self::DbQuery("INSERT INTO dice (`dice_value`) VALUES (0), (0), (0), (0), (0), (0)");
        self::DbQuery("INSERT INTO dice (`dice_value`, `extra`) VALUES (0, true), (0, true), (0, true)");

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('throwNumber', 0);
        self::setGameStateInitialValue('playAgainAfterTurn', 0);
        self::setGameStateInitialValue('playAgainAfterTurnOneLessDie', 0);
        self::setGameStateInitialValue('lessDiceForNextTurn', 0);
        self::setGameStateInitialValue('damageDoneByActivePlayer', 0);
        self::setGameStateInitialValue('energyDrinks', 0);
        self::setGameStateInitialValue('loseHeartEnteringTokyo', 0);
        self::setGameStateInitialValue('madeInALabCard', 0);
        self::setGameStateInitialValue('newCardId', 0);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // setup the initial game situation here
        $this->initCards();
        $this->cards->pickCardsForLocation(3, 'deck', 'table');

        // TODO TEMP card to test
        $this->debugSetup();
        
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
        $sql = "SELECT player_id id, player_score score, player_health health, player_energy energy, player_location `location`, player_monster monster, player_no, player_poison_tokens as poisonTokens, player_shrink_ray_tokens as shrinkRayTokens FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // Gather all information about current game situation (visible by player $current_player_id).

        $activePlayerId = self::getActivePlayerId();
        $result['dice'] = $activePlayerId ? $this->getDice($this->getDiceNumber($activePlayerId)) : [];

        $result['visibleCards'] = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));

        $result['playersCards'] = [];
        foreach ($result['players'] as $playerId => &$playerDb) {
            $result['playersCards'][$playerId] = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));

            $playerDb['poisonTokens'] = intval($playerDb['poisonTokens']);
            $playerDb['shrinkRayTokens'] = intval($playerDb['shrinkRayTokens']);

            $playerDb['rapidHealing'] = $this->countCardOfType($playerId, 37) > 0;
        }

        $result['mimickedCard'] = $this->getMimickedCard();

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
        return $this->getMaxPlayerScore() * 5;
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

    function zombieTurn($state, $active_player) {
    	$statename = $state['name'];

        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->jumpToState(ST_NEXT_PLAYER);
                    //$this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        } else if ($state['type'] == "multipleactiveplayer") {
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
