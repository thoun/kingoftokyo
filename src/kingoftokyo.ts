declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;

declare const board: HTMLDivElement;

const ANIMATION_MS = 1500;
const LONG_ANIMATION_MS = 2500;

class KingOfTokyo implements KingOfTokyoGame {
    private gamedatas: KingOfTokyoGamedatas;
    private healthCounters: Counter[] = [];
    private energyCounters: Counter[] = [];
    private diceManager: DiceManager;
    private visibleCards: Stock;
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
        this.diceManager = new DiceManager(this, gamedatas.dices);  
        this.cards = new Cards(this);
        this.createVisibleCards(gamedatas.visibleCards);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(() => this.playerTables.forEach(playerTable => playerTable.initPlacement()), 200);

        this.setupNotifications();

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
            case 'throwDices':
                const tdArgs = args.args as EnteringThrowDicesArgs;
                this.setGamestateDescription(tdArgs.throwNumber >= tdArgs.maxThrowNumber ? `last` : '');
                this.onEnteringThrowDices(args.args);
                break;
            case 'resolveDices': 
                this.diceManager.hideLock();
                break;
            
            case 'buyCard':
                this.onEnteringBuyCard(args.args);
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

    private onEnteringThrowDices(args: EnteringThrowDicesArgs) {
        this.diceManager.showLock();

        const dices = args.dices;

        this.diceManager.setDices(dices, args.throwNumber === 1, args.throwNumber === args.maxThrowNumber, args.inTokyo);
    }

