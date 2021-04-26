<?php

$gameinfos = [ 

    // Name of the game in English (will serve as the basis for translation) 
    'game_name' => "King of Tokyo",

    // Game designer (or game designers, separated by commas)
    'designer' => 'Richard Garfield',

    // Game artist (or game artists, separated by commas)
    'artist' => 'Régis Torres',

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

    // If in the game, all losers are equal (no score to rank them or explicit in the rules that losers are not ranked between them), set this to true 
    // The game end result will display "Winner" for the 1st player and "Loser" for all other players
    'losers_not_ranked' => false,
    
    // Allow to rank solo games for games where it's the only available mode (ex: Thermopyles). Should be left to false for games where solo mode exists in addition to multiple players mode.
    'solo_mode_ranked' => false,

    // Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
    'is_beta' => 1,

    // Is this game cooperative (all players wins together or loose together)
    'is_coop' => 0,

    // Language dependency. If false or not set, there is no language dependency. If true, all players at the table must speak the same language.
    // If an array of shortcode languages such as array( 1 => 'en', 2 => 'fr', 3 => 'it' ) then all players at the table must speak the same language, and this language must be one of the listed languages.
    // NB: the default will be the first language in this list spoken by the player, so you should list them by popularity/preference.
    'language_dependency' => false,

    // Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
    'complexity' => 2,

    // Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
    'luck' => 3,

    // Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
    'strategy' => 2,

    // Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
    'diplomacy' => 0,

    // Colors attributed to players
    'player_colors' => array( "ff0000", "008000", "0000ff", "ffa500", "773300" ),
    
    // Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
    // NB: this parameter is used only to flag games supporting this feature; you must use (or not use) reattributeColorsBasedOnPreferences PHP method to actually enable or disable the feature.
    'favorite_colors_support' => true,
    
    // When doing a rematch, the player order is swapped using a "rotation" so the starting player is not the same
    // If you want to disable this, set this to true
    'disable_player_order_swap_on_rematch' => false,
    
    // Game interface width range (pixels)
    // Note: game interface = space on the left side, without the column on the right
    'game_interface_width' => array(
    
        // Minimum width
        //  default: 740
        //  maximum possible value: 740 (ie: your game interface should fit with a 740px width (correspond to a 1024px screen)
        //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
        'min' => 400,
    
        // Maximum width
        //  default: null (ie: no limit, the game interface is as big as the player's screen allows it).
        //  maximum possible value: unlimited
        //  minimum possible value: 740
        'max' => null
    ),
    
    // Game presentation
    // Short game presentation text that will appear on the game description page, structured as an array of paragraphs.
    // Each paragraph must be wrapped with totranslate() for translation and should not contain html (plain text without formatting).
    // A good length for this text is between 100 and 150 words (about 6 to 9 lines on a standard display)
    
    // this presentation has been taken from website : https://studiobombyx.com/en/jeu/conspiracy-abyss-universe/
    'presentation' => [
        totranslate("Plots and corruption abound in the perpetual darkness of the Abyss kingdom’s deep waters. At the Oceanic Senate Assembly, you are at the very heart of this permanent battle for power. Your opponents conspire tirelessly to gain influence. In order to organize your Senatorial Chamber, you must rally the most powerful Lords before your opponents turn them away."),
        totranslate("Can you elude the plots of your rivals to establish your empire and reign supreme over the Oceanic Senate Assembly?"),
    ],


    // Games categories
    //  You can attribute any number of "tags" to your game.
    //  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
    'tags' => array( 2, 11, 20, 101, 201 ),


    //////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)
    
    // simple : A plays, B plays, C plays, A plays, B plays, ...
    // circuit : A plays and choose the next player C, C plays and choose the next player D, ...
    // complex : A+B+C plays and says that the next player is A+B
    'is_sandbox' => false,
    'turnControl' => 'simple',
    
    ////////
];
