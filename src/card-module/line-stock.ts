class LineStock<T> extends CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement, wrap: boolean = true, direction: 'row' | 'column' = 'row', center: boolean = true) {
        super(manager, element);
        element.dataset.wrap = wrap.toString();
        element.dataset.direction = direction;
        element.dataset.center = center.toString();
    }
}