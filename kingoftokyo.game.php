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
require_once('modules/php/redirections.php');
require_once('modules/php/monster.php');
require_once('modules/php/initial-card.php');
require_once('modules/php/player/player-utils.php');
require_once('modules/php/player/player-actions.php');
require_once('modules/php/player/player-args.php');
require_once('modules/php/player/player-states.php');
require_once('modules/php/dice/dice-utils.php');
require_once('modules/php/dice/dice-actions.php');
require_once('modules/php/dice/dice-args.php');
require_once('modules/php/dice/dice-states.php');
require_once('modules/php/cards/cards-utils.php');
require_once('modules/php/cards/cards-actions.php');
require_once('modules/php/cards/cards-args.php');
require_once('modules/php/cards/cards-states.php');
require_once('modules/php/wickedness-tiles/wickedness-tiles-utils.php');
require_once('modules/php/wickedness-tiles/wickedness-tiles-actions.php');
require_once('modules/php/wickedness-tiles/wickedness-tiles-args.php');
require_once('modules/php/curse-cards/curse-cards-utils.php');
require_once('modules/php/curse-cards/curse-cards-actions.php');
require_once('modules/php/curse-cards/curse-cards-args.php');
require_once('modules/php/curse-cards/curse-cards-states.php');
require_once('modules/php/evolution-cards/evolution-cards-utils.php');
require_once('modules/php/evolution-cards/evolution-cards-actions.php');
require_once('modules/php/evolution-cards/evolution-cards-args.php');
require_once('modules/php/evolution-cards/evolution-cards-states.php');
require_once('modules/php/intervention.php');
require_once('modules/php/debug-util.php');

class KingOfTokyo extends Table {
    use KOT\States\UtilTrait;
    use KOT\States\RedirectionTrait;
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
    use KOT\States\WickednessTilesUtilTrait;
    use KOT\States\WickednessTilesActionTrait;
    use KOT\States\WickednessTilesArgTrait;
    use KOT\States\CurseCardsUtilTrait;
    use KOT\States\CurseCardsActionTrait;
    use KOT\States\CurseCardsArgTrait;
    use KOT\States\CurseCardsStateTrait;
    use KOT\States\EvolutionCardsUtilTrait;
    use KOT\States\EvolutionCardsActionTrait;
    use KOT\States\EvolutionCardsArgTrait;
    use KOT\States\EvolutionCardsStateTrait;
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
        $this->initGameStateLabels([
            'throwNumber' => 10,
            FRENZY_EXTRA_TURN => 11,
            EXTRA_ROLLS => 13,
            FREEZE_TIME_MAX_TURNS => 15,
            FREEZE_TIME_CURRENT_TURN => 16,
            PSYCHIC_PROBE_ROLLED_A_3 => 19,
            'newCardId' => 20,
            KILL_PLAYERS_SCORE_AUX => 21,
            FRENZY_EXTRA_TURN_FOR_OPPORTUNIST => 22,
            PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST => 23,
            SKIP_BUY_PHASE => 24,
            CLOWN_ACTIVATED => 25,
            CHEERLEADER_SUPPORT => 26,
            STATE_AFTER_RESOLVE => 27,
            PLAYER_WITH_GOLDEN_SCARAB => 28,
            FINAL_PUSH_EXTRA_TURN => 29,
            BUILDERS_UPRISING_EXTRA_TURN => 30,
            STATE_AFTER_MIMIC_CHOOSE => 31,
            RAGING_FLOOD_EXTRA_DIE => 32,
            FALSE_BLESSING_USED_DIE => 33,
            DICE_NUMBER => 34,
            RAGING_FLOOD_EXTRA_DIE_SELECTED => 35,
            PANDA_EXPRESS_EXTRA_TURN => 36,
            MUTANT_EVOLUTION_TURN => 37,

            PICK_MONSTER_OPTION => 100,
            BONUS_MONSTERS_OPTION => BONUS_MONSTERS_OPTION,
            HALLOWEEN_EXPANSION_OPTION => HALLOWEEN_EXPANSION_OPTION,
            KINGKONG_EXPANSION_OPTION => KINGKONG_EXPANSION_OPTION,
            CYBERTOOTH_EXPANSION_OPTION => CYBERTOOTH_EXPANSION_OPTION,
            MUTANT_EVOLUTION_VARIANT_OPTION => MUTANT_EVOLUTION_VARIANT_OPTION,
            CTHULHU_EXPANSION_OPTION => CTHULHU_EXPANSION_OPTION,
            ANUBIS_EXPANSION_OPTION => ANUBIS_EXPANSION_OPTION,
            WICKEDNESS_EXPANSION_OPTION => WICKEDNESS_EXPANSION_OPTION,
            POWERUP_EXPANSION_OPTION => POWERUP_EXPANSION_OPTION,
            DARK_EDITION_OPTION => DARK_EDITION_OPTION,

            AUTO_SKIP_OPTION => 110,
            TWO_PLAYERS_VARIANT_OPTION => 120,
        ]);      
		
