abstract class AbstractStacks<T extends Card> {
    protected selectable: boolean;
    protected pickSelectable: boolean;
    protected pickStock: Stock;
    private max: number = 3;
    private allHidden: boolean = false;

    constructor(public game: ConspiracyGame) {
    }

    protected abstract get pileDiv(): HTMLDivElement;
    protected abstract get pickDiv(): HTMLDivElement;
    protected abstract getCardUniqueId(card: T): number;
    protected abstract pickClick(control_name: string, item_id: string): void;
    public abstract getStockContaining(cardId: string): Stock | null;

    public setSelectable(selectable: boolean, limitToHidden?: number, allHidden?: boolean) {
        this.selectable = selectable;
        const action = selectable ? 'add' : 'remove';
        this.pileDiv.classList[action]('selectable');

        const buttons = Array.from(this.pileDiv.getElementsByClassName('button'));

        if (limitToHidden) {
            const adjustedLimitToHidden = Math.min(this.max, limitToHidden);
            if (selectable) {
                buttons.filter((button: HTMLDivElement) => parseInt(button.dataset.number) !== adjustedLimitToHidden)
                    .forEach(button => button.classList.add('hidden'));
            }
        }

        if (!selectable) {
            buttons.forEach(button => button.classList.remove('hidden'));
        }

        // if player has all hidden location, we replace the 3 buttons by one special for the rest of the game
        if (allHidden && buttons.length > 1) {
            this.allHidden = true;
            document.getElementById('location-hidden-pile').innerHTML = '<div class="button eye location-hidden-pile-eye-tooltip" data-number="0"></div>';

            (this.game as any).addTooltipHtml('location-hidden-pile-eye-tooltip', _("As you have the See all deck location, you can pick a location from all deck, but you cannot pick visible locations."));
        }
    }

    public setMax(max: number) {
        this.max = max;

        if (max === 0) {
            this.pileDiv.style.visibility = 'hidden';
        } else if (!this.allHidden && max < 3) {
            const buttons = Array.from(this.pileDiv.getElementsByClassName('button'));
            buttons.filter((button: HTMLDivElement) => parseInt(button.dataset.number) > max)
                .forEach(button => button.classList.add('max'));
        }
        
    }

    public setPick(showPick: boolean, pickSelectable: boolean, collection?: T[]) {
        if (collection) {
            this.pickStock.items.filter(item => !collection.some(i => item.id === `${i.id}`)).forEach(
                item => this.pickStock.removeFromStockById(`${item.id}`)
            );
            setTimeout(() => this.pickStock.updateDisplay(), 100);
        }

        this.pickDiv.style.display = showPick ? 'block' : 'none';
        const action = pickSelectable ? 'add' : 'remove';
        this.pickDiv.classList[action]('selectable');
        this.pickSelectable = pickSelectable;
        collection?.filter(item => !this.pickStock.items.some(i => i.id === `${item.id}`)).forEach(item => {
            const from = this.getStockContaining(`${item.id}`);

            if (from) {
                moveToAnotherStock(from, this.pickStock, this.getCardUniqueId(item), `${item.id}`);
            } else {
                this.pickStock.addToStockWithId(this.getCardUniqueId(item), `${item.id}`, this.pileDiv.id);
            }
        });   
    }

    protected getGuildStock(guild: number): Stock {
        throw new Error("Must be overriden");
    }

    protected setPickStockClick() {
        dojo.connect(this.pickStock, 'onChangeSelection', this, 'pickClick' );
    }
}