/**
 * Your game interfaces
 */

interface Die {
    id: number;
    value: number;
    extra: boolean;
    locked: boolean;
    rolled: boolean;
    type: number;
    canReroll: boolean;
}

interface Card {
    id: number;
    type: number;
    side: 0 | 1;
    cost: number;
    tokens: number;
    mimicType: number;
    location: string;
    location_arg: number;
}

interface WickednessTile {
    id: number;
    type: number;
    side: 0 | 1;
    tokens: number;
    mimicType: number;
    level: number;
    location: string;
    location_arg: number;
}

interface EvolutionCard {
    id: number;
    monster: number;
    card: number;
    type: number;
    tokens: number;
    location: string;
    location_arg: number;
}

type HeartAction = 'heal' | 'shrink-ray' | 'poison' | 'heal-player';
type SmashAction = 'smash' | 'steal';

interface HeartActionSelection {
    action: HeartAction;
    playerId?: number;
}

interface Question {
    code: string;
    description: string;
    descriptionmyturn: string;
    playersIds: number[];
    args: any;
}

interface BambooSupplyQuestionArgs {
    canTake: boolean;
}

interface BamboozleQuestionArgs {
    cardBeingBought: {
        cardId: number;
        playerId: number;
        from: number;
    }, 
    buyCardArgs: EnteringBuyCardArgs;
}

interface GazeOfTheSphinxSnakeQuestionArgs {
    canLoseEnergy: boolean;
}

interface GiveSymbolQuestionArgs {
    symbols: number[];
}

interface IcyReflectionQuestionArgs {
    enabledEvolutions: EvolutionCard[];
    disabledEvolutions: EvolutionCard[];
}

interface MiraculousCatchQuestionArgs {
    card: Card;
    cost: number;
    costSuperiorAlienTechnology: number;
}

interface DeepDiveQuestionArgs {
    cards: Card[];
}

interface TargetAcquiredQuestionArgs {
    playerId: number;
}

interface SuperiorAlienTechnologyQuestionArgs {
    card: Card;
}

interface FreezeRayChooseOpponentQuestionArgs {
    smashedPlayersIds: number[];
}

interface KingOfTokyoPlayer extends Player {
    player_no: string;
    poisonTokens: number;
    shrinkRayTokens: number;
    rapidHealing: boolean;
    mothershipSupport: boolean;
    mothershipSupportUsed: boolean;
    health: number;
    energy: number;
    monster: number;
    location: number;
    maxHealth: number;
    playerDead: number;
    tokyoTowerLevels?: number[];
    berserk?: boolean;
    cultists: number;
    wickedness?: number;
    cards: Card[];
    reservedCards: Card[]; // TODOPUBG
    wickednessTiles: WickednessTile[];
    visibleEvolutions?: EvolutionCard[];
    ownedEvolutions?: EvolutionCard[];
    hiddenEvolutions?: EvolutionCard[]; // filled only for current player, else EvolutionCard contains only id
    superiorAlienTechnologyTokens?: number[];
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
    dice: Die[];
    visibleCards: Card[];
    topDeckCardBackType: string;
    mimickedCards: {
        card: Card | null;
        tile: Card | null;
        evolution: Card | null;
    } 
    leaveTokyoUnder: number;
    stayTokyoOver: number;
    askPlayEvolution: number;
    twoPlayersVariant: boolean;
    halloweenExpansion: boolean;
    kingkongExpansion: boolean;
    tokyoTowerLevels: number[];
    cybertoothExpansion: boolean;
    mutantEvolutionVariant: boolean;
    cthulhuExpansion: boolean;
    anubisExpansion: boolean;
    wickednessExpansion: boolean;
    powerUpExpansion: boolean;
    darkEdition: boolean;
    playerWithGoldenScarab?: number;
    curseCard?: Card;
    wickednessTiles: WickednessTile[];
    EVOLUTION_CARDS_TYPES?: number[];
    EVOLUTION_CARDS_SINGLE_STATE?: { [stateName: string]: number[] };
    targetedPlayer?: number;
}

