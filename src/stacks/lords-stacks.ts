class LordsStacks extends AbstractStacks<Lord> {
    private lordsStocks: LordStock[] = [];

    constructor(game: ConspiracyGame, visibleLords: { [spot: number]: Lord[] }, pickLords: Lord[]) {
        super(game);

        this.pileDiv.addEventListener('click', (e: MouseEvent) => this.onHiddenLordsClick(e));
        Array.from(this.pileDiv.getElementsByClassName('button')).forEach(button => button.addEventListener('click', (e: MouseEvent) => this.onHiddenLordsClick(e)));

        GUILD_IDS.forEach(guild => this.lordsStocks[guild] = new LordStock(this, guild, visibleLords[guild]));

        this.pickStock = new ebg.stock() as Stock;
        this.pickStock.setSelectionAppearance('class');
        this.pickStock.selectionClass = 'no-visible-selection';
        this.pickStock.create( this.game, this.pickDiv.children[0], LORD_WIDTH, LORD_HEIGHT );
        this.pickStock.centerItems = true;
        this.pickStock.image_items_per_row = 16;
        this.pickStock.onItemCreate = dojo.hitch(this, 'setupNewLordCard'); 
        setupLordCards([this.pickStock]);
        this.setPickStockClick();
        pickLords.forEach(lord => this.pickStock.addToStockWithId(this.getCardUniqueId(lord), `${lord.id}`));

        (this.game as any).addTooltipHtmlToClass('lord-hidden-pile-tooltip', _("Reveal 1 to 3 hidden lords. Choose one, the others are discarded"));
    }

    get pileDiv(): HTMLDivElement {
        return document.getElementById('lord-hidden-pile') as HTMLDivElement;
    }

    get pickDiv(): HTMLDivElement {
        return document.getElementById('lord-pick') as HTMLDivElement;
    }
    
    public getStockContaining(lordId: string): Stock | null {
        if (this.pickStock.items.some(item => item.id === lordId)) {
            return this.pickStock;
        } else {
            const guild = GUILD_IDS.find(guild => this.lordsStocks[guild].getStock().items.some(item => item.id === lordId));
            if (guild) {
                return this.lordsStocks[guild].getStock();
            }
        }
        return null;
    }

    public discardVisible() {
        GUILD_IDS.forEach(guild => this.lordsStocks[guild].removeAllTo('lord-hidden-pile'));
    }

    public addLords(lords: Lord[]) {
        const guilds = new Set(lords.map(lord => lord.guild));
        guilds.forEach(guild => this.lordsStocks[guild].addLords(lords.filter(lord => lord.guild === guild)));
    }

    public setSelectable(selectable: boolean, limitToHidden: number, allHidden?: boolean) {
        super.setSelectable(selectable, limitToHidden, allHidden);

        if (!selectable || !limitToHidden) {
            this.lordsStocks.forEach(lordStock => lordStock.setSelectable(selectable));
        }
    }

    public hasPickCards(): boolean {
        return this.pickStock.items.length > 0;
    }

    public discardPick(lords: Lord[]) {
        const guilds = new Set(lords.map(lord => lord.guild));

        guilds.forEach(guild => 
            lords.filter(lord => lord.guild === guild).forEach(lord => 
                moveToAnotherStock(this.pickStock, this.lordsStocks[guild].getStock(), this.getCardUniqueId(lord), `${lord.id}`)
            )
        );
    }

    public getCardUniqueId(lord: Lord) {
        return getUniqueId(lord.type, lord.guild);
    }

    protected pickClick(control_name: string, item_id: string) {
        this.game.lordPick(Number(item_id));
    }

    public onHiddenLordsClick(event: MouseEvent) {
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

        this.game.takeAction('chooseLordDeckStack', {
            number
        });

        event.stopPropagation();
    }

    public setupNewLordCard(card_div: HTMLDivElement, card_type_id: number, card_id: string) {
        let message = getLordTooltip(card_type_id);

        if (message) {
            (this.game as any).addTooltipHtml(card_div.id, message);
        }
    }

    protected getGuildStock(guild: number): Stock {
        return this.lordsStocks[guild].getStock();
    }
}