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

namespace Bga\Games\KingOfTokyo;

require_once('framework-prototype/Helpers/Arrays.php');

require_once('constants.inc.php');
require_once('Objects/dice.php');
require_once('Objects/card.php');
require_once('player/player-utils.php');
require_once('player/player-actions.php');
require_once('player/player-args.php');
require_once('player/player-states.php');
require_once('dice/dice-utils.php');
require_once('dice/dice-actions.php');
require_once('dice/dice-states.php');
require_once('cards/cards-utils.php');
require_once('cards/cards-actions.php');
require_once('cards/cards-args.php');
require_once('evolution-cards/evolution-cards-utils.php');

use Bga\GameFramework\Actions\CheckAction;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\States\Start;
use KOT\Objects\Question;

const MONSTERS_WITH_ICON = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,18, 61, 62, 63, 102,104,105,106,114,115];

class Game extends \Bga\GameFramework\Table {
    use UtilTrait;
    use RedirectionTrait;
    use \KOT\States\PlayerUtilTrait;
    use \KOT\States\PlayerActionTrait;
    use \KOT\States\PlayerArgTrait;
    use \KOT\States\PlayerStateTrait;
    use \KOT\States\DiceUtilTrait;
    use \KOT\States\DiceActionTrait;
    use \KOT\States\DiceStateTrait;
    use \KOT\States\CardsUtilTrait;
    use \KOT\States\CardsActionTrait;
    use \KOT\States\CardsArgTrait;
    use \KOT\States\EvolutionCardsUtilTrait;
    use InterventionTrait;
    use DebugUtilTrait;

    public AnubisExpansion $anubisExpansion;
    public KingKongExpansion $kingKongExpansion;
    public CybertoothExpansion $cybertoothExpansion;
    public CthulhuExpansion $cthulhuExpansion;
    public WickednessExpansion $wickednessExpansion;
    public PowerUpExpansion $powerUpExpansion;
    public MindbugExpansion $mindbugExpansion;

    public PowerCardManager $powerCards;
    public WickednessTileManager $wickednessTiles;

    // from material file
    public array $EVOLUTION_CARDS_TYPES;
    public array $EVOLUTION_CARDS_TYPES_FOR_STATS;
    public array $AUTO_DISCARDED_EVOLUTIONS;
    public array $EVOLUTION_TO_PLAY_BEFORE_START_PERMANENT;
    public array $EVOLUTION_TO_PLAY_BEFORE_START_TEMPORARY;
    public array $EVOLUTION_TO_PLAY_BEFORE_START;
    public array $EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE_MULTI_OTHERS;
    public array $EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE;
    public array $EVOLUTION_TO_PLAY_DURING_RESOLVE_DICE_ACTIVE;
    public array $EVOLUTION_TO_PLAY_DURING_RESOLVE_DICE_MULTI_OTHERS;
    public array $EVOLUTION_TO_PLAY_DURING_RESOLVE_DICE;
    public array $EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO;
    public array $EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO;
    public array $EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO;
    public array $EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT;
    public array $EVOLUTION_TO_PLAY_BEFORE_END_MULTI;
    public array $EVOLUTION_TO_PLAY_BEFORE_END_ACTIVE;
    public array $EVOLUTION_TO_PLAY_BEFORE_END;
    public array $EVOLUTIONS_TO_HEAL;
    public array $EVOLUTION_GIFTS;

