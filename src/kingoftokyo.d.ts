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
}

interface EnteringThrowDiceArgs {
    dice: Dice[];
    throwNumber: number;
    maxThrowNumber: number;
    inTokyo: boolean;
    energyDrink: {
        hasCard: boolean;
        playerEnergy: number;
    }
    rethrow3: {
        hasCard: boolean;
        hasDice3: boolean;
    };
}

interface EnteringBuyCardArgs {
    disabledIds: number[];
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
