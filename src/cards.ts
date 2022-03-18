const CARD_WIDTH = 132;
const CARD_HEIGHT = 185;

interface PlacedTokens {
    x: number;
    y: number;
}

interface CardPlacedTokens {
    tokens: PlacedTokens[];
    mimicToken: PlacedTokens;
}

const KEEP_CARDS_LIST = {
    base: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48],
    dark: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,  21,22,23,24,25,26,  29,30,31,32,33,34,  36,37,38,  40,41,42,43,44,45,46,47,48, 49,50,51,52,53,54,55],
};

const DISCARD_CARDS_LIST = {
    base: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
    dark: [1,2,3,4,5,6,7,8,9,10,  12,13,  15,16,17,18,19],
};

const COSTUME_CARDS_LIST = [1,2,3,4,5,6,7,8,9,10,11,12];

const TRANSFORMATION_CARDS_LIST = [1];

const FLIPPABLE_CARDS = [301];

class Cards {
    constructor (private game: KingOfTokyoGame) {}
    
    public setupCards(stocks: Stock[]) {
        const version: 'base' | 'dark' = this.game.isDarkEdition() ? 'dark' : 'base';
        const costumes = this.game.isHalloweenExpansion();
        const transformation = this.game.isMutantEvolutionVariant();
        const goldenscarab = this.game.isAnubisExpansion();

        stocks.forEach(stock => {
            const keepcardsurl = `${g_gamethemeurl}img/keep-cards.jpg`;
            KEEP_CARDS_LIST[version].forEach((id, index) => {  // keep
                stock.addItemType(id, id, keepcardsurl, index);
            });

            const discardcardsurl = `${g_gamethemeurl}img/discard-cards.jpg`;
            DISCARD_CARDS_LIST[version].forEach((id, index) => {  // discard
                stock.addItemType(100 + id, 100 + id, discardcardsurl, index);
            });

            if (costumes) {
                const costumecardsurl = `${g_gamethemeurl}img/costume-cards.jpg`;
                COSTUME_CARDS_LIST.forEach((id, index) => {  // costume
                    stock.addItemType(200 + id, 200 + id, costumecardsurl, index);
                });
            }

            if (transformation) {
                const transformationcardsurl = `${g_gamethemeurl}img/transformation-cards.jpg`;
                COSTUME_CARDS_LIST.forEach((id, index) => {  // costume
                    stock.addItemType(300 + id, 300 + id, transformationcardsurl, index);
                });
            }

            if (goldenscarab) {
                const anubiscardsurl = `${g_gamethemeurl}img/anubis-cards.jpg`;
                stock.addItemType(999, 999, anubiscardsurl, 0);
            }
        });
    }

    private getDistance(p1: PlacedTokens, p2: PlacedTokens): number {
        return Math.sqrt((p1.x - p2.x) ** 2 + (p1.y - p2.y) ** 2);
    }

    public placeMimicOnCard(type: 'card' | 'tile', stock: Stock, card: Card, wickednessTiles: WickednessTiles) {
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);

        if (type === 'tile') {
            let html = `<div id="${divId}-mimic-token-tile" class="card-token mimic-tile stockitem"></div>`;
            dojo.place(html, divId);
            div.classList.add('wickedness-tile-stock');
            wickednessTiles.setDivAsCard(document.getElementById(`${divId}-mimic-token-tile`) as HTMLDivElement, 106);
        } else {
            const div = document.getElementById(divId);
            const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
            
            cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);

            let html = `<div id="${divId}-mimic-token" style="left: ${cardPlaced.mimicToken.x - 16}px; top: ${cardPlaced.mimicToken.y - 16}px;" class="card-token mimic token"></div>`;
            dojo.place(html, divId);

