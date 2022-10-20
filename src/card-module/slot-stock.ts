class SlotStock<T> extends LineStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement, settings?: LineStockSettings) {
        super(manager, element, settings);
        element.classList.add('slot-stock');
    }
}