interface CardManagerSettings<T> {
    getId?: (card: T) => string;
    setupDiv?: (card: T, element: HTMLDivElement) => void;
    setupFrontDiv?: (card: T, element: HTMLDivElement) => void;
    setupBackDiv?: (card: T, element: HTMLDivElement) => void;
}

class CardManager<T> {
    private stocks: CardStock<T>[] = [];

    constructor(public game: Game, private settings: CardManagerSettings<T>) {
    }

    public addStock(stock: CardStock<T>) {
        this.stocks.push(stock);
    }

    public getId(card: T) {
        return this.settings.getId?.(card) ?? `card-${(card as any).id}`;
    }

    public getCardElement(card: T, visible: boolean = true): HTMLDivElement {
        const id = this.getId(card);
        const side = visible ? 'front' : 'back';

        // TODO check if exists
        const element = document.createElement("div");
        element.id = id;
        element.dataset.side = ''+side;
        element.innerHTML = `
            <div class="card-sides">
                <div class="card-side front">
                </div>
                <div class="card-side back">
                </div>
            </div>
        `;
        element.classList.add('card');
        document.body.appendChild(element);
        this.settings.setupDiv?.(card, element);
        if (visible) {
            this.settings.setupFrontDiv?.(card, element.getElementsByClassName('front')[0] as HTMLDivElement);
        }
        this.settings.setupBackDiv?.(card, element.getElementsByClassName('back')[0] as HTMLDivElement);
        document.body.removeChild(element);
        return element;
    }

    public createMoveOrUpdateCard(card: Card, destinationId: string, instant: boolean = false, from: string = null) {
        /*const existingDiv = document.getElementById(`card-${card.id}`);
        const side = card.category ? 'front' : 'back';
        if (existingDiv) {
            (this.game as any).removeTooltip(`card-${card.id}`);
            const oldType = Number(existingDiv.dataset.category);
            existingDiv.classList.remove('selectable', 'selected', 'disabled');

            if (existingDiv.parentElement.id != destinationId) {
                if (instant) {
                    document.getElementById(destinationId).appendChild(existingDiv);
                } else {
                    slideToObjectAndAttach(this.game, existingDiv, destinationId);
                }
            }

            existingDiv.dataset.side = ''+side;
            if (!oldType && card.category) {
                this.setVisibleInformations(existingDiv, card);
            } else if (oldType && !card.category) {
                if (instant) {
                    this.removeVisibleInformations(existingDiv);
                } else {
                    setTimeout(() => this.removeVisibleInformations(existingDiv), 500); // so we don't change face while it is still visible
                }
            }
            if (card.category) {
                this.game.setTooltip(existingDiv.id, this.getTooltip(card.category, card.family) + `<br><br><i>${this.COLORS[card.color]}</i>`);
            }
        } else {
            const div = document.createElement('div');
            div.id = `card-${card.id}`;
            div.classList.add('card');
            div.dataset.id = ''+card.id;
            div.dataset.side = ''+side;

            div.innerHTML = `
                <div class="card-sides">
                    <div class="card-side front">
                    </div>
                    <div class="card-side back">
                    </div>
                </div>
            `;
            document.getElementById(destinationId).appendChild(div);
            div.addEventListener('click', () => this.game.onCardClick(card));

            if (from) {
                const fromCardId = document.getElementById(from).id;
                slideFromObject(this.game, div, fromCardId);
            }

            if (card.category) {
                this.setVisibleInformations(div, card);
                if (!destinationId.startsWith('help-')) {
                    this.game.setTooltip(div.id, this.getTooltip(card.category, card.family) + `<br><br><i>${this.COLORS[card.color]}</i>`);
                }
            }
        }*/
    }

    public updateCard(card: T) {
        /*const existingDiv = document.getElementById(`card-${card.id}`);
        const side = card.category ? 'front' : 'back';
        if (existingDiv) {
            (this.game as any).removeTooltip(`card-${card.id}`);
            const oldType = Number(existingDiv.dataset.category);
            existingDiv.dataset.side = ''+side;
            if (!oldType && card.category) {
                this.setVisibleInformations(existingDiv, card);
            } else if (oldType && !card.category) {
                setTimeout(() => this.removeVisibleInformations(existingDiv), 500); // so we don't change face while it is still visible
            }
            if (card.category) {
                this.game.setTooltip(existingDiv.id, this.getTooltip(card.category, card.family) + `<br><br><i>${this.COLORS[card.color]}</i>`);
            }
        }*/
    }

    public removeCard(card: T) {
        const id = this.getId(card);
        const div = document.getElementById(id);
        if (!div) {
            return;
        }

        div.id = `deleted${id}`;
        // TODO this.removeVisibleInformations(div);
        div.remove();
    }
}