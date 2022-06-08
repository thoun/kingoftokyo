const WICKEDNESS_TILES_WIDTH = 132;
const WICKEDNESS_TILES_HEIGHT = 81;
const WICKEDNESS_LEVELS = [3, 6, 10];

const wickenessTilesIndex = [0, 0, 0, 0, 1, 1, 1, 1, 2, 2];

class WickednessTiles {
    constructor (private game: KingOfTokyoGame) {}

    debugSeeAllCards() {
        let html = `<div id="all-wickedness-tiles" class="wickedness-tile-stock player-wickedness-tiles">`;
        [0, 1].forEach(side => 
            html += `<div id="all-wickedness-tiles-${side}" style="display: flex; flex-wrap: nowrap;"></div>`
        );
        html += `</div>`;
        dojo.place(html, 'kot-table', 'before');

        [0, 1].forEach(side => {
            const evolutionRow = document.getElementById(`all-wickedness-tiles-${side}`);
            for (let i = 1; i <= 10; i++) {
                const tempDiv = this.generateCardDiv({
                    type: side * 100 + i,
                    side
                } as WickednessTile);
                tempDiv.id = `all-wickedness-tiles-${side}-${i}`;
                evolutionRow.appendChild(tempDiv);
                (this.game as any).addTooltipHtml(tempDiv.id, this.getTooltip(side * 100 + i));
            }
        })
    }
    
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
        cards.filter(card => card.tokens > 0).forEach(card => this.placeTokensOnTile(stock, card));
    }

    public generateCardDiv(card: WickednessTile): HTMLDivElement {
        const tempDiv: HTMLDivElement = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = `${WICKEDNESS_TILES_WIDTH}px`;
        tempDiv.style.height = `${WICKEDNESS_TILES_HEIGHT}px`;
        tempDiv.style.position = `relative`;
        tempDiv.style.backgroundImage = `url('${g_gamethemeurl}img/wickedness-tiles.jpg')`;
        tempDiv.style.backgroundPosition = `-${wickenessTilesIndex[card.type % 100] * 50}% ${card.side > 0 ? 100 : 0}%`;

        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type);
        document.body.removeChild(tempDiv);
            
        return tempDiv;
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
            case 103: return _("Give 1 <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each token they have on them.</strong> A token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
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

    public getTooltip(cardType: number) {
        const level = this.getCardLevel(cardType);
        const description = formatTextIcons(this.getCardDescription(cardType));
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardType)}</strong></p>
            <p class="level">${ dojo.string.substitute(_("Level : ${level}"), {'level': level}) }</p>
            <p>${description}</p>
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
        <div class="name-and-description">
            <div>
                <div class="name-wrapper">
                    <div class="outline ${cardType > 100 ? 'wickedness-tile-side1' : 'wickedness-tile-side0'}">${name}</div>
                    <div class="text">${name}</div>
                </div>
            </div>
            <div>        
                <div class="description-wrapper">${description}</div>
            </div>
        `;

        let textHeight = (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).clientHeight;

        if (textHeight > 50) {
            (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).style.width = '100%';
        }
        textHeight = (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).clientHeight;

        if (textHeight > 50) {
            (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).style.fontSize = '6pt';
        }
        textHeight = (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).clientHeight;

        let nameHeight = (cardDiv.getElementsByClassName('outline')[0] as HTMLDivElement).clientHeight;

        if (75 - textHeight < nameHeight) {
            (cardDiv.getElementsByClassName('name-wrapper')[0] as HTMLDivElement).style.fontSize = '8pt';
        }

        nameHeight = (cardDiv.getElementsByClassName('outline')[0] as HTMLDivElement).clientHeight;

        if (75 - textHeight < nameHeight) {
            (cardDiv.getElementsByClassName('name-wrapper')[0] as HTMLDivElement).style.fontSize = '7pt';
        }
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

    public placeTokensOnTile(stock: Stock, tile: WickednessTile, playerId?: number) {
        const divId = `${stock.container_div.id}_item_${tile.id}`;
        const div = document.getElementById(divId);
        if (!div) {
            return;
        }
        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        const placed: PlacedTokens[] = cardPlaced.tokens;

        const cardType = tile.mimicType || tile.type;

        // remove tokens
        for (let i = tile.tokens; i < placed.length; i++) {
            if (cardType === 28 && playerId) {
                (this.game as any).slideToObjectAndDestroy(`${divId}-token${i}`, `energy-counter-${playerId}`);
            } else {
                (this.game as any).fadeOutAndDestroy(`${divId}-token${i}`);
            }
        }
        placed.splice(tile.tokens, placed.length - tile.tokens);

        // add tokens
        for (let i = placed.length; i < tile.tokens; i++) {
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