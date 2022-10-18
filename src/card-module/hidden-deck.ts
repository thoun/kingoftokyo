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