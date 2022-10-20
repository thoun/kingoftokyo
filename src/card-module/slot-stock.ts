interface SlotStockSettings<T> extends LineStockSettings {
    slotsIds: SlotId[];
    slotClasses?: string[];
    mapCardToSlot?: (card: T) => SlotId;
}

type SlotId = number | string;

interface AddCardToSlotSettings extends AddCardSettings {
    slot?: SlotId;
}

class SlotStock<T> extends LineStock<T> {
    protected slots: HTMLDivElement[] = [];
    protected mapCardToSlot?: (card: T) => SlotId;

    constructor(protected manager: CardManager<T>, protected element: HTMLElement, settings: SlotStockSettings<T>) {
        super(manager, element, settings);
        element.classList.add('slot-stock');

        this.mapCardToSlot = settings.mapCardToSlot;
        settings.slotsIds.forEach(slotId => {
            this.createSlot(slotId, settings.slotClasses);
        });
    }

    protected createSlot(slot: SlotId, slotClasses?: string[]) {
        this.slots[slot] = document.createElement("div");
        this.element.appendChild(this.slots[slot]);
        this.slots[slot].classList.add(...['slot', ...(slotClasses ?? [])]);
    }

    public addCard(card: T, animation?: CardAnimation<T>, settings?: AddCardToSlotSettings): Promise<boolean> {
        const slotId = settings?.slot ?? this.mapCardToSlot?.(card);
        if (!slotId) {
            throw new Error(`Impossible to add card to slot : no SlotId. Add slotId to settings or set mapCardToSlot to SlotCard constructor.`);
        }
        if (!this.slots[slotId]) {
            throw new Error(`Impossible to add card to slot "${slotId}" : slot "${slotId}" doesn't exists.`);
        }

        const newSettings = {
            ...settings,
            forceToElement: this.slots[slotId],
        };
        return super.addCard(card, animation, newSettings);
    }

    protected cardElementInStock(element: HTMLElement): boolean {
        return element?.parentElement.parentElement == this.element;
    }
}