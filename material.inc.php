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
 * material.inc.php
 *
 * KingOfTokyo game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->CARD_COST = [
    // KEEP
    1 => 6,
    2 => 3,
    3 => 5,
    4 => 4,
    5 => 4,
    6 => 5,
    7 => 3,
    8 => 3,
    9 => 3,
    10 => 4,
    11 => 3,
    12 => 4,
    13 => 7, 14 => 7,
    15 => 4,
    16 => 5,
    17 => 3,
    18 => 5,
    19 => 4,
    20 => 4,
    21 => 5,
    22 => 3,
    23 => 7,
    24 => 5,
    25 => 2,
    26 => 3,
    27 => 8,
    28 => 3,
    29 => 7,
    30 => 4,
    31 => 3,
    32 => 4,
    33 => 3,
    34 => 3,
    35 => 4,
    36 => 3,
    37 => 3,
    38 => 4,
    39 => 3,
    40 => 6,
    41 => 4,
    42 => 2,
    43 => 5,
    44 => 3,
    45 => 4,
    46 => 4,
    47 => 3,
    48 => 6,
    50 => 3,

    // DISCARD
    101 => 5,
    102 => 4,
    103 => 3,
    104 => 5,
    105 => 8,
    106 => 7, 107 => 7,
    108 => 3,
    109 => 7,
    110 => 6,
    111 => 3,
    112 => 4,
    113 => 5,
    114 => 3,
    115 => 6,
    116 => 6,
    117 => 4,
    118 => 6,
    119 => 0,

    // COSTUME
    201 => 4,
    202 => 4,
    203 => 3,
    204 => 4,
    205 => 3,
    206 => 4,
    207 => 5,
    208 => 4,
    209 => 3,
    210 => 4,
    211 => 4,
    212 => 3,

];

// JSON.stringify(Array.from(Array(48)).map((_, index) => index + 1))
$this->KEEP_CARDS_LIST = [
    'base' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48],
    'dark' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,  21,22,23,24,25,26,  29,30,31,32,33,34,  36,37,38,  40,41,42,43,44,45,46,47,48, 49,50,51,52,53,54,55],
];

$this->DISCARD_CARDS_LIST = [
    'base' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
    'dark' => [1,2,3,4,5,6,7,8,9,10,  12,13,  15,16,17,18,19],
];