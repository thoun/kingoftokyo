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
 * stats.inc.php
 *
 * KingOfTokyo game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice ("Your game configuration" section):
    http://en.studio.boardgamearena.com/admin/studio
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$monstersNames = [
    1 => "Space Penguin", 
    2 => "Alienoid", 
    3 => "Cyber Kitty", 
    4 => "The King", 
    5 => "Gigazaur", 
    6 => "Meka Dragon", 
    7 => "Boogie Woogie", 
    8 => "Pumpkin Jack", 
];

$commonStats = [
    "monster" => ["id" => 100, "type" => "int", "name" => "Monster", "display" => "limited"],
    "monsterAutomatic" => ["id" => 101, "type" => "int", "name" => totranslate("Monster (automatic)")],
    "monsterPick" => ["id" => 102, "type" => "int", "name" => totranslate("Monster (chosen)")],
];

$stats_type = [
    // Statistics global to table
    "table" => $commonStats + [
        "turnsNumber" => ["id" => 10, "type" => "int", "name" => totranslate("Number of turns")],
        "pointsWin" => ["id" => 11, "type" => "bool", "name" => totranslate("Won by points")],
        "eliminationWin" => ["id" => 12, "type" => "bool", "name" => totranslate("Won by elimination")],
        "survivorRatio" => ["id" => 20, "type" => "float", "name" => totranslate("Survivors ratio")],
    ],
    
    // Statistics existing for each player
    "player" => $commonStats + [
        "turnsNumber" => ["id" => 10, "type" => "int", "name" => totranslate("Number of turns")],
        "pointsWin" => ["id" => 11, "type" => "bool", "name" => totranslate("Won by points")],
        "eliminationWin" => ["id" => 12, "type" => "bool", "name" => totranslate("Won by elimination")],
        "survived" => ["id" => 30, "type" => "bool", "name" => totranslate("Survived")],
        "turnsInTokyo" => ["id" => 31, "type" => "int", "name" => totranslate("Turns in Tokyo")],
        "tokyoEnters" => ["id" => 32, "type" => "int", "name" => totranslate("Tokyo enters")],
        "tokyoLeaves" => ["id" => 33, "type" => "int", "name" => totranslate("Tokyo leaves")],
        "keepBoughtCards" => ["id" => 34, "type" => "int", "name" => totranslate("Bought cards (Keep)")],
        "discardBoughtCards" => ["id" => 35, "type" => "int", "name" => totranslate("Bought cards (Discard)")],
        "costumeBoughtCards" => ["id" => 46, "type" => "int", "name" => totranslate("Bought cards (Costume)")],
        "costumeStolenCards" => ["id" => 47, "type" => "int", "name" => totranslate("Stolen cards (Costume)")],
        "damageDealt" => ["id" => 36, "type" => "int", "name" => totranslate("Damage dealt")],
        "damage" => ["id" => 37, "type" => "int", "name" => totranslate("Life loss")],
        "heal" => ["id" => 38, "type" => "int", "name" => totranslate("Heal")],
        "wonEnergyCubes" => ["id" => 39, "type" => "int", "name" => totranslate("Won energy cubes")],
        "endScore" => ["id" => 40, "type" => "int", "name" => totranslate("End score (if game finished to points)")],
        "endHealth" => ["id" => 41, "type" => "int", "name" => totranslate("End life (if alive)")],
        "rethrownDice" => ["id" => 42, "type" => "int", "name" => totranslate("Rethrown dice")],
        "pointsWonWith1Dice" => ["id" => 43, "type" => "int", "name" => totranslate("Points won with 1 dice")],
        "pointsWonWith2Dice" => ["id" => 44, "type" => "int", "name" => totranslate("Points won with 2 dice")],
        "pointsWonWith3Dice" => ["id" => 45, "type" => "int", "name" => totranslate("Points won with 3 dice")],
    ],

    "value_labels" => [
		100 => $monstersNames,
		101 => $monstersNames,
		102 => $monstersNames,
    ]
];
