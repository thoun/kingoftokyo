
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- KingOfTokyo implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- dice_value : 1, 2, 3, 4 : health, 5: energy, 6: smash
CREATE TABLE IF NOT EXISTS `dice` (
  `dice_id` TINYINT unsigned NOT NULL AUTO_INCREMENT,
  `dice_value` TINYINT unsigned NOT NULL DEFAULT 0,
  `extra` TINYINT unsigned NOT NULL DEFAULT false,
  `locked` TINYINT unsigned NOT NULL DEFAULT false,
  `rolled` TINYINT unsigned NOT NULL DEFAULT true,
  PRIMARY KEY (`dice_id`)
) ENGINE=InnoDB;

-- card_type : 0..100 for keep power, 100..200 for discard power
-- card_type_arg : tokens
-- card_location : deck / player / discard
-- card_location_arg : player id
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` smallint unsigned NOT NULL,
  `card_type_arg` tinyint unsigned NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` INT(10) unsigned NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB;

-- player_location : 0 : outside tokyo, 1 : tokyo city, 2: tokyo bay
ALTER TABLE `player` ADD `player_location` tinyint UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `player_health` tinyint UNSIGNED NOT NULL DEFAULT 10;
ALTER TABLE `player` ADD `player_energy` tinyint UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `player_monster` tinyint unsigned NOT NULL;
ALTER TABLE `player` ADD `player_poison_tokens` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `player` ADD `player_shrink_ray_tokens` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `player` ADD `leave_tokyo_under` tinyint unsigned;

CREATE TABLE IF NOT EXISTS `global_variables` (
  `name` varchar(50) NOT NULL,
  `value` json,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