interface KingOfTokyoGame extends Game {
    isDarkEdition(): boolean;
    isHalloweenExpansion(): boolean;
    isKingkongExpansion(): boolean;
    isCybertoothExpansion(): boolean;
    isMutantEvolutionVariant(): boolean;
    isCthulhuExpansion(): boolean;
    isAnubisExpansion(): boolean;
    isWickednessExpansion(): boolean;
    isPowerUpExpansion(): boolean;
    isDefaultFont(): boolean;
    
    tableManager: TableManager;
    cards: Cards;
    curseCards: CurseCards;
    wickednessTiles: WickednessTiles;
    evolutionCards: EvolutionCards;
    POISON_TOKEN_TOOLTIP: string;
    SHINK_RAY_TOKEN_TOOLTIP: string;
    CULTIST_TOOLTIP: string;

    changeDie: (id: number, value: number, card: number) => void;
    psychicProbeRollDie: (id: number) => void;
    discardDie: (id: number) => void;
    rerollOrDiscardDie: (id: number) => void;
    freezeDie: (id: number) => void;
    createButton: (destinationId: string, id: string, text: string, callback: Function, disabled?: boolean) => void;
    onVisibleCardClick: (stock: Stock, cardId: number, from?: number) => void;
    takeWickednessTile(id: number): void;
    chooseEvolutionCardClick(id: number): void;
    getPlayerId: () => number;
    applyHeartActions: (selections: HeartActionSelection[]) => void;
    applySmashActions: (selections: SmashAction[]) => void;
    getZoom(): number;
    getPreferencesManager(): PreferencesManager;
    checkBuyEnergyDrinkState(): void;
    checkUseSmokeCloudState(): void;
    checkUseCultistState(): void;
    setFont(prefValue: number): void;
    toggleRerollDiceButton(): void;
    getPlayer(playerId: number): KingOfTokyoPlayer;
    getPlayerEnergy(playerId: number): number;
    onHiddenEvolutionClick: (cardId: number) => void;
    onVisibleEvolutionClick: (cardId: number) => void;
    addAutoSkipPlayEvolutionButton(): void;
}

interface EnteringPickMonsterArgs {
    availableMonsters: number[];
}

interface EnteringChooseInitialCardArgs {
    chooseCostume: boolean;
    chooseEvolution: boolean;
    cards?: Card[];
    _private?: {
        evolutions: EvolutionCard[];
    };
}

interface EnteringGiveSymbolToActivePlayerArgs {
    canGive: { [symbol: number]: boolean };
}

interface EnteringDiceArgs {
    dice: Die[];
    selectableDice: Die[];
    canHealWithDice: boolean;
    frozenFaces: number[];
}

interface EnergyDrink {
    hasCard: boolean;
    playerEnergy: number;
}

interface Rethrow3 {
    hasCard: boolean;
    hasDice3: boolean;
}

interface RerollDie {
    isBeastForm: boolean;
    canUseBeastForm: boolean;
}

interface EnteringPickEvolutionForDeckArgs {
    _private: {
        chooseCardIn: EvolutionCard[];
        inDeck: EvolutionCard[];
    };
}

interface EnteringStepEvolutionArgs {
    highlighted: EvolutionCard[];
}

interface EnteringBeforeEndTurnArgs extends EnteringStepEvolutionArgs {
    _private: { [cardId: number]: number[] };
}

interface EnteringThrowDiceArgs extends EnteringDiceArgs {
    throwNumber: number;
    maxThrowNumber: number;
    energyDrink: EnergyDrink;
    rethrow3: Rethrow3;
    rerollDie: RerollDie;
    hasSmokeCloud: boolean;
    hasActions: boolean;
    hasCultist: boolean;
}

