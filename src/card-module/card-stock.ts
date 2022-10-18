interface CardAnimation<T> {
    fromStock?: CardStock<T>;
    fromElement?: HTMLElement;
    originalSide?: 'front' | 'back';
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
        element?.classList.add('card-stock', this.constructor.name.split(/(?=[A-Z])/).join('-').toLowerCase());
        this.bindClick();
    }

    public getCards(): T[] {
        return this.cards;
    }

    public isEmpty(): boolean {
        return !this.cards.length;
    }

    public getSelection(): T[] {
        return this.selectedCards;
    }

    public addCard(card: T, animation?: CardAnimation<T>) {
        let moved = false;
        console.log(card, animation);
        if (animation?.fromStock) {
            let element = document.getElementById(this.manager.getId(card));
            if (element?.parentElement == animation.fromStock.element) {
                this.element.appendChild(element);
                this.slideFromElement(element, animation.fromStock.element, animation.originalSide);
                animation.fromStock.removeCard(card);
                moved = true;
            }
        }

        if (!moved) {
            const element = this.manager.getCardElement(card);
            this.element.appendChild(element);
    
            if (animation) {
                if (animation.fromStock) {
                    this.slideFromElement(element, animation.fromStock.element, animation.originalSide);
                    animation.fromStock.removeCard(card);
                } else if (animation.fromElement && element.closest(`#${animation.fromElement.id}`)) {
                    this.slideFromElement(element, animation.fromElement, animation.originalSide);
                }
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
        let element = document.getElementById(this.manager.getId(card));
        if (element && element.parentElement == this.element) {
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

    public setSelectionMode(selectionMode: CardSelectionMode) {
        this.cards.forEach(card => {
            const element = this.manager.getCardElement(card);
            element.classList.toggle('selectable', selectionMode != 'none');
        });

        this.selectionMode = selectionMode;
    }

    public selectCard(card: T, silent: boolean = false) {
        if (this.selectionMode == 'none') {
            return;
        }
        
        if (this.selectionMode === 'single') {
            this.cards.filter(c => this.manager.getId(c) != this.manager.getId(card)).forEach(c => this.unselectCard(c, true));
        }

        this.cards.forEach(card => {
            const element = this.manager.getCardElement(card);
            element.classList.add('selected');
            this.selectedCards.push(card);
        });
        
        if (!silent) {
            this.onSelectionChange?.(this.selectedCards.slice(), card);
        }
    }

    public unselectCard(card: T, silent: boolean = false) {
        const element = this.manager.getCardElement(card);
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
        this.cards.forEach(c => this.unselectCard(c, true));
        
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

    protected slideFromElement(element: HTMLElement, fromElement: HTMLElement, originalSide: 'front' | 'back', rotationDelta: number = 0) {
        const originBR = fromElement.getBoundingClientRect();
        
        if (document.visibilityState !== 'hidden' && !(this.manager.game as any).instantaneousMode) {
            const destinationBR = element.getBoundingClientRect();
    
            const deltaX = (destinationBR.left + destinationBR.right)/2 - (originBR.left + originBR.right)/2;
            const deltaY = (destinationBR.top + destinationBR.bottom)/2 - (originBR.top+ originBR.bottom)/2;
    
            element.style.zIndex = '10';
            element.style.transform = `translate(${-deltaX}px, ${-deltaY}px) rotate(${rotationDelta}deg)`;

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
