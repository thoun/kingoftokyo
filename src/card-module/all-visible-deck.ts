class AllVisibleDeck<T> extends CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement, width: string, height: string, shift: string) {
        super(manager, element);
        element.style.setProperty('--width', width);
        element.style.setProperty('--height', height);
        element.style.setProperty('--shift', shift);
    }        

    public addCard(card: T, animation?: CardAnimation<T>) {
        const order = this.cards.length;
        super.addCard(card, animation);
        
        const cardId = this.manager.getId(card);
        const cardDiv = document.getElementById(cardId);
        cardDiv.style.setProperty('--order', ''+order);

        this.element.style.setProperty('--tile-count', ''+this.cards.length);
    }

    public addCards(cards: T[], animation?: CardAnimation<T>, shift: number | boolean = false) {
        cards.forEach(card => this.addCard(card, animation));
    }

    public setOpened(opened: boolean) {
        this.element.classList.toggle('opened', opened);
    }

    public cardRemoved(card: T) {
        super.cardRemoved(card);
        this.cards.forEach((c, index) => {
            const cardId = this.manager.getId(c);
            const cardDiv = document.getElementById(cardId)
            cardDiv.style.setProperty('--order', ''+index);
        });
        this.element.style.setProperty('--tile-count', ''+this.cards.length);
    }
}