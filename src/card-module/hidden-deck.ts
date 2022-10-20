class HiddenDeck<T> extends CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement, empty: boolean = false) {
        super(manager, element);
        element.classList.add('hidden-deck');
        this.setEmpty(empty);

        this.element.appendChild(this.manager.createCardElement({ id: `${element.id}-hidden-deck-back` } as any, false));
    }

    public setEmpty(empty: boolean) {
        this.element.dataset.empty = empty.toString();
    }

    public addCard(card: T, animation?: CardAnimation<T>, visible: boolean = false): Promise<boolean> {
        return super.addCard(card, animation, visible);
    }
}