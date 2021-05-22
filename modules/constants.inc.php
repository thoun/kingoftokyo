<?php
define('MAX_POINT', 20);
define('START_LIFE', 10);

/*
 * State constants
 */
define('ST_BGA_GAME_SETUP', 1);

define('ST_START', 20);
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
define('ST_LEAVE_TOKYO_APPLY_JETS', 40);
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
?>
