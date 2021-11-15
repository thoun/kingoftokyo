const WICKEDNESS_TILES_WIDTH = 132;
const WICKEDNESS_TILES_HEIGHT = 82; // TODOWI
const WICKEDNESS_LEVELS = [3, 6, 10];

class WickednessTiles {
    constructor (private game: KingOfTokyoGame) {}
    
    public setupCards(stocks: Stock[]) {
        stocks.forEach(stock => {
            const orangewickednesstilessurl = `${g_gamethemeurl}img/orange-wickedness-tiles.jpg`;
            [1,2,3,4,5,6,7,8,9,10].forEach((id, index) => {
                stock.addItemType(id, this.getCardLevel(id) * 100 + index, orangewickednesstilessurl, index);
            });
            const greenwickednesstilessurl = `${g_gamethemeurl}img/green-wickedness-tiles.jpg`;
            [101,102,103,104,105,106,107,108,109,110].forEach((id, index) => {
                stock.addItemType(id, this.getCardLevel(id) * 100 + index, greenwickednesstilessurl, index);
            });
        });
    }
    
    public addCardsToStock(stock: Stock, cards: WickednessTile[], from?: string) {
        if (!cards.length) {
            return;
        }

        cards.forEach(card => stock.addToStockWithId(card.type + card.side, `${card.id}`, from));
    }

    public moveToAnotherStock(sourceStock: Stock, destinationStock: Stock, tile: WickednessTile) {
        if (sourceStock === destinationStock) {
            return;
        }
        
        const sourceStockItemId = `${sourceStock.container_div.id}_item_${tile.id}`;
        if (document.getElementById(sourceStockItemId)) {     
            this.addCardsToStock(destinationStock, [tile], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById(`${tile.id}`);
        } else {
            console.warn(`${sourceStockItemId} not found in `, sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [tile], sourceStock.container_div.id);
        }
    }

    public getCardLevel(cardTypeId: number): number {
        const id = cardTypeId % 100;
        if (id > 8) {
            return 10;
        } else if (id > 4) {
            return 6;
        } else {
            return 3;
        }
    }

    public getCardName(cardTypeId: number): string {
        switch( cardTypeId ) {
            // orange
            // TODOWI
            case 9: return _("Antimatter Beam");
            case 10: return _("Skybeam");
            // green
            // TODOWI
            case 109: return _("Final push");
            case 110: return _("Starburst");
        }
        return null;
    }

    private getCardDescription(cardTypeId: number) {
        switch( cardTypeId ) {
            // orange
            case 9: return _("<strong>Double all of your [diceSmash].</strong>");
            case 10: return _("<strong>Gain 1 extra [Energy]</strong> for each [diceEnergy] and <strong>1 extra [Heart]</strong> for each [diceHeart]");

            // green
            case 109: return _("<strong>+2[Heart] +2[Energy]</strong><br><br><strong>Take another turn after this one,</strong> then discard this tile.");
            case 110: return _("<strong>+12[Energy]</strong> then discard this tile.");
        }
        return null;
    }

    private getTooltip(cardTypeId: number) {
        const level = this.getCardLevel(cardTypeId);
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId)}</strong></p>
            <p class="level">${ dojo.string.substitute(_("Level : ${level}"), {'level': level}) }</p>
            <p>${formatTextIcons(this.getCardDescription(cardTypeId))}</p>
        </div>`;
        return tooltip;
    }

    public setupNewCard(cardDiv: HTMLDivElement, cardType: number) {
        this.setDivAsCard(cardDiv, cardType); 
        (this.game as any).addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    }

    public setDivAsCard(cardDiv: HTMLDivElement, cardType: number) {
        const name = this.getCardName(cardType);
        const level = this.getCardLevel(cardType);
        const description = formatTextIcons(this.getCardDescription(cardType));

        cardDiv.innerHTML = `
        <div class="name-wrapper">
            <div class="outline ${cardType > 100 ? 'wickedness-tile-side1' : 'wickedness-tile-side0'}">${name}</div>
            <div class="text">${name}</div>
        </div>
        <div class="level" [data-level]="${level}">${level}</div>
        
        <div class="description-wrapper">${description}</div>`;
    }
}