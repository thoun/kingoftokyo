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

$this->MONSTERS_WITH_POWER_UP_CARDS = [1,2,3,4,5,6,/* TODOPUHA 7,8,*//*TODOPUKK 11,*/13,14,15/* TODOPUBG ,18*/, 61, 62, 63];

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
    71 => 2, 72 => 3, 73 => 3, 74 => 1, 75 => 2, 76 => 2, 77 => 1, 78 => 2, // Boogie Woogie
    81 => 3, 82 => 3, 83 => 2, 84 => 1, 85 => 1, 86 => 2, 87 => 1, 88 => 2, // Pumpkin Jack
    111 => 2, 112 => 2, 113 => 2, 114 => 2, 115 => 1, 116 => 1, 117 => 1, 118 => 1, // King Kong
    131 => 2, 132 => 2, 133 => 2, 134 => 2, 135 => 1, 136 => 1, 137 => 1, 138 => 1, // Pandakaï
    141 => 2, 142 => 2, 143 => 2, 144 => 2, 145 => 1, 146 => 1, 147 => 1, 148 => 1, // Cyber Bunny
    151 => 2, 152 => 2, 153 => 2, 154 => 2, 155 => 1, 156 => 1, 157 => 1, 158 => 1, // Kraken
    181 => 1, 182 => 1, 183 => 2, 184 => 1, 185 => 1, 186 => 1, 187 => 2, 188 => 2, // Baby Gigazaur
    611 => 2, 612 => 1, 613 => 1, 614 => 1, 615 => 2, 616 => 1, 617 => 2, 618 => 2, // Gigasnail Hydra
    621 => 2, 622 => 1, 623 => 2, 624 => 1, 625 => 2, 626 => 1, 627 => 2, 628 => 2, // MasterMindbug
    631 => 2, 632 => 2, 633 => 1, 634 => 2, 635 => 1, 636 => 1, 637 => 1, 638 => 1, // Sharky Crab-dog Mummypus-Zilla
];

$this->EVOLUTION_CARDS_TYPES_FOR_STATS = [
    1 => 'PermanentEvolution',
    2 => 'TemporaryEvolution',
    3 => 'GiftEvolution',
];

// remove the evolutions 5s after use
$this->AUTO_DISCARDED_EVOLUTIONS = [
    ALIEN_SCOURGE_EVOLUTION, PRECISION_FIELD_SUPPORT_EVOLUTION, ANGER_BATTERIES_EVOLUTION, // Alienoid
    ELECTRO_SCRATCH_EVOLUTION, NINE_LIVES_EVOLUTION, // Cyber Kitty
    GIANT_BANANA_EVOLUTION, // The King
    RADIOACTIVE_WASTE_EVOLUTION, PRIMAL_BELLOW_EVOLUTION, // Gigazaur
    DESTRUCTIVE_ANALYSIS_EVOLUTION, TUNE_UP_EVOLUTION, // Meka Dragon 
    WELL_OF_SHADOW_EVOLUTION, WORM_INVADERS_EVOLUTION, // Boogie Woogie
    SMASHING_PUMPKIN_EVOLUTION, CANDY_EVOLUTION, // Pumpkin Jack
    SON_OF_KONG_KIKO_EVOLUTION, // King Kong
    PANDA_MONIUM_EVOLUTION, EATS_SHOOTS_AND_LEAVES_EVOLUTION, BEAR_NECESSITIES_EVOLUTION, // Pandakaï
    HEART_OF_THE_RABBIT_EVOLUTION, STROKE_OF_GENIUS_EVOLUTION, EMERGENCY_BATTERY_EVOLUTION, // Cyber Bunny
    HEALING_RAIN_EVOLUTION, DESTRUCTIVE_WAVE_EVOLUTION, CULT_WORSHIPPERS_EVOLUTION, // Kraken
];

$this->EVOLUTION_TO_PLAY_BEFORE_START_PERMANENT = [
    BAMBOO_SUPPLY_EVOLUTION,
    ENERGY_SWORD_EVOLUTION,
    SUNKEN_TEMPLE_EVOLUTION,
    FOLLOW_THE_CUBES_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_START_TEMPORARY = [
    CAT_NIP_EVOLUTION,
    TUNE_UP_EVOLUTION,
    SHARK_ATTACK_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_START = array_merge($this->EVOLUTION_TO_PLAY_BEFORE_START_PERMANENT, $this->EVOLUTION_TO_PLAY_BEFORE_START_TEMPORARY);

$this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE_ACTIVE = [
    // cards that can be played by active player
    MECHA_BLAST_EVOLUTION,
    DESTRUCTIVE_ANALYSIS_EVOLUTION,
    UNDERRATED_EVOLUTION,
    HIGH_TIDE_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE_MULTI = [
    HELPFUL_MINDBUG_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE = array_merge($this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE_MULTI, $this->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE_ACTIVE);;

$this->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO = [
    FELINE_MOTOR_EVOLUTION,
    MONKEY_RUSH_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO = [
    EATS_SHOOTS_AND_LEAVES_EVOLUTION,
    DESTRUCTIVE_WAVE_EVOLUTION,
];
$this->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO = [
    JUNGLE_FRENZY_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT = [
    BAMBOOZLE_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_END_MULTI = [
    ANGER_BATTERIES_EVOLUTION,
    STROKE_OF_GENIUS_EVOLUTION,
    CULT_WORSHIPPERS_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_END_ACTIVE = [
    // in case it's picked just before turn end (no buy phase)
    COLD_WAVE_EVOLUTION, 
    BLIZZARD_EVOLUTION,
];

$this->EVOLUTION_TO_PLAY_BEFORE_END = array_merge($this->EVOLUTION_TO_PLAY_BEFORE_END_MULTI, $this->EVOLUTION_TO_PLAY_BEFORE_END_ACTIVE);

$this->EVOLUTIONS_TO_HEAL = [ // evolutionType => heal amount (null if variable)

    // Space Penguin
    DEEP_DIVE_EVOLUTION => null, // in case Heal or Even bigger is picked on cards
    // Alienoid
    PRECISION_FIELD_SUPPORT_EVOLUTION => null, // in case Even bigger is picked on cards
    // Cyber Kitty
    // The King
    GIANT_BANANA_EVOLUTION => 2,
    // Gigazaur
    RADIOACTIVE_WASTE_EVOLUTION => 1,
    // Meka Dragon    
    // Boogie Woogie
    WELL_OF_SHADOW_EVOLUTION => 2,
    // Pumpkin Jack
    FEAST_OF_CROWS_EVOLUTION => null,
    CANDY_EVOLUTION => null,
    // Pandakaï
    BEAR_NECESSITIES_EVOLUTION => 2,
    // Cyber Bunny
    // Kraken
    HEALING_RAIN_EVOLUTION => 2,
    CULT_WORSHIPPERS_EVOLUTION => null,
    // Baby Gigazaur
    YUMMY_YUMMY_EVOLUTION => 2,
];

$this->EVOLUTION_GIFTS = array_keys(array_filter($this->EVOLUTION_CARDS_TYPES, fn($evolutionType) => $evolutionType == 3));
