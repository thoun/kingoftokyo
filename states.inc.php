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
            "changeMimickedCard" => ST_PLAYER_CHANGE_MIMICKED_CARD,
            "throw" => ST_PLAYER_THROW_DICE,
        ],
    ],

    ST_PLAYER_CHANGE_MIMICKED_CARD => [
        "name" => "changeMimickedCard",
        "description" => clienttranslate('${actplayer} can change mimicked card for 1[Energy]'),
        "descriptionmyturn" => clienttranslate('${you} can change mimicked card for 1[Energy]'),
        "type" => "activeplayer",
        "args" => "argChooseMimickedCard",
        "possibleactions" => [ "changeMimickedCard", "skipChangeMimickedCard" ],
        "transitions" => [
            "next" => ST_PLAYER_THROW_DICE,
        ]
    ],

    ST_PLAYER_THROW_DICE => [
        "name" => "throwDice",
        "description" => clienttranslate('${actplayer} can rethrow dice or resolve dice'),
        "descriptionlast" => clienttranslate('${actplayer} must resolve dice'),
        "descriptionmyturn" => clienttranslate('${you} can rethrow dice or resolve dice'),
        "descriptionmyturnlast" => clienttranslate('${you} must resolve dice'),
        "type" => "activeplayer",
        "args" => "argThrowDice",
        "possibleactions" => [ "rethrow", "goToChangeDie", "buyEnergyDrink", "rethrow3", "useSmokeCloud", "resolve" ],
        "transitions" => [
            "rethrow" => ST_PLAYER_THROW_DICE,
            "goToChangeDie" => ST_PLAYER_CHANGE_DIE,
            "psychicProbe" => ST_MULTIPLAYER_PSYCHIC_PROBE_ROLL_DIE,
            //"zombiePass" => ST_NEXT_PLAYER,
        ],
    ],  

    ST_PLAYER_CHANGE_DIE => [
        "name" => "changeDie",
        "description" => clienttranslate('${actplayer} can change die result'),
        "descriptionmyturn" => clienttranslate('${you} can change die result'),
        "type" => "activeplayer",
        "action" => "stChangeDie",
        "args" => "argChangeDie",
        "possibleactions" => [ "changeDie", "resolve" ],
        "transitions" => [
            "changeDie" => ST_PLAYER_CHANGE_DIE,
            "changeDieWithPsychicProbe" => ST_MULTIPLAYER_PSYCHIC_PROBE_ROLL_DIE,
            "resolve" => ST_RESOLVE_DICE,
        ],

    ],

    ST_MULTIPLAYER_PSYCHIC_PROBE_ROLL_DIE => [
        "name" => "psychicProbeRollDie",
        "description" => clienttranslate('Player with Psychic Probe can reroll a die'),
        "descriptionmyturn" => clienttranslate('${you} can reroll a die'),
        "type" => "multipleactiveplayer",
        "action" => "stPsychicProbeRollDie",
        "args" => "argPsychicProbeRollDie",
        "possibleactions" => [ "psychicProbeRollDie", "psychicProbeSkip" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_PSYCHIC_PROBE_ROLL_DIE,
            "end" => ST_RESOLVE_DICE,
            "endAndChangeDieAgain" => ST_PLAYER_CHANGE_DIE,
            //"endGame" => ST_END_GAME,
            //"zombiePass" => ST_PLAYER_BUY_CARD,
        ],
    ],

    ST_RESOLVE_DICE => [
        "name" => "resolveDice",
        "description" => "",
        "type" => "game",
        "action" => "stResolveDice",
        "transitions" => [
            "next" => ST_RESOLVE_NUMBER_DICE,
        ],
    ],

    ST_RESOLVE_NUMBER_DICE => [
        "name" => "resolveNumberDice",
        "description" => "",
        "type" => "game",
        "action" => "stResolveNumberDice",
        "transitions" => [
            "next" => ST_RESOLVE_HEART_DICE,
            "nextAction" => ST_RESOLVE_HEART_DICE_ACTION,
        ],
    ],

    ST_RESOLVE_HEART_DICE => [
        "name" => "resolveHeartDice",
        "description" => "",
        "type" => "game",
        "action" => "stResolveHeartDice",
        "transitions" => [
            "next" => ST_RESOLVE_ENERGY_DICE,
        ],
    ],

    ST_RESOLVE_HEART_DICE_ACTION => [
        "name" => "resolveHeartDiceAction",
        "description" => clienttranslate('${actplayer} can select effect of [diceHeart] dice'),
        "descriptionmyturn" => clienttranslate('${you} can select effect of [diceHeart] dice'),
        "type" => "activeplayer",
        "args" => "argResolveHeartDiceAction",
        "possibleactions" => [ "applyHeartDieChoices" ],
        "transitions" => [
            "next" => ST_RESOLVE_ENERGY_DICE,
        ],
    ],

    ST_RESOLVE_ENERGY_DICE => [
        "name" => "resolveEnergyDice",
        "description" => "",
        "type" => "game",
        "action" => "stResolveEnergyDice",
        "transitions" => [
            "next" => ST_RESOLVE_SMASH_DICE,
        ],
    ],

    ST_RESOLVE_SMASH_DICE => [
        "name" => "resolveSmashDice",
        "description" => "",
        "type" => "game",
        "action" => "stResolveSmashDice",
        "transitions" => [
            "enterTokyo" => ST_ENTER_TOKYO_APPLY_BURROWING,
            "smashes" => ST_MULTIPLAYER_LEAVE_TOKYO,
            "cancelDamage" => ST_MULTIPLAYER_CANCEL_DAMAGE,
            //"endGame" => ST_END_GAME,
        ],
    ],

    ST_MULTIPLAYER_CANCEL_DAMAGE => [
        "name" => "cancelDamage",
        "description" => clienttranslate('A player can reduce damage (${damage}[Heart])'),
        "descriptionmyturn" => clienttranslate('${you} can reduce damage (${damage}[Heart])'),
        "type" => "multipleactiveplayer",
        "action" => "stCancelDamage",
        "args" => "argCancelDamage",
        "possibleactions" => [ "throwCamouflageDice", "useWings", "skipWings" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_CANCEL_DAMAGE,
            "enterTokyo" => ST_ENTER_TOKYO_APPLY_BURROWING,
            "enterTokyoAfterBurrowing" => ST_ENTER_TOKYO,
            "smashes" => ST_MULTIPLAYER_LEAVE_TOKYO,
            "endTurn" => ST_END_TURN,
            "zombiePass" => ST_END_TURN,
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
            "resume" => ST_LEAVE_TOKYO_APPLY_JETS,
            //"endGame" => ST_END_GAME,
            //"zombiePass" => ST_PLAYER_BUY_CARD,
        ],
    ],

    ST_LEAVE_TOKYO_APPLY_JETS => [
        "name" => "leaveTokyoApplyJets",
        "description" => "",
        "type" => "game",
        "action" => "stLeaveTokyoApplyJets",
        "transitions" => [
            "next" => ST_ENTER_TOKYO_APPLY_BURROWING,
        ],        
    ],

    ST_ENTER_TOKYO_APPLY_BURROWING => [
        "name" => "enterTokyoApplyBurrowing",
        "description" => "",
        "type" => "game",
        "action" => "stEnterTokyoApplyBurrowing",
        "transitions" => [
            "next" => ST_ENTER_TOKYO,
            //"endGame" => ST_END_GAME,
        ],
    ],

    ST_ENTER_TOKYO => [
        "name" => "enterTokyo",
        "description" => "",
        "type" => "game",
        "action" => "stEnterTokyo",
        "transitions" => [
            "next" => ST_PLAYER_BUY_CARD,
            //"endGame" => ST_END_GAME,
        ],
    ],

    ST_PLAYER_BUY_CARD => [
        "name" => "buyCard",
        "description" => clienttranslate('${actplayer} can buy a card'),
        "descriptionmyturn" => clienttranslate('${you} can buy a card'),
        "type" => "activeplayer",
        "args" => "argBuyCard",
        "action" => "stBuyCard",
        "possibleactions" => [ "buyCard", "goToSellCard", "renew" ],
        "transitions" => [
            "buyCard" => ST_PLAYER_BUY_CARD,
            "buyMimicCard" => ST_PLAYER_CHOOSE_MIMICKED_CARD,
            "opportunist" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            "goToSellCard" => ST_PLAYER_SELL_CARD,
            "renew" => ST_PLAYER_BUY_CARD,
            //"zombiePass" => ST_NEXT_PLAYER,
        ]
    ],

    ST_PLAYER_CHOOSE_MIMICKED_CARD => [
        "name" => "chooseMimickedCard",
        "description" => clienttranslate('${actplayer} must select a card to mimic'),
        "descriptionmyturn" => clienttranslate('${you} must select a card to mimic'),
        "type" => "activeplayer",
        "args" => "argChooseMimickedCard",
        "possibleactions" => [ "chooseMimickedCard" ],
        "transitions" => [
            "buyCard" => ST_PLAYER_BUY_CARD,
            "opportunist" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            "goToSellCard" => ST_PLAYER_SELL_CARD,
            "renew" => ST_PLAYER_BUY_CARD,
            //"zombiePass" => ST_NEXT_PLAYER,
        ]
    ],

    ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD => [
        "name" => "opportunistBuyCard",
        "description" => clienttranslate('Player with Opportunist can buy revealed card'),
        "descriptionmyturn" => clienttranslate('${you} can buy revealed card'),
        "type" => "multipleactiveplayer",
        "action" => "stOpportunistBuyCard",
        "args" => "argOpportunistBuyCard",
        "possibleactions" => [ "buyCard", "opportunistSkip" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            "stayMimicCard" => ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD,
            "end" => ST_PLAYER_BUY_CARD,
            //"endGame" => ST_END_GAME,
            //"zombiePass" => ST_PLAYER_BUY_CARD,
        ],
    ],

    ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD => [
        "name" => "opportunistChooseMimicCard",
        "description" => clienttranslate('Player with Opportunist must select a card to mimic'),
        "descriptionmyturn" => clienttranslate('${you} must select a card to mimic'),
        "type" => "multipleactiveplayer",
        "action" => "stOpportunistChooseMimicCard",
        "args" => "argChooseMimickedCard",
        "possibleactions" => [ "chooseMimickedCard" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            "end" => ST_PLAYER_BUY_CARD,
            //"endGame" => ST_END_GAME,
            //"zombiePass" => ST_PLAYER_BUY_CARD,
        ],
    ],

    ST_PLAYER_SELL_CARD => [
        "name" => "sellCard",
        "description" => clienttranslate('${actplayer} can sell a card'),
        "descriptionmyturn" => clienttranslate('${you} can sell a card'),
        "type" => "activeplayer",
        "action" => "stSellCard",
        "possibleactions" => [ "sellCard", "endTurn" ],
        "transitions" => [
            "sellCard" => ST_PLAYER_SELL_CARD,
            "endTurn" => ST_RESOLVE_END_TURN,
            //"zombiePass" => ST_NEXT_PLAYER,
        ]
    ],

    ST_RESOLVE_END_TURN => [
        "name" => "resolveEndTurn",
        "description" => "",
        "type" => "game",
        "action" => "stResolveEndTurn",
        "transitions" => [ 
            "cancelDamage" => ST_MULTIPLAYER_CANCEL_DAMAGE,
            "endTurn" => ST_END_TURN,
            //"zombiePass" => ST_NEXT_PLAYER,
        ],
    ],

    ST_END_TURN => [
        "name" => "endTurn",
        "description" => "",
        "type" => "game",
        "action" => "stEndTurn",
        "transitions" => [ 
            "nextPlayer" => ST_NEXT_PLAYER,
            //"endGame" => ST_END_GAME,
        ],
    ],
];
 
$machinestates = $basicGameStates + $playerActionsGameStates;
