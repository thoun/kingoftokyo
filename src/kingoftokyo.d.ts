/**
 * Your game interfaces
 */

interface Dice {
    id: number;
    value: number;
    extra: boolean;
    locked: boolean;
    rolled: boolean;
    type: number;
}

interface Card {
    id: number;
    type: number;
    cost: number;
    tokens: number;
    mimicType: number;
    location_arg: number;
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
    playerDead: number;
    tokyoTowerLevels?: number[];
    berserk?: boolean;
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
    topDeckCardBackType: string;
    playersCards: { [playerId: number]: Card[] };
    mimickedCard: Card | null;
    leaveTokyoUnder: number;
    stayTokyoOver: number;
    twoPlayersVariant: boolean;
    halloweenExpansion: boolean;
    kingkongExpansion: boolean;
    tokyoTowerLevels: number[];
    cybertoothExpansion: boolean;
}

interface KingOfTokyoGame extends Game {
    isDarkEdition(): boolean;
    isHalloweenExpansion(): boolean;
    isKingkongExpansion(): boolean;
    isCybertoothExpansion(): boolean;
    isDefaultFont(): boolean;
    
    cards: Cards;
    POISON_TOKEN_TOOLTIP: string;
    SHINK_RAY_TOKEN_TOOLTIP: string;

    changeDie: (id: number, value: number, card: number) => void;
    psychicProbeRollDie: (id: number) => void;
    createButton: (destinationId: string, id: string, text: string, callback: Function, disabled?: boolean) => void;
    onVisibleCardClick: (stock: Stock, cardId: string, from: number) => void;
    getPlayerId: () => number;
    applyHeartActions: (selections: HeartActionSelection[]) => void;
    getZoom(): number;
    getPreferencesManager(): PreferencesManager;
    checkBuyEnergyDrinkState(): void;
    checkUseSmokeCloudState(): void;
    setFont(prefValue: number): void;
}

interface EnteringPickMonsterArgs {
    availableMonsters: number[];
}

interface EnteringChooseInitialCardArgs {
    cards: Card[];
}

interface EnteringDiceArgs {
    dice: Dice[];
    inTokyo: boolean;
}

interface EnergyDrink {
    hasCard: boolean;
    playerEnergy: number;
}

interface Rethrow3 {
    hasCard: boolean;
    hasDice3: boolean;
}

interface EnteringThrowDiceArgs extends EnteringDiceArgs {
    throwNumber: number;
    maxThrowNumber: number;
    energyDrink: EnergyDrink;
    rethrow3: Rethrow3;
    hasSmokeCloud: boolean;
    hasActions: boolean;
}

interface EnteringPsychicProbeRollDieArgs extends EnteringDiceArgs {
    canRoll: boolean;
    rethrow3: Rethrow3;
}

interface EnteringChangeDieArgs extends EnteringDiceArgs {
    hasHerdCuller: boolean;
    hasPlotTwist: boolean;
    hasStretchy: boolean;
    hasClown: boolean;
    rethrow3: Rethrow3;
}

interface HealablePlayer {
    id: number;
    name: string; 
    color: string; 
    missingHearts: number;
}

interface EnteringResolveHeartDiceArgs extends EnteringDiceArgs {
    hasHealingRay: boolean;
    healablePlayers: HealablePlayer[];
    poisonTokens: number;
    shrinkRayTokens: number;
    skipped: boolean;
}

interface EnteringStealCostumeCardArgs {
    disabledIds: number[];
    canBuyFromPlayers: boolean;
}

interface EnteringBuyCardArgs {
    disabledIds: number[];
    canBuyFromPlayers: boolean;
    canBuyOrNenew: boolean;
    canSell: boolean;
    _private: {
        pickCards: Card[];
    };
}

interface EnteringCancelDamageArgs {
    canThrowDices: boolean;
    canUseWings: boolean;
    canUseRobot: boolean;
    playerEnergy: number;
    dice: Dice[];
    rethrow3: Rethrow3;
    rapidHealingHearts: number;
    damage: number;
}

interface EnteringLeaveTokyoArgs {
    jetsDamage: number;
    jetsPlayers: number[];
    _private?: {
        skipBuyPhase: boolean;
    };
}

interface NotifPickMonsterArgs {
    playerId: number;
    monster: number;
}

interface NotifSetInitialCardsArgs {
    cards: Card[];
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



interface NotifResolveHealingRayArgs {
    healedPlayerId: number;
    healNumber: number;
}

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
    energy: number;
}

interface NotifBuyCardArgs {
    playerId: number;
    player_name: string;
    card: Card;
    newCard: Card;
    energy: number;
    from: number;
    discardCard?: Card;
    topDeckCardBackType: string;
}

interface NotifRenewCardsArgs {
    playerId: number;
    player_name: string;
    cards: Card[];
    energy: number;
    topDeckCardBackType: string;
}

interface NotifRemoveCardsArgs {
    playerId: number;
    player_name: string;
    cards: Card[];
    delay: boolean;
}

interface NotifPointsArgs extends NotifResolveArgs {
    points: number;
}

interface NotifHealthArgs extends NotifResolveArgs {
    health: number;
    delta_health: number;
}

interface NotifEnergyArgs extends NotifResolveArgs {
    energy: number;
}

interface NotifMaxHealthArgs extends NotifHealthArgs {
    maxHealth: number;
}

interface NotifSetPlayerTokensArgs extends NotifResolveArgs {
    tokens: number;
    deltaTokens?: number;
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
    diceValues: Dice[];
    cancelDamageArgs: EnteringCancelDamageArgs;
}

interface NotifChangeDieArgs {
    playerId: number;
    inTokyo: boolean;
    dieId: number;
    toValue: number;
    roll?: boolean;
    psychicProbeRollDieArgs?: EnteringPsychicProbeRollDieArgs;
}

interface NotifUpdateLeaveTokyoUnderArgs {
    under: number;
}

interface NotifUpdateStayTokyoOverArgs {
    over: number;
}

interface NotifChangeTokyoTowerOwnerArgs {
    playerId: number;
    level: number;
}

interface NotifSetPlayerBerserkArgs {
    playerId: number;
    berserk: boolean;
}
