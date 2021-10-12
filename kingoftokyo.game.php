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

require_once('modules/constants.inc.php');
require_once('modules/php/objects/dice.php');
require_once('modules/php/objects/card.php');
require_once('modules/php/utils.php');
require_once('modules/php/monster.php');
require_once('modules/php/initial-card.php');
require_once('modules/php/player-utils.php');
require_once('modules/php/player-actions.php');
require_once('modules/php/player-args.php');
require_once('modules/php/player-states.php');
require_once('modules/php/dice-utils.php');
require_once('modules/php/dice-actions.php');
require_once('modules/php/dice-args.php');
require_once('modules/php/dice-states.php');
require_once('modules/php/cards-utils.php');
require_once('modules/php/cards-actions.php');
require_once('modules/php/cards-args.php');
require_once('modules/php/cards-states.php');
require_once('modules/php/intervention.php');
require_once('modules/php/debug-util.php');

class KingOfTokyo extends Table {
    use KOT\States\UtilTrait;
    use KOT\States\MonsterTrait;
    use KOT\States\InitialCardTrait;
    use KOT\States\PlayerUtilTrait;
    use KOT\States\PlayerActionTrait;
    use KOT\States\PlayerArgTrait;
    use KOT\States\PlayerStateTrait;
    use KOT\States\DiceUtilTrait;
    use KOT\States\DiceActionTrait;
    use KOT\States\DiceArgTrait;
    use KOT\States\DiceStateTrait;
    use KOT\States\CardsUtilTrait;
    use KOT\States\CardsActionTrait;
    use KOT\States\CardsArgTrait;
    use KOT\States\CardsStateTrait;
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
            'throwNumber' => 10,
            FRENZY_EXTRA_TURN => 11,
            'damageDoneByActivePlayer' => 12,
            EXTRA_ROLLS => 13,
            'loseHeartEnteringTokyo' => 14,
            FREEZE_TIME_MAX_TURNS => 15,
            FREEZE_TIME_CURRENT_TURN => 16,
            PSYCHIC_PROBE_ROLLED_A_3 => 19,
            'newCardId' => 20,
            KILL_PLAYERS_SCORE_AUX => 21,
            FRENZY_EXTRA_TURN_FOR_OPPORTUNIST => 22,
            PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST => 23,
            SKIP_BUY_PHASE => 24,

