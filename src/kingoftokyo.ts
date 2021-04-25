declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;

declare const board: HTMLDivElement;

const ANIMATION_MS = 1500;
const CARD_WIDTH = 123;
const CARD_HEIGHT = 185;

const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

class KingOfTokyo implements KingOfTokyo {
    private gamedatas: KingOfTokyoGamedatas;
    private healthCounters: Counter[] = [];
    private energyCounters: Counter[] = [];
    private selectedDicesIds: number[] = null;
    private visibleCards: Stock;

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
        /*(this as any).dontPreloadImage('eye-shadow.png');
        (this as any).dontPreloadImage('publisher.png');
        [1,2,3,4,5,6,7,8,9,10].filter(i => !Object.values(gamedatas.players).some(player => Number((player as any).mat) === i)).forEach(i => (this as any).dontPreloadImage(`playmat_${i}.jpg`));
*/
        log( "Starting game setup" );
        
        this.gamedatas = gamedatas;

        log('gamedatas', gamedatas);

        this.createPlayerPanels(gamedatas);
        this.createVisibleCards(gamedatas.visibleCards);


        /*this.lordsStacks = new LordsStacks(this, gamedatas.visibleLords, gamedatas.pickLords);
        this.locationsStacks = new LocationsStacks(this, gamedatas.visibleLocations, gamedatas.pickLocations);

        this.createPlayerTables(gamedatas);

        if (gamedatas.endTurn) {
            this.notif_lastTurn();
        }

        if (Number(gamedatas.gamestate.id) >= 80) { // score or end
            this.onEnteringShowScore(true);
        }

        this.addHelp();*/

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
            case 'pickCard':
                this.onEnteringPickCard(args.args);
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
        if (args.throwNumber === 1) {
            $('dices-selector').innerHTML = '';
        }

        const dices = args.dices;
        const addedDicesIds = [];
        for (let i=1; i<=6; i++) {
            dices.filter(dice => dice.value == i && !document.getElementById(`dice${dice.id}`)).forEach(dice => {
                addedDicesIds.push(`dice${dice.id}`);
                dojo.place(this.createDiceHtml(dice), 'dices-selector');
            });
        }

        const selectable = (this as any).isCurrentPlayerActive() && args.throwNumber < args.maxThrowNumber;

        addedDicesIds.map(id => document.getElementById(id)).forEach((dice: HTMLDivElement) => {
            dice.classList.add('rolled');
            setTimeout(() => {
                dice.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll');
            }, 100); 

            if (selectable) {
                dice.addEventListener('click', () => this.toggleDiceSelection(dice));
            }
        });

