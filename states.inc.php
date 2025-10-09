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

require_once("modules/php/constants.inc.php");

$machinestates = [
    ST_RESOLVE_HEART_DICE_ACTION => [
        "name" => "resolveHeartDiceAction",
        "description" => clienttranslate('${actplayer} can select effect of [diceHeart] dice'),
        "descriptionmyturn" => clienttranslate('${you} can select effect of [diceHeart] dice'),
        "type" => "activeplayer",
        "args" => "argResolveHeartDiceAction",
        "action" => "stResolveHeartDiceAction",
        "possibleactions" => [ "actApplyHeartDieChoices" ],
        "transitions" => [
            "next" => ST_RESOLVE_ENERGY_DICE,
        ],
    ],
    ST_RESOLVE_SMASH_DICE_ACTION => [
        "name" => "resolveSmashDiceAction",
        "description" => clienttranslate('${actplayer} can select effect of [diceSmash] dice'),
        "descriptionmyturn" => clienttranslate('${you} can select effect of [diceSmash] dice'),
        "type" => "activeplayer",
        "args" => "argResolveSmashDiceAction",
        "possibleactions" => [ "actApplySmashDieChoices" ],
        "transitions" => [],
    ],
    ST_CHOOSE_EVOLUTION_CARD => [
        "name" => "chooseEvolutionCard",
        "description" => clienttranslate('${actplayer} must choose an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} must choose an Evolution card'),
        "type" => "activeplayer",
        "args" => "argChooseEvolutionCard",
        "possibleactions" => [ "actChooseEvolutionCard" ],
        "transitions" => [],
    ],

    ST_MULTIPLAYER_CANCEL_DAMAGE => [
        "name" => "cancelDamage",
        "description" => '',
        "descriptionmyturn" => '',
        "descriptionReduce" => clienttranslate('${actplayer} can reduce damage (${damage}[Heart])'),
        "descriptionmyturnReduce" => clienttranslate('${you} can reduce damage (${damage}[Heart])'),
        "descriptionHealBeforeDamage" => clienttranslate('${actplayer} can heal before taking damage (${damage}[Heart])'),
        "descriptionmyturnHealBeforeDamage" => clienttranslate('${you} can heal before taking damage (${damage}[Heart])'),
        "type" => "multipleactiveplayer",
        "action" => "stCancelDamage",
        "args" => "argCancelDamage",
        "possibleactions" => [ "actThrowCamouflageDice", "actUseWings", "actSkipWings", "actUseRobot", "actUseElectricArmor", "actUseSuperJump", "actUseRapidHealingSync", "actRethrow3Camouflage", "actUseInvincibleEvolution", "actUseCandyEvolution", "actUseRapidHealing", "actUseMothershipSupport" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_CANCEL_DAMAGE,
            "enterTokyo" => ST_ENTER_TOKYO_APPLY_BURROWING,
            "enterTokyoAfterBurrowing" => ST_ENTER_TOKYO,
            "smashes" => ST_MULTIPLAYER_LEAVE_TOKYO,
            "endTurn" => ST_END_TURN,
            "zombiePass" => ST_END_TURN,
        ],
    ],

    ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE => [
        "name" => "afterResolveDamage",
        "description" => '',
        "descriptionmyturn" => '',
        "type" => "multipleactiveplayer",
        "action" => "stAfterResolveDamage",
        "possibleactions" => [],
        "transitions" => [],
    ],

    ST_MULTIPLAYER_LEAVE_TOKYO => [
        "name" => "leaveTokyo",
        "description" => clienttranslate('Players in Tokyo must choose to stay or leave Tokyo'),
        "descriptionmyturn" => clienttranslate('${you} must choose to stay or leave Tokyo'),
        "type" => "multipleactiveplayer",
        "action" => "stLeaveTokyo",
        "args" => "argLeaveTokyo",
        "possibleactions" => [ "actStay", "actLeave", "actUseChestThumping", "actSkipChestThumping" ],
        "transitions" => [
            "resume" => ST_LEAVE_TOKYO_APPLY_JETS,
            "end" => ST_LEAVE_TOKYO_APPLY_JETS, // for zombie
        ],
    ],
    ST_MULTIPLAYER_LEAVE_TOKYO_EXCHANGE_CARD => [
        "name" => "leaveTokyoExchangeCard",
        "description" => clienttranslate('Players with Unstable DNA can exchange this card'),
        "descriptionmyturn" => clienttranslate('${you} can exchange Unstable DNA'),
        "type" => "multipleactiveplayer",
        "action" => "stLeaveTokyoExchangeCard",
        "args" => "argLeaveTokyoExchangeCard",
        "possibleactions" => [ "actExchangeCard", "actSkipExchangeCard" ],
        "transitions" => [
            "next" => ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO,
            "end" => ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO, // for zombie
        ],
    ],

    ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO => [
        "name" => "beforeEnteringTokyo",
        "description" => clienttranslate('Some players may activate an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} may activate an Evolution card'),
        "type" => "multipleactiveplayer",
        "action" => "stBeforeEnteringTokyo",
        "args" => "argBeforeEnteringTokyo",
        "possibleactions" => [
            "actSkipBeforeEnteringTokyo",
            "actUseFelineMotor", 
        ],
        "transitions" => [
            'next' => ST_ENTER_TOKYO,
            "end" => ST_ENTER_TOKYO, // for zombie
        ],        
    ],
    ST_PLAYER_AFTER_ENTERING_TOKYO => [
        "name" => "afterEnteringTokyo",
        "description" => clienttranslate('Some players may activate an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} may activate an Evolution card'),
        "type" => "activeplayer",
        "action" => "stAfterEnteringTokyo",
        "args" => "argAfterEnteringTokyo",
        "possibleactions" => [ "actSkipAfterEnteringTokyo" ],
        "transitions" => [],        
    ],

    ST_PLAYER_STEAL_COSTUME_CARD_OR_GIVE_GIFT_EVOLUTION => [
        "name" => "stealCostumeCard",
        "description" => clienttranslate('${actplayer} can steal a Costume card'),
        "descriptionmyturn" => clienttranslate('${you} can steal a Costume card'),
        "descriptionStealAndGive" => /*client TODOPUHA translate*/('${actplayer} can steal a Costume card and give a Gift Evolution'),
        "descriptionmyturnStealAndGive" => /*client TODOPUHA translate*/('${you} can steal a Costume card and give a Gift Evolution'),
        "descriptionGive" => /*client TODOPUHA translate*/('${actplayer} can give a Gift Evolution'),
        "descriptionmyturnGive" => /*client TODOPUHA translate*/('${you} can give a Gift Evolution'),
        "type" => "activeplayer",
        "args" => "argStealCostumeCard",
        "action" => "stStealCostumeCard",
        "possibleactions" => [ "actStealCostumeCard", 'actGiveGiftEvolution', "actEndStealCostume" ],
        "transitions" => [],
    ],

    ST_PLAYER_CHANGE_FORM => [
        "name" => "changeForm",
        "description" => clienttranslate('${actplayer} can change form'),
        "descriptionmyturn" => clienttranslate('${you} can change form'),
        "type" => "activeplayer",
        "args" => "argChangeForm",
        "action" => "stChangeForm",
        "possibleactions" => [ "actChangeForm", "actSkipChangeForm" ],
        "transitions" => [
            "buyCard" => ST_PLAYER_BUY_CARD,
        ]
    ],

    ST_PLAYER_BUY_CARD => [
        "name" => "buyCard",
        "description" => clienttranslate('${actplayer} can buy a card'),
        "descriptionmyturn" => clienttranslate('${you} can buy a card'),
        "type" => "activeplayer",
        "args" => "argBuyCard",
        "action" => "stBuyCard",
        "possibleactions" => [ "actBuyCard", "actGoToSellCard", "actEndTurn", "actRenewPowerCards", "actUseMiraculousCatch" ],
        "transitions" => [
            "buyCard" => ST_PLAYER_BUY_CARD,
            "opportunist" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            "goToSellCard" => ST_PLAYER_SELL_CARD,
            "renew" => ST_PLAYER_BUY_CARD,
        ]
    ],

    ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD => [
        "name" => "opportunistBuyCard",
        "description" => clienttranslate('Player with Opportunist can buy revealed card'),
        "descriptionmyturn" => clienttranslate('${you} can buy revealed card'),
        "type" => "multipleactiveplayer",
        "action" => "stOpportunistBuyCard",
        "args" => "argOpportunistBuyCard",
        "possibleactions" => [ "actBuyCard", "actOpportunistSkip" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            "end" => ST_PLAYER_BUY_CARD,
        ],
    ],

];
