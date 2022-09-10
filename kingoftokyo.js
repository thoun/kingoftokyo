function slideToObjectAndAttach(game, object, destinationId, posX, posY) {
    var _this = this;
    return new Promise(function (resolve) {
        var destination = document.getElementById(destinationId);
        if (destination.contains(object)) {
            return resolve(false);
        }
        if (document.visibilityState === 'hidden' || game.instantaneousMode) {
            destination.appendChild(object);
            resolve(true);
        }
        else {
            object.style.zIndex = '10';
            var animation = (posX || posY) ?
                game.slideToObjectPos(object, destinationId, posX, posY) :
                game.slideToObject(object, destinationId);
            dojo.connect(animation, 'onEnd', dojo.hitch(_this, function () {
                object.style.top = 'unset';
                object.style.left = 'unset';
                object.style.position = 'relative';
                object.style.zIndex = 'unset';
                destination.appendChild(object);
                resolve(true);
            }));
            animation.play();
        }
    });
}
function transitionToObjectAndAttach(game, object, destinationId, zoom) {
    return new Promise(function (resolve) {
        var destination = document.getElementById(destinationId);
        if (destination.contains(object)) {
            return resolve(false);
        }
        if (document.visibilityState === 'hidden' || game.instantaneousMode) {
            destination.appendChild(object);
            resolve(true);
        }
        else {
            var destinationBR = document.getElementById(destinationId).getBoundingClientRect();
            var originBR = object.getBoundingClientRect();
            var deltaX = destinationBR.left - originBR.left;
            var deltaY = destinationBR.top - originBR.top;
            object.style.zIndex = '10';
            object.style.transition = "transform 0.5s linear";
            object.style.transform = "translate(" + deltaX / zoom + "px, " + deltaY / zoom + "px)";
            setTimeout(function () {
                object.style.zIndex = null;
                object.style.transition = null;
                object.style.transform = null;
                destination.appendChild(object);
                resolve(true);
            }, 500);
        }
    });
}
function formatTextIcons(rawText) {
    if (!rawText) {
        return '';
    }
    return rawText
        .replace(/\[Star\]/ig, '<span class="icon points"></span>')
        .replace(/\[Heart\]/ig, '<span class="icon health"></span>')
        .replace(/\[Energy\]/ig, '<span class="icon energy"></span>')
        .replace(/\[Skull\]/ig, '<span class="icon dead"></span>')
        .replace(/\[dice1\]/ig, '<span class="dice-icon dice1"></span>')
        .replace(/\[dice2\]/ig, '<span class="dice-icon dice2"></span>')
        .replace(/\[dice3\]/ig, '<span class="dice-icon dice3"></span>')
        .replace(/\[diceHeart\]/ig, '<span class="dice-icon dice4"></span>')
        .replace(/\[diceEnergy\]/ig, '<span class="dice-icon dice5"></span>')
        .replace(/\[diceSmash\]/ig, '<span class="dice-icon dice6"></span>')
        .replace(/\[dieFateEye\]/ig, '<span class="dice-icon die-of-fate eye"></span>')
        .replace(/\[dieFateRiver\]/ig, '<span class="dice-icon die-of-fate river"></span>')
        .replace(/\[dieFateSnake\]/ig, '<span class="dice-icon die-of-fate snake"></span>')
        .replace(/\[dieFateAnkh\]/ig, '<span class="dice-icon die-of-fate ankh"></span>')
        .replace(/\[berserkDieEnergy\]/ig, '<span class="dice-icon berserk dice1"></span>')
        .replace(/\[berserkDieDoubleEnergy\]/ig, '<span class="dice-icon berserk dice2"></span>')
        .replace(/\[berserkDieSmash\]/ig, '<span class="dice-icon berserk dice3"></span>')
        .replace(/\[berserkDieDoubleSmash\]/ig, '<span class="dice-icon berserk dice5"></span>')
        .replace(/\[berserkDieSkull\]/ig, '<span class="dice-icon berserk dice6"></span>')
        .replace(/\[snowflakeToken\]/ig, '<span class="icy-reflection token"></span>')
        .replace(/\[ufoToken\]/ig, '<span class="ufo token"></span>')
        .replace(/\[alienoidToken\]/ig, '<span class="alienoid token"></span>')
        .replace(/\[targetToken\]/ig, '<span class="target token"></span>')
        .replace(/\[keep\]/ig, "<span class=\"card-keep-text\"><span class=\"outline\">" + _('Keep') + "</span><span class=\"text\">" + _('Keep') + "</span></span>");
}
var CARD_WIDTH = 132;
var CARD_HEIGHT = 185;
var EVOLUTION_SIZE = 198;
var KEEP_CARDS_LIST = {
    base: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48],
    dark: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25, 26, 29, 30, 31, 32, 33, 34, 36, 37, 38, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55],
};
var DISCARD_CARDS_LIST = {
    base: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
    dark: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 15, 16, 17, 18, 19],
};
var COSTUME_CARDS_LIST = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
var TRANSFORMATION_CARDS_LIST = [1];
var FLIPPABLE_CARDS = [301];
var DARK_EDITION_CARDS_COLOR_MAPPINGS = {
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
        '624A9F': 'e86a24', // TODODE 624A9F not present in current card, to add ?
    },
    4: {
        '6FBA44': '25c1f2',
        '6FBA45': '9adbf2', // TODODE 6FBA45 not present in current card, to add ?
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
        '836381': 'be6d4f', // TODODE 836381 not present in current card, to add ?
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
        '293067': 'ee2b2c', // TODODE 293067 not present in current card, to add ?
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
        '5F6D7B': '817ebb', // TODODE 5F6D7B not present in current card, to add ?
    },
    17: {
        '0481C4': 'e37ea0',
        '0481C5': 'c53240', // TODODE 0481C5 not present in current card, to add ? before Children
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
        '0C94D1': 'af68aa', // TODODE 0C94D1 not present in current card, to add ? before Child
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
        '1E345E': '447537', // TODODE 1E345E not present in current card, to add ?
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
        '5E7796': 'e0a137', // TODODE 5E7796 not present in current card, to add ?
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
        '56170F': 'fdbb43', // TODODE 56170F not present in current card, to add ?
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
        '5B79A3': 'fdda50', // TODODE 5B79A3 not present in current card, to add ?
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
var DARK_EDITION_CARDS_MAIN_COLOR = {
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
var Cards = /** @class */ (function () {
    function Cards(game) {
        this.game = game;
    }
    Cards.prototype.setupCards = function (stocks) {
        var darkEdition = this.game.isDarkEdition();
        var version = darkEdition ? 'dark' : 'base';
        var costumes = this.game.isHalloweenExpansion();
        var transformation = this.game.isMutantEvolutionVariant();
        var goldenscarab = this.game.isAnubisExpansion();
        stocks.forEach(function (stock) {
            var keepcardsurl = g_gamethemeurl + "img/" + (darkEdition ? 'dark/' : '') + "keep-cards.jpg";
            KEEP_CARDS_LIST[version].forEach(function (id, index) {
                stock.addItemType(id, id, keepcardsurl, index);
            });
            var discardcardsurl = g_gamethemeurl + "img/" + (darkEdition ? 'dark/' : '') + "discard-cards.jpg";
            DISCARD_CARDS_LIST[version].forEach(function (id, index) {
                stock.addItemType(100 + id, 100 + id, discardcardsurl, index);
            });
            if (costumes) {
                var costumecardsurl_1 = g_gamethemeurl + "img/costume-cards.jpg";
                COSTUME_CARDS_LIST.forEach(function (id, index) {
                    stock.addItemType(200 + id, 200 + id, costumecardsurl_1, index);
                });
            }
            if (transformation) {
                var transformationcardsurl_1 = g_gamethemeurl + "img/transformation-cards.jpg";
                COSTUME_CARDS_LIST.forEach(function (id, index) {
                    stock.addItemType(300 + id, 300 + id, transformationcardsurl_1, index);
                });
            }
            if (goldenscarab) {
                var anubiscardsurl = g_gamethemeurl + "img/anubis-cards.jpg";
                stock.addItemType(999, 999, anubiscardsurl, 0);
            }
        });
    };
    Cards.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    Cards.prototype.placeMimicOnCard = function (type, stock, card, wickednessTiles) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        if (type === 'tile') {
            var html = "<div id=\"" + divId + "-mimic-token-tile\" class=\"card-token mimic-tile stockitem\"></div>";
            dojo.place(html, divId);
            div.classList.add('wickedness-tile-stock');
            wickednessTiles.setDivAsCard(document.getElementById(divId + "-mimic-token-tile"), 106);
        }
        else {
            var div_1 = document.getElementById(divId);
            var cardPlaced = div_1.dataset.placed ? JSON.parse(div_1.dataset.placed) : { tokens: [] };
            cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);
            var html = "<div id=\"" + divId + "-mimic-token\" style=\"left: " + (cardPlaced.mimicToken.x - 16) + "px; top: " + (cardPlaced.mimicToken.y - 16) + "px;\" class=\"card-token mimic token\"></div>";
            dojo.place(html, divId);
            div_1.dataset.placed = JSON.stringify(cardPlaced);
        }
    };
    Cards.prototype.removeMimicOnCard = function (type, stock, card) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        if (type === 'tile') {
            if (document.getElementById(divId + "-mimic-token-tile")) {
                this.game.fadeOutAndDestroy(divId + "-mimic-token-tile");
            }
            div.classList.remove('wickedness-tile-stock');
        }
        else {
            var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
            cardPlaced.mimicToken = null;
            if (document.getElementById(divId + "-mimic-token")) {
                this.game.fadeOutAndDestroy(divId + "-mimic-token");
            }
            div.dataset.placed = JSON.stringify(cardPlaced);
        }
    };
    Cards.prototype.getPlaceOnCard = function (cardPlaced) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 100 + 16,
        };
        var protection = 0;
        var otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        if (cardPlaced.superiorAlienTechnologyToken) {
            otherPlaces.push(cardPlaced.superiorAlienTechnologyToken);
        }
        while (protection < 1000 && otherPlaces.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }
        return newPlace;
    };
    Cards.prototype.placeTokensOnCard = function (stock, card, playerId) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        var placed = cardPlaced.tokens;
        var cardType = card.mimicType || card.type;
        // remove tokens
        for (var i = card.tokens; i < placed.length; i++) {
            if (cardType === 28 && playerId) {
                this.game.slideToObjectAndDestroy(divId + "-token" + i, "energy-counter-" + playerId);
            }
            else {
                this.game.fadeOutAndDestroy(divId + "-token" + i);
            }
        }
        placed.splice(card.tokens, placed.length - card.tokens);
        // add tokens
        for (var i = placed.length; i < card.tokens; i++) {
            var newPlace = this.getPlaceOnCard(cardPlaced);
            placed.push(newPlace);
            var html = "<div id=\"" + divId + "-token" + i + "\" style=\"left: " + (newPlace.x - 16) + "px; top: " + (newPlace.y - 16) + "px;\" class=\"card-token ";
            if (cardType === 28) {
                html += "energy-cube cube-shape-" + Math.floor(Math.random() * 5);
            }
            else if (cardType === 41) {
                html += "smoke-cloud token";
            }
            html += "\"></div>";
            dojo.place(html, divId);
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    Cards.prototype.addCardsToStock = function (stock, cards, from) {
        var _this = this;
        if (!cards.length) {
            return;
        }
        cards.forEach(function (card) {
            stock.addToStockWithId(card.type, "" + card.id, from);
            var cardDiv = document.getElementById(stock.container_div.id + "_item_" + card.id);
            cardDiv.dataset.side = '' + card.side;
            if (card.side !== null) {
                _this.game.cards.updateFlippableCardTooltip(cardDiv);
            }
        });
        cards.filter(function (card) { return card.tokens > 0; }).forEach(function (card) { return _this.placeTokensOnCard(stock, card); });
    };
    Cards.prototype.moveToAnotherStock = function (sourceStock, destinationStock, card) {
        if (sourceStock === destinationStock) {
            return;
        }
        var sourceStockItemId = sourceStock.container_div.id + "_item_" + card.id;
        if (document.getElementById(sourceStockItemId)) {
            this.addCardsToStock(destinationStock, [card], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById("" + card.id);
        }
        else {
            console.warn(sourceStockItemId + " not found in ", sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [card], sourceStock.container_div.id);
        }
    };
    Cards.prototype.exchangeCardFromStocks = function (sourceStock, destinationStock, cardOnSource, cardOnDestination) {
        if (sourceStock === destinationStock) {
            return;
        }
        var sourceStockItemId = sourceStock.container_div.id + "_item_" + cardOnSource.id;
        var destinationStockItemId = destinationStock.container_div.id + "_item_" + cardOnDestination.id;
        this.addCardsToStock(destinationStock, [cardOnSource], sourceStockItemId);
        this.addCardsToStock(sourceStock, [cardOnDestination], destinationStockItemId);
        sourceStock.removeFromStockById("" + cardOnSource.id);
        destinationStock.removeFromStockById("" + cardOnDestination.id);
    };
    Cards.prototype.getCardNamePosition = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        switch (cardTypeId) {
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
            case 106:
            case 107: return [35, 65];
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
    };
    Cards.prototype.getCardCost = function (cardTypeId) {
        switch (cardTypeId) {
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
            case 13:
            case 14: return 7;
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
            // DISCARD
            case 101: return 5;
            case 102: return 4;
            case 103: return 3;
            case 104: return 5;
            case 105: return 8;
            case 106:
            case 107: return 7;
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
    };
    Cards.prototype.getColoredCardName = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        switch (cardTypeId) {
            // KEEP
            case 1: return _("[724468]Acid [6E3F63]Attack");
            case 2: return _("[442E70]Alien [57347E]Origin");
            case 39999: return _("[624A9E]Alpha Monster"); // TODODEAFTER remove
            case 3: return _("[624A9E]Alpha [624A9F]Monster");
            case 49999: return _("[6FBA44]Armor Plating"); // TODODEAFTER remove
            case 4: return _("[6FBA44]Armor [6FBA45]Plating");
            case 5: return _("[0068A1]Background [0070AA]Dweller");
            case 6: return _("[5A6E79]Burrowing");
            case 7: return _("[5DB1DD]Camouflage");
            case 8: return _("[7C7269]Complete [958B7F]Destruction");
            case 99999: return _("[836380]Media-Friendly"); // TODODEAFTER remove
            case 9: return _("[836380]Media-[836381]Friendly");
            case 10: return _("[42B4B4]Eater of [25948B]the Dead");
            case 11: return _("[0C4E4A]Energy [004C6E]Hoarder");
            case 129999: return _("[293066]Even Bigger"); // TODODEAFTER remove
            case 12: return _("[293066]Even [293067]Bigger");
            case 13:
            case 14: return _("[060D29]Extra [0C1946]Head");
            case 15: return _("[823F24]Fire [FAAE5A]Breathing");
            case 169999: return _("[5F6D7A]Freeze Time"); // TODODEAFTER remove
            case 16: return _("[5F6D7A]Freeze [5F6D7B]Time");
            case 179999: return _("[0481C4]Friend of [0481C5]Children"); // TODODEAFTER remove
            case 17: return _("[0481C4]Friend of Children");
            case 18: return _("[8E4522]Giant [277C43]Brain");
            case 19: return _("[958877]Gourmet");
            case 20: return _("[7A673C]Healing [DC825F]Ray");
            case 21: return _("[2B63A5]Herbivore");
            case 22: return _("[BBB595]Herd [835C25]Culler");
            case 239999: return _("[0C94D0]It Has a Child!"); // TODODEAFTER remove
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
            case 349999: return _("[1E345D]Poison Quills"); // TODODEAFTER remove
            case 34: return _("[1E345D]Poison [1E345E]Quills");
            case 35: return _("[3D5C33]Poison Spit");
            case 36: return _("[2A7C3C]Psychic [6DB446]Probe");
            case 37: return _("[8D6E5C]Rapid [B16E44]Healing");
            case 38: return _("[5C273B]Regeneration");
            case 39: return _("[007DC0]Rooting for the Underdog");
            case 40: return _("[A2B164]Shrink [A07958]Ray");
            case 419999: return _("[5E7795]Smoke Cloud"); // TODODEAFTER remove
            case 41: return _("[5E7795]Smoke [5E7796]Cloud");
            case 42: return this.game.isDarkEdition() ? /*TODODE_*/ ("[2eb28b]Lunar [91cc83]Powered") : _("[142338]Solar [46617C]Powered");
            case 43: return _("[A9C7AD]Spiked [4F6269]Tail");
            case 44: return _("[AE2B7B]Stretchy");
            case 459999: return _("[56170E]Energy Drink"); // TODODEAFTER remove
            case 45: return _("[56170E]Energy [56170F]Drink");
            case 46: return _("[B795A5]Urbavore");
            case 47: return _("[757A52]We're [60664A]Only [52593A]Making It [88A160]Stronger!");
            case 48: return _("[443E56]Wings");
            case 49: return /*TODODE_*/ ("[eeb91a]Hibernation");
            case 50: return /*TODODE_*/ ("[ee3934]Nanobots");
            case 51: return /*TODODE_*/ ("[9e4163]Natural [f283ae]Selection");
            case 52: return /*TODODE_*/ ("[ad457e]Reflective [d65ca3]Hide");
            case 53: return /*TODODE_*/ ("[f2633b]Super [faa73b]Jump");
            case 54: return /*TODODE_*/ ("[4f7f3a]Unstable [a9d154]DNA");
            case 55: return /*TODODE_*/ ("[659640]Zombify");
            // DISCARD
            case 101: return _("[B180A0]Apartment [9F7595]Building");
            case 102: return _("[496787]Commuter [415C7A]Train");
            case 103: return _("[993422]Corner [5F6A70]Store");
            case 104: return _("[5BB3E2]Death [45A2D6]From [CE542B]Above");
            case 105: return _("[5D657F]Energize");
            case 106:
            case 107: return _("[7F2719]Evacuation [812819]Orders");
            case 108: return _("[71200F]Flame [4E130B]Thrower");
            case 109: return _("[B1624A]Frenzy");
            case 110: return _("[645656]Gas [71625F]Refinery");
            case 111: return _("[815321]Heal");
            case 1129999: return _("[5B79A2]High Altitude Bombing"); // TODODEAFTER remove
            case 112: return _("[5B79A2]High Altitude [5B79A3]Bombing");
            case 113: return _("[EE008E]Jet [49236C]Fighters");
            case 114: return _("[68696B]National [53575A]Guard");
            case 115: return _("[684376]Nuclear [41375F]Power Plant");
            case 116: return _("[5F8183]Skyscraper");
            case 117: return _("[AF966B]Tank");
            case 118: return _("[847443]Vast [8D7F4E]Storm");
            case 119: return /*TODODE _*/ ("[83aa50]Monster [41813c]pets");
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
    };
    Cards.prototype.getCardName = function (cardTypeId, state, side) {
        if (side === void 0) { side = null; }
        var coloredCardName = this.getColoredCardName(cardTypeId, side);
        if (state == 'text-only') {
            return coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, '');
        }
        else if (state == 'span') {
            var first_1 = true;
            var colorMapping_1 = this.game.isDarkEdition() ? DARK_EDITION_CARDS_COLOR_MAPPINGS[cardTypeId] : null;
            return (coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, function (index, color) {
                var mappedColor = color;
                if (colorMapping_1 === null || colorMapping_1 === void 0 ? void 0 : colorMapping_1[color]) {
                    mappedColor = colorMapping_1[color];
                }
                var span = "<span style=\"-webkit-text-stroke-color: #" + mappedColor + ";\">";
                if (first_1) {
                    first_1 = false;
                }
                else {
                    span = "</span>" + span;
                }
                return span;
            })) + ("" + (first_1 ? '' : '</span>'));
        }
        return null;
    };
    Cards.prototype.getCardDescription = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        switch (cardTypeId) {
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
            case 13:
            case 14: return _("<strong>You get 1 extra die.</strong>");
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
            case 28: return dojo.string.substitute(_("When you buy <i>${card_name}</i>, put 6[Energy] on it from the bank. At the start of your turn <strong>take 2[Energy] off and add them to your pool.</strong> When there are no [Energy] left discard this card."), { 'card_name': this.getCardName(cardTypeId, 'text-only') });
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
            case 49: return "<div><i>" + ("You CANNOT buy this card while in TOKYO") + "</i></div>" + /*_TODODE*/ ("<strong>You no longer take damage.</strong> You cannot move, even if Tokyo is empty. You can no longer buy cards. <strong>The only results you can use are [diceHeart] and [diceEnergy].</strong> Discard this card to end its effects and restrictions immediately.");
            case 50: return /*_TODODE*/ ("At the start of your turn, if you have fewer than 3[Heart], <strong>gain 2[Heart].</strong>");
            case 51: return '<div><strong>+4[Energy] +4[Heart]</strong></div>' + /*TODODE_*/ ("<strong>Use an extra die.</strong> If you ever end one of your turns with at least [dice3], you lose all your [Heart].");
            case 52: return /*TODODE_*/ ("<strong>Any Monster who makes you lose [Heart] loses 1[Heart]</strong> as well.");
            case 53: return /*TODODE_*/ ("Once each player’s turn, you may spend 1[Energy] <strong>to negate the loss of 1[Heart].</strong>");
            case 54: return /*TODODE_*/ ("When you Yield Tokyo, <strong>you may exchange this card</strong> with a card of your choice from the Monster who Smashed you.");
            case 55: return /*TODODE_*/ ("If you reach [Skull] for the first time in this game, <strong>discard all your cards and tiles, remove your Counter from the Wickedness Gauge, lose all your [Star], Yield Tokyo, gain 12[Heart] and continue playing.</strong> For the rest of the game, your maximum [Heart] is increased to 12[Heart] and <strong>you can’t use [diceHeart] anymore.</strong>");
            // TODODE
            // DISCARD
            case 101: return "<strong>+ 3[Star].</strong>";
            case 102: return "<strong>+ 2[Star].</strong>";
            case 103: return "<strong>+ 1[Star].</strong>";
            case 104: return _("<strong>+ 2[Star] and take control of Tokyo</strong> if you don't already control it.");
            case 105: return "<strong>+ 9[Energy].</strong>";
            case 106:
            case 107: return _("<strong>All other Monsters lose 5[Star].</strong>");
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
    };
    Cards.prototype.updateFlippableCardTooltip = function (cardDiv) {
        var type = Number(cardDiv.dataset.type);
        if (!FLIPPABLE_CARDS.includes(type)) {
            return;
        }
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(type, Number(cardDiv.dataset.side)));
    };
    Cards.prototype.getTooltip = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        if (cardTypeId === 999) {
            return _("The Golden Scarab affects certain Curse cards. At the start of the game, the player who will play last gets the Golden Scarab.");
        }
        var cost = this.getCardCost(cardTypeId);
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId, 'text-only', side) + "</strong></p>";
        if (cost !== null) {
            tooltip += "<p class=\"cost\">" + dojo.string.substitute(_("Cost : ${cost}"), { 'cost': cost }) + " <span class=\"icon energy\"></span></p>";
        }
        tooltip += "<p>" + formatTextIcons(this.getCardDescription(cardTypeId, side)) + "</p>";
        if (FLIPPABLE_CARDS.includes(cardTypeId) && side !== null) {
            var otherSide = side == 1 ? 0 : 1;
            var tempDiv = document.createElement('div');
            tempDiv.classList.add('stockitem');
            tempDiv.style.width = CARD_WIDTH + "px";
            tempDiv.style.height = CARD_HEIGHT + "px";
            tempDiv.style.position = "relative";
            tempDiv.style.backgroundImage = "url('" + g_gamethemeurl + "img/" + this.getImageName(cardTypeId) + "-cards.jpg')";
            tempDiv.style.backgroundPosition = "-" + otherSide * 100 + "% 0%";
            document.body.appendChild(tempDiv);
            this.setDivAsCard(tempDiv, cardTypeId, otherSide);
            document.body.removeChild(tempDiv);
            tooltip += "<p>" + _("Other side :") + "<br>" + tempDiv.outerHTML + "</p>";
        }
        tooltip += "</div>";
        return tooltip;
    };
    Cards.prototype.setupNewCard = function (cardDiv, cardType) {
        if (FLIPPABLE_CARDS.includes(cardType)) {
            cardDiv.dataset.type = '' + cardType;
            cardDiv.classList.add('card-inner');
            dojo.place("\n                <div class=\"card-side front\"></div>\n                <div class=\"card-side back\"></div>\n            ", cardDiv);
            this.setDivAsCard(cardDiv.getElementsByClassName('front')[0], 301, 0);
            this.setDivAsCard(cardDiv.getElementsByClassName('back')[0], 301, 1);
        }
        else {
            if (cardType !== 999) { // no text for golden scarab
                this.setDivAsCard(cardDiv, cardType);
            }
            this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
        }
    };
    Cards.prototype.getCardTypeName = function (cardType) {
        if (cardType < 100) {
            return _('Keep');
        }
        else if (cardType < 200) {
            return _('Discard');
        }
        else if (cardType < 300) {
            return _('Costume');
        }
        else if (cardType < 400) {
            return _('Transformation');
        }
    };
    Cards.prototype.getCardTypeClass = function (cardType) {
        if (cardType < 100) {
            return 'keep';
        }
        else if (cardType < 200) {
            return 'discard';
        }
        else if (cardType < 300) {
            return 'costume';
        }
        else if (cardType < 400) {
            return 'transformation';
        }
    };
    Cards.prototype.setDivAsCard = function (cardDiv, cardType, side) {
        if (side === void 0) { side = null; }
        cardDiv.classList.add('kot-card');
        cardDiv.dataset.design = cardType < 200 && this.game.isDarkEdition() ? 'dark-edition' : 'standard';
        var type = this.getCardTypeName(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType, side));
        var position = this.getCardNamePosition(cardType, side);
        cardDiv.innerHTML = "<div class=\"bottom\"></div>\n        <div class=\"name-wrapper\" " + (position ? "style=\"left: " + position[0] + "px; top: " + position[1] + "px;\"" : '') + ">\n            <div class=\"outline\">" + this.getCardName(cardType, 'span', side) + "</div>\n            <div class=\"text\">" + this.getCardName(cardType, 'text-only', side) + "</div>\n        </div>\n        <div class=\"type-wrapper " + this.getCardTypeClass(cardType) + "\">\n            <div class=\"outline\">" + type + "</div>\n            <div class=\"text\">" + type + "</div>\n        </div>\n        \n        <div class=\"description-wrapper\">" + description + "</div>";
        if (this.game.isDarkEdition() && DARK_EDITION_CARDS_MAIN_COLOR[cardType]) {
            cardDiv.style.setProperty('--main-color', DARK_EDITION_CARDS_MAIN_COLOR[cardType]);
        }
        var textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        if (textHeight > 80) {
            cardDiv.getElementsByClassName('description-wrapper')[0].style.fontSize = '6pt';
            textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        }
        var height = Math.min(textHeight, 116);
        cardDiv.getElementsByClassName('bottom')[0].style.top = 166 - height + "px";
        cardDiv.getElementsByClassName('type-wrapper')[0].style.top = 168 - height + "px";
        var nameTopPosition = (position === null || position === void 0 ? void 0 : position[1]) || 14;
        var nameWrapperDiv = cardDiv.getElementsByClassName('name-wrapper')[0];
        var nameDiv = nameWrapperDiv.getElementsByClassName('text')[0];
        var spaceBetweenDescriptionAndName = (155 - height) - (nameTopPosition + nameDiv.clientHeight);
        if (spaceBetweenDescriptionAndName < 0) {
            nameWrapperDiv.style.top = Math.max(5, nameTopPosition + spaceBetweenDescriptionAndName) + "px";
        }
    };
    Cards.prototype.getImageName = function (cardType) {
        if (cardType < 100) {
            return 'keep';
        }
        else if (cardType < 200) {
            return 'discard';
        }
        else if (cardType < 300) {
            return 'costume';
        }
        else if (cardType < 400) {
            return 'transformation';
        }
    };
    Cards.prototype.generateCardDiv = function (card) {
        var tempDiv = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = CARD_WIDTH + "px";
        tempDiv.style.height = CARD_HEIGHT + "px";
        tempDiv.style.position = "relative";
        tempDiv.style.backgroundImage = "url('" + g_gamethemeurl + "img/" + this.getImageName(card.type) + "-cards.jpg')";
        var imagePosition = ((card.type + card.side) % 100) - 1;
        var image_items_per_row = 10;
        var row = Math.floor(imagePosition / image_items_per_row);
        var xBackgroundPercent = (imagePosition - (row * image_items_per_row)) * 100;
        var yBackgroundPercent = row * 100;
        tempDiv.style.backgroundPosition = "-" + xBackgroundPercent + "% -" + yBackgroundPercent + "%";
        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type + (card.side || 0));
        document.body.removeChild(tempDiv);
        return tempDiv;
    };
    Cards.prototype.getMimickedCardText = function (mimickedCard) {
        var mimickedCardText = '-';
        if (mimickedCard) {
            var tempDiv = this.generateCardDiv(mimickedCard);
            mimickedCardText = "<br>" + tempDiv.outerHTML;
        }
        return mimickedCardText;
    };
    Cards.prototype.changeMimicTooltip = function (mimicCardId, mimickedCardText) {
        this.game.addTooltipHtml(mimicCardId, this.getTooltip(27) + ("<br>" + _('Mimicked card:') + " " + mimickedCardText));
    };
    Cards.prototype.placeSuperiorAlienTechnologyTokenOnCard = function (stock, card) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.superiorAlienTechnologyToken = this.getPlaceOnCard(cardPlaced);
        var html = "<div id=\"" + divId + "-superior-alien-technology-token\" style=\"left: " + (cardPlaced.superiorAlienTechnologyToken.x - 16) + "px; top: " + (cardPlaced.superiorAlienTechnologyToken.y - 16) + "px;\" class=\"card-token ufo token\"></div>";
        dojo.place(html, divId);
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    return Cards;
}());
var CurseCards = /** @class */ (function () {
    function CurseCards(game) {
        this.game = game;
    }
    CurseCards.prototype.setupCards = function (stocks) {
        stocks.forEach(function (stock) {
            var anubiscardsurl = g_gamethemeurl + "img/anubis-cards.jpg";
            for (var i = 1; i <= 24; i++) {
                stock.addItemType(i, i, anubiscardsurl, 2);
            }
        });
    };
    CurseCards.prototype.getCardName = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Pharaonic Ego");
            case 2: return _("Isis's Disgrace");
            case 3: return _("Thot's Blindness");
            case 4: return _("Tutankhamun's Curse");
            case 5: return _("Buried in Sand");
            case 6: return _("Raging Flood");
            case 7: return _("Hotep's Peace");
            case 8: return _("Set's Storm");
            case 9: return _("Builders' Uprising");
            case 10: return _("Inadequate offering");
            case 11: return _("Bow Before Ra");
            case 12: return _("Vengeance of Horus");
            case 13: return _("Ordeal of the Mighty");
            case 14: return _("Ordeal of the Wealthy");
            case 15: return _("Ordeal of the Spiritual");
            case 16: return _("Resurrection of Osiris");
            case 17: return _("Forbidden Library");
            case 18: return _("Confused Senses");
            case 19: return _("Pharaonic Skin");
            case 20: return _("Khepri's Rebellion");
            case 21: return _("Body, Spirit and Ka");
            case 22: return _("False Blessing");
            case 23: return _("Gaze of the Sphinx");
            case 24: return _("Scribe's Perserverance");
        }
        return null;
    };
    CurseCards.prototype.getPermanentEffect = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Monsters cannot Yield Tokyo.");
            case 2: return _("Monsters without the Golden Scarab cannot gain [Heart].");
            case 3: return _("Monsters without the Golden Scarab cannot gain [Energy].");
            case 4: return _("Monsters without the Golden Scarab cannot gain [Star].");
            case 5: return _("Monsters have 1 less Roll (minimum 1 Roll).");
            case 6: return _("Monsters roll 1 less die.");
            case 7: return _("Monsters without the Golden Scarab cannot use [diceSmash].");
            case 8: return _("At the start of your turn, lose 1[Heart].");
            case 9: return _("At the start of your turn, lose 2[Star].");
            case 10: return _("Cards cost 2 extra [Energy].");
            case 11: return _("Monsters’ maximum [Heart] is 8[Heart] (Monsters that have more than 8[Heart] go down to 8[Heart]).");
            case 12: return _("Monsters cannot reroll [diceSmash].");
            case 13: return _("At the start of each turn, the Monster(s) with the most [Heart] lose 1[Heart].");
            case 14: return _("At the start of each turn, the Monster(s) with the most [Star] lose 1[Star].");
            case 15: return _("At the start of each turn, the Monster(s) with the most [Energy] lose 1[Energy].");
            case 16: return _("Monsters outside of Tokyo cannot use [diceHeart]. Monsters in Tokyo can use their [diceHeart].");
            case 17: return _("Monsters without the Golden Scarab cannot buy Power cards.");
            case 18: return _("After resolving the die of Fate, the Monster with the Golden Scarab can force you to reroll up to 2 dice of his choice.");
            case 19: return _("The Monster with the Golden Scarab cannot lose [Heart].");
            case 20: return _("At the start of each turn, the Monster with the Golden Scarab must give 1[Heart]/[Energy]/[Star] to the Monster whose turn it is.");
            case 21: return _("Only [diceSmash], [diceHeart] and [diceEnergy] faces can be used.");
            case 22: return _("Monsters roll 2 extra dice and have 1 extra die Roll. After resolving their dice, they lose 1[Heart] for each different face they rolled.");
            case 23: return this.game.isPowerUpExpansion() ? _("[Keep] cards and Permanent Evolution cards have no effect.") : _("[Keep] cards have no effect.");
            case 24: return _("You cannot reroll your [dice1].");
        }
        return null;
    };
    CurseCards.prototype.getAnkhEffect = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Yield Tokyo. You can’t enter Tokyo this turn.");
            case 2:
            case 3:
            case 4:
            case 7:
            case 17:
            case 18:
            case 19: return _("Take the Golden Scarab.");
            case 5: return _("You have 1 extra die Roll.");
            case 6: return _("Take an extra die and put it on the face of your choice.");
            case 8:
            case 11:
            case 13: return "+2[Heart]";
            case 9: return _("If you are not in Tokyo, play an extra turn after this one without the die of Fate.");
            case 10: return _("Draw a Power card.");
            case 12: return _("Gain 1[Star] for each [diceSmash] you rolled.");
            case 14: return "+2[Star]";
            case 15: return "+2[Energy]";
            case 16: return _("Take control of Tokyo.");
            case 20: return _("Take the Golden Scarab and give it to the Monster of your choice.");
            case 21: return _("Cancel the Curse effect.");
            case 22: return _("Choose up to 2 dice, you can reroll or discard each of these dice.");
            case 23: return this.game.isPowerUpExpansion() ? _("Draw an Evolution card or gain 3[Energy].") : "+3[Energy].";
            case 24: return _("Gain 1[Energy] for each [dice1] you rolled.");
        }
        return null;
    };
    CurseCards.prototype.getSnakeEffect = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Take control of Tokyo.");
            case 2:
            case 8: return "-1[Heart]";
            case 3: return "-2[Energy]";
            case 4:
            case 9: return "-2[Star]";
            case 5: return _("You cannot use your [diceSmash].");
            case 6: return _("Discard 1 die.");
            case 7: return _("Lose 1[Energy] for each [diceSmash] you rolled.");
            case 10: return _("Discard a [Keep] card.");
            case 11: return "-2[Heart]";
            case 12: return _("Lose 1[Heart] for each [diceSmash] you rolled.");
            case 13: return _("The Monster(s) with the most [Heart] lose 1[Heart].");
            case 14: return _("The Monster(s) with the most [Star] lose 1[Star].");
            case 15: return _("The Monster(s) with the most [Energy] lose 1[Energy].");
            case 16: return _("Yield Tokyo. You can’t enter Tokyo this turn.");
            case 17: return _("Discard a [Keep] card.");
            case 18: return _("The Monster with the Golden Scarab, instead of you, gains all [Heart] and [Energy] that you should have gained this turn.");
            case 19: return _("Give any combination of 2[Heart]/[Energy]/[Star] to the Monster with the Golden Scarab.");
            case 20: return _("Take the Golden Scarab.");
            case 21: return _("Cancel the Curse effect. [diceSmash], [diceHeart] and [diceEnergy] faces cannot be used.");
            case 22: return _("The player on your left chooses two of your dice. Reroll these dice.");
            case 23: return this.game.isPowerUpExpansion() ? _("Discard an Evolution card from your hand or in play or lose 3[Energy].") : "-3[Energy].";
            case 24: return _("Discard 1[dice1]");
        }
        return null;
    };
    CurseCards.prototype.getTooltip = function (cardTypeId) {
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId) + "</strong></p>\n            <p><strong>" + _("Permanent effect") + " :</strong> " + formatTextIcons(this.getPermanentEffect(cardTypeId)) + "</p>\n            <p><strong>" + _("Ankh effect") + " :</strong> " + formatTextIcons(this.getAnkhEffect(cardTypeId)) + "</p>\n            <p><strong>" + _("Snake effect") + " :</strong> " + formatTextIcons(this.getSnakeEffect(cardTypeId)) + "</p>\n        </div>";
        return tooltip;
    };
    CurseCards.prototype.setupNewCard = function (cardDiv, cardType) {
        this.setDivAsCard(cardDiv, cardType);
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    CurseCards.prototype.setDivAsCard = function (cardDiv, cardType) {
        cardDiv.classList.add('kot-curse-card');
        var permanentEffect = formatTextIcons(this.getPermanentEffect(cardType));
        var ankhEffect = formatTextIcons(this.getAnkhEffect(cardType));
        var snakeEffect = formatTextIcons(this.getSnakeEffect(cardType));
        cardDiv.innerHTML = "\n        <div class=\"name-wrapper\">\n            <div class=\"outline curse\">" + this.getCardName(cardType) + "</div>\n            <div class=\"text\">" + this.getCardName(cardType) + "</div>\n        </div>\n        \n        <div class=\"effect-wrapper permanent-effect-wrapper\"><div class=\"effect-text\">" + permanentEffect + "</div></div>\n        <div class=\"effect-wrapper ankh-effect-wrapper\"><div class=\"effect-text\">" + ankhEffect + "</div></div>\n        <div class=\"effect-wrapper snake-effect-wrapper\"><div class=\"effect-text\">" + snakeEffect + "</div></div>";
        Array.from(cardDiv.getElementsByClassName('effect-wrapper')).forEach(function (wrapperDiv) {
            if (wrapperDiv.children[0].clientHeight > wrapperDiv.clientHeight) {
                wrapperDiv.style.fontSize = '6pt';
            }
        });
        ['permanent', 'ankh', 'snake'].forEach(function (effectType) {
            var effectWrapper = cardDiv.getElementsByClassName(effectType + "-effect-wrapper")[0];
            var effectText = effectWrapper.getElementsByClassName('effect-text')[0];
            if (effectText.clientHeight > effectWrapper.clientHeight) {
                effectText.classList.add('overflow', effectType);
            }
        });
    };
    return CurseCards;
}());
var MONSTERS_WITH_POWER_UP_CARDS = [1, 2, 3, 4, 5, 6, 7, 8, 13, 14, 15, 18];
var EvolutionCards = /** @class */ (function () {
    function EvolutionCards(game) {
        this.game = game;
        this.EVOLUTION_CARDS_TYPES = game.gamedatas.EVOLUTION_CARDS_TYPES;
        //this.debugSeeAllCards();
    }
    // gameui.evolutionCards.debugSeeAllCards()
    EvolutionCards.prototype.debugSeeAllCards = function () {
        var _this = this;
        var html = "<div id=\"all-evolution-cards\" class=\"evolution-card-stock player-evolution-cards\">";
        MONSTERS_WITH_POWER_UP_CARDS.forEach(function (monster) {
            return html += "<div id=\"all-evolution-cards-" + monster + "\" style=\"display: flex; flex-wrap: nowrap;\"></div>";
        });
        html += "</div>";
        dojo.place(html, 'kot-table', 'before');
        MONSTERS_WITH_POWER_UP_CARDS.forEach(function (monster) {
            var evolutionRow = document.getElementById("all-evolution-cards-" + monster);
            for (var i = 1; i <= 8; i++) {
                var tempDiv = _this.generateCardDiv({
                    type: monster * 10 + i
                });
                tempDiv.id = "all-evolution-cards-" + monster + "-" + i;
                evolutionRow.appendChild(tempDiv);
                _this.game.addTooltipHtml(tempDiv.id, _this.getTooltip(monster * 10 + i));
            }
        });
    };
    EvolutionCards.prototype.setupCards = function (stocks) {
        stocks.forEach(function (stock) {
            var evolutioncardsurl = g_gamethemeurl + "img/evolution-cards.jpg";
            stock.addItemType(0, 0, evolutioncardsurl, 0);
            MONSTERS_WITH_POWER_UP_CARDS.forEach(function (monster, index) {
                for (var i = 1; i <= 8; i++) {
                    var uniqueId = monster * 10 + i;
                    stock.addItemType(uniqueId, uniqueId, evolutioncardsurl, index + 1);
                }
            });
        });
    };
    EvolutionCards.prototype.getColoredCardName = function (cardTypeId) {
        switch (cardTypeId) {
            // Space Penguin : blue 2384c6 grey 4c7c96
            case 11: return _("[2384c6]Freeze [4c7c96]Ray");
            case 12: return _("[2384c6]Miraculous [4c7c96]Catch");
            case 13: return _("[2384c6]Deep [4c7c96]Dive");
            case 14: return _("[2384c6]Cold [4c7c96]Wave");
            case 15: return _("[2384c6]Encased [4c7c96]in Ice");
            case 16: return _("[2384c6]Blizzard");
            case 17: return _("[2384c6]Black [4c7c96]Diamond");
            case 18: return _("[2384c6]Icy [4c7c96]Reflection");
            // Alienoid : orange e39717 brown aa673d
            case 21: return _("[e39717]Alien [aa673d]Scourge");
            case 22: return _("[e39717]Precision [aa673d]Field Support");
            case 23: return _("[e39717]Anger [aa673d]Batteries");
            case 24: return _("[e39717]Adapting [aa673d]Technology");
            case 25: return _("[e39717]Funny Looking [aa673d]But Dangerous");
            case 26: return _("[e39717]Exotic [aa673d]Arms");
            case 27: return _("[e39717]Mothership [aa673d]Support");
            case 28: return _("[e39717]Superior Alien [aa673d]Technology");
            // Cyber Kitty : soft b67392 strong ec008c
            case 31: return _("[b67392]Nine [ec008c]Lives");
            case 32: return _("[b67392]Mega [ec008c]Purr");
            case 33: return _("[b67392]Electro- [ec008c]Scratch");
            case 34: return _("[b67392]Cat [ec008c]Nip");
            case 35: return _("[b67392]Play with your [ec008c]Food");
            case 36: return _("[b67392]Feline [ec008c]Motor");
            case 37: return _("[b67392]Mouse [ec008c]Hunter");
            case 38: return _("[b67392]Meow [ec008c]Missle");
            // The King : dark a2550b light ca6c39
            case 41: return _("[a2550b]Monkey [ca6c39]Rush");
            case 42: return _("[a2550b]Simian [ca6c39]Scamper");
            case 43: return _("[a2550b]Jungle [ca6c39]Frenzy");
            case 44: return _("[a2550b]Giant [ca6c39]Banana");
            case 45: return _("[a2550b]Chest [ca6c39]Thumping");
            case 46: return _("[a2550b]Alpha [ca6c39]Male");
            case 47: return _("[a2550b]I Am [ca6c39]the King!");
            case 48: return _("[a2550b]Twas Beauty [ca6c39]Killed the Beast");
            // Gigazaur : dark 00a651 light bed62f
            case 51: return _("[00a651]Detachable [bed62f]Tail");
            case 52: return _("[00a651]Radioactive [bed62f]Waste");
            case 53: return _("[00a651]Primal [bed62f]Bellow");
            case 54: return _("[00a651]Saurian [bed62f]Adaptability");
            case 55: return _("[00a651]Defender [bed62f]Of Tokyo");
            case 56: return _("[00a651]Heat [bed62f]Vision");
            case 57: return _("[00a651]Gamma [bed62f]Breath");
            case 58: return _("[00a651]Tail [bed62f]Sweep");
            // Meka Dragon : gray a68d83 brown aa673d
            case 61: return _("[a68d83]Mecha [aa673d]Blast");
            case 62: return _("[a68d83]Destructive [aa673d]Analysis");
            case 63: return _("[a68d83]Programmed [aa673d]To Destroy");
            case 64: return _("[a68d83]Tune [aa673d]-Up");
            case 65: return _("[a68d83]Breath [aa673d]of Doom");
            case 66: return _("[a68d83]Lightning [aa673d]Armor");
            case 67: return _("[a68d83]Claws [aa673d]of Steel");
            case 68: return _("[a68d83]Target [aa673d]Acquired");
            // Boogie Woogie : dark 6c5b55 light a68d83
            case 71: return /*_TODOPUHA*/ ("[6c5b55]Boo!");
            case 72: return /*_TODOPUHA*/ ("[6c5b55]Worst [a68d83]Nightmare");
            case 73: return /*_TODOPUHA*/ ("[6c5b55]I Live [a68d83]Under Your Bed");
            case 74: return /*_TODOPUHA*/ ("[6c5b55]Boogie [a68d83]Dance");
            case 75: return /*_TODOPUHA*/ ("[6c5b55]Well of [a68d83]Shadow");
            case 76: return /*_TODOPUHA*/ ("[6c5b55]Woem [a68d83]Invaders");
            case 77: return /*_TODOPUHA*/ ("[6c5b55]Nighlife!");
            case 78: return /*_TODOPUHA*/ ("[6c5b55]Dusk [a68d83]Ritual");
            // Pumpkin Jack : dark de6428 light f7941d
            case 81: return /*_TODOPUHA*/ ("[de6428]Detachable [f7941d]Head");
            case 82: return /*_TODOPUHA*/ ("[de6428]Ignis [f7941d]Fatus");
            case 83: return /*_TODOPUHA*/ ("[de6428]Smashing [f7941d]Pumpkin");
            case 84: return /*_TODOPUHA*/ ("[de6428]Trick [f7941d]or Threat");
            case 85: return /*_TODOPUHA*/ ("[de6428]Bobbing [f7941d]for Apples");
            case 86: return /*_TODOPUHA*/ ("[de6428]Feast [f7941d]of Crows");
            case 87: return /*_TODOPUHA*/ ("[de6428]Scythe");
            case 88: return /*_TODOPUHA*/ ("[de6428]Candy!");
            // Cthulhu
            // Anubis
            // King Kong
            // Cybertooth
            // Pandakaï : light 6d6e71 dark 231f20
            case 131: return _("[6d6e71]Panda[231f20]Monium");
            case 132: return _("[6d6e71]Eats, Shoots [231f20]and Leaves");
            case 133: return _("[6d6e71]Bam[231f20]Boozle");
            case 134: return _("[6d6e71]Bear [231f20]Necessities");
            case 135: return _("[6d6e71]Panda [231f20]Express");
            case 136: return _("[6d6e71]Bamboo [231f20]Supply");
            case 137: return _("[6d6e71]Pandarwinism [231f20]Survival of the Cutest");
            case 138: return _("[6d6e71]Yin [231f20]& Yang");
            // cyberbunny : soft b67392 strong ec008c
            case 141: return /*_TODODE*/ ("[b67392]Stroke [ec008c]Of Genius");
            case 142: return /*_TODODE*/ ("[b67392]Emergency [ec008c]Battery");
            case 143: return /*_TODODE*/ ("[b67392]Rabbit's [ec008c]Foot");
            case 144: return /*_TODODE*/ ("[b67392]Heart [ec008c]of the Rabbit");
            case 145: return /*_TODODE*/ ("[b67392]Secret [ec008c]Laboratory");
            case 146: return /*_TODODE*/ ("[b67392]King [ec008c]of the Gizmo");
            case 147: return /*_TODODE*/ ("[b67392]Energy [ec008c]Sword");
            case 148: return /*_TODODE*/ ("[b67392]Electric [ec008c]Carrot");
            // kraken : blue 2384c6 gray 4c7c96
            case 151: return /*_TODODE*/ ("[2384c6]Healing [4c7c96]Rain");
            case 152: return /*_TODODE*/ ("[2384c6]Destructive [4c7c96]Wave");
            case 153: return /*_TODODE*/ ("[2384c6]Cult [4c7c96]Worshippers");
            case 154: return /*_TODODE*/ ("[2384c6]High [4c7c96]Tide");
            case 155: return /*_TODODE*/ ("[2384c6]Terror [4c7c96]of the Deep");
            case 156: return /*_TODODE*/ ("[2384c6]Eater [4c7c96]of Souls");
            case 157: return /*_TODODE*/ ("[2384c6]Sunken [4c7c96]Temple");
            case 158: return /*_TODODE*/ ("[2384c6]Mandibles [4c7c96]of Dread");
            // Baby Gigazaur : dark a5416f light f05a7d
            case 181: return /*_TODOPUBG*/ ("[a5416f]My [f05a7d]Toy");
            case 182: return /*_TODOPUBG*/ ("[a5416f]Growing [f05a7d]Fast");
            case 183: return /*_TODOPUBG*/ ("[a5416f]Nurture [f05a7d]the Young");
            case 184: return /*_TODOPUBG*/ ("[a5416f]Tiny [f05a7d]Tail");
            case 185: return /*_TODOPUBG*/ ("[a5416f]Too Cute [f05a7d]to Smash");
            case 186: return /*_TODOPUBG*/ ("[a5416f]So [f05a7d]Small!");
            case 187: return /*_TODOPUBG*/ ("[a5416f]Underrated");
            case 188: return /*_TODOPUBG*/ ("[a5416f]Yummy [f05a7d]Yummy");
        }
        return null;
    };
    EvolutionCards.prototype.getCardName = function (cardTypeId, state) {
        var coloredCardName = this.getColoredCardName(cardTypeId);
        if (state == 'text-only') {
            return coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, '');
        }
        else if (state == 'span') {
            var first_2 = true;
            return (coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, function (index, color) {
                var span = "<span style=\"-webkit-text-stroke-color: #" + color + ";\">";
                if (first_2) {
                    first_2 = false;
                }
                else {
                    span = "</span>" + span;
                }
                return span;
            })) + ("" + (first_2 ? '' : '</span>'));
        }
        return null;
    };
    EvolutionCards.prototype.getCardDescription = function (cardTypeId) {
        switch (cardTypeId) {
            // Space Penguin
            case 11: return _("When you wound a Monster in <i>Tokyo</i>, give them this card. At the start of their turn, choose a die face. This face has no effect this turn. Take back this card at the end of their turn.");
            case 12: return _("Once per turn, during the Buy Power Cards phase, you can shuffle the discard pile and reveal one card randomly. You can buy this card for 1[Energy] less than the normal price or discard it. Put back the rest of the discard pile.");
            case 13: return _("Look at the top 3 Power cards of the deck. Choose one and play it in front of you for free. Put the other Power cards on the bottom of the deck.");
            case 14: return _("Until your next turn, other Monsters roll with 1 less die.");
            case 15: return _("Spend 1[Energy] to choose one of the dice you rolled. This die is frozen until the beginning of your next turn: it cannot be changed and is used normally by Monsters during the Resolve Dice phase.");
            case 16: return _("Play during your turn. Until the start of your next turn, Monsters only have a single Roll and cannot Yield <i>Tokyo</i>.");
            case 17: return _("Gain 1 extra [Star] each time you take control of <i>Tokyo</i> or choose to stay in <i>Tokyo</i> when you could have Yielded.");
            case 18: return _("Choose an Evolution Card in front of a Monster and put a [snowflakeToken] on it. Icy Reflection becomes a copy of that card as if you had played it. If the copied card is removed from play, discard <i>Icy Reflection</i>.");
            // Alienoid
            case 21: return "+2[Star]";
            case 22: return _("Draw Power cards from the top of the deck until you reveal a [keep] card that costs 4[Energy] or less. Play this card in front of you and discard the other cards you drew.");
            case 23: return _("Gain 1[Energy] for each [Heart] you lost this turn.");
            case 24: return _("Put 3 [alienoidToken] tokens on this card. On your turn, you can remove a [alienoidToken] token to discard the 3 face-up Power cards and reveal 3 new ones. Discard this card when there are no more tokens on it.");
            case 25: return _("If you roll at least [dice2][dice2][dice2] each of the other Monster loses 1[Heart].");
            case 26: return _("Before you roll, you can put 2[Energy] on this card. If you do, and roll at least [diceSmash][diceSmash][diceSmash], you can take back your two [Energy] and make the Monsters you wound lose 2 extra [Heart]. Otherwise you lose your 2[Energy] and lose 2[Heart].");
            case 27: return _("Once during your turn, you can spend 1[Energy] to gain 1[Heart].");
            case 28: return _("You can buy [keep] cards by paying half of their cost (rounding up). When you do so, place a [UfoToken] on it. At the start of you turn, roll a die for each of your [keep] cards with a [UfoToken]. Discard each [keep] card for which you rolled a [diceSmash]. You cannot have more than 3 [keep] cards with [UfoToken] at a time.");
            // Cyber Kitty
            case 31: return _("If you reach [Skull] discard your cards (including your Evolutions), lose all your [Energy] and [Star], and leave <i>Tokyo</i>. Gain 9[Heart], 9[Star], and continue playing.");
            case 32: return _("Each of the other Monsters give you 1[Energy] or 1[Star] if they have any (they choose which to give you).");
            case 33: return _("Each of the other Monsters lose 1[Heart].");
            case 34: return _("Play at the start of your turn. You only have one roll this turn. Double the result.");
            case 35: return _("When you wound a Monster in <i>Tokyo</i>, if they must lose at least 2[Heart], you may make them lose 2[Heart] less and steal 1[Star] and 1[Energy] from them instead.");
            case 36: return _("During other Monsters' Enter Tokyo phases, if <i>Tokyo</i> is empty and you were not inside at the start of the turn, you can enter <i>Tokyo</i> instead of the Monster whose turn it is.");
            case 37: return _("If you roll at least one [dice1], gain 1[Star].");
            case 38: return _("If you roll at least one [dice1], add [diceSmash] to your roll.");
            // The King
            case 41: return _("Play when a Monster who controls <i>Tokyo</i> leaves or is eliminated. Take control of <i>Tokyo</i>.");
            case 42: return _("If you Yield <i>Tokyo</i>, do not lose [Heart]. You can’t lose [Heart] this turn.");
            case 43: return _("Play at the end of your Enter Tokyo phase. If you wounded a Monster who controls <i>Tokyo</i> and you didn't take control of <i>Tokyo</i>, take an extra turn after this one.");
            case 44: return "+2[Heart]";
            case 45: return _("You can force Monsters you wound to Yield <i>Tokyo</i>.");
            case 46: return _("Each turn you wound at least one Monster, gain 1[Star].");
            case 47: return _("Gain 1 extra [Star] if you take control of <i>Tokyo</i> or if you start your turn in <i>Tokyo</i>.");
            case 48: return _("Play when you are in <i>Tokyo</i>. Gain 1[Star] at the end of each Monster’s turn (including yours). Discard this card and lose all your [Star] if you leave <i>Tokyo</i>.");
            // Gigazaur 
            case 51:
            case 143: return _("You can’t lose [Heart] this turn.");
            case 52: return "+2[Energy] +1[Heart].";
            case 53: return _("Each of the other Monsters lose 2[Star].");
            case 54: return _("Choose a die face. Take all dice with this face and flip them to a (single) face of your choice.");
            case 55: return _("If you start your turn in <i>Tokyo</i>, each of the other Monsters lose 1[Star].");
            case 56:
            case 185: return _("Monsters that wound you lose 1[Star].");
            case 57: return _("Once per turn, you can change one of the dice you rolled to [diceSmash].");
            case 58: return _("Once per turn, you can change one of the dice you rolled to [dice1] or [dice2].");
            // Meka Dragon
            case 61: return _("Each Monster you wound this turn loses 2 extra [Heart].");
            case 62: return _("Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 63: return _("Gain 3[Star] and 2[Energy] each time another Monster reaches [Skull].");
            case 64: return _("Play before rolling dice. If you are not in <i>Tokyo</i>, skip your turn, gain 4[Heart] and 2[Energy].");
            case 65: return _("When you make Monsters in <i>Tokyo</i> lose at least 1[Heart], Monsters who aren't in <i>Tokyo</i> also lose 1[Heart] each (except you).");
            case 66: return _("When you lose [Heart], you can roll a die for each [Heart] lost. For each [diceSmash] rolled this way, the Monster whose turn it is also loses 1[Heart].");
            case 67: return _("On your turn, if you make another Monster lose at least 3[Heart], they lose 1 extra [Heart].");
            case 68: return _("When a Monster wounds you, you can give them the [targetToken] token. The Monster who has the [targetToken] token loses 1 extra [Heart] each time you make them lose [Heart].");
            // Boogie Woogie
            // TODOPUHA 71 72
            case 73: return /*_TODOPUHA*/ ("You play with one less die.");
            // TODOPUHA 74
            case 75: return "+2[Heart]";
            case 76: return /*_TODOPUHA*/ ("Each of the other Monsters loses 2[Heart].");
            case 77: return /*_TODOPUHA*/ ("When you enter <i>Tokyo</i>, gain 1[Heart].");
            // TODOPUHA 78
            // Pumpkin Jack 
            case 81: return /*_TODOPUHA*/ ("Every time the <i>Owner</i> of this card wounds you, lose an extra [Heart].");
            case 82: return /*_TODOPUHA*/ ("You have one less Roll each turn.");
            case 83: return /*_TODOPUHA*/ ("All Monsters with 12 or more [Star] lose 2[Heart].");
            case 84: return /*_TODOPUHA*/ ("If you roll [dice1][dice1][dice1], each of the other Monsters must give you 1[Energy] or lose 2[Heart].");
            case 85: return /*_TODOPUHA*/ ("Once per turn, you can buy a Power card for 2[Energy] less. If the Power card that replaces it has an odd cost, discard the one you just bought and regain the [Energy] you spent.");
            case 86: return /*_TODOPUHA*/ ("Each Monster must give you 1[Heart], 1[Star], or 1[Energy].");
            case 87: return /*_TODOPUHA*/ ("When you play this card and each time you eliminate a Monster, put 1[Energy] from the pool on this card. For each [Energy] on this card, add [diceSmash] to your Roll.");
            case 88: return "+1[Heart]<br>" + /*_TODOPUHA*/ ("<strong>Or</strong><br>Play this card when a Monster wounds you. Do not lose [Heart] and give this card to that Monster.");
            // Pandakaï
            case 131: return _("Gain 6[Energy]. All other Monsters gain 3[Energy].");
            case 132: return _("Play when you enter <i>Tokyo</i>. All Monsters outside of <i>Tokyo</i> lose 2[Heart] each. Gain 1[Energy], then leave <i>Tokyo</i>. No Monster takes your place.");
            case 133: return _("Play when a player buys a Power card. They do not spend [Energy] and cannot buy that card this turn. Choose a different Power card they can afford to buy. They must purchase that card.");
            case 134: return "-1[Star] +2[Energy] +2[Heart].";
            case 135: return _("If you rolled at least [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy], gain 2[Star] and take another turn.");
            case 136: return _("At the start of your turn, you can put 1[Energy] from the bank on this card OR take all of the [Energy] off this card.");
            case 137: return _("If you roll at least [diceHeart][diceHeart][diceHeart], gain 1[Star]. Also gain 1[Star] for each extra [diceHeart] you roll.");
            case 138: return _("Before resolving your dice, you can choose to flip all your dice to the opposite side.") + "<div>[dice1]\u2194[dice3] &nbsp; [dice2]\u2194[diceHeart] &nbsp; [diceSmash]\u2194[diceEnergy]</div>";
            // Cyber Bunny
            case 141: return /*_TODODE*/ ("Gain 1[Energy] for each [Energy] you already gained this turn.");
            case 142: return "+3[Energy]";
            // 143 same as 51
            case 144: return /*_TODODE*/ ("Play when another Monster finishes Rolling. Reroll one of this Monster’s dice. Take back <i>Heart of the Rabbit</i> from your discard when you take control of <i>Tokyo</i>.");
            case 145: return /*_TODODE*/ ("The price of Power cards you buy is reduced by 1[Energy].");
            case 146: return /*_TODODE*/ ("Gain 1[Star] each time you buy a Power card.");
            case 147: return /*_TODODE*/ ("Before rolling dice, you can pay 2[Energy]. If you do so and you roll at least 1 [diceSmash], add [diceSmash] to your Roll. Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 148: return /*_TODODE*/ ("If you are in <i>Tokyo</i>, Monsters you wound lose one extra [Heart] unless they give you 1[Energy].");
            // Kraken
            case 151: return "+2[Heart]";
            case 152: return /*_TODODE*/ ("Play when you enter <i>Tokyo</i>. All other Monsters lose 2[Heart].");
            case 153: return /*_TODODE*/ ("Gain 1[Star] for each [Heart] gained this turn.");
            case 154: return /*_TODODE*/ ("For each [diceHeart] you rolled, add [diceHeart] to your Roll");
            case 155: return /*_TODODE*/ ("Roll one die for each [Heart] you lost this turn. Don’t lose [Heart] for each [diceHeart] you roll.");
            case 156: return /*_TODODE*/ ("Gain 1[Heart] each time you enter <i>Tokyo</i>. You can have up to 12[Heart] as long as you own this card.");
            case 157: return /*_TODODE*/ ("Before rolling dice, if you are not in <i>Tokyo</i>, you can pass your turn to gain 3[Heart] and 3[Energy].");
            case 158: return /*_TODODE*/ ("Monsters you wound lose 1[Star].");
            // Baby Gigazaur
            case 181: return /*_TODOPUBG*/ ("Take one of the three face-up Power cards and put it under this card. It is reserved for your purchase. Once purchased, choose another card to reserve."); // TODOPUBG
            case 182: return /*_TODOPUBG*/ ("If you roll no [diceHeart], gain 1[Heart].");
            case 183: return /*_TODOPUBG*/ ("Each Monster who has more [Star] than you has to give you 1[Star].");
            case 184: return /*_TODOPUBG*/ ("Once per turn, you may change two dice you rolled to [dice1].");
            // 185 same as 56
            case 186: return /*_TODOPUBG*/ ("When a Monster wounds you, roll a die for each [diceSmash]. If any of the results is [diceHeart], you lose no [Heart].");
            case 187: return /*_TODOPUBG*/ ("Add 2 [diceSmash] to your Roll.");
            case 188: return "+2[Heart] +1[Energy].";
        }
        return null;
    };
    EvolutionCards.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    EvolutionCards.prototype.placeMimicOnCard = function (stock, card) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);
        var html = "<div id=\"" + divId + "-mimic-token\" style=\"left: " + (cardPlaced.mimicToken.x - 16) + "px; top: " + (cardPlaced.mimicToken.y - 16) + "px;\" class=\"card-token icy-reflection token\"></div>";
        dojo.place(html, divId);
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    EvolutionCards.prototype.removeMimicOnCard = function (stock, card) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.mimicToken = null;
        if (document.getElementById(divId + "-mimic-token")) {
            this.game.fadeOutAndDestroy(divId + "-mimic-token");
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    EvolutionCards.prototype.getPlaceOnCard = function (cardPlaced) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 100 + 16,
        };
        var protection = 0;
        var otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }
        return newPlace;
    };
    EvolutionCards.prototype.placeTokensOnCard = function (stock, card, playerId) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        var placed = cardPlaced.tokens;
        var cardType = /* TODOPU card.mimicType ||*/ card.type;
        // remove tokens
        for (var i = card.tokens; i < placed.length; i++) {
            if ([136, 87].includes(cardType) && playerId) {
                this.game.slideToObjectAndDestroy(divId + "-token" + i, "energy-counter-" + playerId);
            }
            else {
                this.game.fadeOutAndDestroy(divId + "-token" + i);
            }
        }
        placed.splice(card.tokens, placed.length - card.tokens);
        // add tokens
        for (var i = placed.length; i < card.tokens; i++) {
            var newPlace = this.getPlaceOnCard(cardPlaced);
            placed.push(newPlace);
            var html = "<div id=\"" + divId + "-token" + i + "\" style=\"left: " + (newPlace.x - 16) + "px; top: " + (newPlace.y - 16) + "px;\" class=\"card-token ";
            if (cardType === 24) {
                html += "ufo token";
            }
            else if ([26, 136, 87].includes(cardType)) {
                html += "energy-cube cube-shape-" + Math.floor(Math.random() * 5);
            }
            html += "\"></div>";
            dojo.place(html, divId);
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    EvolutionCards.prototype.setDivAsCard = function (cardDiv, cardType) {
        cardDiv.classList.add('kot-evolution');
        var type = this.getCardTypeName(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>'));
        cardDiv.innerHTML = "\n        <div class=\"evolution-type\">" + type + "</div>\n        <div class=\"name-and-description\">\n            <div class=\"name-row\">\n                <div class=\"name-wrapper\">\n                    <div class=\"outline\">" + this.getCardName(cardType, 'span') + "</div>\n                    <div class=\"text\">" + this.getCardName(cardType, 'text-only') + "</div>\n                </div>\n            </div>\n            <div class=\"description-row\">\n                <div class=\"description-wrapper\">" + description + "</div>\n            </div>\n        </div>      \n        ";
        var nameWrapper = cardDiv.getElementsByClassName('name-wrapper')[0];
        var outline = cardDiv.getElementsByClassName('outline')[0];
        var descriptionWrapper = cardDiv.getElementsByClassName('description-wrapper')[0];
        var textHeight = descriptionWrapper.clientHeight;
        var nameHeight = outline.clientHeight;
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '10pt';
            outline.style.webkitTextStroke = '4px #a6c136';
            nameHeight = outline.clientHeight;
        }
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '9pt';
            nameHeight = outline.clientHeight;
        }
        if (textHeight > 80) {
            descriptionWrapper.style.fontSize = '7pt';
            textHeight = descriptionWrapper.clientHeight;
        }
        else {
            return;
        }
        if (textHeight > 80) {
            descriptionWrapper.style.fontSize = '6pt';
            textHeight = descriptionWrapper.clientHeight;
        }
        else {
            return;
        }
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '8pt';
            outline.style.webkitTextStroke = '3px #a6c136';
            nameHeight = outline.clientHeight;
        }
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '7pt';
            outline.style.webkitTextStroke = '3px #a6c136';
            nameHeight = outline.clientHeight;
        }
    };
    EvolutionCards.prototype.getTooltip = function (cardTypeId, ownerId) {
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId, 'text-only') + "</strong></p>\n            <p>" + this.getCardTypeName(cardTypeId) + "</p>";
        if (ownerId) {
            var owner = this.game.getPlayer(ownerId);
            tooltip += "<p>" + _('Owner:') + " <strong style=\"color: #" + owner.color + ";\">" + owner.name + "</strong></p>";
        }
        tooltip += "<p>" + formatTextIcons(this.getCardDescription(cardTypeId).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>')) + "</p>\n        </div>";
        return tooltip;
    };
    EvolutionCards.prototype.setupNewCard = function (cardDiv, cardType) {
        if (cardType == 0) {
            return;
        }
        this.setDivAsCard(cardDiv, cardType);
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    EvolutionCards.prototype.getCardTypeName = function (cardType) {
        var type = this.EVOLUTION_CARDS_TYPES[cardType];
        switch (type) {
            case 1: return _('<strong>Permanent</strong> evolution');
            case 2: return _('<strong>Temporary</strong> evolution');
            case 3: return _('<strong>Gift</strong> evolution');
        }
        return null;
    };
    EvolutionCards.prototype.addCardsToStock = function (stock, cards, from) {
        var _this = this;
        if (!cards.length) {
            return;
        }
        cards.forEach(function (card) {
            stock.addToStockWithId(card.type, "" + card.id, from);
            var cardDiv = document.getElementById(stock.container_div.id + "_item_" + card.id);
            _this.game.addTooltipHtml(cardDiv.id, _this.getTooltip(card.type, card.ownerId));
        });
        cards.filter(function (card) { return card.tokens > 0; }).forEach(function (card) { return _this.placeTokensOnCard(stock, card); });
    };
    EvolutionCards.prototype.moveToAnotherStock = function (sourceStock, destinationStock, card) {
        if (sourceStock === destinationStock) {
            return;
        }
        var sourceStockItemId = sourceStock.container_div.id + "_item_" + card.id;
        if (document.getElementById(sourceStockItemId)) {
            this.addCardsToStock(destinationStock, [card], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById("" + card.id);
        }
        else {
            console.warn(sourceStockItemId + " not found in ", sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [card], sourceStock.container_div.id);
        }
        this.game.tableManager.tableHeightChange();
    };
    EvolutionCards.prototype.generateCardDiv = function (card) {
        var tempDiv = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = EVOLUTION_SIZE + "px";
        tempDiv.style.height = EVOLUTION_SIZE + "px";
        tempDiv.style.position = "relative";
        tempDiv.style.backgroundImage = "url('" + g_gamethemeurl + "img/evolution-cards.jpg')";
        var imagePosition = MONSTERS_WITH_POWER_UP_CARDS.indexOf(Math.floor(card.type / 10)) + 1;
        var xBackgroundPercent = imagePosition * 100;
        tempDiv.style.backgroundPosition = "-" + xBackgroundPercent + "% 0%";
        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type);
        document.body.removeChild(tempDiv);
        return tempDiv;
    };
    EvolutionCards.prototype.getMimickedCardText = function (mimickedCard) {
        var mimickedCardText = '-';
        if (mimickedCard) {
            var tempDiv = this.generateCardDiv(mimickedCard);
            mimickedCardText = "<br><div class=\"player-evolution-cards\">" + tempDiv.outerHTML + "</div>";
        }
        return mimickedCardText;
    };
    EvolutionCards.prototype.changeMimicTooltip = function (mimicCardId, mimickedCardText) {
        this.game.addTooltipHtml(mimicCardId, this.getTooltip(18) + ("<br>" + _('Mimicked card:') + " " + mimickedCardText));
    };
    return EvolutionCards;
}());
var WICKEDNESS_TILES_WIDTH = 132;
var WICKEDNESS_TILES_HEIGHT = 81;
var WICKEDNESS_LEVELS = [3, 6, 10];
var wickenessTilesIndex = [0, 0, 0, 0, 1, 1, 1, 1, 2, 2];
var WickednessTiles = /** @class */ (function () {
    function WickednessTiles(game) {
        this.game = game;
    }
    WickednessTiles.prototype.debugSeeAllCards = function () {
        var _this = this;
        var html = "<div id=\"all-wickedness-tiles\" class=\"wickedness-tile-stock player-wickedness-tiles\">";
        [0, 1].forEach(function (side) {
            return html += "<div id=\"all-wickedness-tiles-" + side + "\" style=\"display: flex; flex-wrap: nowrap;\"></div>";
        });
        html += "</div>";
        dojo.place(html, 'kot-table', 'before');
        [0, 1].forEach(function (side) {
            var evolutionRow = document.getElementById("all-wickedness-tiles-" + side);
            for (var i = 1; i <= 10; i++) {
                var tempDiv = _this.generateCardDiv({
                    type: side * 100 + i,
                    side: side
                });
                tempDiv.id = "all-wickedness-tiles-" + side + "-" + i;
                evolutionRow.appendChild(tempDiv);
                _this.game.addTooltipHtml(tempDiv.id, _this.getTooltip(side * 100 + i));
            }
        });
    };
    WickednessTiles.prototype.setupCards = function (stocks, darkEdition) {
        var _this = this;
        var wickednesstilessurl = g_gamethemeurl + "img/" + (darkEdition ? 'dark/' : '') + "wickedness-tiles.jpg";
        stocks.forEach(function (stock) {
            stock.image_items_per_row = 3;
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10].forEach(function (id, index) {
                stock.addItemType(id, _this.getCardLevel(id) * 100 + index, wickednesstilessurl, wickenessTilesIndex[index]);
            });
            [101, 102, 103, 104, 105, 106, 107, 108, 109, 110].forEach(function (id, index) {
                stock.addItemType(id, _this.getCardLevel(id) * 100 + index, wickednesstilessurl, wickenessTilesIndex[index] + 3);
            });
        });
    };
    WickednessTiles.prototype.addCardsToStock = function (stock, cards, from) {
        var _this = this;
        if (!cards.length) {
            return;
        }
        cards.forEach(function (card) { return stock.addToStockWithId(card.type, "" + card.id, from); });
        cards.filter(function (card) { return card.tokens > 0; }).forEach(function (card) { return _this.placeTokensOnTile(stock, card); });
    };
    WickednessTiles.prototype.generateCardDiv = function (card) {
        var tempDiv = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = WICKEDNESS_TILES_WIDTH + "px";
        tempDiv.style.height = WICKEDNESS_TILES_HEIGHT + "px";
        tempDiv.style.position = "relative";
        tempDiv.style.backgroundImage = "url('" + g_gamethemeurl + "img/wickedness-tiles.jpg')";
        tempDiv.style.backgroundPosition = "-" + wickenessTilesIndex[card.type % 100] * 50 + "% " + (card.side > 0 ? 100 : 0) + "%";
        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type);
        document.body.removeChild(tempDiv);
        return tempDiv;
    };
    WickednessTiles.prototype.getCardLevel = function (cardTypeId) {
        var id = cardTypeId % 100;
        if (id > 8) {
            return 10;
        }
        else if (id > 4) {
            return 6;
        }
        else {
            return 3;
        }
    };
    WickednessTiles.prototype.getCardName = function (cardTypeId) {
        switch (cardTypeId) {
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
    };
    WickednessTiles.prototype.getCardDescription = function (cardTypeId) {
        switch (cardTypeId) {
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
    };
    WickednessTiles.prototype.getTooltip = function (cardType) {
        var level = this.getCardLevel(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>'));
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardType) + "</strong></p>\n            <p class=\"level\">" + dojo.string.substitute(_("Level : ${level}"), { 'level': level }) + "</p>\n            <p>" + description + "</p>\n        </div>";
        return tooltip;
    };
    WickednessTiles.prototype.setupNewCard = function (cardDiv, cardType) {
        this.setDivAsCard(cardDiv, cardType);
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    WickednessTiles.prototype.setDivAsCard = function (cardDiv, cardType) {
        cardDiv.classList.add('kot-tile');
        var name = this.getCardName(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>'));
        cardDiv.innerHTML = "\n        <div class=\"name-and-description\">\n            <div>\n                <div class=\"name-wrapper\">\n                    <div class=\"outline " + (cardType > 100 ? 'wickedness-tile-side1' : 'wickedness-tile-side0') + "\">" + name + "</div>\n                    <div class=\"text\">" + name + "</div>\n                </div>\n            </div>\n            <div>        \n                <div class=\"description-wrapper\">" + description + "</div>\n            </div>\n        ";
        var textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        if (textHeight > 50) {
            cardDiv.getElementsByClassName('description-wrapper')[0].style.width = '100%';
        }
        textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        if (textHeight > 50) {
            cardDiv.getElementsByClassName('description-wrapper')[0].style.fontSize = '6pt';
        }
        textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        var nameHeight = cardDiv.getElementsByClassName('outline')[0].clientHeight;
        if (75 - textHeight < nameHeight) {
            cardDiv.getElementsByClassName('name-wrapper')[0].style.fontSize = '8pt';
        }
        nameHeight = cardDiv.getElementsByClassName('outline')[0].clientHeight;
        if (75 - textHeight < nameHeight) {
            cardDiv.getElementsByClassName('name-wrapper')[0].style.fontSize = '7pt';
        }
    };
    WickednessTiles.prototype.changeMimicTooltip = function (mimicCardId, mimickedCardText) {
        this.game.addTooltipHtml(mimicCardId, this.getTooltip(106) + ("<br>" + _('Mimicked card:') + " " + mimickedCardText));
    };
    WickednessTiles.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    WickednessTiles.prototype.getPlaceOnCard = function (cardPlaced) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 50 + 16,
        };
        var protection = 0;
        var otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 50 + 16;
            protection++;
        }
        return newPlace;
    };
    WickednessTiles.prototype.placeTokensOnTile = function (stock, tile, playerId) {
        var divId = stock.container_div.id + "_item_" + tile.id;
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        var placed = cardPlaced.tokens;
        var cardType = tile.mimicType || tile.type;
        // remove tokens
        for (var i = tile.tokens; i < placed.length; i++) {
            if (cardType === 28 && playerId) {
                this.game.slideToObjectAndDestroy(divId + "-token" + i, "energy-counter-" + playerId);
            }
            else {
                this.game.fadeOutAndDestroy(divId + "-token" + i);
            }
        }
        placed.splice(tile.tokens, placed.length - tile.tokens);
        // add tokens
        for (var i = placed.length; i < tile.tokens; i++) {
            var newPlace = this.getPlaceOnCard(cardPlaced);
            placed.push(newPlace);
            var html = "<div id=\"" + divId + "-token" + i + "\" style=\"left: " + (newPlace.x - 16) + "px; top: " + (newPlace.y - 16) + "px;\" class=\"card-token ";
            if (cardType === 28) {
                html += "energy-cube cube-shape-" + Math.floor(Math.random() * 5);
            }
            else if (cardType === 41) {
                html += "smoke-cloud token";
            }
            html += "\"></div>";
            dojo.place(html, divId);
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    return WickednessTiles;
}());
var TokyoTower = /** @class */ (function () {
    function TokyoTower(divId, levels) {
        this.divId = divId + "-tokyo-tower";
        var html = "\n        <div id=\"" + this.divId + "\" class=\"tokyo-tower tokyo-tower-tooltip\">";
        for (var i = 3; i >= 1; i--) {
            html += "<div id=\"" + this.divId + "-level" + i + "\">";
            if (levels.includes(i)) {
                html += "<div id=\"tokyo-tower-level" + i + "\" class=\"level level" + i + "\">";
                if (i == 1 || i == 2) {
                    html += "<div class=\"icon health\"></div>";
                }
                if (i == 2) {
                    html += "<div class=\"icon energy\"></div>";
                }
                if (i == 3) {
                    html += "<div class=\"icon star\"></div>";
                }
                html += "</div>";
            }
            html += "</div>";
        }
        html += "</div>";
        dojo.place(html, divId);
    }
    return TokyoTower;
}());
var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
;
var log = isDebug ? console.log.bind(window.console) : function () { };
var POINTS_DEG = [25, 40, 56, 73, 89, 105, 122, 138, 154, 170, 187, 204, 221, 237, 254, 271, 288, 305, 322, 339, 359];
var POINTS_DEG_DARK_EDITION = [44, 62, 76, 91, 106, 121, 136, 148, 161, 174, 189, 205, 224, 239, 256, 275, 292, 309, 327, 342, 359];
var HEALTH_DEG = [360, 326, 301, 274, 249, 226, 201, 174, 149, 122, 98, 64, 39];
var HEALTH_DEG_DARK_EDITION = [360, 332, 305, 279, 255, 230, 204, 177, 153, 124, 101, 69, 48];
var SPLIT_ENERGY_CUBES = 6;
var PlayerTable = /** @class */ (function () {
    function PlayerTable(game, player, playerWithGoldenScarab, evolutionCardsWithSingleState) {
        var _this = this;
        var _a, _b, _c, _d, _e;
        this.game = game;
        this.player = player;
        this.showHand = false;
        this.hiddenEvolutionCards = null;
        this.pickEvolutionCards = null;
        this.playerId = Number(player.id);
        this.playerNo = Number(player.player_no);
        this.monster = Number(player.monster);
        var eliminated = Number(player.eliminated) > 0;
        var html = "\n        <div id=\"player-table-" + player.id + "\" class=\"player-table whiteblock " + (eliminated ? 'eliminated' : '') + "\">\n            <div id=\"player-name-" + player.id + "\" class=\"player-name " + (game.isDefaultFont() ? 'standard' : 'goodgirl') + "\" style=\"color: #" + player.color + "\">\n                <div class=\"outline" + (player.color === '000000' ? ' white' : '') + "\">" + player.name + "</div>\n                <div class=\"text\">" + player.name + "</div>\n            </div> \n            <div id=\"monster-board-wrapper-" + player.id + "\" class=\"monster-board-wrapper monster" + this.monster + " " + (player.location > 0 ? 'intokyo' : '') + "\">\n                <div class=\"blue wheel\" id=\"blue-wheel-" + player.id + "\"></div>\n                <div class=\"red wheel\" id=\"red-wheel-" + player.id + "\"></div>\n                <div class=\"kot-token\"></div>\n                <div id=\"monster-board-" + player.id + "\" class=\"monster-board monster" + this.monster + "\">\n                    <div id=\"monster-board-" + player.id + "-figure-wrapper\" class=\"monster-board-figure-wrapper\">\n                        <div id=\"monster-figure-" + player.id + "\" class=\"monster-figure monster" + this.monster + "\"><div class=\"stand\"></div></div>\n                    </div>\n                </div>\n                <div id=\"token-wrapper-" + this.playerId + "-poison\" class=\"token-wrapper poison\"></div>\n                <div id=\"token-wrapper-" + this.playerId + "-shrink-ray\" class=\"token-wrapper shrink-ray\"></div>\n            </div> \n            <div id=\"energy-wrapper-" + player.id + "-left\" class=\"energy-wrapper left\"></div>\n            <div id=\"energy-wrapper-" + player.id + "-right\" class=\"energy-wrapper right\"></div>";
        if (game.isPowerUpExpansion()) {
            html += "\n            <div id=\"visible-evolution-cards-" + player.id + "\" class=\"evolution-card-stock player-evolution-cards " + (((_a = player.visibleEvolutions) === null || _a === void 0 ? void 0 : _a.length) ? '' : 'empty') + "\"></div>\n            ";
            // TODOPUBG
            html += "\n            <div id=\"reserved-cards-" + player.id + "\" class=\"reserved card-stock player-cards " + (player.cards.length ? '' : 'empty') + "\"></div>\n            ";
        }
        if (game.isWickednessExpansion()) {
            html += "<div id=\"wickedness-tiles-" + player.id + "\" class=\"wickedness-tile-stock player-wickedness-tiles " + (((_b = player.wickednessTiles) === null || _b === void 0 ? void 0 : _b.length) ? '' : 'empty') + "\"></div>";
        }
        html += "    <div id=\"cards-" + player.id + "\" class=\"card-stock player-cards " + (player.reservedCards.length ? '' : 'empty') + "\"></div>\n        </div>\n        ";
        dojo.place(html, 'table');
        this.setMonsterFigureBeastMode(((_c = player.cards.find(function (card) { return card.type === 301; })) === null || _c === void 0 ? void 0 : _c.side) === 1);
        this.cards = new ebg.stock();
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $("cards-" + this.player.id), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = function (card_div, card_type_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
        this.cards.image_items_per_row = 10;
        this.cards.centerItems = true;
        dojo.connect(this.cards, 'onChangeSelection', this, function (_, itemId) { return _this.game.onVisibleCardClick(_this.cards, Number(itemId), _this.playerId); });
        this.game.cards.setupCards([this.cards]);
        this.game.cards.addCardsToStock(this.cards, player.cards);
        if (playerWithGoldenScarab) {
            this.cards.addToStockWithId(999, 'goldenscarab');
        }
        if ((_d = player.superiorAlienTechnologyTokens) === null || _d === void 0 ? void 0 : _d.length) {
            player.cards.filter(function (card) { return player.superiorAlienTechnologyTokens.includes(card.id); }).forEach(function (card) { return _this.game.cards.placeSuperiorAlienTechnologyTokenOnCard(_this.cards, card); });
        }
        if (game.isPowerUpExpansion()) {
            // TODOPUBG
            this.reservedCards = new ebg.stock();
            this.reservedCards.setSelectionAppearance('class');
            this.reservedCards.selectionClass = 'no-visible-selection';
            this.reservedCards.create(this.game, $("reserved-cards-" + this.player.id), CARD_WIDTH, CARD_HEIGHT);
            this.reservedCards.setSelectionMode(0);
            this.reservedCards.onItemCreate = function (card_div, card_type_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
            this.reservedCards.image_items_per_row = 10;
            this.reservedCards.centerItems = true;
            dojo.connect(this.reservedCards, 'onChangeSelection', this, function (_, itemId) { return _this.game.onVisibleCardClick(_this.reservedCards, Number(itemId), _this.playerId); });
            this.game.cards.setupCards([this.reservedCards]);
            this.game.cards.addCardsToStock(this.reservedCards, player.reservedCards);
        }
        this.initialLocation = Number(player.location);
        this.setPoints(Number(player.score));
        this.setHealth(Number(player.health));
        if (!eliminated) {
            this.setEnergy(Number(player.energy));
            this.setPoisonTokens(Number(player.poisonTokens));
            this.setShrinkRayTokens(Number(player.shrinkRayTokens));
        }
        if (this.game.isKingkongExpansion()) {
            dojo.place("<div id=\"tokyo-tower-" + player.id + "\" class=\"tokyo-tower-wrapper\"></div>", "player-table-" + player.id);
            this.tokyoTower = new TokyoTower("tokyo-tower-" + player.id, player.tokyoTowerLevels);
        }
        if (this.game.isCybertoothExpansion()) {
            dojo.place("<div id=\"berserk-token-" + player.id + "\" class=\"berserk-token berserk-tooltip\" data-visible=\"" + (player.berserk ? 'true' : 'false') + "\"></div>", "monster-board-" + player.id);
        }
        if (this.game.isCthulhuExpansion()) {
            dojo.place("<div id=\"player-table-cultist-tokens-" + player.id + "\" class=\"cultist-tokens\"></div>", "monster-board-" + player.id);
            if (!eliminated) {
                this.setCultistTokens(player.cultists);
            }
        }
        if (this.game.isWickednessExpansion()) {
            this.wickednessTiles = new ebg.stock();
            this.wickednessTiles.setSelectionAppearance('class');
            this.wickednessTiles.selectionClass = 'no-visible-selection';
            this.wickednessTiles.create(this.game, $("wickedness-tiles-" + player.id), WICKEDNESS_TILES_WIDTH, WICKEDNESS_TILES_HEIGHT);
            this.wickednessTiles.setSelectionMode(0);
            this.wickednessTiles.centerItems = true;
            this.wickednessTiles.onItemCreate = function (card_div, card_type_id) { return _this.game.wickednessTiles.setupNewCard(card_div, card_type_id); };
            this.game.wickednessTiles.setupCards([this.wickednessTiles], this.game.isDarkEdition());
            this.game.wickednessTiles.addCardsToStock(this.wickednessTiles, player.wickednessTiles);
        }
        if (game.isPowerUpExpansion()) {
            this.showHand = this.playerId == this.game.getPlayerId();
            if (this.showHand) {
                document.getElementById("hand-wrapper").classList.add('whiteblock');
                dojo.place("\n                <div id=\"pick-evolution\" class=\"evolution-card-stock player-evolution-cards pick-evolution-cards\"></div>\n                <div id=\"hand-evolution-cards-wrapper\">\n                    <div class=\"hand-title\">\n                        <div>\n                            <div id=\"myhand\">" + _('My hand') + "</div>\n                        </div>\n                        <div id=\"autoSkipPlayEvolution-wrapper\"></div>\n                    </div>\n                    <div id=\"hand-evolution-cards\" class=\"evolution-card-stock player-evolution-cards\">\n                        <div id=\"empty-message\">" + _('Your hand is empty') + "</div>\n                    </div>\n                </div>\n                ", "hand-wrapper");
                this.game.addAutoSkipPlayEvolutionButton();
                this.hiddenEvolutionCards = new ebg.stock();
                this.hiddenEvolutionCards.setSelectionAppearance('class');
                this.hiddenEvolutionCards.selectionClass = 'no-visible-selection';
                this.hiddenEvolutionCards.create(this.game, $("hand-evolution-cards"), EVOLUTION_SIZE, EVOLUTION_SIZE);
                this.hiddenEvolutionCards.setSelectionMode(2);
                this.hiddenEvolutionCards.centerItems = true;
                this.hiddenEvolutionCards.onItemCreate = function (card_div, card_type_id) { return _this.game.evolutionCards.setupNewCard(card_div, card_type_id); };
                dojo.connect(this.hiddenEvolutionCards, 'onChangeSelection', this, function (_, item_id) { return _this.game.onHiddenEvolutionClick(Number(item_id)); });
                this.game.evolutionCards.setupCards([this.hiddenEvolutionCards]);
                if (player.hiddenEvolutions) {
                    this.game.evolutionCards.addCardsToStock(this.hiddenEvolutionCards, player.hiddenEvolutions);
                }
                (_e = player.hiddenEvolutions) === null || _e === void 0 ? void 0 : _e.forEach(function (card) {
                    if (evolutionCardsWithSingleState.includes(card.type)) {
                        document.getElementById(_this.hiddenEvolutionCards.container_div.id + "_item_" + card.id).classList.add('disabled');
                    }
                });
                this.checkHandEmpty();
            }
            this.visibleEvolutionCards = new ebg.stock();
            this.visibleEvolutionCards.setSelectionAppearance('class');
            this.visibleEvolutionCards.selectionClass = 'no-visible-selection';
            this.visibleEvolutionCards.create(this.game, $("visible-evolution-cards-" + player.id), EVOLUTION_SIZE, EVOLUTION_SIZE);
            this.visibleEvolutionCards.setSelectionMode(0);
            this.visibleEvolutionCards.centerItems = true;
            this.visibleEvolutionCards.onItemCreate = function (card_div, card_type_id) { return _this.game.evolutionCards.setupNewCard(card_div, card_type_id); };
            dojo.connect(this.visibleEvolutionCards, 'onChangeSelection', this, function (_, item_id) { return _this.game.onVisibleEvolutionClick(Number(item_id)); });
            this.game.evolutionCards.setupCards([this.visibleEvolutionCards]);
            if (player.visibleEvolutions) {
                this.game.evolutionCards.addCardsToStock(this.visibleEvolutionCards, player.visibleEvolutions);
            }
        }
    }
    ;
    PlayerTable.prototype.initPlacement = function () {
        if (this.initialLocation > 0) {
            this.enterTokyo(this.initialLocation);
        }
    };
    PlayerTable.prototype.enterTokyo = function (location) {
        transitionToObjectAndAttach(this.game, document.getElementById("monster-figure-" + this.playerId), "tokyo-" + (location == 2 ? 'bay' : 'city'), this.game.getZoom());
    };
    PlayerTable.prototype.leaveTokyo = function () {
        transitionToObjectAndAttach(this.game, document.getElementById("monster-figure-" + this.playerId), "monster-board-" + this.playerId + "-figure-wrapper", this.game.getZoom());
    };
    PlayerTable.prototype.setVisibleCardsSelectionClass = function (visible) {
        document.getElementById("hand-wrapper").classList.toggle('double-selection', visible);
        document.getElementById("player-table-" + this.playerId).classList.toggle('double-selection', visible);
    };
    PlayerTable.prototype.removeCards = function (cards) {
        var _this = this;
        var cardsIds = cards.map(function (card) { return card.id; });
        cardsIds.forEach(function (id) { return _this.cards.removeFromStockById('' + id); });
    };
    PlayerTable.prototype.removeWickednessTiles = function (tiles) {
        var _this = this;
        var tilesIds = tiles.map(function (tile) { return tile.id; });
        tilesIds.forEach(function (id) { return _this.wickednessTiles.removeFromStockById('' + id); });
    };
    PlayerTable.prototype.removeEvolutions = function (cards) {
        var _this = this;
        var cardsIds = cards.map(function (card) { return card.id; });
        cardsIds.forEach(function (id) {
            var _a;
            (_a = _this.hiddenEvolutionCards) === null || _a === void 0 ? void 0 : _a.removeFromStockById('' + id);
            _this.visibleEvolutionCards.removeFromStockById('' + id);
        });
        this.checkHandEmpty();
    };
    PlayerTable.prototype.setPoints = function (points, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        var deg = this.monster > 100 ? POINTS_DEG_DARK_EDITION : POINTS_DEG;
        setTimeout(function () { return document.getElementById("blue-wheel-" + _this.playerId).style.transform = "rotate(" + deg[Math.min(20, points)] + "deg)"; }, delay);
    };
    PlayerTable.prototype.setHealth = function (health, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        var deg = this.monster > 100 ? HEALTH_DEG_DARK_EDITION : HEALTH_DEG;
        setTimeout(function () { return document.getElementById("red-wheel-" + _this.playerId).style.transform = "rotate(" + (health > 12 ? 22 : deg[health]) + "deg)"; }, delay);
    };
    PlayerTable.prototype.setEnergy = function (energy, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        setTimeout(function () {
            if (_this.game.isKingkongExpansion()) {
                _this.setEnergyOnSide('left', energy);
            }
            else {
                _this.setEnergyOnSide('left', Math.min(energy, SPLIT_ENERGY_CUBES));
                _this.setEnergyOnSide('right', Math.max(energy - SPLIT_ENERGY_CUBES, 0));
            }
        }, delay);
    };
    PlayerTable.prototype.eliminatePlayer = function () {
        var _this = this;
        var _a;
        this.setEnergy(0);
        this.cards.items.filter(function (item) { return item.id !== 'goldenscarab'; }).forEach(function (item) { return _this.cards.removeFromStockById(item.id); });
        (_a = this.visibleEvolutionCards) === null || _a === void 0 ? void 0 : _a.removeAll();
        if (document.getElementById("monster-figure-" + this.playerId)) {
            this.game.fadeOutAndDestroy("monster-figure-" + this.playerId);
        }
        if (this.game.isCybertoothExpansion()) {
            this.setBerserk(false);
        }
        dojo.addClass("player-table-" + this.playerId, 'eliminated');
    };
    PlayerTable.prototype.setActivePlayer = function (active) {
        dojo.toggleClass("player-table-" + this.playerId, 'active', active);
        dojo.toggleClass("overall_player_board_" + this.playerId, 'active', active);
    };
    PlayerTable.prototype.setFont = function (prefValue) {
        var defaultFont = prefValue === 1;
        dojo.toggleClass("player-name-" + this.playerId, 'standard', defaultFont);
        dojo.toggleClass("player-name-" + this.playerId, 'goodgirl', !defaultFont);
    };
    PlayerTable.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    PlayerTable.prototype.getPlaceEnergySide = function (placed) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 33 + 16,
            y: Math.random() * 188 + 16,
        };
        var protection = 0;
        while (protection < 1000 && placed.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 33 + 16;
            newPlace.y = Math.random() * 188 + 16;
            protection++;
        }
        return newPlace;
    };
    PlayerTable.prototype.setEnergyOnSide = function (side, energy) {
        var divId = "energy-wrapper-" + this.playerId + "-" + side;
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var placed = div.dataset.placed ? JSON.parse(div.dataset.placed) : [];
        // remove tokens
        for (var i = energy; i < placed.length; i++) {
            this.game.fadeOutAndDestroy(divId + "-token" + i);
        }
        placed.splice(energy, placed.length - energy);
        // add tokens
        for (var i = placed.length; i < energy; i++) {
            var newPlace = this.getPlaceEnergySide(placed);
            placed.push(newPlace);
            var html = "<div id=\"" + divId + "-token" + i + "\" style=\"left: " + (newPlace.x - 16) + "px; top: " + (newPlace.y - 16) + "px;\" class=\"energy-cube cube-shape-" + Math.floor(Math.random() * 5) + "\"></div>";
            dojo.place(html, divId);
        }
        div.dataset.placed = JSON.stringify(placed);
    };
    PlayerTable.prototype.setMonster = function (monster) {
        var newMonsterClass = "monster" + monster;
        dojo.removeClass("monster-figure-" + this.playerId, 'monster0');
        dojo.addClass("monster-figure-" + this.playerId, newMonsterClass);
        dojo.removeClass("monster-board-" + this.playerId, 'monster0');
        dojo.addClass("monster-board-" + this.playerId, newMonsterClass);
        dojo.removeClass("monster-board-wrapper-" + this.playerId, 'monster0');
        dojo.addClass("monster-board-wrapper-" + this.playerId, newMonsterClass);
        var wickednessMarkerDiv = document.getElementById("monster-icon-" + this.playerId + "-wickedness");
        wickednessMarkerDiv === null || wickednessMarkerDiv === void 0 ? void 0 : wickednessMarkerDiv.classList.remove('monster0');
        wickednessMarkerDiv === null || wickednessMarkerDiv === void 0 ? void 0 : wickednessMarkerDiv.classList.add(newMonsterClass);
        if (monster > 100) {
            wickednessMarkerDiv.style.backgroundColor = 'unset';
        }
    };
    PlayerTable.prototype.getPlaceToken = function (placed) {
        var _this = this;
        var newPlace = {
            x: 16,
            y: Math.random() * 138 + 16,
        };
        var protection = 0;
        while (protection < 1000 && placed.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.y = Math.random() * 138 + 16;
            protection++;
        }
        return newPlace;
    };
    PlayerTable.prototype.setTokens = function (type, tokens) {
        var divId = "token-wrapper-" + this.playerId + "-" + type;
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var placed = div.dataset.placed ? JSON.parse(div.dataset.placed) : [];
        // remove tokens
        for (var i = tokens; i < placed.length; i++) {
            this.game.fadeOutAndDestroy(divId + "-token" + i);
        }
        placed.splice(tokens, placed.length - tokens);
        // add tokens
        for (var i = placed.length; i < tokens; i++) {
            var newPlace = this.getPlaceToken(placed);
            placed.push(newPlace);
            var html = "<div id=\"" + divId + "-token" + i + "\" style=\"left: " + (newPlace.x - 16) + "px; top: " + (newPlace.y - 16) + "px;\" class=\"" + type + " token\"></div>";
            dojo.place(html, divId);
            this.game.addTooltipHtml(divId + "-token" + i, type === 'poison' ? this.game.POISON_TOKEN_TOOLTIP : this.game.SHINK_RAY_TOKEN_TOOLTIP);
        }
        div.dataset.placed = JSON.stringify(placed);
    };
    PlayerTable.prototype.setPoisonTokens = function (tokens) {
        this.setTokens('poison', tokens);
    };
    PlayerTable.prototype.setShrinkRayTokens = function (tokens) {
        this.setTokens('shrink-ray', tokens);
    };
    PlayerTable.prototype.getTokyoTower = function () {
        return this.tokyoTower;
    };
    PlayerTable.prototype.setBerserk = function (berserk) {
        document.getElementById("berserk-token-" + this.playerId).dataset.visible = berserk ? 'true' : 'false';
    };
    PlayerTable.prototype.changeForm = function (card) {
        var cardDiv = document.getElementById(this.cards.container_div.id + "_item_" + card.id);
        cardDiv.dataset.side = '' + card.side;
        this.game.cards.updateFlippableCardTooltip(cardDiv);
        this.setMonsterFigureBeastMode(card.side === 1);
    };
    PlayerTable.prototype.setMonsterFigureBeastMode = function (beastMode) {
        if (this.monster === 12) {
            document.getElementById("monster-figure-" + this.playerId).classList.toggle('beast-mode', beastMode);
        }
    };
    PlayerTable.prototype.setCultistTokens = function (tokens) {
        var containerId = "player-table-cultist-tokens-" + this.playerId;
        var container = document.getElementById(containerId);
        while (container.childElementCount > tokens) {
            container.removeChild(container.lastChild);
        }
        for (var i = container.childElementCount; i < tokens; i++) {
            dojo.place("<div id=\"" + containerId + "-" + i + "\" class=\"cultist-token cultist-tooltip\"></div>", containerId);
            this.game.addTooltipHtml(containerId + "-" + i, this.game.CULTIST_TOOLTIP);
        }
    };
    PlayerTable.prototype.takeGoldenScarab = function (previousOwnerStock) {
        var sourceStockItemId = previousOwnerStock.container_div.id + "_item_goldenscarab";
        this.cards.addToStockWithId(999, 'goldenscarab', sourceStockItemId);
        previousOwnerStock.removeFromStockById("goldenscarab");
    };
    PlayerTable.prototype.showEvolutionPickStock = function (cards) {
        var _this = this;
        if (!this.pickEvolutionCards) {
            this.pickEvolutionCards = new ebg.stock();
            this.pickEvolutionCards.setSelectionAppearance('class');
            this.pickEvolutionCards.selectionClass = 'no-visible-selection-except-double-selection';
            this.pickEvolutionCards.create(this.game, $("pick-evolution"), EVOLUTION_SIZE, EVOLUTION_SIZE);
            this.pickEvolutionCards.setSelectionMode(1);
            this.pickEvolutionCards.onItemCreate = function (card_div, card_type_id) { return _this.game.evolutionCards.setupNewCard(card_div, card_type_id); };
            this.pickEvolutionCards.centerItems = true;
            dojo.connect(this.pickEvolutionCards, 'onChangeSelection', this, function (_, item_id) { return _this.game.chooseEvolutionCardClick(Number(item_id)); });
        }
        document.getElementById("pick-evolution").style.display = 'block';
        this.game.evolutionCards.setupCards([this.pickEvolutionCards]);
        //this.game.evolutionCards.addCardsToStock(this.pickEvolutionCards, cards);
        cards.forEach(function (card) { return _this.pickEvolutionCards.addToStockWithId(card.type, '' + card.id); });
    };
    PlayerTable.prototype.hideEvolutionPickStock = function () {
        if (this.pickEvolutionCards) {
            document.getElementById("pick-evolution").style.display = 'none';
            this.pickEvolutionCards.removeAll();
        }
    };
    PlayerTable.prototype.playEvolution = function (card, fromStock) {
        if (this.hiddenEvolutionCards) {
            this.game.evolutionCards.moveToAnotherStock(this.hiddenEvolutionCards, this.visibleEvolutionCards, card);
        }
        else {
            if (fromStock) {
                this.game.evolutionCards.moveToAnotherStock(fromStock, this.visibleEvolutionCards, card);
            }
            else {
                this.game.evolutionCards.addCardsToStock(this.visibleEvolutionCards, [card], "playerhand-counter-wrapper-" + this.playerId);
            }
        }
        this.checkHandEmpty();
    };
    PlayerTable.prototype.highlightHiddenEvolutions = function (cards) {
        var _this = this;
        if (!this.hiddenEvolutionCards) {
            return;
        }
        cards.forEach(function (card) {
            var cardDiv = document.getElementById(_this.hiddenEvolutionCards.container_div.id + "_item_" + card.id);
            cardDiv === null || cardDiv === void 0 ? void 0 : cardDiv.classList.add('highlight-evolution');
        });
    };
    PlayerTable.prototype.unhighlightHiddenEvolutions = function () {
        var _this = this;
        var _a;
        if (!this.hiddenEvolutionCards) {
            return;
        }
        (_a = this.hiddenEvolutionCards) === null || _a === void 0 ? void 0 : _a.items.forEach(function (card) {
            var cardDiv = document.getElementById(_this.hiddenEvolutionCards.container_div.id + "_item_" + card.id);
            cardDiv.classList.remove('highlight-evolution');
        });
    };
    PlayerTable.prototype.removeTarget = function () {
        var _a;
        var target = document.getElementById("player-table" + this.playerId + "-target");
        (_a = target === null || target === void 0 ? void 0 : target.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(target);
    };
    PlayerTable.prototype.giveTarget = function () {
        dojo.place("<div id=\"player-table" + this.playerId + "-target\" class=\"target token\"></div>", "monster-board-" + this.playerId);
    };
    PlayerTable.prototype.setEvolutionCardsSingleState = function (evolutionCardsSingleState, enabled) {
        var _this = this;
        this.hiddenEvolutionCards.items.forEach(function (item) {
            if (evolutionCardsSingleState.includes(item.type)) {
                document.getElementById(_this.hiddenEvolutionCards.container_div.id + "_item_" + item.id).classList.toggle('disabled', !enabled);
            }
        });
    };
    PlayerTable.prototype.checkHandEmpty = function () {
        if (this.hiddenEvolutionCards) {
            document.getElementById("hand-evolution-cards-wrapper").classList.toggle('empty', !this.hiddenEvolutionCards.items.length);
        }
    };
    return PlayerTable;
}());
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
var PLAYER_TABLE_WIDTH = 420;
var PLAYER_BOARD_HEIGHT = 247;
var CARDS_PER_ROW = 3;
var TABLE_MARGIN = 20;
var PLAYER_TABLE_WIDTH_MARGINS = PLAYER_TABLE_WIDTH + 2 * TABLE_MARGIN;
var PLAYER_BOARD_HEIGHT_MARGINS = PLAYER_BOARD_HEIGHT + 2 * TABLE_MARGIN;
var DISPOSITION_1_COLUMN = [];
var DISPOSITION_2_COLUMNS = [];
var DISPOSITION_3_COLUMNS = [];
DISPOSITION_1_COLUMN[2] = [[0, 1]];
DISPOSITION_1_COLUMN[3] = [[0, 1, 2]];
DISPOSITION_1_COLUMN[4] = [[0, 1, 2, 3]];
DISPOSITION_1_COLUMN[5] = [[0, 1, 2, 3, 4]];
DISPOSITION_1_COLUMN[6] = [[0, 1, 2, 3, 4, 5]];
DISPOSITION_2_COLUMNS[2] = [[0], [1]];
DISPOSITION_2_COLUMNS[3] = [[0], [1, 2]];
DISPOSITION_2_COLUMNS[4] = [[0], [1, 2, 3]];
DISPOSITION_2_COLUMNS[5] = [[0, 4], [1, 2, 3]];
DISPOSITION_2_COLUMNS[6] = [[0, 5], [1, 2, 3, 4]];
DISPOSITION_3_COLUMNS[2] = [[0], [], [1]];
DISPOSITION_3_COLUMNS[3] = [[0, 2], [], [1]];
DISPOSITION_3_COLUMNS[4] = [[0, 3], [], [1, 2]];
DISPOSITION_3_COLUMNS[5] = [[0, 4, 3], [], [1, 2]];
DISPOSITION_3_COLUMNS[6] = [[0, 5, 4], [], [1, 2, 3]];
var ZOOM_LEVELS = [0.25, 0.375, 0.5, 0.625, 0.75, 0.875, 1];
var ZOOM_LEVELS_MARGIN = [-300, -166, -100, -60, -33, -14, 0];
var LOCAL_STORAGE_ZOOM_KEY = 'KingOfTokyo-zoom';
var TableManager = /** @class */ (function () {
    function TableManager(game, playerTables) {
        var _this = this;
        this.game = game;
        this.zoom = 1;
        var zoomStr = localStorage.getItem(LOCAL_STORAGE_ZOOM_KEY);
        if (zoomStr) {
            this.zoom = Number(zoomStr);
        }
        this.setPlayerTables(playerTables);
        this.game.onScreenWidthChange = function () {
            _this.setAutoZoomAndPlacePlayerTables();
            // shift background for mobile
            var backgroundPositionY = 0;
            if (document.body.classList.contains('mobile_version')) {
                backgroundPositionY = 62 + document.getElementById('right-side').getBoundingClientRect().height;
            }
            document.getElementsByTagName(('html'))[0].style.backgroundPositionY = backgroundPositionY + "px";
        };
    }
    TableManager.prototype.setPlayerTables = function (playerTables) {
        var currentPlayerId = Number(this.game.getPlayerId());
        var playerTablesOrdered = playerTables.sort(function (a, b) { return a.playerNo - b.playerNo; });
        var playerIndex = playerTablesOrdered.findIndex(function (playerTable) { return playerTable.playerId === currentPlayerId; });
        if (playerIndex > 0) { // not spectator (or 0)            
            this.playerTables = __spreadArray(__spreadArray([], playerTablesOrdered.slice(playerIndex), true), playerTablesOrdered.slice(0, playerIndex), true);
        }
        else { // spectator
            this.playerTables = playerTablesOrdered;
        }
    };
    TableManager.prototype.setAutoZoomAndPlacePlayerTables = function () {
        var _this = this;
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            return;
        }
        var zoomWrapperWidth = document.getElementById('zoom-wrapper').clientWidth;
        if (!zoomWrapperWidth) {
            setTimeout(function () { return _this.setAutoZoomAndPlacePlayerTables(); }, 200);
            return;
        }
        var centerTableWidth = document.getElementById('table-center').clientWidth;
        var newZoom = this.zoom;
        while (newZoom > ZOOM_LEVELS[0] && zoomWrapperWidth / newZoom < centerTableWidth) {
            newZoom = ZOOM_LEVELS[ZOOM_LEVELS.indexOf(newZoom) - 1];
        }
        // zoom will also place player tables. we call setZoom even if this method didn't change it because it might have been changed by localStorage zoom
        this.setZoom(newZoom);
    };
    TableManager.prototype.getAvailableColumns = function (tableWidth, tableCenterWidth) {
        if (tableWidth >= tableCenterWidth + 2 * PLAYER_TABLE_WIDTH_MARGINS) {
            return 3;
        }
        else if (tableWidth >= tableCenterWidth + PLAYER_TABLE_WIDTH_MARGINS) {
            return 2;
        }
        else {
            return 1;
        }
    };
    TableManager.prototype.placePlayerTable = function () {
        var _this = this;
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            return;
        }
        var players = this.playerTables.length;
        var tableDiv = document.getElementById('table');
        var tableWidth = tableDiv.clientWidth;
        var tableCenterDiv = document.getElementById('table-center');
        var availableColumns = this.getAvailableColumns(tableWidth, tableCenterDiv.clientWidth);
        var columns = Math.min(availableColumns, 3);
        var dispositionModelColumn;
        if (columns === 1) {
            dispositionModelColumn = DISPOSITION_1_COLUMN;
        }
        else if (columns === 2) {
            dispositionModelColumn = DISPOSITION_2_COLUMNS;
        }
        else {
            dispositionModelColumn = DISPOSITION_3_COLUMNS;
        }
        var dispositionModel = dispositionModelColumn[players];
        var disposition = dispositionModel.map(function (columnIndexes) { return columnIndexes.map(function (columnIndex) { return _this.playerTables[columnIndex].playerId; }); });
        var centerColumnIndex = columns === 3 ? 1 : 0;
        // we always compute "center" column first
        var columnOrder;
        if (columns === 1) {
            columnOrder = [0];
        }
        else if (columns === 2) {
            columnOrder = [0, 1];
        }
        else {
            columnOrder = [1, 0, 2];
        }
        columnOrder.forEach(function (columnIndex) {
            var leftColumn = columnIndex === 0 && columns === 3;
            var centerColumn = centerColumnIndex === columnIndex;
            var rightColumn = columnIndex > centerColumnIndex;
            var playerOverTable = centerColumn && disposition[columnIndex].length;
            var dispositionColumn = disposition[columnIndex];
            dispositionColumn.forEach(function (id, index) {
                var playerTableDiv = document.getElementById("player-table-" + id);
                var columnId = 'center-column';
                if (rightColumn) {
                    columnId = 'right-column';
                }
                else if (leftColumn) {
                    columnId = 'left-column';
                }
                document.getElementById(columnId).appendChild(playerTableDiv);
                if (centerColumn && playerOverTable && index === 0) {
                    playerTableDiv.after(tableCenterDiv);
                }
            });
        });
        this.tableHeightChange();
    };
    TableManager.prototype.tableHeightChange = function () {
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.visibleEvolutionCards) {
                dojo.toggleClass("visible-evolution-cards-" + playerTable.playerId, 'empty', !playerTable.visibleEvolutionCards.items.length);
            }
            if (playerTable.wickednessTiles) {
                dojo.toggleClass("wickedness-tiles-" + playerTable.playerId, 'empty', !playerTable.wickednessTiles.items.length);
            }
            if (playerTable.reservedCards) {
                dojo.toggleClass("reserved-cards-" + playerTable.playerId, 'empty', !playerTable.reservedCards.items.length);
            }
            dojo.toggleClass("cards-" + playerTable.playerId, 'empty', !playerTable.cards.items.length);
        });
        var zoomWrapper = document.getElementById('zoom-wrapper');
        zoomWrapper.style.height = document.getElementById('table').clientHeight * this.zoom + "px";
    };
    TableManager.prototype.setZoom = function (zoom) {
        if (zoom === void 0) { zoom = 1; }
        this.zoom = zoom;
        localStorage.setItem(LOCAL_STORAGE_ZOOM_KEY, '' + this.zoom);
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom);
        dojo.toggleClass('zoom-in', 'disabled', newIndex === ZOOM_LEVELS.length - 1);
        dojo.toggleClass('zoom-out', 'disabled', newIndex === 0);
        var div = document.getElementById('table');
        if (zoom === 1) {
            div.style.transform = '';
            div.style.margin = '';
        }
        else {
            div.style.transform = "scale(" + zoom + ")";
            div.style.margin = "0 " + ZOOM_LEVELS_MARGIN[newIndex] + "% " + (1 - zoom) * -100 + "% 0";
        }
        this.placePlayerTable();
    };
    TableManager.prototype.zoomIn = function () {
        if (this.zoom === ZOOM_LEVELS[ZOOM_LEVELS.length - 1]) {
            return;
        }
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom) + 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    };
    TableManager.prototype.zoomOut = function () {
        if (this.zoom === ZOOM_LEVELS[0]) {
            return;
        }
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom) - 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    };
    return TableManager;
}());
var DieFaceSelector = /** @class */ (function () {
    function DieFaceSelector(nodeId, die, canHealWithDice) {
        var _this = this;
        this.nodeId = nodeId;
        this.dieValue = die.value;
        var colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        var _loop_1 = function (face) {
            var faceId = nodeId + "-face" + face;
            var html = "<div id=\"" + faceId + "\" class=\"die-item dice-icon dice" + face + " " + colorClass + " " + (this_1.dieValue == face ? 'disabled' : '') + "\">";
            if (!die.type && face === 4 && !canHealWithDice) {
                html += "<div class=\"icon forbidden\"></div>";
            }
            html += "</div>";
            dojo.place(html, nodeId);
            document.getElementById(faceId).addEventListener('click', function (event) {
                var _a;
                if (_this.value) {
                    if (_this.value === face) {
                        return;
                    }
                    _this.reset();
                }
                _this.value = face;
                dojo.addClass(nodeId + "-face" + _this.value, 'selected');
                (_a = _this.onChange) === null || _a === void 0 ? void 0 : _a.call(_this, face);
                event.stopImmediatePropagation();
            });
        };
        var this_1 = this;
        for (var face = 1; face <= 6; face++) {
            _loop_1(face);
        }
    }
    DieFaceSelector.prototype.getValue = function () {
        return this.value;
    };
    DieFaceSelector.prototype.reset = function (dieValue) {
        dojo.removeClass(this.nodeId + "-face" + this.value, 'selected');
        if (dieValue && dieValue != this.dieValue) {
            dojo.removeClass(this.nodeId + "-face" + this.dieValue, 'disabled');
            this.dieValue = dieValue;
            dojo.addClass(this.nodeId + "-face" + this.dieValue, 'disabled');
        }
    };
    return DieFaceSelector;
}());
var DIE4_ICONS = [
    null,
    [1, 3, 2],
    [1, 2, 4],
    [1, 4, 3],
    [4, 3, 2],
];
var DICE_STRINGS = [null, '[dice1]', '[dice2]', '[dice3]', '[diceHeart]', '[diceEnergy]', '[diceSmash]'];
var BERSERK_DIE_STRINGS = [null, '[berserkDieEnergy]', '[berserkDieDoubleEnergy]', '[berserkDieSmash]', '[berserkDieSmash]', '[berserkDieDoubleSmash]', '[berserkDieSkull]'];
var DiceManager = /** @class */ (function () {
    function DiceManager(game) {
        this.game = game;
        this.dice = [];
        this.dieFaceSelectors = [];
    }
    DiceManager.prototype.hideLock = function () {
        dojo.addClass('locked-dice', 'hide-lock');
    };
    DiceManager.prototype.showLock = function () {
        dojo.removeClass('locked-dice', 'hide-lock');
    };
    DiceManager.prototype.getDice = function () {
        return this.dice;
    };
    DiceManager.prototype.getBerserkDice = function () {
        return this.dice.filter(function (die) { return die.type === 1; });
    };
    DiceManager.prototype.getLockedDice = function () {
        return this.dice.filter(function (die) { return die.locked; });
    };
    DiceManager.prototype.destroyFreeDice = function () {
        var _this = this;
        var freeDice = this.dice.filter(function (die) { return !die.locked; });
        freeDice.forEach(function (die) { return _this.removeDice(die); });
        return freeDice.map(function (die) { return die.id; });
    };
    DiceManager.prototype.removeAllDice = function () {
        var _this = this;
        this.dice.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = [];
    };
    DiceManager.prototype.setDiceForThrowDice = function (dice, selectableDice, canHealWithDice, frozenFaces) {
        var _this = this;
        var _a;
        this.action = 'move';
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) { return _this.createDice(die, canHealWithDice, frozenFaces); });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.disableDiceAction = function () {
        this.setSelectableDice();
        this.action = undefined;
    };
    DiceManager.prototype.getLockedDiceId = function (die) {
        return "locked-dice" + this.getDieFace(die);
    };
    DiceManager.prototype.discardDie = function (die) {
        this.removeDice(die, ANIMATION_MS);
    };
    DiceManager.prototype.setDiceForChangeDie = function (dice, selectableDice, args, canHealWithDice, frozenFaces) {
        var _this = this;
        var _a;
        this.action = args.hasHerdCuller || args.hasPlotTwist || args.hasStretchy || args.hasClown || args.hasSaurianAdaptability || args.hasGammaBreath || args.hasTailSweep || args.hasTinyTail ? 'change' : null;
        this.changeDieArgs = args;
        if (this.dice.length) {
            this.setSelectableDice(selectableDice);
            return;
        }
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.setDiceForDiscardDie = function (dice, selectableDice, canHealWithDice, frozenFaces, action) {
        var _this = this;
        if (action === void 0) { action = 'discard'; }
        this.action = action;
        this.selectedDice = [];
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.setDiceForSelectHeartAction = function (dice, selectableDice, canHealWithDice, frozenFaces) {
        var _this = this;
        this.action = null;
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.setDiceForPsychicProbe = function (dice, selectableDice, canHealWithDice, frozenFaces) {
        var _this = this;
        this.action = 'psychicProbeRoll';
        /*if (this.dice.length) { if active, event are not reset and roll is not applied
            this.setSelectableDice(selectableDice);
            return;
        }*/
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.changeDie = function (dieId, canHealWithDice, toValue, roll) {
        var die = this.dice.find(function (die) { return die.id == dieId; });
        var divId = "dice" + dieId;
        var div = document.getElementById(divId);
        if (div) {
            dojo.removeClass(div, "dice" + div.dataset.diceValue);
            div.dataset.diceValue = '' + toValue;
            dojo.addClass(div, "dice" + toValue);
            var list = div.getElementsByTagName('ol')[0];
            list.dataset.rollType = roll ? 'odd' : 'change';
            if (roll) {
                this.addDiceRollClass({
                    id: dieId,
                    rolled: roll
                });
            }
            if (!canHealWithDice && !die.type) {
                if (die.value !== 4 && toValue === 4) {
                    dojo.place('<div class="icon forbidden"></div>', divId);
                }
                else if (die.value === 4 && toValue !== 4) {
                    Array.from(div.getElementsByClassName('forbidden')).forEach(function (elem) { return dojo.destroy(elem); });
                }
            }
            list.dataset.roll = '' + toValue;
        }
        if (die) {
            die.value = toValue;
        }
    };
    DiceManager.prototype.showCamouflageRoll = function (dice) {
        var _this = this;
        this.clearDiceHtml();
        dice.forEach(function (dieValue, index) {
            var die = {
                id: index,
                value: dieValue.value,
                extra: false,
                locked: false,
                rolled: dieValue.rolled,
                type: 0,
                canReroll: true,
            };
            _this.createAndPlaceDiceHtml(die, true, [], "dice-selector");
            _this.addDiceRollClass(die);
        });
    };
    DiceManager.prototype.clearDiceHtml = function () {
        var ids = [];
        for (var i = 1; i <= 7; i++) {
            ids.push("locked-dice" + i);
        }
        ids.push("locked-dice10", "dice-selector");
        ids.forEach(function (id) {
            var div = document.getElementById(id);
            if (div) {
                div.innerHTML = '';
            }
        });
    };
    DiceManager.prototype.resolveNumberDice = function (args) {
        var _this = this;
        this.dice.filter(function (die) { return die.value === args.diceValue; }).forEach(function (die) { return _this.removeDice(die, 1000, 1500); });
    };
    DiceManager.prototype.resolveHealthDiceInTokyo = function () {
        var _this = this;
        this.dice.filter(function (die) { return die.value === 4; }).forEach(function (die) { return _this.removeDice(die, 1000); });
    };
    DiceManager.prototype.getDieFace = function (die) {
        if (die.type === 2) {
            return 10;
        }
        else if (die.type === 1) {
            if (die.value <= 2) {
                return 5;
            }
            else if (die.value <= 5) {
                return 6;
            }
            else {
                return 7;
            }
        }
        else {
            return die.value;
        }
    };
    DiceManager.prototype.getDiceShowingFace = function (face) {
        var dice = this.dice.filter(function (die) { var _a; return !die.type && die.value === face && ((_a = document.getElementById("dice" + die.id)) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        }
        else {
            var berserkDice = this.dice.filter(function (die) { return die.type === 1; });
            if (face == 5) { // energy
                return berserkDice.filter(function (die) { var _a; return die.value >= 1 && die.value <= 2 && ((_a = document.getElementById("dice" + die.id)) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else if (face == 6) { // smash
                return berserkDice.filter(function (die) { var _a; return die.value >= 3 && die.value <= 5 && ((_a = document.getElementById("dice" + die.id)) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else {
                return [];
            }
        }
    };
    DiceManager.prototype.addDiceAnimation = function (diceValue, number) {
        var _this = this;
        var dice = this.getDiceShowingFace(diceValue);
        if (number) {
            dice = dice.slice(0, number);
        }
        dice.forEach(function (die) {
            document.getElementById("dice" + die.id).dataset.animated !== 'true';
            _this.removeDice(die, 500, 2500);
        });
    };
    DiceManager.prototype.resolveHealthDice = function (number) {
        this.addDiceAnimation(4, number);
    };
    DiceManager.prototype.resolveEnergyDice = function () {
        this.addDiceAnimation(5);
    };
    DiceManager.prototype.resolveSmashDice = function () {
        this.addDiceAnimation(6);
    };
    DiceManager.prototype.toggleLockDice = function (die, event, forcedLockValue) {
        var _this = this;
        if (forcedLockValue === void 0) { forcedLockValue = null; }
        if ((event === null || event === void 0 ? void 0 : event.altKey) || (event === null || event === void 0 ? void 0 : event.ctrlKey)) {
            var dice = [];
            if (event.ctrlKey && event.altKey) { // move everything but die.value dice
                dice = this.dice.filter(function (idie) { return idie.locked === die.locked && _this.getDieFace(idie) !== _this.getDieFace(die); });
            }
            else if (event.ctrlKey) { // move everything with die.value dice
                dice = this.dice.filter(function (idie) { return idie.locked === die.locked && _this.getDieFace(idie) === _this.getDieFace(die); });
            }
            else { // move everything but die
                dice = this.dice.filter(function (idie) { return idie.locked === die.locked && idie.id !== die.id; });
            }
            dice.forEach(function (idie) { return _this.toggleLockDice(idie, null); });
            return;
        }
        if (!die.canReroll) {
            return;
        }
        die.locked = forcedLockValue === null ? !die.locked : forcedLockValue;
        var dieDivId = "dice" + die.id;
        var dieDiv = document.getElementById(dieDivId);
        dieDiv.dataset.rolled = 'false';
        var destinationId = die.locked ? this.getLockedDiceId(die) : "dice-selector";
        var tempDestinationId = "temp-destination-wrapper-" + destinationId + "-" + die.id;
        var tempOriginId = "temp-origin-wrapper-" + destinationId + "-" + die.id;
        if (document.getElementById(destinationId)) {
            dojo.place("<div id=\"" + tempDestinationId + "\" style=\"width: 0px; height: " + dieDiv.clientHeight + "px; display: inline-block; margin: 0;\"></div>", destinationId);
            dojo.place("<div id=\"" + tempOriginId + "\" style=\"width: " + dieDiv.clientWidth + "px; height: " + dieDiv.clientHeight + "px; display: inline-block; margin: -3px 6px 3px -3px;\"></div>", dieDivId, 'after');
            var destination_1 = document.getElementById(destinationId);
            var tempDestination_1 = document.getElementById(tempDestinationId);
            var tempOrigin_1 = document.getElementById(tempOriginId);
            tempOrigin_1.appendChild(dieDiv);
            dojo.animateProperty({
                node: tempDestinationId,
                properties: {
                    width: dieDiv.clientHeight,
                }
            }).play();
            dojo.animateProperty({
                node: tempOriginId,
                properties: {
                    width: 0,
                }
            }).play();
            dojo.animateProperty({
                node: dieDivId,
                properties: {
                    marginLeft: -13
                }
            }).play();
            slideToObjectAndAttach(this.game, dieDiv, tempDestinationId).then(function () {
                dieDiv.style.marginLeft = '3px';
                if (tempDestination_1.parentElement) { // we only attach if temp div still exists (not deleted)
                    destination_1.append(tempDestination_1.childNodes[0]);
                }
                dojo.destroy(tempDestination_1);
                dojo.destroy(tempOrigin_1);
            });
        }
        this.activateRethrowButton();
        this.game.checkBuyEnergyDrinkState();
        this.game.checkUseSmokeCloudState();
        this.game.checkUseCultistState();
    };
    DiceManager.prototype.lockAll = function () {
        var _this = this;
        var _a;
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.filter(function (die) { return !die.locked; }).forEach(function (die) { return _this.toggleLockDice(die, null, true); });
    };
    DiceManager.prototype.activateRethrowButton = function () {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.canRethrow());
        }
    };
    DiceManager.prototype.canRethrow = function () {
        return this.dice.some(function (die) { return !die.locked; });
    };
    DiceManager.prototype.createAndPlaceDie4Html = function (die, destinationId) {
        var html = "\n        <div id=\"dice" + die.id + "\" class=\"die4\" data-dice-id=\"" + die.id + "\" data-dice-value=\"" + die.value + "\">\n            <ol class=\"die-list\" data-roll=\"" + die.value + "\">";
        for (var dieFace = 1; dieFace <= 4; dieFace++) {
            html += "<li class=\"face\" data-side=\"" + dieFace + "\">";
            DIE4_ICONS[dieFace].forEach(function (icon) { return html += "<span class=\"number face" + icon + "\"><div class=\"anubis-icon anubis-icon" + icon + "\"></div></span>"; });
            html += "</li>";
        }
        html += "    </ol>";
        if (true) {
            html += "<div class=\"dice-icon die-of-fate\"></div>";
        }
        html += "</div>";
        dojo.place(html, destinationId);
        this.game.addTooltipHtml("dice" + die.id, "\n        <strong>" + _("Die of Fate effects") + "</strong>\n        <div><div class=\"anubis-icon anubis-icon1\"></div> " + _("Change Curse: Discard the current Curse and reveal the next one.") + "</div>\n        <div><div class=\"anubis-icon anubis-icon2\"></div> " + _("No effect. The card's permanent effect remains active, however.") + "</div>\n        <div><div class=\"anubis-icon anubis-icon3\"></div> " + _("Suffer the Snake effect.") + "</div>\n        <div><div class=\"anubis-icon anubis-icon4\"></div> " + _("Receive the blessing of the Ankh effect.") + "</div>\n        ");
    };
    DiceManager.prototype.createAndPlaceDie6Html = function (die, canHealWithDice, frozenFaces, destinationId) {
        var html = "<div id=\"dice" + die.id + "\" class=\"dice dice" + die.value + "\" data-dice-id=\"" + die.id + "\" data-dice-value=\"" + die.value + "\">\n        <ol class=\"die-list\" data-roll=\"" + die.value + "\">";
        var colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        for (var dieFace = 1; dieFace <= 6; dieFace++) {
            html += "<li class=\"die-item " + colorClass + " side" + dieFace + "\" data-side=\"" + dieFace + "\"></li>";
        }
        html += "</ol>";
        if (!die.type && (frozenFaces === null || frozenFaces === void 0 ? void 0 : frozenFaces.includes(die.value))) {
            html += "<div class=\"icon frozen\"></div>";
        }
        else if (!die.type && die.value === 4 && !canHealWithDice) {
            html += "<div class=\"icon forbidden\"></div>";
        }
        if (!die.canReroll) {
            html += "<div class=\"icon lock\"></div>";
        }
        html += "</div>";
        // security to destroy pre-existing die with same id
        var dieDiv = document.getElementById("dice" + die.id);
        dieDiv === null || dieDiv === void 0 ? void 0 : dieDiv.parentNode.removeChild(dieDiv);
        dojo.place(html, destinationId);
    };
    DiceManager.prototype.createAndPlaceDiceHtml = function (die, canHealWithDice, frozenFaces, destinationId) {
        var _this = this;
        if (die.type == 2) {
            this.createAndPlaceDie4Html(die, destinationId);
        }
        else {
            this.createAndPlaceDie6Html(die, canHealWithDice, frozenFaces, destinationId);
        }
        this.getDieDiv(die).addEventListener('click', function (event) { return _this.dieClick(die, event); });
    };
    DiceManager.prototype.getDieDiv = function (die) {
        return document.getElementById("dice" + die.id);
    };
    DiceManager.prototype.createDice = function (die, canHealWithDice, frozenFaces) {
        this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, die.locked ? this.getLockedDiceId(die) : "dice-selector");
        var div = this.getDieDiv(die);
        div.addEventListener('animationend', function (e) {
            if (e.animationName == 'rolled-dice') {
                div.dataset.rolled = 'false';
            }
        });
        this.addDiceRollClass(die);
    };
    DiceManager.prototype.dieClick = function (die, event) {
        if (this.action === 'move') {
            this.toggleLockDice(die, event);
        }
        else if (this.action === 'change') {
            this.toggleBubbleChangeDie(die);
        }
        else if (this.action === 'psychicProbeRoll') {
            this.game.psychicProbeRollDie(die.id);
        }
        else if (this.action === 'discard') {
            this.game.discardDie(die.id);
        }
        else if (this.action === 'rerollOrDiscard') {
            this.game.rerollOrDiscardDie(die.id);
        }
        else if (this.action === 'rerollDice') {
            if (die.type < 2) {
                dojo.toggleClass(this.getDieDiv(die), 'die-selected');
                var selectedDieIndex = this.selectedDice.findIndex(function (d) { return d.id == die.id; });
                if (selectedDieIndex !== -1) {
                    this.selectedDice.splice(selectedDieIndex, 1);
                }
                else {
                    this.selectedDice.push(die);
                }
                this.game.toggleRerollDiceButton();
            }
        }
        else if (this.action === 'freezeDie') {
            this.game.freezeDie(die.id);
        }
    };
    DiceManager.prototype.getSelectedDiceIds = function () {
        return this.selectedDice.map(function (die) { return die.id; });
    };
    DiceManager.prototype.removeSelection = function () {
        var _this = this;
        this.selectedDice.forEach(function (die) { return dojo.removeClass(_this.getDieDiv(die), 'die-selected'); });
        this.selectedDice = [];
    };
    DiceManager.prototype.addRollToDiv = function (dieDiv, rollType, attempt) {
        var _this = this;
        if (attempt === void 0) { attempt = 0; }
        var dieList = dieDiv.getElementsByClassName('die-list')[0];
        if (dieList) {
            dieList.dataset.rollType = rollType;
        }
        else if (attempt < 5) {
            setTimeout(function () { return _this.addRollToDiv(dieDiv, rollType, attempt + 1); }, 200);
        }
    };
    DiceManager.prototype.addDiceRollClass = function (die) {
        var _this = this;
        var dieDiv = this.getDieDiv(die);
        dieDiv.dataset.rolled = die.rolled ? 'true' : 'false';
        if (die.rolled) {
            setTimeout(function () { return _this.addRollToDiv(dieDiv, Math.random() < 0.5 && die.type != 2 ? 'odd' : 'even'); }, 200);
        }
        else {
            this.addRollToDiv(dieDiv, '-');
        }
    };
    DiceManager.prototype.removeDice = function (die, duration, delay) {
        this.dice.splice(this.dice.findIndex(function (d) { return d.id == die.id; }), 1);
        if (duration) {
            this.game.fadeOutAndDestroy("dice" + die.id, duration, delay);
        }
        else {
            var dieDiv = document.getElementById("dice" + die.id);
            dieDiv === null || dieDiv === void 0 ? void 0 : dieDiv.parentNode.removeChild(dieDiv);
        }
    };
    DiceManager.prototype.hideBubble = function (dieId) {
        var bubble = document.getElementById("discussion_bubble_dice" + dieId);
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    };
    DiceManager.prototype.removeAllBubbles = function () {
        this.dieFaceSelectors = [];
        Array.from(document.getElementsByClassName('change-die-discussion_bubble')).forEach(function (elem) { return elem.parentElement.removeChild(elem); });
    };
    DiceManager.prototype.toggleBubbleChangeDie = function (die) {
        var _this = this;
        if (die.type === 2) {
            // die of fate cannot be changed by power cards
            return;
        }
        var divId = "dice" + die.id;
        if (!document.getElementById("discussion_bubble_" + divId)) {
            dojo.place("<div id=\"discussion_bubble_" + divId + "\" class=\"discussion_bubble change-die-discussion_bubble\"></div>", divId);
        }
        var bubble = document.getElementById("discussion_bubble_" + divId);
        var visible = bubble.dataset.visible == 'true';
        if (visible) {
            this.hideBubble(die.id);
        }
        else {
            var bubbleActionButtonsId = "discussion_bubble_" + divId + "-action-buttons";
            var bubbleDieFaceSelectorId = "discussion_bubble_" + divId + "-die-face-selector";
            var creation = bubble.innerHTML == '';
            if (creation) {
                dojo.place("\n                <div id=\"" + bubbleDieFaceSelectorId + "\" class=\"die-face-selector\"></div>\n                <div id=\"" + bubbleActionButtonsId + "\" class=\"action-buttons\"></div>\n                ", bubble.id);
            }
            var herdCullerButtonId_1 = bubbleActionButtonsId + "-herdCuller";
            var gammaBreathButtonId_1 = bubbleActionButtonsId + "-gammaBreath";
            var tailSweepButtonId_1 = bubbleActionButtonsId + "-tailSweep";
            var tinyTailButtonId_1 = bubbleActionButtonsId + "-tinyTail";
            var plotTwistButtonId_1 = bubbleActionButtonsId + "-plotTwist";
            var stretchyButtonId_1 = bubbleActionButtonsId + "-stretchy";
            var saurianAdaptabilityButtonId_1 = bubbleActionButtonsId + "-saurianAdaptability";
            var clownButtonId_1 = bubbleActionButtonsId + "-clown";
            var args_1 = this.changeDieArgs;
            if (!this.dieFaceSelectors[die.id]) {
                this.dieFaceSelectors[die.id] = new DieFaceSelector(bubbleDieFaceSelectorId, die, args_1.canHealWithDice);
            }
            var dieFaceSelector_1 = this.dieFaceSelectors[die.id];
            if (creation) {
                var buttonText = _("Change die face with ${card_name}");
                if (args_1.hasClown) {
                    this.game.createButton(bubbleActionButtonsId, clownButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.cards.getCardName(212, 'text-only') + "</strong>" }), function () {
                        _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 212),
                            _this.toggleBubbleChangeDie(die);
                    }, true);
                }
                else {
                    if (args_1.hasHerdCuller) {
                        this.game.createButton(bubbleActionButtonsId, herdCullerButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.cards.getCardName(22, 'text-only') + "</strong>" }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 22);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasGammaBreath) {
                        this.game.createButton(bubbleActionButtonsId, gammaBreathButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.evolutionCards.getCardName(57, 'text-only') + "</strong>" }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3057);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasTailSweep) {
                        this.game.createButton(bubbleActionButtonsId, tailSweepButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.evolutionCards.getCardName(58, 'text-only') + "</strong>" }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3058);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasTinyTail) {
                        this.game.createButton(bubbleActionButtonsId, tinyTailButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.evolutionCards.getCardName(184, 'text-only') + "</strong>" }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3058);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasPlotTwist) {
                        this.game.createButton(bubbleActionButtonsId, plotTwistButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.cards.getCardName(33, 'text-only') + "</strong>" }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 33),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasStretchy) {
                        this.game.createButton(bubbleActionButtonsId, stretchyButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.cards.getCardName(44, 'text-only') + "</strong>" }) + formatTextIcons(' (2 [Energy])'), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 44),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasSaurianAdaptability) {
                        var saurianAdaptabilityButtonLabel = dojo.string.substitute(_("Change all ${die_face} with ${card_name}"), {
                            'card_name': "<strong>" + this.game.evolutionCards.getCardName(54, 'text-only') + "</strong>",
                            'die_face': formatTextIcons(DICE_STRINGS[die.value]),
                        });
                        this.game.createButton(bubbleActionButtonsId, saurianAdaptabilityButtonId_1, saurianAdaptabilityButtonLabel, function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3054),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                }
                dieFaceSelector_1.onChange = function (value) {
                    if (args_1.hasClown) {
                        dojo.toggleClass(clownButtonId_1, 'disabled', value < 1);
                    }
                    else {
                        if (args_1.hasHerdCuller && die.value != 1) {
                            dojo.toggleClass(herdCullerButtonId_1, 'disabled', value != 1);
                        }
                        if (args_1.hasGammaBreath && die.value != 6) {
                            dojo.toggleClass(gammaBreathButtonId_1, 'disabled', value != 6);
                        }
                        if (args_1.hasTailSweep) {
                            dojo.toggleClass(tailSweepButtonId_1, 'disabled', value != 1 && value != 2);
                        }
                        if (args_1.hasTinyTail && die.value != 1) {
                            dojo.toggleClass(tinyTailButtonId_1, 'disabled', value != 1);
                        }
                        if (args_1.hasPlotTwist) {
                            dojo.toggleClass(plotTwistButtonId_1, 'disabled', value < 1);
                        }
                        if (args_1.hasStretchy) {
                            var couldUseStretchy = value >= 1;
                            dojo.toggleClass(stretchyButtonId_1, 'disabled', !couldUseStretchy || _this.game.getPlayerEnergy(args_1.playerId) < 2);
                            if (couldUseStretchy) {
                                document.getElementById(stretchyButtonId_1).dataset.enableAtEnergy = '2';
                            }
                            else {
                                document.getElementById(stretchyButtonId_1).removeAttribute('data-enable-at-energy');
                            }
                        }
                        if (args_1.hasSaurianAdaptability) {
                            dojo.removeClass(saurianAdaptabilityButtonId_1, 'disabled');
                        }
                    }
                };
                bubble.addEventListener('click', function (event) { return event.stopImmediatePropagation(); });
            }
            if (die.value == dieFaceSelector_1.getValue()) {
                dieFaceSelector_1.reset(die.value);
                if (args_1.hasClown) {
                    dojo.addClass(stretchyButtonId_1, 'disabled');
                }
                else {
                    if (args_1.hasHerdCuller) {
                        dojo.addClass(herdCullerButtonId_1, 'disabled');
                    }
                    if (args_1.hasGammaBreath) {
                        dojo.addClass(gammaBreathButtonId_1, 'disabled');
                    }
                    if (args_1.hasTailSweep) {
                        dojo.addClass(tailSweepButtonId_1, 'disabled');
                    }
                    if (args_1.hasTinyTail) {
                        dojo.addClass(tinyTailButtonId_1, 'disabled');
                    }
                    if (args_1.hasPlotTwist) {
                        dojo.addClass(plotTwistButtonId_1, 'disabled');
                    }
                    if (args_1.hasStretchy) {
                        dojo.addClass(stretchyButtonId_1, 'disabled');
                    }
                    if (args_1.hasSaurianAdaptability) {
                        dojo.addClass(saurianAdaptabilityButtonId_1, 'disabled');
                    }
                }
            }
            args_1.dice.filter(function (idie) { return idie.id != die.id; }).forEach(function (idie) { return _this.hideBubble(idie.id); });
            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
    };
    DiceManager.prototype.setSelectableDice = function (selectableDice) {
        var _this = this;
        if (selectableDice === void 0) { selectableDice = null; }
        var playerIsActive = this.game.isCurrentPlayerActive();
        this.dice.forEach(function (die) { return _this.getDieDiv(die).classList.toggle('selectable', playerIsActive && (selectableDice === null || selectableDice === void 0 ? void 0 : selectableDice.some(function (d) { return d.id == die.id; }))); });
    };
    return DiceManager;
}());
var SPACE_BETWEEN_ANIMATION_AT_START = 43;
var ANIMATION_FULL_SIZE = 220;
var AnimationManager = /** @class */ (function () {
    function AnimationManager(game, diceManager) {
        this.game = game;
        this.diceManager = diceManager;
    }
    AnimationManager.prototype.getDice = function (dieValue) {
        var dice = this.diceManager.getDice();
        var filteredDice = this.getDiceShowingFace(dice, dieValue);
        return filteredDice.length ? filteredDice : dice;
    };
    AnimationManager.prototype.resolveNumberDice = function (args) {
        var dice = this.getDice(args.diceValue);
        this.game.displayScoring("dice" + (dice[Math.floor(dice.length / 2)] || dice[0]).id, this.game.getPreferencesManager().getDiceScoringColor(), args.deltaPoints, 1500);
    };
    AnimationManager.prototype.getDiceShowingFace = function (allDice, face) {
        var dice = allDice.filter(function (die) { var _a; return !die.type && ((_a = document.getElementById("dice" + die.id)) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        }
        else {
            var berserkDice = this.diceManager.getBerserkDice();
            if (face == 5) { // energy
                return berserkDice.filter(function (die) { var _a; return die.value >= 1 && die.value <= 2 && ((_a = document.getElementById("dice" + die.id)) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else if (face == 6) { // smash
                return berserkDice.filter(function (die) { var _a; return die.value >= 3 && die.value <= 5 && ((_a = document.getElementById("dice" + die.id)) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else {
                return [];
            }
        }
    };
    AnimationManager.prototype.addDiceAnimation = function (diceValue, playerIds, number, targetToken) {
        var _this = this;
        if (document.visibilityState === 'hidden' || this.game.instantaneousMode) {
            return;
        }
        var dice = this.getDice(diceValue);
        var originTop = (document.getElementById(dice[0] ? "dice" + dice[0].id : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect().top;
        var leftDieBR = (document.getElementById(dice[0] ? "dice" + dice[0].id : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect();
        var rightDieBR = (document.getElementById(dice.length ? "dice" + dice[dice.length - 1].id : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect();
        var originCenter = (leftDieBR.left + rightDieBR.right) / 2;
        playerIds.forEach(function (playerId) {
            var maxSpaces = SPACE_BETWEEN_ANIMATION_AT_START * number;
            var halfMaxSpaces = maxSpaces / 2;
            var shift = targetToken ? 16 : 59;
            var _loop_2 = function (i) {
                var originLeft = originCenter - halfMaxSpaces + SPACE_BETWEEN_ANIMATION_AT_START * i;
                var animationId = "animation" + diceValue + "-" + i + "-player" + playerId + "-" + new Date().getTime();
                dojo.place("<div id=\"" + animationId + "\" class=\"animation animation" + diceValue + "\" style=\"left: " + (originLeft + window.scrollX - 94) + "px; top: " + (originTop + window.scrollY - 94) + "px;\"></div>", document.body);
                var animationDiv = document.getElementById(animationId);
                setTimeout(function () {
                    var middleIndex = number / 2;
                    var deltaX = (i - middleIndex) * ANIMATION_FULL_SIZE;
                    animationDiv.style.transform = "translate(" + deltaX + "px, 100px) scale(1)";
                }, 50);
                setTimeout(function () {
                    var _a, _b;
                    var targetId = "monster-figure-" + playerId;
                    if (targetToken) {
                        var tokensDivs = document.querySelectorAll("div[id^='token-wrapper-" + playerId + "-" + targetToken + "-token'");
                        targetId = tokensDivs[tokensDivs.length - (i + 1)].id;
                    }
                    var destination = (_a = document.getElementById(targetId)) === null || _a === void 0 ? void 0 : _a.getBoundingClientRect();
                    if (destination) {
                        var deltaX = destination.left - originLeft + shift * _this.game.getZoom();
                        var deltaY = destination.top - originTop + shift * _this.game.getZoom();
                        animationDiv.style.transition = "transform 0.5s ease-in";
                        animationDiv.style.transform = "translate(" + deltaX + "px, " + deltaY + "px) scale(" + 0.3 * _this.game.getZoom() + ")";
                        animationDiv.addEventListener('transitionend', function () { var _a; return (_a = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(animationDiv); });
                        // security
                        setTimeout(function () { var _a; return (_a = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(animationDiv); }, 1050);
                    }
                    else {
                        // in case the player dies when starting the animation
                        (_b = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _b === void 0 ? void 0 : _b.removeChild(animationDiv);
                    }
                }, 1000);
            };
            for (var i = 0; i < number; i++) {
                _loop_2(i);
            }
        });
    };
    AnimationManager.prototype.resolveHealthDice = function (playerId, number, targetToken) {
        this.addDiceAnimation(4, [playerId], number, targetToken);
    };
    AnimationManager.prototype.resolveEnergyDice = function (args) {
        this.addDiceAnimation(5, [args.playerId], args.deltaEnergy);
    };
    AnimationManager.prototype.resolveSmashDice = function (args) {
        this.addDiceAnimation(6, args.smashedPlayersIds, args.number);
    };
    return AnimationManager;
}());
var HeartActionSelector = /** @class */ (function () {
    function HeartActionSelector(game, nodeId, args) {
        var _this = this;
        this.game = game;
        this.nodeId = nodeId;
        this.args = args;
        this.selections = [];
        this.createToggleButtons(nodeId, args);
        dojo.place("<div id=\"" + nodeId + "-apply-wrapper\" class=\"action-selector-apply-wrapper\"><button class=\"bgabutton bgabutton_blue action-selector-apply\" id=\"" + nodeId + "-apply\">" + _('Apply') + "</button></div>", nodeId);
        document.getElementById(nodeId + "-apply").addEventListener('click', function () { return _this.game.applyHeartActions(_this.selections); });
    }
    HeartActionSelector.prototype.createToggleButtons = function (nodeId, args) {
        var _this = this;
        args.dice.filter(function (die) { return die.value === 4; }).forEach(function (die, index) {
            var html = "<div class=\"row\">\n                <div class=\"legend\">\n                    <div class=\"dice-icon dice4\"></div>\n                </div>\n                <div id=\"" + nodeId + "-die" + index + "\" class=\"toggle-buttons\"></div>\n            </div>";
            dojo.place(html, nodeId);
            _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-heal", _('Heal'), function () { return _this.healSelected(index); }, false, true);
            if (!args.canHealWithDice) {
                var buttonDiv = document.getElementById(nodeId + "-die" + index + "-heal");
                buttonDiv.style.position = 'relative';
                buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
            }
            _this.selections[index] = { action: 'heal' };
            if (args.shrinkRayTokens > 0) {
                _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-shrink-ray", _('Remove Shrink Ray token'), function () { return _this.shrinkRaySelected(index); }, !args.canHealWithDice);
                if (!args.canHealWithDice) {
                    var buttonDiv = document.getElementById(nodeId + "-die" + index + "-shrink-ray");
                    buttonDiv.style.position = 'relative';
                    buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
                }
            }
            if (args.poisonTokens > 0) {
                _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-poison", _('Remove Poison token'), function () { return _this.poisonSelected(index); }, !args.canHealWithDice);
                if (!args.canHealWithDice) {
                    var buttonDiv = document.getElementById(nodeId + "-die" + index + "-poison");
                    buttonDiv.style.position = 'relative';
                    buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
                }
            }
            if (args.hasHealingRay) {
                args.healablePlayers.forEach(function (healablePlayer) {
                    return _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-heal-player-" + healablePlayer.id, dojo.string.substitute(_('Heal player ${player_name}'), { 'player_name': "<span style=\"color: #" + healablePlayer.color + "\">" + healablePlayer.name + "</span>" }), function () { return _this.healPlayerSelected(index, healablePlayer.id); }, false);
                });
            }
        });
    };
    HeartActionSelector.prototype.createToggleButton = function (destinationId, id, text, callback, disabled, selected) {
        if (selected === void 0) { selected = false; }
        var html = "<div class=\"toggle-button\" id=\"" + id + "\">\n            " + text + "\n        </button>";
        dojo.place(html, destinationId);
        if (disabled) {
            dojo.addClass(id, 'disabled');
        }
        else if (selected) {
            dojo.addClass(id, 'selected');
        }
        document.getElementById(id).addEventListener('click', function () { return callback(); });
    };
    HeartActionSelector.prototype.removeOldSelection = function (index) {
        var oldSelectionId = this.selections[index].action == 'heal-player' ? this.nodeId + "-die" + index + "-heal-player-" + this.selections[index].playerId : this.nodeId + "-die" + index + "-" + this.selections[index].action;
        dojo.removeClass(oldSelectionId, 'selected');
    };
    HeartActionSelector.prototype.healSelected = function (index) {
        if (this.selections[index].action == 'heal') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'heal';
        dojo.addClass(this.nodeId + "-die" + index + "-" + this.selections[index].action, 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.shrinkRaySelected = function (index) {
        if (this.selections[index].action == 'shrink-ray') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'shrink-ray';
        dojo.addClass(this.nodeId + "-die" + index + "-" + this.selections[index].action, 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.poisonSelected = function (index) {
        if (this.selections[index].action == 'poison') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'poison';
        dojo.addClass(this.nodeId + "-die" + index + "-" + this.selections[index].action, 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.healPlayerSelected = function (index, playerId) {
        if (this.selections[index].action == 'heal-player' && this.selections[index].playerId == playerId) {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'heal-player';
        this.selections[index].playerId = playerId;
        dojo.addClass(this.nodeId + "-die" + index + "-heal-player-" + playerId, 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.checkDisabled = function () {
        var _this = this;
        var removedShrinkRays = this.selections.filter(function (selection) { return selection.action === 'shrink-ray'; }).length;
        var removedPoisons = this.selections.filter(function (selection) { return selection.action === 'poison'; }).length;
        var healedPlayers = [];
        this.args.healablePlayers.forEach(function (player) { return healedPlayers[player.id] = _this.selections.filter(function (selection) { return selection.action === 'heal-player' && selection.playerId == player.id; }).length; });
        this.selections.forEach(function (selection, index) {
            if (_this.args.shrinkRayTokens > 0) {
                dojo.toggleClass(_this.nodeId + "-die" + index + "-shrink-ray", 'disabled', selection.action != 'shrink-ray' && removedShrinkRays >= _this.args.shrinkRayTokens);
            }
            if (_this.args.poisonTokens > 0) {
                dojo.toggleClass(_this.nodeId + "-die" + index + "-poison", 'disabled', selection.action != 'poison' && removedPoisons >= _this.args.poisonTokens);
            }
            if (_this.args.hasHealingRay) {
                _this.args.healablePlayers.forEach(function (player) { return dojo.toggleClass(_this.nodeId + "-die" + index + "-heal-player-" + player.id, 'disabled', selection.action != 'heal-player' && selection.playerId != player.id && healedPlayers[player.id] >= player.missingHearts); });
            }
        });
    };
    return HeartActionSelector;
}());
var SmashActionSelector = /** @class */ (function () {
    function SmashActionSelector(game, nodeId, args) {
        var _this = this;
        this.game = game;
        this.nodeId = nodeId;
        this.args = args;
        this.selections = [];
        this.createToggleButtons(nodeId, args);
        dojo.place("<div id=\"" + nodeId + "-apply-wrapper\" class=\"action-selector-apply-wrapper\"><button class=\"bgabutton bgabutton_blue action-selector-apply\" id=\"" + nodeId + "-apply\">" + _('Apply') + "</button></div>", nodeId);
        document.getElementById(nodeId + "-apply").addEventListener('click', function () { return _this.game.applySmashActions(_this.selections); });
    }
    SmashActionSelector.prototype.createToggleButtons = function (nodeId, args) {
        var _this = this;
        args.willBeWoundedIds.forEach(function (playerId) {
            var player = _this.game.getPlayer(playerId);
            var html = "<div class=\"row\">\n                <div class=\"legend\" style=\"color: #" + player.color + "\">\n                    " + player.name + "\n                </div>\n                <div id=\"" + nodeId + "-player" + playerId + "\" class=\"toggle-buttons\"></div>\n            </div>";
            dojo.place(html, nodeId);
            _this.selections[playerId] = 'smash';
            _this.createToggleButton(nodeId + "-player" + playerId, nodeId + "-player" + playerId + "-smash", _("Don't steal"), function () { return _this.setSelectedAction(playerId, 'smash'); }, true);
            _this.createToggleButton(nodeId + "-player" + playerId, nodeId + "-player" + playerId + "-steal", formatTextIcons(_('Steal 1[Star] and 1[Energy]')), function () { return _this.setSelectedAction(playerId, 'steal'); });
        });
    };
    SmashActionSelector.prototype.createToggleButton = function (destinationId, id, text, callback, selected) {
        if (selected === void 0) { selected = false; }
        var html = "<div class=\"toggle-button\" id=\"" + id + "\">\n            " + text + "\n        </button>";
        dojo.place(html, destinationId);
        if (selected) {
            dojo.addClass(id, 'selected');
        }
        document.getElementById(id).addEventListener('click', function () { return callback(); });
    };
    SmashActionSelector.prototype.removeOldSelection = function (playerId) {
        var oldSelectionId = this.nodeId + "-player" + playerId + "-" + this.selections[playerId];
        dojo.removeClass(oldSelectionId, 'selected');
    };
    SmashActionSelector.prototype.setSelectedAction = function (playerId, action) {
        if (this.selections[playerId] == action) {
            return;
        }
        this.removeOldSelection(playerId);
        this.selections[playerId] = action;
        dojo.addClass(this.nodeId + "-player" + playerId + "-" + action, 'selected');
    };
    return SmashActionSelector;
}());
var PreferencesManager = /** @class */ (function () {
    function PreferencesManager(game) {
        this.game = game;
        this.setupPreferences();
    }
    PreferencesManager.prototype.setupPreferences = function () {
        var _this = this;
        // Extract the ID and value from the UI control
        var onchange = function (e) {
            var match = e.target.id.match(/^preference_[cf]ontrol_(\d+)$/);
            if (!match) {
                return;
            }
            var prefId = +match[1];
            var prefValue = +e.target.value;
            _this.game.prefs[prefId].value = prefValue;
            _this.onPreferenceChange(prefId, prefValue);
        };
        // Call onPreferenceChange() when any value changes
        dojo.query(".preference_control").connect("onchange", onchange);
        // Call onPreferenceChange() now
        dojo.forEach(dojo.query("#ingame_menu_content .preference_control"), function (el) { return onchange({ target: el }); });
        try {
            document.getElementById('preference_control_203').closest(".preference_choice").style.display = 'none';
            document.getElementById('preference_fontrol_203').closest(".preference_choice").style.display = 'none';
        }
        catch (e) { }
    };
    PreferencesManager.prototype.getGameVersionNumber = function (versionNumber) {
        if (versionNumber > 0) {
            return versionNumber;
        }
        else {
            if (this.game.isDarkEdition()) {
                return 5;
            }
            else if (this.game.isPowerUpExpansion()) {
                return 4;
            }
            else if (this.game.isHalloweenExpansion()) {
                return 2;
            }
            return 1;
        }
    };
    PreferencesManager.prototype.onPreferenceChange = function (prefId, prefValue) {
        switch (prefId) {
            case 201:
                this.game.setFont(prefValue);
                break;
            case 203:
                if (prefValue == 2) {
                    dojo.destroy('board-corner-highlight');
                    dojo.destroy('twoPlayersVariant-message');
                }
                break;
            case 204:
                document.getElementsByTagName('html')[0].dataset.background = '' + this.getGameVersionNumber(prefValue);
                break;
            case 205:
                document.getElementsByTagName('html')[0].dataset.dice = '' + this.getGameVersionNumber(prefValue);
                break;
        }
    };
    PreferencesManager.prototype.getDiceScoringColor = function () {
        var prefId = this.getGameVersionNumber(this.game.prefs[205].value);
        switch (prefId) {
            case 2: return '000000';
            case 3: return '0096CC';
            case 4: return '157597';
            case 5: return 'ecda5f';
        }
        return '96c93c';
    };
    return PreferencesManager;
}());
var WICKEDNESS_MONSTER_ICON_POSITION = [
    [2, 270],
    [32, 317],
    [84, 312],
    [124, 280],
    [103, 235],
    [82, 191],
    [124, 164],
    [83, 130],
    [41, 96],
    [84, 58],
    [124, 33],
];
var WICKEDNESS_MONSTER_ICON_POSITION_DARK_EDITION = [
    [-50, 302],
    [29, 345],
    [8, 312],
    [42, 277],
    [29, 239],
    [5, 200],
    [42, 165],
    [10, 130],
    [37, 90],
    [9, 58],
    [41, 22],
];
var TableCenter = /** @class */ (function () {
    function TableCenter(game, players, visibleCards, topDeckCardBackType, wickednessTiles, tokyoTowerLevels, curseCard) {
        var _this = this;
        this.game = game;
        this.wickednessTiles = [];
        this.wickednessPoints = new Map();
        this.createVisibleCards(visibleCards, topDeckCardBackType);
        if (game.isWickednessExpansion()) {
            dojo.place("\n            <div id=\"wickedness-board-wrapper\">\n                <div id=\"wickedness-board\"></div>\n            </div>", 'full-board');
            this.createWickednessTiles(wickednessTiles);
            if (!game.isDarkEdition()) {
                document.getElementById("table-cards").dataset.wickednessBoard = 'true';
            }
            players.forEach(function (player) {
                dojo.place("<div id=\"monster-icon-" + player.id + "-wickedness\" class=\"monster-icon monster" + player.monster + "\" style=\"background-color: " + (player.monster > 100 ? 'unset' : '#' + player.color) + ";\"></div>", 'wickedness-board');
                _this.wickednessPoints.set(Number(player.id), Number(player.wickedness));
            });
            this.moveWickednessPoints();
        }
        if (game.isKingkongExpansion()) {
            dojo.place("<div id=\"tokyo-tower-0\" class=\"tokyo-tower-wrapper\"></div>", 'full-board');
            this.tokyoTower = new TokyoTower('tokyo-tower-0', tokyoTowerLevels);
        }
        if (game.isAnubisExpansion()) {
            this.createCurseCard(curseCard);
        }
        else {
            document.getElementById('table-curse-cards').style.display = 'none';
        }
    }
    TableCenter.prototype.createVisibleCards = function (visibleCards, topDeckCardBackType) {
        var _this = this;
        this.visibleCards = new ebg.stock();
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection-except-double-selection';
        this.visibleCards.create(this.game, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = function (card_div, card_type_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
        this.visibleCards.image_items_per_row = 10;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, function (_, item_id) { return _this.game.onVisibleCardClick(_this.visibleCards, Number(item_id)); });
        this.game.cards.setupCards([this.visibleCards]);
        this.setVisibleCards(visibleCards);
        this.setTopDeckCardBackType(topDeckCardBackType);
    };
    TableCenter.prototype.createCurseCard = function (curseCard) {
        var _this = this;
        dojo.place("<div id=\"curse-wrapper\">\n            <div id=\"curse-deck\"></div>\n            <div id=\"curse-card\"></div>\n        </div>", 'table-curse-cards');
        this.curseCard = new ebg.stock();
        this.curseCard.setSelectionAppearance('class');
        this.curseCard.selectionClass = 'no-visible-selection';
        this.curseCard.create(this.game, $('curse-card'), CARD_WIDTH, CARD_HEIGHT);
        this.curseCard.setSelectionMode(0);
        this.curseCard.centerItems = true;
        this.curseCard.onItemCreate = function (card_div, card_type_id) { return _this.game.curseCards.setupNewCard(card_div, card_type_id); };
        this.game.curseCards.setupCards([this.curseCard]);
        this.curseCard.addToStockWithId(curseCard.type, '' + curseCard.id);
        this.game.addTooltipHtml("curse-deck", "\n        <strong>" + _("Curse card pile.") + "</strong>\n        <div> " + dojo.string.substitute(_("Discard the current Curse and reveal the next one by rolling ${changeCurseCard}."), { 'changeCurseCard': '<div class="anubis-icon anubis-icon1"></div>' }) + "</div>\n        ");
    };
    TableCenter.prototype.setVisibleCardsSelectionMode = function (mode) {
        this.visibleCards.setSelectionMode(mode);
    };
    TableCenter.prototype.setVisibleCardsSelectionClass = function (visible) {
        document.getElementById('table-center').classList.toggle('double-selection', visible);
    };
    TableCenter.prototype.showPickStock = function (cards) {
        var _this = this;
        if (!this.pickCard) {
            dojo.place('<div id="pick-stock" class="card-stock"></div>', 'deck-wrapper');
            this.pickCard = new ebg.stock();
            this.pickCard.setSelectionAppearance('class');
            this.pickCard.selectionClass = 'no-visible-selection';
            this.pickCard.create(this.game, $('pick-stock'), CARD_WIDTH, CARD_HEIGHT);
            this.pickCard.setSelectionMode(1);
            this.pickCard.onItemCreate = function (card_div, card_type_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
            this.pickCard.image_items_per_row = 10;
            this.pickCard.centerItems = true;
            dojo.connect(this.pickCard, 'onChangeSelection', this, function (_, item_id) { return _this.game.onVisibleCardClick(_this.pickCard, Number(item_id)); });
        }
        else {
            document.getElementById('pick-stock').style.display = 'block';
        }
        this.game.cards.setupCards([this.pickCard]);
        this.game.cards.addCardsToStock(this.pickCard, cards);
    };
    TableCenter.prototype.hidePickStock = function () {
        var div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
        }
    };
    TableCenter.prototype.renewCards = function (cards, topDeckCardBackType) {
        this.visibleCards.removeAll();
        this.setVisibleCards(cards);
        this.setTopDeckCardBackType(topDeckCardBackType);
    };
    TableCenter.prototype.setTopDeckCardBackType = function (topDeckCardBackType) {
        if (topDeckCardBackType !== undefined && topDeckCardBackType !== null) {
            document.getElementById('deck').dataset.type = topDeckCardBackType;
        }
    };
    TableCenter.prototype.setInitialCards = function (cards) {
        this.game.cards.addCardsToStock(this.visibleCards, cards, 'deck');
    };
    TableCenter.prototype.setVisibleCards = function (cards) {
        var newWeights = {};
        cards.forEach(function (card) { return newWeights[card.type] = card.location_arg; });
        this.visibleCards.changeItemsWeight(newWeights);
        this.game.cards.addCardsToStock(this.visibleCards, cards, 'deck');
    };
    TableCenter.prototype.removeOtherCardsFromPick = function (cardId) {
        var _this = this;
        var _a;
        var removeFromPickIds = (_a = this.pickCard) === null || _a === void 0 ? void 0 : _a.items.map(function (item) { return Number(item.id); });
        removeFromPickIds === null || removeFromPickIds === void 0 ? void 0 : removeFromPickIds.forEach(function (id) {
            if (id !== cardId) {
                _this.pickCard.removeFromStockById('' + id);
            }
        });
    };
    TableCenter.prototype.changeVisibleCardWeight = function (card) {
        var _a;
        this.visibleCards.changeItemsWeight((_a = {}, _a[card.type] = card.location_arg, _a));
    };
    TableCenter.prototype.getVisibleCards = function () {
        return this.visibleCards;
    };
    TableCenter.prototype.getPickCard = function () {
        return this.pickCard;
    };
    TableCenter.prototype.getTokyoTower = function () {
        return this.tokyoTower;
    };
    TableCenter.prototype.changeCurseCard = function (card) {
        this.curseCard.removeAll();
        this.curseCard.addToStockWithId(card.type, '' + card.id, 'curse-deck');
    };
    TableCenter.prototype.createWickednessTiles = function (wickednessTiles) {
        var _this = this;
        WICKEDNESS_LEVELS.forEach(function (level) {
            _this.wickednessTiles[level] = wickednessTiles.filter(function (tile) { return _this.game.wickednessTiles.getCardLevel(tile.type) === level; });
            dojo.place("<div id=\"wickedness-tiles-pile-" + level + "\" class=\"wickedness-tiles-pile wickedness-tile-stock\"></div>", 'wickedness-board');
            _this.setWickednessTilesPile(level);
        });
    };
    TableCenter.prototype.moveWickednessPoints = function () {
        var _this = this;
        this.wickednessPoints.forEach(function (wickedness, playerId) {
            var markerDiv = document.getElementById("monster-icon-" + playerId + "-wickedness");
            var positionArray = _this.game.isDarkEdition() ? WICKEDNESS_MONSTER_ICON_POSITION_DARK_EDITION : WICKEDNESS_MONSTER_ICON_POSITION;
            var position = positionArray[wickedness];
            var topShift = 0;
            var leftShift = 0;
            _this.wickednessPoints.forEach(function (iWickedness, iPlayerId) {
                if (iWickedness === wickedness && iPlayerId < playerId) {
                    topShift += 5;
                    leftShift += 5;
                }
            });
            markerDiv.style.left = position[0] + leftShift + "px";
            markerDiv.style.top = position[1] + topShift + "px";
        });
    };
    TableCenter.prototype.setWickedness = function (playerId, wickedness) {
        this.wickednessPoints.set(playerId, wickedness);
        this.moveWickednessPoints();
    };
    TableCenter.prototype.showWickednessTiles = function (level) {
        WICKEDNESS_LEVELS.filter(function (l) { return l !== level; }).forEach(function (l) { return dojo.removeClass("wickedness-tiles-pile-" + l, 'opened'); });
        dojo.addClass("wickedness-tiles-pile-" + level, 'opened');
    };
    TableCenter.prototype.setWickednessTilesSelectable = function (level, show, selectable) {
        if (show) {
            this.showWickednessTiles(level);
        }
        else {
            WICKEDNESS_LEVELS.forEach(function (level) { return dojo.removeClass("wickedness-tiles-pile-" + level, 'opened'); });
        }
        if (selectable) {
            dojo.addClass("wickedness-tiles-pile-" + level, 'selectable');
        }
        else {
            WICKEDNESS_LEVELS.forEach(function (level) {
                dojo.removeClass("wickedness-tiles-pile-" + level, 'selectable');
            });
        }
    };
    TableCenter.prototype.setWickednessTilesPile = function (level) {
        var _this = this;
        var pileDiv = document.getElementById("wickedness-tiles-pile-" + level);
        pileDiv.innerHTML = '';
        this.wickednessTiles[level].forEach(function (tile, index) {
            dojo.place("<div id=\"wickedness-tiles-pile-tile-" + tile.id + "\" class=\"stockitem wickedness-tile\" data-side=\"" + tile.side + "\" data-background-index=\"" + wickenessTilesIndex[(tile.type % 100) - 1] + "\"></div>", pileDiv);
            var tileDiv = document.getElementById("wickedness-tiles-pile-tile-" + tile.id);
            _this.game.wickednessTiles.setDivAsCard(tileDiv, tile.type);
            _this.game.addTooltipHtml(tileDiv.id, _this.game.wickednessTiles.getTooltip(tile.type));
            tileDiv.style.setProperty('--order', '' + index);
            tileDiv.addEventListener('click', function () {
                if (tileDiv.closest('.wickedness-tiles-pile').classList.contains('selectable')) {
                    _this.game.takeWickednessTile(tile.id);
                }
            });
        });
        pileDiv.style.setProperty('--tile-count', '' + this.wickednessTiles[level].length);
    };
    TableCenter.prototype.removeWickednessTileFromPile = function (level, removedTile) {
        this.wickednessTiles[level].splice(this.wickednessTiles[level].findIndex(function (tile) { return tile.id == removedTile.id; }), 1);
        this.setWickednessTilesPile(level);
        dojo.removeClass("wickedness-tiles-pile-" + level, 'opened');
    };
    return TableCenter;
}());
var RULEBOOK_LINKS = [
    {
        'en': 'https://cdn.shopify.com/s/files/1/0049/3351/7425/files/KOT2-rulebook_EN.pdf?1387',
        'fr': 'https://iello.fr/regles/regles_KOTv2.pdf',
    },
    {
        'en': 'https://www.fgbradleys.com/rules/rules6/King%20of%20Tokyo%20Halloween%20-%20rules.pdf',
        'fr': 'https://www.iello.fr/regles/KOT_HALLOWEEN_regles.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/47/0e/7f-king-of-tokyo-new-york-monster-pack-cthulhu-rulebook.pdf',
        'fr': 'https://www.play-in.com/pdf/rules_games/monster_pack_cthulhu_-_extension_king_of_tokyo_regles_fr.pdf',
    },
    {
        'en': 'https://www.iello.fr/regles/KOT_KingKong-US-Rules.pdf',
        'fr': 'http://iello.fr/regles/KOT_KONG_regles.pdf',
    },
    {
        'en': 'http://iello.fr/regles/KOT-Anubis-rulebook-EN.pdf',
        'fr': 'http://iello.fr/regles/51530_regles.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/6f/b6/07-king-of-tokyo-new-york-monster-pack-cybertooth-rulebook.pdf',
        'fr': 'https://cdn.1j1ju.com/medias/80/e7/99-king-of-tokyo-new-york-monster-pack-cybertooth-regle.pdf',
    },
    {
        'en': 'https://boardgamegeek.com/file/download_redirect/3d766a927a5baa69f0c801f9c94075980ad22892161e1f12/KOT_Wickedness_Rules_EN.pdf',
        'fr': 'https://iello.fr/regles/KOT_mechancete_Rules_FR.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/69/8c/32-king-of-tokyo-power-up-rulebook.pdf',
        'fr': 'https://cdn.1j1ju.com/medias/8c/62/83-king-of-tokyo-power-up-regle.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/53/d4/2e-king-of-tokyo-dark-edition-rulebook.pdf',
        'fr': 'http://iello.fr/regles/KOT%20DARK_rulebook.pdf',
    },
];
var EXPANSION_NUMBER = 7; // TODODE
var ActivatedExpansionsPopin = /** @class */ (function () {
    function ActivatedExpansionsPopin(gamedatas, language) {
        var _this = this;
        if (language === void 0) { language = 'en'; }
        this.gamedatas = gamedatas;
        this.language = language;
        this.activatedExpansions = [];
        if (this.gamedatas.halloweenExpansion) {
            this.activatedExpansions.push(1);
        }
        if (this.gamedatas.cthulhuExpansion) {
            this.activatedExpansions.push(2);
        }
        if (this.gamedatas.kingkongExpansion) {
            this.activatedExpansions.push(3);
        }
        if (this.gamedatas.anubisExpansion) {
            this.activatedExpansions.push(4);
        }
        if (this.gamedatas.cybertoothExpansion) {
            this.activatedExpansions.push(5);
        }
        if (this.gamedatas.wickednessExpansion) {
            this.activatedExpansions.push(6);
        }
        if (this.gamedatas.powerUpExpansion) {
            this.activatedExpansions.push(7);
        }
        if (this.gamedatas.darkEdition) {
            this.activatedExpansions.push(8);
        }
        if (this.activatedExpansions.length) {
            var html = "\n            <div>\t\t\t\t\t\n                <button id=\"active-expansions-button\" class=\"bgabutton bgabutton_gray\">\n                    <div class=\"title\">" + _('Active expansions') + "</div>\n                    <div class=\"expansion-zone-list\">";
            for (var i = 1; i <= EXPANSION_NUMBER; i++) {
                var activated = this.activatedExpansions.includes(i);
                if (i == 6 && this.gamedatas.darkEdition) {
                    activated = false;
                }
                html += "<div class=\"expansion-zone\" data-expansion=\"" + i + "\" data-activated=\"" + activated.toString() + "\"><div class=\"expansion-icon\"></div></div>";
            }
            html += "        </div>\n                </button>\n            </div>";
            dojo.place(html, "player_boards");
            document.getElementById("active-expansions-button").addEventListener("click", function () { return _this.createPopin(); });
        }
    }
    ActivatedExpansionsPopin.prototype.getTitle = function (index) {
        switch (index) {
            case 0: return _('Base game');
            case 1: return _('“Halloween” event (Costume cards)');
            case 2: return _('“Battle of the Gods, part I” event (Cultists)');
            case 3: return _('“Nature vs. Machine, part I” event (Tokyo Tower)');
            case 4: return _('“Battle of the Gods: the Revenge!” event (Curse cards)');
            case 5: return _('“Nature vs. Machine: the Comeback!” event (Berserk)');
            case 6: return _('“Even more wicked!” event');
            case 7: return _('Power-Up! (Evolutions)');
            case 8: return /*TODODE*/ ('Dark Edition');
        }
    };
    ActivatedExpansionsPopin.prototype.getDescription = function (index) {
        switch (index) {
            case 1: return formatTextIcons(_('Halloween expansion brings a new set of Costume cards. Each player start with a Costume card (chosen between 2). When you smash a player with at least 3 [diceSmash], you can steal their Costumes cards (by paying its cost).'));
            case 2: return formatTextIcons("<p>" + _("After resolving your dice, if you rolled four identical faces, take a Cultist tile") + "</p>\n            <p>" + _("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll.") + "</p>");
            case 3: return formatTextIcons("<p>" + _("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1] while in Tokyo.") + "</p>\n            <p>" + _("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative).") + "</p>\n            <p><strong>" + _("Claiming the top level automatically wins the game.") + "</strong></p>");
            case 4: return formatTextIcons(_("Anubis brings the Curse cards and the Die of Fate. The Curse card on the table show a permanent effect, applied to all players, and the Die of Fate can trigger the Ankh effect or the Snake effect."));
            case 5: return formatTextIcons("<p>" + _("When you roll 4 or more [diceSmash], you are in Berserk mode!") + "</p>\n            <p>" + _("You play with the additional Berserk die, until you heal yourself.") + "</p>");
            case 6: return formatTextIcons(_("When you roll 3 or more [dice1] or [dice2], gain Wickeness points to get special Tiles."));
            case 7: return formatTextIcons(_("Power-Up! expansion brings new sets of Evolution cards, giving each Monster special abilities. Each player start with an Evolution card (chosen between 2). You can play this Evolution card any time. When you roll 3 or more [diceHeart], you can choose a new Evolution card."));
            case 8: return /*TODODE_*/ ("Dark Edition brings gorgeous art, and the wickedness track is included in the game, with a new set of cards.");
        }
        return '';
    };
    ActivatedExpansionsPopin.prototype.viewRulebook = function (index) {
        var _a;
        var rulebookContainer = document.getElementById("rulebook-" + index);
        var show = rulebookContainer.innerHTML === '';
        if (show) {
            var url = (_a = RULEBOOK_LINKS[index][this.language]) !== null && _a !== void 0 ? _a : RULEBOOK_LINKS[index]['en'];
            var html = "<iframe src=\"" + url + "\" style=\"width: 100%; height: 60vh\"></iframe>";
            rulebookContainer.innerHTML = html;
        }
        else {
            rulebookContainer.innerHTML = '';
        }
        document.getElementById("show-rulebook-" + index).innerHTML = show ? _('Hide rulebook') : _('Show rulebook');
    };
    ActivatedExpansionsPopin.prototype.createBlock = function (index) {
        var _this = this;
        var _a;
        var url = (_a = RULEBOOK_LINKS[index][this.language]) !== null && _a !== void 0 ? _a : RULEBOOK_LINKS[index]['en'];
        var activated = this.activatedExpansions.includes(index);
        var html = "\n        <details data-expansion=\"" + index + "\" data-activated=\"" + activated.toString() + "\">\n            <summary><span class=\"activation-status\">" + (activated ? _('Enabled') : _('Disabled')) + "</span>" + this.getTitle(index) + "</summary>\n            <div class=\"description\">" + this.getDescription(index) + "</div>\n            <p class=\"block-buttons\">\n                <button id=\"show-rulebook-" + index + "\" class=\"bgabutton bgabutton_blue\">" + _('Show rulebook') + "</button>\n                <a href=\"" + url + "\" target=\"_blank\" class=\"bgabutton bgabutton_blue\">" + _('Open rulebook in a new tab') + "</a>\n            </p>\n            <div id=\"rulebook-" + index + "\"></div>\n        </details>";
        dojo.place(html, "playermat-container-modal");
        document.getElementById("show-rulebook-" + index).addEventListener("click", function () { return _this.viewRulebook(index); });
    };
    ActivatedExpansionsPopin.prototype.createPopin = function () {
        var _this = this;
        var html = "\n        <div id=\"popin_showActivatedExpansions_container\" class=\"kingoftokyo_popin_container\">\n            <div id=\"popin_showActivatedExpansions_underlay\" class=\"kingoftokyo_popin_underlay\"></div>\n                <div id=\"popin_showActivatedExpansions_wrapper\" class=\"kingoftokyo_popin_wrapper\">\n                <div id=\"popin_showActivatedExpansions\" class=\"kingoftokyo_popin\">\n                    <a id=\"popin_showActivatedExpansions_close\" class=\"closeicon\"><i class=\"fa fa-times fa-2x\" aria-hidden=\"true\"></i></a>\n                                \n                    <h2>" + _('Active expansions') + "</h2>\n                    <div id=\"playermat-container-modal\"></div>\n                </div>\n            </div>\n        </div>";
        dojo.place(html, $(document.body));
        document.getElementById("popin_showActivatedExpansions_close").addEventListener("click", function () { return _this.closePopin(); });
        document.getElementById("popin_showActivatedExpansions_underlay").addEventListener("click", function () { return _this.closePopin(); });
        for (var i = 0; i <= EXPANSION_NUMBER; i++) {
            html += this.createBlock(i);
        }
    };
    ActivatedExpansionsPopin.prototype.closePopin = function () {
        document.getElementById('popin_showActivatedExpansions_container').remove();
    };
    return ActivatedExpansionsPopin;
}());
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var ANIMATION_MS = 1500;
var PUNCH_SOUND_DURATION = 250;
var ACTION_TIMER_DURATION = 5;
var SYMBOL_AS_STRING_PADDED = ['[Star]', null, null, null, '[Heart]', '[Energy]'];
var KingOfTokyo = /** @class */ (function () {
    function KingOfTokyo() {
        this.healthCounters = [];
        this.energyCounters = [];
        this.wickednessCounters = [];
        this.cultistCounters = [];
        this.handCounters = [];
        this.playerTables = [];
        //private rapidHealingSyncHearts: number;
        this.towerLevelsOwners = [];
        this.falseBlessingAnkhAction = null;
        this.cardLogId = 0;
    }
    /*
        setup:

        This method must set up the game user interface according to current game situation specified
        in parameters.

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)

        "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
    */
    KingOfTokyo.prototype.setup = function (gamedatas) {
        var _this = this;
        var players = Object.values(gamedatas.players);
        // ignore loading of some pictures
        [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 21].filter(function (i) { return !players.some(function (player) { return Number(player.monster) === i; }); }).forEach(function (i) {
            _this.dontPreloadImage("monster-board-" + i + ".png");
            _this.dontPreloadImage("monster-figure-" + i + ".png");
        });
        this.dontPreloadImage("tokyo-2pvariant.jpg");
        this.dontPreloadImage("background-halloween.jpg");
        this.dontPreloadImage("background-christmas.jpg");
        this.dontPreloadImage("animations-halloween.jpg");
        this.dontPreloadImage("animations-christmas.jpg");
        this.dontPreloadImage("christmas_dice.png");
        if (!gamedatas.halloweenExpansion) {
            this.dontPreloadImage("costume-cards.jpg");
            this.dontPreloadImage("orange_dice.png");
        }
        if (!gamedatas.powerUpExpansion) {
            this.dontPreloadImage("background-powerup.jpg");
            this.dontPreloadImage("animations-powerup.jpg");
            this.dontPreloadImage("powerup_dice.png");
        }
        log("Starting game setup");
        this.gamedatas = gamedatas;
        log('gamedatas', gamedatas);
        if (gamedatas.halloweenExpansion) {
            document.body.classList.add('halloween');
        }
        if (gamedatas.kingkongExpansion) {
            gamedatas.tokyoTowerLevels.forEach(function (level) { return _this.towerLevelsOwners[level] = 0; });
            players.forEach(function (player) { return player.tokyoTowerLevels.forEach(function (level) { return _this.towerLevelsOwners[level] = Number(player.id); }); });
        }
        if (gamedatas.twoPlayersVariant) {
            this.addTwoPlayerVariantNotice(gamedatas);
        }
        this.cards = new Cards(this);
        this.curseCards = new CurseCards(this);
        this.wickednessTiles = new WickednessTiles(this);
        this.evolutionCards = new EvolutionCards(this);
        this.SHINK_RAY_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Shrink ray tokens (given by ${card_name}). Reduce dice count by one per token. Use you [diceHeart] to remove them.")), { 'card_name': this.cards.getCardName(40, 'text-only') });
        this.POISON_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Poison tokens (given by ${card_name}). Make you lose one [heart] per token at the end of your turn. Use you [diceHeart] to remove them.")), { 'card_name': this.cards.getCardName(35, 'text-only') });
        this.createPlayerPanels(gamedatas);
        setTimeout(function () { var _a, _b; return new ActivatedExpansionsPopin(gamedatas, (_b = (_a = _this.players_metadata) === null || _a === void 0 ? void 0 : _a[_this.getPlayerId()]) === null || _b === void 0 ? void 0 : _b.language); }, 500);
        this.diceManager = new DiceManager(this);
        this.animationManager = new AnimationManager(this, this.diceManager);
        this.tableCenter = new TableCenter(this, players, gamedatas.visibleCards, gamedatas.topDeckCardBackType, gamedatas.wickednessTiles, gamedatas.tokyoTowerLevels, gamedatas.curseCard);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(function () { return _this.playerTables.forEach(function (playerTable) { return playerTable.initPlacement(); }); }, 200);
        this.setMimicToken('card', gamedatas.mimickedCards.card);
        this.setMimicToken('tile', gamedatas.mimickedCards.tile);
        this.setMimicEvolutionToken(gamedatas.mimickedCards.evolution);
        var playerId = this.getPlayerId();
        var currentPlayer = players.find(function (player) { return Number(player.id) === playerId; });
        if (currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.rapidHealing) {
            this.addRapidHealingButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
        }
        if (currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.mothershipSupport) {
            this.addMothershipSupportButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
        }
        if (currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.cultists) {
            this.addRapidCultistButtons(currentPlayer.health >= currentPlayer.maxHealth);
        }
        if ((currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.location) > 0) {
            this.addAutoLeaveUnderButton();
        }
        this.setupNotifications();
        this.preferencesManager = new PreferencesManager(this);
        document.getElementById('zoom-out').addEventListener('click', function () { var _a; return (_a = _this.tableManager) === null || _a === void 0 ? void 0 : _a.zoomOut(); });
        document.getElementById('zoom-in').addEventListener('click', function () { var _a; return (_a = _this.tableManager) === null || _a === void 0 ? void 0 : _a.zoomIn(); });
        if (gamedatas.kingkongExpansion) {
            var tooltip = formatTextIcons("\n            <h3>" + _("Tokyo Tower") + "</h3>\n            <p>" + _("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1] while in Tokyo.") + "</p>\n            <p>" + _("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative).") + "</p>\n            <p><strong>" + _("Claiming the top level automatically wins the game.") + "</strong></p>\n            ");
            this.addTooltipHtmlToClass('tokyo-tower-tooltip', tooltip);
        }
        if (gamedatas.cybertoothExpansion) {
            var tooltip = formatTextIcons("\n            <h3>" + _("Berserk mode") + "</h3>\n            <p>" + _("When you roll 4 or more [diceSmash], you are in Berserk mode!") + "</p>\n            <p>" + _("You play with the additional Berserk die, until you heal yourself.") + "</p>");
            this.addTooltipHtmlToClass('berserk-tooltip', tooltip);
        }
        if (gamedatas.cthulhuExpansion) {
            this.CULTIST_TOOLTIP = formatTextIcons("\n            <h3>" + _("Cultists") + "</h3>\n            <p>" + _("After resolving your dice, if you rolled four identical faces, take a Cultist tile") + "</p>\n            <p>" + _("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll.") + "</p>");
            this.addTooltipHtmlToClass('cultist-tooltip', this.CULTIST_TOOLTIP);
        }
        if (gamedatas.darkEdition) {
            document.getElementsByTagName('html')[0].dataset.darkEdition = 'true';
        }
        // override to allow icons in messages
        var oldShowMessage = this.showMessage;
        this.showMessage = function (msg, type) { return oldShowMessage(formatTextIcons(msg), type); };
        log("Ending game setup");
        /*if (window.location.host == 'studio.boardgamearena.com') {
            //this.isPowerUpExpansion() && this.evolutionCards.debugSeeAllCards();
            //this.isWickednessExpansion() && this.wickednessTiles.debugSeeAllCards();
        }*/
    };
    ///////////////////////////////////////////////////
    //// Game & client states
    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    KingOfTokyo.prototype.onEnteringState = function (stateName, args) {
        var _a;
        log('Entering state: ' + stateName, args.args);
        this.showActivePlayer(Number(args.active_player));
        var pickMonsterPhase = ['pickMonster', 'pickMonsterNextPlayer'].includes(stateName);
        var pickEvolutionForDeckPhase = ['pickEvolutionForDeck', 'nextPickEvolutionForDeck'].includes(stateName);
        if (!pickMonsterPhase) {
            this.removeMonsterChoice();
        }
        if (!pickMonsterPhase && !pickEvolutionForDeckPhase) {
            this.removeMutantEvolutionChoice();
            this.showMainTable();
        }
        if (this.isPowerUpExpansion()) {
            var evolutionCardsSingleState = this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE[stateName];
            if (evolutionCardsSingleState) {
                (_a = this.getPlayerTable(this.getPlayerId())) === null || _a === void 0 ? void 0 : _a.setEvolutionCardsSingleState(evolutionCardsSingleState, true);
            }
        }
        switch (stateName) {
            case 'pickMonster':
                dojo.addClass('kot-table', 'pickMonsterOrEvolutionDeck');
                this.onEnteringPickMonster(args.args);
                break;
            case 'pickEvolutionForDeck':
                dojo.addClass('kot-table', 'pickMonsterOrEvolutionDeck');
                this.onEnteringPickEvolutionForDeck(args.args);
                break;
            case 'chooseInitialCard':
                this.onEnteringChooseInitialCard(args.args);
                this.showEvolutionsPopinPlayerButtons();
                break;
            case 'startGame':
                this.showEvolutionsPopinPlayerButtons();
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'changeMimickedCardWickednessTile':
            case 'chooseMimickedCardWickednessTile':
                this.setDiceSelectorVisibility(false);
                this.onEnteringChooseMimickedCard(args.args);
                break;
            case 'throwDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringThrowDice(args.args);
                break;
            case 'changeDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringChangeDie(args.args, this.isCurrentPlayerActive());
                break;
            case 'prepareResolveDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPrepareResolveDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'discardDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringDiscardDie(args.args);
                break;
            case 'selectExtraDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringSelectExtraDie(args.args);
                break;
            case 'discardKeepCard':
                this.onEnteringDiscardKeepCard(args.args);
                break;
            case 'resolveDice':
                this.falseBlessingAnkhAction = null;
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollOrDiscardDie(args.args);
                this.diceManager.hideLock();
                var argsResolveDice = args.args;
                if (argsResolveDice.canLeaveHibernation) {
                    this.setGamestateDescription('Hibernation');
                }
                break;
            case 'rerollOrDiscardDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollOrDiscardDie(args.args);
                break;
            case 'resolveNumberDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveNumberDice(args.args);
                break;
            case 'takeWickednessTile':
                this.onEnteringTakeWickednessTile(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveHeartDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveHeartDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveSmashDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveSmashDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'chooseEvolutionCard':
                this.onEnteringChooseEvolutionCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'stealCostumeCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringStealCostumeCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'leaveTokyoExchangeCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'buyCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringBuyCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'sellCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringSellCard(args.args);
                break;
            case 'answerQuestion':
                this.onEnteringAnswerQuestion(args.args);
                break;
            case 'endTurn':
                this.setDiceSelectorVisibility(false);
                this.onEnteringEndTurn();
                break;
        }
    };
    KingOfTokyo.prototype.showEvolutionsPopinPlayerButtons = function () {
        if (this.isPowerUpExpansion()) {
            Object.keys(this.gamedatas.players).forEach(function (playerId) { return document.getElementById("see-monster-evolution-player-" + playerId).classList.toggle('visible', true); });
        }
    };
    KingOfTokyo.prototype.showActivePlayer = function (playerId) {
        this.playerTables.forEach(function (playerTable) { return playerTable.setActivePlayer(playerId == playerTable.playerId); });
    };
    KingOfTokyo.prototype.setGamestateDescription = function (property) {
        if (property === void 0) { property = ''; }
        var originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        if (this.gamedatas.gamestate.description !== "" + originalState['description' + property]) {
            this.gamedatas.gamestate.description = "" + originalState['description' + property];
            this.gamedatas.gamestate.descriptionmyturn = "" + originalState['descriptionmyturn' + property];
            this.updatePageTitle();
        }
    };
    KingOfTokyo.prototype.removeGamestateDescription = function () {
        this.gamedatas.gamestate.description = '';
        this.gamedatas.gamestate.descriptionmyturn = '';
        this.updatePageTitle();
    };
    KingOfTokyo.prototype.onEnteringPickMonster = function (args) {
        var _this = this;
        // TODO clean only needed
        document.getElementById('monster-pick').innerHTML = '';
        args.availableMonsters.forEach(function (monster) {
            var html = "\n            <div id=\"pick-monster-figure-" + monster + "-wrapper\">\n                <div id=\"pick-monster-figure-" + monster + "\" class=\"monster-figure monster" + monster + "\"></div>";
            if (_this.isPowerUpExpansion()) {
                html += "<div><button id=\"see-monster-evolution-" + monster + "\" class=\"bgabutton bgabutton_blue see-evolutions-button\"><div class=\"player-evolution-card\"></div>" + _('Show Evolutions') + "</button></div>";
            }
            html += "</div>";
            dojo.place(html, "monster-pick");
            document.getElementById("pick-monster-figure-" + monster).addEventListener('click', function () { return _this.pickMonster(monster); });
            if (_this.isPowerUpExpansion()) {
                document.getElementById("see-monster-evolution-" + monster).addEventListener('click', function () { return _this.showMonsterEvolutions(monster % 100); });
            }
        });
        var isCurrentPlayerActive = this.isCurrentPlayerActive();
        dojo.toggleClass('monster-pick', 'selectable', isCurrentPlayerActive);
    };
    KingOfTokyo.prototype.onEnteringPickEvolutionForDeck = function (args) {
        var _this = this;
        if (!document.getElementById('choose-evolution-in')) {
            dojo.place("\n                <div class=\"whiteblock\">\n                    <h3>" + _("Choose an Evolution in") + "</h3>\n                    <div id=\"choose-evolution-in\" class=\"evolution-card-stock player-evolution-cards\"></div>\n                </div>\n                <div class=\"whiteblock\">\n                    <h3>" + _("Evolutions in your deck") + "</h3>\n                    <div id=\"evolutions-in-deck\" class=\"evolution-card-stock player-evolution-cards\"></div>\n                </div>\n            ", 'mutant-evolution-choice');
            this.choseEvolutionInStock = new ebg.stock();
            this.choseEvolutionInStock.setSelectionAppearance('class');
            this.choseEvolutionInStock.selectionClass = 'no-visible-selection';
            this.choseEvolutionInStock.create(this, $("choose-evolution-in"), EVOLUTION_SIZE, EVOLUTION_SIZE);
            this.choseEvolutionInStock.setSelectionMode(2);
            this.choseEvolutionInStock.centerItems = true;
            this.choseEvolutionInStock.onItemCreate = function (card_div, card_type_id) { return _this.evolutionCards.setupNewCard(card_div, card_type_id); };
            dojo.connect(this.choseEvolutionInStock, 'onChangeSelection', this, function (_, item_id) { return _this.pickEvolutionForDeck(Number(item_id)); });
            this.inDeckEvolutionsStock = new ebg.stock();
            this.inDeckEvolutionsStock.setSelectionAppearance('class');
            this.inDeckEvolutionsStock.selectionClass = 'no-visible-selection';
            this.inDeckEvolutionsStock.create(this, $("evolutions-in-deck"), EVOLUTION_SIZE, EVOLUTION_SIZE);
            this.inDeckEvolutionsStock.setSelectionMode(0);
            this.inDeckEvolutionsStock.centerItems = true;
            this.inDeckEvolutionsStock.onItemCreate = function (card_div, card_type_id) { return _this.evolutionCards.setupNewCard(card_div, card_type_id); };
            this.evolutionCards.setupCards([this.choseEvolutionInStock, this.inDeckEvolutionsStock]);
        }
        this.choseEvolutionInStock.removeAll();
        args._private.chooseCardIn.forEach(function (card) { return _this.choseEvolutionInStock.addToStockWithId(card.type, '' + card.id); });
        args._private.inDeck.filter(function (card) { return !_this.inDeckEvolutionsStock.items.some(function (item) { return Number(item.id) === card.id; }); }).forEach(function (card) { return _this.inDeckEvolutionsStock.addToStockWithId(card.type, '' + card.id); });
    };
    KingOfTokyo.prototype.onEnteringChooseInitialCard = function (args) {
        var suffix = '';
        if (args.chooseEvolution) {
            suffix = args.chooseCostume ? 'evocostume' : 'evo';
        }
        this.setGamestateDescription(suffix);
        if (args.chooseCostume) {
            this.tableCenter.setInitialCards(args.cards);
            this.tableCenter.setVisibleCardsSelectionClass(args.chooseEvolution);
        }
        if (this.isCurrentPlayerActive()) {
            this.tableCenter.setVisibleCardsSelectionMode(1);
            if (args.chooseEvolution) {
                var playerTable = this.getPlayerTable(this.getPlayerId());
                playerTable.showEvolutionPickStock(args._private.evolutions);
                playerTable.setVisibleCardsSelectionClass(args.chooseCostume);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringStepEvolution = function (args) {
        console.log('onEnteringStepEvolution', args, this.isCurrentPlayerActive());
        if (this.isCurrentPlayerActive()) {
            var playerId_1 = this.getPlayerId();
            this.getPlayerTable(playerId_1).highlightHiddenEvolutions(args.highlighted.filter(function (card) { return card.location_arg === playerId_1; }));
        }
    };
    KingOfTokyo.prototype.onEnteringBeforeEndTurn = function (args) {
        var _a;
        (_a = Object.keys(args._private)) === null || _a === void 0 ? void 0 : _a.forEach(function (key) {
            var div = document.getElementById("hand-evolution-cards_item_" + key);
            if (div) {
                var counter = args._private[key];
                var symbol = SYMBOL_AS_STRING_PADDED[counter[1]];
                dojo.place(formatTextIcons("<div class=\"evolution-inner-counter\">" + counter[0] + " " + symbol + "</div>"), div);
            }
        });
    };
    KingOfTokyo.prototype.onEnteringThrowDice = function (args) {
        var _this = this;
        var _a, _b;
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? "last" : '');
        this.diceManager.showLock();
        var isCurrentPlayerActive = this.isCurrentPlayerActive();
        this.diceManager.setDiceForThrowDice(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        if (isCurrentPlayerActive) {
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', dojo.string.substitute(_("Reroll dice (${number} roll(s) remaining)"), { 'number': args.maxThrowNumber - args.throwNumber }), function () { return _this.onRethrow(); }, !args.dice.some(function (dice) { return !dice.locked; }));
                this.addTooltip('rethrow_button', _("Click on dice you want to keep to lock them, then click this button to reroll the others"), _("Ctrl+click to move all dice with same value") + "<br>\n                    " + _("Alt+click to move all dice but clicked die"));
            }
            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]') + ' (' + this.cards.getCardName(5, 'text-only') + ')', function () { return _this.rethrow3(); }, !args.rethrow3.hasDice3);
            }
            if (((_a = args.energyDrink) === null || _a === void 0 ? void 0 : _a.hasCard) && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + formatTextIcons(" ( 1[Energy])"), function () { return _this.buyEnergyDrink(); });
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }
            if (args.hasSmokeCloud && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_smoke_cloud_button', _("Get extra die Roll") + " (<span class=\"smoke-cloud token\"></span>)", function () { return _this.useSmokeCloud(); });
            }
            if (args.hasCultist && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_cultist_button', _("Get extra die Roll") + (" (" + _('Cultist') + ")"), function () { return _this.useCultist(); });
            }
            if (args.rerollDie.isBeastForm) {
                dojo.place("<div id=\"beast-form-dice-actions\"></div>", 'dice-actions');
                var simpleFaces_1 = [];
                args.dice.filter(function (die) { return die.type < 2; }).forEach(function (die) {
                    if (die.canReroll && (die.type > 0 || !simpleFaces_1.includes(die.value))) {
                        var faceText = die.type == 1 ? BERSERK_DIE_STRINGS[die.value] : DICE_STRINGS[die.value];
                        _this.createButton('beast-form-dice-actions', "rerollDie" + die.id + "_button", _("Reroll") + formatTextIcons(' ' + faceText) + ' (' + _this.cards.getCardName(301, 'text-only', 1) + ')', function () { return _this.rerollDie(die.id); }, !args.rerollDie.canUseBeastForm);
                        if (die.type == 0) {
                            simpleFaces_1.push(die.value);
                        }
                    }
                });
            }
        }
        if (args.throwNumber === args.maxThrowNumber && !args.hasSmokeCloud && !args.hasCultist && !((_b = args.energyDrink) === null || _b === void 0 ? void 0 : _b.hasCard) && (!args.rerollDie.isBeastForm || !args.rerollDie.canUseBeastForm)) {
            this.diceManager.disableDiceAction();
        }
    };
    KingOfTokyo.prototype.onEnteringChangeDie = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a, _b;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args.selectableDice, args, args.canHealWithDice, args.frozenFaces);
        }
        if (isCurrentPlayerActive && args.dice && ((_b = args.rethrow3) === null || _b === void 0 ? void 0 : _b.hasCard)) {
            if (document.getElementById('rethrow3changeDie_button')) {
                dojo.toggleClass('rethrow3changeDie_button', 'disabled', !args.rethrow3.hasDice3);
            }
            else {
                this.createButton('dice-actions', 'rethrow3changeDie_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3changeDie(); }, !args.rethrow3.hasDice3);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringPsychicProbeRollDie = function (args) {
        var _this = this;
        var _a;
        this.diceManager.setDiceForPsychicProbe(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        if (args.dice && ((_a = args.rethrow3) === null || _a === void 0 ? void 0 : _a.hasCard) && this.isCurrentPlayerActive()) {
            if (document.getElementById('rethrow3psychicProbe_button')) {
                dojo.toggleClass('rethrow3psychicProbe_button', 'disabled', !args.rethrow3.hasDice3);
            }
            else {
                this.createButton('dice-actions', 'rethrow3psychicProbe_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3psychicProbe(); }, !args.rethrow3.hasDice3);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringDiscardDie = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringSelectExtraDie = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringRerollOrDiscardDie = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces, 'rerollOrDiscard');
        }
    };
    KingOfTokyo.prototype.onEnteringRerollDice = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces, 'rerollDice');
        }
    };
    KingOfTokyo.prototype.onEnteringPrepareResolveDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.hasEncasedInIce) {
            this.setGamestateDescription('EncasedInIce');
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, isCurrentPlayerActive ? args.selectableDice : [], args.canHealWithDice, args.frozenFaces, 'freezeDie');
        }
    };
    KingOfTokyo.prototype.onEnteringDiscardKeepCard = function (args) {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_" + id + "\"]")) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringResolveNumberDice = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringTakeWickednessTile = function (args, isCurrentPlayerActive) {
        var _a;
        this.tableCenter.setWickednessTilesSelectable(args.level, true, isCurrentPlayerActive);
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringResolveHeartDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
            if (isCurrentPlayerActive) {
                dojo.place("<div id=\"heart-action-selector\" class=\"whiteblock action-selector\"></div>", 'rolled-dice-and-rapid-actions', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringResolveSmashDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
            if (isCurrentPlayerActive) {
                dojo.place("<div id=\"smash-action-selector\" class=\"whiteblock action-selector\"></div>", 'rolled-dice-and-rapid-actions', 'after');
                new SmashActionSelector(this, 'smash-action-selector', args);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringCancelDamage = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        if (args.dice) {
            this.diceManager.showCamouflageRoll(args.dice);
        }
        if (!args.canCancelDamage && args.canHealToAvoidDeath) {
            this.setGamestateDescription('HealBeforeDamage');
        }
        else if (args.canCancelDamage) {
            this.setGamestateDescription('Reduce');
        }
        if (isCurrentPlayerActive) {
            if (args.dice && ((_a = args.rethrow3) === null || _a === void 0 ? void 0 : _a.hasCard)) {
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.toggleClass('rethrow3camouflage_button', 'disabled', !args.rethrow3.hasDice3);
                }
                else {
                    this.createButton('dice-actions', 'rethrow3camouflage_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3camouflage(); }, !args.rethrow3.hasDice3);
                }
            }
            if (args.canThrowDices && !document.getElementById('throwCamouflageDice_button')) {
                this.addActionButton('throwCamouflageDice_button', _("Throw dice"), 'throwCamouflageDice');
            }
            else if (!args.canThrowDices && document.getElementById('throwCamouflageDice_button')) {
                dojo.destroy('throwCamouflageDice_button');
            }
            if (args.canUseWings && !document.getElementById('useWings_button')) {
                this.addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + " ( 2[Energy] )", { 'card_name': this.cards.getCardName(48, 'text-only') })), function () { return _this.useWings(); });
                document.getElementById('useWings_button').dataset.enableAtEnergy = '2';
                if (args.playerEnergy < 2) {
                    dojo.addClass('useWings_button', 'disabled');
                }
            }
            if (args.canUseDetachableTail && !document.getElementById('useDetachableTail_button')) {
                this.addActionButton('useDetachableTail_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(51, 'text-only') }), function () { return _this.useInvincibleEvolution(51); });
            }
            if (args.canUseRabbitsFoot && !document.getElementById('useRabbitsFoot_button')) {
                this.addActionButton('useRabbitsFoot_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(143, 'text-only') }), function () { return _this.useInvincibleEvolution(143); });
            }
            if (args.canUseCandy && !document.getElementById('useCandy_button')) {
                this.addActionButton('useCandy_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(88, 'text-only') }), function () { return _this.useCandyEvolution(); });
            }
            if (args.countSuperJump > 0 && !document.getElementById('useSuperJump1_button')) {
                Object.keys(args.replaceHeartByEnergyCost).filter(function (energy) { return Number(energy) <= args.countSuperJump; }).forEach(function (energy) {
                    var energyCost = Number(energy);
                    var remainingDamage = args.replaceHeartByEnergyCost[energy];
                    var id = "useSuperJump" + energyCost + "_button";
                    if (!document.getElementById(id)) {
                        _this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': _this.cards.getCardName(53, 'text-only') }) + (remainingDamage > 0 ? " (-" + remainingDamage + "[Heart])" : '')), function () { return _this.useSuperJump(energyCost); });
                        document.getElementById(id).dataset.enableAtEnergy = '' + energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }
            if (args.canUseRobot && !document.getElementById('useRobot1_button')) {
                Object.keys(args.replaceHeartByEnergyCost).forEach(function (energy) {
                    var energyCost = Number(energy);
                    var remainingDamage = args.replaceHeartByEnergyCost[energy];
                    var id = "useRobot" + energyCost + "_button";
                    if (!document.getElementById(id)) {
                        _this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': _this.cards.getCardName(210, 'text-only') }) + (remainingDamage > 0 ? " (-" + remainingDamage + "[Heart])" : '')), function () { return _this.useRobot(energyCost); });
                        document.getElementById(id).dataset.enableAtEnergy = '' + energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }
            if (!args.canThrowDices && !document.getElementById('skipWings_button')) {
                var canAvoidDeath_1 = args.canDoAction && args.skipMeansDeath && (args.canCancelDamage || args.canHealToAvoidDeath);
                this.addActionButton('skipWings_button', args.canUseWings ? dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cards.getCardName(48, 'text-only') }) : _("Skip"), function () {
                    if (canAvoidDeath_1) {
                        _this.confirmationDialog(formatTextIcons(_("Are you sure you want to Skip? It means [Skull]")), function () { return _this.skipWings(); });
                    }
                    else {
                        _this.skipWings();
                    }
                }, null, null, canAvoidDeath_1 ? 'red' : undefined);
                if (!args.canDoAction) {
                    this.startActionTimer('skipWings_button', ACTION_TIMER_DURATION);
                }
            }
            var rapidHealingSyncButtons = document.querySelectorAll("[id^='rapidHealingSync_button'");
            rapidHealingSyncButtons.forEach(function (rapidHealingSyncButton) { return rapidHealingSyncButton.parentElement.removeChild(rapidHealingSyncButton); });
            if (args.canHeal && args.damageToCancelToSurvive > 0) {
                var _loop_3 = function (i) {
                    var cultistCount = i;
                    var rapidHealingCount = args.rapidHealingHearts > 0 ? args.canHeal - cultistCount : 0;
                    var cardsNames = [];
                    if (cultistCount > 0) {
                        cardsNames.push(_('Cultist'));
                    }
                    if (rapidHealingCount > 0) {
                        cardsNames.push(_(this_2.cards.getCardName(37, 'text-only')));
                    }
                    if (cultistCount + rapidHealingCount >= args.damageToCancelToSurvive && 2 * rapidHealingCount <= args.playerEnergy) {
                        var text = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons("" + _('Gain ${hearts}[Heart]') + (rapidHealingCount > 0 ? " (" + 2 * rapidHealingCount + "[Energy])" : '')), { 'card_name': cardsNames.join(', '), 'hearts': cultistCount + rapidHealingCount });
                        this_2.addActionButton("rapidHealingSync_button_" + i, text, function () { return _this.useRapidHealingSync(cultistCount, rapidHealingCount); });
                    }
                };
                var this_2 = this;
                //this.rapidHealingSyncHearts = args.rapidHealingHearts;
                for (var i = Math.min(args.rapidHealingCultists, args.canHeal); i >= 0; i--) {
                    _loop_3(i);
                }
            }
        }
    };
    KingOfTokyo.prototype.onEnteringChooseEvolutionCard = function (args, isCurrentPlayerActive) {
        if (isCurrentPlayerActive) {
            this.getPlayerTable(this.getPlayerId()).showEvolutionPickStock(args._private.evolutions);
        }
    };
    KingOfTokyo.prototype.onEnteringStealCostumeCard = function (args, isCurrentPlayerActive) {
        var _this = this;
        if (isCurrentPlayerActive) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId != _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            this.setBuyDisabledCard(args);
        }
    };
    KingOfTokyo.prototype.onEnteringExchangeCard = function (args, isCurrentPlayerActive) {
        var _this = this;
        if (isCurrentPlayerActive) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId != _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_" + id + "\"]")) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringBuyCard = function (args, isCurrentPlayerActive) {
        var _a, _b;
        if (isCurrentPlayerActive) {
            var stateName = this.getStateName();
            var bamboozle = stateName === 'answerQuestion' && this.gamedatas.gamestate.args.question.code === 'Bamboozle';
            var playerId_2 = this.getPlayerId();
            if (bamboozle) {
                playerId_2 = this.gamedatas.gamestate.args.question.args.cardBeingBought.playerId;
            }
            this.tableCenter.setVisibleCardsSelectionMode(1);
            if (this.isPowerUpExpansion()) {
                this.getPlayerTable(playerId_2).reservedCards.setSelectionMode(1);
            }
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(args.canBuyFromPlayers && playerTable.playerId != playerId_2 ? 1 : 0); });
            if ((_b = (_a = args._private) === null || _a === void 0 ? void 0 : _a.pickCards) === null || _b === void 0 ? void 0 : _b.length) {
                this.tableCenter.showPickStock(args._private.pickCards);
            }
            this.setBuyDisabledCard(args);
        }
    };
    KingOfTokyo.prototype.onEnteringChooseMimickedCard = function (args) {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            this.setBuyDisabledCard(args);
        }
    };
    KingOfTokyo.prototype.onEnteringSellCard = function (args) {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_" + id + "\"]")) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringAnswerQuestion = function (args) {
        var _this = this;
        var question = args.question;
        this.gamedatas.gamestate.description = question.description;
        this.gamedatas.gamestate.descriptionmyturn = question.descriptionmyturn;
        this.updatePageTitle();
        switch (question.code) {
            case 'ChooseMimickedCard':
                this.onEnteringChooseMimickedCard(question.args.mimicArgs);
                break;
            case 'Bamboozle':
                var bamboozleArgs = question.args;
                this.onEnteringBuyCard(bamboozleArgs.buyCardArgs, this.isCurrentPlayerActive());
                break;
            case 'GazeOfTheSphinxSnake':
                if (this.isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode(1);
                }
                break;
            case 'IcyReflection':
                if (this.isCurrentPlayerActive()) {
                    var icyReflectionArgs = question.args;
                    this.playerTables.forEach(function (playerTable) { return playerTable.visibleEvolutionCards.setSelectionMode(1); });
                    icyReflectionArgs.disabledEvolutions.forEach(function (evolution) {
                        var cardDiv = document.querySelector("div[id$=\"_item_" + evolution.id + "\"]");
                        if (cardDiv && cardDiv.closest('.player-evolution-cards') !== null) {
                            dojo.addClass(cardDiv, 'disabled');
                        }
                    });
                }
                break;
            case 'MiraculousCatch':
                var miraculousCatchArgs = question.args;
                var miraculousCatchCard = this.cards.generateCardDiv(miraculousCatchArgs.card);
                miraculousCatchCard.id = "miraculousCatch-card-" + miraculousCatchArgs.card.id;
                dojo.place("<div id=\"card-MiraculousCatch-wrapper\" class=\"card-in-title-wrapper\">" + miraculousCatchCard.outerHTML + "</div>", "maintitlebar_content");
                break;
            case 'DeepDive':
                var deepDiveCatchArgs = question.args;
                dojo.place("<div id=\"card-DeepDive-wrapper\" class=\"card-in-title-wrapper\">" + deepDiveCatchArgs.cards.map(function (card) {
                    var cardDiv = _this.cards.generateCardDiv(card);
                    cardDiv.id = "deepDive-card-" + card.id;
                    return cardDiv.outerHTML;
                }).join('') + "</div>", "maintitlebar_content");
                break;
            case 'MyToy':
                this.tableCenter.setVisibleCardsSelectionMode(1);
                break;
            case 'SuperiorAlienTechnology':
                var superiorAlienTechnologyArgs = question.args;
                this.setTitleBarSuperiorAlienTechnologyCard(superiorAlienTechnologyArgs.card);
                this.setDiceSelectorVisibility(false);
                break;
            case 'FreezeRayChooseOpponent':
                var argsFreezeRayChooseOpponent = question.args;
                argsFreezeRayChooseOpponent.smashedPlayersIds.forEach(function (playerId) {
                    var player = _this.gamedatas.players[playerId];
                    var label = "<div class=\"monster-icon monster" + player.monster + "\" style=\"background-color: " + (player.monster > 100 ? 'unset' : '#' + player.color) + ";\"></div> " + player.name;
                    _this.addActionButton("freezeRayChooseOpponent_button_" + playerId, label, function () { return _this.freezeRayChooseOpponent(playerId); });
                });
                break;
        }
    };
    KingOfTokyo.prototype.onEnteringEndTurn = function () {
    };
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        var _a;
        log('Leaving state: ' + stateName);
        if (this.isPowerUpExpansion()) {
            var evolutionCardsSingleState = this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE[stateName];
            if (evolutionCardsSingleState) {
                (_a = this.getPlayerTable(this.getPlayerId())) === null || _a === void 0 ? void 0 : _a.setEvolutionCardsSingleState(evolutionCardsSingleState, false);
            }
        }
        switch (stateName) {
            case 'chooseInitialCard':
                this.tableCenter.setVisibleCardsSelectionMode(0);
                this.tableCenter.setVisibleCardsSelectionClass(false);
                this.playerTables.forEach(function (playerTable) {
                    playerTable.hideEvolutionPickStock();
                    playerTable.setVisibleCardsSelectionClass(false);
                });
                break;
            case 'beforeStartTurn':
            case 'beforeResolveDice':
            case 'beforeEnteringTokyo':
            case 'afterEnteringTokyo':
            case 'cardIsBought':
                this.onLeavingStepEvolution();
                break;
            case 'beforeEndTurn':
                this.onLeavingStepEvolution();
                this.onLeavingBeforeEndTurn();
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'opportunistChooseMimicCard':
            case 'chooseMimickedCardWickednessTile':
            case 'changeMimickedCardWickednessTile':
                this.onLeavingChooseMimickedCard();
                break;
            case 'throwDice':
                document.getElementById('dice-actions').innerHTML = '';
                break;
            case 'changeActivePlayerDie':
            case 'psychicProbeRollDie': // TODO remove
                if (document.getElementById('rethrow3psychicProbe_button')) {
                    dojo.destroy('rethrow3psychicProbe_button');
                }
                break;
            case 'changeDie':
                if (document.getElementById('rethrow3changeDie_button')) {
                    dojo.destroy('rethrow3changeDie_button');
                }
                this.diceManager.removeAllBubbles();
                break;
            case 'discardKeepCard':
                this.onLeavingSellCard();
                break;
            case 'rerollDice':
                this.diceManager.removeSelection();
                break;
            case 'takeWickednessTile':
                this.onLeavingTakeWickednessTile();
                break;
            case 'resolveHeartDiceAction':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDiceAction':
                if (document.getElementById('smash-action-selector')) {
                    dojo.destroy('smash-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;
            case 'chooseEvolutionCard':
                this.playerTables.forEach(function (playerTable) { return playerTable.hideEvolutionPickStock(); });
                break;
            case 'leaveTokyo':
                this.removeSkipBuyPhaseToggle();
                break;
            case 'leaveTokyoExchangeCard':
            case 'stealCostumeCard':
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
                break;
            case 'cardIsBought':
                this.onLeavingStepEvolution();
                break;
            case 'sellCard':
                this.onLeavingSellCard();
                break;
            case 'cancelDamage':
                this.diceManager.removeAllDice();
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.destroy('rethrow3camouflage_button');
                }
                break;
            case 'answerQuestion':
                this.onLeavingAnswerQuestion();
                if (this.gamedatas.gamestate.args.question.code === 'Bamboozle') {
                    this.onLeavingBuyCard();
                }
                break;
            case 'MyToy':
                this.tableCenter.setVisibleCardsSelectionMode(0);
                break;
        }
    };
    KingOfTokyo.prototype.onLeavingStepEvolution = function () {
        var _a;
        var playerId = this.getPlayerId();
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.unhighlightHiddenEvolutions();
    };
    KingOfTokyo.prototype.onLeavingBeforeEndTurn = function () {
        Array.from(document.querySelectorAll(".evolution-inner-counter")).forEach(function (elem) {
            var _a;
            (_a = elem === null || elem === void 0 ? void 0 : elem.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(elem);
        });
    };
    KingOfTokyo.prototype.onLeavingTakeWickednessTile = function () {
        this.tableCenter.setWickednessTilesSelectable(null, false, false);
    };
    KingOfTokyo.prototype.onLeavingBuyCard = function () {
        this.tableCenter.setVisibleCardsSelectionMode(0);
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(0); });
        this.tableCenter.hidePickStock();
    };
    KingOfTokyo.prototype.onLeavingChooseMimickedCard = function () {
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(0); });
    };
    KingOfTokyo.prototype.onLeavingSellCard = function () {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(0); });
            dojo.query('.stockitem').removeClass('disabled');
        }
    };
    KingOfTokyo.prototype.onLeavingAnswerQuestion = function () {
        var _a, _b, _c;
        var question = this.gamedatas.gamestate.args.question;
        switch (question.code) {
            case 'Bamboozle':
                this.onLeavingBuyCard();
                break;
            case 'GazeOfTheSphinxSnake':
                if (this.isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode(0);
                }
                break;
            case 'IcyReflection':
                if (this.isCurrentPlayerActive()) {
                    this.playerTables.forEach(function (playerTable) { return playerTable.visibleEvolutionCards.setSelectionMode(0); });
                    dojo.query('.stockitem').removeClass('disabled');
                }
                break;
            case 'MiraculousCatch':
                var miraculousCatchCard = document.getElementById("card-MiraculousCatch-wrapper");
                (_a = miraculousCatchCard === null || miraculousCatchCard === void 0 ? void 0 : miraculousCatchCard.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(miraculousCatchCard);
                break;
            case 'DeepDive':
                var cards = document.getElementById("card-DeepDive-wrapper");
                (_b = cards === null || cards === void 0 ? void 0 : cards.parentElement) === null || _b === void 0 ? void 0 : _b.removeChild(cards);
                break;
            case 'SuperiorAlienTechnology':
                var superiorAlienTechnologyCard = document.getElementById("card-SuperiorAlienTechnology-wrapper");
                while (superiorAlienTechnologyCard) {
                    (_c = superiorAlienTechnologyCard === null || superiorAlienTechnologyCard === void 0 ? void 0 : superiorAlienTechnologyCard.parentElement) === null || _c === void 0 ? void 0 : _c.removeChild(superiorAlienTechnologyCard);
                    superiorAlienTechnologyCard = document.getElementById("card-SuperiorAlienTechnology-wrapper");
                }
                break;
        }
    };
    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    KingOfTokyo.prototype.onUpdateActionButtons = function (stateName, args) {
        var _this = this;
        var _a, _b;
        switch (stateName) {
            case 'beforeStartTurn':
            case 'beforeResolveDice':
            case 'beforeEnteringTokyo':
            case 'afterEnteringTokyo':
            case 'cardIsBought':
                this.onEnteringStepEvolution(args); // because it's multiplayer, enter action must be set here
                break;
            case 'beforeEndTurn':
                this.onEnteringStepEvolution(args); // because it's multiplayer, enter action must be set here
                this.onEnteringBeforeEndTurn(args);
                break;
            case 'changeActivePlayerDie':
            case 'psychicProbeRollDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args); // because it's multiplayer, enter action must be set here
                break;
            case 'rerollDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollDice(args);
                break;
            case 'cheerleaderSupport':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args); // because it's multiplayer, enter action must be set here
                break;
            case 'leaveTokyo':
                this.setDiceSelectorVisibility(false);
                var argsLeaveTokyo = args;
                if (argsLeaveTokyo._private) {
                    this.addSkipBuyPhaseToggle(argsLeaveTokyo._private.skipBuyPhase);
                }
                break;
            case 'opportunistBuyCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'opportunistChooseMimicCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'cancelDamage':
                var argsCancelDamage = args;
                this.setDiceSelectorVisibility(argsCancelDamage.canThrowDices || !!argsCancelDamage.dice);
                this.onEnteringCancelDamage(argsCancelDamage, this.isCurrentPlayerActive());
                // TODOBUG
                if (argsCancelDamage.canCancelDamage === undefined) {
                    try {
                        var tableId_1 = window.location.search.split('=')[1];
                        if (tableId_1 === '277711940' || tableId_1 === '277304366') {
                            this.addActionButton('debugBlockedTable_button', "Skip error message", function () { return _this.takeAction('debugBlockedTable', { tableId: tableId_1 }); });
                        }
                    }
                    catch (e) {
                        console.error(e);
                    }
                }
                break;
        }
        if (this.isCurrentPlayerActive()) {
            switch (stateName) {
                case 'chooseInitialCard':
                    if (this.isInitialCardDoubleSelection()) {
                        this.addActionButton('confirmInitialCards_button', _("Confirm"), function () {
                            var _a, _b;
                            return _this.chooseInitialCard(Number((_a = _this.tableCenter.getVisibleCards().getSelectedItems()[0]) === null || _a === void 0 ? void 0 : _a.id), Number((_b = _this.getPlayerTable(_this.getPlayerId()).pickEvolutionCards.getSelectedItems()[0]) === null || _b === void 0 ? void 0 : _b.id));
                        });
                        document.getElementById("confirmInitialCards_button").classList.add('disabled');
                    }
                    break;
                case 'beforeStartTurn':
                    this.addActionButton('skipBeforeStartTurn_button', _("Skip"), function () { return _this.skipBeforeStartTurn(); });
                    break;
                case 'beforeEndTurn':
                    this.addActionButton('skipBeforeEndTurn_button', _("Skip"), function () { return _this.skipBeforeEndTurn(); });
                    break;
                case 'changeMimickedCardWickednessTile':
                    this.addActionButton('skipChangeMimickedCardWickednessTile_button', _("Skip"), function () { return _this.skipChangeMimickedCardWickednessTile(); });
                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCardWickednessTile_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeMimickedCard':
                    this.addActionButton('skipChangeMimickedCard_button', _("Skip"), function () { return _this.skipChangeMimickedCard(); });
                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCard_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'giveSymbolToActivePlayer':
                    var argsGiveSymbolToActivePlayer_1 = args;
                    var SYMBOL_AS_STRING_1 = ['[Heart]', '[Energy]', '[Star]'];
                    [4, 5, 0].forEach(function (symbol, symbolIndex) {
                        _this.addActionButton("giveSymbolToActivePlayer_button" + symbol, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_1[symbolIndex] })), function () { return _this.giveSymbolToActivePlayer(symbol); });
                        if (!argsGiveSymbolToActivePlayer_1.canGive[symbol]) {
                            dojo.addClass("giveSymbolToActivePlayer_button" + symbol, 'disabled');
                        }
                    });
                    document.getElementById("giveSymbolToActivePlayer_button5").dataset.enableAtEnergy = '1';
                    break;
                case 'throwDice':
                    this.addActionButton('goToChangeDie_button', _("Resolve dice"), function () { return _this.goToChangeDie(); }, null, null, 'red');
                    var argsThrowDice = args;
                    if (!argsThrowDice.hasActions) {
                        this.startActionTimer('goToChangeDie_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeDie':
                    var argsChangeDie = args;
                    if (argsChangeDie.hasYinYang) {
                        this.addActionButton('useYinYang_button', dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCards.getCardName(138, 'text-only') }), function () { return _this.useYinYang(); });
                    }
                    this.addActionButton('resolve_button', _("Resolve dice"), function () { return _this.resolveDice(); }, null, null, 'red');
                    break;
                case 'changeActivePlayerDie':
                case 'psychicProbeRollDie':
                    this.addActionButton('changeActivePlayerDieSkip_button', _("Skip"), function () { return _this.changeActivePlayerDieSkip(); });
                    break;
                case 'cheerleaderSupport':
                    this.addActionButton('support_button', formatTextIcons(_("Support (add [diceSmash] )")), function () { return _this.support(); });
                    this.addActionButton('dontSupport_button', _("Don't support"), function () { return _this.dontSupport(); });
                    break;
                case 'giveGoldenScarab':
                    var argsGiveGoldenScarab = args;
                    argsGiveGoldenScarab.playersIds.forEach(function (playerId) {
                        var player = _this.gamedatas.players[playerId];
                        var label = "<div class=\"monster-icon monster" + player.monster + "\" style=\"background-color: " + (player.monster > 100 ? 'unset' : '#' + player.color) + ";\"></div> " + player.name;
                        _this.addActionButton("giveGoldenScarab_button_" + playerId, label, function () { return _this.giveGoldenScarab(playerId); });
                    });
                    break;
                case 'giveSymbols':
                    var argsGiveSymbols = args;
                    argsGiveSymbols.combinations.forEach(function (combination, combinationIndex) {
                        var symbols = SYMBOL_AS_STRING_PADDED[combination[0]] + (combination.length > 1 ? SYMBOL_AS_STRING_PADDED[combination[1]] : '');
                        _this.addActionButton("giveSymbols_button" + combinationIndex, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: symbols })), function () { return _this.giveSymbols(combination); });
                    });
                    break;
                case 'selectExtraDie':
                    var _loop_4 = function (face) {
                        this_3.addActionButton("selectExtraDie_button" + face, formatTextIcons(DICE_STRINGS[face]), function () { return _this.selectExtraDie(face); });
                    };
                    var this_3 = this;
                    for (var face = 1; face <= 6; face++) {
                        _loop_4(face);
                    }
                    break;
                case 'rerollOrDiscardDie':
                    this.addActionButton('falseBlessingReroll_button', _("Reroll"), function () {
                        dojo.addClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        _this.falseBlessingAnkhAction = 'falseBlessingReroll';
                    }, null, null, 'gray');
                    this.addActionButton('falseBlessingDiscard_button', _("Discard"), function () {
                        dojo.addClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        _this.falseBlessingAnkhAction = 'falseBlessingDiscard';
                    }, null, null, 'gray');
                    this.addActionButton('falseBlessingSkip_button', _("Skip"), function () { return _this.falseBlessingSkip(); });
                    break;
                case 'rerollDice':
                    var argsRerollDice = args;
                    this.addActionButton('rerollDice_button', _("Reroll selected dice"), function () { return _this.rerollDice(_this.diceManager.getSelectedDiceIds()); });
                    dojo.addClass('rerollDice_button', 'disabled');
                    if (argsRerollDice.min === 0) {
                        this.addActionButton('skipRerollDice_button', _("Skip"), function () { return _this.rerollDice([]); });
                    }
                    break;
                case 'resolveDice':
                    var argsResolveDice = args;
                    if (argsResolveDice.canLeaveHibernation) {
                        this.addActionButton('stayInHibernation_button', /*_TODODE*/ ("Stay in Hibernation"), function () { return _this.stayInHibernation(); });
                        this.addActionButton('leaveHibernation_button', /*_TODODE*/ ("Leave Hibernation"), function () { return _this.leaveHibernation(); }, null, null, 'red');
                    }
                    break;
                case 'prepareResolveDice':
                    var argsPrepareResolveDice = args;
                    if (argsPrepareResolveDice.hasEncasedInIce) {
                        this.addActionButton('skipFreezeDie_button', _("Skip"), function () { return _this.skipFreezeDie(); });
                    }
                    break;
                case 'beforeResolveDice':
                    this.addActionButton('skipBeforeResolveDice_button', _("Skip"), function () { return _this.skipBeforeResolveDice(); });
                    break;
                case 'takeWickednessTile':
                    this.addActionButton('skipTakeWickednessTile_button', _("Skip"), function () { return _this.skipTakeWickednessTile(); });
                    var argsTakeWickednessTile = args;
                    if (!argsTakeWickednessTile.canTake) {
                        this.startActionTimer('skipTakeWickednessTile_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'leaveTokyo':
                    var label = _("Stay in Tokyo");
                    var argsLeaveTokyo = args;
                    if (argsLeaveTokyo.canUseChestThumping && argsLeaveTokyo.activePlayerId == this.getPlayerId()) {
                        if (!this.smashedPlayersStillInTokyo) {
                            this.smashedPlayersStillInTokyo = argsLeaveTokyo.smashedPlayersInTokyo;
                        }
                        this.smashedPlayersStillInTokyo.forEach(function (playerId) {
                            var player = _this.gamedatas.players[playerId];
                            _this.addActionButton("useChestThumping_button" + playerId, dojo.string.substitute(_("Force ${player_name} to Yield Tokyo"), { 'player_name': "<span style=\"color: #" + player.color + "\">" + player.name + "</span>" }), function () { return _this.useChestThumping(playerId); });
                        });
                        if (this.smashedPlayersStillInTokyo.length) {
                            this.addActionButton('skipChestThumping_button', dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(45, 'text-only') }), function () { return _this.skipChestThumping(); });
                        }
                    }
                    else {
                        var playerHasJets_1 = (_a = argsLeaveTokyo.jetsPlayers) === null || _a === void 0 ? void 0 : _a.includes(this.getPlayerId());
                        var playerHasSimianScamper = (_b = argsLeaveTokyo.simianScamperPlayers) === null || _b === void 0 ? void 0 : _b.includes(this.getPlayerId());
                        if (playerHasJets_1 || playerHasSimianScamper) {
                            label += formatTextIcons(" (- " + argsLeaveTokyo.jetsDamage + " [heart])");
                        }
                        this.addActionButton('stayInTokyo_button', label, function () { return _this.onStayInTokyo(); });
                        this.addActionButton('leaveTokyo_button', _("Leave Tokyo"), function () { return _this.onLeaveTokyo(playerHasJets_1 ? 24 : undefined); });
                        if (playerHasSimianScamper) {
                            this.addActionButton('leaveTokyoSimianScamper_button', _("Leave Tokyo") + ' : ' + dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCards.getCardName(42, 'text-only') }), function () { return _this.onLeaveTokyo(3042); });
                        }
                        if (!argsLeaveTokyo.canYieldTokyo[this.getPlayerId()]) {
                            this.startActionTimer('stayInTokyo_button', ACTION_TIMER_DURATION);
                            dojo.addClass('leaveTokyo_button', 'disabled');
                        }
                    }
                    break;
                case 'stealCostumeCard':
                    var argsStealCostumeCard = args;
                    this.addActionButton('endStealCostume_button', _("Skip"), 'endStealCostume', null, null, 'red');
                    if (!argsStealCostumeCard.canBuyFromPlayers) {
                        this.startActionTimer('endStealCostume_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeForm':
                    var argsChangeForm = args;
                    this.addActionButton('changeForm_button', dojo.string.substitute(_("Change to ${otherForm}"), { 'otherForm': _(argsChangeForm.otherForm) }) + formatTextIcons(" ( 1 [Energy])"), function () { return _this.changeForm(); });
                    this.addActionButton('skipChangeForm_button', _("Don't change form"), function () { return _this.skipChangeForm(); });
                    dojo.toggleClass('changeForm_button', 'disabled', !argsChangeForm.canChangeForm);
                    document.getElementById("changeForm_button").dataset.enableAtEnergy = '1';
                    break;
                case 'leaveTokyoExchangeCard':
                    var argsExchangeCard = args;
                    this.addActionButton('skipExchangeCard_button', _("Skip"), function () { return _this.skipExchangeCard(); });
                    if (!argsExchangeCard.canExchange) {
                        this.startActionTimer('skipExchangeCard_button', ACTION_TIMER_DURATION);
                    }
                    this.onEnteringExchangeCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'beforeEnteringTokyo':
                    var argsBeforeEnteringTokyo = args;
                    if (argsBeforeEnteringTokyo.canUseFelineMotor.includes(this.getPlayerId())) {
                        this.addActionButton('useFelineMotor_button', dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCards.getCardName(36, 'text-only') }), function () { return _this.useFelineMotor(); });
                    }
                    this.addActionButton('skipBeforeEnteringTokyo_button', _("Skip"), function () { return _this.skipBeforeEnteringTokyo(); });
                    break;
                case 'afterEnteringTokyo':
                    this.addActionButton('skipAfterEnteringTokyo_button', _("Skip"), function () { return _this.skipAfterEnteringTokyo(); });
                    break;
                case 'buyCard':
                    var argsBuyCard = args;
                    if (argsBuyCard.canUseMiraculousCatch) {
                        this.addActionButton('useMiraculousCatch_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(12, 'text-only') }), function () { return _this.useMiraculousCatch(); });
                        if (!argsBuyCard.unusedMiraculousCatch) {
                            dojo.addClass('useMiraculousCatch_button', 'disabled');
                        }
                    }
                    if (argsBuyCard.canUseAdaptingTechnology) {
                        this.addActionButton('renewAdaptiveTechnology_button', _("Renew cards") + ' (' + dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(24, 'text-only') }) + ')', function () { return _this.onRenew(3024); });
                    }
                    this.addActionButton('renew_button', _("Renew cards") + formatTextIcons(" ( 2 [Energy])"), function () { return _this.onRenew(4); });
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    if (argsBuyCard.canSell) {
                        this.addActionButton('goToSellCard_button', _("End turn and sell cards"), 'goToSellCard');
                    }
                    this.addActionButton('endTurn_button', argsBuyCard.canSell ? _("End turn without selling") : _("End turn"), 'onEndTurn', null, null, 'red');
                    if (!argsBuyCard.canBuyOrNenew && !argsBuyCard.canSell) {
                        this.startActionTimer('endTurn_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'opportunistBuyCard':
                    this.addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');
                    if (!args.canBuy) {
                        this.startActionTimer('opportunistSkip_button', ACTION_TIMER_DURATION);
                    }
                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                    break;
                case 'cardIsBought':
                    this.addActionButton('skipCardIsBought_button', _("Skip"), function () { return _this.skipCardIsBought(); });
                    break;
                case 'sellCard':
                    this.addActionButton('endTurnSellCard_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;
                case 'answerQuestion':
                    this.onUpdateActionButtonsAnswerQuestion(args);
            }
        }
    };
    KingOfTokyo.prototype.onUpdateActionButtonsAnswerQuestion = function (args) {
        var _this = this;
        var question = args.question;
        switch (question.code) {
            case 'BambooSupply':
                var substituteParams = { card_name: this.evolutionCards.getCardName(136, 'text-only') };
                var putLabel = dojo.string.substitute(_("Put ${number}[Energy] on ${card_name}"), __assign(__assign({}, substituteParams), { number: 1 }));
                var takeLabel = dojo.string.substitute(_("Take all [Energy] from ${card_name}"), substituteParams);
                this.addActionButton('putEnergyOnBambooSupply_button', formatTextIcons(putLabel), function () { return _this.putEnergyOnBambooSupply(); });
                this.addActionButton('takeEnergyOnBambooSupply_button', formatTextIcons(takeLabel), function () { return _this.takeEnergyOnBambooSupply(); });
                var bambooSupplyQuestionArgs = question.args;
                if (!bambooSupplyQuestionArgs.canTake) {
                    dojo.addClass('takeEnergyOnBambooSupply_button', 'disabled');
                }
                break;
            case 'GazeOfTheSphinxAnkh':
                this.addActionButton('gazeOfTheSphinxDrawEvolution_button', _("Draw Evolution"), function () { return _this.gazeOfTheSphinxDrawEvolution(); });
                this.addActionButton('gazeOfTheSphinxGainEnergy_button', formatTextIcons("" + dojo.string.substitute(_('Gain ${energy}[Energy]'), { energy: 3 })), function () { return _this.gazeOfTheSphinxGainEnergy(); });
                break;
            case 'GazeOfTheSphinxSnake':
                this.addActionButton('gazeOfTheSphinxLoseEnergy_button', formatTextIcons("" + dojo.string.substitute(_('Lose ${energy}[Energy]'), { energy: 3 })), function () { return _this.gazeOfTheSphinxLoseEnergy(); });
                var gazeOfTheSphinxLoseEnergyQuestionArgs = question.args;
                if (!gazeOfTheSphinxLoseEnergyQuestionArgs.canLoseEnergy) {
                    dojo.addClass('gazeOfTheSphinxLoseEnergy_button', 'disabled');
                }
                break;
            case 'MegaPurr': // TODOPU TOTOMP TODOPUHA remove MegaPurr here and over
                var playerId_3 = this.getPlayerId();
                var SYMBOL_AS_STRING_2 = ['[Energy]', '[Star]'];
                [5, 0].forEach(function (symbol, symbolIndex) {
                    _this.addActionButton("giveSymbol_button" + symbol, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_2[symbolIndex] })), function () { return _this.giveSymbol(symbol); });
                    if (!question.args["canGive" + symbol].includes(playerId_3)) {
                        dojo.addClass("giveSymbol_button" + symbol, 'disabled');
                    }
                });
                break;
            case 'GiveSymbol':
                var giveSymbolPlayerId_1 = this.getPlayerId();
                var giveSymbolQuestionArgs = question.args;
                giveSymbolQuestionArgs.symbols.forEach(function (symbol) {
                    _this.addActionButton("giveSymbol_button" + symbol, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_PADDED[symbol] })), function () { return _this.giveSymbol(symbol); });
                    if (!question.args["canGive" + symbol].includes(giveSymbolPlayerId_1)) {
                        dojo.addClass("giveSymbol_button" + symbol, 'disabled');
                    }
                });
                break;
            case 'TrickOrThreat':
                var trickOrThreatPlayerId = this.getPlayerId();
                var trickOrThreatQuestionArgs = question.args;
                this.addActionButton("giveSymbol_button5", formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_PADDED[5] })), function () { return _this.giveSymbol(5); });
                if (!trickOrThreatQuestionArgs.canGiveEnergy.includes(trickOrThreatPlayerId)) {
                    dojo.addClass("giveSymbol_button5", 'disabled');
                }
                this.addActionButton("trickOrThreatLoseHearts", formatTextIcons(dojo.string.substitute(_("Lose ${symbol}"), { symbol: '2[Heart]' })), function () { return _this.trickOrThreatLoseHearts(); });
                break;
            case 'FreezeRay':
                var _loop_5 = function (face) {
                    this_4.addActionButton("selectFrozenDieFace_button" + face, formatTextIcons(DICE_STRINGS[face]), function () { return _this.chooseFreezeRayDieFace(face); });
                };
                var this_4 = this;
                for (var face = 1; face <= 6; face++) {
                    _loop_5(face);
                }
                break;
            case 'MiraculousCatch':
                var miraculousCatchArgs_1 = question.args;
                this.addActionButton('buyCardMiraculousCatch_button', formatTextIcons(dojo.string.substitute(_('Buy ${card_name} for ${cost}[Energy]'), { card_name: this.cards.getCardName(miraculousCatchArgs_1.card.type, 'text-only'), cost: miraculousCatchArgs_1.cost })), function () { return _this.buyCardMiraculousCatch(false); });
                if (miraculousCatchArgs_1.costSuperiorAlienTechnology !== null && miraculousCatchArgs_1.costSuperiorAlienTechnology !== miraculousCatchArgs_1.cost) {
                    this.addActionButton('buyCardMiraculousCatchUseSuperiorAlienTechnology_button', formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCards.getCardName(28, 'text-only'), cost: miraculousCatchArgs_1.costSuperiorAlienTechnology })), function () { return _this.buyCardMiraculousCatch(true); });
                }
                this.addActionButton('skipMiraculousCatch_button', formatTextIcons(dojo.string.substitute(_('Discard ${card_name}'), { card_name: this.cards.getCardName(miraculousCatchArgs_1.card.type, 'text-only') })), function () { return _this.skipMiraculousCatch(); });
                setTimeout(function () { var _a; return (_a = document.getElementById("miraculousCatch-card-" + miraculousCatchArgs_1.card.id)) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () { return _this.buyCardMiraculousCatch(); }); }, 250);
                document.getElementById('buyCardMiraculousCatch_button').dataset.enableAtEnergy = '' + miraculousCatchArgs_1.cost;
                dojo.toggleClass('buyCardMiraculousCatch_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < miraculousCatchArgs_1.cost);
                break;
            case 'DeepDive':
                var deepDiveCatchArgs = question.args;
                deepDiveCatchArgs.cards.forEach(function (card) {
                    _this.addActionButton("playCardDeepDive_button" + card.id, formatTextIcons(dojo.string.substitute(_('Play ${card_name}'), { card_name: _this.cards.getCardName(card.type, 'text-only') })), function () { return _this.playCardDeepDive(card.id); });
                    setTimeout(function () { var _a; return (_a = document.getElementById("deepDive-card-" + card.id)) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () { return _this.playCardDeepDive(card.id); }); }, 250);
                });
                break;
            case 'ExoticArms':
                var useExoticArmsLabel = dojo.string.substitute(_("Put ${number}[Energy] on ${card_name}"), { card_name: this.evolutionCards.getCardName(26, 'text-only'), number: 2 });
                this.addActionButton('useExoticArms_button', formatTextIcons(useExoticArmsLabel), function () { return _this.useExoticArms(); });
                this.addActionButton('skipExoticArms_button', _('Skip'), function () { return _this.skipExoticArms(); });
                dojo.toggleClass('useExoticArms_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useExoticArms_button').dataset.enableAtEnergy = '2';
                break;
            case 'TargetAcquired':
                var targetAcquiredCatchArgs = question.args;
                this.addActionButton('giveTarget_button', dojo.string.substitute(_("Give target to ${player_name}"), { 'player_name': this.getPlayer(targetAcquiredCatchArgs.playerId).name }), function () { return _this.giveTarget(); });
                this.addActionButton('skipGiveTarget_button', _('Skip'), function () { return _this.skipGiveTarget(); });
                break;
            case 'LightningArmor':
                this.addActionButton('useLightningArmor_button', _("Throw dice"), function () { return _this.useLightningArmor(); });
                this.addActionButton('skipLightningArmor_button', _('Skip'), function () { return _this.skipLightningArmor(); });
                break;
            case 'EnergySword':
                this.addActionButton('useEnergySword_button', dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCards.getCardName(147, 'text-only') }), function () { return _this.answerEnergySword(true); });
                this.addActionButton('skipEnergySword_button', _('Skip'), function () { return _this.answerEnergySword(false); });
                dojo.toggleClass('useEnergySword_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useEnergySword_button').dataset.enableAtEnergy = '2';
                break;
            case 'SunkenTemple':
                this.addActionButton('useSunkenTemple_button', dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCards.getCardName(157, 'text-only') }), function () { return _this.answerSunkenTemple(true); });
                this.addActionButton('skipSunkenTemple_button', _('Skip'), function () { return _this.answerSunkenTemple(false); });
                break;
            case 'ElectricCarrot':
                this.addActionButton('answerElectricCarrot5_button', formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: '[Energy]' })), function () { return _this.answerElectricCarrot(5); });
                dojo.toggleClass('answerElectricCarrot5_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 4);
                document.getElementById('answerElectricCarrot5_button').dataset.enableAtEnergy = '1';
                this.addActionButton('answerElectricCarrot4_button', formatTextIcons(_("Lose 1 extra [Heart]")), function () { return _this.answerElectricCarrot(4); });
                break;
            case 'SuperiorAlienTechnology':
                this.addActionButton('throwDieSuperiorAlienTechnology_button', _('Roll a die'), function () { return _this.throwDieSuperiorAlienTechnology(); });
                break;
        }
    };
    ///////////////////////////////////////////////////
    //// Utility methods
    ///////////////////////////////////////////////////
    KingOfTokyo.prototype.getPlayerId = function () {
        return Number(this.player_id);
    };
    KingOfTokyo.prototype.isHalloweenExpansion = function () {
        return this.gamedatas.halloweenExpansion;
    };
    KingOfTokyo.prototype.isKingkongExpansion = function () {
        return this.gamedatas.kingkongExpansion;
    };
    KingOfTokyo.prototype.isCybertoothExpansion = function () {
        return this.gamedatas.cybertoothExpansion;
    };
    KingOfTokyo.prototype.isMutantEvolutionVariant = function () {
        return this.gamedatas.mutantEvolutionVariant;
    };
    KingOfTokyo.prototype.isCthulhuExpansion = function () {
        return this.gamedatas.cthulhuExpansion;
    };
    KingOfTokyo.prototype.isAnubisExpansion = function () {
        return this.gamedatas.anubisExpansion;
    };
    KingOfTokyo.prototype.isWickednessExpansion = function () {
        return this.gamedatas.wickednessExpansion;
    };
    KingOfTokyo.prototype.isPowerUpExpansion = function () {
        return this.gamedatas.powerUpExpansion;
    };
    KingOfTokyo.prototype.isDarkEdition = function () {
        return this.gamedatas.darkEdition;
    };
    KingOfTokyo.prototype.isDefaultFont = function () {
        return Number(this.prefs[201].value) == 1;
    };
    KingOfTokyo.prototype.getPlayer = function (playerId) {
        return this.gamedatas.players[playerId];
    };
    KingOfTokyo.prototype.createButton = function (destinationId, id, text, callback, disabled) {
        if (disabled === void 0) { disabled = false; }
        var html = "<button class=\"action-button bgabutton bgabutton_blue\" id=\"" + id + "\">\n            " + text + "\n        </button>";
        dojo.place(html, destinationId);
        if (disabled) {
            dojo.addClass(id, 'disabled');
        }
        document.getElementById(id).addEventListener('click', function () { return callback(); });
    };
    KingOfTokyo.prototype.addTwoPlayerVariantNotice = function (gamedatas) {
        var _a;
        dojo.addClass('board', 'twoPlayersVariant');
        // 2-players variant notice
        if (Object.keys(gamedatas.players).length == 2 && ((_a = this.prefs[203]) === null || _a === void 0 ? void 0 : _a.value) == 1) {
            dojo.place("\n                    <div id=\"board-corner-highlight\"></div>\n                    <div id=\"twoPlayersVariant-message\">\n                        " + _("You are playing the 2-players variant.") + "<br>\n                        " + _("When entering or starting a turn on Tokyo, you gain 1 energy instead of points") + ".<br>\n                        " + _("You can check if variant is activated in the bottom left corner of the table.") + "<br>\n                        <div style=\"text-align: center\"><a id=\"hide-twoPlayersVariant-message\">" + _("Dismiss") + "</a></div>\n                    </div>\n                ", 'board');
            document.getElementById('hide-twoPlayersVariant-message').addEventListener('click', function () {
                var select = document.getElementById('preference_control_203');
                select.value = '2';
                var event = new Event('change');
                select.dispatchEvent(event);
            });
        }
    };
    KingOfTokyo.prototype.getOrderedPlayers = function () {
        return Object.values(this.gamedatas.players).sort(function (a, b) { return Number(a.player_no) - Number(b.player_no); });
    };
    KingOfTokyo.prototype.createPlayerPanels = function (gamedatas) {
        var _this = this;
        Object.values(gamedatas.players).forEach(function (player) {
            var playerId = Number(player.id);
            var eliminated = Number(player.eliminated) > 0 || player.playerDead > 0;
            // health & energy counters
            var html = "<div class=\"counters\">\n                <div id=\"health-counter-wrapper-" + player.id + "\" class=\"counter\">\n                    <div class=\"icon health\"></div> \n                    <span id=\"health-counter-" + player.id + "\"></span>\n                </div>\n                <div id=\"energy-counter-wrapper-" + player.id + "\" class=\"counter\">\n                    <div class=\"icon energy\"></div> \n                    <span id=\"energy-counter-" + player.id + "\"></span>\n                </div>";
            if (gamedatas.wickednessExpansion) {
                html += "\n                <div id=\"wickedness-counter-wrapper-" + player.id + "\" class=\"counter\">\n                    <div class=\"icon wickedness\"></div> \n                    <span id=\"wickedness-counter-" + player.id + "\"></span>\n                </div>";
            }
            html += "</div>";
            dojo.place(html, "player_board_" + player.id);
            _this.addTooltipHtml("health-counter-wrapper-" + player.id, _("Health"));
            _this.addTooltipHtml("energy-counter-wrapper-" + player.id, _("Energy"));
            if (gamedatas.wickednessExpansion) {
                _this.addTooltipHtml("wickedness-counter-wrapper-" + player.id, _("Wickedness points"));
            }
            if (gamedatas.kingkongExpansion || gamedatas.cybertoothExpansion || gamedatas.cthulhuExpansion) {
                var html_1 = "<div class=\"counters\">";
                if (gamedatas.cthulhuExpansion) {
                    html_1 += "\n                    <div id=\"cultist-counter-wrapper-" + player.id + "\" class=\"counter cultist-tooltip\">\n                        <div class=\"icon cultist\"></div>\n                        <span id=\"cultist-counter-" + player.id + "\"></span>\n                    </div>";
                }
                if (gamedatas.kingkongExpansion) {
                    html_1 += "<div id=\"tokyo-tower-counter-wrapper-" + player.id + "\" class=\"counter tokyo-tower-tooltip\">";
                    for (var level = 1; level <= 3; level++) {
                        html_1 += "<div id=\"tokyo-tower-icon-" + player.id + "-level-" + level + "\" class=\"tokyo-tower-icon level" + level + "\" data-owned=\"" + player.tokyoTowerLevels.includes(level).toString() + "\"></div>";
                    }
                    html_1 += "</div>";
                }
                if (gamedatas.cybertoothExpansion) {
                    html_1 += "\n                    <div id=\"berserk-counter-wrapper-" + player.id + "\" class=\"counter berserk-tooltip\">\n                        <div class=\"berserk-icon-wrapper\">\n                            <div id=\"player-panel-berserk-" + player.id + "\" class=\"berserk icon " + (player.berserk ? 'active' : '') + "\"></div>\n                        </div>\n                    </div>";
                }
                html_1 += "</div>";
                dojo.place(html_1, "player_board_" + player.id);
                if (gamedatas.cthulhuExpansion) {
                    var cultistCounter = new ebg.counter();
                    cultistCounter.create("cultist-counter-" + player.id);
                    cultistCounter.setValue(player.cultists);
                    _this.cultistCounters[playerId] = cultistCounter;
                }
            }
            var healthCounter = new ebg.counter();
            healthCounter.create("health-counter-" + player.id);
            healthCounter.setValue(player.health);
            _this.healthCounters[playerId] = healthCounter;
            var energyCounter = new ebg.counter();
            energyCounter.create("energy-counter-" + player.id);
            energyCounter.setValue(player.energy);
            _this.energyCounters[playerId] = energyCounter;
            if (gamedatas.wickednessExpansion) {
                var wickednessCounter = new ebg.counter();
                wickednessCounter.create("wickedness-counter-" + player.id);
                wickednessCounter.setValue(player.wickedness);
                _this.wickednessCounters[playerId] = wickednessCounter;
            }
            if (gamedatas.powerUpExpansion) {
                // hand cards counter
                dojo.place("<div class=\"counters\">\n                    <div id=\"playerhand-counter-wrapper-" + player.id + "\" class=\"playerhand-counter\">\n                        <div class=\"player-evolution-card\"></div>\n                        <div class=\"player-hand-card\"></div> \n                        <span id=\"playerhand-counter-" + player.id + "\"></span>\n                    </div>\n                    <div class=\"show-evolutions-button\">\n                    <button id=\"see-monster-evolution-player-" + playerId + "\" class=\"bgabutton bgabutton_gray " + (_this.gamedatas.gamestate.id >= 15 /*ST_PLAYER_CHOOSE_INITIAL_CARD*/ ? 'visible' : '') + "\">\n                        " + _('Show Evolutions') + "\n                    </button>\n                    </div>\n                </div>", "player_board_" + player.id);
                var handCounter = new ebg.counter();
                handCounter.create("playerhand-counter-" + playerId);
                handCounter.setValue(player.hiddenEvolutions.length);
                _this.handCounters[playerId] = handCounter;
                _this.addTooltipHtml("playerhand-counter-wrapper-" + player.id, _("Number of Evolution cards in hand."));
                document.getElementById("see-monster-evolution-player-" + playerId).addEventListener('click', function () { return _this.showPlayerEvolutions(playerId); });
            }
            dojo.place("<div class=\"player-tokens\">\n                <div id=\"player-board-target-tokens-" + player.id + "\" class=\"player-token target-tokens\"></div>\n                <div id=\"player-board-shrink-ray-tokens-" + player.id + "\" class=\"player-token shrink-ray-tokens\"></div>\n                <div id=\"player-board-poison-tokens-" + player.id + "\" class=\"player-token poison-tokens\"></div>\n            </div>", "player_board_" + player.id);
            if (!eliminated) {
                _this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
                _this.setPoisonTokens(playerId, player.poisonTokens);
                _this.setPlayerTokens(playerId, gamedatas.targetedPlayer == playerId ? 1 : 0, 'target');
            }
            dojo.place("<div id=\"player-board-monster-figure-" + player.id + "\" class=\"monster-figure monster" + player.monster + "\"><div class=\"kot-token\"></div></div>", "player_board_" + player.id);
            if (player.location > 0) {
                dojo.addClass("overall_player_board_" + playerId, 'intokyo');
            }
            if (eliminated) {
                setTimeout(function () { return _this.eliminatePlayer(playerId); }, 200);
            }
        });
        this.addTooltipHtmlToClass('shrink-ray-tokens', this.SHINK_RAY_TOKEN_TOOLTIP);
        this.addTooltipHtmlToClass('poison-tokens', this.POISON_TOKEN_TOOLTIP);
    };
    KingOfTokyo.prototype.createPlayerTables = function (gamedatas) {
        var _this = this;
        var evolutionCardsWithSingleState = this.isPowerUpExpansion() ?
            Object.values(this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE).reduce(function (a1, a2) { return __spreadArray(__spreadArray([], a1, true), a2, true); }, []) :
            null;
        this.playerTables = this.getOrderedPlayers().map(function (player) {
            var playerId = Number(player.id);
            var playerWithGoldenScarab = gamedatas.anubisExpansion && playerId === gamedatas.playerWithGoldenScarab;
            return new PlayerTable(_this, player, playerWithGoldenScarab, evolutionCardsWithSingleState);
        });
        if (gamedatas.targetedPlayer) {
            this.getPlayerTable(gamedatas.targetedPlayer).giveTarget();
        }
    };
    KingOfTokyo.prototype.getPlayerTable = function (playerId) {
        return this.playerTables.find(function (playerTable) { return playerTable.playerId === Number(playerId); });
    };
    KingOfTokyo.prototype.isInitialCardDoubleSelection = function () {
        var args = this.gamedatas.gamestate.args;
        return args.chooseCostume && args.chooseEvolution;
    };
    KingOfTokyo.prototype.confirmDoubleSelectionCheckState = function () {
        var _a, _b, _c;
        var costumeSelected = ((_a = this.tableCenter.getVisibleCards()) === null || _a === void 0 ? void 0 : _a.getSelectedItems().length) === 1;
        var evolutionSelected = ((_b = this.getPlayerTable(this.getPlayerId())) === null || _b === void 0 ? void 0 : _b.pickEvolutionCards.getSelectedItems().length) === 1;
        (_c = document.getElementById("confirmInitialCards_button")) === null || _c === void 0 ? void 0 : _c.classList.toggle('disabled', !costumeSelected || !evolutionSelected);
    };
    KingOfTokyo.prototype.setDiceSelectorVisibility = function (visible) {
        var div = document.getElementById('rolled-dice');
        div.style.display = visible ? 'flex' : 'none';
    };
    KingOfTokyo.prototype.getZoom = function () {
        return this.tableManager.zoom;
    };
    KingOfTokyo.prototype.getPreferencesManager = function () {
        return this.preferencesManager;
    };
    KingOfTokyo.prototype.removeMonsterChoice = function () {
        if (document.getElementById('monster-pick')) {
            this.fadeOutAndDestroy('monster-pick');
        }
    };
    KingOfTokyo.prototype.removeMutantEvolutionChoice = function () {
        if (document.getElementById('mutant-evolution-choice')) {
            this.fadeOutAndDestroy('mutant-evolution-choice');
        }
    };
    KingOfTokyo.prototype.showMainTable = function () {
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            dojo.removeClass('kot-table', 'pickMonsterOrEvolutionDeck');
            this.tableManager.setAutoZoomAndPlacePlayerTables();
            this.tableCenter.getVisibleCards().updateDisplay();
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.updateDisplay(); });
        }
    };
    KingOfTokyo.prototype.getStateName = function () {
        return this.gamedatas.gamestate.name;
    };
    KingOfTokyo.prototype.toggleRerollDiceButton = function () {
        var args = this.gamedatas.gamestate.args;
        var selectedDiceCount = this.diceManager.getSelectedDiceIds().length;
        var canReroll = selectedDiceCount >= args.min && selectedDiceCount <= args.max;
        dojo.toggleClass('rerollDice_button', 'disabled', !canReroll);
    };
    KingOfTokyo.prototype.onVisibleCardClick = function (stock, cardId, from, warningChecked) {
        var _this = this;
        var _a, _b;
        if (from === void 0) { from = 0; }
        if (warningChecked === void 0) { warningChecked = false; }
        if (!cardId) {
            return;
        }
        if (dojo.hasClass(stock.container_div.id + "_item_" + cardId, 'disabled')) {
            stock.unselectItem('' + cardId);
            return;
        }
        var stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            if (!this.isInitialCardDoubleSelection()) {
                this.chooseInitialCard(Number(cardId), null);
            }
            else {
                this.confirmDoubleSelectionCheckState();
            }
        }
        else if (stateName === 'stealCostumeCard') {
            this.stealCostumeCard(cardId);
        }
        else if (stateName === 'sellCard') {
            this.sellCard(cardId);
        }
        else if (stateName === 'chooseMimickedCard' || stateName === 'opportunistChooseMimicCard') {
            this.chooseMimickedCard(cardId);
        }
        else if (stateName === 'changeMimickedCard') {
            this.changeMimickedCard(cardId);
        }
        else if (stateName === 'chooseMimickedCardWickednessTile') {
            this.chooseMimickedCardWickednessTile(cardId);
        }
        else if (stateName === 'changeMimickedCardWickednessTile') {
            this.changeMimickedCardWickednessTile(cardId);
        }
        else if (stateName === 'buyCard' || stateName === 'opportunistBuyCard') {
            var buyCardArgs = this.gamedatas.gamestate.args;
            var warningIcon = !warningChecked && buyCardArgs.warningIds[cardId];
            if (warningIcon) {
                this.confirmationDialog(formatTextIcons(dojo.string.substitute(_("Are you sure you want to buy that card? You won't gain ${symbol}"), { symbol: warningIcon })), function () { return _this.onVisibleCardClick(stock, cardId, from, true); });
            }
            else {
                var cardCostSuperiorAlienTechnology = (_a = buyCardArgs.cardsCostsSuperiorAlienTechnology) === null || _a === void 0 ? void 0 : _a[cardId];
                var cardCostBobbingForApples = (_b = buyCardArgs.cardsCostsBobbingForApples) === null || _b === void 0 ? void 0 : _b[cardId];
                var canUseSuperiorAlienTechnologyForCard_1 = cardCostSuperiorAlienTechnology !== null && cardCostSuperiorAlienTechnology !== undefined && cardCostSuperiorAlienTechnology !== buyCardArgs.cardsCosts[cardId];
                var canUseBobbingForApplesForCard_1 = cardCostBobbingForApples !== null && cardCostBobbingForApples !== undefined && cardCostBobbingForApples !== buyCardArgs.cardsCosts[cardId];
                if (canUseSuperiorAlienTechnologyForCard_1 || canUseBobbingForApplesForCard_1) {
                    var both_1 = canUseSuperiorAlienTechnologyForCard_1 && canUseBobbingForApplesForCard_1;
                    var keys = [
                        formatTextIcons(dojo.string.substitute(_('Don\'t use ${card_name} and pay full cost ${cost}[Energy]'), { card_name: this.evolutionCards.getCardName(canUseSuperiorAlienTechnologyForCard_1 ? 28 : 85, 'text-only'), cost: buyCardArgs.cardsCosts[cardId] })),
                        _('Cancel')
                    ];
                    if (cardCostBobbingForApples) {
                        keys.unshift(formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay ${cost}[Energy]'), { card_name: this.evolutionCards.getCardName(85, 'text-only'), cost: cardCostBobbingForApples })));
                    }
                    if (canUseSuperiorAlienTechnologyForCard_1) {
                        keys.unshift(formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCards.getCardName(28, 'text-only'), cost: cardCostSuperiorAlienTechnology })));
                    }
                    this.multipleChoiceDialog(dojo.string.substitute(_('Do you want to buy the card at reduced cost with ${card_name} ?'), { 'card_name': this.evolutionCards.getCardName(28, 'text-only') }), keys, function (choice) {
                        var choiceIndex = Number(choice);
                        if (choiceIndex < (both_1 ? 3 : 2)) {
                            _this.tableCenter.removeOtherCardsFromPick(cardId);
                            _this.buyCard(cardId, from, canUseSuperiorAlienTechnologyForCard_1 && choiceIndex === 0, canUseBobbingForApplesForCard_1 && choiceIndex === (both_1 ? 1 : 0));
                        }
                    });
                    if (canUseSuperiorAlienTechnologyForCard_1 && buyCardArgs.canUseSuperiorAlienTechnology === false || cardCostSuperiorAlienTechnology > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById("choice_btn_0").classList.add('disabled');
                    }
                    if (canUseBobbingForApplesForCard_1 && cardCostBobbingForApples > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById("choice_btn_" + (both_1 ? 1 : 0)).classList.add('disabled');
                    }
                    if (buyCardArgs.cardsCosts[cardId] > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById("choice_btn_" + (both_1 ? 2 : 1)).classList.add('disabled');
                    }
                }
                else {
                    this.tableCenter.removeOtherCardsFromPick(cardId);
                    this.buyCard(cardId, from);
                }
            }
        }
        else if (stateName === 'discardKeepCard') {
            this.discardKeepCard(cardId);
        }
        else if (stateName === 'leaveTokyoExchangeCard') {
            this.exchangeCard(cardId);
        }
        else if (stateName === 'answerQuestion') {
            var args = this.gamedatas.gamestate.args;
            if (args.question.code === 'Bamboozle') {
                this.buyCardBamboozle(cardId, from);
            }
            else if (args.question.code === 'ChooseMimickedCard') {
                this.chooseMimickedCard(cardId);
            }
            else if (args.question.code === 'MyToy') {
                this.reserveCard(cardId);
            }
        }
    };
    KingOfTokyo.prototype.chooseEvolutionCardClick = function (id) {
        var stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            if (!this.isInitialCardDoubleSelection()) {
                this.chooseInitialCard(null, id);
            }
            else {
                this.confirmDoubleSelectionCheckState();
            }
        }
        else if (stateName === 'chooseEvolutionCard') {
            this.chooseEvolutionCard(id);
        }
    };
    KingOfTokyo.prototype.onHiddenEvolutionClick = function (cardId) {
        var stateName = this.getStateName();
        if (stateName === 'answerQuestion') {
            var args = this.gamedatas.gamestate.args;
            if (args.question.code === 'GazeOfTheSphinxSnake') {
                this.gazeOfTheSphinxDiscardEvolution(Number(cardId));
                return;
            }
        }
        this.playEvolution(cardId);
    };
    KingOfTokyo.prototype.onVisibleEvolutionClick = function (cardId) {
        var stateName = this.getStateName();
        if (stateName === 'answerQuestion') {
            var args = this.gamedatas.gamestate.args;
            if (args.question.code === 'GazeOfTheSphinxSnake') {
                this.gazeOfTheSphinxDiscardEvolution(Number(cardId));
            }
            else if (args.question.code === 'IcyReflection') {
                this.chooseMimickedEvolution(Number(cardId));
            }
        }
    };
    KingOfTokyo.prototype.setBuyDisabledCardByCost = function (disabledIds, cardsCosts, playerEnergy) {
        var disabledCardsIds = __spreadArray(__spreadArray([], disabledIds, true), Object.keys(cardsCosts).map(function (cardId) { return Number(cardId); }), true);
        disabledCardsIds.forEach(function (id) {
            var disabled = disabledIds.some(function (disabledId) { return disabledId == id; }) || cardsCosts[id] > playerEnergy;
            var cardDiv = document.querySelector(".card-stock div[id$=\"_item_" + id + "\"]");
            cardDiv === null || cardDiv === void 0 ? void 0 : cardDiv.classList.toggle('disabled', disabled);
        });
    };
    // called on state enter and when energy number is changed
    KingOfTokyo.prototype.setBuyDisabledCard = function (args, playerEnergy) {
        if (args === void 0) { args = null; }
        if (playerEnergy === void 0) { playerEnergy = null; }
        if (!this.isCurrentPlayerActive()) {
            return;
        }
        var stateName = this.getStateName();
        var buyState = stateName === 'buyCard' || stateName === 'opportunistBuyCard' || stateName === 'stealCostumeCard' || (stateName === 'answerQuestion' && ['ChooseMimickedCard', 'Bamboozle'].includes(this.gamedatas.gamestate.args.question.code));
        var changeMimicState = stateName === 'changeMimickedCard' || stateName === 'changeMimickedCardWickednessTile';
        if (!buyState && !changeMimicState) {
            return;
        }
        var bamboozle = stateName === 'answerQuestion' && this.gamedatas.gamestate.args.question.code === 'Bamboozle';
        var playerId = this.getPlayerId();
        if (bamboozle) {
            playerId = this.gamedatas.gamestate.args.question.args.cardBeingBought.playerId;
            playerEnergy = this.energyCounters[playerId].getValue();
        }
        if (args === null) {
            args = bamboozle ?
                this.gamedatas.gamestate.args.question.args.buyCardArgs :
                this.gamedatas.gamestate.args;
        }
        if (playerEnergy === null) {
            playerEnergy = this.energyCounters[playerId].getValue();
        }
        var cardsCosts = __assign({}, args.cardsCosts);
        var argsBuyCard = args;
        if (argsBuyCard.gotSuperiorAlienTechnology) {
            cardsCosts = __assign(__assign({}, cardsCosts), argsBuyCard.cardsCostsSuperiorAlienTechnology);
        }
        Object.keys(argsBuyCard.cardsCostsBobbingForApples).forEach(function (cardId) {
            if (argsBuyCard.cardsCostsBobbingForApples[cardId] < cardsCosts[cardId]) {
                cardsCosts[cardId] = argsBuyCard.cardsCostsBobbingForApples[cardId];
            }
        });
        this.setBuyDisabledCardByCost(args.disabledIds, cardsCosts, playerEnergy);
        // renew button
        if (buyState && document.getElementById('renew_button')) {
            dojo.toggleClass('renew_button', 'disabled', playerEnergy < 2);
        }
    };
    KingOfTokyo.prototype.addRapidHealingButton = function (userEnergy, isMaxHealth) {
        var _this = this;
        if (!document.getElementById('rapidHealingButton')) {
            this.createButton('rapid-actions-wrapper', 'rapidHealingButton', dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(_('Gain ${hearts}[Heart]') + " (2[Energy])"), { card_name: this.cards.getCardName(37, 'text-only'), hearts: 1 }), function () { return _this.useRapidHealing(); }, userEnergy < 2 || isMaxHealth);
        }
    };
    KingOfTokyo.prototype.removeRapidHealingButton = function () {
        if (document.getElementById('rapidHealingButton')) {
            dojo.destroy('rapidHealingButton');
        }
    };
    KingOfTokyo.prototype.addMothershipSupportButton = function (userEnergy, isMaxHealth) {
        var _this = this;
        if (!document.getElementById('mothershipSupportButton')) {
            this.createButton('rapid-actions-wrapper', 'mothershipSupportButton', dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(_('Gain ${hearts}[Heart]') + " (1[Energy])"), { card_name: this.evolutionCards.getCardName(27, 'text-only'), hearts: 1 }), function () { return _this.useMothershipSupport(); }, this.gamedatas.players[this.getPlayerId()].mothershipSupportUsed || userEnergy < 1 || isMaxHealth);
        }
    };
    KingOfTokyo.prototype.removeMothershipSupportButton = function () {
        if (document.getElementById('mothershipSupportButton')) {
            dojo.destroy('mothershipSupportButton');
        }
    };
    KingOfTokyo.prototype.addRapidCultistButtons = function (isMaxHealth) {
        var _this = this;
        if (!document.getElementById('rapidCultistButtons')) {
            dojo.place("<div id=\"rapidCultistButtons\"><span>" + dojo.string.substitute(_('Use ${card_name}'), { card_name: _('Cultist') }) + " :</span></div>", 'rapid-actions-wrapper');
            this.createButton('rapidCultistButtons', 'rapidCultistHealthButton', formatTextIcons("" + dojo.string.substitute(_('Gain ${hearts}[Heart]'), { hearts: 1 })), function () { return _this.useRapidCultist(4); }, isMaxHealth);
            this.createButton('rapidCultistButtons', 'rapidCultistEnergyButton', formatTextIcons("" + dojo.string.substitute(_('Gain ${energy}[Energy]'), { energy: 1 })), function () { return _this.useRapidCultist(5); });
        }
    };
    KingOfTokyo.prototype.removeRapidCultistButtons = function () {
        if (document.getElementById('rapidCultistButtons')) {
            dojo.destroy('rapidCultistButtons');
        }
    };
    KingOfTokyo.prototype.checkRapidHealingButtonState = function () {
        if (document.getElementById('rapidHealingButton')) {
            var playerId = this.getPlayerId();
            var userEnergy = this.energyCounters[playerId].getValue();
            var health = this.healthCounters[playerId].getValue();
            var maxHealth = this.gamedatas.players[playerId].maxHealth;
            dojo.toggleClass('rapidHealingButton', 'disabled', userEnergy < 2 || health >= maxHealth);
        }
    };
    KingOfTokyo.prototype.checkMothershipSupportButtonState = function () {
        if (document.getElementById('mothershipSupportButton')) {
            var playerId = this.getPlayerId();
            var userEnergy = this.energyCounters[playerId].getValue();
            var health = this.healthCounters[playerId].getValue();
            var maxHealth = this.gamedatas.players[playerId].maxHealth;
            var used = this.gamedatas.players[playerId].mothershipSupportUsed;
            dojo.toggleClass('mothershipSupportButton', 'disabled', used || userEnergy < 1 || health >= maxHealth);
        }
    };
    KingOfTokyo.prototype.checkHealthCultistButtonState = function () {
        if (document.getElementById('rapidCultistHealthButton')) {
            var playerId = this.getPlayerId();
            var health = this.healthCounters[playerId].getValue();
            var maxHealth = this.gamedatas.players[playerId].maxHealth;
            dojo.toggleClass('rapidCultistHealthButton', 'disabled', health >= maxHealth);
        }
    };
    KingOfTokyo.prototype.addSkipBuyPhaseToggle = function (active) {
        var _this = this;
        if (!document.getElementById('skipBuyPhaseWrapper')) {
            dojo.place("<div id=\"skipBuyPhaseWrapper\">\n                <label class=\"switch\">\n                    <input id=\"skipBuyPhaseCheckbox\" type=\"checkbox\" " + (active ? 'checked' : '') + ">\n                    <span class=\"slider round\"></span>\n                </label>\n                <label for=\"skipBuyPhaseCheckbox\" class=\"text-label\">" + _("Skip buy phase") + "</label>\n            </div>", 'rapid-actions-wrapper');
            document.getElementById('skipBuyPhaseCheckbox').addEventListener('change', function (e) { return _this.setSkipBuyPhase(e.target.checked); });
        }
    };
    KingOfTokyo.prototype.removeSkipBuyPhaseToggle = function () {
        if (document.getElementById('skipBuyPhaseWrapper')) {
            dojo.destroy('skipBuyPhaseWrapper');
        }
    };
    KingOfTokyo.prototype.addAutoLeaveUnderButton = function () {
        var _this = this;
        if (!document.getElementById('autoLeaveUnderButton')) {
            this.createButton('rapid-actions-wrapper', 'autoLeaveUnderButton', _("Leave Tokyo") + ' &#x25BE;', function () { return _this.toggleAutoLeaveUnderPopin(); });
        }
    };
    KingOfTokyo.prototype.removeAutoLeaveUnderButton = function () {
        if (document.getElementById('autoLeaveUnderButton')) {
            dojo.destroy('autoLeaveUnderButton');
        }
    };
    KingOfTokyo.prototype.toggleAutoLeaveUnderPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoLeaveUnder");
        if ((bubble === null || bubble === void 0 ? void 0 : bubble.dataset.visible) === 'true') {
            this.closeAutoLeaveUnderPopin();
        }
        else {
            this.openAutoLeaveUnderPopin();
        }
    };
    KingOfTokyo.prototype.openAutoLeaveUnderPopin = function () {
        var _this = this;
        var popinId = "discussion_bubble_autoLeaveUnder";
        var bubble = document.getElementById(popinId);
        if (!bubble) {
            var maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
            var html = "<div id=\"" + popinId + "\" class=\"discussion_bubble autoLeaveUnderBubble\">\n                <div>" + _("Automatically leave tokyo when life goes down to, or under") + "</div>\n                <div id=\"" + popinId + "-buttons\" class=\"button-grid\">";
            for (var i = maxHealth; i > 0; i--) {
                html += "<button class=\"action-button bgabutton " + (this.gamedatas.leaveTokyoUnder === i || (i == 1 && !this.gamedatas.leaveTokyoUnder) ? 'bgabutton_blue' : 'bgabutton_gray') + " autoLeaveButton " + (i == 1 ? 'disable' : '') + "\" id=\"" + popinId + "_set" + i + "\">\n                    " + (i == 1 ? _('Disabled') : i - 1) + "\n                </button>";
            }
            html += "</div>\n            <div>" + _("If your life is over it, or if disabled, you'll be asked if you want to stay or leave") + "</div>\n            <hr>\n            <div>" + _("Automatically stay in tokyo when life is at least") + "</div>\n                <div id=\"" + popinId + "-stay-buttons\" class=\"button-grid\">";
            for (var i = maxHealth + 1; i > 2; i--) {
                html += "<button class=\"action-button bgabutton " + (this.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray') + " autoStayButton " + (this.gamedatas.leaveTokyoUnder > 0 && i <= this.gamedatas.leaveTokyoUnder ? 'disabled' : '') + "\" id=\"" + popinId + "_setStay" + i + "\">" + (i - 1) + "</button>";
            }
            html += "<button class=\"action-button bgabutton " + (!this.gamedatas.stayTokyoOver ? 'bgabutton_blue' : 'bgabutton_gray') + " autoStayButton disable\" id=\"" + popinId + "_setStay0\">" + _('Disabled') + "</button>";
            html += "</div>\n            </div>";
            dojo.place(html, 'autoLeaveUnderButton');
            var _loop_6 = function (i) {
                document.getElementById(popinId + "_set" + i).addEventListener('click', function () {
                    _this.setLeaveTokyoUnder(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            };
            for (var i = maxHealth; i > 0; i--) {
                _loop_6(i);
            }
            var _loop_7 = function (i) {
                document.getElementById(popinId + "_setStay" + i).addEventListener('click', function () {
                    _this.setStayTokyoOver(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            };
            for (var i = maxHealth + 1; i > 2; i--) {
                _loop_7(i);
            }
            document.getElementById(popinId + "_setStay0").addEventListener('click', function () {
                _this.setStayTokyoOver(0);
                setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
            });
            bubble = document.getElementById(popinId);
        }
        bubble.style.display = 'block';
        bubble.dataset.visible = 'true';
    };
    KingOfTokyo.prototype.updateAutoLeavePopinButtons = function () {
        var _this = this;
        var popinId = "discussion_bubble_autoLeaveUnder";
        var maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
        for (var i = maxHealth + 1; i <= 14; i++) {
            if (document.getElementById(popinId + "_set" + i)) {
                dojo.destroy(popinId + "_set" + i);
            }
            if (document.getElementById(popinId + "_setStay" + i)) {
                dojo.destroy(popinId + "_setStay" + i);
            }
        }
        var _loop_8 = function (i) {
            if (!document.getElementById(popinId + "_set" + i)) {
                dojo.place("<button class=\"action-button bgabutton " + (this_5.gamedatas.leaveTokyoUnder === i ? 'bgabutton_blue' : 'bgabutton_gray') + " autoLeaveButton\" id=\"" + popinId + "_set" + i + "\">\n                    " + (i - 1) + "\n                </button>", popinId + "-buttons", 'first');
                document.getElementById(popinId + "_set" + i).addEventListener('click', function () {
                    _this.setLeaveTokyoUnder(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            }
        };
        var this_5 = this;
        for (var i = 11; i <= maxHealth; i++) {
            _loop_8(i);
        }
        var _loop_9 = function (i) {
            if (!document.getElementById(popinId + "_setStay" + i)) {
                dojo.place("<button class=\"action-button bgabutton " + (this_6.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray') + " autoStayButton " + (this_6.gamedatas.leaveTokyoUnder > 0 && i <= this_6.gamedatas.leaveTokyoUnder ? 'disabled' : '') + "\" id=\"" + popinId + "_setStay" + i + "\">\n                    " + (i - 1) + "\n                </button>", popinId + "-stay-buttons", 'first');
                document.getElementById(popinId + "_setStay" + i).addEventListener('click', function () {
                    _this.setStayTokyoOver(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            }
        };
        var this_6 = this;
        for (var i = 12; i <= maxHealth + 1; i++) {
            _loop_9(i);
        }
    };
    KingOfTokyo.prototype.closeAutoLeaveUnderPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoLeaveUnder");
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    };
    KingOfTokyo.prototype.addAutoSkipPlayEvolutionButton = function () {
        var _this = this;
        if (!document.getElementById('autoSkipPlayEvolutionButton')) {
            this.createButton('autoSkipPlayEvolution-wrapper', 'autoSkipPlayEvolutionButton', _("Ask to play evolution") + ' &#x25BE;', function () { return _this.toggleAutoSkipPlayEvolutionPopin(); });
        }
    };
    KingOfTokyo.prototype.toggleAutoSkipPlayEvolutionPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoSkipPlayEvolution");
        if ((bubble === null || bubble === void 0 ? void 0 : bubble.dataset.visible) === 'true') {
            this.closeAutoSkipPlayEvolutionPopin();
        }
        else {
            this.openAutoSkipPlayEvolutionPopin();
        }
    };
    KingOfTokyo.prototype.openAutoSkipPlayEvolutionPopin = function () {
        var _this = this;
        var popinId = "discussion_bubble_autoSkipPlayEvolution";
        var bubble = document.getElementById(popinId);
        if (!bubble) {
            var html = "<div id=\"" + popinId + "\" class=\"discussion_bubble autoSkipPlayEvolutionBubble\">\n                <h3>" + _("Ask to play Evolution, for Evolutions playable on specific occasions") + "</h3>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"0\" id=\"autoSkipPlayEvolution-all\" />\n                    <label for=\"autoSkipPlayEvolution-all\">\n                        " + _("Ask for every specific occasion even if I don't have the card in my hand.") + "\n                        <div class=\"label-detail\">\n                            " + _("Recommended. You won't be asked when your hand is empty") + "\n                        </div>\n                    </label>\n                </div>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"1\" id=\"autoSkipPlayEvolution-real\" />\n                    <label for=\"autoSkipPlayEvolution-real\">\n                        " + _("Ask only if I have in my hand an Evolution matching the specific occasion.") + "<br>\n                        <div class=\"label-detail spe-warning\">\n                            <strong>" + _("Warning:") + "</strong> " + _("Your opponent can deduce what you have in hand with this option.") + "\n                        </div>\n                    </label>\n                </div>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"2\" id=\"autoSkipPlayEvolution-turn\" />\n                    <label for=\"autoSkipPlayEvolution-turn\">\n                        " + _("Do not ask until my next turn.") + "<br>\n                        <div class=\"label-detail spe-warning\">\n                            <strong>" + _("Warning:") + "</strong> " + _("Do it only if you're sure you won't need an Evolution soon.") + "\n                        </div>\n                    </label>\n                </div>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"3\" id=\"autoSkipPlayEvolution-off\" />\n                    <label for=\"autoSkipPlayEvolution-off\">\n                        " + _("Do not ask until I turn it back on.") + "\n                        <div class=\"label-detail spe-warning\">\n                            <strong>" + _("Warning:") + "</strong> " + _("Do it only if you're sure you won't need an Evolution soon.") + "\n                        </div>\n                    </label>\n                </div>\n            </div>";
            dojo.place(html, 'autoSkipPlayEvolutionButton');
            Array.from(document.querySelectorAll('input[name="autoSkipPlayEvolution"]')).forEach(function (input) {
                input.addEventListener('change', function () {
                    var value = document.querySelector('input[name="autoSkipPlayEvolution"]:checked').value;
                    _this.setAskPlayEvolution(Number(value));
                    setTimeout(function () { return _this.closeAutoSkipPlayEvolutionPopin(); }, 100);
                });
            });
            bubble = document.getElementById(popinId);
            this.notif_updateAskPlayEvolution({
                args: {
                    value: this.gamedatas.askPlayEvolution
                }
            });
        }
        bubble.style.display = 'block';
        bubble.dataset.visible = 'true';
    };
    KingOfTokyo.prototype.closeAutoSkipPlayEvolutionPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoSkipPlayEvolution");
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    };
    KingOfTokyo.prototype.setMimicToken = function (type, card) {
        var _this = this;
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.items.some(function (item) { return Number(item.id) == card.id; })) {
                _this.cards.placeMimicOnCard(type, playerTable.cards, card, _this.wickednessTiles);
            }
        });
        this.setMimicTooltip(type, card);
    };
    KingOfTokyo.prototype.removeMimicToken = function (type, card) {
        var _this = this;
        this.setMimicTooltip(type, null);
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.items.some(function (item) { return Number(item.id) == card.id; })) {
                _this.cards.removeMimicOnCard(type, playerTable.cards, card);
            }
        });
    };
    KingOfTokyo.prototype.setMimicEvolutionToken = function (card) {
        var _this = this;
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.visibleEvolutionCards.items.some(function (item) { return Number(item.id) == card.id; })) {
                _this.evolutionCards.placeMimicOnCard(playerTable.visibleEvolutionCards, card);
            }
        });
        this.setMimicEvolutionTooltip(card);
    };
    KingOfTokyo.prototype.setMimicTooltip = function (type, mimickedCard) {
        var _this = this;
        this.playerTables.forEach(function (playerTable) {
            var stock = type === 'tile' ? playerTable.wickednessTiles : playerTable.cards;
            var mimicCardId = type === 'tile' ? 106 : 27;
            var mimicCardItem = stock.items.find(function (item) { return Number(item.type) == mimicCardId; });
            if (mimicCardItem) {
                var cardManager = type === 'tile' ? _this.wickednessTiles : _this.cards;
                cardManager.changeMimicTooltip(stock.container_div.id + "_item_" + mimicCardItem.id, _this.cards.getMimickedCardText(mimickedCard));
            }
        });
    };
    KingOfTokyo.prototype.setMimicEvolutionTooltip = function (mimickedCard) {
        var _this = this;
        this.playerTables.forEach(function (playerTable) {
            var mimicCardItem = playerTable.visibleEvolutionCards.items.find(function (item) { return Number(item.type) == 18; });
            if (mimicCardItem) {
                _this.evolutionCards.changeMimicTooltip(playerTable.visibleEvolutionCards.container_div.id + "_item_" + mimicCardItem.id, _this.evolutionCards.getMimickedCardText(mimickedCard));
            }
        });
    };
    KingOfTokyo.prototype.removeMimicEvolutionToken = function (card) {
        var _this = this;
        this.setMimicEvolutionTooltip(null);
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.items.some(function (item) { return Number(item.id) == card.id; })) {
                _this.evolutionCards.removeMimicOnCard(playerTable.cards, card);
            }
        });
    };
    KingOfTokyo.prototype.showEvolutionsPopin = function (cardsTypes, title) {
        var _this = this;
        var viewCardsDialog = new ebg.popindialog();
        viewCardsDialog.create('kotViewEvolutionsDialog');
        viewCardsDialog.setTitle(title);
        var html = "<div id=\"see-monster-evolutions\" class=\"evolution-card-stock player-evolution-cards\"></div>";
        // Show the dialog
        viewCardsDialog.setContent(html);
        cardsTypes.forEach(function (cardType) {
            dojo.place("\n                <div id=\"see-monster-evolutions_item_" + cardType + "\" class=\"stockitem stockitem_unselectable\" style=\"background-position: -" + (MONSTERS_WITH_POWER_UP_CARDS.indexOf(Math.floor(cardType / 10)) + 1) * 100 + "% 0%;\"></div>\n            ", 'see-monster-evolutions');
            _this.evolutionCards.setupNewCard(document.getElementById("see-monster-evolutions_item_" + cardType), cardType);
        });
        viewCardsDialog.show();
        // Replace the function call when it's clicked
        viewCardsDialog.replaceCloseCallback(function () {
            viewCardsDialog.destroy();
        });
    };
    KingOfTokyo.prototype.showMonsterEvolutions = function (monster) {
        var cardsTypes = [];
        for (var i = 1; i <= 8; i++) {
            cardsTypes.push(monster * 10 + i);
        }
        this.showEvolutionsPopin(cardsTypes, _("Monster Evolution cards"));
    };
    KingOfTokyo.prototype.showPlayerEvolutions = function (playerId) {
        var cardsTypes = this.gamedatas.players[playerId].ownedEvolutions.map(function (evolution) { return evolution.type; });
        this.showEvolutionsPopin(cardsTypes, dojo.string.substitute(_("Evolution cards owned by ${player_name}"), { 'player_name': this.gamedatas.players[playerId].name }));
    };
    KingOfTokyo.prototype.pickMonster = function (monster) {
        if (!this.checkAction('pickMonster')) {
            return;
        }
        this.takeAction('pickMonster', {
            monster: monster
        });
    };
    KingOfTokyo.prototype.pickEvolutionForDeck = function (id) {
        if (!this.checkAction('pickEvolutionForDeck')) {
            return;
        }
        this.takeAction('pickEvolutionForDeck', {
            id: id
        });
    };
    KingOfTokyo.prototype.chooseInitialCard = function (id, evolutionId) {
        if (!this.checkAction('chooseInitialCard')) {
            return;
        }
        this.takeAction('chooseInitialCard', {
            id: id,
            evolutionId: evolutionId,
        });
    };
    KingOfTokyo.prototype.skipBeforeStartTurn = function () {
        if (!this.checkAction('skipBeforeStartTurn')) {
            return;
        }
        this.takeAction('skipBeforeStartTurn');
    };
    KingOfTokyo.prototype.skipBeforeEndTurn = function () {
        if (!this.checkAction('skipBeforeEndTurn')) {
            return;
        }
        this.takeAction('skipBeforeEndTurn');
    };
    KingOfTokyo.prototype.skipBeforeEnteringTokyo = function () {
        if (!this.checkAction('skipBeforeEnteringTokyo')) {
            return;
        }
        this.takeAction('skipBeforeEnteringTokyo');
    };
    KingOfTokyo.prototype.skipAfterEnteringTokyo = function () {
        if (!this.checkAction('skipAfterEnteringTokyo')) {
            return;
        }
        this.takeAction('skipAfterEnteringTokyo');
    };
    KingOfTokyo.prototype.giveSymbolToActivePlayer = function (symbol) {
        if (!this.checkAction('giveSymbolToActivePlayer')) {
            return;
        }
        this.takeAction('giveSymbolToActivePlayer', {
            symbol: symbol
        });
    };
    KingOfTokyo.prototype.giveSymbol = function (symbol) {
        if (!this.checkAction('giveSymbol')) {
            return;
        }
        this.takeAction('giveSymbol', {
            symbol: symbol
        });
    };
    KingOfTokyo.prototype.onRethrow = function () {
        this.rethrowDice(this.diceManager.destroyFreeDice());
    };
    KingOfTokyo.prototype.rethrowDice = function (diceIds) {
        if (!this.checkAction('rethrow')) {
            return;
        }
        this.takeAction('rethrow', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.rethrow3 = function () {
        var lockedDice = this.diceManager.getLockedDice();
        this.takeAction('rethrow3', {
            diceIds: lockedDice.map(function (die) { return die.id; }).join(',')
        });
    };
    KingOfTokyo.prototype.rerollDie = function (id) {
        var lockedDice = this.diceManager.getLockedDice();
        this.takeAction('rerollDie', {
            id: id,
            diceIds: lockedDice.map(function (die) { return die.id; }).join(',')
        });
    };
    KingOfTokyo.prototype.rethrow3camouflage = function () {
        this.takeAction('rethrow3camouflage');
    };
    KingOfTokyo.prototype.rethrow3psychicProbe = function () {
        this.takeAction('rethrow3psychicProbe');
    };
    KingOfTokyo.prototype.rethrow3changeDie = function () {
        this.takeAction('rethrow3changeDie');
    };
    KingOfTokyo.prototype.buyEnergyDrink = function () {
        var diceIds = this.diceManager.destroyFreeDice();
        this.takeAction('buyEnergyDrink', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.useSmokeCloud = function () {
        var diceIds = this.diceManager.destroyFreeDice();
        this.takeAction('useSmokeCloud', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.useCultist = function () {
        var diceIds = this.diceManager.destroyFreeDice();
        this.takeAction('useCultist', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.useRapidHealing = function () {
        this.takeNoLockAction('useRapidHealing');
    };
    KingOfTokyo.prototype.useMothershipSupport = function () {
        this.takeNoLockAction('useMothershipSupport');
    };
    KingOfTokyo.prototype.useRapidCultist = function (type) {
        this.takeNoLockAction('useRapidCultist', { type: type });
    };
    KingOfTokyo.prototype.setSkipBuyPhase = function (skipBuyPhase) {
        this.takeNoLockAction('setSkipBuyPhase', {
            skipBuyPhase: skipBuyPhase
        });
    };
    KingOfTokyo.prototype.changeDie = function (id, value, card) {
        if (!this.checkAction('changeDie')) {
            return;
        }
        this.takeAction('changeDie', {
            id: id,
            value: value,
            card: card
        });
    };
    KingOfTokyo.prototype.psychicProbeRollDie = function (id) {
        if (!this.checkAction('psychicProbeRollDie')) {
            return;
        }
        this.takeAction('psychicProbeRollDie', {
            id: id
        });
    };
    KingOfTokyo.prototype.goToChangeDie = function (confirmed) {
        var _this = this;
        if (confirmed === void 0) { confirmed = false; }
        var args = this.gamedatas.gamestate.args;
        if (!confirmed && args.throwNumber == 1 && args.maxThrowNumber > 1) {
            this.confirmationDialog(formatTextIcons(_('Are you sure you want to resolve dice without any reroll? If you want to change your dice, click on the dice you want to keep and use "Reroll dice" button to reroll the others.')), function () { return _this.goToChangeDie(true); });
            return;
        }
        if (!this.checkAction('goToChangeDie', true)) {
            return;
        }
        this.takeAction('goToChangeDie');
    };
    KingOfTokyo.prototype.resolveDice = function () {
        if (!this.checkAction('resolve')) {
            return;
        }
        this.takeAction('resolve');
    };
    KingOfTokyo.prototype.support = function () {
        if (!this.checkAction('support')) {
            return;
        }
        this.takeAction('support');
    };
    KingOfTokyo.prototype.dontSupport = function () {
        if (!this.checkAction('dontSupport')) {
            return;
        }
        this.takeAction('dontSupport');
    };
    KingOfTokyo.prototype.discardDie = function (id) {
        if (!this.checkAction('discardDie')) {
            return;
        }
        this.takeAction('discardDie', {
            id: id
        });
    };
    KingOfTokyo.prototype.rerollOrDiscardDie = function (id) {
        if (!this.falseBlessingAnkhAction) {
            return;
        }
        if (!this.checkAction(this.falseBlessingAnkhAction)) {
            return;
        }
        this.takeAction(this.falseBlessingAnkhAction, {
            id: id
        });
    };
    KingOfTokyo.prototype.freezeDie = function (id) {
        if (!this.checkAction('freezeDie')) {
            return;
        }
        this.takeAction('freezeDie', {
            id: id
        });
    };
    KingOfTokyo.prototype.skipFreezeDie = function () {
        if (!this.checkAction('skipFreezeDie')) {
            return;
        }
        this.takeAction('skipFreezeDie');
    };
    KingOfTokyo.prototype.discardKeepCard = function (id) {
        if (!this.checkAction('discardKeepCard')) {
            return;
        }
        this.takeAction('discardKeepCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.giveGoldenScarab = function (playerId) {
        if (!this.checkAction('giveGoldenScarab')) {
            return;
        }
        this.takeAction('giveGoldenScarab', {
            playerId: playerId
        });
    };
    KingOfTokyo.prototype.giveSymbols = function (symbols) {
        if (!this.checkAction('giveSymbols')) {
            return;
        }
        this.takeAction('giveSymbols', {
            symbols: symbols.join(',')
        });
    };
    KingOfTokyo.prototype.selectExtraDie = function (face) {
        if (!this.checkAction('selectExtraDie')) {
            return;
        }
        this.takeAction('selectExtraDie', {
            face: face
        });
    };
    KingOfTokyo.prototype.falseBlessingReroll = function (id) {
        if (!this.checkAction('falseBlessingReroll')) {
            return;
        }
        this.takeAction('falseBlessingReroll', {
            id: id
        });
    };
    KingOfTokyo.prototype.falseBlessingDiscard = function (id) {
        if (!this.checkAction('falseBlessingDiscard')) {
            return;
        }
        this.takeAction('falseBlessingDiscard', {
            id: id
        });
    };
    KingOfTokyo.prototype.falseBlessingSkip = function () {
        if (!this.checkAction('falseBlessingSkip')) {
            return;
        }
        this.takeAction('falseBlessingSkip');
    };
    KingOfTokyo.prototype.rerollDice = function (diceIds) {
        if (!this.checkAction('rerollDice')) {
            return;
        }
        this.takeAction('rerollDice', {
            ids: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.takeWickednessTile = function (id) {
        if (!this.checkAction('takeWickednessTile')) {
            return;
        }
        this.takeAction('takeWickednessTile', {
            id: id
        });
    };
    KingOfTokyo.prototype.skipTakeWickednessTile = function () {
        if (!this.checkAction('skipTakeWickednessTile')) {
            return;
        }
        this.takeAction('skipTakeWickednessTile');
    };
    KingOfTokyo.prototype.applyHeartActions = function (selections) {
        if (!this.checkAction('applyHeartDieChoices')) {
            return;
        }
        var base64 = btoa(JSON.stringify(selections));
        this.takeAction('applyHeartDieChoices', {
            selections: base64
        });
    };
    KingOfTokyo.prototype.applySmashActions = function (selections) {
        if (!this.checkAction('applySmashDieChoices')) {
            return;
        }
        var base64 = btoa(JSON.stringify(__assign({}, selections)));
        this.takeAction('applySmashDieChoices', {
            selections: base64
        });
    };
    KingOfTokyo.prototype.chooseEvolutionCard = function (id) {
        if (!this.checkAction('chooseEvolutionCard')) {
            return;
        }
        this.takeAction('chooseEvolutionCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.onStayInTokyo = function () {
        if (!this.checkAction('stay')) {
            return;
        }
        this.takeAction('stay');
    };
    KingOfTokyo.prototype.onLeaveTokyo = function (useCard) {
        if (!this.checkAction('leave')) {
            return;
        }
        this.takeAction('leave', {
            useCard: useCard
        });
    };
    KingOfTokyo.prototype.stealCostumeCard = function (id) {
        if (!this.checkAction('stealCostumeCard')) {
            return;
        }
        this.takeAction('stealCostumeCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.changeForm = function () {
        if (!this.checkAction('changeForm')) {
            return;
        }
        this.takeAction('changeForm');
    };
    KingOfTokyo.prototype.skipChangeForm = function () {
        if (!this.checkAction('skipChangeForm')) {
            return;
        }
        this.takeAction('skipChangeForm');
    };
    KingOfTokyo.prototype.buyCard = function (id, from, useSuperiorAlienTechnology, useBobbingForApples) {
        if (useSuperiorAlienTechnology === void 0) { useSuperiorAlienTechnology = false; }
        if (useBobbingForApples === void 0) { useBobbingForApples = false; }
        if (!this.checkAction('buyCard')) {
            return;
        }
        this.takeAction('buyCard', {
            id: id,
            from: from,
            useSuperiorAlienTechnology: useSuperiorAlienTechnology,
            useBobbingForApples: useBobbingForApples
        });
    };
    KingOfTokyo.prototype.buyCardBamboozle = function (id, from) {
        if (!this.checkAction('buyCardBamboozle')) {
            return;
        }
        this.takeAction('buyCardBamboozle', {
            id: id,
            from: from
        });
    };
    KingOfTokyo.prototype.chooseMimickedCard = function (id) {
        if (!this.checkAction('chooseMimickedCard')) {
            return;
        }
        this.takeAction('chooseMimickedCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.chooseMimickedEvolution = function (id) {
        if (!this.checkAction('chooseMimickedEvolution')) {
            return;
        }
        this.takeAction('chooseMimickedEvolution', {
            id: id
        });
    };
    KingOfTokyo.prototype.changeMimickedCard = function (id) {
        if (!this.checkAction('changeMimickedCard')) {
            return;
        }
        this.takeAction('changeMimickedCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.chooseMimickedCardWickednessTile = function (id) {
        if (!this.checkAction('chooseMimickedCardWickednessTile')) {
            return;
        }
        this.takeAction('chooseMimickedCardWickednessTile', {
            id: id
        });
    };
    KingOfTokyo.prototype.changeMimickedCardWickednessTile = function (id) {
        if (!this.checkAction('changeMimickedCardWickednessTile')) {
            return;
        }
        this.takeAction('changeMimickedCardWickednessTile', {
            id: id
        });
    };
    KingOfTokyo.prototype.sellCard = function (id) {
        if (!this.checkAction('sellCard')) {
            return;
        }
        this.takeAction('sellCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.onRenew = function (cardType) {
        if (!this.checkAction('renew')) {
            return;
        }
        this.takeAction('renew', {
            cardType: cardType
        });
    };
    KingOfTokyo.prototype.skipCardIsBought = function () {
        if (!this.checkAction('skipCardIsBought')) {
            return;
        }
        this.takeAction('skipCardIsBought');
    };
    KingOfTokyo.prototype.goToSellCard = function () {
        if (!this.checkAction('goToSellCard', true)) {
            return;
        }
        this.takeAction('goToSellCard');
    };
    KingOfTokyo.prototype.opportunistSkip = function () {
        if (!this.checkAction('opportunistSkip', true)) {
            return;
        }
        this.takeAction('opportunistSkip');
    };
    KingOfTokyo.prototype.changeActivePlayerDieSkip = function () {
        if (!this.checkAction('changeActivePlayerDieSkip')) {
            return;
        }
        this.takeAction('changeActivePlayerDieSkip');
    };
    KingOfTokyo.prototype.skipChangeMimickedCard = function () {
        if (!this.checkAction('skipChangeMimickedCard', true)) {
            return;
        }
        this.takeAction('skipChangeMimickedCard');
    };
    KingOfTokyo.prototype.skipChangeMimickedCardWickednessTile = function () {
        if (!this.checkAction('skipChangeMimickedCardWickednessTile', true)) {
            return;
        }
        this.takeAction('skipChangeMimickedCardWickednessTile');
    };
    KingOfTokyo.prototype.endStealCostume = function () {
        if (!this.checkAction('endStealCostume')) {
            return;
        }
        this.takeAction('endStealCostume');
    };
    KingOfTokyo.prototype.onEndTurn = function () {
        if (!this.checkAction('endTurn')) {
            return;
        }
        this.takeAction('endTurn');
    };
    KingOfTokyo.prototype.throwCamouflageDice = function () {
        if (!this.checkAction('throwCamouflageDice')) {
            return;
        }
        this.takeAction('throwCamouflageDice');
    };
    KingOfTokyo.prototype.useWings = function () {
        if (!this.checkAction('useWings')) {
            return;
        }
        this.takeAction('useWings');
    };
    KingOfTokyo.prototype.useInvincibleEvolution = function (evolutionType) {
        if (!this.checkAction('useInvincibleEvolution')) {
            return;
        }
        this.takeAction('useInvincibleEvolution', {
            evolutionType: evolutionType
        });
    };
    KingOfTokyo.prototype.useCandyEvolution = function () {
        if (!this.checkAction('useCandyEvolution')) {
            return;
        }
        this.takeAction('useCandyEvolution');
    };
    KingOfTokyo.prototype.skipWings = function () {
        if (!this.checkAction('skipWings')) {
            return;
        }
        this.takeAction('skipWings');
    };
    KingOfTokyo.prototype.useRobot = function (energy) {
        if (!this.checkAction('useRobot')) {
            return;
        }
        this.takeAction('useRobot', {
            energy: energy
        });
    };
    KingOfTokyo.prototype.useSuperJump = function (energy) {
        if (!this.checkAction('useSuperJump')) {
            return;
        }
        this.takeAction('useSuperJump', {
            energy: energy
        });
    };
    KingOfTokyo.prototype.useRapidHealingSync = function (cultistCount, rapidHealingCount) {
        if (!this.checkAction('useRapidHealingSync')) {
            return;
        }
        this.takeAction('useRapidHealingSync', {
            cultistCount: cultistCount,
            rapidHealingCount: rapidHealingCount
        });
    };
    KingOfTokyo.prototype.setLeaveTokyoUnder = function (under) {
        this.takeNoLockAction('setLeaveTokyoUnder', {
            under: under
        });
    };
    KingOfTokyo.prototype.setStayTokyoOver = function (over) {
        this.takeNoLockAction('setStayTokyoOver', {
            over: over
        });
    };
    KingOfTokyo.prototype.setAskPlayEvolution = function (value) {
        this.takeNoLockAction('setAskPlayEvolution', {
            value: value
        });
    };
    KingOfTokyo.prototype.exchangeCard = function (id) {
        if (!this.checkAction('exchangeCard')) {
            return;
        }
        this.takeAction('exchangeCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.skipExchangeCard = function () {
        if (!this.checkAction('skipExchangeCard')) {
            return;
        }
        this.takeAction('skipExchangeCard');
    };
    KingOfTokyo.prototype.stayInHibernation = function () {
        if (!this.checkAction('stayInHibernation')) {
            return;
        }
        this.takeAction('stayInHibernation');
    };
    KingOfTokyo.prototype.leaveHibernation = function () {
        if (!this.checkAction('leaveHibernation')) {
            return;
        }
        this.takeAction('leaveHibernation');
    };
    KingOfTokyo.prototype.playEvolution = function (id) {
        this.takeNoLockAction('playEvolution', {
            id: id
        });
    };
    KingOfTokyo.prototype.useYinYang = function () {
        if (!this.checkAction('useYinYang')) {
            return;
        }
        this.takeAction('useYinYang');
    };
    KingOfTokyo.prototype.putEnergyOnBambooSupply = function () {
        if (!this.checkAction('putEnergyOnBambooSupply')) {
            return;
        }
        this.takeAction('putEnergyOnBambooSupply');
    };
    KingOfTokyo.prototype.takeEnergyOnBambooSupply = function () {
        if (!this.checkAction('takeEnergyOnBambooSupply')) {
            return;
        }
        this.takeAction('takeEnergyOnBambooSupply');
    };
    KingOfTokyo.prototype.gazeOfTheSphinxDrawEvolution = function () {
        if (!this.checkAction('gazeOfTheSphinxDrawEvolution')) {
            return;
        }
        this.takeAction('gazeOfTheSphinxDrawEvolution');
    };
    KingOfTokyo.prototype.gazeOfTheSphinxGainEnergy = function () {
        if (!this.checkAction('gazeOfTheSphinxGainEnergy')) {
            return;
        }
        this.takeAction('gazeOfTheSphinxGainEnergy');
    };
    KingOfTokyo.prototype.gazeOfTheSphinxDiscardEvolution = function (id) {
        if (!this.checkAction('gazeOfTheSphinxDiscardEvolution')) {
            return;
        }
        this.takeAction('gazeOfTheSphinxDiscardEvolution', {
            id: id
        });
    };
    KingOfTokyo.prototype.gazeOfTheSphinxLoseEnergy = function () {
        if (!this.checkAction('gazeOfTheSphinxLoseEnergy')) {
            return;
        }
        this.takeAction('gazeOfTheSphinxLoseEnergy');
    };
    KingOfTokyo.prototype.useChestThumping = function (id) {
        if (!this.checkAction('useChestThumping')) {
            return;
        }
        this.takeAction('useChestThumping', {
            id: id
        });
    };
    KingOfTokyo.prototype.skipChestThumping = function () {
        if (!this.checkAction('skipChestThumping')) {
            return;
        }
        this.takeAction('skipChestThumping');
    };
    KingOfTokyo.prototype.chooseFreezeRayDieFace = function (symbol) {
        if (!this.checkAction('chooseFreezeRayDieFace')) {
            return;
        }
        this.takeAction('chooseFreezeRayDieFace', {
            symbol: symbol
        });
    };
    KingOfTokyo.prototype.useMiraculousCatch = function () {
        if (!this.checkAction('useMiraculousCatch')) {
            return;
        }
        this.takeAction('useMiraculousCatch');
    };
    KingOfTokyo.prototype.buyCardMiraculousCatch = function (useSuperiorAlienTechnology) {
        if (useSuperiorAlienTechnology === void 0) { useSuperiorAlienTechnology = false; }
        if (!this.checkAction('buyCardMiraculousCatch')) {
            return;
        }
        this.takeAction('buyCardMiraculousCatch', {
            useSuperiorAlienTechnology: useSuperiorAlienTechnology,
        });
    };
    KingOfTokyo.prototype.skipMiraculousCatch = function () {
        if (!this.checkAction('skipMiraculousCatch')) {
            return;
        }
        this.takeAction('skipMiraculousCatch');
    };
    KingOfTokyo.prototype.playCardDeepDive = function (id) {
        if (!this.checkAction('playCardDeepDive')) {
            return;
        }
        this.takeAction('playCardDeepDive', {
            id: id
        });
    };
    KingOfTokyo.prototype.useExoticArms = function () {
        if (!this.checkAction('useExoticArms')) {
            return;
        }
        this.takeAction('useExoticArms');
    };
    KingOfTokyo.prototype.skipExoticArms = function () {
        if (!this.checkAction('skipExoticArms')) {
            return;
        }
        this.takeAction('skipExoticArms');
    };
    KingOfTokyo.prototype.skipBeforeResolveDice = function () {
        if (!this.checkAction('skipBeforeResolveDice')) {
            return;
        }
        this.takeAction('skipBeforeResolveDice');
    };
    KingOfTokyo.prototype.giveTarget = function () {
        if (!this.checkAction('giveTarget')) {
            return;
        }
        this.takeAction('giveTarget');
    };
    KingOfTokyo.prototype.skipGiveTarget = function () {
        if (!this.checkAction('skipGiveTarget')) {
            return;
        }
        this.takeAction('skipGiveTarget');
    };
    KingOfTokyo.prototype.useLightningArmor = function () {
        if (!this.checkAction('useLightningArmor')) {
            return;
        }
        this.takeAction('useLightningArmor');
    };
    KingOfTokyo.prototype.skipLightningArmor = function () {
        if (!this.checkAction('skipLightningArmor')) {
            return;
        }
        this.takeAction('skipLightningArmor');
    };
    KingOfTokyo.prototype.answerEnergySword = function (use) {
        if (!this.checkAction('answerEnergySword')) {
            return;
        }
        this.takeAction('answerEnergySword', { use: use });
    };
    KingOfTokyo.prototype.answerSunkenTemple = function (use) {
        if (!this.checkAction('answerSunkenTemple')) {
            return;
        }
        this.takeAction('answerSunkenTemple', { use: use });
    };
    KingOfTokyo.prototype.answerElectricCarrot = function (choice) {
        if (!this.checkAction('answerElectricCarrot')) {
            return;
        }
        this.takeAction('answerElectricCarrot', { choice: choice });
    };
    KingOfTokyo.prototype.reserveCard = function (id) {
        if (!this.checkAction('reserveCard')) {
            return;
        }
        this.takeAction('reserveCard', { id: id });
    };
    KingOfTokyo.prototype.useFelineMotor = function () {
        if (!this.checkAction('useFelineMotor')) {
            return;
        }
        this.takeAction('useFelineMotor');
    };
    KingOfTokyo.prototype.throwDieSuperiorAlienTechnology = function () {
        if (!this.checkAction('throwDieSuperiorAlienTechnology')) {
            return;
        }
        this.takeAction('throwDieSuperiorAlienTechnology');
    };
    KingOfTokyo.prototype.freezeRayChooseOpponent = function (playerId) {
        if (!this.checkAction('freezeRayChooseOpponent')) {
            return;
        }
        this.takeAction('freezeRayChooseOpponent', { playerId: playerId });
    };
    KingOfTokyo.prototype.trickOrThreatLoseHearts = function () {
        if (!this.checkAction('trickOrThreatLoseHearts')) {
            return;
        }
        this.takeAction('trickOrThreatLoseHearts');
    };
    KingOfTokyo.prototype.takeAction = function (action, data) {
        data = data || {};
        data.lock = true;
        this.ajaxcall("/kingoftokyo/kingoftokyo/" + action + ".html", data, this, function () { });
    };
    KingOfTokyo.prototype.takeNoLockAction = function (action, data) {
        data = data || {};
        this.ajaxcall("/kingoftokyo/kingoftokyo/" + action + ".html", data, this, function () { });
    };
    KingOfTokyo.prototype.setFont = function (prefValue) {
        this.playerTables.forEach(function (playerTable) { return playerTable.setFont(prefValue); });
    };
    KingOfTokyo.prototype.startActionTimer = function (buttonId, time) {
        var _a;
        if (((_a = this.prefs[202]) === null || _a === void 0 ? void 0 : _a.value) === 2) {
            return;
        }
        var button = document.getElementById(buttonId);
        var actionTimerId = null;
        var _actionTimerLabel = button.innerHTML;
        var _actionTimerSeconds = time;
        var actionTimerFunction = function () {
            var button = document.getElementById(buttonId);
            if (button == null) {
                window.clearInterval(actionTimerId);
            }
            else if (_actionTimerSeconds-- > 1) {
                button.innerHTML = _actionTimerLabel + ' (' + _actionTimerSeconds + ')';
            }
            else {
                window.clearInterval(actionTimerId);
                button.click();
            }
        };
        actionTimerFunction();
        actionTimerId = window.setInterval(function () { return actionTimerFunction(); }, 1000);
    };
    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications
    /*
        setupNotifications:

        In this method, you associate each of your game notifications with your local method to handle it.

        Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                your pylos.game.php file.

    */
    KingOfTokyo.prototype.setupNotifications = function () {
        //log( 'notifications subscriptions setup' );
        var _this = this;
        var notifs = [
            ['pickMonster', 500],
            ['setInitialCards', 500],
            ['resolveNumberDice', ANIMATION_MS],
            ['resolveHealthDice', ANIMATION_MS],
            ['resolveHealingRay', ANIMATION_MS],
            ['resolveHealthDiceInTokyo', ANIMATION_MS],
            ['removeShrinkRayToken', ANIMATION_MS],
            ['removePoisonToken', ANIMATION_MS],
            ['resolveEnergyDice', ANIMATION_MS],
            ['resolveSmashDice', ANIMATION_MS],
            ['playerEliminated', ANIMATION_MS],
            ['playerEntersTokyo', ANIMATION_MS],
            ['renewCards', ANIMATION_MS],
            ['buyCard', ANIMATION_MS],
            ['reserveCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['useLightningArmor', ANIMATION_MS],
            ['changeDie', ANIMATION_MS],
            ['changeDice', ANIMATION_MS],
            ['rethrow3changeDie', ANIMATION_MS],
            ['changeCurseCard', ANIMATION_MS],
            ['takeWickednessTile', ANIMATION_MS],
            ['changeGoldenScarabOwner', ANIMATION_MS],
            ['discardedDie', ANIMATION_MS],
            ['exchangeCard', ANIMATION_MS],
            ['playEvolution', ANIMATION_MS],
            ['superiorAlienTechnologyRolledDie', ANIMATION_MS],
            ['superiorAlienTechnologyLog', ANIMATION_MS],
            ['resolvePlayerDice', 500],
            ['changeTokyoTowerOwner', 500],
            ['changeForm', 500],
            ['evolutionPickedForDeck', 500],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['maxHealth', 1],
            ['wickedness', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['setCardTokens', 1],
            ['setEvolutionTokens', 1],
            ['setTileTokens', 1],
            ['removeCards', 1],
            ['removeEvolutions', 1],
            ['setMimicToken', 1],
            ['setMimicEvolutionToken', 1],
            ['removeMimicToken', 1],
            ['removeMimicEvolutionToken', 1],
            ['toggleRapidHealing', 1],
            ['toggleMothershipSupport', 1],
            ['toggleMothershipSupportUsed', 1],
            ['updateLeaveTokyoUnder', 1],
            ['updateStayTokyoOver', 1],
            ['updateAskPlayEvolution', 1],
            ['kotPlayerEliminated', 1],
            ['setPlayerBerserk', 1],
            ['cultist', 1],
            ['removeWickednessTiles', 1],
            ['addEvolutionCardInHand', 1],
            ['addSuperiorAlienTechnologyToken', 1],
            ['giveTarget', 1],
            ['updateCancelDamage', 1],
            ['ownedEvolutions', 1],
            ['log500', 500]
        ];
        notifs.forEach(function (notif) {
            dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
            _this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
    };
    KingOfTokyo.prototype.notif_log500 = function () {
        // nothing, it's just for the delay
    };
    KingOfTokyo.prototype.notif_pickMonster = function (notif) {
        var _this = this;
        var monsterDiv = document.getElementById("pick-monster-figure-" + notif.args.monster);
        var destinationId = "player-board-monster-figure-" + notif.args.playerId;
        var animation = this.slideToObject(monsterDiv, destinationId);
        dojo.connect(animation, 'onEnd', dojo.hitch(this, function () {
            _this.fadeOutAndDestroy(monsterDiv);
            dojo.removeClass(destinationId, 'monster0');
            dojo.addClass(destinationId, "monster" + notif.args.monster);
        }));
        animation.play();
        this.getPlayerTable(notif.args.playerId).setMonster(notif.args.monster);
    };
    KingOfTokyo.prototype.notif_evolutionPickedForDeck = function (notif) {
        this.evolutionCards.moveToAnotherStock(this.choseEvolutionInStock, this.inDeckEvolutionsStock, notif.args.card);
    };
    KingOfTokyo.prototype.notif_setInitialCards = function (notif) {
        this.tableCenter.setInitialCards(notif.args.cards);
    };
    KingOfTokyo.prototype.notif_resolveNumberDice = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points, ANIMATION_MS);
        this.animationManager.resolveNumberDice(notif.args);
        this.diceManager.resolveNumberDice(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDice = function (notif) {
        this.animationManager.resolveHealthDice(notif.args.playerId, notif.args.deltaHealth);
        this.diceManager.resolveHealthDice(notif.args.deltaHealth);
    };
    KingOfTokyo.prototype.notif_resolveHealthDiceInTokyo = function (notif) {
        this.diceManager.resolveHealthDiceInTokyo();
    };
    KingOfTokyo.prototype.notif_resolveHealingRay = function (notif) {
        this.animationManager.resolveHealthDice(notif.args.healedPlayerId, notif.args.healNumber);
        this.diceManager.resolveHealthDice(notif.args.healNumber);
    };
    KingOfTokyo.prototype.notif_resolveEnergyDice = function (notif) {
        this.animationManager.resolveEnergyDice(notif.args);
        this.diceManager.resolveEnergyDice();
    };
    KingOfTokyo.prototype.notif_resolveSmashDice = function (notif) {
        this.animationManager.resolveSmashDice(notif.args);
        this.diceManager.resolveSmashDice();
        if (notif.args.smashedPlayersIds.length > 0) {
            for (var delayIndex = 0; delayIndex < notif.args.number; delayIndex++) {
                setTimeout(function () { return playSound('kot-punch'); }, ANIMATION_MS - (PUNCH_SOUND_DURATION * delayIndex - 1));
            }
        }
    };
    KingOfTokyo.prototype.notif_playerEliminated = function (notif) {
        var playerId = Number(notif.args.who_quits);
        this.setPoints(playerId, 0);
        this.eliminatePlayer(playerId);
    };
    KingOfTokyo.prototype.notif_kotPlayerEliminated = function (notif) {
        this.notif_playerEliminated(notif);
    };
    KingOfTokyo.prototype.notif_leaveTokyo = function (notif) {
        this.getPlayerTable(notif.args.playerId).leaveTokyo();
        dojo.removeClass("overall_player_board_" + notif.args.playerId, 'intokyo');
        dojo.removeClass("monster-board-wrapper-" + notif.args.playerId, 'intokyo');
        if (notif.args.playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }
        if (this.smashedPlayersStillInTokyo) {
            this.smashedPlayersStillInTokyo = this.smashedPlayersStillInTokyo.filter(function (playerId) { return playerId != notif.args.playerId; });
        }
        var useChestThumpingButton = document.getElementById("useChestThumping_button" + notif.args.playerId);
        useChestThumpingButton === null || useChestThumpingButton === void 0 ? void 0 : useChestThumpingButton.parentElement.removeChild(useChestThumpingButton);
    };
    KingOfTokyo.prototype.notif_playerEntersTokyo = function (notif) {
        this.getPlayerTable(notif.args.playerId).enterTokyo(notif.args.location);
        dojo.addClass("overall_player_board_" + notif.args.playerId, 'intokyo');
        dojo.addClass("monster-board-wrapper-" + notif.args.playerId, 'intokyo');
        if (notif.args.playerId == this.getPlayerId()) {
            this.addAutoLeaveUnderButton();
        }
    };
    KingOfTokyo.prototype.notif_buyCard = function (notif) {
        var card = notif.args.card;
        this.tableCenter.changeVisibleCardWeight(card);
        if (notif.args.energy !== undefined) {
            this.setEnergy(notif.args.playerId, notif.args.energy);
        }
        if (notif.args.discardCard) { // initial card
            this.cards.moveToAnotherStock(this.tableCenter.getVisibleCards(), this.getPlayerTable(notif.args.playerId).cards, card);
            this.tableCenter.getVisibleCards().removeFromStockById('' + notif.args.discardCard.id);
        }
        else if (notif.args.newCard) {
            var newCard = notif.args.newCard;
            this.cards.moveToAnotherStock(this.tableCenter.getVisibleCards(), this.getPlayerTable(notif.args.playerId).cards, card);
            this.cards.addCardsToStock(this.tableCenter.getVisibleCards(), [newCard], 'deck');
            this.tableCenter.changeVisibleCardWeight(newCard);
        }
        else if (notif.args.from > 0) {
            this.cards.moveToAnotherStock(notif.args.from == notif.args.playerId ? this.getPlayerTable(notif.args.playerId).reservedCards : this.getPlayerTable(notif.args.from).cards, this.getPlayerTable(notif.args.playerId).cards, card);
        }
        else { // from Made in a lab Pick
            if (this.tableCenter.getPickCard()) { // active player
                this.cards.moveToAnotherStock(this.tableCenter.getPickCard(), this.getPlayerTable(notif.args.playerId).cards, card);
            }
            else {
                this.cards.addCardsToStock(this.getPlayerTable(notif.args.playerId).cards, [card], 'deck');
            }
        }
        this.tableCenter.setTopDeckCardBackType(notif.args.topDeckCardBackType);
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_reserveCard = function (notif) {
        var card = notif.args.card;
        this.tableCenter.changeVisibleCardWeight(card);
        var newCard = notif.args.newCard;
        this.cards.moveToAnotherStock(this.tableCenter.getVisibleCards(), this.getPlayerTable(notif.args.playerId).reservedCards, card); // TODOPUBG add under evolution
        this.cards.addCardsToStock(this.tableCenter.getVisibleCards(), [newCard], 'deck');
        this.tableCenter.changeVisibleCardWeight(newCard);
        this.tableCenter.setTopDeckCardBackType(notif.args.topDeckCardBackType);
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeCards = function (notif) {
        var _this = this;
        if (notif.args.delay) {
            notif.args.delay = false;
            setTimeout(function () { return _this.notif_removeCards(notif); }, ANIMATION_MS);
        }
        else {
            this.getPlayerTable(notif.args.playerId).removeCards(notif.args.cards);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    };
    KingOfTokyo.prototype.notif_removeEvolutions = function (notif) {
        var _this = this;
        if (notif.args.delay) {
            setTimeout(function () { return _this.notif_removeEvolutions({
                args: __assign(__assign({}, notif.args), { delay: 0 })
            }); }, notif.args.delay);
        }
        else {
            this.getPlayerTable(notif.args.playerId).removeEvolutions(notif.args.cards);
            this.handCounters[notif.args.playerId].incValue(-notif.args.cards.filter(function (card) { return card.location === 'hand'; }).length);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    };
    KingOfTokyo.prototype.notif_setMimicToken = function (notif) {
        this.setMimicToken(notif.args.type, notif.args.card);
    };
    KingOfTokyo.prototype.notif_removeMimicToken = function (notif) {
        this.removeMimicToken(notif.args.type, notif.args.card);
    };
    KingOfTokyo.prototype.notif_removeMimicEvolutionToken = function (notif) {
        this.removeMimicEvolutionToken(notif.args.card);
    };
    KingOfTokyo.prototype.notif_setMimicEvolutionToken = function (notif) {
        this.setMimicEvolutionToken(notif.args.card);
    };
    KingOfTokyo.prototype.notif_renewCards = function (notif) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
        this.tableCenter.renewCards(notif.args.cards, notif.args.topDeckCardBackType);
    };
    KingOfTokyo.prototype.notif_points = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points);
    };
    KingOfTokyo.prototype.notif_health = function (notif) {
        this.setHealth(notif.args.playerId, notif.args.health);
        /*const rapidHealingSyncButton = document.getElementById('rapidHealingSync_button');
        if (rapidHealingSyncButton && notif.args.playerId === this.getPlayerId()) {
            this.rapidHealingSyncHearts = Math.max(0, this.rapidHealingSyncHearts - notif.args.delta_health);
            rapidHealingSyncButton.innerHTML = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (${2*this.rapidHealingSyncHearts}[Energy])`), { 'card_name': this.cards.getCardName(37, 'text-only'), 'hearts': this.rapidHealingSyncHearts });
        }*/
    };
    KingOfTokyo.prototype.notif_maxHealth = function (notif) {
        this.setMaxHealth(notif.args.playerId, notif.args.maxHealth);
        this.setHealth(notif.args.playerId, notif.args.health);
    };
    KingOfTokyo.prototype.notif_energy = function (notif) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
    };
    KingOfTokyo.prototype.notif_wickedness = function (notif) {
        this.setWickedness(notif.args.playerId, notif.args.wickedness);
    };
    KingOfTokyo.prototype.notif_shrinkRayToken = function (notif) {
        this.setShrinkRayTokens(notif.args.playerId, notif.args.tokens);
    };
    KingOfTokyo.prototype.notif_poisonToken = function (notif) {
        this.setPoisonTokens(notif.args.playerId, notif.args.tokens);
    };
    KingOfTokyo.prototype.notif_removeShrinkRayToken = function (notif) {
        var _this = this;
        this.animationManager.resolveHealthDice(notif.args.playerId, notif.args.deltaTokens, 'shrink-ray');
        this.diceManager.resolveHealthDice(notif.args.deltaTokens);
        setTimeout(function () { return _this.notif_shrinkRayToken(notif); }, ANIMATION_MS);
    };
    KingOfTokyo.prototype.notif_removePoisonToken = function (notif) {
        var _this = this;
        this.animationManager.resolveHealthDice(notif.args.playerId, notif.args.deltaTokens, 'poison');
        this.diceManager.resolveHealthDice(notif.args.deltaTokens);
        setTimeout(function () { return _this.notif_poisonToken(notif); }, ANIMATION_MS);
    };
    KingOfTokyo.prototype.notif_setCardTokens = function (notif) {
        this.cards.placeTokensOnCard(this.getPlayerTable(notif.args.playerId).cards, notif.args.card, notif.args.playerId);
    };
    KingOfTokyo.prototype.notif_setEvolutionTokens = function (notif) {
        this.evolutionCards.placeTokensOnCard(this.getPlayerTable(notif.args.playerId).visibleEvolutionCards, notif.args.card, notif.args.playerId);
    };
    KingOfTokyo.prototype.notif_setTileTokens = function (notif) {
        this.wickednessTiles.placeTokensOnTile(this.getPlayerTable(notif.args.playerId).wickednessTiles, notif.args.card, notif.args.playerId);
    };
    KingOfTokyo.prototype.notif_toggleRapidHealing = function (notif) {
        if (notif.args.active) {
            this.addRapidHealingButton(notif.args.playerEnergy, notif.args.isMaxHealth);
        }
        else {
            this.removeRapidHealingButton();
        }
    };
    KingOfTokyo.prototype.notif_toggleMothershipSupport = function (notif) {
        if (notif.args.active) {
            this.addMothershipSupportButton(notif.args.playerEnergy, notif.args.isMaxHealth);
        }
        else {
            this.removeMothershipSupportButton();
        }
    };
    KingOfTokyo.prototype.notif_toggleMothershipSupportUsed = function (notif) {
        this.gamedatas.players[notif.args.playerId].mothershipSupportUsed = notif.args.used;
        this.checkMothershipSupportButtonState();
    };
    KingOfTokyo.prototype.notif_useCamouflage = function (notif) {
        this.notif_updateCancelDamage(notif);
        this.diceManager.showCamouflageRoll(notif.args.diceValues);
    };
    KingOfTokyo.prototype.notif_updateCancelDamage = function (notif) {
        if (notif.args.cancelDamageArgs) {
            this.gamedatas.gamestate.args = notif.args.cancelDamageArgs;
            this.updatePageTitle();
            this.onEnteringCancelDamage(notif.args.cancelDamageArgs, this.isCurrentPlayerActive());
        }
    };
    KingOfTokyo.prototype.notif_useLightningArmor = function (notif) {
        this.diceManager.showCamouflageRoll(notif.args.diceValues);
    };
    KingOfTokyo.prototype.notif_changeDie = function (notif) {
        if (notif.args.psychicProbeRollDieArgs) {
            this.onEnteringPsychicProbeRollDie(notif.args.psychicProbeRollDieArgs);
        }
        else {
            this.diceManager.changeDie(notif.args.dieId, notif.args.canHealWithDice, notif.args.toValue, notif.args.roll);
        }
    };
    KingOfTokyo.prototype.notif_rethrow3changeDie = function (notif) {
        this.diceManager.changeDie(notif.args.dieId, notif.args.canHealWithDice, notif.args.toValue, notif.args.roll);
    };
    KingOfTokyo.prototype.notif_changeDice = function (notif) {
        var _this = this;
        Object.keys(notif.args.dieIdsToValues).forEach(function (key) {
            return _this.diceManager.changeDie(Number(key), notif.args.canHealWithDice, notif.args.dieIdsToValues[key], false);
        });
    };
    KingOfTokyo.prototype.notif_resolvePlayerDice = function () {
        this.diceManager.lockAll();
    };
    KingOfTokyo.prototype.notif_updateLeaveTokyoUnder = function (notif) {
        dojo.query('.autoLeaveButton').removeClass('bgabutton_blue');
        dojo.query('.autoLeaveButton').addClass('bgabutton_gray');
        var popinId = "discussion_bubble_autoLeaveUnder";
        if (document.getElementById(popinId + "_set" + notif.args.under)) {
            dojo.removeClass(popinId + "_set" + notif.args.under, 'bgabutton_gray');
            dojo.addClass(popinId + "_set" + notif.args.under, 'bgabutton_blue');
        }
        for (var i = 1; i <= 15; i++) {
            if (document.getElementById(popinId + "_setStay" + i)) {
                dojo.toggleClass(popinId + "_setStay" + i, 'disabled', notif.args.under > 0 && i <= notif.args.under);
            }
        }
    };
    KingOfTokyo.prototype.notif_updateStayTokyoOver = function (notif) {
        dojo.query('.autoStayButton').removeClass('bgabutton_blue');
        dojo.query('.autoStayButton').addClass('bgabutton_gray');
        var popinId = "discussion_bubble_autoLeaveUnder";
        if (document.getElementById(popinId + "_setStay" + notif.args.over)) {
            dojo.removeClass(popinId + "_setStay" + notif.args.over, 'bgabutton_gray');
            dojo.addClass(popinId + "_setStay" + notif.args.over, 'bgabutton_blue');
        }
    };
    KingOfTokyo.prototype.notif_updateAskPlayEvolution = function (notif) {
        document.querySelector("input[name=\"autoSkipPlayEvolution\"][value=\"" + notif.args.value + "\"]").checked = true;
    };
    KingOfTokyo.prototype.notif_changeTokyoTowerOwner = function (notif) {
        var playerId = notif.args.playerId;
        var previousOwner = this.towerLevelsOwners[notif.args.level];
        this.towerLevelsOwners[notif.args.level] = playerId;
        var newLevelTower = playerId == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(playerId).getTokyoTower();
        transitionToObjectAndAttach(this, document.getElementById("tokyo-tower-level" + notif.args.level), newLevelTower.divId + "-level" + notif.args.level, this.getZoom());
        if (previousOwner != 0) {
            document.getElementById("tokyo-tower-icon-" + previousOwner + "-level-" + notif.args.level).dataset.owned = 'false';
        }
        if (playerId != 0) {
            document.getElementById("tokyo-tower-icon-" + playerId + "-level-" + notif.args.level).dataset.owned = 'true';
        }
    };
    KingOfTokyo.prototype.notif_setPlayerBerserk = function (notif) {
        this.getPlayerTable(notif.args.playerId).setBerserk(notif.args.berserk);
        dojo.toggleClass("player-panel-berserk-" + notif.args.playerId, 'active', notif.args.berserk);
    };
    KingOfTokyo.prototype.notif_changeForm = function (notif) {
        this.getPlayerTable(notif.args.playerId).changeForm(notif.args.card);
        this.setEnergy(notif.args.playerId, notif.args.energy);
    };
    KingOfTokyo.prototype.notif_cultist = function (notif) {
        this.setCultists(notif.args.playerId, notif.args.cultists, notif.args.isMaxHealth);
    };
    KingOfTokyo.prototype.notif_changeCurseCard = function (notif) {
        this.tableCenter.changeCurseCard(notif.args.card);
    };
    KingOfTokyo.prototype.notif_takeWickednessTile = function (notif) {
        var tile = notif.args.tile;
        this.wickednessTiles.addCardsToStock(this.getPlayerTable(notif.args.playerId).wickednessTiles, [tile], "wickedness-tiles-pile-tile-" + tile.id);
        this.tableCenter.removeWickednessTileFromPile(notif.args.level, tile);
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeWickednessTiles = function (notif) {
        this.getPlayerTable(notif.args.playerId).removeWickednessTiles(notif.args.tiles);
        this.tableManager.tableHeightChange(); // adapt after removed cards
    };
    KingOfTokyo.prototype.notif_changeGoldenScarabOwner = function (notif) {
        this.getPlayerTable(notif.args.playerId).takeGoldenScarab(this.getPlayerTable(notif.args.previousOwner).cards);
        this.tableManager.tableHeightChange(); // adapt after moved card
    };
    KingOfTokyo.prototype.notif_discardedDie = function (notif) {
        this.diceManager.discardDie(notif.args.die);
    };
    KingOfTokyo.prototype.notif_exchangeCard = function (notif) {
        this.cards.exchangeCardFromStocks(this.getPlayerTable(notif.args.playerId).cards, this.getPlayerTable(notif.args.previousOwner).cards, notif.args.unstableDnaCard, notif.args.exchangedCard);
    };
    KingOfTokyo.prototype.notif_addEvolutionCardInHand = function (notif) {
        var playerId = notif.args.playerId;
        var card = notif.args.card;
        var isCurrentPlayer = this.getPlayerId() === playerId;
        var playerTable = this.getPlayerTable(playerId);
        if (isCurrentPlayer) {
            if (card === null || card === void 0 ? void 0 : card.type) {
                playerTable.hiddenEvolutionCards.addToStockWithId(card.type, '' + card.id);
            }
        }
        else if (card === null || card === void 0 ? void 0 : card.id) {
            playerTable.hiddenEvolutionCards.addToStockWithId(0, '' + card.id);
        }
        if (!card || !card.type) {
            this.handCounters[playerId].incValue(1);
        }
        playerTable === null || playerTable === void 0 ? void 0 : playerTable.checkHandEmpty();
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_playEvolution = function (notif) {
        this.handCounters[notif.args.playerId].incValue(-1);
        var fromStock = null;
        if (notif.args.fromPlayerId) {
            fromStock = this.getPlayerTable(notif.args.fromPlayerId).visibleEvolutionCards;
        }
        this.getPlayerTable(notif.args.playerId).playEvolution(notif.args.card, fromStock);
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_addSuperiorAlienTechnologyToken = function (notif) {
        var stock = this.getPlayerTable(notif.args.playerId).cards;
        this.cards.placeSuperiorAlienTechnologyTokenOnCard(stock, notif.args.card);
    };
    KingOfTokyo.prototype.notif_giveTarget = function (notif) {
        if (notif.args.previousOwner) {
            this.getPlayerTable(notif.args.previousOwner).removeTarget();
            this.setPlayerTokens(notif.args.previousOwner, 0, 'target');
        }
        this.getPlayerTable(notif.args.playerId).giveTarget();
        this.setPlayerTokens(notif.args.playerId, 1, 'target');
    };
    KingOfTokyo.prototype.notif_ownedEvolutions = function (notif) {
        this.gamedatas.players[notif.args.playerId].ownedEvolutions = notif.args.evolutions;
    };
    KingOfTokyo.prototype.setTitleBarSuperiorAlienTechnologyCard = function (card, parent) {
        if (parent === void 0) { parent = "maintitlebar_content"; }
        var superiorAlienTechnologyCard = this.cards.generateCardDiv(card);
        superiorAlienTechnologyCard.id = "SuperiorAlienTechnology-card-" + card.id;
        dojo.place("<div id=\"card-SuperiorAlienTechnology-wrapper\" class=\"card-in-title-wrapper\">" + superiorAlienTechnologyCard.outerHTML + "</div>", parent);
    };
    KingOfTokyo.prototype.notif_superiorAlienTechnologyRolledDie = function (notif) {
        this.setTitleBarSuperiorAlienTechnologyCard(notif.args.card, 'gameaction_status_wrap');
        this.setDiceSelectorVisibility(true);
        this.diceManager.showCamouflageRoll([{
                id: 0,
                value: notif.args.dieValue,
                extra: false,
                locked: false,
                rolled: true,
                type: 0,
                canReroll: true,
            }]);
    };
    KingOfTokyo.prototype.notif_superiorAlienTechnologyLog = function (notif) {
        //this.setTitleBarSuperiorAlienTechnologyCard(notif.args.card, 'gameaction_status_wrap');
        if (document.getElementById('dice0')) {
            var message = notif.args.dieValue == 6 ?
                _('<strong>${card_name}</strong> card removed!') :
                _('<strong>${card_name}</strong> card kept!');
            this.doShowBubble('dice0', dojo.string.substitute(message, {
                'card_name': this.cards.getCardName(notif.args.card.type, 'text-only')
            }), 'superiorAlienTechnologyBubble');
        }
    };
    KingOfTokyo.prototype.setPoints = function (playerId, points, delay) {
        var _a;
        if (delay === void 0) { delay = 0; }
        (_a = this.scoreCtrl[playerId]) === null || _a === void 0 ? void 0 : _a.toValue(points);
        this.getPlayerTable(playerId).setPoints(points, delay);
    };
    KingOfTokyo.prototype.setHealth = function (playerId, health, delay) {
        if (delay === void 0) { delay = 0; }
        this.healthCounters[playerId].toValue(health);
        this.getPlayerTable(playerId).setHealth(health, delay);
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.checkHealthCultistButtonState();
    };
    KingOfTokyo.prototype.setMaxHealth = function (playerId, maxHealth) {
        this.gamedatas.players[playerId].maxHealth = maxHealth;
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.checkHealthCultistButtonState();
        var popinId = "discussion_bubble_autoLeaveUnder";
        if (document.getElementById(popinId)) {
            this.updateAutoLeavePopinButtons();
        }
    };
    KingOfTokyo.prototype.getPlayerEnergy = function (playerId) {
        return this.energyCounters[playerId].getValue();
    };
    KingOfTokyo.prototype.setEnergy = function (playerId, energy, delay) {
        if (delay === void 0) { delay = 0; }
        this.energyCounters[playerId].toValue(energy);
        this.getPlayerTable(playerId).setEnergy(energy, delay);
        this.checkBuyEnergyDrinkState(energy); // disable button if energy gets down to 0
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.setBuyDisabledCard(null, energy);
        Array.from(document.querySelectorAll("[data-enable-at-energy]")).forEach(function (button) {
            var enableAtEnergy = Number(button.dataset.enableAtEnergy);
            dojo.toggleClass(button, 'disabled', energy < enableAtEnergy);
        });
    };
    KingOfTokyo.prototype.setWickedness = function (playerId, wickedness) {
        this.wickednessCounters[playerId].toValue(wickedness);
        this.tableCenter.setWickedness(playerId, wickedness);
    };
    KingOfTokyo.prototype.setPlayerTokens = function (playerId, tokens, tokenName) {
        var containerId = "player-board-" + tokenName + "-tokens-" + playerId;
        var container = document.getElementById(containerId);
        while (container.childElementCount > tokens) {
            container.removeChild(container.lastChild);
        }
        for (var i = container.childElementCount; i < tokens; i++) {
            dojo.place("<div class=\"" + tokenName + " token\"></div>", containerId);
        }
    };
    KingOfTokyo.prototype.setShrinkRayTokens = function (playerId, tokens) {
        var _a;
        this.setPlayerTokens(playerId, tokens, 'shrink-ray');
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.setShrinkRayTokens(tokens);
    };
    KingOfTokyo.prototype.setPoisonTokens = function (playerId, tokens) {
        var _a;
        this.setPlayerTokens(playerId, tokens, 'poison');
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.setPoisonTokens(tokens);
    };
    KingOfTokyo.prototype.setCultists = function (playerId, cultists, isMaxHealth) {
        var _a;
        this.cultistCounters[playerId].toValue(cultists);
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.setCultistTokens(cultists);
        if (playerId == this.getPlayerId()) {
            if (cultists > 0) {
                this.addRapidCultistButtons(isMaxHealth);
            }
            else {
                this.removeRapidCultistButtons();
                if (document.getElementById('use_cultist_button')) {
                    dojo.addClass('use_cultist_button', 'disabled');
                }
            }
        }
    };
    KingOfTokyo.prototype.checkBuyEnergyDrinkState = function (energy) {
        if (energy === void 0) { energy = null; }
        if (document.getElementById('buy_energy_drink_button')) {
            if (energy === null) {
                energy = this.energyCounters[this.getPlayerId()].getValue();
            }
            dojo.toggleClass('buy_energy_drink_button', 'disabled', energy < 1 || !this.diceManager.canRethrow());
        }
    };
    KingOfTokyo.prototype.checkUseSmokeCloudState = function () {
        if (document.getElementById('use_smoke_cloud_button')) {
            dojo.toggleClass('use_smoke_cloud_button', 'disabled', !this.diceManager.canRethrow());
        }
    };
    KingOfTokyo.prototype.checkUseCultistState = function () {
        if (document.getElementById('use_cultist_button')) {
            dojo.toggleClass('use_cultist_button', 'disabled', !this.diceManager.canRethrow());
        }
    };
    KingOfTokyo.prototype.eliminatePlayer = function (playerId) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById("overall_player_board_" + playerId).classList.add('eliminated-player');
        if (!document.getElementById("dead-icon-" + playerId)) {
            dojo.place("<div id=\"dead-icon-" + playerId + "\" class=\"icon dead\"></div>", "player_board_" + playerId);
        }
        this.getPlayerTable(playerId).eliminatePlayer();
        this.tableManager.tableHeightChange(); // because all player's card were removed
        if (document.getElementById("player-board-monster-figure-" + playerId)) {
            this.fadeOutAndDestroy("player-board-monster-figure-" + playerId);
        }
        dojo.removeClass("overall_player_board_" + playerId, 'intokyo');
        dojo.removeClass("monster-board-wrapper-" + playerId, 'intokyo');
        if (playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }
        this.setShrinkRayTokens(playerId, 0);
        this.setPoisonTokens(playerId, 0);
        if (this.isCthulhuExpansion()) {
            this.setCultists(playerId, 0, false);
        }
    };
    KingOfTokyo.prototype.getLogCardName = function (logType) {
        if (logType >= 3000) {
            return this.evolutionCards.getCardName(logType - 3000, 'text-only');
        }
        else if (logType >= 2000) {
            return this.wickednessTiles.getCardName(logType - 2000);
        }
        else if (logType >= 1000) {
            return this.curseCards.getCardName(logType - 1000);
        }
        else {
            return this.cards.getCardName(logType, 'text-only');
        }
    };
    KingOfTokyo.prototype.getLogCardTooltip = function (logType) {
        if (logType >= 3000) {
            return this.evolutionCards.getTooltip(logType - 3000);
        }
        else if (logType >= 2000) {
            return this.wickednessTiles.getTooltip(logType - 2000);
        }
        else if (logType >= 1000) {
            return this.curseCards.getTooltip(logType - 1000);
        }
        else {
            return this.cards.getTooltip(logType);
        }
    };
    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    KingOfTokyo.prototype.format_string_recursive = function (log, args) {
        var _this = this;
        var _a, _b;
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                ['card_name', 'card_name2'].forEach(function (cardArg) {
                    if (args[cardArg]) {
                        var types = null;
                        if (typeof args[cardArg] == 'number') {
                            types = [args[cardArg]];
                        }
                        else if (typeof args[cardArg] == 'string' && args[cardArg][0] >= '0' && args[cardArg][0] <= '9') {
                            types = args[cardArg].split(',').map(function (cardType) { return Number(cardType); });
                        }
                        if (types !== null) {
                            var tags = types.map(function (cardType) {
                                var cardLogId = _this.cardLogId++;
                                setTimeout(function () { return _this.addTooltipHtml("card-log-" + cardLogId, _this.getLogCardTooltip(cardType)); }, 500);
                                return "<strong id=\"card-log-" + cardLogId + "\" data-log-type=\"" + cardType + "\">" + _this.getLogCardName(cardType) + "</strong>";
                            });
                            args[cardArg] = tags.join(', ');
                        }
                    }
                });
                for (var property in args) {
                    if (((_b = (_a = args[property]) === null || _a === void 0 ? void 0 : _a.indexOf) === null || _b === void 0 ? void 0 : _b.call(_a, ']')) > 0) {
                        args[property] = formatTextIcons(_(args[property]));
                    }
                }
                if (args.player_name && typeof args.player_name[0] === 'string' && args.player_name.indexOf('<') === -1) {
                    var player = Object.values(this.gamedatas.players).find(function (player) { return player.name == args.player_name; });
                    args.player_name = "<span style=\"font-weight:bold;color:#" + player.color + ";\">" + args.player_name + "</span>";
                }
                if (args.symbolsToGive && typeof args.symbolsToGive === 'object') {
                    var symbolsStr = args.symbolsToGive.map(function (symbol) { return SYMBOL_AS_STRING_PADDED[symbol]; });
                    args.symbolsToGive = formatTextIcons(_('${symbol1} or ${symbol2}')
                        .replace('${symbol1}', symbolsStr.slice(0, symbolsStr.length - 1).join(', '))
                        .replace('${symbol2}', symbolsStr[symbolsStr.length - 1]));
                }
                log = formatTextIcons(_(log));
            }
        }
        catch (e) {
            console.error(log, args, "Exception thrown", e.stack);
        }
        return this.inherited(arguments);
    };
    return KingOfTokyo;
}());
define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
], function (dojo, declare) {
    return declare("bgagame.kingoftokyo", ebg.core.gamegui, new KingOfTokyo());
});
