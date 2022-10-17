interface CardAnimation<T> {
    fromStock?: CardStock<T>;
    fromElement?: HTMLElement;
    originalSide?: 'front' | 'back';
}

class CardStock<T> {
    protected cards: T[] = [];

    constructor(protected manager: CardManager<T>, protected element: HTMLElement) {
        manager.addStock(this);
        element.classList.add('card-stock', this.constructor.name.split(/(?=[A-Z])/).join('-').toLowerCase());
    }

    public getCards(): T[] {
        return this.cards;
    }

    public isEmpty(): boolean {
        return !this.cards.length;
    }

    public addCard(card: T, animation?: CardAnimation<T>) {
        const element = this.manager.getCardElement(card);
        this.element.appendChild(element);

        if (animation) {
            if (animation.fromStock) {
                // TODO
            } else if (animation.fromElement && !element.closest(`#${animation.fromElement.id}`)) {
                this.slideFromElement(element, animation.fromElement, animation.originalSide);
            }
        }

        this.cards.push(card);
    }

    public addCards(cards: T[], animation?: CardAnimation<T>, shift: number | boolean = false) {
        if (shift === true) {
            // TODO chain promise
            shift = 500;
        }

        if (shift) {
            for (let i=0; i<cards.length; i++) {
                setTimeout(() => this.addCard(cards[i], animation), i * shift);
            }
        } else {
            cards.forEach(card => this.addCard(card, animation));
        }
    }

    public removeCard(card: T) {
        this.manager.removeCard(card);
        this.onCardRemoved(card);
    }

    public onCardRemoved(card: T) {
        const index = this.cards.findIndex(c => this.manager.getId(c) == this.manager.getId(card));
        if (index !== -1) {
            this.cards.splice(index, 1);
        }
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

class LineStock<T> extends CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement, wrap: boolean = true, direction: 'row' | 'column' = 'row', center: boolean = true) {
        super(manager, element);
        element.dataset.wrap = wrap.toString();
        element.dataset.direction = direction;
        element.dataset.center = center.toString();
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
    constructor(protected manager: CardManager<T>, protected element: HTMLElement) {
        super(manager, element);
    }

    public addCard(card: T, animation?: CardAnimation<T>) {
        const currentCard = this.cards[this.cards.length - 1];
        if (currentCard) {
            document.getElementById(this.manager.getId(currentCard)).classList.add('under');
            setTimeout(() => this.removeCard(currentCard), 600);
        }

        super.addCard(card, animation);
    }
}