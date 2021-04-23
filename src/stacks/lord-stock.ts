const LORD_OVERLAP_WIDTH = 35;
const LORD_OVERLAP_HEIGHT = 35;

function updateDisplay(from: string) {
    if (!$(this.control_name)) {
        return;
    }
    let topDestination = 0;
    let leftDestination = 0;

    const itemWidth = this.item_width;
    const itemHeight = this.item_height;

    const topDestinations = [];
    const leftDestinations = [];

    this.items.forEach((item, iIndex) => {;
        if (typeof item.loc == "undefined") {
            topDestinations[iIndex] = iIndex * LORD_OVERLAP_HEIGHT;
            leftDestinations[iIndex] = (this.items.length - iIndex - 1) * LORD_OVERLAP_WIDTH;
        }
    });

    for (let i in this.items) {
        topDestination = topDestinations[i];
        leftDestination = leftDestinations[i];

        const item = this.items[i];
        const itemDivId = this.getItemDivId(item.id);

        let $itemDiv = $(itemDivId);
        if ($itemDiv) {
            if (typeof item.loc == "undefined") {
                dojo.fx.slideTo({
                    node: $itemDiv,
                    top: topDestination,
                    left: leftDestination,
                    duration: 1000,
                    unit: "px"
                }).play();
            } else {
                this.page.slideToObject($itemDiv, item.loc, 1000).play();
            }

            dojo.style($itemDiv, "width", itemWidth + "px");
            dojo.style($itemDiv, "height", itemHeight + "px");
            //dojo.style($itemDiv, "z-index", i);
            // dojo.style($itemDiv, "background-size", "100% auto");
        } else {
            const type = this.item_type[item.type];
            if (!type) {
                console.error("Stock control: Unknow type: " + type);
            }
            if (typeof itemDivId == "undefined") {
                console.error("Stock control: Undefined item id");
            } else {
                if (typeof itemDivId == "object") {
                    console.error("Stock control: Item id with 'object' type");
                    console.error(itemDivId);
                }
            }
            let additional_style = "";
            const jstpl_stock_item_template = dojo.trim(dojo.string.substitute(this.jstpl_stock_item, {
                id: itemDivId,
                width: itemWidth,
                height: itemHeight,
                top: topDestination,
                left: leftDestination,
                image: type.image,
                position: '', //"z-index:" + i,
                extra_classes: this.extraClasses,
                additional_style: additional_style
            }));
            dojo.place(jstpl_stock_item_template, this.control_name);
            $itemDiv = $(itemDivId);
            if (typeof item.loc != "undefined") {
                this.page.placeOnObject($itemDiv, item.loc);
            }
            if (this.selectable == 0) {
                dojo.addClass($itemDiv, "stockitem_unselectable");
            }
            dojo.connect($itemDiv, "onclick", this, "onClickOnItem");
            if (Number(type.image_position) !== 0) {
                let backgroundPositionWidth = 0;
                let backgroundPositionHeight = 0;
                if (this.image_items_per_row) {
                    const rowNumber = Math.floor(type.image_position / this.image_items_per_row);
                    if (!this.image_in_vertical_row) {
                        backgroundPositionWidth = (type.image_position - (rowNumber * this.image_items_per_row)) * 100;
                        backgroundPositionHeight = rowNumber * 100;
                    } else {
                        backgroundPositionHeight = (type.image_position - (rowNumber * this.image_items_per_row)) * 100;
                        backgroundPositionWidth = rowNumber * 100;
                    }
                    dojo.style($itemDiv, "backgroundPosition", "-" + backgroundPositionWidth + "% -" + backgroundPositionHeight + "%");
                } else {
                    backgroundPositionWidth = type.image_position * 100;
                    dojo.style($itemDiv, "backgroundPosition", "-" + backgroundPositionWidth + "% 0%");
                }
            }
            if (this.onItemCreate) {
                this.onItemCreate($itemDiv, item.type, itemDivId);
            }
            if (typeof from != "undefined") {
                this.page.placeOnObject($itemDiv, from);
                if (typeof item.loc == "undefined") {
                    let anim = dojo.fx.slideTo({
                        node: $itemDiv,
                        top: topDestination,
                        left: leftDestination,
                        duration: 1000,
                        unit: "px"
                    });
                    anim = this.page.transformSlideAnimTo3d(anim, $itemDiv, 1000, null);
                    anim.play();
                } else {
                    this.page.slideToObject($itemDiv, item.loc, 1000).play();
                }
            } else {
                dojo.style($itemDiv, "opacity", 0);
                dojo.fadeIn({
                    node: $itemDiv
                }).play();
            }
        }
    }
    /*const controlHeight = (itemHeight + itemMargin) + (this.items.length - 1) * LORD_OVERLAP_HEIGHT;
    const controlWidth = (itemWidth + itemMargin) + (this.items.length - 1) * LORD_OVERLAP_WIDTH;
    if (this.autowidth) {
        if (controlWidth > 0) {
            dojo.style(this.control_name, "width", controlWidth + "px");
        }
        if (controlHeight > 0) {
            dojo.style(this.control_name, "height", controlHeight + "px");
        }
        
    }

    dojo.style(this.control_name, "minHeight", (itemHeight + itemMargin) + "px");*/
}