        $this->cards = $this->getNew("module.common.deck");
        $this->cards->init("card");
        $this->cards->autoreshuffle = true;
		
        $this->curseCards = $this->getNew("module.common.deck");
        $this->curseCards->init("curse_card");
        $this->curseCards->autoreshuffle = true;
		
        $this->wickednessTiles = $this->getNew("module.common.deck");
        $this->wickednessTiles->init("wickedness_tile");
		
        $this->evolutionCards = $this->getNew("module.common.deck");
        $this->evolutionCards->init("evolution_card");
        $this->evolutionCards->autoreshuffle = true;
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
    protected function setupNewGame($players, $options = []) {

        $sql = "DELETE FROM player WHERE 1 ";
        $this->DbQuery( $sql );

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_score_aux, player_monster) VALUES ";
        $values = [];
        $affectedMonsters = [];
        $affectedPlayersMonsters = [];
        $eliminationRank = count($players);

        $monsters = $this->getGameMonsters();
        $pickMonster = $this->canPickMonster();

        foreach ($players as $playerId => $player) {
            $playerMonster = 0;

            if (!$pickMonster) {
                $playerMonster = $monsters[bga_rand(1, count($monsters)) - 1];
                while (in_array($playerMonster, $affectedMonsters)) {
                    $playerMonster = $monsters[bga_rand(1, count($monsters)) - 1];
                }
                $affectedMonsters[$playerId] = $playerMonster;
                $affectedPlayersMonsters[$playerMonster] = $playerId;
            }

            $color = array_shift( $default_colors );
            $values[] = "('".$playerId."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."', $eliminationRank, $playerMonster)";
        }
        $sql .= implode(',', $values);
        $this->DbQuery($sql);
        $this->reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        $this->reloadPlayersBasicInfos();

        // Create dice
        $this->DbQuery("INSERT INTO dice (`dice_value`) VALUES (0), (0), (0), (0), (0), (0)");
        $this->DbQuery("INSERT INTO dice (`dice_value`, `extra`) VALUES (0, true), (0, true), (0, true)");

        if ($this->isCybertoothExpansion()) {
            $this->DbQuery("INSERT INTO dice (`dice_value`, `type`) VALUES (0, 1)");
        }
        if ($this->isAnubisExpansion()) {
           $this->DbQuery("INSERT INTO dice (`dice_value`, `type`) VALUES (0, 2)");
        }

        /************ Start the game initialization *****/
        $wickednessExpansion = intval($this->getGameStateValue(WICKEDNESS_EXPANSION_OPTION));

        // Init global values with their initial values
        $this->setGameStateInitialValue('throwNumber', 0);
        $this->setGameStateInitialValue(FRENZY_EXTRA_TURN, 0);
        $this->setGameStateInitialValue(FREEZE_TIME_MAX_TURNS, 0);
        $this->setGameStateInitialValue(FREEZE_TIME_CURRENT_TURN, 0);
        $this->setGameStateInitialValue(EXTRA_ROLLS, 0);
        $this->setGameStateInitialValue('newCardId', 0);
        $this->setGameStateInitialValue(PSYCHIC_PROBE_ROLLED_A_3, 0);
        $this->setGameStateInitialValue(KILL_PLAYERS_SCORE_AUX, 1);
        $this->setGameStateInitialValue(FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);
        $this->setGameStateInitialValue(PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST, 0);
        $this->setGameStateInitialValue(SKIP_BUY_PHASE, 0);
        $this->setGameStateInitialValue(CLOWN_ACTIVATED, 0);
        $this->setGameStateInitialValue(CHEERLEADER_SUPPORT, 0);
        $this->setGameStateInitialValue(STATE_AFTER_RESOLVE, 0);
        $this->setGameStateInitialValue(FINAL_PUSH_EXTRA_TURN, 0);
        $this->setGameStateInitialValue(BUILDERS_UPRISING_EXTRA_TURN, 0);
        $this->setGameStateInitialValue(PANDA_EXPRESS_EXTRA_TURN, 0);
        $this->setGameStateInitialValue(STATE_AFTER_MIMIC_CHOOSE, 0);
        $this->setGameStateInitialValue(RAGING_FLOOD_EXTRA_DIE, 0);
        $this->setGameStateInitialValue(RAGING_FLOOD_EXTRA_DIE_SELECTED, 0);
        $this->setGameStateInitialValue(FALSE_BLESSING_USED_DIE, 0);
        $this->setGameStateInitialValue(MUTANT_EVOLUTION_TURN, 0);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        $this->initStat('table', 'turnsNumber', 0);   // Init a table statistics
        $this->initStat('player', 'turnsNumber', 0);  // Init a player statistics (for all players)
        $this->initStat('table', 'pointsWin', 0);
        $this->initStat('player', 'pointsWin', 0);
        $this->initStat('table', 'eliminationWin', 0);
        $this->initStat('player', 'eliminationWin', 0);
        
