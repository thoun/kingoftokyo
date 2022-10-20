interface CardAnimation<T> {
    fromStock?: CardStock<T>;
    fromElement?: HTMLElement;
    originalSide?: 'front' | 'back';
    rotationDelta?: number,
}

type CardSelectionMode = 'none' | 'single' | 'multiple';

class CardStock<T> {
    protected cards: T[] = [];
    protected selectedCards: T[] = [];
    protected selectionMode: CardSelectionMode = 'none';

    public onSelectionChange: (selection: T[], lastChange: T | null) => void;
    public onCardClick: (card: T) => void;

    constructor(protected manager: CardManager<T>, protected element: HTMLElement) {
        manager.addStock(this);
        element?.classList.add('card-stock'/*, this.constructor.name.split(/(?=[A-Z])/).join('-').toLowerCase()* doesn't work in production because of minification */);
        this.bindClick();
    }

    public getCards(): T[] {
        return this.cards.slice();
    }

    public isEmpty(): boolean {
        return !this.cards.length;
    }

    public getSelection(): T[] {
        return this.selectedCards.slice();
    }

    public contains(card: T): boolean {
        return this.cards.some(c => this.manager.getId(c) == this.manager.getId(card));
    }
    // TODO keep only one ?
    protected cardInStock(card: T): boolean {
        const element = document.getElementById(this.manager.getId(card));
        return element ? this.cardElementInStock(element) : false;
    }

    protected cardElementInStock(element: HTMLElement): boolean {
        return element?.parentElement == this.element;
    }

    public getCardElement(card: T): HTMLElement {
        return document.getElementById(this.manager.getId(card));
    }

    public addCard(card: T, animation?: CardAnimation<T>, visible: boolean = true): Promise<boolean> {
        if (this.cardInStock(card)) {
            return;
        }

        let promise: Promise<boolean>;

        // we check if card is in stock then we ignore animation
        const currentStock = this.manager.getCardStock(card);

        if (currentStock?.cardInStock(card)) {
            let element = document.getElementById(this.manager.getId(card));
            promise = this.moveFromOtherStock(card, element, { ...animation, fromStock: currentStock,  });
            element.dataset.side = visible ? 'front' : 'back';
        } else if (animation?.fromStock && animation.fromStock.cardInStock(card)) {
            let element = document.getElementById(this.manager.getId(card));
            promise = this.moveFromOtherStock(card, element, animation);
        } else {
            const element = this.manager.createCardElement(card, visible);
            promise = this.moveFromElement(card, element, animation);
        }

        this.setSelectableCard(card, this.selectionMode != 'none');

        this.cards.push(card);

        return promise;
    }

    protected moveFromOtherStock(card: T, cardElement: HTMLElement, animation: CardAnimation<T>): Promise<boolean> {
        let promise: Promise<boolean>;

        this.element.appendChild(cardElement);
        cardElement.classList.remove('selectable', 'selected');
        promise = this.slideFromElement(cardElement, animation.fromStock.element, animation.originalSide, animation.rotationDelta);
        animation.fromStock.removeCard(card);

        return promise;
    }

    protected moveFromElement(card: T, cardElement: HTMLElement, animation: CardAnimation<T>): Promise<boolean> {
        let promise: Promise<boolean>;

        this.element.appendChild(cardElement);
    
        if (animation) {
            if (animation.fromStock) {
                promise = this.slideFromElement(cardElement, animation.fromStock.element, animation.originalSide, animation.rotationDelta);
                animation.fromStock.removeCard(card);
            } else if (animation.fromElement) {
                promise = this.slideFromElement(cardElement, animation.fromElement, animation.originalSide, animation.rotationDelta);
            }
        }

        return promise;
    }