class LordStock {
    private stock: Stock;
    protected selectable: boolean;

    constructor(private lordsStacks: LordsStacks, private guild: number, visibleLords: Lord[]) {
        this.stock = new ebg.stock() as Stock;
        this.stock.setSelectionAppearance('class');
        this.stock.selectionClass = 'no-visible-selection';
        this.stock.create(this.lordsStacks.game, this.div, LORD_WIDTH, LORD_HEIGHT);
        this.stock.setSelectionMode(0);
        this.stock.onItemCreate = dojo.hitch(this, 'setupNewLordCard'); 
        this.stock.image_items_per_row = 16;
        this.stock.updateDisplay = (from: string) => {
            updateDisplay.apply(this.stock, [from]);
            this.updateSize();
        }
        dojo.connect(this.stock, 'onChangeSelection', this, 'click');
        setupLordCards([this.stock]);

        visibleLords.forEach(lord => this.stock.addToStockWithId(this.lordsStacks.getCardUniqueId(lord), `${lord.id}`));
        //this.updateSize();

        this.div.addEventListener('click', () => this.click());
    }

    getStock(): Stock {
        return this.stock;
    }

    addLords(lords: Lord[]): void {
        lords.forEach(lord => this.stock.addToStockWithId(this.lordsStacks.getCardUniqueId(lord), `${lord.id}`));
    }

    removeAllTo(to: string): void {
        this.stock.removeAllTo(to);
    }

    private updateSize() {
        const size = this.stock.items.length;
        this.div.style.width = `${LORD_WIDTH + (Math.max(size - 1, 0) * LORD_OVERLAP_WIDTH)}px`;
        this.div.style.height = `${LORD_HEIGHT + (Math.max(size - 1, 0) * LORD_OVERLAP_HEIGHT)}px`;
        this.div.style.display = size ? 'inline-block' : 'none';
    }

    get div() {
        return document.getElementById(`lord-visible-stock${this.guild}`);
    }

    public setSelectable(selectable: boolean) {
        this.selectable = selectable;
        const action = selectable ? 'add' : 'remove';
        this.div.classList[action]('selectable');
        this.stock.setSelectionMode(selectable ? 2 : 0);
    }

    private click() {
        if (!this.selectable) {
            return;
        }
        this.lordsStacks.game.lordStockPick(this.guild);
    }

    public setupNewLordCard(card_div: HTMLDivElement, card_type_id: number, card_id: string) {
        let message = getLordTooltip(card_type_id);

        if (message) {
            (this.lordsStacks.game as any).addTooltipHtml(card_div.id, message);
        }
    }
}