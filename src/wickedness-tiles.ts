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
            case 1: return _("Devious");
            case 2: return _("Eternal");
            case 3: return _("Skulking");
            case 4: return _("Tireless");
            case 5: return _("Cyberbrain");
            case 6: return _("Evil Lair");
            case 7: return _("Full regeneration");
            case 8: return _("Widespread Panic");
            case 9: return _("Antimatter Beam");
            case 10: return _("Skybeam");
            // green
            case 101: return _("Barbs");
            case 102: return _("Final Roar");
            case 103: return _("Poison Spit");
            case 104: return _("Underdog");
            case 105: return _("Defender of Tokyo");
            case 106: return _("Fluxling");
            case 107: return _("Have it all!");
            case 108: return _("Sonic Boomer");
            case 109: return _("Final push");
            case 110: return _("Starburst");
        }
        return null;
    }

    private getCardDescription(cardTypeId: number) {
        switch( cardTypeId ) {
            // orange
            case 1: return _("<strong>Gain one extra die Roll</strong> each turn.");
            case 2: return _("At the start of your turn, <strong> gain 1[Heart].</strong>");
            case 3: return _("When you roll [dice1][dice1][dice1] or more, <strong> gain 1 extra [Star].</strong>");
            case 4: return _("At the start of your turn, <strong> gain 1[Energy].</strong>");
            case 5: return _("You get <strong>1 extra die.</strong>");
            case 6: return _("Buying Power cards <strong>costs you 1 less [energy].</strong>");
            case 7: return _("<strong>You may have up to 12[heart].</strong> Fully heal (to 12) when you gain this tile.");
            case 8: return _("<strong>All other Monsters lose 4[Star],</strong> then discard this tile.");
            case 9: return _("<strong>Double all of your [diceSmash].</strong>");
            case 10: return _("<strong>Gain 1 extra [Energy]</strong> for each [diceEnergy] and <strong>1 extra [Heart]</strong> for each [diceHeart]");

            // green
            case 101: return _("<strong>When you roll at least [diceSmash][diceSmash], gain a [diceSmash].</strong>");
            case 102: return _("If you are eliminated from the game with 16[Star] or more, <strong>you win the game instead.</strong>");
            case 103: return _("Give one <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each <i>Poison</i> token they have on them.</strong> A <i>Poison</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 104: return _("<strong>When you smash a Monster,</strong> if that Monster has more [Star] than you, <strong>steal 1[Star]</strong>");
            case 105: return _("When you move into Tokyo or begin yout turn in Tokyo, <strong>all other Monsters lose 1[Star].</strong>");
            case 106: return _("When you gain this, place it in front of a [keep] card of any player. <strong>This tile counts as a copy of that [keep] card.</strong> You can change which card you are copying at the start of your turn.");
            case 107: return _("When you acquire this tile, <strong>gain 1[Star] for each [keep] card you have.</strong> Gain 1[Star] each time you buy any Power card");
            case 108: return _("At the start of your turn, <strong>gain 1[Star].</strong>");
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