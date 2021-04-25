<?php

$gameinfos = array(


// Game designer (or game designers, separated by commas)
'designer' => 'Richard Garfield',

// Game artist (or game artists, separated by commas)
'artist' => 'Gabriel Butik, Romain Gaschet, Jonathan Silvestre, Igor Polouchine, Benjamin Raynal, Jean-Baptiste Reynaud, Régis Torres, Anthony Wolff',

// Year of FIRST publication of this game. Can be negative.
'year' => 2011,

// Game publisher
'publisher' => 'IELLO',

// Url of game publisher website
'publisher_website' => 'https://www.iello.fr/co.uk',

// Board Game Geek ID of the publisher
'publisher_bgg_id' => 8923,

// Board game geek if of the game
'bgg_id' => 70323,


// Players configuration that can be played (ex: 2 to 4 players)
'players' => array( 2,3,4,5,6 ),

// Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
'suggest_player_number' => null,

// Discourage players to play with these numbers of players. Must be null if there is no such advice.
'not_recommend_player_number' => array( 2 ),
// 'not_recommend_player_number' => array( 2, 3 ),      // <= example: this is not recommended to play this game with 2 or 3 players


// Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
'estimated_duration' => 30,

// Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
'fast_additional_time' => 30,

// Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
'medium_additional_time' => 40,

// Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
'slow_additional_time' => 50,

// If you are using a tie breaker in your game (using "player_score_aux"), you must describe here
// the formula used to compute "player_score_aux". This description will be used as a tooltip to explain
// the tie breaker to the players.
// Note: if you are NOT using any tie breaker, leave the empty string.
//
// Example: 'tie_breaker_description' => totranslate( "Number of remaining cards in hand" ),
'tie_breaker_description' => "",

// Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
'is_beta' => 1,

// Is this game cooperative (all players wins together or loose together)
'is_coop' => 0,


// Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
'complexity' => 2,

// Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
'luck' => 3,

// Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
'strategy' => 2,

// Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
'diplomacy' => 0,


// Games categories
//  You can attribute any number of "tags" to your game.
//  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
'tags' => array( 2, 11, 101, 201 )
);