        $this->initStat('table', 'survivorRatio', 0);

        $this->initStat('player', 'survived', 0);
        $this->initStat('player', 'turnsInTokyo', 0);
        $this->initStat('player', 'tokyoEnters', 0);
        $this->initStat('player', 'tokyoLeaves', 0);
        $this->initStat('player', 'keepBoughtCards', 0);
        $this->initStat('player', 'discardBoughtCards', 0);
        if ($this->isHalloweenExpansion()) {
            $this->initStat('player', 'costumeBoughtCards', 0);
            $this->initStat('player', 'costumeStolenCards', 0);
        }
        $this->initStat('player', 'damageDealt', 0);
        $this->initStat('player', 'damage', 0);
        $this->initStat('player', 'heal', 0);
        $this->initStat('player', 'wonEnergyCubes', 0);
        $this->initStat('player', 'endScore', 0);
        $this->initStat('player', 'endHealth', 0);
        $this->initStat('player', 'rethrownDice', 0);
        $this->initStat('player', 'pointsWonWith1Dice', 0);
        $this->initStat('player', 'pointsWonWith2Dice', 0);
        $this->initStat('player', 'pointsWonWith3Dice', 0);
        if ($this->isCthulhuExpansion()) {
            $this->initStat('player', 'gainedCultists', 0);
            $this->initStat('player', 'cultistReroll', 0);
            $this->initStat('player', 'cultistHeal', 0);
            $this->initStat('player', 'cultistEnergy', 0);
        }
        if ($this->isAnubisExpansion()) {
            $this->initStat('player', 'dieOfFateEye', 0);
            $this->initStat('player', 'dieOfFateRiver', 0);
            $this->initStat('player', 'dieOfFateSnake', 0);
            $this->initStat('player', 'dieOfFateAnkh', 0);
        }
        if ($this->isCybertoothExpansion()) {
            $this->initStat('player', 'berserkActivated', 0);
            $this->initStat('player', 'turnsInBerserk', 0);
        }

        if ($wickednessExpansion > 1) {
            //$this->initStat('player', 'gainedWickedness', 0);
            //$this->initStat('player', 'wickednessTilesTaken', 0);
        }

        if ($this->isKingKongExpansion()) {
            $this->initStat('player', 'tokyoTowerLevel1claimed', 0);
            $this->initStat('player', 'tokyoTowerLevel2claimed', 0);
            $this->initStat('player', 'tokyoTowerLevel3claimed', 0);
            $this->initStat('player', 'bonusFromTokyoTowerLevel1applied', 0);
            $this->initStat('player', 'bonusFromTokyoTowerLevel2applied', 0);   
        }

        if (!$this->canPickMonster()) {
            foreach($affectedMonsters as $playerId => $monsterId) {
                $this->saveMonsterStat($playerId, $monsterId, true);
            }
        }

        // setup the initial game situation here
        $this->initCards();
        if ($this->isAnubisExpansion()) {
            $lastPlayer = array_key_last($players);
            $this->setGameStateInitialValue(PLAYER_WITH_GOLDEN_SCARAB, $lastPlayer);
            $this->initCurseCards();
            // init first curse card
            $this->curseCards->pickCardForLocation('deck', 'table');
            $this->applyCursePermanentEffectOnReveal();
        }
        if ($wickednessExpansion > 1) {
            $this->initWickednessTiles($wickednessExpansion);
        }

        if ($this->isKingKongExpansion()) {
            $this->DbQuery("INSERT INTO tokyo_tower(`level`) VALUES (1), (2), (3)");
        }

