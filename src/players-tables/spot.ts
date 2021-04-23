class PlayerTableSpotStock {
    private playerId: number;
    private lordsStock: Stock;
    private locationsStock: Stock;

    constructor(
        private game: ConspiracyGame, 
        private playerTable: PlayerTable,
        player: Player,
        private spot: PlayerTableSpot,
        private spotNumber: number) {

        this.playerId = Number(player.id);

        dojo.place(`<div id="player-table-${this.playerId}-spot${spotNumber}" class="player-table-spot spot${spotNumber}">
                <div id="player${this.playerId}-spot${spotNumber}-lord-stock"></div>
                <div id="player${this.playerId}-spot${spotNumber}-location-stock" class="player-table-spot-location"></div>
                <div id="player${this.playerId}-spot${spotNumber}-token" class="player-table-spot-token"></div>
        </div>`, `player-table-${this.playerId}`);

        this.lordsStock = new ebg.stock() as Stock;
        this.lordsStock.create( this.game, $(`player${this.playerId}-spot${spotNumber}-lord-stock`), LORD_WIDTH, LORD_HEIGHT );
        this.lordsStock.setSelectionMode(0);
        this.lordsStock.setSelectionAppearance('class');
        this.lordsStock.onItemCreate = dojo.hitch(this, 'setupNewLordCard'); 
        this.lordsStock.image_items_per_row = 16;
        dojo.connect(this.lordsStock, 'onChangeSelection', this, 'onLordSelection');
        setupLordCards([this.lordsStock]);

        const lord = spot.lord;
        if (lord) {
            this.lordsStock.addToStockWithId(getUniqueId(lord.type, lord.guild), `${lord.id}`);
        }

        this.locationsStock = new ebg.stock() as Stock;
        this.locationsStock.create( this.game, $(`player${this.playerId}-spot${spotNumber}-location-stock`), LOCATION_WIDTH, LOCATION_HEIGHT );
        this.locationsStock.setSelectionMode(0);
        this.locationsStock.onItemCreate = dojo.hitch(this, 'setupNewLocationCard'); 
        this.locationsStock.image_items_per_row = 13;
        setupLocationCards([this.locationsStock]);

        
        const location = spot.location;
        if (location) {
            this.locationsStock.addToStockWithId(getUniqueId(location.type, location.passivePowerGuild ?? 0), `${location.id}`);
        }
    }

    public hasLord(): boolean {
        return !!this.spot.lord;
    }
    public hasLocation(): boolean {
        return !!this.spot.location;
    }

    public getLordStock(): Stock {
        return this.lordsStock;
    }

    private get tokenWrapper(): HTMLDivElement {
       return document.getElementById(`player${this.playerId}-spot${this.spotNumber}-token`) as HTMLDivElement;
    }

    public getLord(): Lord {
        return this.spot.lord;
    }
    
    public setLord(lord: Lord, fromStock: Stock | null) {
        if (fromStock) {
            moveToAnotherStock(fromStock, this.lordsStock, getUniqueId(lord.type, lord.guild), `${lord.id}`);
        } else {
            this.lordsStock.addToStockWithId(getUniqueId(lord.type, lord.guild), `${lord.id}`, 'lord-hidden-pile');
        }
        this.spot.lord = lord;
    }

    public setLocation(location: Location, fromStock: Stock | null) {
        if (fromStock) {
            moveToAnotherStock(fromStock, this.locationsStock, getUniqueId(location.type, location.passivePowerGuild ?? 0), `${location.id}`);
        } else {
            this.locationsStock.addToStockWithId(getUniqueId(location.type, location.passivePowerGuild ?? 0), `${location.id}`, 'location-hidden-pile');
        }
        this.spot.location = location;
    }

    public setSelectableForSwap(selectable: boolean): void {
        if (!this.spot.lord) {
            return;
        }

        if (this.spot.lord.key) { // can't swap
            dojo.toggleClass(`player${this.playerId}-spot${this.spotNumber}-lord-stock_item_${this.spot.lord.id}`, 'disabled', selectable);
        } else { // can swap
            this.lordsStock.setSelectionMode(selectable ? 2 : 0);
            dojo.toggleClass(`player${this.playerId}-spot${this.spotNumber}-lord-stock_item_${this.spot.lord.id}`, 'selectable', selectable);
            
            if (!selectable) {
                this.lordsStock.unselectAll();
            }
        }
    }

    onLordSelection() {
        const items = this.lordsStock.getSelectedItems();
        if (items.length == 1) {
            this.playerTable.addSelectedSpot(this.spotNumber);
        } else if (items.length == 0) {
            this.playerTable.removeSelectedSpot(this.spotNumber);
        }
    }

    public placeTopLordToken() {
        const guild = this.spot.lord.guild;
        const tokenDiv = document.getElementById(`top-lord-token-${guild}-${this.playerId}`) as HTMLDivElement;
        this.addTokenDiv(tokenDiv);
    }

    public setupNewLordCard(card_div: HTMLDivElement, card_type_id: number, card_id: string) {
        let message = getLordTooltip(card_type_id);

        if (message) {
            (this.game as any).addTooltipHtml(card_div.id, message);
        }
    }

    public setupNewLocationCard(card_div: HTMLDivElement, card_type_id: number, card_id: string) {
        let message = getLocationTooltip(card_type_id);

        if (message) {
            (this.game as any).addTooltipHtml(card_div.id, message);
        }
    }

    public addTokenDiv(tokenDiv: HTMLDivElement) {
        slideToObjectAndAttach(this.game, tokenDiv, this.tokenWrapper.id);
    }
    
    public getTokenDiv(): HTMLDivElement | undefined {
        return this.tokenWrapper.getElementsByTagName('div')[0] as HTMLDivElement;
    }
    
    public highlightLord(guild: number = null) {
        const cardId = this.lordsStock.items[0]?.id;
        cardId && document.getElementById(`${this.lordsStock.container_div.id}_item_${cardId}`).classList.add(`highlight${guild ? `-guild${guild}` : ''}`);
    }

    public clearLordHighlight() {
        const cardId = this.lordsStock.items[0]?.id;
        cardId && document.getElementById(`${this.lordsStock.container_div.id}_item_${cardId}`).classList.remove('highlight');
    }

    public highlightLocation() {
        const cardId = this.locationsStock.items[0]?.id;
        cardId && document.getElementById(`${this.locationsStock.container_div.id}_item_${cardId}`).classList.add('highlight');
    }
}