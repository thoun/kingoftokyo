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

$this->MONSTERS_WITH_ICON = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,18]; // TODODE?

$this->MONSTERS_WITH_POWER_UP_CARDS = [1,2,3,4,5,6,13,14,15]; // TODODE add cyberbunny & kraken [101,102,103,104,105,106]

// 1 : permanent
// 2 : temporary
// 3 : gift
$this->EVOLUTION_CARDS_TYPES = [
    11 => 1, 12 => 1, 13 => 2, 14 => 2, 15 => 1, 16 => 2, 17 => 1, 18 => 1, // Space Penguin
    21 => 2, 22 => 2, 23 => 2, 24 => 1, 25 => 1, 26 => 1, 27 => 1, 28 => 1, // Alienoid
    31 => 2, 32 => 2, 33 => 2, 34 => 2, 35 => 1, 36 => 1, 37 => 1, 38 => 1, // Cyber Kitty
    41 => 2, 42 => 2, 43 => 2, 44 => 2, 45 => 1, 46 => 1, 47 => 1, 48 => 1, // The King
    51 => 2, 52 => 2, 53 => 2, 54 => 2, 55 => 1, 56 => 1, 57 => 1, 58 => 1, // Gigazaur
    61 => 2, 62 => 2, 63 => 1, 64 => 2, 65 => 1, 66 => 1, 67 => 1, 68 => 1, // Meka Dragon
    131 => 2, 132 => 2, 133 => 2, 134 => 2, 135 => 1, 136 => 1, 137 => 1, 138 => 1, // Pandakaï
    141 => 2, 142 => 2, 143 => 2, 144 => 2, 145 => 1, 146 => 1, 147 => 1, 148 => 1, // Cyber Bunny
    151 => 2, 152 => 2, 153 => 2, 154 => 2, 155 => 1, 156 => 1, 157 => 1, 158 => 1, // Kraken
];

// remove the evolutions 5s after use
$this->AUTO_DISCARDED_EVOLUTIONS = [
    21, 22, 23,
    32, 33,
    44, 
    52, 53,
    62,
    131, 132, 134,
    141, 142,
    151, 152, 153, 154,
];

$this->EVOLUTION_TO_PLAY_BEFORE_START = [
    CAT_NIP_EVOLUTION,
    TUNE_UP_EVOLUTION,
    BAMBOO_SUPPLY_EVOLUTION,
    //- 87 épée énergétique (cyber bunny)
    //- 95 temple englouti (Kraken)
];

$this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE = [
    MECHA_BLAST_EVOLUTION,
    DESTRUCTIVE_ANALYSIS_EVOLUTION,
    UNDERRATED_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO = [
    FELINE_MOTOR_EVOLUTION,
    MONKEY_RUSH_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT = [
    BAMBOOZLE_EVOLUTION,
];

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
    49 => 4,
    50 => 3,
    51 => 2,
    52 => 6,
    53 => 4,
    54 => 3,
    55 => 4,

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