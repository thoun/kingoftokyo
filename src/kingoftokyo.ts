declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;

declare const board: HTMLDivElement;

const ANIMATION_MS = 500;
const SCORE_MS = 1500;

const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

const LOG_GUILD_COLOR = [];
LOG_GUILD_COLOR[1] = '#c1950b';
LOG_GUILD_COLOR[2] = '#770405';
LOG_GUILD_COLOR[3] = '#097138';
LOG_GUILD_COLOR[4] = '#011d4d';
LOG_GUILD_COLOR[5] = '#522886';

class KingOfTokyo implements ConspiracyGame {
    private gamedatas: KingOfTokyoGamedatas;
    private lordsStacks: LordsStacks;
    private locationsStacks: LocationsStacks;
    private playersTables: PlayerTable[] = [];
    private minimaps: Minimap[] = [];
    private pearlCounters: Counter[] = [];
    private silverKeyCounters: Counter[] = [];
    private goldKeyCounters: Counter[] = [];
    private swapSpots: number[];
    private helpDialog: any;
    private playerInPopin: number | null = null;

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

        /*this.createPlayerPanels(gamedatas);

        this.lordsStacks = new LordsStacks(this, gamedatas.visibleLords, gamedatas.pickLords);
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
            case 'lordStackSelection':
                const limitToHidden = (args.args as EnteringLordStackSelectionArgs).limitToHidden;
                this.setGamestateDescription(limitToHidden ? `limitToHidden${limitToHidden}` : '');
                this.onEnteringLordStackSelection(args.args);
                break;
            case 'lordSelection':
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
                break;
        }
    }
    
    private setGamestateDescription(property: string = '') {
        const originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        this.gamedatas.gamestate.description = `${originalState['description' + property]}`; 
        this.gamedatas.gamestate.descriptionmyturn = `${originalState['descriptionmyturn' + property]}`; 
        (this as any).updatePageTitle();        
    }

    onEnteringLordStackSelection(args: EnteringLordStackSelectionArgs) {
        this.lordsStacks.setMax(args.max);
        if ((this as any).isCurrentPlayerActive()) {
            this.lordsStacks.setSelectable(true, args.limitToHidden);
        }
    }

    onEnteringLordSelection(args: EnteringLordSelectionArgs) {
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
    }

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    public onUpdateActionButtons(stateName: string, args: any) {
        if((this as any).isCurrentPlayerActive()) {
            switch (stateName) {
                case 'lordSwap':
                (this as any).addActionButton( 'dontSwap_button', _("Don't swap"), 'onDontSwap' );
                break;
            }

        }
    } 
    

    ///////////////////////////////////////////////////
    //// Utility methods


    ///////////////////////////////////////////////////

    private createViewPlayermatPopin()  {
        dojo.place(`<div id="popin_showPlayermat_container" class="conspiracy_popin_container">
            <div id="popin_showPlayermat_underlay" class="conspiracy_popin_underlay"></div>
                <div id="popin_showPlayermat_wrapper" class="conspiracy_popin_wrapper">
                <div id="popin_showPlayermat" class="conspiracy_popin">
                    <a id="popin_showPlayermat_close" class="closeicon"><i class="fa fa-times fa-2x" aria-hidden="true"></i></a>
                    <a id="popin_showPlayermat_left" class="left arrow"></a>
                    <a id="popin_showPlayermat_right" class="right arrow"></a>
                                
                    <div id="playermat-container-modal" class="player-table-wrapper" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
                    </div>
                </div>
            </div>
        </div>`, $(document.body));

        dojo.connect($(`popin_showPlayermat_close`), 'onclick', this, () => this.closePopin());
        dojo.connect($(`popin_showPlayermat_left`), 'onclick', this, () => this.changePopinPlayer(-1));
        dojo.connect($(`popin_showPlayermat_right`), 'onclick', this, () => this.changePopinPlayer(1));
    }

    private movePlayerTableToPopin(playerId: number) {
        document.getElementById('playermat-container-modal').style.zoom = document.getElementById('page-content').style.zoom;
        this.playerInPopin = playerId;
        document.getElementById('popin_showPlayermat_container').style.display = 'block';

        document.getElementById('playermat-container-modal').appendChild(document.getElementById(`player-table-mat-${playerId}`));
    }

    private closePopin() {
        if (!this.playerInPopin) {
            return;
        }
        
        document.getElementById('popin_showPlayermat_container').style.display = 'none';

        document.getElementById(`player-table-wrapper-${this.playerInPopin}`).appendChild(document.getElementById(`player-table-mat-${this.playerInPopin}`));

        this.playerInPopin = null;
    }

    private changePopinPlayer(delta: number) {
        document.getElementById(`player-table-wrapper-${this.playerInPopin}`).appendChild(document.getElementById(`player-table-mat-${this.playerInPopin}`));
        
        const playerIds = this.gamedatas.playerorder.map(val => Number(val));
        this.playerInPopin = playerIds[(playerIds.indexOf(this.playerInPopin) + delta) % playerIds.length];

        document.getElementById('playermat-container-modal').appendChild(document.getElementById(`player-table-mat-${this.playerInPopin}`));
    }

    private createPlayerPanels(gamedatas: KingOfTokyoGamedatas) {
        this.createViewPlayermatPopin();

        Object.values(gamedatas.players).forEach(player => {
            const playerId = Number(player.id);
            const playerTable = Object.values(gamedatas.playersTables[playerId]);         

            // Lord & pearl counters

            dojo.place(`<div class="counters">
                <div id="lord-counter-wrapper-${player.id}" class="lord-counter"></div>
                <div id="pearl-counter-wrapper-${player.id}" class="pearl-counter">
                    <div class="token pearl"></div> 
                    <span id="pearl-counter-${player.id}" class="left"></span>
                </div>
            </div>`, `player_board_${player.id}`);

            this.minimaps[playerId] = new Minimap(playerId, playerTable);

            const pearlCounter = new ebg.counter();
            pearlCounter.create(`pearl-counter-${player.id}`);
            pearlCounter.setValue((player as any).pearls);
            this.pearlCounters[playerId] = pearlCounter;

            // keys counters

            dojo.place(`<div class="counters">
                <div id="silver-key-counter-wrapper-${player.id}" class="key-counter silver-key-counter">
                    <div id="silver-key-${player.id}" class="token silver key"></div> 
                    <span id="silver-key-counter-${player.id}" class="left"></span>
                </div>
                <div id="gold-key-counter-wrapper-${player.id}" class="key-counter gold-key-counter">
                    <div id="gold-key-${player.id}"  class="token gold key"></div> 
                    <span id="gold-key-counter-${player.id}" class="left"></span>
                </div>
            </div>`, `player_board_${player.id}`);

            const lastLocationSpotIndex = playerTable.map((spot: PlayerTableSpot, spotIndex: number) => spot.location ? spotIndex : -1).reduce((a, b) => a > b ? a : b, -1);

            const silverKeyAvailable = playerTable.filter((spot: PlayerTableSpot, spotIndex: number) => spotIndex > lastLocationSpotIndex && spot.lord?.key === 1).length > 0;
            dojo.toggleClass(`silver-key-counter-wrapper-${player.id}`, 'available', silverKeyAvailable);
            const silverKeyCounter = new ebg.counter();
            silverKeyCounter.create(`silver-key-counter-${player.id}`);
            silverKeyCounter.setValue(playerTable.filter((spot: PlayerTableSpot) => spot.lord?.key === 1).length);
            this.silverKeyCounters[playerId] = silverKeyCounter;

            const goldKeyAvailable = playerTable.filter((spot: PlayerTableSpot, spotIndex: number) => spotIndex > lastLocationSpotIndex && spot.lord?.key === 2).length > 0;
            dojo.toggleClass(`gold-key-counter-wrapper-${player.id}`, 'available', goldKeyAvailable);
            const goldKeyCounter = new ebg.counter();
            goldKeyCounter.create(`gold-key-counter-${player.id}`);
            goldKeyCounter.setValue(playerTable.filter((spot: PlayerTableSpot) => spot.lord?.key === 2).length);
            this.goldKeyCounters[playerId] = goldKeyCounter;

            // top lord tokens

            let html = `<div class="top-lord-tokens">`;
            GUILD_IDS.forEach(guild => html += `<div class="token guild${guild} token-guild${guild}" id="top-lord-token-${guild}-${player.id}"></div>`);
            html += `</div>`;
            dojo.place(html, `player_board_${player.id}`);

            // pearl master token
            dojo.place(`<div id="player_board_${player.id}_pearlMasterWrapper" class="pearlMasterWrapper"></div>`, `player_board_${player.id}`);

            if (gamedatas.pearlMasterPlayer === playerId) {
                this.placePearlMasterToken(gamedatas.pearlMasterPlayer);
            }

            // vision popup button
            /*if (playerId !== Number((this as any).player_id)) {*/
                dojo.place(`<div id="show-playermat-${player.id}" class="show-playermat-button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 85.333343 145.79321">
                    <path fill="currentColor" d="M 1.6,144.19321 C 0.72,143.31321 0,141.90343 0,141.06039 0,140.21734 5.019,125.35234 11.15333,108.02704 L 22.30665,76.526514 14.626511,68.826524 C 8.70498,62.889705 6.45637,59.468243 4.80652,53.884537 0.057,37.810464 3.28288,23.775161 14.266011,12.727735 23.2699,3.6711383 31.24961,0.09115725 42.633001,0.00129225 c 15.633879,-0.123414 29.7242,8.60107205 36.66277,22.70098475 8.00349,16.263927 4.02641,36.419057 -9.54327,48.363567 l -6.09937,5.36888 10.8401,30.526466 c 5.96206,16.78955 10.84011,32.03102 10.84011,33.86992 0,1.8389 -0.94908,3.70766 -2.10905,4.15278 -1.15998,0.44513 -19.63998,0.80932 -41.06667,0.80932 -28.52259,0 -39.386191,-0.42858 -40.557621,-1.6 z M 58.000011,54.483815 c 3.66666,-1.775301 9.06666,-5.706124 11.99999,-8.735161 l 5.33334,-5.507342 -6.66667,-6.09345 C 59.791321,26.035633 53.218971,23.191944 43.2618,23.15582 33.50202,23.12041 24.44122,27.164681 16.83985,34.94919 c -4.926849,5.045548 -5.023849,5.323672 -2.956989,8.478106 3.741259,5.709878 15.032709,12.667218 24.11715,14.860013 4.67992,1.129637 13.130429,-0.477436 20,-3.803494 z m -22.33337,-2.130758 c -2.8907,-1.683676 -6.3333,-8.148479 -6.3333,-11.893186 0,-11.58942 14.57544,-17.629692 22.76923,-9.435897 8.41012,8.410121 2.7035,22.821681 -9,22.728685 -2.80641,-0.0223 -6.15258,-0.652121 -7.43593,-1.399602 z m 14.6667,-6.075289 c 3.72801,-4.100734 3.78941,-7.121364 0.23656,-11.638085 -2.025061,-2.574448 -3.9845,-3.513145 -7.33333,-3.513145 -10.93129,0 -13.70837,13.126529 -3.90323,18.44946 3.50764,1.904196 7.30574,0.765377 11,-3.29823 z m -11.36999,0.106494 c -3.74071,-2.620092 -4.07008,-7.297494 -0.44716,-6.350078 3.2022,0.837394 4.87543,-1.760912 2.76868,-4.29939 -1.34051,-1.615208 -1.02878,-1.94159 1.85447,-1.94159 4.67573,0 8.31873,5.36324 6.2582,9.213366 -1.21644,2.27295 -5.30653,5.453301 -7.0132,5.453301 -0.25171,0 -1.79115,-0.934022 -3.42099,-2.075605 z"></path>
                    </svg>
                </div>`, `player_board_${player.id}`);
                dojo.connect($(`show-playermat-${player.id}`), 'onclick', this, () => this.movePlayerTableToPopin(Number(player.id)));
            /*}*/

