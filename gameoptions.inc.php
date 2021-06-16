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

$game_options = [

    /* note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.*/
    100 => [
        'name' => totranslate('Pick monster'),
        'values' => [
            1 => [
                'name' => totranslate('Automatic'), 
                'description' => totranslate("A random monster is automatically picked"),
            ],
            2 => [
                'name' => totranslate('Pick'), 
                'description' => totranslate("Every player choose it's mosnter before playing"),
            ],
        ],
        'default' => 1,
    ],
];

$game_preferences = [
    201 => [
        'name' => totranslate('Font style'),
        'needReload' => true, // after user changes this preference game interface would auto-reload => auto-reload deactivated, cpu intensive at setup
        'values' => [
            1 => [ 'name' => totranslate( 'Default font' )],
            2 => [ 'name' => totranslate( 'King of Tokyo font' )],
        ],
        'default' => 2
    ],
];