        if ($this->isMutantEvolutionVariant()) {
            foreach (array_keys($players) as $playerId) {
                $this->cards->pickCardForLocation('mutantdeck', 'hand', $playerId);
            }
        }

        if ($this->isPowerUpExpansion()) {
            $this->initEvolutionCards($affectedPlayersMonsters);
        }
        
        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        // TODO TEMP card to test
        $this->debugSetup(array_keys($players));

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
        $isCthulhuExpansion = $this->isCthulhuExpansion();
        $isKingKongExpansion = $this->isKingKongExpansion();
        $isCybertoothExpansion = $this->isCybertoothExpansion();
        $isAnubisExpansion = $this->isAnubisExpansion();
        $isWickednessExpansion = $this->isWickednessExpansion();
        $isMutantEvolutionVariant = $this->isMutantEvolutionVariant();
        $isPowerUpExpansion = $this->isPowerUpExpansion();
        $isDarkEdition = $this->isDarkEdition();

        $result = ['players' => []];

        $current_player_id = $this->getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_health health, player_energy energy, player_location `location`, player_monster monster, player_no, player_poison_tokens as poisonTokens, player_shrink_ray_tokens as shrinkRayTokens, player_dead playerDead ";
        if ($isCybertoothExpansion) {
            $sql .= ", player_berserk berserk ";
        }
        if ($isCthulhuExpansion) {
            $sql .= ", player_cultists cultists ";
        }
        if ($isWickednessExpansion) {
            $sql .= ", player_wickedness wickedness ";
        }
        $sql .= "FROM player order by player_no ";
        $result['players'] = $this->getCollectionFromDb($sql);

        // Gather all information about current game situation (visible by player $current_player_id).

        $activePlayerId = $this->getActivePlayerId();
        $result['dice'] = $activePlayerId ? $this->getPlayerRolledDice($activePlayerId, true, true, true) : [];

        $result['visibleCards'] = $this->getCardsFromDb($this->cards->getCardsInLocation('table', null, 'location_arg'));
        $result['topDeckCardBackType'] = $this->getTopDeckCardBackType();

        if ($isKingKongExpansion) {
            $result['tokyoTowerLevels'] = $this->getTokyoTowerLevels(0);
        }

