/**
 * Your game interfaces
 */

interface Card {
    id: number;
    type: number;
    pearls: number;
    points: number;
}

interface Lord extends Card {
    guild: number;    
    key: number; 
    showExtraLord: boolean;
    swap: boolean;
}

interface Location extends Card {
    activePower?: number;
    passivePower?: number;
    passivePowerGuild?: number;
}

interface PlayerTableSpot {
    lord?: Lord;
    location?: Location;
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
    visibleLords: { [spot: number]: Lord[] };
    pickLords: Lord[];
    visibleLocations: Location[];
    pickLocations: Location[];
    pearlMasterPlayer: number;
    playersTables: { [playerId: number]: PlayerTableSpot[] };
    endTurn: boolean;
}

interface KingOfTokyo extends Game {
}

interface Score {
    lords: number;
    locations: number;
    coalition: number;
    pearlMaster: number;
    total: number;
}

interface EnteringStackSelectionArgs {
    max: number;
}

interface EnteringLordStackSelectionArgs extends EnteringStackSelectionArgs {
    limitToHidden: number;
}

interface EnteringLordSelectionArgs {
    lords: Lord[];
    multiple: boolean;
}

interface EnteringLocationStackSelectionArgs extends EnteringStackSelectionArgs {
    allHidden: boolean;
}

interface EnteringLocationSelectionArgs {
    locations: Location[];
}

interface NotifDiscardLordPickArgs {
    discardedLords: Lord[];
}

interface NotifDiscardLocationPickArgs {
    discardedLocations: Location[];
}

interface NotifNewScoreArgs {
    playerId: number;
    newScore: Score;
}

interface NotifLordPlayedArgs extends NotifNewScoreArgs {
    lord: Lord;
    spot: number;
    stackSelection: boolean;
    discardedLords: Lord[];
    pearls: number;
}

interface NotifLordSwappedArgs extends NotifNewScoreArgs {
    spot1: number;
    spot2: number;
}

interface NotifExtraLordRevealedArgs {
    lord: Lord;
}

interface NotifLocationPlayedArgs extends NotifNewScoreArgs {
    location: Location;
    spot: number;
    discardedLocations: Location[];
    pearls: number;
}

interface NotifNewPearlMasterArgs {
    playerId: number;
    previousPlayerId: number;
}

interface NotifScorePointArgs {
    playerId: number;
    points: number;
}

interface Coalition {
    spot: number;
    size: number;
    guild: number;
    alreadyCounted: number[];
}

interface NotifScoreCoalitionArgs extends NotifScorePointArgs {
    coalition: Coalition;
}

interface NotifScorePearlMasterArgs {
    playerId: number;
}