    private onEnteringBuyCard(args: EnteringBuyCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.visibleCards.setSelectionMode(1);
            args.disabledIds.forEach(id => dojo.query(`#visible-cards_item_${id}`).addClass('disabled'));
        }
    }

    private onEnteringEndTurn() {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables[(this as any).player_id].removeDiscardCards();
            this.tableManager.placePlayerTable(); // adapt to removed card
        }
    }

    public onLeavingState(stateName: string) {
        log( 'Leaving state: '+stateName );

        switch (stateName) {
            case 'resolveDices':
                this.diceManager.removeAllDices();
                break;
            case 'buyCard':
                this.onLeavingBuyCard();
                break;
        }
    }

    private onLeavingBuyCard() {
        this.visibleCards.setSelectionMode(0);
        dojo.query('#visible-cards .stockitem').removeClass('disabled');
    }

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    public onUpdateActionButtons(stateName: string, args: any) {
        if((this as any).isCurrentPlayerActive()) {
            switch (stateName) {
                case 'throwDices':
                    const tdArgs = args as EnteringThrowDicesArgs;
                    if (tdArgs.throwNumber < tdArgs.maxThrowNumber) {
                        (this as any).addActionButton('rethrow_button', _("Rethrow dices") + ` ${tdArgs.throwNumber}/${tdArgs.maxThrowNumber}`, 'onRethrow');
                        dojo.addClass('rethrow_button', 'disabled');
                    }
                    (this as any).addActionButton('resolve_button', _("Resolve dices"), 'resolveDices', null, null, 'red');
                    break;
                
                case 'buyCard':
                    (this as any).addActionButton('renew_button', _("Renew cards") + ` ( 2 <span class="small icon energy"></span>)`, 'onRenew');
                    if (this.energyCounters[(this as any).player_id].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    (this as any).addActionButton('endTurn_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;

                case 'leaveTokyo':
                    (this as any).addActionButton('stayInTokyo_button', _("Stay in Tokyo"), 'onStayInTokyo');
                    (this as any).addActionButton('leaveTokyo_button', _("Leave Tokyo"), 'onLeaveTokyo');
                    break;
            }

        }
    } 
    

    ///////////////////////////////////////////////////
    //// Utility methods


    ///////////////////////////////////////////////////

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

            dojo.place(`<div id="player-board-monster-figure-${player.id}" class="monster-figure monster${(player as any).monster}"></div>`, `player_board_${player.id}`);

            if (player.eliminated) {
                setTimeout(() => this.eliminatePlayer(playerId), 200);
            }
        });

        // (this as any).addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
    }
    
    private createPlayerTables(gamedatas: KingOfTokyoGamedatas) {
        this.getOrderedPlayers().forEach((player, index) =>
            this.playerTables[Number(player.id)] = new PlayerTable(this, player, index, gamedatas.playersCards[Number(player.id)])
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
        dojo.connect(this.visibleCards, 'onChangeSelection', this, 'onVisibleCardClick');

        this.cards.setupCards([this.visibleCards]);

        visibleCards.forEach(card => this.visibleCards.addToStockWithId(card.type, `${card.id}`));
    }

    private onVisibleCardClick(control_name: string, item_id: string) {
        if (dojo.hasClass(`visible-cards_item_${item_id}`, 'disabled')) {
            this.visibleCards.unselectItem(item_id);
            return;
        }

        this.buyCard(item_id);
    }

    public onRethrow() {
        this.rethrowDices(this.diceManager.destroyFreeDices());      
    }

    public rethrowDices(dicesIds: number[]) {
        if(!(this as any).checkAction('rethrow')) {
            return;
        }

        this.takeAction('rethrow', {
            dicesIds: dicesIds.join(',')
        });
    }

    public resolveDices() {
        if(!(this as any).checkAction('resolve')) {
            return;
        }

        this.takeAction('resolve');
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

    public buyCard(id: number | string) {
        if(!(this as any).checkAction('buyCard')) {
            return;
        }

        this.takeAction('buyCard', {
            id
        });
    }

    public onRenew() {
        if(!(this as any).checkAction('renew')) {
            return;
        }

        this.takeAction('renew');
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
            ['resolveHealthDice', LONG_ANIMATION_MS],
            ['resolveHealthDiceInTokyo', ANIMATION_MS],
            ['resolveEnergyDice', LONG_ANIMATION_MS],
            ['resolveSmashDice', LONG_ANIMATION_MS],
            ['playerEliminated', LONG_ANIMATION_MS],
            ['playerEntersTokyo', LONG_ANIMATION_MS],
            ['renewCards', ANIMATION_MS],
            ['buyCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });
    }

    notif_resolveNumberDice(notif: Notif<NotifResolveNumberDiceArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points);
        this.diceManager.resolveNumberDices(notif.args);
    }

    notif_resolveHealthDice(notif: Notif<NotifResolveHealthDiceArgs>) {
        this.setHealth(notif.args.playerId, notif.args.health);
        this.diceManager.resolveHealthDices(notif.args);
    }
    notif_resolveHealthDiceInTokyo(notif: Notif<NotifResolveHealthDiceInTokyoArgs>) {
        this.diceManager.resolveHealthDicesInTokyo();
    }

    notif_resolveEnergyDice(notif: Notif<NotifResolveEnergyDiceArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
        this.diceManager.resolveEnergyDices(notif.args);
    }

    notif_resolveSmashDice(notif: Notif<NotifResolveSmashDiceArgs>) {
        notif.args.smashedPlayersIds.forEach(playerId => {            
            const health = this.healthCounters[playerId]?.getValue();
            if (health) {
                const newHealth = Math.max(0, health - notif.args.number);
                this.setHealth(notif.args.playerId, newHealth);
            }
            this.diceManager.resolveSmashDices(notif.args);
        });
    }

    notif_playerEliminated(notif: Notif<NotifPlayerEliminatedArgs>) {
        this.setPoints(notif.args.playerId, 0);
        this.eliminatePlayer(notif.args.playerId);
    }

    notif_leaveTokyo(notif: Notif<NotifPlayerLeavesTokyoArgs>) {
        this.playerTables[notif.args.playerId].leaveTokyo();
    }

    notif_playerEntersTokyo(notif: Notif<NotifPlayerEntersTokyoArgs>) {
        this.playerTables[notif.args.playerId].enterTokyo(notif.args.location);
    }

    notif_buyCard(notif: Notif<NotifBuyCardArgs>) {
        const card = notif.args.card;
        const newCard = notif.args.newCard;
        this.setEnergy(notif.args.playerId, notif.args.energy);

        moveToAnotherStock(this.visibleCards, this.playerTables[notif.args.playerId].cards, card.type, `${card.id}`);
        this.visibleCards.addToStockWithId(newCard.type, `${newCard.id}`);

        this.tableManager.placePlayerTable(); // adapt to new card
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
    }

    private eliminatePlayer(playerId: number) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById(`overall_player_board_${playerId}`).classList.add('eliminated-player');
        dojo.place(`<div class="icon dead"></div>`, `player_board_${playerId}`);

        this.playerTables[playerId].removeAllCards();
        this.tableManager.placePlayerTable();

        (this as any).fadeOutAndDestroy(`player-board-monster-figure-${playerId}`);
    }

    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    /*public format_string_recursive(log: string, args: any) {
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                

            }
        } catch (e) {
            console.error(log,args,"Exception thrown", e.stack);
        }
        return (this as any).inherited(arguments);
    }*/
}