	function __construct(){
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        include(__DIR__.'/material.inc.php');

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
            RAGING_FLOOD_EXTRA_DIE => 32,
            FALSE_BLESSING_USED_DIE => 33,
            DICE_NUMBER => 34,
            RAGING_FLOOD_EXTRA_DIE_SELECTED => 35,
            PANDA_EXPRESS_EXTRA_TURN => 36,
            MUTANT_EVOLUTION_TURN => 37,
            PREVENT_ENTER_TOKYO => 38,
            JUNGLE_FRENZY_EXTRA_TURN => 39,
            ENCASED_IN_ICE_DIE_ID => 40,
            TARGETED_PLAYER => 41,
        ]);      
		
        
        $this->anubisExpansion = new AnubisExpansion($this);
        $this->kingKongExpansion = new KingKongExpansion($this);
        $this->cybertoothExpansion = new CybertoothExpansion($this);
        $this->cthulhuExpansion = new CthulhuExpansion($this);
        $this->wickednessExpansion = new WickednessExpansion($this);
        $this->powerUpExpansion = new PowerUpExpansion($this);
        $this->mindbugExpansion = new MindbugExpansion($this);
		
        $this->powerCards = new PowerCardManager($this);
        $this->wickednessTiles = new WickednessTileManager($this);
	}

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = []) {  
        $this->powerCards->initDb();
        $this->wickednessTiles->initDb();
        $this->powerUpExpansion->initDb();
        $this->anubisExpansion->initDb();
        $this->mindbugExpansion->initDb(array_keys($players));

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

        $playersIds = array_keys($players);
        foreach ($players as $playerId => $player) {
            $playerMonster = 0;

            if (!$pickMonster) {
                $playerMonster = $monsters[bga_rand(1, count($monsters)) - 1];
                while (in_array($playerMonster, $affectedMonsters)) {
                    $playerMonster = $monsters[bga_rand(1, count($monsters)) - 1];
                }
                $affectedMonsters[$playerId] = $playerMonster;
                $affectedPlayersMonsters[$playerMonster % 100] = $playerId; // % 100, for evolutions only!
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

        if ($this->cybertoothExpansion->isActive()) {
            $this->cybertoothExpansion->setup();
        }
        if ($this->anubisExpansion->isActive()) {
            $this->anubisExpansion->setup($players);
        }

        /************ Start the game initialization *****/
        
        $isOrigins = $this->isOrigins();
        $darkEdition = $isOrigins ? 1 : $this->tableOptions->get(DARK_EDITION_OPTION);
        $wickednessExpansion = $isOrigins ? 1 : $this->tableOptions->get(WICKEDNESS_EXPANSION_OPTION);

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
        $this->setGameStateInitialValue(JUNGLE_FRENZY_EXTRA_TURN, 0);
        $this->setGameStateInitialValue(RAGING_FLOOD_EXTRA_DIE, 0);
        $this->setGameStateInitialValue(RAGING_FLOOD_EXTRA_DIE_SELECTED, 0);
        $this->setGameStateInitialValue(FALSE_BLESSING_USED_DIE, 0);
        $this->setGameStateInitialValue(MUTANT_EVOLUTION_TURN, 0);
        $this->setGameStateInitialValue(ENCASED_IN_ICE_DIE_ID, 0);
        $this->setGameStateInitialValue(TARGETED_PLAYER, 0);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        $this->initStat('table', 'turnsNumber', 0);   // Init a table statistics
        $this->initStat('player', 'turnsNumber', 0);  // Init a player statistics (for all players)
        $this->initStat('table', 'pointsWin', 0);
        $this->initStat('player', 'pointsWin', 0);
        $this->initStat('table', 'eliminationWin', 0);
        $this->initStat('player', 'eliminationWin', 0);
        $this->initStat('table', 'smashesGiven', 0);
        $this->initStat('player', 'smashesGiven', 0);
        
        $this->initStat('table', 'survivorRatio', 0);

        $this->initStat('player', 'smashesReceived', 0);
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
        if ($this->cthulhuExpansion->isActive()) {
            $this->initStat('player', 'gainedCultists', 0);
            $this->initStat('player', 'cultistReroll', 0);
            $this->initStat('player', 'cultistHeal', 0);
            $this->initStat('player', 'cultistEnergy', 0);
        }
        if ($this->anubisExpansion->isActive()) {
            $this->initStat('player', 'dieOfFateEye', 0);
            $this->initStat('player', 'dieOfFateRiver', 0);
            $this->initStat('player', 'dieOfFateSnake', 0);
            $this->initStat('player', 'dieOfFateAnkh', 0);
        }
        if ($this->cybertoothExpansion->isActive()) {
            $this->initStat('player', 'berserkActivated', 0);
            $this->initStat('player', 'turnsInBerserk', 0);
        }

        if ($darkEdition > 1 || $wickednessExpansion > 1) {
            $this->initStat('player', 'gainedWickedness', 0);
            $this->initStat('player', 'wickednessTilesTaken', 0);
        }

        if ($this->kingKongExpansion->isActive()) {
            $this->initStat('player', 'tokyoTowerLevel1claimed', 0);
            $this->initStat('player', 'tokyoTowerLevel2claimed', 0);
            $this->initStat('player', 'tokyoTowerLevel3claimed', 0);
            $this->initStat('player', 'bonusFromTokyoTowerLevel1applied', 0);
            $this->initStat('player', 'bonusFromTokyoTowerLevel2applied', 0);   
        }
        if ($this->isMutantEvolutionVariant()) {
            $this->initStat('player', 'formChanged', 0);
            $this->initStat('player', 'turnsInBipedForm', 0);
            $this->initStat('player', 'turnsInBeastForm', 0);
        }
        if ($this->powerUpExpansion->isActive()) {
            $this->initStat('player', 'pickedPermanentEvolution', 0);
            $this->initStat('player', 'pickedTemporaryEvolution', 0);
            $this->initStat('player', 'pickedGiftEvolution', 0);
            $this->initStat('player', 'playedPermanentEvolution', 0);
            $this->initStat('player', 'playedTemporaryEvolution', 0);
            $this->initStat('player', 'playedGiftEvolution', 0);
        }

        if (!$this->canPickMonster()) {
            foreach($affectedMonsters as $playerId => $monsterId) {
                $this->saveMonsterStat($playerId, $monsterId, true);
            }
        }

        // setup the initial game situation here
        $mindbugCardsSetting = $this->mindbugExpansion->getMindbugCardsSetting();
        $this->powerCards->setup($isOrigins, $darkEdition > 1, $mindbugCardsSetting);
        
        if ($darkEdition > 1) {
            $this->wickednessTiles->setup($darkEdition);
        } else if ($wickednessExpansion > 1) {
            $this->wickednessTiles->setup($wickednessExpansion);
        }

        if ($this->kingKongExpansion->isActive()) {
            $this->kingKongExpansion->setup();
        }

        if ($this->isMutantEvolutionVariant()) {
            foreach ($playersIds as $playerId) {
                $this->powerCards->pickCardForLocation('mutantdeck', null, 'hand', $playerId);
            }
        }

        if ($this->powerUpExpansion->isActive()) {
            $this->powerUpExpansion->setup($affectedPlayersMonsters);
        }

        if ($this->mindbugExpansion->isActive()) {
            $this->mindbugExpansion->setup();
        }
        
        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        return Start::class;

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas(): array {
        $isCthulhuExpansion = $this->cthulhuExpansion->isActive();
        $isKingKongExpansion = $this->kingKongExpansion->isActive();
        $isCybertoothExpansion = $this->cybertoothExpansion->isActive();
        $isAnubisExpansion = $this->anubisExpansion->isActive();
        $isWickednessExpansion = $this->wickednessExpansion->isActive();
        $isMutantEvolutionVariant = $this->isMutantEvolutionVariant();
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();
        $isDarkEdition = $this->isDarkEdition();
        $isOrigins = $this->isOrigins();

        $result = ['players' => []];

        $current_player_id = $this->getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_health health, player_energy energy, player_location `location`, player_monster monster, player_no, player_poison_tokens as poisonTokens, player_shrink_ray_tokens as shrinkRayTokens, player_dead playerDead, player_zombified zombified ";
        if ($isCybertoothExpansion) {
            $sql .= ", player_berserk berserk ";
        }
        if ($isCthulhuExpansion) {
            $sql .= ", player_cultists cultists ";
        }
        if ($isWickednessExpansion) {
            $sql .= ", player_wickedness wickedness ";
        }
        if ($isPowerUpExpansion) {
            $sql .= ", ask_play_evolution askPlayEvolution ";
        }
        $sql .= "FROM player order by player_no ";
        $result['players'] = $this->getCollectionFromDb($sql);

        // Gather all information about current game situation (visible by player $current_player_id).

        $activePlayerId = $this->getActivePlayerId();
        $result['dice'] = $activePlayerId ? $this->getPlayerRolledDice($activePlayerId, true, true, true) : [];

        $result['deckCardsCount'] = $this->powerCards->getDeckCount();
        $result['visibleCards'] = $this->powerCards->getTable();
        $result['topDeckCard'] = $this->powerCards->getTopDeckCard();

        foreach ($result['players'] as $playerId => &$playerDb) {
            if (intval($playerDb['score']) > MAX_POINT) {
                $playerDb['score'] = MAX_POINT;
            }

            $playerDb['cards'] = $this->powerCards->getCardsInLocation('hand', $playerId);
            $playerDb['reservedCards'] = $this->powerCards->getCardsInLocation('reserved'.$playerId);

            foreach($playerDb['cards'] as &$card) {
                if ($card->type == MIMIC_CARD) {
                    $card->mimicType = $this->getMimickedCardType(MIMIC_CARD);
                }
            }

            $playerDb['poisonTokens'] = intval($playerDb['poisonTokens']);
            $playerDb['shrinkRayTokens'] = intval($playerDb['shrinkRayTokens']);
            $playerDb['playerDead'] = intval($playerDb['playerDead']);
            $playerDb['zombified'] = boolval($playerDb['zombified']);

            $playerDb['rapidHealing'] = $this->countCardOfType($playerId, RAPID_HEALING_CARD) > 0;
            $playerDb['maxHealth'] = $this->getPlayerMaxHealth($playerId);
            
            if ($isCybertoothExpansion) {
                $playerDb['berserk'] = boolval($playerDb['berserk']);
            }
            if ($isCthulhuExpansion) {
                $playerDb['cultists'] = intval($playerDb['cultists']);
            }
            if ($isWickednessExpansion) {
                $playerDb['wickedness'] = intval($playerDb['wickedness']);
                $playerDb['wickednessTiles'] = $this->wickednessTiles->getPlayerTiles($playerId);

                foreach($playerDb['wickednessTiles'] as &$card) {
                    if ($card->type == FLUXLING_WICKEDNESS_TILE) {
                        $card->mimicType = $this->getMimickedCardType(FLUXLING_WICKEDNESS_TILE);
                    }
                }
            }

            if ($isPowerUpExpansion) {
                $playerDb['visibleEvolutions'] = $this->getEvolutionCardsByLocation('table', $playerId);
                $playerDb['hiddenEvolutions'] = $this->getEvolutionCardsByLocation('hand', $playerId);
                $playerDb['ownedEvolutions'] = $this->getEvolutionCardsByOwner($playerId);
                
                $mothershipSupportCards = $this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION, true, false);
                $playerDb['mothershipSupport'] = count($mothershipSupportCards) > 0;
                $playerDb['mothershipSupportUsed'] = count($mothershipSupportCards) > 0 && Arrays::every($mothershipSupportCards, fn($card) => $this->isUsedCard(3000 + $card->id));

                $playerDb['superiorAlienTechnologyTokens'] = $this->getSuperiorAlienTechnologyTokens($playerId);
            }
        }

        $result['mimickedCards'] = [
            'card' => $this->getMimickedCard(MIMIC_CARD),
            'tile' => $this->getMimickedCard(FLUXLING_WICKEDNESS_TILE),
            'evolution' => $this->getMimickedEvolution(),
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
        $result['mindbugExpansion'] = $this->mindbugExpansion->isActive();
        $result['darkEdition'] = $isDarkEdition;
        $result['origins'] = $isOrigins;

        if ($isWickednessExpansion) {
            $result['wickednessTiles'] = $this->wickednessTiles->getTable();
        }

        if ($isPowerUpExpansion) {
            $result['EVOLUTION_CARDS_TYPES'] = $this->EVOLUTION_CARDS_TYPES;
            $result['EVOLUTION_CARDS_SINGLE_STATE'] = [ // only temporary, to show them as disabled
                'beforeStartTurn' => $this->EVOLUTION_TO_PLAY_BEFORE_START_TEMPORARY, // ST_PLAYER_BEFORE_START_TURN
                'beforeResolveDice' => $this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE, // ST_PLAYER_BEFORE_RESOLVE_DICE_MULTI
                'beforeEnteringTokyo' => $this->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO, // ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO
                'afterEnteringTokyo' => $this->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO + $this->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO, // ST_PLAYER_AFTER_ENTERING_TOKYO
                'cardIsBought' => $this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT, // ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT
            ];

            $targetedPlayer = intval($this->getGameStateValue(TARGETED_PLAYER));
            if ($targetedPlayer > 0) {
                $result['targetedPlayer'] = $targetedPlayer;
            }

            if (array_key_exists($current_player_id, $result['players'])) {
                $result['askPlayEvolution'] = intval($result['players'][$current_player_id]['askPlayEvolution']);
            }
        }

        if ($this->mindbugExpansion->isActive()) {
            $this->mindbugExpansion->fillResult($result);
        }
        if ($isAnubisExpansion) {
            $this->anubisExpansion->fillResult($result);
        }
        if ($isKingKongExpansion) {
            $this->kingKongExpansion->fillResult($result);
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
    function getGameProgression()/*: int*/ {
        $stateId = $this->gamestate->getCurrentMainStateId(); 
        if ($stateId === 99) {
            return 100;
        }

        return $this->getMaxPlayerScore() * 5;
    }

    #[CheckAction(false)]
    function actPlayEvolution(int $id, int $currentPlayerId): void {
        $card = $this->powerUpExpansion->evolutionCards->getCardById($id);

        if ($card->location != 'hand') {
            throw new \BgaUserException('Evolution card is not in your hand');
        }

        $this->powerUpExpansion->checkCanPlayEvolution($card, $currentPlayerId);

        $this->powerUpExpansion->applyPlayEvolution($currentPlayerId, $card);

        // if the player has no more evolution cards, we skip the state for him
        if ($this->powerUpExpansion->evolutionCards->countCardsInLocation('hand', $currentPlayerId) == 0) {
            $stateId = $this->gamestate->getCurrentMainStateId();

            if (in_array($stateId, [ST_PLAYER_BEFORE_START_TURN, ST_PLAYER_DURING_RESOLVE_DICE])) {
                $this->goToState($stateId);
            }
        }
    }

    public function incBaseDice(int $playerId, int $inc): void {
        $this->DbQuery("UPDATE `player` SET `player_base_dice` = `player_base_dice` + $inc WHERE `player_id` = $playerId");

        $message = $inc >= 0 ? '' : clienttranslate('${player_name} will play with ${number} less dice for the rest of the game.');

        $this->notify->all('incBaseDice', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'number' => abs($inc),
        ]);
    }

    public function getBaseDice(int $playerId): int {
        return (int)$this->getUniqueValueFromDB("SELECT `player_base_dice` FROM `player`WHERE `player_id` = $playerId");
    }

    function canPickMonster(): bool {
        return $this->tableOptions->get(PICK_MONSTER_OPTION) === 2;
    }

    /**
     * @return int[]
     */
    public function getGameMonsters(): array {
        $bonusMonsters = $this->tableOptions->get(BONUS_MONSTERS_OPTION) == 2;
        $isDarkEdition = $this->isDarkEdition();
        $isOrigins = $this->isOrigins();

        // Base game monsters : Space Penguin, Alienoid, Cyber Kitty, The King, Gigazaur, Meka Dragon
        $monsters = $isOrigins ? [51,52,53,54] : ($isDarkEdition ? [102,104,105,106,114,115] : [1,2,3,4,5,6]);

        // Boogie Woogie, Pumpkin Jack
        if ($bonusMonsters || $this->isHalloweenExpansion()) {
            $monsters = [...$monsters, 7, 8];
        }

        // Cthulhu, Anubis
        if ($bonusMonsters || $this->cthulhuExpansion->isActive() || $this->anubisExpansion->isActive()) {
            $monsters = [...$monsters, 9, 10];
        }

        // King Kong, Cybertooth
        if ($bonusMonsters || $this->kingKongExpansion->isActive() || $this->cybertoothExpansion->isActive()) {
            $monsters = [...$monsters, 11, 12];
        }

        // Pandakaï
        if ($bonusMonsters || $this->powerUpExpansion->isActive()) {
            if ($isDarkEdition) {
                if ($bonusMonsters) {
                    $monsters = [...$monsters, 13];
                }
            } else {
                $monsters = [...$monsters, 13];
            }
        }

        // Kookie, X-Smash Tree
        if ($bonusMonsters) {
            $monsters = [...$monsters, 16, 17];
        }

        // Baby Gigazaur
        if ($bonusMonsters) {
            $monsters = [...$monsters, 18];
        }

        // Lollybot
        if ($bonusMonsters/* && $this->releaseDatePassed("2022-04-17T11:00:00", 2)*/) {
            $monsters = [...$monsters, 19];
        }

        // Rob
        if ($bonusMonsters/* && $this->releaseDatePassed("2022-05-04T11:00:00", 2)*/) {
            $monsters = [...$monsters, 21];
        }

        if ($bonusMonsters) {
            // World tour
            /*if ($this->releaseDatePassed("2022-07-01T00:00:00", 2) && !$this->releaseDatePassed("2022-07-08T00:00:00", 2)) {
                $monsters = [...$monsters, 31];
            }
            if ($this->releaseDatePassed("2022-07-08T00:00:00", 2) && !$this->releaseDatePassed("2022-07-15T00:00:00", 2)) {
                $monsters = [...$monsters, 32];
            }
            if ($this->releaseDatePassed("2022-07-15T00:00:00", 2) && !$this->releaseDatePassed("2022-07-22T00:00:00", 2)) {
                $monsters = [...$monsters, 33];
            }
            if ($this->releaseDatePassed("2022-07-22T00:00:00", 2) && !$this->releaseDatePassed("2022-07-29T00:00:00", 2)) {
                $monsters = [...$monsters, 34];
            }
            if ($this->releaseDatePassed("2022-07-29T00:00:00", 2) && !$this->releaseDatePassed("2022-08-05T00:00:00", 2)) {
                $monsters = [...$monsters, 35];
            }
            if ($this->releaseDatePassed("2022-08-05T00:00:00", 2) && !$this->releaseDatePassed("2022-08-12T00:00:00", 2)) {
                $monsters = [...$monsters, 36];
            }
            if ($this->releaseDatePassed("2022-08-12T00:00:00", 2) && !$this->releaseDatePassed("2022-08-19T00:00:00", 2)) {
                $monsters = [...$monsters, 37];
            }
            if ($this->releaseDatePassed("2022-08-19T00:00:00", 2) && !$this->releaseDatePassed("2022-08-26T00:00:00", 2)) {
                $monsters = [...$monsters, 38];
            }*/
            // KoMI
            /*if ($this->releaseDatePassed("2022-11-18T00:00:00", 1) && !$this->releaseDatePassed("2023-01-02T00:00:00", 1)) {
                $monsters = [...$monsters, 41, 42, 43, 44, 45];
            }*/

            if ($isOrigins) {
                $monsters = [...$monsters, 1,2,3,4,5,6];
            } else {
                $monsters = [...$monsters, 51,52,53,54];
            }
        }

        // Gigasnail Hydra, MasterMindbug, Sharky Crab-dog Mummypus-Zilla
        if ($bonusMonsters || $this->mindbugExpansion->isActive()) {             
            $monsters = Game::getBgaEnvironment()==='studio' ? [...$monsters, 61, 62, 63] : [...$monsters, /*61,*/ 62, /*63*/]; // TODOMB
        }

        if ($this->wickednessExpansion->isActive()) {
            $monsters = array_values(array_filter($monsters, fn($monster) => in_array($monster, MONSTERS_WITH_ICON)));            
        }

        if ($this->powerUpExpansion->isActive()) {
            $monsters = array_values(array_filter($monsters, fn($monster) => in_array($monster % 100, $this->powerUpExpansion->getMonstersWithPowerUpCards())));            
        }
        
        return $monsters;
    }

    function saveMonsterStat(int $playerId, int $monsterId, bool $automatic): void {
        $this->setStat($monsterId, 'monster', $playerId);
        $this->setStat($monsterId, $automatic ? 'monsterAutomatic': 'monsterPick', $playerId);
    }

    function isBeastForm(int $playerId): bool {
        $formCard = $this->getFormCard($playerId);
        return $formCard != null && $formCard->side == 1;
    }

    function isInitialCardDistributionComplete(): bool {
        return ($this->isHalloweenExpansion() && $this->everyPlayerHasCostumeCard()) || ($this->powerUpExpansion->isActive() && $this->everyPlayerHasEvolutionCard());
    }

    private function everyPlayerHasCostumeCard(): bool {
        $playersIds = $this->getNonZombiePlayersIds();
        foreach($playersIds as $playerId) {
            $cardsOfPlayer = $this->powerCards->getPlayerReal($playerId);
            if (!Arrays::some($cardsOfPlayer, fn($card) => $card->type > 200 && $card->type < 300)) {
                return false;
            }
        }
        return true;
    }

    private function everyPlayerHasEvolutionCard(): bool {
        $playersIds = $this->getNonZombiePlayersIds();
        foreach($playersIds as $playerId) {
            if ($this->powerUpExpansion->evolutionCards->countCardsInLocation('hand', $playerId) == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return int[]
     */
    function getStackedStates(): array {
        $states = $this->getGlobalVariable(STACKED_STATES, true);
        return $states == null ? [] : $states;
    }

    function addStackedState(?int $currentState = null): void {
        $states = $this->getStackedStates();
        $states[] = $currentState ?? $this->gamestate->getCurrentMainStateId();
        $this->setGlobalVariable(STACKED_STATES, $states);
    }

    function removeStackedStateAndRedirect(): void {
        $states = $this->getStackedStates();
        if (count($states) < 1) {
            throw new \Exception('No stacked state to remove');
        }
        $newState = array_pop($states);
        $this->setGlobalVariable(STACKED_STATES, $states);
        $this->goToState($newState);
    }

    function getStackedStateSuffix(): string {
        $states = $this->getStackedStates();
        return count($states) > 0 ? ''.count($states) : '';
    }

    function getQuestion()/*: object of Question*/ {
        return $this->getGlobalVariable(QUESTION.$this->getStackedStateSuffix());
    }

    function setQuestion(/*object of Question*/ $question): void {
        $this->setGlobalVariable(QUESTION.$this->getStackedStateSuffix(), $question);
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
 
        if ($from_version <= 2203271836) {
            $sql = "ALTER TABLE `DBPREFIX_player` MODIFY COLUMN `player_energy` SMALLINT";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2204102300) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_energy` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2204120846) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_health` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2204171618) {
            $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `player` LIKE 'player_turn_energy'");
            if (is_null($result)) {
              $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_energy` tinyint unsigned NOT NULL DEFAULT 0";
              self::applyDbUpgradeToAllDB($sql);
            }
            $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `player` LIKE 'player_turn_health'");
            if (is_null($result)) {
              $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_health` tinyint unsigned NOT NULL DEFAULT 0";
              self::applyDbUpgradeToAllDB($sql);
            }
        }

        if ($from_version <= 2205091813) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_entered_tokyo` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2205101844) {
            $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `player` LIKE 'player_turn_entered_tokyo'");
            if (is_null($result)) {
                $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_entered_tokyo` tinyint unsigned NOT NULL DEFAULT 0";
                self::applyDbUpgradeToAllDB($sql);
            }
        }

        if ($from_version <= 2206121517) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_take_wickedness_tiles` varchar(15) DEFAULT '[]'";
            self::applyDbUpgradeToAllDB($sql);
            $sql = "UPDATE `DBPREFIX_player` SET `player_take_wickedness_tiles` = CONCAT('[', `player_take_wickedness_tile`, ']') WHERE `player_take_wickedness_tile` <> 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2206121552) {
            $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `player` LIKE 'player_take_wickedness_tiles'");
            if (is_null($result)) {
                $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_take_wickedness_tiles` varchar(15) DEFAULT '[]'";
                self::applyDbUpgradeToAllDB($sql);
                $sql = "UPDATE `DBPREFIX_player` SET `player_take_wickedness_tiles` = CONCAT('[', `player_take_wickedness_tile`, ']') WHERE `player_take_wickedness_tile` <> 0";
                self::applyDbUpgradeToAllDB($sql);
            }
        }

        if ($from_version <= 2208051621) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `ask_play_evolution` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2209041822) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_gained_health` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2209042230) {
            $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `player` LIKE 'player_turn_gained_health'");
            if (is_null($result)) {
                $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_turn_gained_health` tinyint unsigned NOT NULL DEFAULT 0";
                self::applyDbUpgradeToAllDB($sql);
            }
        }

        if ($from_version <= 2209111657) {
            $sql = "ALTER TABLE `DBPREFIX_turn_damages` ADD `claw_damages` tinyint unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2209141913) {
            $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `turn_damages` LIKE 'claw_damages'");
            if (is_null($result)) {
                $sql = "ALTER TABLE `DBPREFIX_turn_damages` ADD `claw_damages` tinyint unsigned NOT NULL DEFAULT 0";
                self::applyDbUpgradeToAllDB($sql);
            }
        }

        if ($from_version <= 2411291203) {
            $sql = "ALTER TABLE `DBPREFIX_wickedness_tile` ADD `order` INT DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2508011620) {
            $sql = "ALTER TABLE `DBPREFIX_curse_card` ADD `order` INT DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2508121245) {
            $sql = "ALTER TABLE `DBPREFIX_evolution_card` ADD `order` INT DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2508131511) {
            $sql = "ALTER TABLE `DBPREFIX_card` ADD `order` INT DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2510131042) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_base_dice` tinyint unsigned NOT NULL DEFAULT 6";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2510171738) {
            self::applyDbUpgradeToAllDB("ALTER TABLE `DBPREFIX_card` ADD `used` INT NULL DEFAULT NULL");
            self::applyDbUpgradeToAllDB("ALTER TABLE `DBPREFIX_card` ADD `activated` JSON NULL DEFAULT NULL");
            self::applyDbUpgradeToAllDB("ALTER TABLE `DBPREFIX_evolution_card` ADD `used` INT NULL DEFAULT NULL");
            self::applyDbUpgradeToAllDB("ALTER TABLE `DBPREFIX_evolution_card` ADD `activated` JSON NULL DEFAULT NULL");
        }
    }
}
