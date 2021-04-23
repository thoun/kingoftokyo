declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;

declare const board: HTMLDivElement;

const ANIMATION_MS = 1500;

const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

class KingOfTokyo implements KingOfTokyo {
    private gamedatas: KingOfTokyoGamedatas;
    private healthCounters: Counter[] = [];
    private energyCounters: Counter[] = [];

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

        /*this.lordsStacks = new LordsStacks(this, gamedatas.visibleLords, gamedatas.pickLords);
        this.locationsStacks = new LocationsStacks(this, gamedatas.visibleLocations, gamedatas.pickLocations);

        this.createPlayerTables(gamedatas);

        if (gamedatas.endTurn) {
            this.notif_lastTurn();
        }

        if (Number(gamedatas.gamestate.id) >= 80) { // score or end
            this.onEnteringShowScore(true);
        }

        this.addHelp();

        this.setupNotifications();*/

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
            /*case 'lordSelection':
                const multiple = (args.args as EnteringLordSelectionArgs).multiple;
                const number = (args.args as EnteringLordSelectionArgs).lords?.length;
                this.setGamestateDescription(multiple ? (number > 1 ? 'multiple' : 'last') : '');
                this.onEnteringLordSelection(args.args);
                break;
            case 'lordSwap':
                this.onEnteringLordSwap();
                break;

            case 'locationStackSelection':
                const allHidden = (args.args as EnteringLocationStackSelectionArgs).allHidden;
                this.setGamestateDescription(allHidden ? 'allHidden' : '');
                this.onEnteringLocationStackSelection(args.args);
                break;
            case 'locationSelection':
                this.onEnteringLocationSelection(args.args);
                break;

            case 'showScore':
                Object.keys(this.gamedatas.players).forEach(playerId => (this as any).scoreCtrl[playerId].setValue(0));
                this.onEnteringShowScore();
                break;*/
        }
    }
    
    private setGamestateDescription(property: string = '') {
        const originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        this.gamedatas.gamestate.description = `${originalState['description' + property]}`; 
        this.gamedatas.gamestate.descriptionmyturn = `${originalState['descriptionmyturn' + property]}`; 
        (this as any).updatePageTitle();        
    }

    onEnteringThrowDices(args: EnteringThrowDicesArgs) {
        document.getElementById('rolled_dice').innerHTML = args.dices.map(dice => dice.value).join(',');
    }

    /*onEnteringLordSelection(args: EnteringLordSelectionArgs) {
        this.lordsStacks.setPick(true, (this as any).isCurrentPlayerActive(), args.lords);
    }

    onEnteringLordSwap() {    
        if ((this as any).isCurrentPlayerActive()) {
            this.swapSpots = [];
            this.playersTables[(this as any).player_id].setSelectableForSwap(true);
        }
    }

    onEnteringLocationStackSelection(args: EnteringLocationStackSelectionArgs) {
        this.locationsStacks.setMax(args.max);
        if ((this as any).isCurrentPlayerActive()) {
            this.locationsStacks.setSelectable(true, null, args.allHidden);
        }
    } 

    onEnteringLocationSelection(args: EnteringLocationSelectionArgs) {
        this.locationsStacks.setPick(true, (this as any).isCurrentPlayerActive(), args.locations);
    }   

    onEnteringShowScore(fromReload: boolean = false) {
        this.closePopin();
        const lastTurnBar = document.getElementById('last-round');
        if (lastTurnBar) {
            lastTurnBar.style.display = 'none';
        }

        document.getElementById('stacks').style.display = 'none';
        document.getElementById('score').style.display = 'flex';

        Object.values(this.gamedatas.players).forEach(player => {
            //if we are a reload of end state, we display values, else we wait for notifications
            const score: Score = fromReload ? (player as any).newScore : null;

            dojo.place(`<tr id="score${player.id}">
                <td class="player-name" style="color: #${player.color}">${player.name}</td>
                <td id="lords-score${player.id}" class="score-number lords-score">${score?.lords !== undefined ? score.lords : ''}</td>
                <td id="locations-score${player.id}" class="score-number locations-score">${score?.locations !== undefined ? score.locations : ''}</td>
                <td id="coalition-score${player.id}" class="score-number coalition-score">${score?.coalition !== undefined ? score.coalition : ''}</td>
                <td id="masterPearl-score${player.id}" class="score-number masterPearl-score">${score?.pearlMaster !== undefined ? score.pearlMaster : ''}</td>
                <td class="score-number total">${score?.total !== undefined ? score.total : ''}</td>
            </tr>`, 'score-table-body');
        });

        (this as any).addTooltipHtmlToClass('lords-score', _("The total of Influence Points from the Lords with the Coat of Arms tokens (the most influential Lord of each color in your Senate Chamber)."));
        (this as any).addTooltipHtmlToClass('locations-score', _("The total of Influence Points from the Locations you control."));
        (this as any).addTooltipHtmlToClass('coalition-score', _("The biggest area of adjacent Lords of the same color is identified and 3 points are scored for each Lord within it"));
        (this as any).addTooltipHtmlToClass('masterPearl-score', _("The player who has the Pearl Master token gains a bonus of 5 Influence Points."));

        if(!document.getElementById('page-content').style.zoom) {
            // scale down 
            Array.from(document.getElementsByClassName('player-table-wrapper')).forEach(elem => elem.classList.add('scaled-down'));
        }
    }

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    public onLeavingState(stateName: string) {
        log( 'Leaving state: '+stateName );

        switch (stateName) {
            case 'lordStackSelection':
                this.onLeavingLordStackSelection();
                break;
            case 'lordSelection':
                this.onLeavingLordSelection();
                break;
            case 'lordSwap':
                this.onLeavingLordSwap();
                break;

            case 'locationStackSelection':
                this.onLeavingLocationStackSelection();
                break;
            case 'locationSelection':
                this.onLeavingLocationSelection();
                break;
        }
    }

    onLeavingLordStackSelection() {
        this.lordsStacks.setSelectable(false, null);
    }

    onLeavingLordSelection() {
        this.lordsStacks.setPick(this.lordsStacks.hasPickCards(), false);
    }

    onLeavingLordSwap() {        
        if ((this as any).isCurrentPlayerActive()) {
            this.playersTables[(this as any).player_id].setSelectableForSwap(false);
        }
        this.swapSpots = null;
    }

    onLeavingLocationStackSelection() {
        this.locationsStacks.setSelectable(false);
    }

    onLeavingLocationSelection() {
        this.locationsStacks.setSelectable(false);
    }*/

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    public onUpdateActionButtons(stateName: string, args: any) {
        if((this as any).isCurrentPlayerActive()) {
            switch (stateName) {
                case 'throwDices':
                const tdArgs = args as EnteringThrowDicesArgs;console.log(tdArgs);
                if (tdArgs.throwNumber < tdArgs.maxThrowNumber) {
                    (this as any).addActionButton('rethrow_button', _("Rethrow selected dices") + ` ${tdArgs.throwNumber}/${tdArgs.maxThrowNumber}`, 'onRethrow');
                    dojo.addClass('rethrow_button', 'disabled');
                }
                (this as any).addActionButton('resolve_button', _("Resolve dices"), 'resolveDices', null, null, 'red');
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

    public onRethrow() {
        // TODO
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

    public takeAction(action: string, data?: any) {
        data = data || {};
        data.lock = true;
        (this as any).ajaxcall(`/kingoftokyo/kingoftokyo/${action}.html`, data, this, () => {});
    }

    /*placePearlMasterToken(playerId: number) {
        const pearlMasterToken = document.getElementById('pearlMasterToken');
        if (pearlMasterToken) {
            slideToObjectAndAttach(this, pearlMasterToken, `player_board_${playerId}_pearlMasterWrapper`);
        } else {
            dojo.place('<div id="pearlMasterToken" class="token"></div>', `player_board_${playerId}_pearlMasterWrapper`);

            (this as any).addTooltipHtml('pearlMasterToken', _("Pearl Master token. At the end of the game, the player possessing the Pearl Master token gains a bonus of 5 Influence Points."));
        }
    }

    public setCanSwap(swapSpots: number[]) {
        if (this.swapSpots.length !== 2 && swapSpots.length === 2) {
            (this as any).addActionButton( 'swap_button', _("Swap"), 'onSwap' );
        } else if (this.swapSpots.length === 2 && swapSpots.length !== 2) {
            dojo.destroy('swap_button');
        }
        this.swapSpots = swapSpots.slice();
    }

    public onSwap() {
        if(!(this as any).checkAction('next')) {
            return;
        }
     
        this.takeAction('swap', { spots: this.swapSpots.join(',') });
    }

    public onDontSwap() {
        /_*if(!(this as any).checkAction('next')) {
            return;
        }*_/
     
        this.takeAction('dontSwap');
    }

    private setScore(playerId: number | string, column: number, score: number) { // column 1 for lord ... 5 for pearl master
        const cell = (document.getElementById(`score${playerId}`).getElementsByTagName('td')[column] as HTMLTableDataCellElement);
        cell.innerHTML = `${score}`;
        cell.classList.add('highlight');
    }

    private addHelp() {
        dojo.place(`<button id="conspiracy-help-button">?</button>`, 'left-side');
        dojo.connect( $('conspiracy-help-button'), 'onclick', this, () => this.showHelp());
    }

    private showHelp() {
        if (!this.helpDialog) {
            this.helpDialog = new ebg.popindialog();
            this.helpDialog.create( 'conspiracyHelpDialog' );
            this.helpDialog.setTitle( _("Cards help") );
            
            var html = `<div id="help-popin">
                <h1>${_("Lords")}</h1>
                <div id="help-lords" class="help-section">
                    <table>`;
                LORDS_IDS.forEach(number => html += `<tr><td><div id="lord${number}" class="lord"></div></td><td>${getLordTooltip(number * 10)}</td></tr>`);
                html += `</table>
                </div>
                <h1>${_("Locations")}</h1>
                <div id="help-locations" class="help-section">
                    <table>`;
                LOCATIONS_UNIQUE_IDS.forEach(number => html += `<tr><td><div id="location${number}" class="location"></div></td><td>${getLocationTooltip(number * 10)}</td></tr>`);
                LOCATIONS_GUILDS_IDS.forEach(number => html += `<tr><td><div id="location${number}" class="location"></div></td><td>${getLocationTooltip(number * 10)}</td></tr>`);
                html += `</table>
                </div>
            </div>`;
            
            // Show the dialog
            this.helpDialog.setContent(html);
        }

        this.helpDialog.show();
    }

    private setNewScoreTooltip(playerId: number) {
        const score: Score = (this.gamedatas.players[playerId] as any).newScore
        const html = `
            ${_("Lords points")} : <strong>${score.lords}</strong><br>
            ${_("Locations points")} : <strong>${score.locations}</strong><br>
            ${_("Coalition points")} : <strong>${score.coalition}</strong><br>
            ${_("Pearl Master points")} : <strong>${score.pearlMaster}</strong><br>
        `;

        (this as any).addTooltipHtml(`player_score_${playerId}`, html);
        (this as any).addTooltipHtml(`icon_point_${playerId}`, html);
    }

    private setNewScore(args: NotifNewScoreArgs) {
        const score = args.newScore;
        (this.gamedatas.players[args.playerId] as any).newScore = score;
        if (!isNaN(score.total)) {
            (this as any).scoreCtrl[args.playerId]?.toValue(score.total);
        }
        
        this.setNewScoreTooltip(args.playerId);
    }*/

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
            ['resolveEnergyDice', ANIMATION_MS],
            /*['locationPlayed', ANIMATION_MS],
            ['discardLords', ANIMATION_MS],
            ['discardLocations', ANIMATION_MS],
            ['newPearlMaster', 1],
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
    }

    notif_resolveHealthDice(notif: Notif<NotifResolveHealthDiceArgs>) {
        this.healthCounters[notif.args.playerId].incValue(notif.args.health);
    }

    notif_resolveEnergyDice(notif: Notif<NotifResolveEnergyDiceArgs>) {
        this.energyCounters[notif.args.playerId].incValue(notif.args.number);
    }
/*
    notif_lordSwapped(notif: Notif<NotifLordSwappedArgs>) {
        this.playersTables[notif.args.playerId].lordSwapped(notif.args);
        this.minimaps[notif.args.playerId].lordSwapped(notif.args);
        this.setNewScore(notif.args);
    }

    notif_extraLordRevealed(notif: Notif<NotifExtraLordRevealedArgs>) {
        this.lordsStacks.addLords([notif.args.lord]);
    }

    notif_locationPlayed(notif: Notif<NotifLocationPlayedArgs>) {
        const from = this.locationsStacks.getStockContaining(`${notif.args.location.id}`);

        this.playersTables[notif.args.playerId].addLocation(notif.args.spot, notif.args.location, from);
        this.setNewScore(notif.args);
        this.pearlCounters[notif.args.playerId].incValue(notif.args.pearls);

        if (notif.args.discardedLocations?.length) {
            this.locationsStacks.discardPick(notif.args.discardedLocations);
        }

        this.locationsStacks.setPick(false, false);

        this.updateKeysForPlayer(notif.args.playerId);
    }

    notif_discardLords() {
        this.lordsStacks.discardVisible();
    }

    notif_discardLordPick(notif: Notif<NotifDiscardLordPickArgs>) {
        // log('notif_discardLordPick', notif.args);
        this.lordsStacks.discardPick(notif.args.discardedLords);
        this.lordsStacks.setPick(false, false);
    }
    
    notif_discardLocationPick(notif: Notif<NotifDiscardLocationPickArgs>) {
        // log('notif_discardLordPick', notif.args);
        this.locationsStacks.discardPick(notif.args.discardedLocations);
        this.locationsStacks.setPick(false, false);
    }

    notif_discardLocations() {
        this.locationsStacks.discardVisible();
    }

    notif_newPearlMaster(notif: Notif<NotifNewPearlMasterArgs>) {
        this.placePearlMasterToken(notif.args.playerId);

        (this as any).scoreCtrl[notif.args.playerId].incValue(5);
        (this.gamedatas.players[notif.args.playerId] as any).newScore.pearlMaster = 5;
        this.setNewScoreTooltip(notif.args.playerId);

        
        (this as any).scoreCtrl[notif.args.previousPlayerId]?.incValue(-5);
        if (this.gamedatas.players[notif.args.previousPlayerId]) {
            (this.gamedatas.players[notif.args.previousPlayerId] as any).newScore.pearlMaster = 0;
            this.setNewScoreTooltip(notif.args.previousPlayerId);
        }
    }

    notif_lastTurn() {
        dojo.place(`<div id="last-round">
            ${_("This is the last round of the game!")}
        </div>`, 'page-title');
    }

    notif_scoreLords(notif: Notif<NotifScorePointArgs>) {
        log('notif_scoreLords', notif.args);
        this.setScore(notif.args.playerId, 1, notif.args.points);
        (this as any).scoreCtrl[notif.args.playerId].incValue(notif.args.points);
        this.playersTables[notif.args.playerId].highlightTopLords();
    }

    notif_scoreLocations(notif: Notif<NotifScorePointArgs>) {
        log('notif_scoreLocations', notif.args);
        this.setScore(notif.args.playerId, 2, notif.args.points);
        (this as any).scoreCtrl[notif.args.playerId].incValue(notif.args.points);
        this.playersTables[notif.args.playerId].highlightLocations();
    }

    notif_scoreCoalition(notif: Notif<NotifScoreCoalitionArgs>) {
        log('notif_scoreCoalition', notif.args);
        this.setScore(notif.args.playerId, 3, notif.args.points);
        (this as any).scoreCtrl[notif.args.playerId].incValue(notif.args.points);
        this.playersTables[notif.args.playerId].highlightCoalition(notif.args.coalition);
    }

    notif_scorePearlMaster(notif: Notif<NotifScorePearlMasterArgs>) {
        log('notif_scorePearlMaster', notif.args);
        Object.keys(this.gamedatas.players).forEach(playerId => {
            const isPearlMaster = notif.args.playerId == Number(playerId);
            this.setScore(playerId, 4, isPearlMaster ? 5 : 0);
            if (isPearlMaster) {
                (this as any).scoreCtrl[notif.args.playerId].incValue(5);
            }
        });

        document.getElementById('pearlMasterToken').classList.add('highlight');
    }

    notif_scoreTotal(notif: Notif<NotifScorePointArgs>) {
        log('notif_scoreTotal', notif.args);
        this.setScore(notif.args.playerId, 5, notif.args.points);
    }*/

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