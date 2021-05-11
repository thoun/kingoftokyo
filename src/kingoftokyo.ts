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
        // ignore loading of some pictures
        [1,2,3,4,5,6].filter(i => !Object.values(gamedatas.players).some(player => Number((player as any).mmonster) === i)).forEach(i => {
            (this as any).dontPreloadImage(`monster-board-${i + 1}.png`);
            (this as any).dontPreloadImage(`monster-figure-${i + 1}.png`);
        });

        log( "Starting game setup" );
        
        this.gamedatas = gamedatas;

        log('gamedatas', gamedatas);

        this.createPlayerPanels(gamedatas); 
        this.diceManager = new DiceManager(this, gamedatas.dice);  
        this.cards = new Cards(this);
        this.createVisibleCards(gamedatas.visibleCards);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(() => this.playerTables.forEach(playerTable => playerTable.initPlacement()), 200);

        this.setupNotifications();

        /*document.getElementById('test').addEventListener('click', () => this.notif_resolveSmashDice({
            args: {
                number: 3,
                smashedPlayersIds: [2343492, 2343493]
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
        log( 'Entering state: '+stateName , args.args );

        switch (stateName) {
            case 'throwDice':
                this.onEnteringThrowDice(args.args);
                break;
            case 'changeDie': 
                this.onEnteringChangeDie(args.args);
                break;
            case 'resolveDice': 
                this.diceManager.hideLock();
                break;
            case 'resolveHeartDice':
                this.onEnteringResolveHeartDice(args.args);
                break;
            
            case 'buyCard':
                this.onEnteringBuyCard(args.args);
                break;
            case 'sellCard':
                this.onEnteringSellCard();
                break;

            case 'endTurn':
                this.onEnteringEndTurn();
                break;
        }
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

    private onEnteringThrowDice(args: EnteringThrowDiceArgs) {
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? `last` : '');

        this.diceManager.showLock();

        const dice = args.dice;

        this.diceManager.setDiceForThrowDice(dice, args.throwNumber === args.maxThrowNumber, args.inTokyo);
        
        if ((this as any).isCurrentPlayerActive()) {
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', _("Rethrow dice") + ` (${args.throwNumber}/${args.maxThrowNumber})`, () => this.onRethrow(), !args.dice.some(dice => !dice.locked));
            }

            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3(), !args.rethrow3.hasDice3);
            }

            if (args.energyDrink.hasCard && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + ` ( 1 <span class="small icon energy"></span>)`, () => this.buyEnergyDrink());
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }
        }
    }

    private onEnteringChangeDie(args: EnteringChangeDieArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args, args.inTokyo);
        }
    }

    private onEnteringResolveHeartDice(args: EnteringResolveHeartDiceArgs) {
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.inTokyo);

            if ((this as any).isCurrentPlayerActive()) {
                dojo.place(`<div id="heart-action-selector" class="whiteblock"></div>`, 'rolled-dice', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    }

    private onEnteringBuyCard(args: EnteringBuyCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.visibleCards.setSelectionMode(1);

            if (args.canBuyFromPlayers) {
                this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            }

            if (args._private?.pickCard) {
                this.showPickStock(args._private.pickCard);
            }

            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`).classList.add('disabled'));
        }
    }

    private onEnteringSellCard() {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
        }
    }

    private onEnteringEndTurn() {
        // clean discard cards
        this.playerTables.forEach(playerTable => playerTable.removeDiscardCards());
        this.tableManager.placePlayerTable(); // adapt to removed card
    }

    public onLeavingState(stateName: string) {
        log( 'Leaving state: '+stateName );

        switch (stateName) {
            case 'throwDice':
                document.getElementById('dice-actions').innerHTML = '';
                break;                
            case 'resolveHeartDice':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;
            case 'buyCard':
                this.onLeavingBuyCard();
                break;
            case 'sellCard':
                this.onLeavingSellCard();
                break;
        }
    }

    private onLeavingBuyCard() {
        this.visibleCards.setSelectionMode(0);
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(0));            
        this.hidePickStock();
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
        if((this as any).isCurrentPlayerActive()) {
            switch (stateName) {
                case 'throwDice':
                    (this as any).addActionButton('resolve_button', _("Resolve dice"), 'goToChangeDie', null, null, 'red');
                    break;
                case 'changeDie':
                    (this as any).addActionButton('resolve_button', _("Resolve dice"), 'resolveDice', null, null, 'red');
                    break;

                case 'leaveTokyo':
                    (this as any).addActionButton('stayInTokyo_button', _("Stay in Tokyo"), 'onStayInTokyo');
                    (this as any).addActionButton('leaveTokyo_button', _("Leave Tokyo"), 'onLeaveTokyo');
                    break;
                
                case 'buyCard':
                    (this as any).addActionButton('renew_button', _("Renew cards") + formatTextIcons(` ( 2 [Energy])`), 'onRenew');
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    (this as any).addActionButton('endTurn_button', _("End turn"), 'goToSellCard', null, null, 'red');
                    break;
                
                case 'sellCard':
                    (this as any).addActionButton('endTurn_button', _("End turn"), 'onEndTurn', null, null, 'red');
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

    private getOrderedPlayers(): Player[] {
        return this.gamedatas.playerorder.map(id => this.gamedatas.players[Number(id)]);
    }

    private createPlayerPanels(gamedatas: KingOfTokyoGamedatas) {

        Object.values(gamedatas.players).forEach(player => {
            const playerId = Number(player.id);  

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
            healthCounter.setValue((player as any).health);
            this.healthCounters[playerId] = healthCounter;

            const energyCounter = new ebg.counter();
            energyCounter.create(`energy-counter-${player.id}`);
            energyCounter.setValue((player as any).energy);
            this.energyCounters[playerId] = energyCounter;

            dojo.place(`<div class="player-tokens">
                <div id="player-board-shrink-ray-tokens-${player.id}" class="player-token"></div>
                <div id="player-board-poison-tokens-${player.id}" class="player-token"></div>
            </div>`, `player_board_${player.id}`);

            this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
            this.setPoisonTokens(playerId, player.poisonTokens);

            dojo.place(`<div id="player-board-monster-figure-${player.id}" class="monster-figure monster${(player as any).monster}"></div>`, `player_board_${player.id}`);

            if ((player as any).location > 0) {
                dojo.addClass(`overall_player_board_${playerId}`, 'intokyo');
            }
            if (player.eliminated) {
                setTimeout(() => this.eliminatePlayer(playerId), 200);
            }
        });

        // (this as any).addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
    }
    
    private createPlayerTables(gamedatas: KingOfTokyoGamedatas) {
        this.getOrderedPlayers().forEach(player =>
            this.playerTables[Number(player.id)] = new PlayerTable(this, player, gamedatas.playersCards[Number(player.id)])
        );
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

        visibleCards.forEach(card => this.visibleCards.addToStockWithId(card.type, `${card.id}`));
    }

    public onVisibleCardClick(stock: Stock, cardId: string, from: number = 0) {
        if (dojo.hasClass(`${stock.container_div.id}_item_${cardId}`, 'disabled')) {
            stock.unselectItem(cardId);
            return;
        }

        if (this.gamedatas.gamestate.name === 'sellCard') {
            this.sellCard(cardId);
        } else {
            this.buyCard(cardId, from)
        }
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
        this.takeAction('rethrow3');
    }

    public buyEnergyDrink() {
        this.takeAction('buyEnergyDrink');
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

    public goToChangeDie() {
        if(!(this as any).checkAction('goToChangeDie')) {
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

    public buyCard(id: number | string, from: number) {
        if(!(this as any).checkAction('buyCard')) {
            return;
        }

        this.takeAction('buyCard', {
            id,
            from
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
        if(!(this as any).checkAction('goToSellCard')) {
            return;
        }

        this.takeAction('goToSellCard');
    }

    public onEndTurn() {
        if(!(this as any).checkAction('endTurn')) {
            return;
        }

        this.takeAction('endTurn');
    }

    public takeAction(action: string, data?: any) {
        data = data || {};
        data.lock = true;
        (this as any).ajaxcall(`/kingoftokyo/kingoftokyo/${action}.html`, data, this, () => {});
    }
    
    private showPickStock(card: Card) {
        if (!this.pickCard) { 
            dojo.place('<div id="pick-stock"></div>', 'deck');

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

        this.pickCard.addToStockWithId(card.type, `${card.id}`);
    }

    private hidePickStock() {
        const div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
        }
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
            ['resolveNumberDice', ANIMATION_MS],
            ['resolveHealthDice', ANIMATION_MS],
            ['resolveHealthDiceInTokyo', ANIMATION_MS],
            ['resolveEnergyDice', ANIMATION_MS],
            ['resolveSmashDice', ANIMATION_MS],
            ['playerEliminated', ANIMATION_MS],
            ['playerEntersTokyo', ANIMATION_MS],
            ['renewCards', ANIMATION_MS],
            ['buyCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['removeCards', 1],
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });
    }

    notif_resolveNumberDice(notif: Notif<NotifResolveNumberDiceArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points);
        this.diceManager.resolveNumberDice(notif.args);
    }

    notif_resolveHealthDice(notif: Notif<NotifResolveHealthDiceArgs>) {
        this.setHealth(notif.args.playerId, notif.args.health);
        this.diceManager.resolveHealthDice(notif.args);
    }
    notif_resolveHealthDiceInTokyo(notif: Notif<NotifResolveHealthDiceInTokyoArgs>) {
        this.diceManager.resolveHealthDiceInTokyo();
    }

    notif_resolveEnergyDice(notif: Notif<NotifResolveEnergyDiceArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
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

    notif_leaveTokyo(notif: Notif<NotifPlayerLeavesTokyoArgs>) {
        this.playerTables[notif.args.playerId].leaveTokyo();
        dojo.removeClass(`overall_player_board_${notif.args.playerId}`, 'intokyo');
    }

    notif_playerEntersTokyo(notif: Notif<NotifPlayerEntersTokyoArgs>) {
        this.playerTables[notif.args.playerId].enterTokyo(notif.args.location);
        this.setPoints(notif.args.playerId, notif.args.points);
        dojo.addClass(`overall_player_board_${notif.args.playerId}`, 'intokyo');
    }

    notif_buyCard(notif: Notif<NotifBuyCardArgs>) {
        const card = notif.args.card;
        const newCard = notif.args.newCard;
        this.setEnergy(notif.args.playerId, notif.args.energy);

        if (newCard) {
            moveToAnotherStock(this.visibleCards, this.playerTables[notif.args.playerId].cards, card.type, `${card.id}`);
            this.visibleCards.addToStockWithId(newCard.type, `${newCard.id}`, 'deck');
        } else if (notif.args.from > 0) {
            moveToAnotherStock(this.playerTables[notif.args.from].cards, this.playerTables[notif.args.playerId].cards, card.type, `${card.id}`);
        } else { // from Made in a lab Pick
            if (this.pickCard) { // active player
                moveToAnotherStock(this.pickCard, this.playerTables[notif.args.playerId].cards, card.type, `${card.id}`);
            } else {
                this.playerTables[notif.args.playerId].cards.addToStockWithId(card.type, `${card.id}`, 'deck');
            }
        }

        this.tableManager.placePlayerTable(); // adapt to new card
    }

    notif_removeCards(notif: Notif<NotifRemoveCardsArgs>) {
        this.playerTables[notif.args.playerId].removeCards(notif.args.cards);
        this.tableManager.placePlayerTable(); // adapt after removed cards
    }

    notif_renewCards(notif: Notif<NotifRenewCardsArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy);

        this.visibleCards.removeAll();
        notif.args.cards.forEach(card => this.visibleCards.addToStockWithId(card.type, `${card.id}`));
    }

    

    notif_points(notif: Notif<NotifPointsArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points);
    }

    notif_health(notif: Notif<NotifHealthArgs>) {
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
    
    private setPoints(playerId: number, points: number) {
        (this as any).scoreCtrl[playerId]?.toValue(points);
        this.playerTables[playerId].setPoints(points);
    }
    
    private setHealth(playerId: number, health: number) {
        this.healthCounters[playerId].toValue(health);
        this.playerTables[playerId].setHealth(health);
    }
    
    private setEnergy(playerId: number, energy: number) {
        this.energyCounters[playerId].toValue(energy);
        this.checkBuyEnergyDrinkState(this.energyCounters[this.getPlayerId()].getValue()); // disable button if energy gets down to 0
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
    }

    private setPoisonTokens(playerId: number, tokens: number) {
        this.setPlayerTokens(playerId, tokens, 'poison');
    }

    private checkBuyEnergyDrinkState(energy: number) {
        if (document.getElementById('buy_energy_drink_button')) {
            dojo.toggleClass('buy_energy_drink_button', 'disabled', energy < 1);
        }
    }

    private eliminatePlayer(playerId: number) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById(`overall_player_board_${playerId}`).classList.add('eliminated-player');
        dojo.place(`<div class="icon dead"></div>`, `player_board_${playerId}`);

        this.playerTables[playerId].eliminatePlayer();
        this.tableManager.placePlayerTable(); // because all player's card were removed

    }

    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    public format_string_recursive(log: string, args: any) {
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                
                if (args.card_name && args.card_name[0] != '<') {
                    args.card_name = `<strong>${args.card_name}</strong>`;
                }

                if (args.dice_value && args.dice_value.indexOf(']') > 0) {
                    args.dice_value = formatTextIcons(args.dice_value);
                }

                log = formatTextIcons(log);
            }
        } catch (e) {
            console.error(log,args,"Exception thrown", e.stack);
        }
        return (this as any).inherited(arguments);
    }
}