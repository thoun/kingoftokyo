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
    // base game
    1 => "Space Penguin", 
    2 => "Alienoid", 
    3 => "Cyber Kitty", 
    4 => "The King", 
    5 => "Gigazaur", 
    6 => "Meka Dragon",
    // halloween 
    7 => "Boogie Woogie", 
    8 => "Pumpkin Jack", 
    // monster packs
    9 => "Cthulhu", 
    10 => "Anubis", 
    11 => "King Kong", 
    12 => "Cybertooth", 
    // power up
    13 => "Pandakaï", 
    // 14 cyberbunny
    // 15 kraken
    // promo
    16 => "Kookie", 
    17 => "X-Smash Tree", 
    18 => "Baby Gigazaur",
    19 => "Lollybot",
    // KONY
    21 => "Rob",
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
        "gainedCultists" => ["id" => 48, "type" => "int", "name" => totranslate("Gained cultists")],
        "cultistReroll" => ["id" => 49, "type" => "int", "name" => totranslate("Cultist used for reroll")],
        "cultistHeal" => ["id" => 50, "type" => "int", "name" => totranslate("Cultist used for healing")],
        "cultistEnergy" => ["id" => 51, "type" => "int", "name" => totranslate("Cultist used for energy")],
        "tokyoTowerLevel1claimed" => ["id" => 52, "type" => "int", "name" => totranslate("Tokyo Tower level 1 claimed")],
        "tokyoTowerLevel2claimed" => ["id" => 53, "type" => "int", "name" => totranslate("Tokyo Tower level 2 claimed")],
        "tokyoTowerLevel3claimed" => ["id" => 54, "type" => "int", "name" => totranslate("Tokyo Tower level 3 claimed")],
        "bonusFromTokyoTowerLevel1applied" => ["id" => 55, "type" => "int", "name" => totranslate("Bonus for Tokyo Tower level 1 applied")],
        "bonusFromTokyoTowerLevel2applied" => ["id" => 56, "type" => "int", "name" => totranslate("Bonus for Tokyo Tower level 2 applied")],
        "dieOfFateEye" => ["id" => 57, "type" => "int", "name" => totranslate("Changed card with die of fate")],
        "dieOfFateRiver" => ["id" => 58, "type" => "int", "name" => totranslate("No effect with die of fate")],
        "dieOfFateSnake" => ["id" => 59, "type" => "int", "name" => totranslate("Snake effect with die of fate")],
        "dieOfFateAnkh" => ["id" => 60, "type" => "int", "name" => totranslate("Ankh effect with die of fate")],
        "berserkActivated" => ["id" => 61, "type" => "int", "name" => totranslate("Berserk mode activated")],
        "turnsInBerserk" => ["id" => 62, "type" => "int", "name" => totranslate("Turns in Berserk mode")],
        "formChanged" => ["id" => 63, "type" => "int", "name" => totranslate("Form change (Mutant Evolution)")],
        "turnsInBipedForm" => ["id" => 64, "type" => "int", "name" => totranslate("Turns in Biped form")],
        "turnsInBeastForm" => ["id" => 65, "type" => "int", "name" => totranslate("Turns in Beast form")],
        // TODOWI change id !!! "gainedWickedness" => ["id" => x, "type" => "int", "name" => totranslate("Wickedness")],
        // TODOWI change id !!! "wickednessTilesTaken" => ["id" => x, "type" => "int", "name" => totranslate("Wickedness tales taken")],
    ],

    "value_labels" => [
		100 => $monstersNames,
		101 => $monstersNames,
		102 => $monstersNames,
    ]
];
