/**
 * Your game interfaces
 */

interface Dice {
    id: number;
    value: number;
    extra: boolean;
    locked: boolean;
    rolled: boolean;
}

interface Card {
    id: number;
    type: number;
    cost: number;
    tokens: number;
    mimicType: number;
}

type HeartAction = 'heal' | 'shrink-ray' | 'poison' | 'heal-player';

interface HeartActionSelection {
    action: HeartAction;
    playerId?: number;
}

interface KingOfTokyoPlayer extends Player {
    player_no: string;
    poisonTokens: number;
    shrinkRayTokens: number;
    rapidHealing: boolean;
    health: number;
    energy: number;
    monster: number;
    location: number;
    maxHealth: number;
}

interface KingOfTokyoGamedatas {
    current_player_id: string;
    decision: {decision_type: string};
    game_result_neutralized: string;
    gamestate: Gamestate;
    gamestates: { [gamestateId: number]: Gamestate };
    neutralized_player_id: string;
    notifications: {last_packet_id: string, move_nbr: string}
    playerorder: (string | number)[];
    players: { [playerId: number]: KingOfTokyoPlayer };
    tablespeed: string;

    // Add here variables you set up in getAllDatas
    dice: Dice[];
    visibleCards: Card[];
    playersCards: { [playerId: number]: Card[] };
    mimickedCard: Card | null;
}

interface KingOfTokyoGame extends Game {
    isDefaultFont(): boolean;
    cards: Cards;

    changeDie: (id: number, value: number, card: number) => void;
    psychicProbeRollDie: (id: number) => void;
    createButton: (destinationId: string, id: string, text: string, callback: Function, disabled?: boolean) => void;
    onVisibleCardClick: (stock: Stock, cardId: string, from: number) => void;
    getPlayerId: () => number;
    applyHeartActions: (selections: HeartActionSelection[]) => void;
}

interface EnteringDiceArgs {
    dice: Dice[];
    inTokyo: boolean;
}

interface EnteringThrowDiceArgs extends EnteringDiceArgs {
    throwNumber: number;
    maxThrowNumber: number;
    energyDrink: {
        hasCard: boolean;
        playerEnergy: number;
    }
    rethrow3: {
        hasCard: boolean;
        hasDice3: boolean;
    };
    hasSmokeCloud: boolean;
}

interface EnteringChangeDieArgs extends EnteringDiceArgs {
    hasHerdCuller: boolean;
    hasPlotTwist: boolean;
    hasStretchy: boolean;
}

interface EnteringResolveHeartDiceArgs extends EnteringDiceArgs {
    hasHealingRay: boolean;
    healablePlayers: {
        id: number;
        name: string; 
        color: string; 
        missingHearts: number;
    }[];
    poisonTokens: number;
    shrinkRayTokens: number;
    skipped: boolean;
}

interface EnteringBuyCardArgs {
    disabledIds: number[];
    canBuyFromPlayers: boolean;
    _private: {
        pickCards: Card[];
    };
}

interface EnteringCancelDamageArgs {
    canSkipWings: boolean;
    canThrowDices: boolean;
    canUseWings: boolean;
    playerEnergy: number;
    dice: number[];
}

interface NotifResolveArgs {
    playerId: number;
    player_name: string;
}

interface NotifResolveNumberDiceArgs extends NotifResolveArgs {
    points: number;
    deltaPoints: number;
    diceValue: number;
}

interface NotifResolveHealthDiceArgs extends NotifResolveArgs {
    health: number;
    deltaHealth: number;
}
interface NotifResolveHealthDiceInTokyoArgs extends NotifResolveArgs {}

interface NotifResolveEnergyDiceArgs extends NotifResolveArgs {
    energy: number;
    deltaEnergy: number;
}

interface NotifResolveSmashDiceArgs extends NotifResolveArgs {
    number: number;
    smashedPlayersIds: number[];
}

interface NotifPlayerEliminatedArgs {
    who_quits: number;
    player_name: string;
}

interface NotifPlayerLeavesTokyoArgs {
    playerId: number;
    player_name: string;
}

interface NotifPlayerEntersTokyoArgs {
    playerId: number;
    player_name: string;
    location: number;
    locationName: string;
    points: number;
}

interface NotifBuyCardArgs {
    playerId: number;
    player_name: string;
    card: Card;
    newCard: Card;
    energy: number;
    from: number;
}

interface NotifRenewCardsArgs {
    playerId: number;
    player_name: string;
    cards: Card[];
    energy: number;
}

interface NotifRemoveCardsArgs {
    playerId: number;
    player_name: string;
    cards: Card[];
}

interface NotifPointsArgs extends NotifResolveArgs {
    points: number;
}

interface NotifHealthArgs extends NotifResolveArgs {
    health: number;
}

interface NotifEnergyArgs extends NotifResolveArgs {
    energy: number;
}

interface NotifMaxHealthArgs extends NotifHealthArgs {
    maxHealth: number;
}

interface NotifSetPlayerTokensArgs extends NotifResolveArgs {
    tokens: number;
}

interface NotifSetCardTokensArgs {
    playerId: number;
    card: Card;
}

interface NotifToggleRapidHealingArgs {
    playerId: number;
    active: boolean;
    playerEnergy: number;
    isMaxHealth: boolean;
}

interface NotifUseCamouflageArgs {
    playerId: number;
    diceValues: number[];
    cancelDamageArgs: EnteringCancelDamageArgs;
}

interface NotifChangeDieArgs {
    playerId: number;
    dieId: number;
    toValue: number;
}