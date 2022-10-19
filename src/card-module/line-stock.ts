class LineStock<T> extends CardStock<T> {
    constructor(protected manager: CardManager<T>, protected element: HTMLElement, wrap: 'wrap' | 'nowrap' = 'wrap', direction: 'row' | 'column' = 'row', center: boolean = true, gap: string = '8px') {
        super(manager, element);
        element.dataset.center = center.toString();
        element.style.setProperty('--wrap', wrap);
        element.style.setProperty('--direction', direction);
        element.style.setProperty('--gap', gap);
    }
}