        foreach ($result['players'] as $playerId => &$playerDb) {
            $playerDb['cards'] = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $playerId));

            foreach($playerDb['cards'] as &$card) {
                if ($card->type == MIMIC_CARD) {
                    $card->mimicType = $this->getMimickedCardType(MIMIC_CARD);
                }
            }

            $playerDb['poisonTokens'] = intval($playerDb['poisonTokens']);
            $playerDb['shrinkRayTokens'] = intval($playerDb['shrinkRayTokens']);
            $playerDb['playerDead'] = intval($playerDb['playerDead']);

            $playerDb['rapidHealing'] = $this->countCardOfType($playerId, RAPID_HEALING_CARD) > 0;
            $playerDb['maxHealth'] = $this->getPlayerMaxHealth($playerId);

            if ($isKingKongExpansion) {
                $playerDb['tokyoTowerLevels'] = $this->getTokyoTowerLevels($playerId);
            }
            if ($isCybertoothExpansion) {
                $playerDb['berserk'] = boolval($playerDb['berserk']);
            }
            if ($isCthulhuExpansion) {
                $playerDb['cultists'] = intval($playerDb['cultists']);
            }
            if ($isWickednessExpansion) {
                $playerDb['wickedness'] = intval($playerDb['wickedness']);
                $playerDb['wickednessTiles'] = $this->getWickednessTilesFromDb($this->wickednessTiles->getCardsInLocation('hand', $playerId));

                foreach($playerDb['wickednessTiles'] as &$card) {
                    if ($card->type == FLUXLING_WICKEDNESS_TILE) {
                        $card->mimicType = $this->getMimickedCardType(FLUXLING_WICKEDNESS_TILE);
                    }
                }
            }

            if ($isPowerUpExpansion) {
                $playerDb['visibleEvolutions'] = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('table', $playerId));
                $playerDb['hiddenEvolutions'] = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('hand', $playerId));
                
                $mothershipSupportCard = $this->getEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);
                $playerDb['mothershipSupport'] = $mothershipSupportCard != null;
                $playerDb['mothershipSupportUsed'] = $mothershipSupportCard != null && $this->isUsedCard(3000 + $mothershipSupportCard->id);
            }
        }

        $result['mimickedCards'] = [
            'card' => $this->getMimickedCard(MIMIC_CARD),
            'tile' => $this->getMimickedCard(FLUXLING_WICKEDNESS_TILE),
        ];

        $result['leaveTokyoUnder'] = intval($this->getUniqueValueFromDB("SELECT leave_tokyo_under FROM `player` where `player_id` = $current_player_id"));
        $result['stayTokyoOver'] = intval($this->getUniqueValueFromDB("SELECT stay_tokyo_over FROM `player` where `player_id` = $current_player_id"));

        $result['twoPlayersVariant'] = $this->isTwoPlayersVariant();
        $result['halloweenExpansion'] = $this->isHalloweenExpansion();
        $result['cthulhuExpansion'] = $isCthulhuExpansion;
        $result['anubisExpansion'] = $isAnubisExpansion;
        $result['kingkongExpansion'] = $isKingKongExpansion;
        $result['cybertoothExpansion'] = $isCybertoothExpansion;
        $result['wickednessExpansion'] = $isWickednessExpansion;
        $result['mutantEvolutionVariant'] = $isMutantEvolutionVariant;
        $result['powerUpExpansion'] = $isPowerUpExpansion;
        $result['darkEdition'] = $isDarkEdition;
        if ($isAnubisExpansion) {
            $result['playerWithGoldenScarab'] = $this->getPlayerIdWithGoldenScarab(true);
            $result['curseCard'] = $this->getCurseCard();
        }

        if ($isWickednessExpansion) {
            $result['wickednessTiles'] = $this->getWickednessTilesFromDb($this->wickednessTiles->getCardsInLocation('table'));
        }

        if ($isPowerUpExpansion) {
            $result['EVOLUTION_CARDS_TYPES'] = $this->EVOLUTION_CARDS_TYPES;
        }

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
        $this->goToState($this->redirectAfterStart());
    }

    function stStartGame() {
        if ($this->isHalloweenExpansion()) {
            $this->cards->moveAllCardsInLocation('costumedeck', 'deck');
            $this->cards->moveAllCardsInLocation('costumediscard', 'deck');
        }
        $this->cards->shuffle('deck'); 

        // TODO $this->debugSetupBeforePlaceCard();
        $cards = $this->placeNewCardsOnTable();
        // TODO $this->debugSetupAfterPlaceCard();

        $this->notifyAllPlayers("setInitialCards", '', [
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
            $this->DbQuery("UPDATE player SET `player_score` = 0, `player_score_aux` = 0");
        }

        $eliminationWin = $remainingPlayers == 1;

        $this->setStat($pointsWin ? 1 : 0, 'pointsWin');
        $this->setStat($eliminationWin ? 1 : 0, 'eliminationWin');
        $this->setStat($remainingPlayers / (float) $playerCount, 'survivorRatio');

        foreach($players as $player) {            
            $this->setStat($player->eliminated ? 0 : 1, 'survived', $player->id);

            if (!$player->eliminated) {
                if ($player->score >= MAX_POINT) {
                    $this->setStat(1, 'pointsWin', $player->id);
                }
                if ($eliminationWin) {
                    $this->setStat(1, 'eliminationWin', $player->id);
                }

                if ($pointsWin) {
                    $this->setStat($player->score, 'endScore', $player->id);
                }
                $this->setStat($player->health, 'endHealth', $player->id);
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
                case 'chooseInitialCard':
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
                case 'cheerleaderSupport':
                    $this->applyDontSupport($active_player);
                    return;
                case 'cancelDamage':
                    $this->applySkipWings($active_player);
                    return;
                case 'leaveTokyo':
                    $this->applyActionLeaveTokyo($active_player, null);
                case 'leaveTokyoExchangeCard':
                    $this->applySkipExchangeCard($active_player);
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
                    $this->DbQuery($sql);

                    $this->gamestate->updateMultiactiveOrNextState('end');
                    return;

                    // TODO ST_MULTIPLAYER_PICK_EVOLUTION_DECK pick random evolution
                    //answerQuestion : skip if possible
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
 
        if ($from_version <= 2111271657) {
            $sql = "UPDATE `DBPREFIX_global_variables` SET `name` = '".MIMICKED_CARD.MIMIC_CARD."' WHERE `name` = '".MIMICKED_CARD."'";
            self::applyDbUpgradeToAllDB($sql);
        }
 
        if ($from_version <= 2112012135) {
            $sql = "ALTER TABLE `DBPREFIX_dice` ADD `discarded` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }
    }
}
