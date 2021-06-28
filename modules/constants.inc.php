<?php
define('MAX_POINT', 20);
define('START_LIFE', 10);

/*
 * State constants
 */
define('ST_BGA_GAME_SETUP', 1);

define('ST_START', 10);
define('ST_PLAYER_PICK_MONSTER', 11);
define('ST_PICK_MONSTER_NEXT_PLAYER', 12);

define('ST_START_TURN', 20);
define('ST_PLAYER_CHANGE_MIMICKED_CARD', 21);
define('ST_PLAYER_THROW_DICE', 22);
define('ST_PLAYER_CHANGE_DIE', 23);
define('ST_MULTIPLAYER_PSYCHIC_PROBE_ROLL_DIE', 24);

define('ST_RESOLVE_DICE', 30);
define('ST_RESOLVE_NUMBER_DICE', 31);
define('ST_RESOLVE_HEART_DICE', 32);
define('ST_RESOLVE_HEART_DICE_ACTION', 33);
define('ST_RESOLVE_ENERGY_DICE', 34);
define('ST_RESOLVE_SMASH_DICE', 35);

define('ST_MULTIPLAYER_CANCEL_DAMAGE', 38);

define('ST_MULTIPLAYER_LEAVE_TOKYO', 40);
define('ST_LEAVE_TOKYO_APPLY_JETS', 41);
define('ST_ENTER_TOKYO_APPLY_BURROWING', 45);
define('ST_ENTER_TOKYO', 46);

define('ST_PLAYER_BUY_CARD', 50);
define('ST_PLAYER_CHOOSE_MIMICKED_CARD', 51);
define('ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD', 52);
define('ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD', 53);

define('ST_PLAYER_SELL_CARD', 60);

define('ST_RESOLVE_END_TURN', 80);
define('ST_END_TURN', 81);
define('ST_NEXT_PLAYER', 90);

define('ST_END_GAME', 99);
define('END_SCORE', 100);

/*
 * Interventions
 */

define('PSYCHIC_PROBE_INTERVENTION', 'PsychicProbeIntervention');
define('OPPORTUNIST_INTERVENTION', 'OpportunistIntervention');
define('CANCEL_DAMAGE_INTERVENTION', 'CancelDamageIntervention');
define('SMASHED_PLAYERS_IN_TOKYO', 'SmashedPlayersInTokyo');

/*
 * Options
 */

define('PICK_MONSTER_OPTION', 'PickMonsterOption');
define('AUTO_SKIP_OPTION', 'AutoSkipOption');

/*
 * Variables
 */

define('EXTRA_ROLLS', 'ExtraRolls');
define('FREEZE_TIME_MAX_TURNS', 'FreezeTimeMaxTurns');
define('FREEZE_TIME_CURRENT_TURN', 'FreezeTimeCurrentTurn');
define('FRENZY_EXTRA_TURN', 'FrenzyExtraTurn');
define('KILL_ACTIVE_PLAYER', 'KillActivePlayer');
define('MULTIPLAYER_BEING_KILLED', 'MultiplayerBeingKilled');
define('PSYCHIC_PROBE_ROLLED_A_3', 'PsychicProbeRolledA3');
define('KILL_PLAYERS_SCORE_AUX', 'KillPlayersScoreAux');

/*
 * Global variables
 */

define('FIRE_BREATHING_DAMAGES', 'FireBreathingDamages');
define('DICE_COUNTS', 'DiceCounts');
define('USED_CARDS', 'UsedCards');
define('JETS_DAMAGES', 'JetsDamages');
define('MIMICKED_CARD', 'MimickedCard');
define('USED_WINGS', 'UsedWings');
define('MADE_IN_A_LAB', 'MadeInALab');

/*
 * Cards
 */

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
?>
