class LocationsStacks extends AbstractStacks<Location> {
    visibleLocationsStock: Stock;

    constructor(game: ConspiracyGame, visibleLocations: Location[], pickLocations: Location[]) {
        super(game);

        this.pileDiv.addEventListener('click', (e: MouseEvent) => this.onHiddenLocationClick(e));
        Array.from(this.pileDiv.getElementsByClassName('button')).forEach(button => button.addEventListener('click', (e: MouseEvent) => this.onHiddenLocationClick(e)));

        this.visibleLocationsStock = new ebg.stock() as Stock;
        this.visibleLocationsStock.setSelectionAppearance('class');
        this.visibleLocationsStock.selectionClass = 'no-visible-selection';
        this.visibleLocationsStock.create(this.game, $('location-visible-stock'), LOCATION_WIDTH, LOCATION_HEIGHT);
        this.visibleLocationsStock.setSelectionMode(0);
        this.visibleLocationsStock.onItemCreate = dojo.hitch( this, 'setupNewLocationCard' ); 
        this.visibleLocationsStock.image_items_per_row = 13;
        dojo.connect(this.visibleLocationsStock, 'onChangeSelection', this, 'onVisibleLocationClick');
        
        this.pickStock = new ebg.stock() as Stock;
        this.pickStock.setSelectionAppearance('class');
        this.pickStock.selectionClass = 'no-visible-selection';
        this.pickStock.create(this.game, this.pickDiv.children[0], LOCATION_WIDTH, LOCATION_HEIGHT);
        this.pickStock.centerItems = true;
        this.pickStock.onItemCreate = dojo.hitch(this, 'setupNewLocationCard'); 
        this.pickStock.image_items_per_row = 13;
        this.setPickStockClick();

        setupLocationCards([this.visibleLocationsStock, this.pickStock]);        

        visibleLocations.forEach(location => this.visibleLocationsStock.addToStockWithId(this.getCardUniqueId(location), `${location.id}`));
        pickLocations.forEach(location => this.pickStock.addToStockWithId(this.getCardUniqueId(location), `${location.id}`));
        
        (this.game as any).addTooltipHtmlToClass('location-hidden-pile-tooltip', _("Reveal 1 to 3 hidden locations. Choose one, the others are discarded"));
    }

    get pileDiv(): HTMLDivElement {
        return document.getElementById('location-hidden-pile') as HTMLDivElement;
    }

    get pickDiv(): HTMLDivElement {
        return document.getElementById('location-pick') as HTMLDivElement;
    }

    public getStockContaining(locationId: string): Stock | null {
        if (this.pickStock.items.some(item => item.id === locationId)) {
            return this.pickStock;
        } else if (this.visibleLocationsStock.items.some(item => item.id === locationId)) {
            return this.visibleLocationsStock;
        }
        return null;
    }

    public setSelectable(selectable: boolean, limitToHidden?: number, allHidden?: boolean) {
        super.setSelectable(selectable, limitToHidden, allHidden);

        const visibleSelectable = selectable && !allHidden;
        this.visibleLocationsStock.setSelectionMode(visibleSelectable ? 1 : 0); 
        const action = visibleSelectable && this.visibleLocationsStock.items.length ? 'add' : 'remove';
        this.visibleLocationsStock.container_div.classList[action]('selectable');
    }

    public discardVisible() {
        this.visibleLocationsStock.removeAllTo('location-hidden-pile');
    }

    public discardPick(locations: Location[]) {
        locations.forEach(location => moveToAnotherStock(this.pickStock, this.visibleLocationsStock, this.getCardUniqueId(location), `${location.id}`));
    }

    protected getCardUniqueId(location: Location) {
        return getUniqueId(location.type, location.passivePowerGuild ?? 0);
    }

    protected pickClick(control_name: string, item_id: string) {
        this.game.locationPick(Number(item_id));
    }

    public setupNewLocationCard( card_div: HTMLDivElement, card_type_id: number, card_id: string ) {
        let message = getLocationTooltip(card_type_id);

        if (message) {
            (this.game as any).addTooltipHtml(card_div.id, message);
        }
    }

    public onHiddenLocationClick(event: MouseEvent) {
        if (!this.selectable) {
            return;
        }

        const number = parseInt((event.target as HTMLDivElement).dataset.number);

        if (isNaN(number)) {
            return;
        }

        if(!(this.game as any).checkAction('chooseDeckStack')) {
            return;
        }

        this.game.takeAction('chooseLocationDeckStack', {
            number
        });

        event.stopPropagation();
    }

    public onVisibleLocationClick(control_name: string, item_id: string) {
        if (!item_id || !(this.game as any).checkAction('chooseVisibleLocation')) {
            return;
        }

        this.game.takeAction('chooseVisibleLocation', {
            id: item_id
        });
    }

}