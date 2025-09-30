const CARD_WIDTH = 132;
const CARD_HEIGHT = 185;

const EVOLUTION_SIZE = 198;

interface PlacedTokens {
    x: number;
    y: number;
}

interface CardPlacedTokens {
    tokens: PlacedTokens[];
    mimicToken: PlacedTokens;
    superiorAlienTechnologyToken: PlacedTokens;
}

const KEEP_CARDS_LIST = {
    base: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48],
    dark: [1,2,3,4,5,6,7,8,9,10,11,12,13,  15,16,17,18,19,  21,22,23,24,25,26,  29,30,31,32,33,34,  36,37,38,  40,41,42,43,44,45,46,47,48, 49,50,51,52,53,54,55],
};

const DISCARD_CARDS_LIST = {
    base: [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
    dark: [1,2,3,4,5,6,7,8,9,10,  12,13,  15,16,17,18,19],
};

const COSTUME_CARDS_LIST = [1,2,3,4,5,6,7,8,9,10,11,12];

const TRANSFORMATION_CARDS_LIST = [1];

const FLIPPABLE_CARDS = [301];

const DARK_EDITION_CARDS_COLOR_MAPPINGS = {
    // keep
    1: {
        '724468': '6abd45',
        '6E3F63': 'a3ce51',
    },
    2: {
        '442E70': 'ea6284',
        '57347E': 'cc343f',
    },
    3: {
        '624A9E': 'f89b21',
        '624A9F': 'e86a24',
    },
    4: {
        '6FBA44': '25c1f2',
        '6FBA45': '9adbf2',
    },
    5: {
        '0068A1': 'e7622e',
        '0070AA': 'eec248',
    },
    6: {
        '5A6E79': '74a534',
    },
    7: {
        '5DB1DD': 'd89028',
    },
    8: {
        '7C7269': 'c24c47',
        '958B7F': 'e67765',
    },
    9: {
        '836380': 'c4432d',
        '836381': 'be6d4f',
    },
    10: {
        '42B4B4': 'ed2024',
        '25948B': 'b22127',
    },
    11: {
        '0C4E4A': '537dbf',
        '004C6E': 'abe0f7',
    },
    12: {
        '293066': 'f37671',
        '293067': 'ee2b2c',
    },
    13: {
        '060D29': 'ee323e',
        '0C1946': 'b92530',
    },
    14: {
        '060D29': 'ee323e',
        '0C1946': 'b92530',
    },
    15: {
        '823F24': 'eb5224',
        'FAAE5A': 'f09434',
    },
    16: {
        '5F6D7A': '5a56a5',
        '5F6D7B': '817ebb',
    },
    17: {
        '0481C4': 'e37ea0',
        '0481C5': 'c53240',
    },
    18: {
        '8E4522': '3262ae',
        '277C43': '70b3e3',
    },
    19: {
        '958877': 'f37c21',
    },
    21: {
        '2B63A5': 'e47825',
    },
    22: {
        'BBB595': 'fdb813',
        '835C25': 'e27926',
    },
    23: {
        '0C94D0': '6b489d',
        '0C94D1': 'af68aa',
    },
    24: {
        'AABEE1': 'fce150',
    },
    25: {
        '075087': '598c4e',
        '124884': '8ac667',
    },
    26: {
        '5E9541': '5c9942',
    },
    29: {
        '67374D': '2e73b9',
        '83B5B6': '5ebcea',
    },
    30: {
        '5B79A2': 'f16122',
    },
    31: {
        '0068A1': '306bb1',
    },
    32: {
        '462365': 'f59cb7',
        '563D5B': 'd46793',
    },
    33: {
        'CD599A': 'a43c8d',
        'E276A7': 'ed82b4',
    },
    34: {
        '1E345D': '6ea943',
        '1E345E': '447537',
    },
    36: {
        '2A7C3C': '537dbf',
        '6DB446': 'abe0f7',
    },
    37: {
        '8D6E5C': 'ee3343',
        'B16E44': 'ba2c38',
    },
    38: {
        '5C273B': 'ed6f2f',
    },
    40: {
        'A2B164': 'a3ce4e',
        'A07958': '437c3a',
    },
    41: {
        '5E7795': 'efcf43',
        '5E7796': 'e0a137',
    },
    42: {
        '142338': '2eb28b',
        '46617C': '91cc83',
    },
    43: {
        'A9C7AD': 'ee2d31',
        '4F6269': 'bb2026',
    },
    44: {
        'AE2B7B': 'ef549f',
    },
    45: {
        '56170E': 'f7941d',
        '56170F': 'fdbb43',
    },
    46: {
        'B795A5': '7cc145',
    },
    47: {
        '757A52': '23735f',
        '60664A': '23735f',
        '52593A': '23735f',
        '88A160': '1fa776',
    },
    48: {
        '443E56': 'bc4386',
    },
    // discard
    101: {
        'B180A0': 'b0782a',
        '9F7595': 'c5985d',
    },
    102: {
        '496787': 'f47920',
        '415C7A': 'faa61f',
    },
    103: {
        '993422': 'aa1f23',
        '5F6A70': 'e12d2b',
    },
    104: {
        '5BB3E2': '477b3a',
        '45A2D6': '89c546',
        'CE542B': '89c546',
    },
    105: {
        '5D657F': '358246',
    },
    106: {
        '7F2719': 'f7f39b',
        '812819': 'ffd530',
    },
    107: {
        '7F2719': 'f7f39b',
        '812819': 'ffd530',
    },
    108: {
        '71200F': 'ea7b24',
        '4E130B': 'faa61f',
    },
    109: {
        'B1624A': 'e63047',
    },
    110: {
        '645656': '6ea54a',
        '71625F': '3f612e',
    },
    112: {
        '5B79A2': 'eca729',
        '5B79A3': 'fdda50',
    },
    113: {
        'EE008E': 'cfad2e',
        '49236C': 'f8f16b',
    },
    115: {
        '684376': 'c8b62f',
        '41375F': 'f8f16b',
    },
    116: {
        '5F8183': 'f47920',
    },
    117: {
        'AF966B': '5269b1',
    },
    118: {
        '847443': '2e88b9',
        '8D7F4E': '63c0ed',
    },
};

const DARK_EDITION_CARDS_MAIN_COLOR = {
    // keep
    1: '#5ebb46',
    2: '#cc343f',
    3: '#e86a24',
    4: '#25c1f2',
    5: '#e7622e',
    6: '#74a534',
    7: '#d89028',
    8: '#c24c47',
    9: '#c4432d',
    10: '#ed2024',
    11: '#537dbf',
    12: '#ee2b2c',
    13: '#ee323e',
    14: '#ee323e',
    15: '#eb5224',
    16: '#5a56a5',
    17: '#c53240',
    18: '#3262ae',
    19: '#f37c21',
    21: '#e47825',
    22: '#e27926',
    23: '#6b489d',
    24: '#fce150',
    25: '#598c4e',
    26: '#5c9942',
    29: '#5ebcea',
    30: '#f16122',
    31: '#306bb1',
    32: '#d46793',
    33: '#a43c8d',
    36: '#537dbf',
    37: '#ee3343',
    38: '#ed6f2f',
    34: '#447537',
    40: '#437c3a',
    41: '#e0a137',
    42: '#2eb28b',
    43: '#ee2d31',
    44: '#ef549f',
    45: '#f9a229',
    46: '#7cc145',
    47: '#1fa776',
    48: '#bc4386',
    49: '#eeb91a',
    50: '#ee3934',
    51: '#f283ae',
    52: '#d65ca3',
    53: '#f15c37',
    54: '#4f7f3a',
    55: '#659640',
    // discard
    101: '#b0782a',
    102: '#f47920',
    103: '#e12d2b',
    104: '#5a802e',
    105: '#358246',
    106: '#ffd530',
    107: '#ffd530',
    108: '#d56529',
    109: '#e63047',
    110: '#6ea54a',
    112: '#eca729',
    113: '#cfad2e',
    115: '#c8b62f',
    116: '#f47920',
    117: '#5269b1',
    118: '#2e88b9',
    119: '#41813c',
};

class CardsManager extends CardManager<Card> {
    EVOLUTION_CARDS_TYPES: number[];
    constructor (public game: KingOfTokyoGame) {
        super(game, {
            animationManager: game.animationManager,
            getId: (card) => `card-${card.id}`,
            setupDiv: (card: Card, div: HTMLElement) => {
                div.classList.add('kot-card');
                div.dataset.cardId = ''+card.id;
                div.dataset.cardType = ''+card.type;
            },
            setupFrontDiv: (card: Card, div: HTMLElement) => {
                this.setFrontBackground(div as HTMLDivElement, card.type, card.side);
        
                if (FLIPPABLE_CARDS.includes(card.type)) {
                    this.setDivAsCard(div as HTMLDivElement, 301, 0); 
                } else if (card.type < 999) {
                    this.setDivAsCard(div as HTMLDivElement, card.type + (card.side || 0));
                }
                (this.game as any).addTooltipHtml(div.id, this.getTooltip(card.type, card.side));
                if (card.tokens > 0) {
                    this.placeTokensOnCard(card);
                }
            },
            setupBackDiv: (card: Card, div: HTMLElement) => {
                const darkEdition = this.game.isDarkEdition();
                if (card.type >= 0 && card.type < 200) {
                    div.style.backgroundImage = `url('${g_gamethemeurl}img/${darkEdition ? 'dark/' : ''}card-back.jpg')`;
                } else if ((card.type >= 200 && card.type < 300) || card.type == -200) {
                    div.style.backgroundImage = `url('${g_gamethemeurl}img/card-back-costume.jpg')`;
                } else if (FLIPPABLE_CARDS.includes(card.type)) {
                    this.setFrontBackground(div as HTMLDivElement, card.type, card.side);
                    this.setDivAsCard(div as HTMLDivElement, 301, 1);
                    (this.game as any).addTooltipHtml(div.id, this.getTooltip(card.type, 1));
                } else if (card.type == 999) {
                    this.setFrontBackground(div as HTMLDivElement, card.type, card.side);
                }
            },
            isCardVisible: card => FLIPPABLE_CARDS.includes(card.type) ? card.side == 0 : card.type > 0,
            cardWidth: 132,
            cardHeight: 185,
        });
        this.EVOLUTION_CARDS_TYPES = (game as any).gamedatas.EVOLUTION_CARDS_TYPES;
    }

    private getDistance(p1: PlacedTokens, p2: PlacedTokens): number {
        return Math.sqrt((p1.x - p2.x) ** 2 + (p1.y - p2.y) ** 2);
    }

    public placeMimicOnCard(type: 'card' | 'tile', card: Card, wickednessTiles: WickednessTilesManager) {
        const divId = this.getId(card);
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

    public removeMimicOnCard(type: 'card' | 'tile', card: Card) { 
        const divId = this.getId(card);
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
        if (cardPlaced.superiorAlienTechnologyToken) {
            otherPlaces.push(cardPlaced.superiorAlienTechnologyToken);
        }
        while (protection < 1000 && otherPlaces.some(place => this.getDistance(newPlace, place) < 32)) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }

        return newPlace;
    }

    public placeTokensOnCard(card: Card, playerId?: number) {
        const cardType = card.mimicType || card.type;

        if (![28, 41].includes(cardType)) {
            return;
        }

        const divId = this.getId(card);
        const div = document.getElementById(divId).getElementsByClassName('front')[0] as HTMLDivElement;
        if (!div) {
            return;
        }
        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        const placed: PlacedTokens[] = cardPlaced.tokens;


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
                html += `energy-cube cube-shape-${Math.floor(Math.random()*5)}`;
            } else if (cardType === 41) {
                html += `smoke-cloud token`;
            }
            html += `"></div>`;
            div.insertAdjacentHTML('beforeend', html);
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
                this.game.cardsManager.updateFlippableCardTooltip(cardDiv)
            }
        });
        cards.filter(card => card.tokens > 0).forEach(card => this.placeTokensOnCard(card));
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

    private getCardNamePosition(cardTypeId: number, side: number = null) {
        switch( cardTypeId ) {
            // KEEP
            case 3: return [0, 90];
            case 9: return [35, 95];
            case 11: return [0, 85];
            case 17: return [0, 85];
            case 19: return [0, 50];
            case 27: return [35, 65];
            case 38: return this.game.isOrigins() ? null : [0, 100];
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
            case 16: return this.game.isDarkEdition() ? 6 : 5;
            case 17: return 3;
            case 18: return 5;
            case 19: return this.game.isDarkEdition() ? 6 : 4;
            case 20: return 4;
            case 21: return 5;
            case 22: return this.game.isDarkEdition() ? 5 : 3;
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
            case 42: return this.game.isDarkEdition() ? 3 : 2;
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
            case 56: return 4;
            case 57: return 5;
            case 58: return 5;
            case 59: return 5;
            case 60: return 4; 
            case 61: return 4;
            case 62: return 3;
            case 63: return 9;
            case 64: return 3;
            case 65: return 4;
            case 66: return 3;
            
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
            case 120: return 5;
            case 121: return 4;
            case 122: return 7;

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
            case 3: return _("[624A9E]Alpha [624A9F]Monster");
            case 4: return _("[6FBA44]Armor [6FBA45]Plating");
            case 5: return _("[0068A1]Background [0070AA]Dweller");
            case 6: return _("[5A6E79]Burrowing");
            case 7: return _("[5DB1DD]Camouflage");
            case 8: return _("[7C7269]Complete [958B7F]Destruction");
            case 9: return _("[836380]Media-[836381]Friendly");
            case 10: return _("[42B4B4]Eater of [25948B]the Dead");
            case 11: return _("[0C4E4A]Energy [004C6E]Hoarder");
            case 12: return _("[293066]Even [293067]Bigger");
            case 13: case 14: return _("[060D29]Extra [0C1946]Head");
            case 15: return _("[823F24]Fire [FAAE5A]Breathing");
            case 16: return _("[5F6D7A]Freeze [5F6D7B]Time");
            case 17: return _("[0481C4]Friend of Children");
            case 18: return _("[8E4522]Giant [277C43]Brain");
            case 19: return _("[958877]Gourmet");
            case 20: return _("[7A673C]Healing [DC825F]Ray");
            case 21: return _("[2B63A5]Herbivore");
            case 22: return _("[BBB595]Herd [835C25]Culler");
            case 23: return _("[0C94D0]It Has a [0C94D1]Child!");
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
            case 34: return _("[1E345D]Poison [1E345E]Quills");
            case 35: return _("[3D5C33]Poison Spit");
            case 36: return _("[2A7C3C]Psychic [6DB446]Probe");
            case 37: return _("[8D6E5C]Rapid [B16E44]Healing");
            case 38: return _("[5C273B]Regeneration");
            case 39: return _("[007DC0]Rooting for the Underdog");
            case 40: return _("[A2B164]Shrink [A07958]Ray");
            case 41: return _("[5E7795]Smoke [5E7796]Cloud");
            case 42: return this.game.isDarkEdition() ? _("[2eb28b]Lunar [91cc83]Powered") : _("[142338]Solar [46617C]Powered");
            case 43: return _("[A9C7AD]Spiked [4F6269]Tail");
            case 44: return _("[AE2B7B]Stretchy");
            case 45: return _("[56170E]Energy [56170F]Drink");
            case 46: return _("[B795A5]Urbavore");
            case 47: return _("[757A52]We're [60664A]Only [52593A]Making It [88A160]Stronger!");
            case 48: return _("[443E56]Wings");
            case 49: return _("[eeb91a]Hibernation");
            case 50: return _("[ee3934]Nanobots");
            case 51: return _("[9e4163]Natural [f283ae]Selection");
            case 52: return _("[ad457e]Reflective [d65ca3]Hide");
            case 53: return _("[f2633b]Super [faa73b]Jump");
            case 54: return _("[4f7f3a]Unstable [a9d154]DNA");
            case 55: return _("[659640]Zombify");
            case 56: return _("[8ba121]Biofuel");
            case 57: return _("[b34c9c]Draining Ray"); 
            case 58: return _("[bed62f]Electric Armor");
            case 59: return _("[de6428]Flaming Aura");   
            case 60: return _("[6db446]Gamma Blast");      
            case 61: return _("[b34c9c]Hungry Urbavore");
            case 62: return _("[1f7e7f]Jagged Tactician");
            case 63: return _("[a65096]Orb of Doom");
            case 64: return _("[806f52]Scavenger");
            case 65: return _("[1c9c85]Shrinky");
            case 66: return _("[693a3a]Bull Headed");
            
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
            case 112: return _("[5B79A2]High Altitude [5B79A3]Bombing");
            case 113: return _("[EE008E]Jet [49236C]Fighters");
            case 114: return _("[68696B]National [53575A]Guard");
            case 115: return _("[684376]Nuclear [41375F]Power Plant");
            case 116: return _("[5F8183]Skyscraper");
            case 117: return _("[AF966B]Tank");
            case 118: return _("[847443]Vast [8D7F4E]Storm");
            case 119: return _("[83aa50]Monster [41813c]pets");
            case 120: return _("[775b43]Barricades");
            case 121: return _("[6b9957]Ice Cream Truck");
            case 122: return _("[f89c4c]Supertower");

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

            const colorMapping = this.game.isDarkEdition() ? DARK_EDITION_CARDS_COLOR_MAPPINGS[cardTypeId] : null;

            return coloredCardName?.replace(/\[(\w+)\]/g, (index, color) => {
                let mappedColor = color;
                if (colorMapping?.[color]) {
                    mappedColor = colorMapping[color];
                }
                let span = `<span style="-webkit-text-stroke-color: #${mappedColor};">`;
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
            case 3: return _("<strong>Gain 1[Star]</strong> when you roll at least [dieClaw].");
            case 4: return _("<strong>Do not lose [heart] when you lose exactly 1[heart].</strong>");
            case 5: return _("<strong>You can always reroll any [dice3]</strong> you have.");
            case 6: return _("<strong>Add [diceSmash] to your Roll while you are in Tokyo. When you Yield Tokyo, the monster taking it loses 1[heart].</strong>");
            case 7: return _("If you lose [heart], roll a die for each [heart] you lost. <strong>Each [diceHeart] reduces the loss by 1[heart].</strong>");
            case 8: return _("If you roll [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy] <strong>gain 9[Star]</strong> in addition to the regular effects.");
            case 9: return _("<strong>Gain 1[Star]</strong> whenever you buy a Power card.");
            case 10: return _("<strong>Gain 3[Star]</strong> every time a Monster's [Heart] goes to [Skull].");
            case 11: return _("<strong>You gain 1[Star]</strong> for every 6[Energy] you have at the end of your turn.");
            case 12: return _("<strong>+2[Heart] when you buy this card.</strong> Your maximum [Heart] is increased to 12[Heart] as long as you own this card.");
            case 13: case 14: return _("<strong>You get 1 extra die.</strong>");
            case 15: return _("<strong>when you roll at least [dieClaw]</strong>, your neighbor(s) at the table lose 1 extra [heart].");
            case 16: return _("On a turn where you roll at least [die1][die1][die1] or more, <strong>you can take another turn</strong> with one less die.");
            case 17: return _("When you gain any [Energy] <strong>gain 1 extra [Energy].</strong>");
            case 18: return _("<strong>You have 1 extra die Roll</strong> each turn.");
            case 19: return _("When you roll at least [die1][die1][die1] <strong>gain 2 extra [Star]</strong> in addition to the regular effects.");
            case 20: return _("<strong>You can use your [diceHeart] to make other Monsters gain [Heart].</strong> Each Monster must pay you 2[Energy] (or 1[Energy] if it's their last one) for each [Heart] they gain this way");
            case 21: return _("<strong>Gain 1[Star]</strong> at the end of your turn if you don't make anyone lose [Heart].");
            case 22: return _("You can <strong>change one of your dice to a [dice1]</strong> each turn.");
            case 23: return this.game.isDarkEdition() ?
                _("If you reach [Skull], discard all your cards and tiles, remove your Counter from the Wickedness Gauge, lose all your [Star] and Yield Tokyo. <strong>Gain 10[Heart] and continue playing.</strong>") :
                _("If you reach [Skull], discard all your cards and lose all your [Star]. <strong>Gain 10[Heart] and continue playing outside Tokyo.</strong>");
            
            case 24: return _("<strong>You don't lose [Heart]<strong> if you decide to Yield Tokyo.");
            case 25: return _("During the Buy Power cards step, you can <strong>peek at the top card of the deck and buy it</strong> or put it back on top of the deck.");
            case 26: return _("At the end of your turn you can <strong>discard any [keep] cards you have to gain their full cost in [Energy].</strong>");
            case 27: return _("<strong>Choose a [keep] card any monster has in play</strong> and put a Mimic token on it. <strong>This card counts as a duplicate of that card as if you had just bought it.</strong> Spend 1[Energy] at the start of your turn to move the Mimic token and change the card you are mimicking.");
            case 28: return _("When you buy <i>${card_name}</i>, put 6[Energy] on it from the bank. At the start of your turn <strong>take 2[Energy] off and add them to your pool.</strong> When there are no [Energy] left discard this card.").replace('${card_name}', this.getCardName(cardTypeId, 'text-only'));
            case 29: return _("<strong>All of your [dieClaw] Smash all other Monsters.</strong>");
            case 30: return _("<strong>When you roll at least [die1][die2][die3], gain 2[Star],</strong> in addition to the regular effects.");
            case 31: return _("<strong>Whenever a Power card is revealed you have the option of buying it</strong> immediately.");
            case 32: return _("<strong>You can buy Power cards from other monsters.</strong> Pay them the [Energy] cost.");
            case 33: return _("Before resolving your dice, you may <strong>change one die to any result</strong>. Discard when used.");
            case 34: return _("When you roll at least [dice2][dice2][dice2] or more, <strong>add [dieClaw][dieClaw] to your Roll</strong>.");
            case 35: return _("Give one <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each <i>Poison</i> token they have on them.</strong> A <i>Poison</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 36: return _("<strong>You can reroll a die of your choice after the last Roll of each other Monster.</strong> If the result of your reroll is [dieHeart], discard this card.");
            case 37: return _("Spend 2[Energy] at any time to <strong>gain 1[Heart].</strong> This may be used to prevent your health from being reduced to [Skull].");
            case 38: return _("When you gain [Heart], you <strong>gain 1 extra [Heart].</strong>");
            case 39: return _("At the end of your turn, if you have the fewest [Star], <strong>gain 1 [Star].</strong>");
            case 40: return _("Give 1 <i>Shrink Ray</i> to each Monster you Smash with your [diceSmash]. <strong>At the beginning of their turn, Monster roll 1 less dice for each <i>Shrink Ray</i> token they have on them</strong>. A <i>Shrink Ray</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 41: return _("Place 3 <i>Smoke</i> counters on this card. <strong>Spend 1 <i>Smoke</i> counter for an extra Roll.</strong> Discard this card when all <i>Smoke</i> counters are spent.");
            case 42: return _("At the end of your turn <strong>gain 1[Energy] if you have no [Energy].</strong>");
            case 43: return _("<strong>If you roll at least one [diceSmash], add [diceSmash]</strong> to your Roll.");
            case 44: return _("Before resolving your dice, you can spend 2[Energy] to <strong>change one of your dice to any result.</strong>");
            case 45: return _("Spend 1[Energy] to <strong>get 1 extra die Roll.</strong>");
            case 46: return _("<strong>Gain 1 extra [Star]</strong> when beginning your turn in Tokyo. If you are in Tokyo and you roll at least one [diceSmash], <strong>add [diceSmash] to your Roll.</strong>");
            case 47: return _("When you lose at least 2[Heart] you <strong>gain 1[Energy].</strong>");
            case 48: return _("<strong>Spend 2[Energy] to not lose [Heart]<strong> this turn.");
            case 49: return `<div><i>${_("You CANNOT buy this card while in TOKYO")}</i></div>` + _("<strong>You no longer take damage.</strong> You cannot move, even if Tokyo is empty. You can no longer buy cards. <strong>The only results you can use are [diceHeart] and [diceEnergy].</strong> Discard this card to end its effects and restrictions immediately.");
            case 50: return _("At the start of your turn, if you have fewer than 3[Heart], <strong>gain 2[Heart].</strong>");
            case 51: return '<div><strong>+4[Energy] +4[Heart]</strong></div>' + _("<strong>Use an extra die.</strong> If you ever end one of your turns with at least [dice3], you lose all your [Heart].");
            case 52: return _("<strong>Any Monster who makes you lose [Heart] loses 1[Heart]</strong> as well.");
            case 53: return _("Once each player’s turn, you may spend 1[Energy] <strong>to negate the loss of 1[Heart].</strong>");
            case 54: return _("When you Yield Tokyo, <strong>you may exchange this card</strong> with a card of your choice from the Monster who Smashed you.");
            case 55: return _("If you reach [Skull] for the first time in this game, <strong>discard all your cards and tiles, remove your Counter from the Wickedness Gauge, lose all your [Star], Yield Tokyo, gain 12[Heart] and continue playing.</strong> For the rest of the game, your maximum [Heart] is increased to 12[Heart] and <strong>you can’t use [diceHeart] anymore.</strong>");
            case 56: return _("You may use [dieHeart] as [dieEnergy].");
            case 57: return _("When you roll at least 4 of a kind, <strong>steal 1[Star] from the Monster(s) with the most [Star].</strong>");
            case 58: return _("When you lose any [Heart], you may spend 1[Energy] to <strong>reduce the loss of [Heart] by 1.</strong>");
            case 59: return _("When you roll at least 4 of a kind, <strong>all other Monsters lose 1[Heart].</strong>");
            case 60: return _("When you take control of Tokyo, <strong>all other Monsters lose 1[Heart].</strong>");
            case 61: return _("<strong>Gain 1[Star]</strong> when you take control of Tokyo.");
            case 62: return _("When you Yield Tokyo, <strong>the Monster taking it loses 1[Heart]</strong> and you <strong>gain 1[Energy].</strong>");
            case 63: return _("<strong>Other Monsters lose 1[Heart]</strong> each time they reroll.");
            case 64: return _("<strong>You may buy cards from the discard pile.</strong> [Discard] cards bought this way are put on the bottom of the deck.");
            case 65: return _("<strong>You may use [die2] as [die1].");
            case 66: return _("<strong>Gain 1[Star]</strong> when you are able to Yield Tokyo but choose not to.");

            // DISCARD
            case 101: return "<strong>+ 3[Star].</strong>";
            case 102: return "<strong>+ 2[Star].</strong>";
            case 103: return "<strong>+ 1[Star].</strong>";
            case 104: return _("<strong>+ 2[Star] and take control of Tokyo</strong> if you don't already control it. All other Monsters must Yield Tokyo.");
            case 105: return "<strong>+ 9[Energy].</strong>";
            case 106: case 107: return _("<strong>All other Monsters lose 5[Star].</strong>");
            case 108: return _("<strong>All other Monsters lose 2[Heart].</strong>");
            case 109: return _("<strong>Take another turn</strong> after this one");
            case 110: return _("<strong>+ 2[Star] and all other monsters lose 3[Heart].</strong>");
            case 111: return "<strong>+ 2[Heart]</strong>";
            case 112: return _("<strong>All Monsters</strong> (including you) <strong>lose 3[Heart].</strong>");
            case 113: return "<strong>+5[Star] -4[Heart].</strong>";
            case 114: return "<strong>+2[Star] -2[Heart].</strong>";
            case 115: return "<strong>+2[Star] +3[Heart].</strong>";
            case 116: return "<strong>+4[Star].";
            case 117: return "<strong>+4[Star] -3[Heart].</strong>";
            case 118: return _("<strong>+ 2[Star] and all other Monsters lose 1[Energy] for every 2[Energy]</strong> they have.");
            case 119: return _("<strong>All Monsters</strong> (including you) <strong>lose 3[Star].</strong>");
            case 120: return _("<strong>All other Monsters lose 3[Star].</strong>");
            case 121: return "<strong>+1[Star] +2[Heart].</strong>";
            case 122: return "<strong>+5[Star].";

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
        
        (this.game as any).addTooltipHtml(cardDiv.id, this.getTooltip(type, Number(cardDiv.dataset.side)));
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
        cardDiv.classList.add('kot-card');
        cardDiv.dataset.design = cardType < 200 && this.game.isDarkEdition() ? 'dark-edition' : 'standard';
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
        if (this.game.isDarkEdition() && DARK_EDITION_CARDS_MAIN_COLOR[cardType]) {
            cardDiv.style.setProperty('--main-color', DARK_EDITION_CARDS_MAIN_COLOR[cardType]);
        }

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
    
    private setFrontBackground(cardDiv: HTMLDivElement, cardType: number, side: 0 | 1 = null) {
        const darkEdition = this.game.isDarkEdition();
        const version: 'base' | 'dark' = darkEdition ? 'dark' : 'base';

        if (cardType < 100) {
            const originsCard = cardType >= 56;
            const keepcardsurl =  originsCard ? 
                `${g_gamethemeurl}img/cards/cards-keep-origins.jpg` : 
                `${g_gamethemeurl}img/${darkEdition ? 'dark/' : ''}keep-cards.jpg`;
            cardDiv.style.backgroundImage = `url('${keepcardsurl}')`;
            
            const index = originsCard ?
                cardType - 56 : 
                KEEP_CARDS_LIST[version].findIndex(type => type == cardType);

            cardDiv.style.backgroundPositionX = `${(index % 10) * 100 / 9}%`;
            cardDiv.style.backgroundPositionY = `${Math.floor(index / 10) * 100 / (originsCard ? 1 : 4)}%`;

            if (cardType == 38 && this.game.isOrigins()) {
                cardDiv.style.backgroundImage = `url('${g_gamethemeurl}img/cards/cards-regeneration-origins.jpg')`;
                cardDiv.style.backgroundPosition = `0% 0%`;
            }
        } else if (cardType < 200) {
            const originsCard = cardType >= 120;
            const discardcardsurl = originsCard ? 
                `${g_gamethemeurl}img/cards/cards-discard-origins.jpg` : 
                `${g_gamethemeurl}img/${darkEdition ? 'dark/' : ''}discard-cards.jpg`;

            const index = originsCard ?
                cardType - 120 : 
                DISCARD_CARDS_LIST[version].findIndex(type => type == cardType % 100);

            cardDiv.style.backgroundImage = `url('${discardcardsurl}')`;
            cardDiv.style.backgroundPositionX = `${(index % 10) * 100 / 9}%`;
            cardDiv.style.backgroundPositionY = `${Math.floor(index / 10) * 100}%`;
        } else if (cardType < 300) {
            const index = COSTUME_CARDS_LIST.findIndex(type => type == cardType % 100);
            const costumecardsurl = `${g_gamethemeurl}img/costume-cards.jpg`;
            cardDiv.style.backgroundImage = `url('${costumecardsurl}')`;
            cardDiv.style.backgroundPositionX = `${(index % 10) * 100 / 9}%`;
            cardDiv.style.backgroundPositionY = `${Math.floor(index / 10) * 100}%`;
        } else if (cardType < 400) {
            const transformationcardsurl = `${g_gamethemeurl}img/transformation-cards.jpg`;
            cardDiv.style.backgroundImage = `url('${transformationcardsurl}')`;
            cardDiv.style.backgroundPositionX = `${side * 100}%`;
            cardDiv.style.backgroundPositionY = '0%';
        } else if (cardType == 999) {
            const anubiscardsurl = `${g_gamethemeurl}img/anubis-cards.jpg`;
            cardDiv.style.backgroundImage = `url(${anubiscardsurl}`;
            cardDiv.style.backgroundPositionX = '0%';
            cardDiv.style.backgroundPositionY = '0%';
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

    public generateCardDiv(card: Card): HTMLDivElement {
        const tempDiv: HTMLDivElement = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = `${CARD_WIDTH}px`;
        tempDiv.style.height = `${CARD_HEIGHT}px`;
        tempDiv.style.position = `relative`;
        tempDiv.style.backgroundImage = `url('${g_gamethemeurl}img/${this.getImageName(card.type)}-cards.jpg')`;
        const imagePosition = ((card.type + card.side) % 100) - 1;
        const image_items_per_row = 10;
        var row = Math.floor(imagePosition / image_items_per_row);
        const xBackgroundPercent = (imagePosition - (row * image_items_per_row)) * 100;
        const yBackgroundPercent = row * 100;
        tempDiv.style.backgroundPosition = `-${xBackgroundPercent}% -${yBackgroundPercent}%`;

        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type + (card.side || 0));
        document.body.removeChild(tempDiv);
            
        return tempDiv;
    }

    public getMimickedCardText(mimickedCard: Card): string {
        let mimickedCardText = '-';
        if (mimickedCard) {
            const tempDiv = this.generateCardDiv(mimickedCard);

            mimickedCardText = `<br>${tempDiv.outerHTML}`;
        }

        return mimickedCardText;
    }

    public changeMimicTooltip(mimicCardId: string, mimickedCardText: string) {
        (this.game as any).addTooltipHtml(mimicCardId, this.getTooltip(27) + `<br>${_('Mimicked card:')} ${mimickedCardText}`);
    }

    
    public placeSuperiorAlienTechnologyTokenOnCard(card: Card) {
        const divId = this.getId(card);

        const div = document.getElementById(divId);
        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        
        cardPlaced.superiorAlienTechnologyToken = this.getPlaceOnCard(cardPlaced);

        let html = `<div id="${divId}-superior-alien-technology-token" style="left: ${cardPlaced.superiorAlienTechnologyToken.x - 16}px; top: ${cardPlaced.superiorAlienTechnologyToken.y - 16}px;" class="card-token ufo token"></div>`;
        dojo.place(html, divId);

        div.dataset.placed = JSON.stringify(cardPlaced);
    }
}