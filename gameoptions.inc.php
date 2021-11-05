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
 * gameoptions.inc.php
 *
 * KingOfTokyo game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in kingoftokyo.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */
require_once("modules/constants.inc.php");

$game_options = [

    HALLOWEEN_EXPANSION_OPTION => [
        'name' => totranslate('Halloween event (Costume cards)'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
            ],
            2 => [
                'name' => totranslate('Enabled'),
            ],
        ],
        'default' => 1,
    ],

    CTHULHU_EXPANSION_OPTION => [
        'name' => totranslate('Battle of the Gods (Cultists)'), // 'Cthlhu event (Cultists)'
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
            ],
            2 => [
                'name' => totranslate('Enabled'),
            ],
        ],
        'default' => 1,
    ],

    // TODO Wickedness game mechanics ?

    /* TODOKK KINGKONG_EXPANSION_OPTION => [
        'name' => totranslate('King Kong event (Tokyo Tower)'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
            ],
            2 => [
                'name' => totranslate('Enabled'),
            ],
        ],
        'default' => 1,
    ],*/

    /* TODOAN ANUBIS_EXPANSION_OPTION => [
        'name' => totranslate('Anubis event (Curse die)'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
            ],
            2 => [
                'name' => totranslate('Enabled'),
            ],
        ],
        'default' => 1,
    ],*/

    /* TODOCY 

    CYBERTOOTH_EXPANSION_OPTION => [
        'name' => totranslate('Cybertooth event (Berserk)'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
            ],
            2 => [
                'name' => totranslate('Enabled'),
            ],
        ],
        'default' => 1,
    ],

    MUTANT_EVOLUTION_VARIANT_OPTION => [
        'name' => totranslate('Mutant Evolutions variant (Transformation card)'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
            ],
            2 => [
                'name' => totranslate('Enabled'),
            ],
        ],
        'default' => 1,
    ],*/

    /* note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.*/
    100 => [
        'name' => totranslate('Pick monster'),
        'values' => [
            1 => [
                'name' => totranslate('Automatic'), 
                'description' => totranslate("A random monster is automatically picked"),
                'tmdisplay' => totranslate('Random monster'),
            ],
            2 => [
                'name' => totranslate('Pick'), 
                'description' => totranslate("Every player choose it's monster before playing"),
                'tmdisplay' => totranslate('Players can pick a monster'),
            ],
        ],
        'default' => 1,
    ],

    BONUS_MONSTERS_OPTION => [
        'name' => totranslate('Bonus monsters'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
                'description' => totranslate("Only monsters from game version")
            ],
            2 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate("Include bonus/promo monsters"),
            ],
        ],
        'default' => 2,
    ],

    110 => [
        'name' => totranslate('Skip phase with no possible actions'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
                'description' => totranslate("Game phases are always visible, with a timer if no possible actions")
            ],
            2 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate("Game phases are skipped if no possible actions"),
                'tmdisplay' => totranslate('Skip phases with no possible actions'),
            ],
        ],
        'default' => 1,
        'nobeginner' => true
    ],

    120 => [
        'name' => totranslate('2-players variant'),
        'values' => [
            1 => [
                'name' => totranslate('Disabled'),
            ],
            2 => [
                'name' => totranslate('Enabled'),
                'description' => totranslate("When entering or starting a turn on Tokyo, you gain 1 energy instead of points"),
            ],
        ],
        'default' => 2,
        'displaycondition' => [[
            'type' => 'maxplayers',
            'value' => 2,
        ]]
    ],
];

$game_preferences = [
    202 => [
        'name' => totranslate('Countdown timer when no action is possible'),
        'needReload' => false,
        'values' => [
            1 => ['name' => totranslate('Enabled')],
            2 => ['name' => totranslate('Disabled')],
        ],
        'default' => 1,
    ],
    
    204 => [
        'name' => totranslate('Background'),
        'needReload' => false,
        'values' => [
            0 => ['name' => totranslate('Automatic')],
            1 => ['name' => totranslate('Base game')],
            2 => ['name' => totranslate('Halloween event')],
        ],
        'default' => 0
    ],

    205 => [
        'name' => totranslate('Dice'),
        'needReload' => false,
        'values' => [
            0 => ['name' => totranslate('Automatic')],
            1 => ['name' => totranslate('Base game')],
            2 => ['name' => totranslate('Halloween event')],
        ],
        'default' => 0
    ],

    203 => [
        'name' => '',
        'needReload' => false,
        'values' => [
            1 => ['name' => totranslate('Enabled')],
            2 => ['name' => totranslate('Disabled')],
        ],
        'default' => 1
    ],

    201 => [
        'name' => totranslate('Font style'),
        'needReload' => false,
        'values' => [
            1 => [ 'name' => totranslate( 'Default font' )],
            2 => [ 'name' => totranslate( 'King of Tokyo font' )],
        ],
        'default' => 2
    ],
];


