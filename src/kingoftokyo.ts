declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;
declare const playSound;

declare const board: HTMLDivElement;

const ANIMATION_MS = 1500;
const PUNCH_SOUND_DURATION = 250;

class KingOfTokyo implements KingOfTokyoGame {
    private gamedatas: KingOfTokyoGamedatas;
    private healthCounters: Counter[] = [];
    private energyCounters: Counter[] = [];
    private diceManager: DiceManager;
    private visibleCards: Stock;
    private pickCard: Stock;
    private playerTables: PlayerTable[] = [];
    private tableManager: TableManager;
    public cards: Cards;
    private rapidHealingSyncHearts: number;
        
    public SHINK_RAY_TOKEN_TOOLTIP: string;
    public POISON_TOKEN_TOOLTIP: string;

    constructor() {
    }
    
    /*
        setup:

        This method must set up the game user interface according to current game situation specified
        in parameters.

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)

        "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
    */

    public setup(gamedatas: KingOfTokyoGamedatas) {
        const players = Object.values(gamedatas.players);
        // ignore loading of some pictures
        [1,2,3,4,5,6].filter(i => !players.some(player => Number(player.monster) === i)).forEach(i => {
            (this as any).dontPreloadImage(`monster-board-${i}.png`);
            (this as any).dontPreloadImage(`monster-figure-${i}.png`);
        });
        (this as any).dontPreloadImage(`tokyo-2pvariant.jpg`);
        if (!gamedatas.halloweenExpansion) {
            (this as any).dontPreloadImage(`costume-cards.jpg`);
        }

        log( "Starting game setup" );
        
        this.gamedatas = gamedatas;

        log('gamedatas', gamedatas);

        if (gamedatas.twoPlayersVariant) {
            dojo.addClass('board', 'twoPlayersVariant');
            
            // 2-players variant notice
            if (Object.keys(gamedatas.players).length == 2 && (this as any).prefs[203]?.value == 1) {
                dojo.place(`
                    <div id="board-corner-highlight"></div>
                    <div id="twoPlayersVariant-message">
                        ${_("You are playing the 2-players variant.")}<br>
                        ${_("When entering or starting a turn on Tokyo, you gain 1 energy instead of points")}.<br>
                        ${_("You can check if variant is activated in the bottom left corner of the table.")}<br>
                        <div style="text-align: center"><a id="hide-twoPlayersVariant-message">${_("Dismiss")}</a></div>
                    </div>
                `, 'board');

                document.getElementById('hide-twoPlayersVariant-message').addEventListener('click', () => {
                    const select = document.getElementById('preference_control_203') as HTMLSelectElement;
                    select.value = '2';
                    
                    var event = new Event('change');
                    select.dispatchEvent(event);
                });
            }
        }

        this.cards = new Cards(this);
        this.SHINK_RAY_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Shrink ray tokens (given by ${card_name}). Reduce dice count by one per token. Use you [diceHeart] to remove them.")), {'card_name': this.cards.getCardName(40, 'text-only')});
        this.POISON_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Poison tokens (given by ${card_name}). Make you lose one [heart] per token at the end of your turn. Use you [diceHeart] to remove them.")), {'card_name': this.cards.getCardName(35, 'text-only')});
    
        this.createPlayerPanels(gamedatas); 
        this.diceManager = new DiceManager(this, gamedatas.dice);  
        this.createVisibleCards(gamedatas.visibleCards);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(() => this.playerTables.forEach(playerTable => playerTable.initPlacement()), 200);
        this.setMimicToken(gamedatas.mimickedCard);

        const playerId = this.getPlayerId();
        const currentPlayer = players.find(player => Number(player.id) === playerId);

        if (currentPlayer?.rapidHealing) {
            this.addRapidHealingButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
        }        
        if (currentPlayer?.location > 0) {
            this.addAutoLeaveUnderButton();
        }

        this.setupNotifications();
        this.setupPreferences();

        document.getElementById('zoom-out').addEventListener('click', () => this.tableManager?.zoomOut());
        document.getElementById('zoom-in').addEventListener('click', () => this.tableManager?.zoomIn());

        /*document.getElementById('test').addEventListener('click', () => this.notif_resolveSmashDice({
            args: {
                number: 3,
                smashedPlayersIds: [2343492, 2343493]
            }
        } as any));
        document.getElementById('test1').addEventListener('click', () => this.notif_playerEntersTokyo({
            args: {
                playerId: 2343492,
                location: 1
            }
        } as any));
        document.getElementById('test2').addEventListener('click', () => this.notif_leaveTokyo({
            args: {
                playerId: 2343492,
            }
        } as any));*/

        log( "Ending game setup" );
    }

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    public onEnteringState(stateName: string, args: any) {
        log('Entering state: ' + stateName, args.args);
        this.showActivePlayer(Number(args.active_player));

        switch (stateName) {
            case 'pickMonster':
                dojo.addClass('kot-table', 'pickMonster');
                this.onEnteringPickMonster(args.args);
                break;
            case 'chooseInitialCard':
                this.replaceMonsterChoiceByTable();
                this.onEnteringChooseInitialCard(args.args);
                break;
            case 'startGame':
                this.replaceMonsterChoiceByTable();
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringChooseMimickedCard(args.args);
                break;
            case 'throwDice':
                this.replaceMonsterChoiceByTable();
                this.setDiceSelectorVisibility(true);
                this.onEnteringThrowDice(args.args);
                break;
            case 'changeDie': 
                this.setDiceSelectorVisibility(true);
                this.onEnteringChangeDie(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'resolveDice': 
                this.setDiceSelectorVisibility(true);
                this.diceManager.hideLock();
                break;
            case 'resolveHeartDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveHeartDice(args.args, (this as any).isCurrentPlayerActive());
                break;

            case 'stealCostumeCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringStealCostumeCard(args.args, (this as any).isCurrentPlayerActive());
                break;
            
            case 'buyCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringBuyCard(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'sellCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringSellCard();
                break;

            case 'endTurn':
                this.setDiceSelectorVisibility(false);
                this.onEnteringEndTurn();
                break;
        }
    }

    private showActivePlayer(playerId: number) {
        this.playerTables.forEach(playerTable => playerTable.setActivePlayer(playerId == playerTable.playerId));
    }
    
    private setGamestateDescription(property: string = '') {
        const originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        this.gamedatas.gamestate.description = `${originalState['description' + property]}`; 
        this.gamedatas.gamestate.descriptionmyturn = `${originalState['descriptionmyturn' + property]}`;
        (this as any).updatePageTitle();
    }
    
    private removeGamestateDescription() {
        this.gamedatas.gamestate.description = ''; 
        this.gamedatas.gamestate.descriptionmyturn = ''; 
        (this as any).updatePageTitle();        
    }
    
    private onEnteringPickMonster(args: EnteringPickMonsterArgs) {
        // TODO clean only needed
        document.getElementById('monster-pick').innerHTML = '';
        args.availableMonsters.forEach(monster => {
            dojo.place(`
            <div id="pick-monster-figure-${monster}" class="monster-figure monster${monster}"></div>
            `, `monster-pick`);

            document.getElementById(`pick-monster-figure-${monster}`).addEventListener('click', () => {
                this.pickMonster(monster);
            })
        });

        const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();
        dojo.toggleClass('monster-pick', 'selectable', isCurrentPlayerActive);
    }

    private onEnteringChooseInitialCard(args: EnteringChooseInitialCardArgs) {
        //this.visibleCards.removeAllTo('deck');
        this.cards.addCardsToStock(this.visibleCards, args.cards, 'deck');

        if ((this as any).isCurrentPlayerActive()) {
            this.visibleCards.setSelectionMode(1);
        }
    }

    private onEnteringThrowDice(args: EnteringThrowDiceArgs) {
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? `last` : '');

        this.diceManager.showLock();

        const dice = args.dice;
        const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();

        this.diceManager.setDiceForThrowDice(dice, args.inTokyo, isCurrentPlayerActive);
        
        if (isCurrentPlayerActive) {
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', dojo.string.substitute(_("Reroll dice (${number} roll(s) remaining)"), { 'number': args.maxThrowNumber-args.throwNumber }), () => this.onRethrow(), !args.dice.some(dice => !dice.locked));

                (this as any).addTooltip(
                    'rethrow_button', 
                    _("Click on dice you want to keep to lock them, then click this button to reroll the others"),
                    `${_("Ctrl+click to move all dice with same value")}<br>
                    ${_("Alt+click to move all dice but clicked die")}`);
            }

            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3(), !args.rethrow3.hasDice3);
            }

            if (args.energyDrink?.hasCard && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + formatTextIcons(` ( 1[Energy])`), () => this.buyEnergyDrink());
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }

            if (args.hasSmokeCloud && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_smoke_cloud_button', _("Get extra die Roll") + ` (<span class="smoke-cloud token"></span>)`, () => this.useSmokeCloud());
            }
        }

        if (args.throwNumber === args.maxThrowNumber && !args.hasSmokeCloud && !args.energyDrink?.hasCard) {
            this.diceManager.disableDiceAction();
        }
    }

    private onEnteringChangeDie(args: EnteringChangeDieArgs, isCurrentPlayerActive: boolean) {
        if (args.dice?.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args, args.inTokyo, isCurrentPlayerActive);
        }

        if (isCurrentPlayerActive && args.dice && args.rethrow3?.hasCard) {
            if (document.getElementById('rethrow3changeDie_button')) {
                dojo.toggleClass('rethrow3changeDie_button', 'disabled', !args.rethrow3.hasDice3);
            } else {
                this.createButton('dice-actions', 'rethrow3changeDie_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3changeDie(), !args.rethrow3.hasDice3);
            }
        }
    }

    private onEnteringPsychicProbeRollDie(args: EnteringPsychicProbeRollDieArgs, isCurrentPlayerActive: boolean) {
        this.diceManager.setDiceForPsychicProbe(args.dice, args.inTokyo, isCurrentPlayerActive && args.canRoll);

        if (args.dice && args.rethrow3?.hasCard) {
            if (document.getElementById('rethrow3psychicProbe_button')) {
                dojo.toggleClass('rethrow3psychicProbe_button', 'disabled', !args.rethrow3.hasDice3);
            } else {
                this.createButton('dice-actions', 'rethrow3psychicProbe_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3psychicProbe(), !args.rethrow3.hasDice3);
            }
        }
    }

    private onEnteringResolveHeartDice(args: EnteringResolveHeartDiceArgs, isCurrentPlayerActive: boolean) {
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.inTokyo);

            if (isCurrentPlayerActive) {
                dojo.place(`<div id="heart-action-selector" class="whiteblock"></div>`, 'rolled-dice-and-rapid-actions', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    }

    private onEnteringCancelDamage(args: EnteringCancelDamageArgs, isCurrentPlayerActive: boolean) {
        if (args.dice) {
            this.diceManager.showCamouflageRoll(args.dice);
        }
        
        if (isCurrentPlayerActive) {
            if (args.dice && args.rethrow3?.hasCard) {
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.toggleClass('rethrow3camouflage_button', 'disabled', !args.rethrow3.hasDice3);
                } else {
                    this.createButton('dice-actions', 'rethrow3camouflage_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3camouflage(), !args.rethrow3.hasDice3);
                }
            }

            if (args.canThrowDices && !document.getElementById('throwCamouflageDice_button')) {
                (this as any).addActionButton('throwCamouflageDice_button', _("Throw dice"), 'throwCamouflageDice');
            } else if (!args.canThrowDices && document.getElementById('throwCamouflageDice_button')) {
                dojo.destroy('throwCamouflageDice_button');
            }

            if (args.canUseWings && !document.getElementById('useWings_button')) {
                (this as any).addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + " ( 2[Energy] )", { 'card_name': this.cards.getCardName(48, 'text-only')})), 'useWings');
                if (args.playerEnergy < 2) {
                    dojo.addClass('useWings_button', 'disabled');
                }
            }
            if (!args.canThrowDices && !document.getElementById('skipWings_button')) {
                (this as any).addActionButton('skipWings_button', args.canUseWings ? dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cards.getCardName(48, 'text-only')}) : _("Skip"), 'skipWings');
            }

            if (args.rapidHealingHearts && !document.getElementById('rapidHealingSync_button')) {
                this.rapidHealingSyncHearts = args.rapidHealingHearts;
                (this as any).addActionButton('rapidHealingSync_button', dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (${2*args.rapidHealingHearts}[Energy])`), { 'card_name': this.cards.getCardName(37, 'text-only'), 'hearts': args.rapidHealingHearts }), 'useRapidHealingSync');
            }
        }
    }

    private onEnteringStealCostumeCard(args: EnteringStealCostumeCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`).classList.add('disabled'));
        }
    }

    private onEnteringBuyCard(args: EnteringBuyCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            this.visibleCards.setSelectionMode(1);

            if (args.canBuyFromPlayers) {
                this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            }

            if (args._private?.pickCards?.length) {
                this.showPickStock(args._private.pickCards);
            }

            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`).classList.add('disabled'));
        }
    }

    private onEnteringChooseMimickedCard(args: EnteringBuyCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`).classList.add('disabled'));
        }
    }

    private onEnteringSellCard() {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
        }
    }

    private onEnteringEndTurn() {
    }

    public onLeavingState(stateName: string) {
        log( 'Leaving state: '+stateName );

        switch (stateName) {
            case 'chooseInitialCard':                
                this.visibleCards.setSelectionMode(0);
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'opportunistChooseMimicCard':
                this.onLeavingChooseMimickedCard();
                break;            
            case 'throwDice':
                document.getElementById('dice-actions').innerHTML = '';
                break;  
            case 'psychicProbeRollDie':
                if (document.getElementById('rethrow3psychicProbe_button')) {
                    dojo.destroy('rethrow3psychicProbe_button');
                }   
                break;
            case 'changeDie':                 
                if (document.getElementById('rethrow3changeDie_button')) {
                    dojo.destroy('rethrow3changeDie_button');
                }
                this.diceManager.removeAllBubbles();
                break; 
            case 'resolveHeartDiceAction':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;            
            case 'leaveTokyo':
                this.removeSkipBuyPhaseToggle();
                break;
            case 'stealCostumeCard':
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
                break;
            case 'sellCard':
                this.onLeavingSellCard();
                break;

            case 'cancelDamage':
                this.diceManager.removeAllDice();
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.destroy('rethrow3camouflage_button');
                }
                break;
        }
    }

    private onLeavingBuyCard() {
        this.visibleCards.setSelectionMode(0);
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(0));            
        this.hidePickStock();
    }

    private onLeavingChooseMimickedCard() {
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(0));
    }

    private onLeavingSellCard() {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(0));
        }
    }

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    public onUpdateActionButtons(stateName: string, args: any) {

        switch (stateName) {
            case 'psychicProbeRollDie':
                this.setDiceSelectorVisibility(true);
                break;
            case 'cheerleaderSupport':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args, false); // because it's multiplayer, enter action must be set here
                break;
            case 'leaveTokyo':
                this.setDiceSelectorVisibility(false);

                const argsLeaveTokyo = args as EnteringLeaveTokyoArgs;
                if (argsLeaveTokyo._private) {
                    this.addSkipBuyPhaseToggle(argsLeaveTokyo._private.skipBuyPhase);
                }
                break;
            case 'opportunistBuyCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'opportunistChooseMimicCard':
                this.setDiceSelectorVisibility(false);
                break;            
            case 'cancelDamage':
                const argsCancelDamage = args as EnteringCancelDamageArgs;
                this.setDiceSelectorVisibility(argsCancelDamage.canThrowDices || !!argsCancelDamage.dice);
                break;
        }

        if((this as any).isCurrentPlayerActive()) {
            switch (stateName) {
                case 'changeMimickedCard':
                    (this as any).addActionButton('skipChangeMimickedCard_button', _("Skip"), 'skipChangeMimickedCard');

                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCard_button', 5);
                    }
                    break;
                case 'throwDice':
                    (this as any).addActionButton('resolve_button', _("Resolve dice"), 'goToChangeDie', null, null, 'red');

                    const argsThrowDice = args as EnteringThrowDiceArgs;
                    if (!argsThrowDice.hasActions) {
                        this.startActionTimer('resolve_button', 5);
                    }
                    break;
                case 'changeDie':
                    (this as any).addActionButton('resolve_button', _("Resolve dice"), 'resolveDice', null, null, 'red');
                    break;
                case 'psychicProbeRollDie':
                    (this as any).addActionButton('psychicProbeSkip_button', _("Skip"), 'psychicProbeSkip');
                    this.onEnteringPsychicProbeRollDie(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'cheerleaderSupport':
                    (this as any).addActionButton('support_button', formatTextIcons("Support (add [diceSmash] )") /* TODOTR */, () => this.support());
                    (this as any).addActionButton('dontSupport_button', "Don't support" /* TODOTR */, () => this.dontSupport());
                    this.onEnteringPsychicProbeRollDie(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'leaveTokyo':
                    let label = _("Stay in Tokyo");
                    const argsLeaveTokyo = args as EnteringLeaveTokyoArgs;
                    if (argsLeaveTokyo.jetsPlayers?.includes(this.getPlayerId())) {
                        label += formatTextIcons(` (- ${argsLeaveTokyo.jetsDamage} [heart])`);
                    }
                    (this as any).addActionButton('stayInTokyo_button', label, 'onStayInTokyo');
                    (this as any).addActionButton('leaveTokyo_button', _("Leave Tokyo"), 'onLeaveTokyo');
                    break;

                case 'stealCostumeCard':
                    const argsStealCostumeCard = args as EnteringStealCostumeCardArgs;

                    (this as any).addActionButton('endStealCostume_button', _("Skip"), 'endStealCostume', null, null, 'red');

                    if (!argsStealCostumeCard.canBuyFromPlayers) {
                        this.startActionTimer('endStealCostume_button', 5);
                    }
                    break;
                case 'buyCard':
                    const argsBuyCard = args as EnteringBuyCardArgs;

                    (this as any).addActionButton('renew_button', _("Renew cards") + formatTextIcons(` ( 2 [Energy])`), 'onRenew');
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    if (argsBuyCard.canSell) {
                        (this as any).addActionButton('goToSellCard_button', _("End turn and sell cards"), 'goToSellCard');
                    }

                    (this as any).addActionButton('endTurn_button', argsBuyCard.canSell ? _("End turn without selling") : _("End turn"), 'onEndTurn', null, null, 'red');

                    if (!argsBuyCard.canBuyOrNenew && !argsBuyCard.canSell) {
                        this.startActionTimer('endTurn_button', 5);
                    }
                    break;
                case 'opportunistBuyCard':
                    (this as any).addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');

                    if (!args.canBuy) {
                        this.startActionTimer('opportunistSkip_button', 5);
                    }

                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                    break;
                case 'sellCard':
                    (this as any).addActionButton('endTurnSellCard_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;
                
                case 'cancelDamage':
                    this.onEnteringCancelDamage(args, true); // because it's multiplayer, enter action must be set here
                    break;
            }

        }
    } 
    

    ///////////////////////////////////////////////////
    //// Utility methods


    ///////////////////////////////////////////////////

    public getPlayerId(): number {
        return Number((this as any).player_id);
    }

    public isDefaultFont(): boolean {
        return Number((this as any).prefs[201].value) == 1;
    }

    public createButton(destinationId: string, id: string, text: string, callback: Function, disabled: boolean = false) {
        const html = `<button class="action-button bgabutton bgabutton_blue" id="${id}">
            ${text}
        </button>`;
        dojo.place(html, destinationId);
        if (disabled) {
            dojo.addClass(id, 'disabled');
        }
        document.getElementById(id).addEventListener('click', () => callback());
    }

    private getOrderedPlayers(): KingOfTokyoPlayer[] {
        return Object.values(this.gamedatas.players).sort((a,b) => Number(a.player_no) - Number(b.player_no));
    }

    private createPlayerPanels(gamedatas: KingOfTokyoGamedatas) {

        Object.values(gamedatas.players).forEach(player => {
            const playerId = Number(player.id);  

            const eliminated = Number(player.eliminated) > 0 || player.playerDead > 0;

            // health & energy counters
            dojo.place(`<div class="counters">
                <div id="health-counter-wrapper-${player.id}" class="health-counter">
                    <div class="icon health"></div> 
                    <span id="health-counter-${player.id}"></span>
                </div>
                <div id="energy-counter-wrapper-${player.id}" class="energy-counter">
                    <div class="icon energy"></div> 
                    <span id="energy-counter-${player.id}"></span>
                </div>
            </div>`, `player_board_${player.id}`);

            const healthCounter = new ebg.counter();
            healthCounter.create(`health-counter-${player.id}`);
            healthCounter.setValue(player.health);
            this.healthCounters[playerId] = healthCounter;

            const energyCounter = new ebg.counter();
            energyCounter.create(`energy-counter-${player.id}`);
            energyCounter.setValue(player.energy);
            this.energyCounters[playerId] = energyCounter;

            dojo.place(`<div class="player-tokens">
                <div id="player-board-shrink-ray-tokens-${player.id}" class="player-token shrink-ray-tokens"></div>
                <div id="player-board-poison-tokens-${player.id}" class="player-token poison-tokens"></div>
            </div>`, `player_board_${player.id}`);

            if (!eliminated) {
                this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
                this.setPoisonTokens(playerId, player.poisonTokens);
            }

            dojo.place(`<div id="player-board-monster-figure-${player.id}" class="monster-figure monster${player.monster}"><div class="kot-token"></div></div>`, `player_board_${player.id}`);

            if (player.location > 0) {
                dojo.addClass(`overall_player_board_${playerId}`, 'intokyo');
            }
            if (eliminated) {
                setTimeout(() => this.eliminatePlayer(playerId), 200);
            }
        });

        (this as any).addTooltipHtmlToClass('shrink-ray-tokens', this.SHINK_RAY_TOKEN_TOOLTIP);
        (this as any).addTooltipHtmlToClass('poison-tokens', this.POISON_TOKEN_TOOLTIP);
    }
    
    private createPlayerTables(gamedatas: KingOfTokyoGamedatas) {
        this.playerTables = this.getOrderedPlayers().map(player => new PlayerTable(this, player, gamedatas.playersCards[Number(player.id)]));
    }

    private getPlayerTable(playerId: number): PlayerTable {
        return this.playerTables.find(playerTable => playerTable.playerId === Number(playerId));
    }

    private setDiceSelectorVisibility(visible: boolean) {
        const div = document.getElementById('rolled-dice');
        div.style.display = visible ? 'flex' : 'none';
    }

    public getZoom() {
        return this.tableManager.zoom;
    }

    private replaceMonsterChoiceByTable() {
        if (document.getElementById('monster-pick')) {
            (this as any).fadeOutAndDestroy('monster-pick');
        }
        if (dojo.hasClass('kot-table', 'pickMonster')) {
            dojo.removeClass('kot-table', 'pickMonster');
            this.tableManager.setAutoZoomAndPlacePlayerTables();
            this.visibleCards.updateDisplay();
        }
    }

    private createVisibleCards(visibleCards: Card[]) {
        this.visibleCards = new ebg.stock() as Stock;
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection';
        this.visibleCards.create(this, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = (card_div, card_type_id) => this.cards.setupNewCard(card_div, card_type_id); 
        this.visibleCards.image_items_per_row = 10;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, (_, item_id: string) => this.onVisibleCardClick(this.visibleCards, item_id));

        this.cards.setupCards([this.visibleCards]);
        this.setVisibleCards(visibleCards);
    }

    public onVisibleCardClick(stock: Stock, cardId: string, from: number = 0) { // from : player id
        if (!cardId) {
            return;
        }

        if (dojo.hasClass(`${stock.container_div.id}_item_${cardId}`, 'disabled')) {
            stock.unselectItem(cardId);
            return;
        }

        if (this.gamedatas.gamestate.name === 'chooseInitialCard') {
            this.chooseInitialCard(cardId);
        } else if (this.gamedatas.gamestate.name === 'stealCostumeCard') {
            this.stealCostumeCard(cardId);
        } else if (this.gamedatas.gamestate.name === 'sellCard') {
            this.sellCard(cardId);
        } else if (this.gamedatas.gamestate.name === 'chooseMimickedCard' || this.gamedatas.gamestate.name === 'opportunistChooseMimicCard') {
            this.chooseMimickedCard(cardId);
        } else if (this.gamedatas.gamestate.name === 'changeMimickedCard') {
            this.changeMimickedCard(cardId);
        } else {
            const removeFromPickIds = this.pickCard?.items.map(item => Number(item.id));
            removeFromPickIds?.forEach(id => {
                if (id !== Number(cardId)) {
                    this.pickCard.removeFromStockById(''+id);
                }
            });
            this.buyCard(cardId, from);
        }
    }

    private addRapidHealingButton(userEnergy: number, isMaxHealth: boolean) {
        if (!document.getElementById('rapidHealingButton')) {
            this.createButton(
                'rapid-actions-wrapper', 
                'rapidHealingButton', 
                formatTextIcons(`${dojo.string.substitute(_('Gain ${hearts}[Heart]'), { hearts: 1 })} (2[Energy])`), 
                () => this.useRapidHealing(), 
                userEnergy < 2 || isMaxHealth
            );
        }
    }

    private removeRapidHealingButton() {
        if (document.getElementById('rapidHealingButton')) {
            dojo.destroy('rapidHealingButton');
        }
    }

    private checkRapidHealingButtonState() {
        if (document.getElementById('rapidHealingButton')) {
            const playerId = this.getPlayerId();
            const userEnergy = this.energyCounters[playerId].getValue();
            const health = this.healthCounters[playerId].getValue();
            const maxHealth = this.gamedatas.players[playerId].maxHealth;
            dojo.toggleClass('rapidHealingButton', 'disabled', userEnergy < 2 || health >= maxHealth);
        }
    }

    private addSkipBuyPhaseToggle(active: boolean) {
        if (!document.getElementById('skipBuyPhaseWrapper')) {
            dojo.place(`<div id="skipBuyPhaseWrapper">
                <label class="switch">
                    <input id="skipBuyPhaseCheckbox" type="checkbox" ${active ? 'checked' : ''}>
                    <span class="slider round"></span>
                </label>
                <label for="skipBuyPhaseCheckbox" class="text-label">${_("Skip buy phase")}</label>
            </div>`, 'rapid-actions-wrapper');

            document.getElementById('skipBuyPhaseCheckbox').addEventListener('change', (e: any) => this.setSkipBuyPhase(e.target.checked));
        }
    }

    private removeSkipBuyPhaseToggle() {
        if (document.getElementById('skipBuyPhaseWrapper')) {
            dojo.destroy('skipBuyPhaseWrapper');
        }
    }

    private addAutoLeaveUnderButton() {
        if (!document.getElementById('autoLeaveUnderButton')) {
            this.createButton(
                'rapid-actions-wrapper', 
                'autoLeaveUnderButton', 
                _("Leave Tokyo") + ' &#x25BE;', 
                () => this.toggleAutoLeaveUnderPopin(), 
            );
        }
    }

    private removeAutoLeaveUnderButton() {
        if (document.getElementById('autoLeaveUnderButton')) {
            dojo.destroy('autoLeaveUnderButton');
        }
    }

    private toggleAutoLeaveUnderPopin() {
        const bubble = document.getElementById(`discussion_bubble_autoLeaveUnder`);
        if (bubble?.dataset.visible === 'true') {
            this.closeAutoLeaveUnderPopin();
        } else {
            this.openAutoLeaveUnderPopin();
        }
    }

    private openAutoLeaveUnderPopin() {
        const popinId = `discussion_bubble_autoLeaveUnder`;
        let bubble = document.getElementById(popinId);
        if (!bubble) { 
            const maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
            let html = `<div id="${popinId}" class="discussion_bubble autoLeaveUnderBubble">
                <div>${_("Automatically leave tokyo when life goes down to, or under")}</div>
                <div id="${popinId}-buttons" class="button-grid">`;
            for (let i = maxHealth; i>0; i--) {
                html += `<button class="action-button bgabutton ${this.gamedatas.leaveTokyoUnder === i || (i == 1 && !this.gamedatas.leaveTokyoUnder) ? 'bgabutton_blue' : 'bgabutton_gray'} autoLeaveButton ${i == 1 ? 'disable' : ''}" id="${popinId}_set${i}">
                    ${i == 1 ? _('Disabled') : i-1}
                </button>`;
            }
            html += `</div>
            <div>${_("If your life is over it, or if disabled, you'll be asked if you want to stay or leave")}</div>
            <hr>
            <div>${_("Automatically stay in tokyo when life is at least")}</div>
                <div id="${popinId}-stay-buttons" class="button-grid">`;
            for (let i = maxHealth + 1; i>2; i--) {
                html += `<button class="action-button bgabutton ${this.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray'} autoStayButton ${this.gamedatas.leaveTokyoUnder > 0 && i <= this.gamedatas.leaveTokyoUnder ? 'disabled' : ''}" id="${popinId}_setStay${i}">${i-1}</button>`;
            }
            html += `<button class="action-button bgabutton ${!this.gamedatas.stayTokyoOver ? 'bgabutton_blue' : 'bgabutton_gray'} autoStayButton disable" id="${popinId}_setStay0">${_('Disabled')}</button>`;
            html += `</div>
            </div>`;
            dojo.place(html, 'autoLeaveUnderButton');
            for (let i = maxHealth; i>0; i--) {
                document.getElementById(`${popinId}_set${i}`).addEventListener('click', () => {
                    this.setLeaveTokyoUnder(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
            for (let i = maxHealth + 1; i>2; i--) {
                document.getElementById(`${popinId}_setStay${i}`).addEventListener('click', () => {
                    this.setStayTokyoOver(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
            document.getElementById(`${popinId}_setStay0`).addEventListener('click', () => {
                this.setStayTokyoOver(0);
                setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
            });

            bubble = document.getElementById(popinId);
        }

        bubble.style.display = 'block';
        bubble.dataset.visible = 'true';
    }

    private updateAutoLeavePopinButtons() {
        const popinId = `discussion_bubble_autoLeaveUnder`;
        const maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
        for (let i = maxHealth + 1; i<=14; i++) {
            if (document.getElementById(`${popinId}_set${i}`)) {
                dojo.destroy(`${popinId}_set${i}`);
            }
            if (document.getElementById(`${popinId}_setStay${i}`)) {
                dojo.destroy(`${popinId}_setStay${i}`);
            }
        }

        for (let i = 11; i<=maxHealth; i++) {
            if (!document.getElementById(`${popinId}_set${i}`)) {
                dojo.place(`<button class="action-button bgabutton ${this.gamedatas.leaveTokyoUnder === i ? 'bgabutton_blue' : 'bgabutton_gray'} autoLeaveButton" id="${popinId}_set${i}">
                    ${i-1}
                </button>`, `${popinId}-buttons`, 'first');
                document.getElementById(`${popinId}_set${i}`).addEventListener('click', () => {
                    this.setLeaveTokyoUnder(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
        }
        for (let i = 12; i<=maxHealth+1; i++) {
            if (!document.getElementById(`${popinId}_setStay${i}`)) {
                dojo.place(`<button class="action-button bgabutton ${this.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray'} autoStayButton ${this.gamedatas.leaveTokyoUnder > 0 && i <= this.gamedatas.leaveTokyoUnder ? 'disabled' : ''}" id="${popinId}_setStay${i}">
                    ${i-1}
                </button>`, `${popinId}-stay-buttons`, 'first');
                document.getElementById(`${popinId}_setStay${i}`).addEventListener('click', () => {
                    this.setStayTokyoOver(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
        }
    }

    private closeAutoLeaveUnderPopin() {
        const bubble = document.getElementById(`discussion_bubble_autoLeaveUnder`);
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    }

    private setMimicToken(card: Card) {
        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.items.some(item => Number(item.id) == card.id)) {
                this.cards.placeMimicOnCard(playerTable.cards, card);
            }
        });

        this.setMimicTooltip(card);
    }

    private removeMimicToken(card: Card) {
        this.setMimicTooltip(null);

        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.items.some(item => Number(item.id) == card.id)) {
                this.cards.removeMimicOnCard(playerTable.cards, card);
            }
        });
    }

    private setMimicTooltip(mimickedCard: Card) {
        this.playerTables.forEach(playerTable => {
            const mimicCardItem = playerTable.cards.items.find(item => Number(item.type) == 27);
            if (mimicCardItem) {
                this.cards.changeMimicTooltip(`cards-${playerTable.playerId}_item_${mimicCardItem.id}`, mimickedCard);
            }
        });
    }

    public pickMonster(monster: number) {
        if(!(this as any).checkAction('pickMonster')) {
            return;
        }

        this.takeAction('pickMonster', {
            monster
        });
    }

    public chooseInitialCard(id: number | string) {
        if(!(this as any).checkAction('chooseInitialCard')) {
            return;
        }

        this.takeAction('chooseInitialCard', {
            id
        });
    }

    public onRethrow() {
        this.rethrowDice(this.diceManager.destroyFreeDice());      
    }

    public rethrowDice(diceIds: number[]) {
        if(!(this as any).checkAction('rethrow')) {
            return;
        }

        this.takeAction('rethrow', {
            diceIds: diceIds.join(',')
        });
    }

    public rethrow3() {
        const lockedDice = this.diceManager.getLockedDice();

        this.takeAction('rethrow3', {
            diceIds: lockedDice.map(die => die.id).join(',')
        });
    }

    public rethrow3camouflage() {
        this.takeAction('rethrow3camouflage');
    }

    public rethrow3psychicProbe() {
        this.takeAction('rethrow3psychicProbe');
    }

    public rethrow3changeDie() {
        this.takeAction('rethrow3changeDie');
    }

    public buyEnergyDrink() {
        const diceIds = this.diceManager.destroyFreeDice();

        this.takeAction('buyEnergyDrink', {
            diceIds: diceIds.join(',')
        });
    }

    public useSmokeCloud() {
        const diceIds = this.diceManager.destroyFreeDice();
        
        this.takeAction('useSmokeCloud', {
            diceIds: diceIds.join(',')
        });
    }

    public useRapidHealing() {
        this.takeAction('useRapidHealing');
    }

    public setSkipBuyPhase(skipBuyPhase: boolean) {
        this.takeAction('setSkipBuyPhase', {
            skipBuyPhase
        });
    }

    public changeDie(id: number, value: number, card: number) {
        if(!(this as any).checkAction('changeDie')) {
            return;
        }

        this.takeAction('changeDie', {
            id,
            value,
            card
        });
    }

    public psychicProbeRollDie(id: number) {
        if(!(this as any).checkAction('psychicProbeRollDie')) {
            return;
        }

        this.takeAction('psychicProbeRollDie', {
            id
        });
    }

    public goToChangeDie() {
        if(!(this as any).checkAction('goToChangeDie', true)) {
            return;
        }

        this.takeAction('goToChangeDie');
    }

    public resolveDice() {
        if(!(this as any).checkAction('resolve')) {
            return;
        }

        this.takeAction('resolve');
    }

    public support() {
        if(!(this as any).checkAction('support')) {
            return;
        }

        this.takeAction('support');
    }

    public dontSupport() {
        if(!(this as any).checkAction('dontSupport')) {
            return;
        }

        this.takeAction('dontSupport');
    }

    public applyHeartActions(selections: HeartActionSelection[]) {
        if(!(this as any).checkAction('applyHeartDieChoices')) {
            return;
        }

        const base64 = btoa(JSON.stringify(selections));

        this.takeAction('applyHeartDieChoices', {
            selections: base64
        });

    }

    public onStayInTokyo() {
        if(!(this as any).checkAction('stay')) {
            return;
        }

        this.takeAction('stay');
    }
    public onLeaveTokyo() {
        if(!(this as any).checkAction('leave')) {
            return;
        }

        this.takeAction('leave');
    }

    public stealCostumeCard(id: number | string) {
        if(!(this as any).checkAction('stealCostumeCard')) {
            return;
        }

        this.takeAction('stealCostumeCard', {
            id
        });
    }

    public buyCard(id: number | string, from: number) {
        if(!(this as any).checkAction('buyCard')) {
            return;
        }

        this.takeAction('buyCard', {
            id,
            from
        });
    }

    public chooseMimickedCard(id: number | string) {
        if(!(this as any).checkAction('chooseMimickedCard')) {
            return;
        }

        this.takeAction('chooseMimickedCard', {
            id
        });
    }

    public changeMimickedCard(id: number | string) {
        if(!(this as any).checkAction('changeMimickedCard')) {
            return;
        }

        this.takeAction('changeMimickedCard', {
            id
        });
    }

    public sellCard(id: number | string) {
        if(!(this as any).checkAction('sellCard')) {
            return;
        }

        this.takeAction('sellCard', {
            id
        });
    }

    public onRenew() {
        if(!(this as any).checkAction('renew')) {
            return;
        }

        this.takeAction('renew');
    }

    public goToSellCard() {
        if(!(this as any).checkAction('goToSellCard', true)) {
            return;
        }

        this.takeAction('goToSellCard');
    }

    public opportunistSkip() {
        if(!(this as any).checkAction('opportunistSkip', true)) {
            return;
        }

        this.takeAction('opportunistSkip');
    }

    public psychicProbeSkip() {
        if(!(this as any).checkAction('psychicProbeSkip')) {
            return;
        }

        this.takeAction('psychicProbeSkip');
    }

    public skipChangeMimickedCard() {
        if(!(this as any).checkAction('skipChangeMimickedCard', true)) {
            return;
        }

        this.takeAction('skipChangeMimickedCard');
    }

    public endStealCostume() {
        if(!(this as any).checkAction('endStealCostume')) {
            return;
        }

        this.takeAction('endStealCostume');
    }

    public onEndTurn() {
        if(!(this as any).checkAction('endTurn')) {
            return;
        }

        this.takeAction('endTurn');
    }

    public throwCamouflageDice() {
        if(!(this as any).checkAction('throwCamouflageDice')) {
            return;
        }

        this.takeAction('throwCamouflageDice');
    }

    public useWings() {
        if(!(this as any).checkAction('useWings')) {
            return;
        }

        this.takeAction('useWings');
    }

    public skipWings() {
        if(!(this as any).checkAction('skipWings')) {
            return;
        }

        this.takeAction('skipWings');
    }

    public useRapidHealingSync() {
        if(!(this as any).checkAction('useRapidHealingSync')) {
            return;
        }

        this.takeAction('useRapidHealingSync');
    }

    public setLeaveTokyoUnder(under: number) {
        this.takeAction('setLeaveTokyoUnder', {
            under
        });
    }

    public setStayTokyoOver(over: number) {
        this.takeAction('setStayTokyoOver', {
            over
        });
    }

    public takeAction(action: string, data?: any) {
        data = data || {};
        data.lock = true;
        (this as any).ajaxcall(`/kingoftokyo/kingoftokyo/${action}.html`, data, this, () => {});
    }
    
    private showPickStock(cards: Card[]) {
        if (!this.pickCard) { 
            dojo.place('<div id="pick-stock"></div>', 'deck-wrapper');

            this.pickCard = new ebg.stock() as Stock;
            this.pickCard.setSelectionAppearance('class');
            this.pickCard.selectionClass = 'no-visible-selection';
            this.pickCard.create(this, $('pick-stock'), CARD_WIDTH, CARD_HEIGHT);
            this.pickCard.setSelectionMode(1);
            this.pickCard.onItemCreate = (card_div, card_type_id) => this.cards.setupNewCard(card_div, card_type_id); 
            this.pickCard.image_items_per_row = 10;
            this.pickCard.centerItems = true;
            dojo.connect(this.pickCard, 'onChangeSelection', this, (_, item_id: string) => this.onVisibleCardClick(this.pickCard, item_id));
        } else {
            document.getElementById('pick-stock').style.display = 'block';
        }

        this.cards.setupCards([this.pickCard]);
        this.cards.addCardsToStock(this.pickCard, cards);
    }

    private hidePickStock() {
        const div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
        }
    }

    private setupPreferences() {
        // Extract the ID and value from the UI control
        const onchange = (e) => {
          var match = e.target.id.match(/^preference_control_(\d+)$/);
          if (!match) {
            return;
          }
          var prefId = +match[1];
          var prefValue = +e.target.value;
          (this as any).prefs[prefId].value = prefValue;
          this.onPreferenceChange(prefId, prefValue);
        }
        
        // Call onPreferenceChange() when any value changes
        dojo.query(".preference_control").connect("onchange", onchange);
        
        // Call onPreferenceChange() now
        dojo.forEach(
          dojo.query("#ingame_menu_content .preference_control"),
          el => onchange({ target: el })
        );
    }
      
    private onPreferenceChange(prefId: number, prefValue: number) {
        switch (prefId) {
            // KEEP
            case 201: 
                this.playerTables.forEach(playerTable => playerTable.setFont(prefValue));
                break;
            case 203: 
                if (prefValue == 2) {
                    dojo.destroy('board-corner-highlight');
                    dojo.destroy('twoPlayersVariant-message');
                }
                break;
        }
    }

    private setVisibleCards(cards: Card[]) {
        const newWeights = {};
        cards.forEach(card => newWeights[card.type] = card.location_arg);
        this.visibleCards.changeItemsWeight(newWeights);

        this.cards.addCardsToStock(this.visibleCards, cards, 'deck');
    }

    private startActionTimer(buttonId: string, time: number) {
        if ((this as any).prefs[202]?.value === 2) {
            return;
        }

        const button = document.getElementById(buttonId);
 
        let actionTimerId = null;
        const _actionTimerLabel = button.innerHTML;
        let _actionTimerSeconds = time;
        const actionTimerFunction = () => {
          const button = document.getElementById(buttonId);
          if (button == null) {
            window.clearInterval(actionTimerId);
          } else if (_actionTimerSeconds-- > 1) {
            button.innerHTML = _actionTimerLabel + ' (' + _actionTimerSeconds + ')';
          } else {
            window.clearInterval(actionTimerId);
            button.click();
          }
        };
        actionTimerFunction();
        actionTimerId = window.setInterval(() => actionTimerFunction(), 1000);
    }

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
        setupNotifications:

        In this method, you associate each of your game notifications with your local method to handle it.

        Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                your pylos.game.php file.

    */
    setupNotifications() {
        //log( 'notifications subscriptions setup' );

        const notifs = [
            ['pickMonster', 500],
            ['setInitialCards', 500],
            ['resolveNumberDice', ANIMATION_MS],
            ['resolveHealthDice', ANIMATION_MS],
            ['resolveHealingRay', ANIMATION_MS],
            ['resolveHealthDiceInTokyo', ANIMATION_MS],
            ['removeShrinkRayToken', ANIMATION_MS],
            ['removePoisonToken', ANIMATION_MS],
            ['resolveEnergyDice', ANIMATION_MS],
            ['resolveSmashDice', ANIMATION_MS],
            ['playerEliminated', ANIMATION_MS],
            ['playerEntersTokyo', ANIMATION_MS],
            ['renewCards', ANIMATION_MS],
            ['buyCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['changeDie', ANIMATION_MS],
            ['rethrow3changeDie', ANIMATION_MS],
            ['resolvePlayerDice', 500],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['maxHealth', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['setCardTokens', 1],
            ['removeCards', 1],
            ['setMimicToken', 1],
            ['removeMimicToken', 1],
            ['toggleRapidHealing', 1],
            ['updateLeaveTokyoUnder', 1],
            ['updateStayTokyoOver', 1],
            ['kotPlayerEliminated', 1],
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });
    }

    notif_pickMonster(notif: Notif<NotifPickMonsterArgs>) {
       const monsterDiv = document.getElementById(`pick-monster-figure-${notif.args.monster}`); 
       const destinationId = `player-board-monster-figure-${notif.args.playerId}`;
       const animation = (this as any).slideToObject(monsterDiv, destinationId);

        dojo.connect(animation, 'onEnd', dojo.hitch(this, () => {
            (this as any).fadeOutAndDestroy(monsterDiv);
            dojo.removeClass(destinationId, 'monster0');
            dojo.addClass(destinationId, `monster${notif.args.monster}`);
        }));
        animation.play();

        this.getPlayerTable(notif.args.playerId).setMonster(notif.args.monster);
    }

    notif_setInitialCards(notif: Notif<NotifSetInitialCardsArgs>) {
        this.cards.addCardsToStock(this.visibleCards, notif.args.cards, 'deck');
    }

    notif_resolveNumberDice(notif: Notif<NotifResolveNumberDiceArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points, ANIMATION_MS);
        this.diceManager.resolveNumberDice(notif.args);
    }

    notif_resolveHealthDice(notif: Notif<NotifResolveHealthDiceArgs>) {
        this.setHealth(notif.args.playerId, notif.args.health, ANIMATION_MS);
        this.diceManager.resolveHealthDice(notif.args.playerId, notif.args.deltaHealth);
    }
    notif_resolveHealthDiceInTokyo(notif: Notif<NotifResolveHealthDiceInTokyoArgs>) {
        this.diceManager.resolveHealthDiceInTokyo();
    }
    notif_resolveHealingRay(notif: Notif<NotifResolveHealingRayArgs>) {
        this.diceManager.resolveHealthDice(notif.args.healedPlayerId, notif.args.healNumber);
    }

    notif_resolveEnergyDice(notif: Notif<NotifResolveEnergyDiceArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy, ANIMATION_MS);
        this.diceManager.resolveEnergyDice(notif.args);
    }

    notif_resolveSmashDice(notif: Notif<NotifResolveSmashDiceArgs>) {
        this.diceManager.resolveSmashDice(notif.args);

        if (notif.args.smashedPlayersIds.length > 0) {
            for (let delayIndex = 0; delayIndex < notif.args.number; delayIndex++) {
                setTimeout(() => playSound('kot-punch'), ANIMATION_MS -(PUNCH_SOUND_DURATION * delayIndex - 1));
            }
        }
    }

    notif_playerEliminated(notif: Notif<NotifPlayerEliminatedArgs>) {
        const playerId = Number(notif.args.who_quits);
        this.setPoints(playerId, 0);
        this.eliminatePlayer(playerId);
    }

    notif_kotPlayerEliminated(notif: Notif<NotifPlayerEliminatedArgs>) {
        this.notif_playerEliminated(notif);
    }

    notif_leaveTokyo(notif: Notif<NotifPlayerLeavesTokyoArgs>) {
        this.getPlayerTable(notif.args.playerId).leaveTokyo();
        dojo.removeClass(`overall_player_board_${notif.args.playerId}`, 'intokyo');
        dojo.removeClass(`monster-board-wrapper-${notif.args.playerId}`, 'intokyo');
        if (notif.args.playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }        
    }

    notif_playerEntersTokyo(notif: Notif<NotifPlayerEntersTokyoArgs>) {
        this.getPlayerTable(notif.args.playerId).enterTokyo(notif.args.location);
        this.setPoints(notif.args.playerId, notif.args.points);
        this.setEnergy(notif.args.playerId, notif.args.energy);
        dojo.addClass(`overall_player_board_${notif.args.playerId}`, 'intokyo');
        dojo.addClass(`monster-board-wrapper-${notif.args.playerId}`, 'intokyo');
        if (notif.args.playerId == this.getPlayerId()) {
            this.addAutoLeaveUnderButton();
        }  
    }

    notif_buyCard(notif: Notif<NotifBuyCardArgs>) {
        const card = notif.args.card;
        this.visibleCards.changeItemsWeight( { [card.type]: card.location_arg } );

        if (notif.args.energy !== undefined) {
            this.setEnergy(notif.args.playerId, notif.args.energy);
        }

        if (notif.args.discardCard) { // initial card
            this.cards.moveToAnotherStock(this.visibleCards, this.getPlayerTable(notif.args.playerId).cards, card);
            this.visibleCards.removeFromStockById(''+notif.args.discardCard.id);
        } else if (notif.args.newCard) {
        const newCard = notif.args.newCard;
            this.cards.moveToAnotherStock(this.visibleCards, this.getPlayerTable(notif.args.playerId).cards, card);
            this.cards.addCardsToStock(this.visibleCards, [newCard], 'deck');
            this.visibleCards.changeItemsWeight( { [newCard.type]: newCard.location_arg } );
        } else if (notif.args.from > 0) {
            this.cards.moveToAnotherStock(this.getPlayerTable(notif.args.from).cards, this.getPlayerTable(notif.args.playerId).cards, card);
        } else { // from Made in a lab Pick
            if (this.pickCard) { // active player
                this.cards.moveToAnotherStock(this.pickCard, this.getPlayerTable(notif.args.playerId).cards, card);
            } else {
                this.cards.addCardsToStock(this.getPlayerTable(notif.args.playerId).cards, [card], 'deck');
            }
        }

        this.tableManager.placePlayerTable(); // adapt to new card
    }

    notif_removeCards(notif: Notif<NotifRemoveCardsArgs>) {
        if (notif.args.delay) {
            notif.args.delay = false;
            setTimeout(() => this.notif_removeCards(notif), ANIMATION_MS);
        } else {
            this.getPlayerTable(notif.args.playerId).removeCards(notif.args.cards);
            this.tableManager.placePlayerTable(); // adapt after removed cards
        }
    }

    notif_setMimicToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.setMimicToken(notif.args.card);
    }

    notif_removeMimicToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.removeMimicToken(notif.args.card);
    }

    notif_renewCards(notif: Notif<NotifRenewCardsArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy);

        this.visibleCards.removeAll();

        this.setVisibleCards(notif.args.cards);
    }

    notif_points(notif: Notif<NotifPointsArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points);
    }

    notif_health(notif: Notif<NotifHealthArgs>) {
        this.setHealth(notif.args.playerId, notif.args.health);

        const rapidHealingSyncButton = document.getElementById('rapidHealingSync_button');
        if (rapidHealingSyncButton && notif.args.playerId === this.getPlayerId()) {
            this.rapidHealingSyncHearts = Math.max(0, this.rapidHealingSyncHearts - notif.args.delta_health);
            rapidHealingSyncButton.innerHTML = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (${2*this.rapidHealingSyncHearts}[Energy])`), { 'card_name': this.cards.getCardName(37, 'text-only'), 'hearts': this.rapidHealingSyncHearts });
        }
    }

    notif_maxHealth(notif: Notif<NotifMaxHealthArgs>) {
        this.setMaxHealth(notif.args.playerId, notif.args.maxHealth);
        this.setHealth(notif.args.playerId, notif.args.health);
    }

    notif_energy(notif: Notif<NotifEnergyArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
    }

    notif_shrinkRayToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.setShrinkRayTokens(notif.args.playerId, notif.args.tokens);
    }

    notif_poisonToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.setPoisonTokens(notif.args.playerId, notif.args.tokens);
    }

    notif_removeShrinkRayToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.diceManager.resolveHealthDice(notif.args.playerId, notif.args.deltaTokens, 'shrink-ray');
        setTimeout(() => this.notif_shrinkRayToken(notif), ANIMATION_MS);
    }

    notif_removePoisonToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.diceManager.resolveHealthDice(notif.args.playerId, notif.args.deltaTokens, 'poison');
        setTimeout(() => this.notif_poisonToken(notif), ANIMATION_MS);
    }

    notif_setCardTokens(notif: Notif<NotifSetCardTokensArgs>) {
        this.cards.placeTokensOnCard(this.getPlayerTable(notif.args.playerId).cards, notif.args.card, notif.args.playerId);
    }

    notif_toggleRapidHealing(notif: Notif<NotifToggleRapidHealingArgs>) {
        if (notif.args.active) {
            this.addRapidHealingButton(notif.args.playerEnergy, notif.args.isMaxHealth);
        } else {
            this.removeRapidHealingButton();
        }
    }

    notif_useCamouflage(notif: Notif<NotifUseCamouflageArgs>) {
        if (notif.args.cancelDamageArgs) { 
            this.gamedatas.gamestate.args = notif.args.cancelDamageArgs;
            (this as any).updatePageTitle();
            this.onEnteringCancelDamage(notif.args.cancelDamageArgs, (this as any).isCurrentPlayerActive());
        } else {            
            this.diceManager.showCamouflageRoll(notif.args.diceValues);
        }
    }

    notif_changeDie(notif: Notif<NotifChangeDieArgs>) {
        if (notif.args.psychicProbeRollDieArgs) {
            const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();
            this.onEnteringPsychicProbeRollDie(notif.args.psychicProbeRollDieArgs, isCurrentPlayerActive);
        } else {
            this.diceManager.changeDie(notif.args.dieId, notif.args.inTokyo, notif.args.toValue, notif.args.roll);
        }
    }

    notif_rethrow3changeDie(notif: Notif<NotifChangeDieArgs>) {
        this.diceManager.changeDie(notif.args.dieId, notif.args.inTokyo, notif.args.toValue, notif.args.roll);
    }

    notif_resolvePlayerDice() {
        this.diceManager.lockAll();
    }

    notif_updateLeaveTokyoUnder(notif: Notif<NotifUpdateLeaveTokyoUnderArgs>) {                    
        dojo.query('.autoLeaveButton').removeClass('bgabutton_blue');
        dojo.query('.autoLeaveButton').addClass('bgabutton_gray');
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(`${popinId}_set${notif.args.under}`)) {
            dojo.removeClass(`${popinId}_set${notif.args.under}`, 'bgabutton_gray');
            dojo.addClass(`${popinId}_set${notif.args.under}`, 'bgabutton_blue');
        }
        for (let i = 1; i<=15; i++) {
            if (document.getElementById(`${popinId}_setStay${i}`)) {
                dojo.toggleClass(`${popinId}_setStay${i}`, 'disabled', notif.args.under > 0 && i <= notif.args.under);
            }
        }
    }

    notif_updateStayTokyoOver(notif: Notif<NotifUpdateStayTokyoOverArgs>) {                    
        dojo.query('.autoStayButton').removeClass('bgabutton_blue');
        dojo.query('.autoStayButton').addClass('bgabutton_gray');
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(`${popinId}_setStay${notif.args.over}`)) {
            dojo.removeClass(`${popinId}_setStay${notif.args.over}`, 'bgabutton_gray');
            dojo.addClass(`${popinId}_setStay${notif.args.over}`, 'bgabutton_blue');
        }
    }
    
    private setPoints(playerId: number, points: number, delay: number = 0) {
        (this as any).scoreCtrl[playerId]?.toValue(points);
        this.getPlayerTable(playerId).setPoints(points, delay);
    }
    
    private setHealth(playerId: number, health: number, delay: number = 0) {
        this.healthCounters[playerId].toValue(health);
        this.getPlayerTable(playerId).setHealth(health, delay);
        this.checkRapidHealingButtonState();
    }
    
    private setMaxHealth(playerId: number, maxHealth: number) {
        this.gamedatas.players[playerId].maxHealth = maxHealth;
        this.checkRapidHealingButtonState();
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(popinId)) {
            this.updateAutoLeavePopinButtons();
        }
    }
    
    private setEnergy(playerId: number, energy: number, delay: number = 0) {
        this.energyCounters[playerId].toValue(energy);
        this.getPlayerTable(playerId).setEnergy(energy, delay);
        this.checkBuyEnergyDrinkState(energy); // disable button if energy gets down to 0
        this.checkRapidHealingButtonState();
    }

    private setPlayerTokens(playerId: number, tokens: number, tokenName: string) {
        const containerId = `player-board-${tokenName}-tokens-${playerId}`;
        const container = document.getElementById(containerId);
        while (container.childElementCount > tokens) {
            container.removeChild(container.lastChild);
        }
        for (let i=container.childElementCount; i<tokens; i++) {
            dojo.place(`<div class="${tokenName} token"></div>`, containerId);
        }
    }

    private setShrinkRayTokens(playerId: number, tokens: number) {
        this.setPlayerTokens(playerId, tokens, 'shrink-ray');
        this.getPlayerTable(playerId)?.setShrinkRayTokens(tokens);
    }

    private setPoisonTokens(playerId: number, tokens: number) {
        this.setPlayerTokens(playerId, tokens, 'poison');
        this.getPlayerTable(playerId)?.setPoisonTokens(tokens);
    }

    public checkBuyEnergyDrinkState(energy: number = null) {
        if (document.getElementById('buy_energy_drink_button')) {
            if (energy === null) {
                energy = this.energyCounters[this.getPlayerId()].getValue();
            }
            dojo.toggleClass('buy_energy_drink_button', 'disabled', energy < 1 || !this.diceManager.canRethrow());
        }
    }

    public checkUseSmokeCloudState() {
        if (document.getElementById('use_smoke_cloud_button')) {
            dojo.toggleClass('use_smoke_cloud_button', 'disabled', !this.diceManager.canRethrow());
        }
    }

    private eliminatePlayer(playerId: number) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById(`overall_player_board_${playerId}`).classList.add('eliminated-player');
        if (!document.getElementById(`dead-icon-${playerId}`)) {
            dojo.place(`<div id="dead-icon-${playerId}" class="icon dead"></div>`, `player_board_${playerId}`);
        }

        this.getPlayerTable(playerId).eliminatePlayer();
        this.tableManager.placePlayerTable(); // because all player's card were removed

        if (document.getElementById(`player-board-monster-figure-${playerId}`)) {
            (this as any).fadeOutAndDestroy(`player-board-monster-figure-${playerId}`);
        }
        dojo.removeClass(`overall_player_board_${playerId}`, 'intokyo');
        dojo.removeClass(`monster-board-wrapper-${playerId}`, 'intokyo');
        if (playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }
        
        this.setShrinkRayTokens(playerId, 0);
        this.setPoisonTokens(playerId, 0);
    }

    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    public format_string_recursive(log: string, args: any) {
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                if (args.card_name) {
                    let types: number[] = null;
                    if (typeof args.card_name == 'number') {
                        types = [args.card_name];
                    } else if (typeof args.card_name == 'string' && args.card_name[0] >= '0' && args.card_name[0] <= '9') {
                        types = args.card_name.split(',').map((cardType: string) => Number(cardType));
                    }
                    if (types !== null) {
                        const names: string[] = types.map((cardType: number) => this.cards.getCardName(cardType, 'text-only'));
                        args.card_name = `<strong>${names.join(', ')}</strong>`;
                    }
                }

                for (const property in args) {
                    if (args[property]?.indexOf?.(']') > 0) {
                        args[property] = formatTextIcons(_(args[property]));
                    }
                }

                log = formatTextIcons(_(log));
            }
        } catch (e) {
            console.error(log,args,"Exception thrown", e.stack);
        }
        return (this as any).inherited(arguments);
    }
}