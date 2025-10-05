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

$playerActionsGameStates = [

    ST_PLAYER_PICK_MONSTER => [
        "name" => "pickMonster",
        "description" => clienttranslate('${actplayer} must pick a monster'),
        "descriptionmyturn" => clienttranslate('${you} must pick a monster'),
        "type" => "activeplayer",
        "action" => "stPickMonster",
        "args" => "argPickMonster",
        "possibleactions" => [
            "pickMonster",

            "actPickMonster",
        ],
        "transitions" => [
            "next" => ST_PICK_MONSTER_NEXT_PLAYER,
            "start" => ST_START_GAME,
        ],

    ],

    ST_MULTIPLAYER_PICK_EVOLUTION_DECK => [
        "name" => "pickEvolutionForDeck",
        "description" => clienttranslate('Players must pick an Evolution for their deck'),
        "descriptionmyturn" => clienttranslate('${you} must pick an Evolution for your deck'),
        "type" => "multipleactiveplayer",
        "args" => "argPickEvolutionForDeck",
        //"action" => "stCheerleaderSupport",
        "possibleactions" => [ "pickEvolutionForDeck", "actPickEvolutionForDeck" ],
        "transitions" => [
            "next" => ST_NEXT_PICK_EVOLUTION_DECK,
            "end" => ST_NEXT_PICK_EVOLUTION_DECK, // for zombie
        ],
    ],


    ST_PLAYER_CHOOSE_INITIAL_CARD => [
        "name" => "chooseInitialCard",
        "description" => clienttranslate('${actplayer} must choose a Costume card'),
        "descriptionevo" => clienttranslate('${actplayer} must choose an Evolution card'),
        "descriptionevocostume" => clienttranslate('${actplayer} must choose a Costume and an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} must choose a Costume card'),
        "descriptionmyturnevo" => clienttranslate('${you} must choose an Evolution card'),
        "descriptionmyturnevocostume" => clienttranslate('${you} must choose a Costume and an Evolution card'),
        "type" => "activeplayer",
        "action" => "stChooseInitialCard",
        "args" => "argChooseInitialCard",
        "possibleactions" => [ "chooseInitialCard", "actChooseInitialCard" ],
        "transitions" => [
            "next" => ST_CHOOSE_INITIAL_CARD_NEXT_PLAYER,
            "start" => ST_START_GAME,
        ],

    ],

    ST_PLAYER_BEFORE_START_TURN => [
        "name" => "beforeStartTurn",
        "description" => clienttranslate('${actplayer} may activate an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} may activate an Evolution card'),
        "type" => "activeplayer",
        "action" => "stBeforeStartTurn",
        "args" => "argBeforeStartTurn",
        "possibleactions" => [ "skipBeforeStartTurn", "actSkipBeforeStartTurn" ],
        "transitions" => [],
    ],

    ST_QUESTIONS_BEFORE_START_TURN => [
        "name" => "questionsBeforeStartTurn",
        "description" => "",
        "type" => "game",
        "action" => "stQuestionsBeforeStartTurn",
        "transitions" => [
        ],
    ],

    ST_START_TURN => [
        "name" => "startTurn",
        "description" => "",
        "type" => "game",
        "action" => "stStartTurn",
        "transitions" => [ 
            "changeMimickedCard" => ST_PLAYER_CHANGE_MIMICKED_CARD,
            "throw" => ST_INITIAL_DICE_ROLL,
        ],
    ],

    ST_PLAYER_CHANGE_MIMICKED_CARD_WICKEDNESS_TILE => [
        "name" => "changeMimickedCardWickednessTile",
        "description" => clienttranslate('${actplayer} can change mimicked card'),
        "descriptionmyturn" => clienttranslate('${you} can change mimicked card'),
        "type" => "activeplayer",
        "action" => "stChooseMimickedCard",
        "args" => "argChangeMimickedCardWickednessTile",
        "possibleactions" => [ "changeMimickedCardWickednessTile", "actChangeMimickedCardWickednessTile", "skipChangeMimickedCardWickednessTile", "actSkipChangeMimickedCardWickednessTile" ],
        "transitions" => [
            "next" => ST_INITIAL_DICE_ROLL,
            "changeMimickedCard" => ST_PLAYER_CHANGE_MIMICKED_CARD,
        ]
    ],

    ST_PLAYER_CHANGE_MIMICKED_CARD => [
        "name" => "changeMimickedCard",
        "description" => clienttranslate('${actplayer} can change mimicked card for 1[Energy]'),
        "descriptionmyturn" => clienttranslate('${you} can change mimicked card for 1[Energy]'),
        "type" => "activeplayer",
        "action" => "stChooseMimickedCard",
        "args" => "argChangeMimickedCard",
        "possibleactions" => [ "changeMimickedCard", "actChangeMimickedCard", "skipChangeMimickedCard", "actSkipChangeMimickedCard" ],
        "transitions" => [
            "next" => ST_INITIAL_DICE_ROLL,
        ]
    ],

    ST_MULTIPLAYER_GIVE_SYMBOL_TO_ACTIVE_PLAYER => [ // TODOPU move to answerQuestion ?
        "name" => "giveSymbolToActivePlayer",
        "description" => clienttranslate('Player with Golden Scarab must give 1[Heart]/[Energy]/[Star]'),
        "descriptionmyturn" => clienttranslate('${you} must give 1[Heart]/[Energy]/[Star]'),
        "type" => "multipleactiveplayer",
        "args" => "argGiveSymbolToActivePlayer",
        "action" => "stGiveSymbolToActivePlayer",
        "possibleactions" => [ "giveSymbolToActivePlayer", "actGiveSymbolToActivePlayer" ],
        "transitions" => [
            "stay" => ST_INITIAL_DICE_ROLL, // needed for elimination
            "end" => ST_INITIAL_DICE_ROLL, // for zombie
        ],
    ],

    ST_INITIAL_DICE_ROLL => [
        "name" => "initialDiceRoll",
        "description" => "",
        "type" => "game",
        "action" => "stInitialDiceRoll",
        "transitions" => [ 
            "" => ST_PLAYER_THROW_DICE,
        ],
    ],

    ST_PLAYER_THROW_DICE => [
        "name" => "throwDice",
        "description" => clienttranslate('${actplayer} can reroll dice or resolve dice'),
        "descriptionlast" => clienttranslate('${actplayer} must resolve dice'),
        "descriptionmyturn" => clienttranslate('${you} can reroll dice or resolve dice'),
        "descriptionmyturnlast" => clienttranslate('${you} must resolve dice'),
        "type" => "activeplayer",
        "action" => "stThrowDice",
        "args" => "argThrowDice",
        "possibleactions" => [ "rethrow", "actRethrow", "goToChangeDie", "actGoToChangeDie", "buyEnergyDrink", "actBuyEnergyDrink", "rethrow3", "actRethrow3", "useSmokeCloud", "actUseSmokeCloud", "useCultist", "actUseCultist", "rerollDie", "actRerollDie", "useRapidCultist", "actUseRapidCultist" ],
        "transitions" => [
            "goToChangeDie" => ST_PLAYER_CHANGE_DIE,
            "psychicProbe" => ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE,
            //"zombiePass" => ST_NEXT_PLAYER,
        ],
    ],  

    ST_PLAYER_CHANGE_DIE => [
        "name" => "changeDie",
        "description" => clienttranslate('${actplayer} can change die result'),
        "descriptionmyturn" => clienttranslate('${you} can change die result (click on a die to change it)'),
        "type" => "activeplayer",
        "action" => "stChangeDie",
        "args" => "argChangeDie",
        "possibleactions" => [ "changeDie", "actChangeDie", "resolve", "actResolve", "rethrow3changeDie", "actRethrow3ChangeDie", "useYinYang", "actUseYinYang" ],
        "transitions" => [
            "changeDie" => ST_PLAYER_CHANGE_DIE,
            "changeDieWithPsychicProbe" => ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE,
            "resolve" => ST_PREPARE_RESOLVE_DICE,
        ],

    ],

    ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE => [
        "name" => "psychicProbeRollDie", // 'changeActivePlayerDie'
        "description" => clienttranslate('Players with special card can reroll a die'),
        "descriptionmyturn" => clienttranslate('${you} can reroll a die'),
        "type" => "multipleactiveplayer",
        "action" => "stChangeActivePlayerDie",
        "args" => "argChangeActivePlayerDie",
        "possibleactions" => [ "psychicProbeRollDie", "actPsychicProbeRollDie", "changeActivePlayerDie", "psychicProbeSkip", "actPsychicProbeSkip", "changeActivePlayerDieSkip", "actChangeActivePlayerDieSkip", "rethrow3psychicProbe", "actRethrow3PsychicProbe" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE,
            "end" => ST_PREPARE_RESOLVE_DICE,
            "endAndChangeDieAgain" => ST_PLAYER_CHANGE_DIE,
        ],
    ],

    ST_PREPARE_RESOLVE_DICE => [
        "name" => "prepareResolveDice",
        "description" => "",
        "descriptionmyturn" => "",
        "descriptionEncasedInIce" => clienttranslate('${actplayer} can freeze a die'),
        "descriptionmyturnEncasedInIce" => clienttranslate('${you} can freeze a die'),
        "type" => "activeplayer",
        "action" => "stPrepareResolveDice",
        "args" => "argPrepareResolveDice",
        "possibleactions" => [ "freezeDie", "actFreezeDie", "skipFreezeDie", "actSkipFreezeDie" ],
        "transitions" => [],
    ],

    ST_MULTIPLAYER_CHEERLEADER_SUPPORT => [
        "name" => "cheerleaderSupport",
        "description" => clienttranslate('Player with Cheerleader can support monster'),
        "descriptionmyturn" => clienttranslate('${you} can support monster'),
        "type" => "multipleactiveplayer",
        "args" => "argCheerleaderSupport",
        "action" => "stCheerleaderSupport",
        "possibleactions" => [ "support", "actSupport", "dontSupport", "actDontSupport" ],
        "transitions" => [
            "end" => ST_MULTIPLAYER_ASK_MINDBUG,
        ],
    ],

    ST_RESOLVE_DIE_OF_FATE => [
        "name" => "resolveDieOfFate",
        "description" => "",
        "type" => "game",
        "action" => "stResolveDieOfFate",
        "transitions" => [
            "next" => ST_RESOLVE_DICE,
        ],
    ],

    ST_PLAYER_DISCARD_DIE => [
        "name" => "discardDie",
        "description" => clienttranslate('${actplayer} must discard a die'),
        "descriptionmyturn" => clienttranslate('${you} must discard a die (click on a die to discard it)'),
        "type" => "activeplayer",
        "action" => "stDiscardDie",
        "args" => "argDiscardDie",
        "possibleactions" => [ "discardDie", "actDiscardDie" ],
        "transitions" => [
            "next" => ST_RESOLVE_DICE,
        ],
    ],

    ST_PLAYER_DISCARD_KEEP_CARD => [
        "name" => "discardKeepCard",
        "description" => clienttranslate('${actplayer} must discard a [keep] card'),
        "descriptionmyturn" => clienttranslate('${you} must discard a [keep] card'),
        "type" => "activeplayer",
        "args" => "argDiscardKeepCard",
        "possibleactions" => [ "discardKeepCard", "actDiscardKeepCard" ],
        "transitions" => [
            "next" => ST_RESOLVE_DICE,
        ],
    ],

    ST_PLAYER_GIVE_GOLDEN_SCARAB => [ // TODOPU move to answerQuestion ?
        "name" => "giveGoldenScarab",
        "description" => clienttranslate('${actplayer} must give Golden Scarab'),
        "descriptionmyturn" => clienttranslate('${you} must give Golden Scarab'),
        "type" => "activeplayer",
        "args" => "argGiveGoldenScarab",
        "possibleactions" => [ "giveGoldenScarab", "actGiveGoldenScarab" ],
        "transitions" => [
            "next" => ST_RESOLVE_DICE,
        ],
    ],

    ST_PLAYER_GIVE_SYMBOLS => [ // TODOPU move to answerQuestion ?
        "name" => "giveSymbols",
        "description" => clienttranslate('${actplayer} must give 2[Heart]/[Energy]/[Star]'),
        "descriptionmyturn" => clienttranslate('${you} must give 2[Heart]/[Energy]/[Star]'),
        "type" => "activeplayer",
        "args" => "argGiveSymbols",
        "possibleactions" => [ "giveSymbols", "actGiveSymbols" ],
        "transitions" => [
            "next" => ST_RESOLVE_DICE,
        ],
    ],

    ST_PLAYER_SELECT_EXTRA_DIE => [ // TODOPU move to answerQuestion ?
        "name" => "selectExtraDie",
        "description" => clienttranslate('${actplayer} must select the face of the extra die'),
        "descriptionmyturn" => clienttranslate('${you} must select the face of the extra die'),
        "type" => "activeplayer",
        "args" => "argSelectExtraDie",
        "possibleactions" => [ "selectExtraDie", "actSelectExtraDie" ],
        "transitions" => [
            "next" => ST_RESOLVE_DICE,
        ],
    ],

    ST_PLAYER_REROLL_OR_DISCARD_DICE => [
        "name" => "rerollOrDiscardDie",
        "description" => clienttranslate('${actplayer} can reroll or discard a die'),
        "descriptionmyturn" => clienttranslate('${you} can reroll or discard a die (select action then die)'),
        "type" => "activeplayer",
        "args" => "argRerollOrDiscardDie",
        "possibleactions" => [ "falseBlessingReroll", "actFalseBlessingReroll", "falseBlessingDiscard", "actFalseBlessingDiscard", "falseBlessingSkip", "actFalseBlessingSkip" ],
        "transitions" => [
            "next" => ST_RESOLVE_DICE,
        ],
    ],

    ST_MULTIPLAYER_REROLL_DICE => [
        "name" => "rerollDice",
        "description" => clienttranslate('${player_name} can reroll two dice'),
        "descriptionmyturn" => clienttranslate('${you} can reroll two dice'),
        "type" => "multipleactiveplayer",
        "args" => "argRerollDice",
        "action" => "stRerollDice",
        "possibleactions" => [ "rerollDice", "actRerollDice" ],
        "transitions" => [
            "end" => ST_RESOLVE_DICE,
        ],
    ],

    ST_RESOLVE_DICE => [
        "name" => "resolveDice",
        "description" => '',
        "descriptionHibernation" => clienttranslate('${actplayer} can leave Hibernation'),
        "descriptionmyturn" => '',
        "descriptionmyturnHibernation" => clienttranslate('${you} can leave Hibernation'),
        "type" => "activeplayer",
        "action" => "stResolveDice",
        "args" => "argResolveDice",
        "possibleactions" => [ "stayInHibernation", "actStayInHibernation", "leaveHibernation", "actLeaveHibernation" ],
        "transitions" => [
            "resolveNumberDice" => ST_RESOLVE_NUMBER_DICE, // TODOCY remove and test
        ],
    ],

    ST_PLAYER_BEFORE_RESOLVE_DICE => [
        "name" => "beforeResolveDice",
        "description" => clienttranslate('${actplayer} may activate an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} may activate an Evolution card'),
        "type" => "activeplayer",
        "action" => "stBeforeResolveDice",
        "args" => "argBeforeResolveDice",
        "possibleactions" => [ "skipBeforeResolveDice", "actSkipBeforeResolveDice" ],
        "transitions" => [],
    ],

    ST_RESOLVE_NUMBER_DICE => [
        "name" => "resolveNumberDice",
        "description" => "",
        "type" => "game",
        "action" => "stResolveNumberDice",
        "args" => "argResolveNumberDice",
        "transitions" => [
            "takeWickednessTile" => ST_PLAYER_TAKE_WICKEDNESS_TILE,
            "next" => ST_RESOLVE_HEART_DICE,
            "nextAction" => ST_RESOLVE_HEART_DICE_ACTION,
        ],
    ],

    ST_PLAYER_TAKE_WICKEDNESS_TILE => [
        "name" => "takeWickednessTile",
        "description" => clienttranslate('${actplayer} can take a wickedness tile'),
        "descriptionmyturn" => clienttranslate('${you} can take a wickedness tile'),
        "type" => "activeplayer",
        "action" => "stTakeWickednessTile",
        "args" => "argTakeWickednessTile",
        "possibleactions" => [ "takeWickednessTile", "actTakeWickednessTile", "skipTakeWickednessTile", "actSkipTakeWickednessTile" ],
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
        "action" => "stResolveHeartDiceAction",
        "possibleactions" => [ "applyHeartDieChoices", "actApplyHeartDieChoices" ],
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
        "transitions" => [],
    ],

    ST_RESOLVE_SMASH_DICE_ACTION => [
        "name" => "resolveSmashDiceAction",
        "description" => clienttranslate('${actplayer} can select effect of [diceSmash] dice'),
        "descriptionmyturn" => clienttranslate('${you} can select effect of [diceSmash] dice'),
        "type" => "activeplayer",
        "args" => "argResolveSmashDiceAction",
        "possibleactions" => [ "applySmashDieChoices", "actApplySmashDieChoices" ],
        "transitions" => [],
    ],

    ST_RESOLVE_SKULL_DICE => [
        "name" => "resolveSkullDice",
        "description" => "",
        "type" => "game",
        "action" => "stResolveSkullDice",
        "transitions" => [
            "enterTokyo" => ST_ENTER_TOKYO_APPLY_BURROWING,
            "smashes" => ST_MULTIPLAYER_LEAVE_TOKYO,
        ],
    ],

    ST_CHOOSE_EVOLUTION_CARD => [
        "name" => "chooseEvolutionCard",
        "description" => clienttranslate('${actplayer} must choose an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} must choose an Evolution card'),
        "type" => "activeplayer",
        "args" => "argChooseEvolutionCard",
        "possibleactions" => [ "chooseEvolutionCard", "actChooseEvolutionCard" ],
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
        "possibleactions" => [ "throwCamouflageDice", "actThrowCamouflageDice", "useWings", "actUseWings", "skipWings", "actSkipWings", "useRobot", "actUseRobot", "useElectricArmor", "actUseElectricArmor", "useSuperJump", "actUseSuperJump", "useRapidHealingSync", "actUseRapidHealingSync", "rethrow3camouflage", "actRethrow3Camouflage", "useInvincibleEvolution", "actUseInvincibleEvolution", "useCandyEvolution", "actUseCandyEvolution", "useRapidHealing", "actUseRapidHealing", "useMothershipSupport", "actUseMothershipSupport" ],
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
        "possibleactions" => [ "stay", "actStay", "leave", "actLeave", "useChestThumping", "actUseChestThumping", "skipChestThumping", "actSkipChestThumping" ],
        "transitions" => [
            "resume" => ST_LEAVE_TOKYO_APPLY_JETS,
            "end" => ST_LEAVE_TOKYO_APPLY_JETS, // for zombie
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
        "transitions" => [],
    ],

    ST_MULTIPLAYER_LEAVE_TOKYO_EXCHANGE_CARD => [
        "name" => "leaveTokyoExchangeCard",
        "description" => clienttranslate('Players with Unstable DNA can exchange this card'),
        "descriptionmyturn" => clienttranslate('${you} can exchange Unstable DNA'),
        "type" => "multipleactiveplayer",
        "action" => "stLeaveTokyoExchangeCard",
        "args" => "argLeaveTokyoExchangeCard",
        "possibleactions" => [ "exchangeCard", "actExchangeCard", "skipExchangeCard", "actSkipExchangeCard" ],
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
            "skipBeforeEnteringTokyo",
            "actSkipBeforeEnteringTokyo",
            "useFelineMotor", "actUseFelineMotor", 
        ],
        "transitions" => [
            'next' => ST_ENTER_TOKYO,
            "end" => ST_ENTER_TOKYO, // for zombie
        ],        
    ],

    ST_ENTER_TOKYO => [
        "name" => "enterTokyo",
        "description" => "",
        "type" => "game",
        "action" => "stEnterTokyo",
        "transitions" => [
            "stealCostumeCard" => ST_PLAYER_STEAL_COSTUME_CARD_OR_GIVE_GIFT_EVOLUTION,
            "buyCard" => ST_PLAYER_BUY_CARD,
        ],
    ],

    ST_PLAYER_AFTER_ENTERING_TOKYO => [
        "name" => "afterEnteringTokyo",
        "description" => clienttranslate('Some players may activate an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} may activate an Evolution card'),
        "type" => "activeplayer",
        "action" => "stAfterEnteringTokyo",
        "args" => "argAfterEnteringTokyo",
        "possibleactions" => [ "skipAfterEnteringTokyo", "actSkipAfterEnteringTokyo" ],
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
        "possibleactions" => [ "stealCostumeCard", "actStealCostumeCard", 'giveGiftEvolution', 'actGiveGiftEvolution', "endStealCostume", "actEndStealCostume" ],
        "transitions" => [],
    ],

    ST_PLAYER_CHANGE_FORM => [
        "name" => "changeForm",
        "description" => clienttranslate('${actplayer} can change form'),
        "descriptionmyturn" => clienttranslate('${you} can change form'),
        "type" => "activeplayer",
        "args" => "argChangeForm",
        "action" => "stChangeForm",
        "possibleactions" => [ "changeForm", "actChangeForm", "skipChangeForm", "actSkipChangeForm" ],
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
        "possibleactions" => [ "buyCard", "actBuyCard", "goToSellCard", "actGoToSellCard", "endTurn", "actEndTurn", "renew", "actRenew", "useMiraculousCatch", "actUseMiraculousCatch" ],
        "transitions" => [
            "buyCard" => ST_PLAYER_BUY_CARD,
            //"buyMimicCard" => ST_PLAYER_CHOOSE_MIMICKED_CARD,
            "opportunist" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            "goToSellCard" => ST_PLAYER_SELL_CARD,
            "renew" => ST_PLAYER_BUY_CARD,
        ]
    ],

    /*ST_PLAYER_CHOOSE_MIMICKED_CARD => [
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
        ]
    ],*/

    ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD => [
        "name" => "opportunistBuyCard",
        "description" => clienttranslate('Player with Opportunist can buy revealed card'),
        "descriptionmyturn" => clienttranslate('${you} can buy revealed card'),
        "type" => "multipleactiveplayer",
        "action" => "stOpportunistBuyCard",
        "args" => "argOpportunistBuyCard",
        "possibleactions" => [ "buyCard", "actBuyCard", "opportunistSkip", "actOpportunistSkip" ],
        "transitions" => [
            "stay" => ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            //"stayMimicCard" => ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD,
            "end" => ST_PLAYER_BUY_CARD,
        ],
    ],

    /*ST_MULTIPLAYER_OPPORTUNIST_CHOOSE_MIMICKED_CARD => [
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
        ],
    ],*/

    ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT => [ 
        "name" => "cardIsBought",
        "description" => clienttranslate('Some players may activate an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} may activate an Evolution card'),
        "type" => "multipleactiveplayer",
        "action" => "stCardIsBought",
        "args" => "argCardIsBought",
        "possibleactions" => [ "skipCardIsBought", "actSkipCardIsBought" ],
        "transitions" => [
            "next" => ST_AFTER_WHEN_CARD_IS_BOUGHT,
            "end" => ST_AFTER_WHEN_CARD_IS_BOUGHT, // for zombie
        ],
    ],

    ST_AFTER_WHEN_CARD_IS_BOUGHT => [
        "name" => "afterCardIsBought",
        "description" => "",
        "type" => "game",
        "action" => "stAfterCardIsBought",
        "transitions" => [],
    ],

    ST_PLAYER_SELL_CARD => [
        "name" => "sellCard",
        "description" => clienttranslate('${actplayer} can sell a card'),
        "descriptionmyturn" => clienttranslate('${you} can sell a card'),
        "type" => "activeplayer",
        "args" => "argSellCard",
        "action" => "stSellCard",
        "possibleactions" => [ "sellCard", "actSellCard", "endTurn", "actEndTurn" ],
        "transitions" => [
            "sellCard" => ST_PLAYER_SELL_CARD,
        ]
    ],

    ST_MULTIPLAYER_ANSWER_QUESTION => [
        "name" => "answerQuestion",
        "description" => '',
        "descriptionmyturn" => '',
        "type" => "multipleactiveplayer",
        "action" => "stAnswerQuestion",
        "args" => "argAnswerQuestion",
        "possibleactions" => [ 
            "chooseMimickedCard", "actChooseMimickedCard",
            'gazeOfTheSphinxDrawEvolution', 'actGazeOfTheSphinxDrawEvolution', 'gazeOfTheSphinxGainEnergy', 'actGazeOfTheSphinxGainEnergy',
            'gazeOfTheSphinxDiscardEvolution', 'actGazeOfTheSphinxDiscardEvolution', 'gazeOfTheSphinxLoseEnergy', 'actGazeOfTheSphinxLoseEnergy',
                         "putEnergyOnBambooSupply", "actPutEnergyOnBambooSupply", "takeEnergyOnBambooSupply", "actTakeEnergyOnBambooSupply",            "buyCardBamboozle", "actBuyCardBamboozle",
            "giveSymbol",
            "actGiveSymbol",
            "chooseMimickedEvolution", "actChooseMimickedEvolution",
            "chooseFreezeRayDieFace", "actChooseFreezeRayDieFace",
            "buyCardMiraculousCatch", "actBuyCardMiraculousCatch", "skipMiraculousCatch", "actSkipMiraculousCatch",
            "playCardDeepDive", "actPlayCardDeepDive",
            "useExoticArms", "actUseExoticArms", "skipExoticArms", "actSkipExoticArms",
            "giveTarget", "actGiveTarget", "skipGiveTarget", "actSkipGiveTarget",
            "useLightningArmor", "actUseLightningArmor", "skipLightningArmor", "actSkipLightningArmor",
            "answerEnergySword", "actAnswerEnergySword",
            "answerSunkenTemple", "actAnswerSunkenTemple",
            "answerElectricCarrot", "actAnswerElectricCarrot",
            "reserveCard", "actReserveCard",
            "throwDieSuperiorAlienTechnology", "actThrowDieSuperiorAlienTechnology",
            "freezeRayChooseOpponent", "actFreezeRayChooseOpponent",
            "loseHearts", "actLoseHearts",
        ],
        "transitions" => [
            "next" => ST_AFTER_ANSWER_QUESTION,
            "end" => ST_AFTER_ANSWER_QUESTION, // for zombie
        ],
    ],

    ST_AFTER_ANSWER_QUESTION => [
        "name" => "afterAnswerQuestion",
        "description" => "",
        "type" => "game",
        "action" => "stAfterAnswerQuestion",
        "transitions" => [],
    ],

    ST_MULTIPLAYER_BEFORE_END_TURN => [
        "name" => "beforeEndTurn",
        "description" => clienttranslate('${actplayer} may activate an Evolution card'),
        "descriptionmyturn" => clienttranslate('${you} may activate an Evolution card'),
        "type" => "multipleactiveplayer",
        "action" => "stBeforeEndTurn",
        "args" => "argBeforeEndTurn",
        "possibleactions" => [ "skipBeforeEndTurn", "actSkipBeforeEndTurn" ],
        "transitions" => [
            "next" => ST_AFTER_BEFORE_END_TURN,
            "end" => ST_AFTER_BEFORE_END_TURN, // for zombie
        ],
    ],
];
 
$machinestates = $playerActionsGameStates;
