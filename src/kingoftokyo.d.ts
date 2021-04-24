/**
 * Your game interfaces
 */

interface Dice {
    id: number;
    value: number;
    extra: boolean;
}

/*interface Card {
    id: number;
    type: number;
    pearls: number;
    points: number;
}*/

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
}

interface KingOfTokyo extends Game {
}

interface EnteringThrowDicesArgs {
    dices: Dice[];
    throwNumber: number;
    maxThrowNumber: number;
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