    public addCards(cards: T[], animation?: CardAnimation<T>, shift: number | boolean = false) {
        if (shift === true) {
            // TODO chain promise
            shift = 800;
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
        if (this.cardInStock(card)) {
            this.manager.removeCard(card);
        }
        this.cardRemoved(card);
    }

    public cardRemoved(card: T) {
        const index = this.cards.findIndex(c => this.manager.getId(c) == this.manager.getId(card));
        if (index !== -1) {
            this.cards.splice(index, 1);
        }
    }

    public removeAll() {
        const cards = this.getCards(); // use a copy of the array as we iterate and modify it at the same time
        cards.forEach(card => this.removeCard(card));
    }

    protected setSelectableCard(card: T, selectable: boolean) {
        const element = this.getCardElement(card);
        element.classList.toggle('selectable', selectable);
    }

    public setSelectionMode(selectionMode: CardSelectionMode) {
        this.cards.forEach(card => this.setSelectableCard(card, selectionMode != 'none'));
        this.element.classList.toggle('selectable', selectionMode != 'none');
        this.selectionMode = selectionMode;
    }

    public selectCard(card: T, silent: boolean = false) {
        if (this.selectionMode == 'none') {
            return;
        }
        
        if (this.selectionMode === 'single') {
            this.cards.filter(c => this.manager.getId(c) != this.manager.getId(card)).forEach(c => this.unselectCard(c, true));
        }

        const element = this.getCardElement(card);
        element.classList.add('selected');
        this.selectedCards.push(card);
        
        if (!silent) {
            this.onSelectionChange?.(this.selectedCards.slice(), card);
        }
    }

    public unselectCard(card: T, silent: boolean = false) {
        const element = this.getCardElement(card);
        element.classList.remove('selected');

        const index = this.selectedCards.findIndex(c => this.manager.getId(c) == this.manager.getId(card));
        if (index !== -1) {
            this.selectedCards.splice(index, 1);
        }
        
        if (!silent) {
            this.onSelectionChange?.(this.selectedCards.slice(), card);
        }
    }

    public selectAll(silent: boolean = false) {
        if (this.selectionMode == 'none') {
            return;
        }

        this.cards.forEach(c => this.selectCard(c, true));
        
        if (!silent) {
            this.onSelectionChange?.(this.selectedCards.slice(), null);
        }
    }

    public unselectAll(silent: boolean = false) {
        const cards = this.getCards(); // use a copy of the array as we iterate and modify it at the same time
        cards.forEach(c => this.unselectCard(c, true));
        
        if (!silent) {
            this.onSelectionChange?.(this.selectedCards.slice(), null);
        }
    }

    protected bindClick() {
        this.element?.addEventListener('click', event => {
            const cardDiv = (event.target as HTMLElement).closest('.card');
            if (!cardDiv) {
                return;
            }
            const card = this.cards.find(c => this.manager.getId(c) == cardDiv.id);
            if (!card) {
                return;
            }
            this.cardClick(card);
        });
    }

    protected cardClick(card: T) {
        if (this.selectionMode != 'none') {
            const alreadySelected = this.selectedCards.some(c => this.manager.getId(c) == this.manager.getId(card));

            if (alreadySelected) {
                this.unselectCard(card);
            } else {
                this.selectCard(card);
            }
        }

        this.onCardClick?.(card);
    }

    protected slideFromElement(element: HTMLElement, fromElement: HTMLElement, originalSide: 'front' | 'back', rotationDelta: number): Promise<boolean> {
        const promise = new Promise<boolean>((success) => {

            const originBR = fromElement.getBoundingClientRect();
            
            if (document.visibilityState !== 'hidden' && !(this.manager.game as any).instantaneousMode) {
                const destinationBR = element.getBoundingClientRect();
        
                const deltaX = (destinationBR.left + destinationBR.right)/2 - (originBR.left + originBR.right)/2;
                const deltaY = (destinationBR.top + destinationBR.bottom)/2 - (originBR.top+ originBR.bottom)/2;
        
                element.style.zIndex = '10';
                element.style.transform = `translate(${-deltaX}px, ${-deltaY}px) rotate(${rotationDelta ?? 0}deg)`;

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
                    success(true);
                }, 600);
            } else {
                success(true);
            }
        });

        return promise;
    }
}
