/**
 * Your game interfaces
 */

interface Dice {
    id: number;
    value: number;
    extra: boolean;
    locked: boolean;
}

interface Card {
    id: number;
    type: number;
    cost: number;
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
    players: { [playerId: number]: Player };
    tablespeed: string;

    // Add here variables you set up in getAllDatas
    dice: Dice[];
    visibleCards: Card[];
    playersCards: { [playerId: number]: Card[] };
}

interface KingOfTokyoGame extends Game {
    cards: Cards;

    changeDie: (id: number, value: number, card: number) => void;
    createButton: (destinationId: string, id: string, text: string, callback: Function, disabled?: boolean) => void;
    onVisibleCardClick: (stock: Stock, cardId: string, from: number) => void;
    getPlayerId: () => number;
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
}

interface EnteringChangeDieArgs extends EnteringDiceArgs {
    hasHerdCuller: boolean;
    hasPlotTwist: boolean;
    hasStretchy: boolean;
    hasEnergyForStretchy: boolean;
}

interface EnteringBuyCardArgs {
    disabledIds: number[];
    canBuyFromPlayers: boolean;
    _private: {
        pickCard: Card;
    };
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
