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
 * states.inc.php
 *
 * KingOfTokyo game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

require_once("modules/constants.inc.php");

$basicGameStates = [

    // The initial state. Please do not modify.
    ST_BGA_GAME_SETUP => [
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => [ "" => ST_START ]
    ],

    ST_NEXT_PLAYER => [
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => [
            "nextPlayer" => ST_START, 
            "endGame" => ST_END_GAME,
        ],
    ],
   
    // Final state.
    // Please do not modify.
    ST_END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd",
    ],
];

$playerActionsGameStates = [

    ST_START => [
        "name" => "startTurn",
        "description" => "",
        "type" => "game",
        "action" => "stStartTurn",
        "transitions" => [ 
            "throw" => ST_PLAYER_THROW_DICES,
        ],
    ],

    ST_PLAYER_THROW_DICES => [
        "name" => "throwDices",
        "description" => clienttranslate('${actplayer} can rethrow dices or resolve dices'),
        "descriptionlast" => clienttranslate('${actplayer} must resolve dices'),
        "descriptionmyturn" => clienttranslate('${you} can rethrow dices or resolve dices'),
        "descriptionmyturnlast" => clienttranslate('${you} must resolve dices'),
        "type" => "activeplayer",
        "args" => "argThrowDices",
        "possibleactions" => [ "rethrow", "resolve" ],
        "transitions" => [
            "rethrow" => ST_PLAYER_THROW_DICES,
            "resolve" => ST_RESOLVE_DICES,
            "zombiePass" => ST_NEXT_PLAYER,
        ],
    ],  

    ST_RESOLVE_DICES => [
        "name" => "resolveDices",
        "description" => "",
        "type" => "game",
        "action" => "stResolveDices",
        "transitions" => [ 
            "pickCard" => ST_PLAYER_PICK_CARD,
            "smashes" => ST_MULTIPLAYER_LEAVE_TOKYO,
            "endGame" => ST_END_GAME,
            // TODO leave tokyo
        ],
    ],

    ST_MULTIPLAYER_LEAVE_TOKYO => [
        "name" => "leaveTokyo",
        "description" => clienttranslate('Players in Tokyo must choose to stay or leave Tokyo'),
        "descriptionmyturn" => clienttranslate('${you} must choose to stay or leave Tokyo'),
        "type" => "multipleactiveplayer",
        "action" => "stLeaveTokyo",
        "possibleactions" => [ "stay", "leave" ],
        "transitions" => [
            "resume" => ST_ENTER_TOKYO,
            "zombiePass" => ST_PLAYER_PICK_CARD,
        ],
    ],

    ST_ENTER_TOKYO => array(
        "name" => "enterTokyo",
        "description" => "",
        "type" => "game",
        "action" => "stEnterTokyo",
        "transitions" => array( 
            "next" => ST_PLAYER_PICK_CARD,
            "endGame" => ST_END_GAME,
        )
    ),

    ST_PLAYER_PICK_CARD => [
        "name" => "pickCard",
        "description" => clienttranslate('${actplayer} can pick a card'),
        "descriptionmyturn" => clienttranslate('${you} can pick a card'),
        "type" => "activeplayer",
        "action" => "stPickCard",
        "args" => "argPickCard",
        "possibleactions" => [ "pick", "endTurn", "renew" ],
        "transitions" => [
            "pick" => ST_PLAYER_PICK_CARD,
            "endTurn" => ST_END,
            "renew" => ST_PLAYER_PICK_CARD,
            "zombiePass" => ST_NEXT_PLAYER,
        ]
    ],

    ST_END => [
        "name" => "endTurn",
        "description" => "",
        "type" => "game",
        "action" => "stEndTurn",
        "transitions" => [ 
            "nextPlayer" => ST_NEXT_PLAYER,
        ],
    ],
];

$cardsGameStates = [
    // TODO
];
 
$machinestates = $basicGameStates + $playerActionsGameStates + $cardsGameStates;
