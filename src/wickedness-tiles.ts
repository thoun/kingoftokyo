const WICKEDNESS_TILES_WIDTH = 132;
const WICKEDNESS_TILES_HEIGHT = 81;
const WICKEDNESS_LEVELS = [3, 6, 10];

const wickenessTilesIndex = [0, 0, 0, 0, 1, 1, 1, 1, 2, 2];

class WickednessTiles {
    constructor (private game: KingOfTokyoGame) {}
    
    public setupCards(stocks: Stock[]) {
        const wickednesstilessurl = `${g_gamethemeurl}img/wickedness-tiles.jpg`;
        stocks.forEach(stock => {
            stock.image_items_per_row = 3;

            [1,2,3,4,5,6,7,8,9,10].forEach((id, index) => {
                stock.addItemType(id, this.getCardLevel(id) * 100 + index, wickednesstilessurl, wickenessTilesIndex[index]);
            });
            [101,102,103,104,105,106,107,108,109,110].forEach((id, index) => {
                stock.addItemType(id, this.getCardLevel(id) * 100 + index, wickednesstilessurl, wickenessTilesIndex[index] + 3);
            });
        });
    }
    
    public addCardsToStock(stock: Stock, cards: WickednessTile[], from?: string) {
        if (!cards.length) {
            return;
        }

        cards.forEach(card => stock.addToStockWithId(card.type, `${card.id}`, from));
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
            // orange // TODOWI translate
            case 1: return ("Devious");
            case 2: return ("Eternal");
            case 3: return ("Skulking");
            case 4: return ("Tireless");
            case 5: return ("Cyberbrain");
            case 6: return ("Evil Lair");
            case 7: return ("Full regeneration");
            case 8: return ("Widespread Panic");
            case 9: return ("Antimatter Beam");
            case 10: return ("Skybeam");
            // green // TODOWI translate
            case 101: return ("Barbs");
            case 102: return ("Final Roar");
            case 103: return ("Poison Spit");
            case 104: return ("Underdog");
            case 105: return ("Defender of Tokyo");
            case 106: return ("Fluxling");
            case 107: return ("Have it all!");
            case 108: return ("Sonic Boomer");
            case 109: return ("Final push");
            case 110: return ("Starburst");
        }
        return null;
    }

    private getCardDescription(cardTypeId: number) {
        switch( cardTypeId ) {
            // orange
            case 1: return ("<strong>Gain one extra die Roll</strong> each turn.");
            case 2: return ("At the start of your turn, <strong> gain 1[Heart].</strong>");
            case 3: return ("When you roll [dice1][dice1][dice1] or more, <strong> gain 1 extra [Star].</strong>");
            case 4: return ("At the start of your turn, <strong> gain 1[Energy].</strong>");
            case 5: return ("You get <strong>1 extra die.</strong>");
            case 6: return ("Buying Power cards <strong>costs you 1 less [energy].</strong>");
            case 7: return ("<strong>You may have up to 12[heart].</strong> Fully heal (to 12) when you gain this tile.");
            case 8: return ("<strong>All other Monsters lose 4[Star],</strong> then discard this tile.");
            case 9: return ("<strong>Double all of your [diceSmash].</strong>");
            case 10: return ("<strong>Gain 1 extra [Energy]</strong> for each [diceEnergy] and <strong>1 extra [Heart]</strong> for each [diceHeart]");

            // green
            case 101: return ("<strong>When you roll at least [diceSmash][diceSmash], gain a [diceSmash].</strong>");
            case 102: return ("If you are eliminated from the game with 16[Star] or more, <strong>you win the game instead.</strong>");
            case 103: return ("Give one <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each <i>Poison</i> token they have on them.</strong> A <i>Poison</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 104: return ("<strong>When you smash a Monster,</strong> if that Monster has more [Star] than you, <strong>steal 1[Star]</strong>");
            case 105: return ("When you move into Tokyo or begin yout turn in Tokyo, <strong>all other Monsters lose 1[Star].</strong>");
            case 106: return ("When you gain this, place it in front of a [keep] card of any player. <strong>This tile counts as a copy of that [keep] card.</strong> You can change which card you are copying at the start of your turn.");
            case 107: return ("When you acquire this tile, <strong>gain 1[Star] for each [keep] card you have.</strong> Gain 1[Star] each time you buy any Power card");
            case 108: return ("At the start of your turn, <strong>gain 1[Star].</strong>");
            case 109: return ("<strong>+2[Heart] +2[Energy]</strong><br><br><strong>Take another turn after this one,</strong> then discard this tile.");
            case 110: return ("<strong>+12[Energy]</strong> then discard this tile.");
        }
        return null;
    }

    public getTooltip(cardTypeId: number) {
        const level = this.getCardLevel(cardTypeId);
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId)}</strong></p>
            <p class="level">${ dojo.string.substitute(/* TODOWI _(*/"Level : ${level}"/*)*/, {'level': level}) }</p>
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
        const description = formatTextIcons(this.getCardDescription(cardType));

        cardDiv.innerHTML = `
        <div class="name-wrapper">
            <div class="outline ${cardType > 100 ? 'wickedness-tile-side1' : 'wickedness-tile-side0'}">${name}</div>
            <div class="text">${name}</div>
        </div>
        
        <div class="description-wrapper">${description}</div>`;
    }

    public changeMimicTooltip(mimicCardId: string, mimickedCardText: string) {
        (this.game as any).addTooltipHtml(mimicCardId, this.getTooltip(106) + `<br>${_('Mimicked card:')} ${mimickedCardText}`);
    }

    private getDistance(p1: PlacedTokens, p2: PlacedTokens): number {
        return Math.sqrt((p1.x - p2.x) ** 2 + (p1.y - p2.y) ** 2);
    }

    private getPlaceOnCard(cardPlaced: CardPlacedTokens): PlacedTokens {
        const newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 50 + 16,
        };
        let protection = 0;
        const otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(place => this.getDistance(newPlace, place) < 32)) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 50 + 16;
            protection++;
        }

        return newPlace;
    }

    public placeTokensOnTile(stock: Stock, card: Card, playerId?: number) {
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);
        if (!div) {
            return;
        }
        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        const placed: PlacedTokens[] = cardPlaced.tokens;

        const cardType = card.mimicType || card.type;

        // remove tokens
        for (let i = card.tokens; i < placed.length; i++) {
            if (cardType === 28 && playerId) {
                (this.game as any).slideToObjectAndDestroy(`${divId}-token${i}`, `energy-counter-${playerId}`);
            } else {
                (this.game as any).fadeOutAndDestroy(`${divId}-token${i}`);
            }
        }
        placed.splice(card.tokens, placed.length - card.tokens);

        // add tokens
        for (let i = placed.length; i < card.tokens; i++) {
            const newPlace = this.getPlaceOnCard(cardPlaced);

            placed.push(newPlace);
            let html = `<div id="${divId}-token${i}" style="left: ${newPlace.x - 16}px; top: ${newPlace.y - 16}px;" class="card-token `;
            if (cardType === 28) {
                html += `energy-cube`;
            } else if (cardType === 41) {
                html += `smoke-cloud token`;
            }
            html += `"></div>`;
            dojo.place(html, divId);
        }

        div.dataset.placed = JSON.stringify(cardPlaced);
    }
}