            div.dataset.placed = JSON.stringify(cardPlaced);
        }
    }

    public removeMimicOnCard(type: 'card' | 'tile', stock: Stock, card: Card) { 
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);
        
        if (type === 'tile') {
            if (document.getElementById(`${divId}-mimic-token-tile`)) {
                (this.game as any).fadeOutAndDestroy(`${divId}-mimic-token-tile`);
            }
            div.classList.remove('wickedness-tile-stock');
        } else {       
            const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
            cardPlaced.mimicToken = null;

            if (document.getElementById(`${divId}-mimic-token`)) {
                (this.game as any).fadeOutAndDestroy(`${divId}-mimic-token`);
            }

            div.dataset.placed = JSON.stringify(cardPlaced);
        }
    }

    private getPlaceOnCard(cardPlaced: CardPlacedTokens): PlacedTokens {
        const newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 100 + 16,
        };
        let protection = 0;
        const otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(place => this.getDistance(newPlace, place) < 32)) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }

        return newPlace;
    }

    public placeTokensOnCard(stock: Stock, card: Card, playerId?: number) {
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
    
    public addCardsToStock(stock: Stock, cards: Card[], from?: string) {
        if (!cards.length) {
            return;
        }

        cards.forEach(card => {
            stock.addToStockWithId(card.type, `${card.id}`, from);
            const cardDiv = document.getElementById(`${stock.container_div.id}_item_${card.id}`) as HTMLDivElement;
            cardDiv.dataset.side = ''+card.side;
            if (card.side !== null) {
                this.game.cards.updateFlippableCardTooltip(cardDiv)
            }
        });
        cards.filter(card => card.tokens > 0).forEach(card => this.placeTokensOnCard(stock, card));
    }

    public moveToAnotherStock(sourceStock: Stock, destinationStock: Stock, card: Card) {
        if (sourceStock === destinationStock) {
            return;
        }
        
        const sourceStockItemId = `${sourceStock.container_div.id}_item_${card.id}`;
        if (document.getElementById(sourceStockItemId)) {     
            this.addCardsToStock(destinationStock, [card], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById(`${card.id}`);
        } else {
            console.warn(`${sourceStockItemId} not found in `, sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [card], sourceStock.container_div.id);
        }
    }

    public exchangeCardFromStocks(sourceStock: Stock, destinationStock: Stock, cardOnSource: Card, cardOnDestination: Card) {
        if (sourceStock === destinationStock) {
            return;
        }
        
        const sourceStockItemId = `${sourceStock.container_div.id}_item_${cardOnSource.id}`;
        const destinationStockItemId = `${destinationStock.container_div.id}_item_${cardOnDestination.id}`;
        this.addCardsToStock(destinationStock, [cardOnSource], sourceStockItemId);
        this.addCardsToStock(sourceStock, [cardOnDestination], destinationStockItemId);
        sourceStock.removeFromStockById(`${cardOnSource.id}`);
        destinationStock.removeFromStockById(`${cardOnDestination.id}`);
    }

    private getCardNamePosition(cardTypeId: number, side: number = null) {
        switch( cardTypeId ) {
            // KEEP
            case 3: return [0, 90];
            case 9: return [35, 95];
            case 11: return [0, 85];
            case 17: return [0, 85];
            case 19: return [0, 50];
            case 27: return [35, 65];
            case 38: return [0, 100];
            case 43: return [35, 100];
            case 45: return [0, 85];
            // TODODE
            // DISCARD
            case 102: return [30, 80];
            case 106: case 107: return [35, 65];
            case 111: return [35, 95];
            case 112: return [35, 35];
            case 113: return [35, 65];
            case 114: return [35, 95];
            case 115: return [0, 80];
            // COSTUME            
            case 209: return [15, 100];
            // TRANSFORMATION
            case 301: return {
                0: [10, 15],
                1: [10, 15],
            }[side];
        }
        return null;
    }

    private getCardCost(cardTypeId: number): number | null {
        switch( cardTypeId ) {
            // KEEP
            case 1: return 6;
            case 2: return 3;
            case 3: return 5;
            case 4: return 4;
            case 5: return 4;
            case 6: return 5;
            case 7: return 3;
            case 8: return 3;
            case 9: return 3;
            case 10: return 4;
            case 11: return 3;
            case 12: return 4;
            case 13: case 14: return 7;
            case 15: return 4;
            case 16: return 5;
            case 17: return 3;
            case 18: return 5;
            case 19: return 4;
            case 20: return 4;
            case 21: return 5;
            case 22: return 3;
            case 23: return 7;
            case 24: return 5;
            case 25: return 2;
            case 26: return 3;
            case 27: return 8;
            case 28: return 3;
            case 29: return 7;
            case 30: return 4;
            case 31: return 3;
            case 32: return 4;
            case 33: return 3;
            case 34: return 3;
            case 35: return 4;
            case 36: return 3;
            case 37: return 3;
            case 38: return 4;
            case 39: return 3;
            case 40: return 6;
            case 41: return 4;
            case 42: return 2;
            case 43: return 5;
            case 44: return 3;
            case 45: return 4;
            case 46: return 4;
            case 47: return 3;
            case 48: return 6;
            case 49: return 4;
            case 50: return 3;
            case 51: return 2;
            case 52: return 6;
            case 53: return 4;
            case 54: return 3;
            case 55: return 4;
            
            // DISCARD
            case 101: return 5;
            case 102: return 4;
            case 103: return 3;
            case 104: return 5;
            case 105: return 8;
            case 106: case 107: return 7;
            case 108: return 3;
            case 109: return 7;
            case 110: return 6;
            case 111: return 3;
            case 112: return 4;
            case 113: return 5;
            case 114: return 3;
            case 115: return 6;
            case 116: return 6;
            case 117: return 4;
            case 118: return 6;
            case 119: return 0;

            // COSTUME
            case 201: return 4;
            case 202: return 4;
            case 203: return 3;
            case 204: return 4;
            case 205: return 3;
            case 206: return 4;
            case 207: return 5;
            case 208: return 4;
            case 209: return 3;
            case 210: return 4;
            case 211: return 4;
            case 212: return 3;
        }
        return null;
    }

    private getColoredCardName(cardTypeId: number, side: number = null): string {
        switch( cardTypeId ) {
            // KEEP
            case 1: return _("[724468]Acid [6E3F63]Attack");
            case 2: return _("[442E70]Alien [57347E]Origin");
            case 3: return _("[624A9E]Alpha Monster");
            case 4: return _("[6FBA44]Armor Plating");
            case 5: return _("[0068A1]Background [0070AA]Dweller");
            case 6: return _("[5A6E79]Burrowing");
            case 7: return _("[5DB1DD]Camouflage");
            case 8: return _("[7C7269]Complete [958B7F]Destruction");
            case 9: return _("[836380]Media-Friendly");
            case 10: return _("[42B4B4]Eater of [25948B]the Dead");
            case 11: return _("[0C4E4A]Energy [004C6E]Hoarder");
            case 12: return _("[293066]Even Bigger");
            case 13: case 14: return _("[060D29]Extra [0C1946]Head");
            case 15: return _("[823F24]Fire [FAAE5A]Breathing");
            case 16: return _("[5F6D7A]Freeze Time");
            case 17: return _("[0481C4]Friend of Children");
            case 18: return _("[8E4522]Giant [277C43]Brain");
            case 19: return _("[958877]Gourmet");
            case 20: return _("[7A673C]Healing [DC825F]Ray");
            case 21: return _("[2B63A5]Herbivore");
            case 22: return _("[BBB595]Herd [835C25]Culler");
            case 23: return _("[0C94D0]It Has a Child!");
            case 24: return _("[AABEE1]Jets");
            case 25: return _("[075087]Made in [124884]a Lab");
            case 26: return _("[5E9541]Metamorph");
            case 27: return _("[85A8AA]Mimic");
            case 28: return _("[92534C]Battery [88524D]Monster");
            case 29: return _("[67374D]Nova [83B5B6]Breath");
            case 30: return _("[5B79A2]Detritivore");
            case 31: return _("[0068A1]Opportunist");
            case 32: return _("[462365]Parasitic [563D5B]Tentacles");
            case 33: return _("[CD599A]Plot [E276A7]Twist");
            case 34: return _("[1E345D]Poison Quills");
            case 35: return _("[3D5C33]Poison Spit");
            case 36: return _("[2A7C3C]Psychic [6DB446]Probe");
            case 37: return _("[8D6E5C]Rapid [B16E44]Healing");
            case 38: return _("[5C273B]Regeneration");
            case 39: return _("[007DC0]Rooting for the Underdog");
            case 40: return _("[A2B164]Shrink [A07958]Ray");
            case 41: return _("[5E7795]Smoke Cloud");
            case 42: return _("[142338]Solar [46617C]Powered");
            case 43: return _("[A9C7AD]Spiked [4F6269]Tail");
            case 44: return _("[AE2B7B]Stretchy");
            case 45: return _("[56170E]Energy Drink");
            case 46: return _("[B795A5]Urbavore");
            case 47: return _("[757A52]We're [60664A]Only [52593A]Making It [88A160]Stronger!");
            case 48: return _("[443E56]Wings");
            case 49: return ("Hibernation"); // TODODE
            case 50: return ("Nanobots"); // TODODE
            case 51: return ("Natural Selection"); // TODODE
            case 52: return ("Reflective Hide"); // TODODE
            case 53: return ("Super Jump"); // TODODE
            case 54: return ("Unstable DNA"); // TODODE
            case 55: return ("Zombify"); // TODODE
            
            // DISCARD
            case 101: return _("[B180A0]Apartment [9F7595]Building");
            case 102: return _("[496787]Commuter [415C7A]Train");
            case 103: return _("[993422]Corner [5F6A70]Store");
            case 104: return _("[5BB3E2]Death [45A2D6]From [CE542B]Above");
            case 105: return _("[5D657F]Energize");
            case 106: case 107: return _("[7F2719]Evacuation [812819]Orders");
            case 108: return _("[71200F]Flame [4E130B]Thrower");
            case 109: return _("[B1624A]Frenzy");
            case 110: return _("[645656]Gas [71625F]Refinery");
            case 111: return _("[815321]Heal");
            case 112: return _("[5B79A2]High Altitude Bombing");
            case 113: return _("[EE008E]Jet [49236C]Fighters");
            case 114: return _("[68696B]National [53575A]Guard");
            case 115: return _("[684376]Nuclear [41375F]Power Plant");
            case 116: return _("[5F8183]Skyscraper");
            case 117: return _("[AF966B]Tank");
            case 118: return _("[847443]Vast [8D7F4E]Storm");
            case 119: return "Monster pets"; // TODODE

            // COSTUME
            case 201: return _("[353d4b]Astronaut");
            case 202: return _("[005c98]Ghost");
            case 203: return _("[213b75]Vampire");
            case 204: return _("[5a4f86]Witch");
            case 205: return _("[3c4b53]Devil");
            case 206: return _("[584b84]Pirate");
            case 207: return _("[bb6082]Princess");
            case 208: return _("[7e8670]Zombie");
            case 209: return _("[52373d]Cheerleader");
            case 210: return _("[146088]Robot");
            case 211: return _("[733010]Statue of liberty");
            case 212: return _("[2d4554]Clown");

            // TRANSFORMATION
            case 301: return {
                0: _("[deaa26]Biped [72451c]Form"),
                1: _("[982620]Beast [de6526]Form"),
                null: _("[982620]Beast [de6526]Form"),
            }[side];
        }
        return null;
    }

    public getCardName(cardTypeId: number, state: 'text-only' | 'span', side: number = null) {
        const coloredCardName = this.getColoredCardName(cardTypeId, side);
        if (state == 'text-only') {
            return coloredCardName?.replace(/\[(\w+)\]/g, '');
        } else if (state == 'span') {
            let first = true;
            return coloredCardName?.replace(/\[(\w+)\]/g, (index, color) => {
                let span = `<span style="-webkit-text-stroke-color: #${color};">`;
                if (first) {
                    first = false;
                } else {
                    span = `</span>` + span;
                }
                return span;
            }) + `${first ? '' : '</span>'}`;
        }
        return null;
    }

    private getCardDescription(cardTypeId: number, side: number = null) {
        switch( cardTypeId ) {
            // KEEP
            case 1: return _("<strong>Add</strong> [diceSmash] to your Roll");
            case 2: return _("<strong>Buying cards costs you 1 less [Energy].</strong>");
            case 3: return _("<strong>Gain 1[Star]</strong> when you roll at least one [diceSmash].");
            case 4: return _("<strong>Do not lose [heart] when you lose exactly 1[heart].</strong>");
            case 5: return _("<strong>You can always reroll any [dice3]</strong> you have.");
            case 6: return _("<strong>Add [diceSmash] to your Roll while you are in Tokyo. When you Yield Tokyo, the monster taking it loses 1[heart].</strong>");
            case 7: return _("If you lose [heart], roll a die for each [heart] you lost. <strong>Each [diceHeart] reduces the loss by 1[heart].</strong>");
            case 8: return _("If you roll [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy] <strong>gain 9[Star]</strong> in addition to the regular effects.");
            case 9: return _("<strong>Gain 1[Star]</strong> whenever you buy a Power card.");
            case 10: return _("<strong>Gain 3[Star]</strong> every time a Monster's [Heart] goes to 0.");
            case 11: return _("<strong>You gain 1[Star]</strong> for every 6[Energy] you have at the end of your turn.");
            case 12: return _("<strong>+2[Heart] when you buy this card.</strong> Your maximum [Heart] is increased to 12[Heart] as long as you own this card.");
            case 13: case 14: return _("<strong>You get 1 extra die.</strong>");
            case 15: return _("<strong>Your neighbors lose 1[heart]</strong> when you roll at least one [diceSmash].");
            case 16: return _("On a turn where you score [dice1][dice1][dice1], <strong>you can take another turn</strong> with one less die.");
            case 17: return _("When you gain any [Energy] <strong>gain 1 extra [Energy].</strong>");
            case 18: return _("<strong>You have one extra die Roll</strong> each turn.");
            case 19: return _("When you roll [dice1][dice1][dice1] or more <strong>gain 2 extra [Star].</strong>");
            case 20: return _("<strong>You can use your [diceHeart] to make other Monsters gain [Heart].</strong> Each Monster must pay you 2[Energy] (or 1[Energy] if it's their last one) for each [Heart] they gain this way");
            case 21: return _("<strong>Gain 1[Star]</strong> at the end of your turn if you don't make anyone lose [Heart].");
            case 22: return _("You can <strong>change one of your dice to a [dice1]</strong> each turn.");
            case 23: return _("If you reach 0[Heart] discard all your cards and lose all your [Star]. <strong>Gain 10[Heart] and continue playing outside Tokyo.</strong>");
            case 24: return _("<strong>You don't lose [Heart]<strong> if you decide to Yield Tokyo.");
            case 25: return _("During the Buy Power cards step, you can <strong>peek at the top card of the deck and buy it</strong> or put it back on top of the deck.");
            case 26: return _("At the end of your turn you can <strong>discard any [keep] cards you have to gain their full cost in [Energy].</strong>");
            case 27: return _("<strong>Choose a [keep] card any monster has in play</strong> and put a Mimic token on it. <strong>This card counts as a duplicate of that card as if you had just bought it.</strong> Spend 1[Energy] at the start of your turn to move the Mimic token and change the card you are mimicking.");
            case 28: return dojo.string.substitute(_("When you buy <i>${card_name}</i>, put 6[Energy] on it from the bank. At the start of your turn <strong>take 2[Energy] off and add them to your pool.</strong> When there are no [Energy] left discard this card."), {'card_name': this.getCardName(cardTypeId, 'text-only')});
            case 29: return _("<strong>Your [diceSmash] damage all other Monsters.</strong>");
            case 30: return _("<strong>When you roll at least [dice1][dice2][dice3] gain 2[Star].</strong> You can also use these dice in other combinations.");
            case 31: return _("<strong>Whenever a Power card is revealed you have the option of buying it</strong> immediately.");
            case 32: return _("<strong>You may buy [keep] cards from other monsters.</strong> Pay them the [Energy] cost.");
            case 33: return _("Before resolving your dice, you may <strong>change one die to any result</strong>. Discard when used.");
            case 34: return _("When you score [dice2][dice2][dice2] or more, <strong>add [diceSmash][diceSmash] to your Roll</strong>.");
            case 35: return _("Give one <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each <i>Poison</i> token they have on them.</strong> A <i>Poison</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 36: return _("You can reroll a die of your choice after the last Roll of each other Monster. If the reroll [diceHeart], discard this card.");
            case 37: return _("Spend 2[Energy] at any time to <strong>gain 1[Heart].</strong>");
            case 38: return _("When gain [Heart], <strong>gain 1 extra [Heart].</strong>");
            case 39: return _("At the end of your turn, if you have the fewest [Star], <strong>gain 1 [Star].</strong>");
            case 40: return _("Give 1 <i>Shrink Ray</i> to each Monster you Smash with your [diceSmash]. <strong>At the beginning of their turn, Monster roll 1 less dice for each <i>Shrink Ray</i> token they have on them</strong>. A <i>Shrink Ray</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 41: return _("Place 3 <i>Smoke</i> counters on this card. <strong>Spend 1 <i>Smoke</i> counter for an extra Roll.</strong> Discard this card when all <i>Smoke</i> counters are spent.");
            case 42: return _("At the end of your turn <strong>gain 1[Energy] if you have no [Energy].</strong>");
            case 43: return _("<strong>If you roll at least one [diceSmash], add [diceSmash]</strong> to your Roll.");
            case 44: return _("Before resolving your dice, you can spend 2[Energy] to <strong>change one of your dice to any result.</strong>");
            case 45: return _("Spend 1[Energy] to <strong>get 1 extra die Roll.</strong>");
            case 46: return _("<strong>Gain 1 extra [Star]</strong> when beginning your turn in Tokyo. If you are in Tokyo and you roll at least one [diceSmash], <strong>add [diceSmash] to your Roll.</strong>");
            case 47: return _("When you lose 2[Heart] or more <strong>gain 1[Energy].</strong>");
            case 48: return _("<strong>Spend 2[Energy] to not lose [Heart]<strong> this turn.");
            case 49: return `<div><i>${/*_TODODE*/("You CANNOT buy this card while in TOKYO")}</i></div>` + /*_TODODE*/("<strong>You no longer take damage.</strong> You cannot move, even if Tokyo is empty. You can no longer buy cards. <strong>The only results you can use are [diceHeart] and [diceEnergy].</strong> Discard this card to end its effects and restrictions immediately.");
            case 50: return /*_TODODE*/("At the start of your turn, if you have fewer than 3[Heart], <strong>gain 2[Heart].</strong>");
            // TODODE

            // DISCARD
            case 101: return "<strong>+ 3[Star].</strong>";
            case 102: return "<strong>+ 2[Star].</strong>";
            case 103: return "<strong>+ 1[Star].</strong>";
            case 104: return _("<strong>+ 2[Star] and take control of Tokyo</strong> if you don't already control it.");
            case 105: return "<strong>+ 9[Energy].</strong>";
            case 106: case 107: return _("<strong>All other Monsters lose 5[Star].</strong>");
            case 108: return _("<strong>All other Monsters lose 2[Heart].</strong>");
            case 109: return _("<strong>Take another turn</strong> after this one");
            case 110: return _("<strong>+ 2[Star] and all other monsters lose 3[Heart].</strong>");
            case 111: return "<strong>+ 2[Heart]</strong>";
            case 112: return _("<strong>All Monsters</strong> (including you) <strong>lose 3[Heart].</strong>");
            case 113: return "<strong>+ 5[Star] -4[Heart].</strong>";
            case 114: return "<strong>+ 2[Star] -2[Heart].</strong>";
            case 115: return "<strong>+ 2[Star] +3[Heart].</strong>";
            case 116: return "<strong>+ 4[Star].";
            case 117: return "<strong>+ 4[Star] -3[Heart].</strong>";
            case 118: return _("<strong>+ 2[Star] and all other Monsters lose 1[Energy] for every 2[Energy]</strong> they have.");
            case 119: return "<strong>All Monsters</strong> (including you) <strong>lose 3[Star].</strong>"; // TODODE

            // COSTUME
            case 201: return _("<strong>If you reach 17[Star],</strong> you win the game");
            case 202: return _("At the end of each Monster's turn, if you lost at least 1[Heart] <strong>that turn, gain 1[Heart].</strong>");
            case 203: return _("At the end of each Monster's turn, if you made another Monster lose at least 1[Heart], <strong>gain 1[Heart].</strong>");
            case 204: return _("If you must be wounded <strong>by another Monster,</strong> you can reroll one of their dice.");
            case 205: return _("On your turn, when you make other Monsters lose at least 1[Heart], <strong>they lose an extra [Heart].</strong>");
            case 206: return _("<strong>Steal 1[Energy]</strong> from each Monster you made lose at least 1[Heart].");
            case 207: return _("<strong>Gain 1[Star] at the start of your turn.</strong>");
            case 208: return _("You are not eliminated if you reach 0[Heart]. <strong>You cannot lose [Heart]</strong> as long as you have 0[Heart]. If you lose this card while you have 0[Heart], you are immediately eliminated.");
            case 209: return _("<strong>You can choose to cheer for another Monster on their turn.</strong> If you do, add [diceSmash] to their roll.");
            case 210: return _("You can choose to lose [Energy] instead of [Heart].");
            case 211: return _("You have an <strong>extra Roll.</strong>");
            case 212: return _("If you roll [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy], you can <strong>change the result for every die.</strong>");

            // TRANSFORMATION 
            case 301: return {
                0: _("Before the Buy Power cards phase, you may spend 1[Energy] to flip this card."),
                1: _("During the Roll Dice phase, you may reroll one of your dice an extra time. You cannot buy any more Power cards. <em>Before the Buy Power cards phase, you may spend 1[Energy] to flip this card.</em>"),
            }[side];
        }
        return null;
    }

    public updateFlippableCardTooltip(cardDiv: HTMLDivElement) {
        const type = Number(cardDiv.dataset.type);
        if (!FLIPPABLE_CARDS.includes(type)) {
            return;
        }
        
        (this.game as any).addTooltipHtml(cardDiv.id, this.getTooltip(Number(cardDiv.dataset.type), Number(cardDiv.dataset.side)));
    }

    public getTooltip(cardTypeId: number, side: number = null) {
        if (cardTypeId === 999) {
            return _("The Golden Scarab affects certain Curse cards. At the start of the game, the player who will play last gets the Golden Scarab.");
        }
        const cost = this.getCardCost(cardTypeId);
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId, 'text-only', side)}</strong></p>`;
        if (cost !== null) {
            tooltip += `<p class="cost">${ dojo.string.substitute(_("Cost : ${cost}"), {'cost': cost}) } <span class="icon energy"></span></p>`;
        }
        tooltip += `<p>${formatTextIcons(this.getCardDescription(cardTypeId, side))}</p>`;

        if (FLIPPABLE_CARDS.includes(cardTypeId) && side !== null) {
            const otherSide = side == 1 ? 0 : 1;

            const tempDiv: HTMLDivElement = document.createElement('div');
            tempDiv.classList.add('stockitem');
            tempDiv.style.width = `${CARD_WIDTH}px`;
            tempDiv.style.height = `${CARD_HEIGHT}px`;
            tempDiv.style.position = `relative`;
            tempDiv.style.backgroundImage = `url('${g_gamethemeurl}img/${this.getImageName(cardTypeId)}-cards.jpg')`;
            tempDiv.style.backgroundPosition = `-${otherSide*100}% 0%`;

            document.body.appendChild(tempDiv);
            this.setDivAsCard(tempDiv, cardTypeId, otherSide);
            document.body.removeChild(tempDiv);

            tooltip += `<p>${_("Other side :")}<br>${tempDiv.outerHTML}</p>`;
        }

        tooltip += `</div>`;
        return tooltip;
    }

    public setupNewCard(cardDiv: HTMLDivElement, cardType: number) {
        if (FLIPPABLE_CARDS.includes(cardType)) {
            cardDiv.dataset.type = ''+cardType;
            cardDiv.classList.add('card-inner');
            dojo.place(`
                <div class="card-side front"></div>
                <div class="card-side back"></div>
            `, cardDiv);
            this.setDivAsCard(cardDiv.getElementsByClassName('front')[0] as HTMLDivElement, 301, 0); 
            this.setDivAsCard(cardDiv.getElementsByClassName('back')[0] as HTMLDivElement, 301, 1);
        } else {
            if (cardType !== 999) { // no text for golden scarab
                this.setDivAsCard(cardDiv, cardType); 
            }
            (this.game as any).addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
        }
    }

    private getCardTypeName(cardType: number) {
        if (cardType < 100) {
            return _('Keep');
        } else if (cardType < 200) {
            return _('Discard');
        } else if (cardType < 300) {
            return _('Costume');
        } else if (cardType < 400) {
            return _('Transformation');
        }
    }

    private getCardTypeClass(cardType: number) {
        if (cardType < 100) {
            return 'keep';
        } else if (cardType < 200) {
            return 'discard';
        } else if (cardType < 300) {
            return 'costume';
        } else if (cardType < 400) {
            return 'transformation';
        }
    }

    public setDivAsCard(cardDiv: HTMLDivElement, cardType: number, side: number = null) {
        const type = this.getCardTypeName(cardType);
        const description = formatTextIcons(this.getCardDescription(cardType, side));
        const position = this.getCardNamePosition(cardType, side);

        cardDiv.innerHTML = `<div class="bottom"></div>
        <div class="name-wrapper" ${position ? `style="left: ${position[0]}px; top: ${position[1]}px;"` : ''}>
            <div class="outline">${this.getCardName(cardType, 'span', side)}</div>
            <div class="text">${this.getCardName(cardType, 'text-only', side)}</div>
        </div>
        <div class="type-wrapper ${this.getCardTypeClass(cardType)}">
            <div class="outline">${type}</div>
            <div class="text">${type}</div>
        </div>
        
        <div class="description-wrapper">${description}</div>`;

        let textHeight = (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).clientHeight;

        if (textHeight > 80) {
            (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).style.fontSize = '6pt';
            textHeight = (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).clientHeight;
        }
        const height = Math.min(textHeight, 116);
        (cardDiv.getElementsByClassName('bottom')[0] as HTMLDivElement).style.top = `${166 - height}px`;
        (cardDiv.getElementsByClassName('type-wrapper')[0] as HTMLDivElement).style.top = `${168 - height}px`;

        const nameTopPosition = position?.[1] || 14;
        const nameWrapperDiv = cardDiv.getElementsByClassName('name-wrapper')[0] as HTMLDivElement;
        const nameDiv = nameWrapperDiv.getElementsByClassName('text')[0] as HTMLDivElement;
        const spaceBetweenDescriptionAndName = (155 - height) - (nameTopPosition + nameDiv.clientHeight);
        if (spaceBetweenDescriptionAndName < 0) {
            nameWrapperDiv.style.top = `${Math.max(5, nameTopPosition + spaceBetweenDescriptionAndName)}px`;
        }
    }

    private getImageName(cardType: number) {
        if (cardType < 100) {
            return 'keep';
        } else if (cardType < 200) {
            return 'discard';
        } else if (cardType < 300) {
            return 'costume';
        } else if (cardType < 400) {
            return 'transformation';
        }
    }

    public getMimickedCardText(mimickedCard: Card): string {
        let mimickedCardText = '-';
        if (mimickedCard) {
            const tempDiv: HTMLDivElement = document.createElement('div');
            tempDiv.classList.add('stockitem');
            tempDiv.style.width = `${CARD_WIDTH}px`;
            tempDiv.style.height = `${CARD_HEIGHT}px`;
            tempDiv.style.position = `relative`;
            tempDiv.style.backgroundImage = `url('${g_gamethemeurl}img/${this.getImageName(mimickedCard.type)}-cards.jpg')`;
            const imagePosition = ((mimickedCard.type + mimickedCard.side) % 100) - 1;
            const image_items_per_row = 10;
            var row = Math.floor(imagePosition / image_items_per_row);
            const xBackgroundPercent = (imagePosition - (row * image_items_per_row)) * 100;
            const yBackgroundPercent = row * 100;
            tempDiv.style.backgroundPosition = `-${xBackgroundPercent}% -${yBackgroundPercent}%`;

            document.body.appendChild(tempDiv);
            this.setDivAsCard(tempDiv, mimickedCard.type + (mimickedCard.side || 0));
            document.body.removeChild(tempDiv);

            mimickedCardText = `<br>${tempDiv.outerHTML}`;
        }

        return mimickedCardText;
    }

    public changeMimicTooltip(mimicCardId: string, mimickedCardText: string) {
        (this.game as any).addTooltipHtml(mimicCardId, this.getTooltip(27) + `<br>${_('Mimicked card:')} ${mimickedCardText}`);
    }
}