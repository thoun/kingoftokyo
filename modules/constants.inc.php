<?php
define('MAX_POINT', 20);
define('START_LIFE', 10);

/*
 * State constants
 */
define('ST_BGA_GAME_SETUP', 1);

define('ST_START', 20);
define('ST_PLAYER_THROW_DICES', 21);
define('ST_PLAYER_CHANGE_DIE', 23);
define('ST_RESOLVE_DICES', 25);

define('ST_MULTIPLAYER_LEAVE_TOKYO', 30);
define('ST_ENTER_TOKYO', 31);

define('ST_PLAYER_BUY_CARD', 40);

define('ST_PLAYER_SELL_CARD', 50);

define('ST_END', 90);
define('ST_NEXT_PLAYER', 91);

define('ST_END_GAME', 99);
define('END_SCORE', 100);
?>
