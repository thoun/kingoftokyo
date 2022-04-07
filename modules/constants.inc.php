<?php

if(!defined('MAX_POINT')) {

define('MAX_POINT', 20);
define('START_LIFE', 10);

/*
 * State constants
 */
define('ST_BGA_GAME_SETUP', 1);

define('ST_START', 10);
define('ST_PLAYER_PICK_MONSTER', 11);
define('ST_PICK_MONSTER_NEXT_PLAYER', 12);

define('ST_MULTIPLAYER_PICK_EVOLUTION_DECK', 13);
define('ST_NEXT_PICK_EVOLUTION_DECK', 14);

define('ST_PLAYER_CHOOSE_INITIAL_CARD', 15);
define('ST_CHOOSE_INITIAL_CARD_NEXT_PLAYER', 16);


define('ST_START_GAME', 19);

define('ST_PLAYER_BEFORE_START_TURN', 25); // !
define('ST_QUESTIONS_BEFORE_START_TURN', 26); // !
define('ST_START_TURN', 20);
define('ST_PLAYER_CHANGE_MIMICKED_CARD', 21);
define('ST_MULTIPLAYER_GIVE_SYMBOL_TO_ACTIVE_PLAYER', 17); // !
define('ST_INITIAL_DICE_ROLL', 18); // !
define('ST_PLAYER_THROW_DICE', 22);
define('ST_PLAYER_CHANGE_DIE', 23);
define('ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE', 24);

define('ST_PREPARE_RESOLVE_DICE', 28);
define('ST_MULTIPLAYER_CHEERLEADER_SUPPORT', 29);
define('ST_RESOLVE_DICE', 30);
define('ST_RESOLVE_NUMBER_DICE', 31);
define('ST_RESOLVE_HEART_DICE', 32);
define('ST_RESOLVE_HEART_DICE_ACTION', 33);
define('ST_RESOLVE_ENERGY_DICE', 34);
define('ST_RESOLVE_SMASH_DICE', 35);
define('ST_RESOLVE_SMASH_DICE_ACTION', 73); // !
define('ST_RESOLVE_SKULL_DICE', 36);
define('ST_RESOLVE_DIE_OF_FATE', 37);
define('ST_CHOOSE_EVOLUTION_CARD', 39); // !

define('ST_MULTIPLAYER_CANCEL_DAMAGE', 38);

define('ST_MULTIPLAYER_LEAVE_TOKYO', 40);
define('ST_LEAVE_TOKYO_APPLY_JETS', 41);
define('ST_MULTIPLAYER_LEAVE_TOKYO_EXCHANGE_CARD', 42);
define('ST_MULTIPLAYER_HALF_MOVE_PHASE', 43);
define('ST_ENTER_TOKYO_APPLY_BURROWING', 45);
define('ST_ENTER_TOKYO', 46);

define('ST_PLAYER_STEAL_COSTUME_CARD', 49);
define('ST_PLAYER_CHANGE_FORM', 48); // !

define('ST_PLAYER_BUY_CARD', 50);
define('ST_PLAYER_CHOOSE_MIMICKED_CARD', 51);
define('ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD', 52);
define('ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD', 53);
define('ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT', 54);
define('ST_AFTER_WHEN_CARD_IS_BOUGHT', 55);

define('ST_PLAYER_SELL_CARD', 60);

define('ST_PLAYER_DISCARD_DIE', 61);
define('ST_PLAYER_DISCARD_KEEP_CARD', 62);
define('ST_PLAYER_GIVE_GOLDEN_SCARAB', 63);
define('ST_PLAYER_GIVE_SYMBOLS', 64);
define('ST_PLAYER_SELECT_EXTRA_DIE', 65);
define('ST_PLAYER_REROLL_OR_DISCARD_DICE', 66);
define('ST_MULTIPLAYER_REROLL_DICE', 67);

define('ST_PLAYER_TAKE_WICKEDNESS_TILE', 70);
define('ST_PLAYER_CHOOSE_MIMICKED_CARD_WICKEDNESS_TILE', 71);
define('ST_PLAYER_CHANGE_MIMICKED_CARD_WICKEDNESS_TILE', 72);

define('ST_MULTIPLAYER_ANSWER_QUESTION', 75);
define('ST_AFTER_ANSWER_QUESTION', 76);

define('ST_RESOLVE_END_TURN', 80);
define('ST_END_TURN', 81);
define('ST_NEXT_PLAYER', 90);

define('ST_END_GAME', 99);
define('END_SCORE', 100);

/*
 * Interventions
 */

define('CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION', 'PsychicProbeIntervention'); // /!\ on rename 
define('OPPORTUNIST_INTERVENTION', 'OpportunistIntervention');
define('CANCEL_DAMAGE_INTERVENTION', 'CancelDamageIntervention');
define('SMASHED_PLAYERS_IN_TOKYO', 'SmashedPlayersInTokyo');

/*
 * Options
 */

define('PICK_MONSTER_OPTION', 'PickMonsterOption'); // 100
define('AUTO_SKIP_OPTION', 'AutoSkipOption'); // 110
define('TWO_PLAYERS_VARIANT_OPTION', 'TwoPlayersVariantOption'); // 120
define('BONUS_MONSTERS_OPTION', 102);
define('HALLOWEEN_EXPANSION_OPTION', 103);
define('KINGKONG_EXPANSION_OPTION', 104);
define('CYBERTOOTH_EXPANSION_OPTION', 105);
define('MUTANT_EVOLUTION_VARIANT_OPTION', 106);
define('CTHULHU_EXPANSION_OPTION', 107);
define('ANUBIS_EXPANSION_OPTION', 108);
define('WICKEDNESS_EXPANSION_OPTION', 109);
define('POWERUP_EXPANSION_OPTION', 111);
define('DARK_EDITION_OPTION', 112);

/*
 * Variables
 */

define('EXTRA_ROLLS', 'ExtraRolls');
define('FREEZE_TIME_MAX_TURNS', 'FreezeTimeMaxTurns');
define('FREEZE_TIME_CURRENT_TURN', 'FreezeTimeCurrentTurn');
define('FRENZY_EXTRA_TURN', 'FrenzyExtraTurn');
define('FINAL_PUSH_EXTRA_TURN', 'FinalPushExtraTurn');
define('BUILDERS_UPRISING_EXTRA_TURN', 'BuildersUprisingExtraTurn');
define('PANDA_EXPRESS_EXTRA_TURN', 'PandaExpressExtraTurn');
define('JUNGLE_FRENZY_EXTRA_TURN', 'JungleFrenzyExtraTurn');
define('PSYCHIC_PROBE_ROLLED_A_3', 'PsychicProbeRolledA3');
define('KILL_PLAYERS_SCORE_AUX', 'KillPlayersScoreAux');
define('FRENZY_EXTRA_TURN_FOR_OPPORTUNIST', 'FrenzyExtraTurnForOpportunist');
define('PLAYER_BEFORE_FRENZY_EXTRA_TURN_FOR_OPPORTUNIST', 'PlayerBeforeFrenzyExtraTurnForOpportunist');
define('SKIP_BUY_PHASE', 'SkipBuyPhase');
define('CLOWN_ACTIVATED', 'ClownActivated');
define('CHEERLEADER_SUPPORT', 'CheerleaderSupport');
define('STATE_AFTER_RESOLVE', 'stateAfterResolve');
define('PLAYER_WITH_GOLDEN_SCARAB', 'PlayerWithGoldenScarab');
define('STATE_AFTER_MIMIC_CHOOSE', 'StateAfterMimicChoose');
define('RAGING_FLOOD_EXTRA_DIE', 'RagingFloodExtraDie');
define('FALSE_BLESSING_USED_DIE', 'FalseBlessingUsedDie');
define('DICE_NUMBER', 'DiceNumber');
define('RAGING_FLOOD_EXTRA_DIE_SELECTED', 'RagingFloodExtraDieSelected');
define('MUTANT_EVOLUTION_TURN', 'MutantEvolutionTurn');
define('PREVENT_ENTER_TOKYO', 'PreventEnterTokyo');

/*
 * Global variables
 */

define('FIRE_BREATHING_DAMAGES', 'FireBreathingDamages');
define('DICE_COUNTS', 'DiceCounts');
define('USED_CARDS', 'UsedCards');
define('JETS_DAMAGES', 'JetsDamages'); // also contains Simian Scamper damages
define('MIMICKED_CARD', 'MimickedCard');
define('USED_WINGS', 'UsedWings');
define('MADE_IN_A_LAB', 'MadeInALab');
define('BURROWING_PLAYERS', 'BurrowingPlayers');
define('UNSTABLE_DNA_PLAYERS', 'UnstableDNAPlayers');
define('QUESTION', 'Question');
define('CARD_BEING_BOUGHT', 'CardBeingBought');
define('STACKED_STATES', 'StackedStates');
define('STARTED_TURN_IN_TOKYO', 'StartedTurnInTokyo');

/*
 * Cards
 */
// keep
define('ACID_ATTACK_CARD', 1);
define('ALIEN_ORIGIN_CARD', 2);
define('ALPHA_MONSTER_CARD', 3);
define('ARMOR_PLATING_CARD', 4);
define('BACKGROUND_DWELLER_CARD', 5);
define('BURROWING_CARD', 6);
define('CAMOUFLAGE_CARD', 7);
define('COMPLETE_DESTRUCTION_CARD', 8);
define('MEDIA_FRIENDLY_CARD', 9);
define('EATER_OF_THE_DEAD_CARD', 10);
define('ENERGY_HOARDER_CARD', 11);
define('EVEN_BIGGER_CARD', 12);
define('EXTRA_HEAD_1_CARD', 13);
define('EXTRA_HEAD_2_CARD', 14);
define('FIRE_BREATHING_CARD', 15);
define('FREEZE_TIME_CARD', 16);
define('FRIEND_OF_CHILDREN_CARD', 17);
define('GIANT_BRAIN_CARD', 18);
define('GOURMET_CARD', 19);
define('HEALING_RAY_CARD', 20);
define('HERBIVORE_CARD', 21);
define('HERD_CULLER_CARD', 22);
define('IT_HAS_A_CHILD_CARD', 23);
define('JETS_CARD', 24);
define('MADE_IN_A_LAB_CARD', 25);
define('METAMORPH_CARD', 26);
define('MIMIC_CARD', 27);
define('BATTERY_MONSTER_CARD', 28);
define('NOVA_BREATH_CARD', 29);
define('DETRITIVORE_CARD', 30);
define('OPPORTUNIST_CARD', 31);
define('PARASITIC_TENTACLES_CARD', 32);
define('PLOT_TWIST_CARD', 33);
define('POISON_QUILLS_CARD', 34);
define('POISON_SPIT_CARD', 35);
define('PSYCHIC_PROBE_CARD', 36);
define('RAPID_HEALING_CARD', 37);
define('REGENERATION_CARD', 38);
define('ROOTING_FOR_THE_UNDERDOG_CARD', 39);
define('SHRINK_RAY_CARD', 40);
define('SMOKE_CLOUD_CARD', 41);
define('SOLAR_POWERED_CARD', 42);
define('SPIKED_TAIL_CARD', 43);
define('STRETCHY_CARD', 44);
define('ENERGY_DRINK_CARD', 45);
define('URBAVORE_CARD', 46);
define('WE_RE_ONLY_MAKING_IT_STRONGER_CARD', 47);
define('WINGS_CARD', 48);
//Dark edition :
define('HIBERNATION_CARD', 49);
define('NANOBOTS_CARD', 50);
define('NATURAL_SELECTION_CARD', 51);
define('REFLECTIVE_HIDE_CARD', 52);
define('SUPER_JUMP_CARD', 53);
define('UNSTABLE_DNA_CARD', 54);
define('ZOMBIFY_CARD', 55);

// discard
define('APPARTMENT_BUILDING_CARD', 101);
define('COMMUTER_TRAIN_CARD', 102);
define('CORNER_STORE_CARD', 103);
define('DEATH_FROM_ABOVE_CARD', 104);
define('ENERGIZE_CARD', 105);
define('EVACUATION_ORDER_1_CARD', 106);
define('EVACUATION_ORDER_2_CARD', 107);
define('FLAME_THROWER_CARD', 108);
define('FRENZY_CARD', 109);
define('GAS_REFINERY_CARD', 110);
define('HEAL_CARD', 111);
define('HIGH_ALTITUDE_BOMBING_CARD', 112);
define('JET_FIGHTERS_CARD', 113);
define('NATIONAL_GUARD_CARD', 114);
define('NUCLEAR_POWER_PLANT_CARD', 115);
define('SKYSCRAPER_CARD', 116);
define('TANK_CARD', 117);
define('VAST_STORM_CARD', 118);
//Dark edition :
define('MONSTER_PETS_CARD', 119);

// costume
define('ASTRONAUT_CARD', 201);
define('GHOST_CARD', 202);
define('VAMPIRE_CARD', 203);
define('WITCH_CARD', 204);
define('DEVIL_CARD', 205);
define('PIRATE_CARD', 206);
define('PRINCESS_CARD', 207);
define('ZOMBIE_CARD', 208);
define('CHEERLEADER_CARD', 209);
define('ROBOT_CARD', 210);
define('STATUE_OF_LIBERTY_CARD', 211);
define('CLOWN_CARD', 212);

// transformation
define('FORM_CARD', 301);

// curse cards
define('PHARAONIC_EGO_CURSE_CARD', 1);
define('ISIS_S_DISGRACE_CURSE_CARD', 2);
define('THOT_S_BLINDNESS_CURSE_CARD', 3);
define('TUTANKHAMUN_S_CURSE_CURSE_CARD', 4);
define('BURIED_IN_SAND_CURSE_CARD', 5);
define('RAGING_FLOOD_CURSE_CARD', 6);
define('HOTEP_S_PEACE_CURSE_CARD', 7);
define('SET_S_STORM_CURSE_CARD', 8);
define('BUILDERS_UPRISING_CURSE_CARD', 9);
define('INADEQUATE_OFFERING_CURSE_CARD', 10);
define('BOW_BEFORE_RA_CURSE_CARD', 11);
define('VENGEANCE_OF_HORUS_CURSE_CARD', 12);
define('ORDEAL_OF_THE_MIGHTY_CURSE_CARD', 13);
define('ORDEAL_OF_THE_WEALTHY_CURSE_CARD', 14);
define('ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD', 15);
define('RESURRECTION_OF_OSIRIS_CURSE_CARD', 16);
define('FORBIDDEN_LIBRARY_CURSE_CARD', 17);
define('CONFUSED_SENSES_CURSE_CARD', 18);
define('PHARAONIC_SKIN_CURSE_CARD', 19);
define('KHEPRI_S_REBELLION_CURSE_CARD', 20);
define('BODY_SPIRIT_AND_KA_CURSE_CARD', 21);
define('FALSE_BLESSING_CURSE_CARD', 22);
define('GAZE_OF_THE_SPHINX_CURSE_CARD', 23);
define('SCRIBE_S_PERSEVERANCE_CURSE_CARD', 24);

// wickedness tiles orange
define('DEVIOUS_WICKEDNESS_TILE', 1);
define('ETERNAL_WICKEDNESS_TILE', 2);
define('SKULKING_WICKEDNESS_TILE', 3);
define('TIRELESS_WICKEDNESS_TILE', 4);
define('CYBERBRAIN_WICKEDNESS_TILE', 5);
define('EVIL_LAIR_WICKEDNESS_TILE', 6);
define('FULL_REGENERATION_WICKEDNESS_TILE', 7);
define('WIDESPREAD_PANIC_WICKEDNESS_TILE', 8);
define('ANTIMATTER_BEAM_WICKEDNESS_TILE', 9);
define('SKYBEAM_WICKEDNESS_TILE', 10);
// wickedness tiles green 
define('BARBS_WICKEDNESS_TILE', 101);
define('FINAL_ROAR_WICKEDNESS_TILE', 102);
define('POISON_SPIT_WICKEDNESS_TILE', 103);
define('UNDERDOG_WICKEDNESS_TILE', 104);
define('DEFENDER_OF_TOKYO_WICKEDNESS_TILE', 105);
define('FLUXLING_WICKEDNESS_TILE', 106);
define('HAVE_IT_ALL_WICKEDNESS_TILE', 107);
define('SONIC_BOOMER_WICKEDNESS_TILE', 108);
define('FINAL_PUSH_WICKEDNESS_TILE', 109);
define('STARBURST_WICKEDNESS_TILE', 110);

// evolutions : Space Penguin
define('FREEZE_RAY_EVOLUTION', 11);
define('MIRACULOUS_CATCH_EVOLUTION', 12);
define('DEEP_DIVE_EVOLUTION', 13);
define('COLD_WAVE_EVOLUTION', 14);
define('ENCASED_IN_ICE_EVOLUTION', 15);
define('BLIZZARD_EVOLUTION', 16);
define('BLACK_DIAMOND_EVOLUTION', 17);
define('ICY_REFLECTION_EVOLUTION', 18);
// evolutions : Alienoid
define('ALIEN_SCOURGE_EVOLUTION', 21);
define('PRECISION_FIELD_SUPPORT_EVOLUTION', 22);
define('ANGER_BATTERIES_EVOLUTION', 23);
define('ADAPTING_TECHNOLOGY_EVOLUTION', 24);
define('FUNNY_LOOKING_BUT_DANGEROUS_EVOLUTION', 25);
define('EXOTIC_ARMS_EVOLUTION', 26);
define('MOTHERSHIP_SUPPORT_EVOLUTION', 27);
define('SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION', 28);
// evolutions : Cyber Kitty
define('NINE_LIVES_EVOLUTION', 31);
define('MEGA_PURR_EVOLUTION', 32);
define('ELECTRO_SCRATCH_EVOLUTION', 33);
define('CAT_NIP_EVOLUTION', 34);
define('PLAY_WITH_YOUR_FOOD_EVOLUTION', 35);
define('FELINE_MOTOR_EVOLUTION', 36);
define('MOUSE_HUNTER_EVOLUTION', 37);
define('MEOW_MISSLE_EVOLUTION', 38);
// evolutions : The King
define('MONKEY_RUSH_EVOLUTION', 41);
define('SIMIAN_SCAMPER_EVOLUTION', 42);
define('JUNGLE_FRENZY_EVOLUTION', 43);
define('GIANT_BANANA_EVOLUTION', 44);
define('CHEST_THUMPING_EVOLUTION', 45);
define('ALPHA_MALE_EVOLUTION', 46);
define('I_AM_THE_KING_EVOLUTION', 47);
define('TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION', 48);
// evolutions : Gigazaur
define('DETACHABLE_TAIL_EVOLUTION', 51);
define('RADIOACTIVE_WASTE_EVOLUTION', 52);
define('PRIMAL_BELLOW_EVOLUTION', 53);
define('SAURIAN_ADAPTABILITY_EVOLUTION', 54);
define('DEFENDER_OF_TOKYO_EVOLUTION', 55);
define('HEAT_VISION_EVOLUTION', 56);
define('GAMMA_BREATH_EVOLUTION', 57);
define('TAIL_SWEEP_EVOLUTION', 58);
// evolutions : Meka Dragon
define('MECHA_BLAST_EVOLUTION', 61);
define('DESTRUCTIVE_ANALYSIS_EVOLUTION', 62);
define('PROGRAMMED_TO_DESTROY_EVOLUTION', 63);
define('TUNE_UP_EVOLUTION', 64);
define('BREATH_OF_DOOM_EVOLUTION', 65);
define('LIGHTNING_ARMOR_EVOLUTION', 66);
define('CLAWS_OF_STEEL_EVOLUTION', 67);
define('TARGET_ACQUIRED_EVOLUTION', 68);
// evolutions : Pandakaï
define('PANDA_MONIUM_EVOLUTION', 131);
define('EATS_SHOOTS_AND_LEAVES_EVOLUTION', 132);
define('BAMBOOZLE_EVOLUTION', 133);
define('BEAR_NECESSITIES_EVOLUTION', 134);
define('PANDA_EXPRESS_EVOLUTION', 135);
define('BAMBOO_SUPPLY_EVOLUTION', 136);
define('PANDARWINISM_EVOLUTION', 137);
define('YIN_YANG_EVOLUTION', 138);
// evolutions : Cyber Bunny
/*
Electric Carrot	Permanent Evolution
Energy Sword (Bunny Power Blade)	Permanent Evolution
King of the Gizmo (He Who Dies With The Most Toys Wins!)	Permanent Evolution
Secret Laboratory (Rabbit Laboratory)	Permanent Evolution
Heart of the Rabbit (Prince With 1000 Enemies) 	Temporary Evolution
Rabbit's Foot (Decoy Bunny)	Temporary Evolution
Emergency Battery (Hare Style)	Temporary Evolution
Stroke of Genius (Fuzzy Logic)	Temporary Evolution
*/
// evolutions : Kraken
/*
Mandibles of Dread (Dread Maw)	Permanent Evolution
Sunken Temple	Permanent Evolution
Eater Of Souls	Permanent Evolution
Terror Of The Deep	Permanent Evolution
High Tide	Temporary Evolution
 Cult Worshippers (Cult Following)	Temporary Evolution
Destructive Wave (Wave Of Ruin)	Temporary Evolution
Healing Rain	Temporary Evolution
*/

}
?>