        this.selectedDicesIds = [];
        dojo.toggleClass('dices-selector', 'selectable', selectable);
    }

    private onEnteringPickCard(args: EnteringPickCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.visibleCards.setSelectionMode(1);
            args.disabledIds.forEach(id => dojo.query(`#visible-cards_item_${id}`).addClass('disabled'));
        }
    }

    public onLeavingState(stateName: string) {
        log( 'Leaving state: '+stateName );

        switch (stateName) {
            case 'pickCard':
                this.onLeavingPickCard();
                break;
        }
    }

    private onLeavingPickCard() {
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
                        (this as any).addActionButton('rethrow_button', _("Rethrow selected dices") + ` ${tdArgs.throwNumber}/${tdArgs.maxThrowNumber}`, 'onRethrow');
                        dojo.addClass('rethrow_button', 'disabled');
                    }
                    (this as any).addActionButton('resolve_button', _("Resolve dices"), 'resolveDices', null, null, 'red');
                    break;
                
                case 'pickCard':
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
        });

        // (this as any).addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
    }

    private createVisibleCards(visibleCards: Card[]) {
        this.visibleCards = new ebg.stock() as Stock;
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection';
        this.visibleCards.create(this, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = dojo.hitch(this, 'setupNewCard'); 
        this.visibleCards.image_items_per_row = 13;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, 'onVisibleCardClick');

        this.setupCards([this.visibleCards]);

        visibleCards.forEach(card => this.visibleCards.addToStockWithId(card.type, `${card.id}`));
    } 
    
    private setupCards(stocks: Stock[]) {

        stocks.forEach(stock => {
            const keepcardsurl = `${g_gamethemeurl}img/cards0.jpg`;
            for(let id=1; id<=48; id++) {  // keep
                stock.addItemType(id, id, keepcardsurl, id);
            }

            const discardcardsurl = `${g_gamethemeurl}img/cards1.jpg`;
            for(let id=101; id<=118; id++) {  // keep
                stock.addItemType(id, id, discardcardsurl, id);
            }
        });
    }

    private setupNewCard(card_div: HTMLDivElement, card_type_id: number, card_id: string) {
        card_div.innerHTML = `<div class="name">Name</div>
        <div class="type ${ card_type_id < 100 ? 'keep' : 'discard'}">${ card_type_id < 100 ? _('Keep') : _('Discard')}</div>
        <div class="text">Text</div>`;
    }

    private onVisibleCardClick(control_name: string, item_id: string) {
        if (dojo.hasClass(`visible-cards_item_${item_id}`, 'disabled')) {
            this.visibleCards.unselectItem(item_id);
            return;
        }

        this.pickCard(item_id);
    }

    public onRethrow() {
        this.rethrowDices(this.selectedDicesIds);

        this.selectedDicesIds.forEach(id => dojo.destroy(`dice${id}`));        
    }

    public rethrowDices(dicesIds: number[]) {
        if(!(this as any).checkAction('rethrow')) {
            return;
        }

        this.takeAction('rethrow', {
            dicesIds
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

    public pickCard(id: number | string) {
        if(!(this as any).checkAction('pick')) {
            return;
        }

        this.takeAction('pick', {
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

    private createDiceHtml(dice: Dice) {
        let html = `<div id="dice${dice.id}" class="dice dice${dice.value}" data-dice-id="${dice.id}" data-dice-value="${dice.value}">
        <ol class="die-list" data-roll="${dice.value}">`;
        for (let die=1; die<=6; die++) {
            html += `<li class="die-item ${dice.extra ? 'green' : 'black'} side${die}" data-side="${die}"></li>`;
        }
        html += `</ol></div>`;
        return html;
    }

    private toggleDiceSelection(dice: HTMLDivElement) {
        const divId = dice.id;
        const selected = !dojo.hasClass(divId, 'selected');
        dojo.toggleClass(divId, 'selected', selected);

        const id = parseInt(dice.dataset.diceId);
        if (selected) {
            this.selectedDicesIds.push(id);
        } else {
            this.selectedDicesIds.splice(this.selectedDicesIds.indexOf(id), 1);
        }

        dojo.toggleClass(divId, 'selected', selected);

        dojo.toggleClass('rethrow_button', 'disabled', !this.selectedDicesIds.length);
        dojo.toggleClass('resolve_button', 'disabled', !!this.selectedDicesIds.length);
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
            ['pickCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            /*['newPearlMaster', 1],
            ['discardLordPick', 1],
            ['discardLocationPick', 1],
            ['lastTurn', 1],
            ['scoreLords', SCORE_MS],
            ['scoreLocations', SCORE_MS],
            ['scoreCoalition', SCORE_MS],
            ['scorePearlMaster', SCORE_MS],
            ['scoreTotal', SCORE_MS],*/
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });
    }

    notif_resolveNumberDice(notif: Notif<NotifResolveNumberDiceArgs>) {
        (this as any).scoreCtrl[notif.args.playerId]?.incValue(notif.args.points);
        // TODO animation
    }

    notif_resolveHealthDice(notif: Notif<NotifResolveHealthDiceArgs>) {
        this.healthCounters[notif.args.playerId].incValue(notif.args.health);
        // TODO animation
    }
    notif_resolveHealthDiceInTokyo(notif: Notif<NotifResolveHealthDiceInTokyoArgs>) {
        // TODO animation
    }

    notif_resolveEnergyDice(notif: Notif<NotifResolveEnergyDiceArgs>) {
        this.energyCounters[notif.args.playerId].incValue(notif.args.number);
        // TODO animation
    }

    notif_resolveSmashDice(notif: Notif<NotifResolveSmashDiceArgs>) {
        notif.args.smashedPlayersIds.forEach(playerId => {            
            const health = this.healthCounters[playerId]?.getValue();
            if (health) {
                const newHealth = Math.max(0, health - notif.args.number);
                this.healthCounters[playerId].toValue(newHealth);
                // TODO animation
            }
        });
    }

    notif_playerEliminated(notif: Notif<NotifPlayerEliminatedArgs>) {        
        (this as any).scoreCtrl[notif.args.playerId]?.toValue(0);
        // TODO animation? or strike player's name
    }

    notif_leaveTokyo(notif: Notif<NotifPlayerLeavesTokyoArgs>) {
        // TODO animation
    }

    notif_playerEntersTokyo(notif: Notif<NotifPlayerEntersTokyoArgs>) {
        // TODO animation
    }

    notif_pickCard(notif: Notif<NotifPickCardArgs>) {
        const card = notif.args.card;
        const newCard = notif.args.newCard;
        this.energyCounters[notif.args.playerId].incValue(-card.cost);

        this.visibleCards.removeFromStockById(`${card.id}`); // TODO remove to player hand
        this.visibleCards.addToStockWithId(newCard.type, `${newCard.id}`);
    }

    notif_renewCards(notif: Notif<NotifRenewCardsArgs>) {
        this.energyCounters[notif.args.playerId].incValue(-2);

        this.visibleCards.removeAll();
        notif.args.cards.forEach(card => this.visibleCards.addToStockWithId(card.type, `${card.id}`));
    }
    

    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    /*public format_string_recursive(log: string, args: any) {
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                if (args.guild !== undefined && args.guild_name !== undefined && args.guild_name[0] !== '<') {
                    args.guild_name = `<span class='log-guild-name' style='color: ${LOG_GUILD_COLOR[args.guild]}'>${_(args.guild_name)}</span>`;
                }
            }
        } catch (e) {
            console.error(log,args,"Exception thrown", e.stack);
        }
        return (this as any).inherited(arguments);
    }*/
}