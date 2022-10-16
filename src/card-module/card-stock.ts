interface CardAnimation<T> {
    fromStock?: CardStock<T>;
    fromElement?: HTMLElement;
    originalSide?: 'front' | 'back';
}

class CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement) {
        manager.addStock(this);
        element.classList.add('card-stock', this.constructor.name.split(/(?=[A-Z])/).join('-').toLowerCase());
    }

    public addOrUpdateCard(card: T, animation?: CardAnimation<T>) {
        const element = this.manager.getCardElement(card);
        this.element.appendChild(element);

        if (animation) {
            if (animation.fromStock) {
                // TODO
            } else if (animation.fromElement && !element.closest(`#${animation.fromElement.id}`)) {
                this.slideFromElement(element, animation.fromElement, animation.originalSide);
            }
        }

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
        this.manager.removeCard(card);
        this.onCardRemoved(card);
    }

    public onCardRemoved(card: T) {
    }

    protected slideFromElement(element: HTMLElement, fromElement: HTMLElement, originalSide: 'front' | 'back') {
        const originBR = fromElement.getBoundingClientRect();
        
        if (document.visibilityState !== 'hidden' && !(this.manager.game as any).instantaneousMode) {
            const destinationBR = element.getBoundingClientRect();
    
            const deltaX = destinationBR.left - originBR.left;
            const deltaY = destinationBR.top - originBR.top;
    
            element.style.zIndex = '10';
            element.style.transform = `translate(${-deltaX}px, ${-deltaY}px)`;

            const side = element.dataset.side;
            if (originalSide && originalSide != side) {
                const cardSides = element.getElementsByClassName('card-sides')[0] as HTMLDivElement;
                cardSides.style.transition = 'none';
                element.dataset.side = originalSide;
                setTimeout(() => {
                    cardSides.style.transition = null;
                    element.dataset.side = side;
                });
            }
    
            setTimeout(() => {
                element.style.transition = `transform 0.5s linear`;
                element.style.transform = null;
            });
            setTimeout(() => {
                element.style.zIndex = null;
                element.style.transition = null;
                element.style.position = null;
            }, 600);
        }
    }
}

class HiddenDeck<T> extends CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement, empty: boolean = false) {
        super(manager, element);
        this.setEmpty(empty);

        this.element.appendChild(this.manager.getCardElement({ id: `${element.id}-hidden-deck-back` } as any, false));
    }

    public setEmpty(empty: boolean) {
        this.element.dataset.empty = empty.toString();
    }
}

class VisibleDeck<T> extends CardStock<T> {
    private currentCard: T;
    constructor(protected manager: CardManager<T>, protected element: HTMLElement) {
        super(manager, element);
    }

    public addOrUpdateCard(card: T, animation?: CardAnimation<T>) {
        if (this.currentCard) {
            const currentCard = this.currentCard;
            document.getElementById(this.manager.getId(currentCard)).classList.add('under');
            setTimeout(() => this.removeCard(currentCard), 600);
        }
        this.currentCard = card;
        super.addOrUpdateCard(card, animation);
    }
}