            PICK_MONSTER_OPTION => 100,
            GAME_VERSION_OPTION => 101,
            AUTO_SKIP_OPTION => 110,
            TWO_PLAYERS_VARIANT_OPTION => 120,
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
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_score_aux, player_monster) VALUES ";
        $values = [];
        $affectedMonsters = [];
        $eliminationRank = count($players);
        foreach( $players as $player_id => $player ) {
            $playerMonster = 0;

            if (!$this->canPickMonster()) {
                $playerMonster = bga_rand(1, 6);
                while (array_search($playerMonster, $affectedMonsters) !== false) {
                    $playerMonster = bga_rand(1, 6);
                }
                $affectedMonsters[] = $playerMonster;
            }

            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."', $eliminationRank, $playerMonster)";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        // Create dice
        self::DbQuery("INSERT INTO dice (`dice_value`) VALUES (0), (0), (0), (0), (0), (0)");
        self::DbQuery("INSERT INTO dice (`dice_value`, `extra`) VALUES (0, true), (0, true), (0, true)");

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('throwNumber', 0);
        self::setGameStateInitialValue(FRENZY_EXTRA_TURN, 0);
        self::setGameStateInitialValue(FREEZE_TIME_MAX_TURNS, 0);
        self::setGameStateInitialValue(FREEZE_TIME_CURRENT_TURN, 0);
        self::setGameStateInitialValue('damageDoneByActivePlayer', 0);
        self::setGameStateInitialValue(EXTRA_ROLLS, 0);
        self::setGameStateInitialValue('loseHeartEnteringTokyo', 0);
        self::setGameStateInitialValue('newCardId', 0);
        self::setGameStateInitialValue(PSYCHIC_PROBE_ROLLED_A_3, 0);
        self::setGameStateInitialValue(KILL_PLAYERS_SCORE_AUX, 1);
        self::setGameStateInitialValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);
        self::setGameStateInitialValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);
        self::setGameStateInitialValue(SKIP_BUY_PHASE, 0);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat('table', 'turnsNumber', 0);   // Init a table statistics
        self::initStat('player', 'turnsNumber', 0);  // Init a player statistics (for all players)
        self::initStat('table', 'pointsWin', 0);
        self::initStat('player', 'pointsWin', 0);
        self::initStat('table', 'eliminationWin', 0);
        self::initStat('player', 'eliminationWin', 0);
        
        self::initStat('table', 'survivorRatio', 0);

        self::initStat('player', 'survived', 0);
        self::initStat('player', 'turnsInTokyo', 0);
        self::initStat('player', 'tokyoEnters', 0);
        self::initStat('player', 'tokyoLeaves', 0);
        self::initStat('player', 'keepBoughtCards', 0);
        self::initStat('player', 'discardBoughtCards', 0);
        self::initStat('player', 'damageDealt', 0);
        self::initStat('player', 'damage', 0);
        self::initStat('player', 'heal', 0);
        self::initStat('player', 'wonEnergyCubes', 0);
        self::initStat('player', 'endScore', 0);
        self::initStat('player', 'endHealth', 0);

        // setup the initial game situation here
        $this->initCards();
        
        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        // TODO TEMP card to test
        $this->debugSetup();

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
        $sql = "SELECT player_id id, player_score score, player_health health, player_energy energy, player_location `location`, player_monster monster, player_no, player_poison_tokens as poisonTokens, player_shrink_ray_tokens as shrinkRayTokens, player_dead playerDead FROM player order by player_no";
        $result['players'] = self::getCollectionFromDb($sql);

        // Gather all information about current game situation (visible by player $current_player_id).

        $activePlayerId = self::getActivePlayerId();
        $result['dice'] = $activePlayerId ? $this->getDice($this->getDiceNumber($activePlayerId)) : [];

        $result['visibleCards'] = $this->getCardsFromDb($this->cards->getCardsInLocation('table'));

        $result['playersCards'] = [];
        foreach ($result['players'] as $playerId => &$playerDb) {
            $result['playersCards'][$playerId] = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));

            foreach($result['playersCards'][$playerId] as &$card) {
                if ($card->type == MIMIC_CARD) {
                    $card->mimicType = $this->getMimickedCardType();
                }
            }

            $playerDb['poisonTokens'] = intval($playerDb['poisonTokens']);
            $playerDb['shrinkRayTokens'] = intval($playerDb['shrinkRayTokens']);
            $playerDb['playerDead'] = intval($playerDb['playerDead']);

            $playerDb['rapidHealing'] = $this->countCardOfType($playerId, RAPID_HEALING_CARD) > 0;
            $playerDb['maxHealth'] = $this->getPlayerMaxHealth($playerId);
        }

        $result['mimickedCard'] = $this->getMimickedCard();

        $result['leaveTokyoUnder'] = intval(self::getUniqueValueFromDB("SELECT leave_tokyo_under FROM `player` where `player_id` = $current_player_id"));
        $result['stayTokyoOver'] = intval(self::getUniqueValueFromDB("SELECT stay_tokyo_over FROM `player` where `player_id` = $current_player_id"));

        $result['twoPlayersVariant'] = $this->isTwoPlayersVariant();
        $result['halloweenExpansion'] = $this->isHalloweenExpansion();

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
        $stateName = $this->gamestate->state()['name']; 
        if ($stateName === 'gameEnd') {
            return 100;
        }

        return $this->getMaxPlayerScore() * 5;
    }

    function stStart() {
        $this->gamestate->nextState($this->canPickMonster() ? 'pickMonster' : ($this->isHalloweenExpansion() ? 'chooseInitialCard' : 'start'));
    }

    function stStartGame() {
        $this->cards->moveAllCardsInLocation('costumediscard', 'deck');
        $this->cards->shuffle('deck'); 

        $cards = $this->getCardsFromDb($this->cards->pickCardsForLocation(3, 'deck', 'table'));

        self::notifyAllPlayers("setInitialCards", '', [
            'cards' => $cards,
        ]);

        $this->gamestate->nextState('start');
    }

    function stGameEnd() {
        $players = $this->getPlayers(true);
        $playerCount = count($players);
        $remainingPlayers = $this->getRemainingPlayers();
        $pointsWin = false;
        foreach($players as $player) {
            if ($player->score >= MAX_POINT) {
                $pointsWin = true;
            } 
        }

        // in case everyone is dead, no ranking
        if ($remainingPlayers == 0) {
            self::DbQuery("UPDATE player SET `player_score` = 0, `player_score_aux` = 0");
        }

        $eliminationWin = $remainingPlayers == 1;

        self::setStat($pointsWin ? 1 : 0, 'pointsWin');
        self::setStat($eliminationWin ? 1 : 0, 'eliminationWin');
        self::setStat($remainingPlayers / (float) $playerCount, 'survivorRatio');

        foreach($players as $player) {            
            self::setStat($player->eliminated ? 0 : 1, 'survived', $player->id);

            if (!$player->eliminated) {
                if ($player->score >= MAX_POINT) {
                    self::setStat(1, 'pointsWin', $player->id);
                }
                if ($eliminationWin) {
                    self::setStat(1, 'eliminationWin', $player->id);
                }

                if ($pointsWin) {
                    self::setStat($player->score, 'endScore', $player->id);
                }
                self::setStat($player->health, 'endHealth', $player->id);
            }            
        }

        parent::stGameEnd();
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
                case 'pickMonster':
                    $this->setMonster($active_player, $this->getAvailableMonsters()[0]);
                    $this->gamestate->nextState('next');
                    return;
                default:
                    $this->jumpToState(ST_NEXT_PLAYER);
                    //$this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        } else if ($state['type'] == "multipleactiveplayer") {
            switch ($statename) {
                case 'psychicProbeRollDie':
                    $this->applyPsychicProbeSkip($active_player);
                    return;
                case 'cancelDamage':
                    $this->applySkipWings($active_player);
                    return;
                case 'leaveTokyo':
                    $this->applyActionLeaveTokyo($active_player);
                    return;
                case 'opportunistBuyCard':
                    $this->applyOpportunistSkip($active_player);
                    return;
                case 'opportunistChooseMimicCard':
                default:
                    // Make sure player is in a non blocking status for role turn
                    $sql = "
                        UPDATE  player
                        SET     player_is_multiactive = 0
                        WHERE   player_id = $active_player
                    ";
                    self::DbQuery( $sql );

                    $this->gamestate->updateMultiactiveOrNextState('end');
                    return;
            }
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

    function upgradeTableDb($from_version) {
 
        if ($from_version <= 2106161618) { // where your CURRENT version in production has number YYMMDD-HHMM        
            // You DB schema update request.
            // Note: all tables names should be prefixed by "DBPREFIX_" to be compatible with the applyDbUpgradeToAllDB method you should use below
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `leave_tokyo_under` tinyint unsigned";

            // The method below is applying your DB schema update request to all tables, including the BGA framework utility tables like "zz_replayXXXX" or "zz_savepointXXXX".
            // You should really use this request, in conjunction with "DBPREFIX_" in your $sql, so ALL tables are updated. All utility tables MUST have the same schema than the main table, otherwise the game may be blocked.
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2107071429) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `stay_tokyo_over` tinyint unsigned";
            self::applyDbUpgradeToAllDB($sql);
        }
 
        if ($from_version <= 2109081842) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_dead` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }
 
        if ($from_version <= 2110122249) {
            $sql = "
            CREATE TABLE IF NOT EXISTS `DBPREFIX_turn_damages` (
              `from` INT(10) unsigned NOT NULL,
              `to` INT(10) unsigned NOT NULL,
              `damages` TINYINT unsigned NOT NULL,
              PRIMARY KEY (`from`, `to`)
            ) ENGINE=InnoDB;";
            self::applyDbUpgradeToAllDB($sql);
        }
    }
}