interface EnteringPsychicProbeRollDieArgs extends EnteringDiceArgs {
    rethrow3: Rethrow3;
}

interface EnteringChangeDieArgs extends EnteringDiceArgs {
    playerId: number;
    hasHerdCuller: boolean;
    hasPlotTwist: boolean;
    hasStretchy: boolean;
    hasClown: boolean;
    hasSaurianAdaptability: boolean;
    hasGammaBreath: boolean;
    hasTailSweep: boolean;
    hasTinyTail: boolean;
    hasYinYang: boolean;
    rethrow3: Rethrow3;
}

interface EnteringDiscardKeepCardArgs {
    disabledIds: number[];
}

interface EnteringGiveGoldenScarabArgs {
    playersIds: number[];
}

interface EnteringGiveSymbolsArgs {
    combinations: number[][];
}

interface EnteringRerollDiceArgs extends EnteringDiceArgs {
    min: number;
    max: number;
}

interface EnteringPrepareResolveDiceArgs extends EnteringDiceArgs {
    hasEncasedInIce: boolean;
}
interface EnteringTakeWickednessTileArgs extends EnteringDiceArgs {
    level: number;
    canTake: boolean;
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

interface EnteringResolveSmashDiceArgs extends EnteringDiceArgs {    
    canUsePlayWithYourFood: boolean;
    willBeWoundedIds: number[];
    skipped: boolean;
}

interface EnteringStealCostumeCardArgs {
    disabledIds: number[];
    canBuyFromPlayers: boolean;
    cardsCosts: { [cardId: number]: number };
}

interface EnteringChangeFormArgs {
    canChangeForm: boolean;
    otherForm: string;
}

interface EnteringExchangeCardArgs {
    disabledIds: number[];
    canExchange: boolean;
}

interface BeforeEnteringTokyoArgs {
    canUseFelineMotor: number[];
}

interface EnteringBuyCardArgs {
    disabledIds: number[];
    canBuyFromPlayers: boolean;
    canBuyOrNenew: boolean;
    canSell: boolean;
    canUseAdaptingTechnology: boolean;
    canUseMiraculousCatch: boolean;
    unusedMiraculousCatch: boolean;
    gotSuperiorAlienTechnology: boolean;
    canUseSuperiorAlienTechnology: boolean;
    _private: {
        pickCards: Card[];
    };
    cardsCosts: { [cardId: number]: number };
    warningIds: { [cardId: number]: string };
    cardsCostsSuperiorAlienTechnology: { [cardId: number]: number };
}

interface EnteringCancelDamageArgs {
    canCancelDamage: boolean;
    canHealToAvoidDeath: boolean;
    canDoAction: boolean;
    skipMeansDeath: boolean;
    canThrowDices: boolean;
    canUseWings: boolean;
    canUseDetachableTail: boolean;
    canUseRabbitsFoot: boolean;
    canUseCandy: boolean;
    canUseRobot: boolean;
    countSuperJump: number;
    playerEnergy: number;
    dice: Die[];
    rethrow3: Rethrow3;
    rapidHealingHearts: number;
    superJumpHearts: number;
    rapidHealingCultists: number;
    damageToCancelToSurvive: number;
    canHeal: number;
    remainingDamage: number;
    replaceHeartByEnergyCost: { [energy: number]: number }; // key is the energy spent, value is the heart cost
}

interface EnteringLeaveTokyoArgs {
    jetsDamage: number;
    jetsPlayers: number[];
    simianScamperPlayers: number[];
    _private?: {
        skipBuyPhase: boolean;
    };
    canYieldTokyo: { [playerId: number]: boolean };
    smashedPlayersInTokyo: number[];
    activePlayerId: number;
    canUseChestThumping: boolean;
}

interface EnteringChooseEvolutionCardArgs {
    _private?: {
        evolutions: EvolutionCard[];
    };
}

interface EnteringAnswerQuestionArgs {
    question: Question;
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

interface EnteringResolveDiceArgs extends EnteringDiceArgs {
    canLeaveHibernation: boolean;
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

interface NotifRemoveWickednessTilesArgs {
    playerId: number;
    player_name: string;
    tiles: WickednessTile[];
}

interface NotifRemoveEvolutionsArgs {
    playerId: number;
    player_name: string;
    cards: EvolutionCard[];
    delay: number;
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

interface NotifWickednessArgs extends NotifResolveArgs {
    wickedness: number;
}

interface NotifSetPlayerTokensArgs extends NotifResolveArgs {
    tokens: number;
    deltaTokens?: number;
}

interface NotifSetCardTokensArgs {
    playerId: number;
    card: Card;
    type: 'card' | 'tile';
}

interface NotifSetWickednessTileTokensArgs {
    playerId: number;
    card: WickednessTile;
}

interface NotifSetEvolutionTokensArgs {
    playerId: number;
    card: EvolutionCard;
}

interface NotifToggleRapidHealingArgs {
    playerId: number;
    active: boolean;
    playerEnergy: number;
    isMaxHealth: boolean;
}

interface NotifToggleMothershipSupportUsedArgs {
    playerId: number;
    used: boolean;
}

interface NotifUpdateCancelDamageArgs {
    cancelDamageArgs: EnteringCancelDamageArgs;
    diceValues?: Die[];
}

interface NotifChangeDieArgs {
    playerId: number;
    canHealWithDice: boolean;
    dieId: number;
    toValue: number;
    roll?: boolean;
    psychicProbeRollDieArgs?: EnteringPsychicProbeRollDieArgs;
}

interface NotifChangeDiceArgs {
    playerId: number;
    canHealWithDice: boolean;
    dieIdsToValues: { [dieId: number]: number };
}

interface NotifUpdateLeaveTokyoUnderArgs {
    under: number;
}

interface NotifUpdateStayTokyoOverArgs {
    over: number;
}

interface NotifUpdateAskPlayEvolutionArgs {
    value: number;
}

interface NotifChangeTokyoTowerOwnerArgs {
    playerId: number;
    level: number;
}

interface NotifSetPlayerBerserkArgs {
    playerId: number;
    berserk: boolean;
}

interface NotifChangeFormArgs {
    playerId: number;
    card: Card;
    energy: number;    
}

interface NotifCultistArgs {
    playerId: number;
    cultists: number;
    isMaxHealth: boolean;  
}

interface NotifChangeCurseCardArgs {
    card: Card;
}

interface NotifTakeWickednessTileArgs {
    playerId: number;
    player_name: string;
    tile: WickednessTile;
    level: number;
}

interface NotifChangeGoldenScarabOwnerArgs {
    playerId: number;
    player_name: string;
    previousOwner: number;
}

interface NotifDiscardedDieArgs {
    die: Die;
} 

interface NotifChangeGoldenScarabOwnerArgs {
    playerId: number;
    player_name: string;
    previousOwner: number;
}

interface NotifExchangeCardArgs {
    playerId: number;
    previousOwner: number;
    unstableDnaCard: Card;
    exchangedCard: Card;
}

interface NotifAddEvolutionCardInHandArgs {
    playerId: number;
    card?: EvolutionCard;
}

interface NotifPlayEvolutionArgs {
    playerId: number;
    player_name: string;
    card: EvolutionCard;
    fromPlayerId?: number;
}

interface NotifAddSuperiorAlienTechnologyTokenArgs {
    playerId: number;
    card: Card;
}

interface NotifGiveTargetArgs {
    playerId: number;
    previousOwner: number;
}

interface NotifOwnedEvolutionsArgs {
    playerId: number;
    evolutions: EvolutionCard[];
}

interface NotifSuperiorAlienTechnologyRolledDieArgs {
    dieValue: number;
    card: Card;
}