            this.setNewScore({
                playerId,
                newScore: (player as any).newScore
            });
        });

        (this as any).addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
        (this as any).addTooltipHtmlToClass('pearl-counter', _("Number of pearls"));
        (this as any).addTooltipHtmlToClass('silver-key-counter', _("Number of silver keys (highlighted if a silver key is available)"));
        (this as any).addTooltipHtmlToClass('gold-key-counter', _("Number of gold keys (highlighted if a gold key is available)"));
        GUILD_IDS.forEach(guild => (this as any).addTooltipHtmlToClass(`token-guild${guild}`, _("The Coat of Arms token indicates the most influential Lord of each color.")));
    }

    private updateKeysForPlayer(playerId: number) {
        const playerTable = this.playersTables[playerId];
        const lastLocationSpotIndex = playerTable.spotsStock.map((spotStock: PlayerTableSpotStock, spotIndex: number) => spotStock.hasLocation() ? spotIndex : -1).reduce((a, b) => a > b ? a : b, -1);

        const silverKeyAvailable = playerTable.spotsStock.filter((spotStock: PlayerTableSpotStock, spotIndex: number) => spotIndex > lastLocationSpotIndex && spotStock.getLord()?.key === 1).length > 0;
        dojo.toggleClass(`silver-key-counter-wrapper-${playerId}`, 'available', silverKeyAvailable);
        const totalSilverKeyCounter = playerTable.spotsStock.filter((spotStock: PlayerTableSpotStock) => spotStock.getLord()?.key === 1).length;
        this.silverKeyCounters[playerId].toValue(totalSilverKeyCounter);

        const goldKeyAvailable = playerTable.spotsStock.filter((spotStock: PlayerTableSpotStock, spotIndex: number) => spotIndex > lastLocationSpotIndex && spotStock.getLord()?.key === 2).length > 0;
        dojo.toggleClass(`gold-key-counter-wrapper-${playerId}`, 'available', goldKeyAvailable); 
        const totalGoldKeyCounter = playerTable.spotsStock.filter((spotStock: PlayerTableSpotStock) => spotStock.getLord()?.key === 2).length;
        this.goldKeyCounters[playerId].toValue(totalGoldKeyCounter);
    }

    private createPlayerTables(gamedatas: KingOfTokyoGamedatas) {
        const currentPlayer = Object.values(gamedatas.players).find(player => Number(player.id) === Number((this as any).player_id));
        if (currentPlayer) {
            this.createPlayerTable(gamedatas, Number(currentPlayer.id));
        }
        Object.values(gamedatas.players).filter(player => Number(player.id) !== Number((this as any).player_id)).forEach(player => 
            this.createPlayerTable(gamedatas, Number(player.id))
        );
    }

    private createPlayerTable(gamedatas: KingOfTokyoGamedatas, playerId: number) {
        this.playersTables[playerId] = new PlayerTable(this, gamedatas.players[playerId], gamedatas.playersTables[playerId]);
    }

    public lordPick(id: number) {
        if(!(this as any).checkAction('addLord')) {
            return;
        }

        this.takeAction('pickLord', {
            id
        });
    }

    public lordStockPick(guild: number) {
        if(!(this as any).checkAction('chooseVisibleStack')) {
            return;
        }

        this.takeAction('chooseVisibleStack', {
            guild
        });
    }

    public locationPick(id: number) {
        if(!(this as any).checkAction('addLocation')) {
            return;
        }

        this.takeAction('pickLocation', {
            id
        });
    }

    public takeAction(action: string, data?: any) {
        data = data || {};
        data.lock = true;
        (this as any).ajaxcall(`/conspiracy/conspiracy/${action}.html`, data, this, () => {});
    }

    placePearlMasterToken(playerId: number) {
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
        /*if(!(this as any).checkAction('next')) {
            return;
        }*/
     
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
            ['lordPlayed', ANIMATION_MS],
            ['lordSwapped', ANIMATION_MS],
            ['extraLordRevealed', ANIMATION_MS],
            ['locationPlayed', ANIMATION_MS],
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
            ['scoreTotal', SCORE_MS],
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });
    }

    notif_lordPlayed(notif: Notif<NotifLordPlayedArgs>) {
        const from = this.lordsStacks.getStockContaining(`${notif.args.lord.id}`);
        
        this.playersTables[notif.args.playerId].addLord(notif.args.spot, notif.args.lord, from);
        this.minimaps[notif.args.playerId].addLord(notif.args.spot, notif.args.lord);
        this.setNewScore(notif.args);
        this.pearlCounters[notif.args.playerId].incValue(notif.args.pearls);
        
        if (notif.args.stackSelection || !notif.args.discardedLords.length) {
            this.lordsStacks.discardPick(notif.args.discardedLords);
            this.lordsStacks.setPick(false, false);
        }

        if (notif.args.lord.key) {
            this.updateKeysForPlayer(notif.args.playerId);
        }
    }

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
    }

    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    public format_string_recursive(log: string, args: any) {
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
    }
}