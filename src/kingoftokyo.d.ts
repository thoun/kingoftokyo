/**
 * Your game interfaces
 */

interface Dice {
    id: number;
    value: number;
    extra: boolean;
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
    visibleCards: Card[];
}

interface KingOfTokyo extends Game {
}

interface EnteringThrowDicesArgs {
    dices: Dice[];
    throwNumber: number;
    maxThrowNumber: number;
}

interface EnteringPickCardArgs {
    disabledIds: number[];
}

interface NotifResolveArgs {
    playerId: number;
    player_name: string;
}

interface NotifResolveNumberDiceArgs extends NotifResolveArgs {
    points: number;
    diceValue: number;
}

interface NotifResolveHealthDiceArgs extends NotifResolveArgs {
    health: number;
}
interface NotifResolveHealthDiceInTokyoArgs extends NotifResolveArgs {}

interface NotifResolveEnergyDiceArgs extends NotifResolveArgs {
    number: number;
}

interface NotifResolveSmashDiceArgs extends NotifResolveArgs {
    number: number;
    smashedPlayersIds: number[];
}

interface NotifPlayerEliminatedArgs {
    playerId: number;
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
}

interface NotifPickCardArgs {
    playerId: number;
    player_name: string;
    card: Card;
    newCard: Card;
}

interface NotifRenewCardsArgs {
    playerId: number;
    player_name: string;
    cards: Card[];
}
