class VisibleDeck<T> extends CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement) {
        super(manager, element);
        element.classList.add('visible-deck');
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