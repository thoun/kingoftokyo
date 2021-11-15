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
        .replace(/\[keep\]/ig, "<span class=\"card-keep-text\"><span class=\"outline\">" + _('Keep') + "</span><span class=\"text\">" + _('Keep') + "</span></span>");
}
var CARD_WIDTH = 132;
var CARD_HEIGHT = 185;
var KEEP_CARDS_LIST = {
    base: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48],
    dark: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25, 26, 29, 30, 31, 32, 33, 34, 36, 37, 38, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55],
};
var DISCARD_CARDS_LIST = {
    base: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
    dark: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 15, 16, 17, 18, 19],
};
var COSTUME_CARDS_LIST = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
var TRANSFORMATION_CARDS_LIST = [1, 2];
var Cards = /** @class */ (function () {
    function Cards(game) {
        this.game = game;
    }
    Cards.prototype.setupCards = function (stocks) {
        var version = this.game.isDarkEdition() ? 'dark' : 'base';
        var costumes = this.game.isHalloweenExpansion();
        var transformation = this.game.isMutantEvolutionVariant();
        var goldenscarab = this.game.isAnubisExpansion();
        stocks.forEach(function (stock) {
            var keepcardsurl = g_gamethemeurl + "img/keep-cards.jpg";
            KEEP_CARDS_LIST[version].forEach(function (id, index) {
                stock.addItemType(id, id, keepcardsurl, index);
            });
            var discardcardsurl = g_gamethemeurl + "img/discard-cards.jpg";
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
    Cards.prototype.placeMimicOnCard = function (stock, card) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);
        var html = "<div id=\"" + divId + "-mimic-token\" style=\"left: " + (cardPlaced.mimicToken.x - 16) + "px; top: " + (cardPlaced.mimicToken.y - 16) + "px;\" class=\"card-token mimic token\"></div>";
        dojo.place(html, divId);
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    Cards.prototype.removeMimicOnCard = function (stock, card) {
        var divId = stock.container_div.id + "_item_" + card.id;
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.mimicToken = null;
        if (document.getElementById(divId + "-mimic-token")) {
            this.game.fadeOutAndDestroy(divId + "-mimic-token");
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
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
                html += "energy-cube";
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
        cards.forEach(function (card) { return stock.addToStockWithId(card.type + card.side, "" + card.id, from); });
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
    Cards.prototype.getCardNamePosition = function (cardTypeId) {
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
            // TRANSFORMATION   /* TODOME */         
            case 301:
            case 302: return [10, 15];
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
            case 50: return 3;
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
    Cards.prototype.getColoredCardName = function (cardTypeId) {
        switch (cardTypeId) {
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
            case 13:
            case 14: return _("[060D29]Extra [0C1946]Head");
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
            case 59: return "Hibernation"; // TODODE
            case 50: return "Nanobots"; // TODODE
            case 51: return "Natural Selection"; // TODODE
            case 52: return "Reflective Hide"; // TODODE
            case 53: return "Sumper Jump"; // TODODE
            case 54: return "Unstable DNA"; // TODODE
            case 55: return "Zombify"; // TODODE
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
            /* TODOME case 301: return _("[deaa26]Biped [72451c]Form");
            case 302: return _("[982620]Beast [de6526]Form");*/
        }
        return null;
    };
    Cards.prototype.getCardName = function (cardTypeId, state) {
        var coloredCardName = this.getColoredCardName(cardTypeId);
        if (state == 'text-only') {
            return coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, '');
        }
        else if (state == 'span') {
            var first_1 = true;
            return (coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, function (index, color) {
                var span = "<span style=\"-webkit-text-stroke-color: #" + color + ";\">";
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
    Cards.prototype.getCardDescription = function (cardTypeId) {
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
            case 50: return "At the start of your turn, if you have fewer than 3[Heart], <strong>gain 2[Heart].</strong>"; // TODODE
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
            /* TODOME case 301: return _("Before the Buy Power cards phase, you may spend 1[Energy] to flip this card.");
            case 302: return _("During the Roll Dice phase, you may reroll one of your dice an extra time. You cannot buy any more Power cards. <em>Before the Buy Power cards phase, you may spend 1[Energy] to flip this card.</em>");*/
        }
        return null;
    };
    Cards.prototype.getTooltip = function (cardTypeId) {
        var cost = this.getCardCost(cardTypeId);
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId, 'text-only') + "</strong></p>";
        if (cost !== null) {
            tooltip += "<p class=\"cost\">" + dojo.string.substitute(_("Cost : ${cost}"), { 'cost': cost }) + " <span class=\"icon energy\"></span></p>";
        }
        tooltip += "<p>" + formatTextIcons(this.getCardDescription(cardTypeId)) + "</p>";
        /* TODOME if (cardTypeId == 301 || cardTypeId == 302) {
            const otherSide = cardTypeId == 301 ? 302 : 301;

            const tempDiv: HTMLDivElement = document.createElement('div');
            tempDiv.classList.add('stockitem');
            tempDiv.style.width = `${CARD_WIDTH}px`;
            tempDiv.style.height = `${CARD_HEIGHT}px`;
            tempDiv.style.position = `relative`;
            tempDiv.style.backgroundImage = `url('${g_gamethemeurl}img/${this.getImageName(otherSide)}-cards.jpg')`;
            const imagePosition = (otherSide % 100) - 1;
            const image_items_per_row = 10;
            var row = Math.floor(imagePosition / image_items_per_row);
            const xBackgroundPercent = (imagePosition - (row * image_items_per_row)) * 100;
            const yBackgroundPercent = row * 100;
            tempDiv.style.backgroundPosition = `-${xBackgroundPercent}% -${yBackgroundPercent}%`;

            document.body.appendChild(tempDiv);
            this.setDivAsCard(tempDiv, otherSide);
            document.body.removeChild(tempDiv);

            tooltip += `<p>${_("Other side :")}<br>${tempDiv.outerHTML}</p>`;
        }*/
        tooltip += "</div>";
        return tooltip;
    };
    Cards.prototype.setupNewCard = function (cardDiv, cardType) {
        if (cardType !== 999) { // no text for golden scarab
            this.setDivAsCard(cardDiv, cardType);
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
            return 'Transformation'; // TODOME 
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
    Cards.prototype.setDivAsCard = function (cardDiv, cardType) {
        var type = this.getCardTypeName(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType));
        var position = this.getCardNamePosition(cardType);
        cardDiv.innerHTML = "<div class=\"bottom\"></div>\n        <div class=\"name-wrapper\" " + (position ? "style=\"left: " + position[0] + "px; top: " + position[1] + "px;\"" : '') + ">\n            <div class=\"outline\">" + this.getCardName(cardType, 'span') + "</div>\n            <div class=\"text\">" + this.getCardName(cardType, 'text-only') + "</div>\n        </div>\n        <div class=\"type-wrapper " + this.getCardTypeClass(cardType) + "\">\n            <div class=\"outline\">" + type + "</div>\n            <div class=\"text\">" + type + "</div>\n        </div>\n        \n        <div class=\"description-wrapper\">" + description + "</div>";
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
    Cards.prototype.changeMimicTooltip = function (mimicCardId, mimickedCard) {
        var mimickedCardText = '-';
        if (mimickedCard) {
            var tempDiv = document.createElement('div');
            tempDiv.classList.add('stockitem');
            tempDiv.style.width = CARD_WIDTH + "px";
            tempDiv.style.height = CARD_HEIGHT + "px";
            tempDiv.style.position = "relative";
            tempDiv.style.backgroundImage = "url('" + g_gamethemeurl + "img/" + this.getImageName(mimickedCard.type) + "-cards.jpg')";
            var imagePosition = ((mimickedCard.type + mimickedCard.side) % 100) - 1;
            var image_items_per_row = 10;
            var row = Math.floor(imagePosition / image_items_per_row);
            var xBackgroundPercent = (imagePosition - (row * image_items_per_row)) * 100;
            var yBackgroundPercent = row * 100;
            tempDiv.style.backgroundPosition = "-" + xBackgroundPercent + "% -" + yBackgroundPercent + "%";
            document.body.appendChild(tempDiv);
            this.setDivAsCard(tempDiv, mimickedCard.type + (mimickedCard.side || 0));
            document.body.removeChild(tempDiv);
            mimickedCardText = "<br>" + tempDiv.outerHTML;
        }
        this.game.addTooltipHtml(mimicCardId, this.getTooltip(27) + ("<br>" + _('Mimicked card:') + " " + mimickedCardText));
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
            // TODOAN
            case 1: return "Pharaonic Ego";
            case 2: return "Isis's Disgrace";
            case 3: return "Thot's Blindness";
            case 4: return "Tutankhamun's Curse";
            case 5: return "Buried in Sand";
            case 6: return "Raging Flood";
            case 7: return "Hotep's Peace";
            case 8: return "Set's Storm";
            case 9: return "Builders' Uprising";
            case 10: return "Inadequate offering";
            case 11: return "Bow Before Ra";
            case 12: return "Vengeance of Horus";
            case 13: return "Ordeal of the Mighty";
            case 14: return "Ordeal of the Wealthy";
            case 15: return "Ordeal of the Spiritual";
            case 16: return "Resurrection of Osiris";
            case 17: return "Forbidden Library";
            case 18: return "Confused Senses";
            case 19: return "Pharaonic Skin";
            case 20: return "Khepri's Rebellion";
            case 21: return "Body, Spirit and Ka";
            case 22: return "False Blessing";
            case 23: return "Gaze of the Sphinx";
            case 24: return "Scribe's Perserverance";
        }
        return null;
    };
    CurseCards.prototype.getPermanentEffect = function (cardTypeId) {
        switch (cardTypeId) {
            // TODOAN translate
            case 1: return "Monsters cannot Yield Tokyo/Manhattan."; // TODOAN keep manhattan ?
            case 2: return "Monsters without the Golden Scarab cannot gain [Heart].";
            case 3: return "Monsters without the Golden Scarab cannot gain [Energy].";
            case 4: return "Monsters without the Golden Scarab cannot gain [Star].";
            case 5: return "Monsters have 1 less Roll (minimum 1 Roll).";
            case 6: return "Monsters roll 1 less die.";
            case 7: return "Monsters without the Golden Scarab cannot use [diceSmash].";
            case 8: return "At the start of your turn, lose 1[Heart].";
            case 9: return "At the start of your turn, lose 2[Star].";
            case 10: return "Cards cost 2 extra [Energy].";
            case 11: return "Monsters’ maximum [Heart] is 8[Heart] (Monsters that have more than 8[Heart] go down to 8[Heart]).";
            case 12: return "Monsters cannot reroll [diceSmash].";
            case 13: return "At the start of each turn, the Monster(s) with the most [Heart] lose 1[Heart].";
            case 14: return "At the start of each turn, the Monster(s) with the most [Star] lose 1[Star].";
            case 15: return "At the start of each turn, the Monster(s) with the most [Energy] lose 1[Energy].";
            case 16: return "Monsters outside of Tokyo/Manhattan cannot use [diceHeart]. Monsters in Tokyo/Manhattan can use their [diceHeart]."; // TODOAN keep manhattan ? TODOAN adapt front forbidden icon
            case 17: return "Monsters without the Golden Scarab cannot buy Power cards.";
            // TODOAN case 18 
            case 19: return "The Monster with the Golden Scarab cannot lose [Heart].";
            // TODOAN case 20
            case 21: return "Only [diceSmash], [diceHeart] and [diceEnergy] faces can be used.";
            case 22: return "Monsters roll 2 extra dice and have 1 extra die Roll. After resolving their dice, they lose 1[Heart] for each different face they rolled.";
            case 23: return "[Keep] cards have no effect."; // TODOPU "[Keep] cards and Permanent Evolution cards have no effect."
            case 24:
                return "You cannot reroll your [dice1].";
                defaut: return "TODO"; // TODO an
        }
        return null;
    };
    CurseCards.prototype.getAnkhEffect = function (cardTypeId) {
        switch (cardTypeId) {
            // TODOAN translate
            case 1: return "Yield Tokyo. You can’t enter Tokyo this turn.";
            case 2:
            case 3:
            case 4:
            case 7:
            case 17:
            case 18:
            case 19: return "Take the Golden Scarab.";
            case 5: return "You have 1 extra die Roll.";
            // TODOAN case 6               
            case 8:
            case 11:
            case 13: return "+2[Heart]";
            // TODOAN case 10
            case 12: return "Gain 1[Star] for each [diceSmash] you rolled.";
            case 14: return "+2[Star]";
            case 15: return "+2[Energy]";
            case 16: return "Take control of Tokyo.";
            // TODOAN case 20
            case 21: return "Cancel the Curse effect. [diceSmash], [diceHeart] and [diceEnergy] faces cannot be used.";
            // TODOAN case 22
            case 23: return "+3[Energy]."; // TODOPU "Draw an Evolution card or gain 3[Energy]."          
            case 24:
                return "Gain 1[Energy] for each [dice1] you rolled.";
                defaut: return "TODO"; // TODO an
        }
        return null;
    };
    CurseCards.prototype.getSnakeEffect = function (cardTypeId) {
        switch (cardTypeId) {
            // TODOAN translate
            case 1: return "Take control of Tokyo.";
            case 2:
            case 8: return "-1[Heart]";
            case 3: return "-2[Energy]";
            case 4:
            case 9: return "-2[Star]";
            case 5: return "You cannot use your [diceSmash].";
            // TODOAN case 6     
            case 7: return "Lose 1[Energy] for each [diceSmash] you rolled.";
            // TODOAN case 10
            case 11: return "-2[Heart]";
            case 12: return "Lose 1[Heart] for each [diceSmash] you rolled.";
            case 13: return "The Monster(s) with the most [Heart] lose 1[Heart].";
            case 14: return "The Monster(s) with the most [Star] lose 1[Star].";
            case 15: return "The Monster(s) with the most [Energy] lose 1[Energy].";
            case 16: return "Yield Tokyo. You can’t enter Tokyo this turn.";
            // TODOAN case 17 18 19
            case 20: return "Take the Golden Scarab.";
            case 21: return "Cancel the Curse effect.";
            // TODOAN case 22
            case 23:
                return "-3[Energy]."; // TODOPU "Discard an Evolution card from your hand or in play or lose 3[Energy]."         
                // TODOAN 24
                defaut: return "TODO"; // TODO an
        }
        return null;
    };
    CurseCards.prototype.getTooltip = function (cardTypeId) {
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId) + "</strong></p>\n            <p>" + "Permanent effect" + " : " + formatTextIcons(this.getPermanentEffect(cardTypeId)) + "</p>\n            <p>" + "Ankh effect" + " : " + formatTextIcons(this.getAnkhEffect(cardTypeId)) + "</p>\n            <p>" + "Snake effect" + " : " + formatTextIcons(this.getSnakeEffect(cardTypeId)) + "</p>\n        </div>";
        return tooltip;
    };
    CurseCards.prototype.setupNewCard = function (cardDiv, cardType) {
        this.setDivAsCard(cardDiv, cardType);
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    CurseCards.prototype.setDivAsCard = function (cardDiv, cardType) {
        var permanentEffect = formatTextIcons(this.getPermanentEffect(cardType));
        var ankhEffect = formatTextIcons(this.getAnkhEffect(cardType));
        var snakeEffect = formatTextIcons(this.getSnakeEffect(cardType));
        cardDiv.innerHTML = "\n        <div class=\"name-wrapper\">\n            <div class=\"outline curse\">" + this.getCardName(cardType) + "</div>\n            <div class=\"text\">" + this.getCardName(cardType) + "</div>\n        </div>\n        \n        <div class=\"effect-wrapper permanent-effect-wrapper\"><div>" + permanentEffect + "</div></div>\n        <div class=\"effect-wrapper ankh-effect-wrapper\"><div>" + ankhEffect + "</div></div>\n        <div class=\"effect-wrapper snake-effect-wrapper\"><div>" + snakeEffect + "</div></div>";
    };
    return CurseCards;
}());
var WICKEDNESS_TILES_WIDTH = 132;
var WICKEDNESS_TILES_HEIGHT = 82; // TODOWI
var WICKEDNESS_LEVELS = [3, 6, 10];
var WickednessTiles = /** @class */ (function () {
    function WickednessTiles(game) {
        this.game = game;
    }
    WickednessTiles.prototype.setupCards = function (stocks) {
        var _this = this;
        stocks.forEach(function (stock) {
            var orangewickednesstilessurl = g_gamethemeurl + "img/orange-wickedness-tiles.jpg";
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10].forEach(function (id, index) {
                stock.addItemType(id, _this.getCardLevel(id) * 100 + index, orangewickednesstilessurl, index);
            });
            var greenwickednesstilessurl = g_gamethemeurl + "img/green-wickedness-tiles.jpg";
            [101, 102, 103, 104, 105, 106, 107, 108, 109, 110].forEach(function (id, index) {
                stock.addItemType(id, _this.getCardLevel(id) * 100 + index, greenwickednesstilessurl, index);
            });
        });
    };
    WickednessTiles.prototype.addCardsToStock = function (stock, cards, from) {
        if (!cards.length) {
            return;
        }
        cards.forEach(function (card) { return stock.addToStockWithId(card.type + card.side, "" + card.id, from); });
    };
    WickednessTiles.prototype.moveToAnotherStock = function (sourceStock, destinationStock, tile) {
        if (sourceStock === destinationStock) {
            return;
        }
        var sourceStockItemId = sourceStock.container_div.id + "_item_" + tile.id;
        if (document.getElementById(sourceStockItemId)) {
            this.addCardsToStock(destinationStock, [tile], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById("" + tile.id);
        }
        else {
            console.warn(sourceStockItemId + " not found in ", sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [tile], sourceStock.container_div.id);
        }
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
            // TODOWI
            case 9: return _("Antimatter Beam");
            case 10: return _("Skybeam");
            // green
            // TODOWI
            case 109: return _("Final push");
            case 110: return _("Starburst");
        }
        return null;
    };
    WickednessTiles.prototype.getCardDescription = function (cardTypeId) {
        switch (cardTypeId) {
            // orange
            case 9: return _("<strong>Double all of your [diceSmash].</strong>");
            case 10: return _("<strong>Gain 1 extra [Energy]</strong> for each [diceEnergy] and <strong>1 extra [Heart]</strong> for each [diceHeart]");
            // green
            case 109: return _("<strong>+2[Heart] +2[Energy]</strong><br><br><strong>Take another turn after this one,</strong> then discard this tile.");
            case 110: return _("<strong>+12[Energy]</strong> then discard this tile.");
        }
        return null;
    };
    WickednessTiles.prototype.getTooltip = function (cardTypeId) {
        var level = this.getCardLevel(cardTypeId);
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId) + "</strong></p>\n            <p class=\"level\">" + dojo.string.substitute(_("Level : ${level}"), { 'level': level }) + "</p>\n            <p>" + formatTextIcons(this.getCardDescription(cardTypeId)) + "</p>\n        </div>";
        return tooltip;
    };
    WickednessTiles.prototype.setupNewCard = function (cardDiv, cardType) {
        this.setDivAsCard(cardDiv, cardType);
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    WickednessTiles.prototype.setDivAsCard = function (cardDiv, cardType) {
        var name = this.getCardName(cardType);
        var level = this.getCardLevel(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType));
        cardDiv.innerHTML = "\n        <div class=\"name-wrapper\">\n            <div class=\"outline " + (cardType > 100 ? 'wickedness-tile-side1' : 'wickedness-tile-side0') + "\">" + name + "</div>\n            <div class=\"text\">" + name + "</div>\n        </div>\n        <div class=\"level\" [data-level]=\"" + level + "\">" + level + "</div>\n        \n        <div class=\"description-wrapper\">" + description + "</div>";
    };
    return WickednessTiles;
}());
var TokyoTower = /** @class */ (function () {
    function TokyoTower(divId, levels) {
        this.divId = divId + "-tokyo-tower";
        dojo.place("<div id=\"" + this.divId + "\" class=\"tokyo-tower tokyo-tower-tooltip\">\n            <div class=\"level level3\"></div>\n            <div class=\"level level2\"></div>\n            <div class=\"level level1\"></div>\n        </div>", divId);
        this.setLevels(levels);
    }
    TokyoTower.prototype.setLevels = function (levels) {
        for (var i = 1; i <= 3; i++) {
            document.getElementById(this.divId).getElementsByClassName("level" + i)[0].dataset.owned = levels.includes(i) ? 'true' : 'false';
        }
    };
    return TokyoTower;
}());
var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
;
var log = isDebug ? console.log.bind(window.console) : function () { };
var POINTS_DEG = [25, 40, 56, 73, 89, 105, 122, 138, 154, 170, 187, 204, 221, 237, 254, 271, 288, 305, 322, 339, 359];
var HEALTH_DEG = [360, 326, 301, 274, 249, 226, 201, 174, 149, 122, 98, 64, 39];
var SPLIT_ENERGY_CUBES = 6;
var PlayerTable = /** @class */ (function () {
    function PlayerTable(game, player, cards, wickednessTiles, playerWithGoldenScarab) {
        var _this = this;
        this.game = game;
        this.player = player;
        this.playerId = Number(player.id);
        this.playerNo = Number(player.player_no);
        this.monster = Number(player.monster);
        var eliminated = Number(player.eliminated) > 0;
        var html = "\n        <div id=\"player-table-" + player.id + "\" class=\"player-table whiteblock " + (eliminated ? 'eliminated' : '') + "\">\n            <div id=\"player-name-" + player.id + "\" class=\"player-name " + (game.isDefaultFont() ? 'standard' : 'goodgirl') + "\" style=\"color: #" + player.color + "\">\n                <div class=\"outline" + (player.color === '000000' ? ' white' : '') + "\">" + player.name + "</div>\n                <div class=\"text\">" + player.name + "</div>\n            </div> \n            <div id=\"monster-board-wrapper-" + player.id + "\" class=\"monster-board-wrapper " + (player.location > 0 ? 'intokyo' : '') + "\">\n                <div class=\"blue wheel\" id=\"blue-wheel-" + player.id + "\"></div>\n                <div class=\"red wheel\" id=\"red-wheel-" + player.id + "\"></div>\n                <div class=\"kot-token\"></div>\n                <div id=\"monster-board-" + player.id + "\" class=\"monster-board monster" + this.monster + "\">\n                    <div id=\"monster-board-" + player.id + "-figure-wrapper\" class=\"monster-board-figure-wrapper\">\n                        <div id=\"monster-figure-" + player.id + "\" class=\"monster-figure monster" + this.monster + "\"><div class=\"stand\"></div></div>\n                    </div>\n                </div>\n                <div id=\"token-wrapper-" + this.playerId + "-poison\" class=\"token-wrapper poison\"></div>\n                <div id=\"token-wrapper-" + this.playerId + "-shrink-ray\" class=\"token-wrapper shrink-ray\"></div>\n            </div> \n            <div id=\"energy-wrapper-" + player.id + "-left\" class=\"energy-wrapper left\"></div>\n            <div id=\"energy-wrapper-" + player.id + "-right\" class=\"energy-wrapper right\"></div>";
        if (game.isWickednessExpansion()) {
            html += "<div id=\"wickedness-tiles-" + player.id + "\" class=\"wickedness-tile-stock player-wickedness-tiles " + ((wickednessTiles === null || wickednessTiles === void 0 ? void 0 : wickednessTiles.length) ? '' : 'empty') + "\"></div>   ";
        }
        html += "    <div id=\"cards-" + player.id + "\" class=\"card-stock player-cards " + (cards.length ? '' : 'empty') + "\"></div>\n        </div>\n        ";
        dojo.place(html, 'table');
        this.cards = new ebg.stock();
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $("cards-" + this.player.id), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = function (card_div, card_type_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
        this.cards.image_items_per_row = 10;
        this.cards.centerItems = true;
        dojo.connect(this.cards, 'onChangeSelection', this, function (_, itemId) { return _this.game.onVisibleCardClick(_this.cards, itemId, _this.playerId); });
        this.game.cards.setupCards([this.cards]);
        this.game.cards.addCardsToStock(this.cards, cards);
        if (playerWithGoldenScarab) {
            this.cards.addToStockWithId(999, 'goldenscarab');
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
            this.setCultistTokens(player.cultists);
        }
        if (this.game.isWickednessExpansion()) {
            this.wickednessTiles = new ebg.stock();
            this.wickednessTiles.setSelectionAppearance('class');
            this.wickednessTiles.selectionClass = 'no-visible-selection';
            this.wickednessTiles.create(this.game, $("wickedness-tiles-" + player.id), WICKEDNESS_TILES_WIDTH, WICKEDNESS_TILES_HEIGHT);
            this.wickednessTiles.setSelectionMode(0);
            this.wickednessTiles.centerItems = true;
            this.wickednessTiles.onItemCreate = function (card_div, card_type_id) { return _this.game.wickednessTiles.setupNewCard(card_div, card_type_id); };
            this.game.wickednessTiles.setupCards([this.wickednessTiles]);
            wickednessTiles.forEach(function (tile) { return _this.wickednessTiles.addToStockWithId(tile.type, '' + tile.id); });
        }
    }
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
    PlayerTable.prototype.setPoints = function (points, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        setTimeout(function () { return document.getElementById("blue-wheel-" + _this.playerId).style.transform = "rotate(" + POINTS_DEG[Math.min(20, points)] + "deg)"; }, delay);
    };
    PlayerTable.prototype.setHealth = function (health, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        setTimeout(function () { return document.getElementById("red-wheel-" + _this.playerId).style.transform = "rotate(" + (health > 12 ? 22 : HEALTH_DEG[health]) + "deg)"; }, delay);
    };
    PlayerTable.prototype.setEnergy = function (energy, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        setTimeout(function () {
            _this.setEnergyOnSide('left', Math.min(energy, SPLIT_ENERGY_CUBES));
            _this.setEnergyOnSide('right', Math.max(energy - SPLIT_ENERGY_CUBES, 0));
        }, delay);
    };
    PlayerTable.prototype.eliminatePlayer = function () {
        this.setEnergy(0);
        this.cards.removeAll();
        if (document.getElementById("monster-figure-" + this.playerId)) {
            this.game.fadeOutAndDestroy("monster-figure-" + this.playerId);
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
            var html = "<div id=\"" + divId + "-token" + i + "\" style=\"left: " + (newPlace.x - 16) + "px; top: " + (newPlace.y - 16) + "px;\" class=\"energy-cube\"></div>";
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
        this.cards.removeFromStockById('' + card.id);
        this.cards.addToStockWithId(card.type + card.side, '' + card.id);
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
        if (dojo.hasClass('kot-table', 'pickMonster')) {
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
        if (dojo.hasClass('kot-table', 'pickMonster')) {
            return;
        }
        var players = this.playerTables.length;
        var zoomWrapper = document.getElementById('zoom-wrapper');
        var tableDiv = document.getElementById('table');
        var tableWidth = tableDiv.clientWidth;
        var tableCenterDiv = document.getElementById('table-center');
        this.playerTables.forEach(function (playerTable) {
            dojo.toggleClass("wickedness-tiles-" + playerTable.playerId, 'empty', !playerTable.wickednessTiles.items.length);
            dojo.toggleClass("cards-" + playerTable.playerId, 'empty', !playerTable.cards.items.length);
        });
        var availableColumns = this.getAvailableColumns(tableWidth, tableCenterDiv.clientWidth);
        var centerTableWidthMargins = tableCenterDiv.clientWidth + 2 * TABLE_MARGIN;
        tableCenterDiv.style.left = (tableWidth - centerTableWidthMargins) / 2 + "px";
        tableCenterDiv.style.top = "0px";
        var height = tableCenterDiv.clientHeight;
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
        var disposition = dispositionModel.map(function (columnIndexes) { return columnIndexes.map(function (columnIndex) { return ({
            id: _this.playerTables[columnIndex].playerId,
            height: _this.getPlayerTableHeight(_this.playerTables[columnIndex]),
        }); }); });
        var tableCenter = (columns === 2 ? tableWidth - PLAYER_TABLE_WIDTH_MARGINS : tableWidth) / 2;
        var centerColumnIndex = columns === 3 ? 1 : 0;
        if (columns === 2) {
            tableCenterDiv.style.left = tableCenter - tableCenterDiv.clientWidth / 2 + "px";
        }
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
            var top;
            if (centerColumn) {
                top = !playerOverTable ? tableCenterDiv.clientHeight + 20 : 0;
            }
            else {
                top = Math.max(0, (height - dispositionColumn.map(function (dc) { return dc.height; }).reduce(function (a, b) { return a + b; }, 0)) / 2);
            }
            dispositionColumn.forEach(function (playerInfos, index) {
                var playerTableDiv = document.getElementById("player-table-" + playerInfos.id);
                if (centerColumn) {
                    playerTableDiv.style.left = tableCenter - PLAYER_TABLE_WIDTH_MARGINS / 2 + "px";
                }
                else if (rightColumn) {
                    playerTableDiv.style.left = tableCenter + tableCenterDiv.clientWidth / 2 + "px";
                }
                else if (leftColumn) {
                    playerTableDiv.style.left = (tableCenter - centerTableWidthMargins / 2) - PLAYER_TABLE_WIDTH_MARGINS + "px";
                }
                playerTableDiv.style.top = top + "px";
                top += playerInfos.height;
                if (centerColumn && playerOverTable && index === 0) {
                    tableCenterDiv.style.top = playerInfos.height + "px";
                    top += tableCenterDiv.clientHeight + 20;
                }
                height = Math.max(height, top);
            });
        });
        tableDiv.style.height = height + "px";
        zoomWrapper.style.height = height * this.zoom + "px";
    };
    TableManager.prototype.getPlayerTableHeight = function (playerTable) {
        var tilesRows = Math.ceil(playerTable.wickednessTiles.items.length / CARDS_PER_ROW);
        var tilesHeight = tilesRows === 0 ? 0 : ((WICKEDNESS_TILES_HEIGHT + 5) * tilesRows);
        var cardRows = Math.ceil(playerTable.cards.items.length / CARDS_PER_ROW);
        var cardHeight = cardRows === 0 ? 20 : ((CARD_HEIGHT + 5) * cardRows);
        return PLAYER_BOARD_HEIGHT_MARGINS + tilesHeight + cardHeight;
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
    function DieFaceSelector(nodeId, die, inTokyo) {
        var _this = this;
        this.nodeId = nodeId;
        this.die = die;
        this.dieValue = die.value;
        var colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        var _loop_1 = function (face) {
            var faceId = nodeId + "-face" + face;
            var html = "<div id=\"" + faceId + "\" class=\"die-item dice-icon dice" + face + " " + colorClass + " " + (this_1.dieValue == face ? 'disabled' : '') + "\">";
            if (!die.type && face === 4 && inTokyo) {
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
    DiceManager.prototype.setDiceForThrowDice = function (dice, inTokyo, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        this.action = 'move';
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = dice;
        var selectable = isCurrentPlayerActive;
        dice.forEach(function (die) { return _this.createDice(die, selectable, inTokyo); });
        dojo.toggleClass('rolled-dice', 'selectable', selectable);
    };
    DiceManager.prototype.disableDiceAction = function () {
        dojo.removeClass('rolled-dice', 'selectable');
        this.action = undefined;
    };
    DiceManager.prototype.getLockedDiceId = function (die) {
        return "locked-dice" + this.getDieFace(die);
    };
    DiceManager.prototype.setDiceForChangeDie = function (dice, args, inTokyo, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        this.action = args.hasHerdCuller || args.hasPlotTwist || args.hasStretchy || args.hasClown ? 'change' : null;
        this.changeDieArgs = args;
        if (this.dice.length) {
            dice.forEach(function (die) {
                var divId = "dice" + die.id;
                var selectable = isCurrentPlayerActive && _this.action !== null && (!onlyHerdCuller || die.value !== 1);
                dojo.toggleClass(divId, 'selectable', selectable);
            });
            return;
        }
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = dice;
        var onlyHerdCuller = args.hasHerdCuller && !args.hasPlotTwist && !args.hasStretchy && !args.hasClown;
        dice.forEach(function (die) {
            var divId = "dice" + die.id;
            _this.createAndPlaceDiceHtml(die, inTokyo, _this.getLockedDiceId(die));
            var selectable = isCurrentPlayerActive && _this.action !== null && (!onlyHerdCuller || die.value !== 1);
            dojo.toggleClass(divId, 'selectable', selectable);
            _this.addDiceRollClass(die);
            if (selectable) {
                document.getElementById(divId).addEventListener('click', function (event) { return _this.dieClick(die, event); });
            }
        });
    };
    DiceManager.prototype.setDiceForSelectHeartAction = function (dice, inTokyo) {
        var _this = this;
        this.action = null;
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, inTokyo, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
    };
    DiceManager.prototype.setDiceForPsychicProbe = function (dice, inTokyo, isCurrentPlayerActive) {
        var _this = this;
        if (isCurrentPlayerActive === void 0) { isCurrentPlayerActive = false; }
        this.action = 'psychicProbeRoll';
        /*if (this.dice.length) { if active, event are not reset and roll is not applied
            return;
        }*/
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, inTokyo, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
            if (isCurrentPlayerActive) {
                var divId = "dice" + die.id;
                document.getElementById(divId).addEventListener('click', function (event) { return _this.dieClick(die, event); });
            }
        });
        dojo.toggleClass('rolled-dice', 'selectable', isCurrentPlayerActive);
    };
    DiceManager.prototype.changeDie = function (dieId, inTokyo, toValue, roll) {
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
            if (inTokyo && !die.type) {
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
            _this.createAndPlaceDiceHtml(die, false, "dice-selector");
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
        var dice = this.dice.filter(function (die) { return !die.type && die.value === face && document.getElementById("dice" + die.id).dataset.animated !== 'true'; });
        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        }
        else {
            var berserkDice = this.dice.filter(function (die) { return die.type === 1; });
            if (face == 5) { // energy
                return berserkDice.filter(function (die) { return die.value >= 1 && die.value <= 2 && document.getElementById("dice" + die.id).dataset.animated !== 'true'; });
            }
            else if (face == 6) { // smash
                return berserkDice.filter(function (die) { return die.value >= 3 && die.value <= 5 && document.getElementById("dice" + die.id).dataset.animated !== 'true'; });
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
        html += "    </ol>\n        </div>\n        ";
        /*let html = `<div id="dice${die.id}" class="dice dice${die.value}" data-dice-id="${die.id}" data-dice-value="${die.value}">
        <ol class="die-list" data-roll="${die.value}">`;
        const colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        for (let dieFace=1; dieFace<=6; dieFace++) {
            html += `<li class="die-item ${colorClass} side${dieFace}" data-side="${dieFace}"></li>`;
        }
        html += `</ol>`;
        if (!die.type && die.value === 4 && inTokyo) {
            html += `<div class="icon forbidden"></div>`;
        }
        html += `</div>`;*/
        // security to destroy pre-existing die with same id
        //const dieDiv = document.getElementById(`dice${die.id}`);
        //dieDiv?.parentNode.removeChild(dieDiv);
        dojo.place(html, destinationId);
    };
    DiceManager.prototype.createAndPlaceDie6Html = function (die, canHealWithDice, destinationId) {
        var html = "<div id=\"dice" + die.id + "\" class=\"dice dice" + die.value + "\" data-dice-id=\"" + die.id + "\" data-dice-value=\"" + die.value + "\">\n        <ol class=\"die-list\" data-roll=\"" + die.value + "\">";
        var colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        for (var dieFace = 1; dieFace <= 6; dieFace++) {
            html += "<li class=\"die-item " + colorClass + " side" + dieFace + "\" data-side=\"" + dieFace + "\"></li>";
        }
        html += "</ol>";
        if (!die.type && die.value === 4 && !canHealWithDice) {
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
    DiceManager.prototype.createAndPlaceDiceHtml = function (die, inTokyo, destinationId) {
        if (die.type == 2) {
            this.createAndPlaceDie4Html(die, destinationId);
        }
        else {
            this.createAndPlaceDie6Html(die, !inTokyo, destinationId);
        }
    };
    DiceManager.prototype.getDiceDiv = function (die) {
        return document.getElementById("dice" + die.id);
    };
    DiceManager.prototype.createDice = function (die, selectable, inTokyo) {
        var _this = this;
        this.createAndPlaceDiceHtml(die, inTokyo, die.locked ? this.getLockedDiceId(die) : "dice-selector");
        var div = this.getDiceDiv(die);
        div.addEventListener('animationend', function (e) {
            if (e.animationName == 'rolled-dice') {
                div.dataset.rolled = 'false';
            }
        });
        this.addDiceRollClass(die);
        if (selectable) {
            div.addEventListener('click', function (event) { return _this.dieClick(die, event); });
        }
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
        var dieDiv = this.getDiceDiv(die);
        dieDiv.dataset.rolled = die.rolled ? 'true' : 'false';
        if (die.rolled) {
            setTimeout(function () { return _this.addRollToDiv(dieDiv, Math.random() < 0.5 && die.type != 2 ? 'odd' : 'even'); }, 200);
        }
        else {
            this.addRollToDiv(dieDiv, '-');
        }
    };
    DiceManager.prototype.removeDice = function (die, duration, delay) {
        if (duration) {
            this.game.fadeOutAndDestroy("dice" + die.id, duration, delay);
        }
        else {
            var dieDiv = document.getElementById("dice" + die.id);
            dieDiv === null || dieDiv === void 0 ? void 0 : dieDiv.parentNode.removeChild(dieDiv);
        }
        this.dice.splice(this.dice.indexOf(die), 1);
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
            // TODOAN make die cursor forbidden
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
            var plotTwistButtonId_1 = bubbleActionButtonsId + "-plotTwist";
            var stretchyButtonId_1 = bubbleActionButtonsId + "-stretchy";
            var clownButtonId_1 = bubbleActionButtonsId + "-clown";
            var args_1 = this.changeDieArgs;
            if (!this.dieFaceSelectors[die.id]) {
                this.dieFaceSelectors[die.id] = new DieFaceSelector(bubbleDieFaceSelectorId, die, args_1.inTokyo);
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
                }
                dieFaceSelector_1.onChange = function (value) {
                    if (args_1.hasClown) {
                        dojo.toggleClass(clownButtonId_1, 'disabled', value < 1);
                    }
                    else {
                        if (args_1.hasHerdCuller && die.value > 1) {
                            dojo.toggleClass(herdCullerButtonId_1, 'disabled', value != 1);
                        }
                        if (args_1.hasPlotTwist) {
                            dojo.toggleClass(plotTwistButtonId_1, 'disabled', value < 1);
                        }
                        if (args_1.hasStretchy) {
                            dojo.toggleClass(stretchyButtonId_1, 'disabled', value < 1);
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
                    if (args_1.hasPlotTwist) {
                        dojo.addClass(plotTwistButtonId_1, 'disabled');
                    }
                    if (args_1.hasStretchy) {
                        dojo.addClass(stretchyButtonId_1, 'disabled');
                    }
                }
            }
            args_1.dice.filter(function (idie) { return idie.id != die.id; }).forEach(function (idie) { return _this.hideBubble(idie.id); });
            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
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
        var dice = allDice.filter(function (die) { return !die.type && document.getElementById("dice" + die.id).dataset.animated !== 'true'; });
        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        }
        else {
            var berserkDice = this.diceManager.getBerserkDice();
            if (face == 5) { // energy
                return berserkDice.filter(function (die) { return die.value >= 1 && die.value <= 2 && document.getElementById("dice" + die.id).dataset.animated !== 'true'; });
            }
            else if (face == 6) { // smash
                return berserkDice.filter(function (die) { return die.value >= 3 && die.value <= 5 && document.getElementById("dice" + die.id).dataset.animated !== 'true'; });
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
        var originTop = document.getElementById(dice[0] ? "dice" + dice[0].id : 'dice-selector').getBoundingClientRect().top;
        var leftDieBR = document.getElementById(dice[0] ? "dice" + dice[0].id : 'dice-selector').getBoundingClientRect();
        var rightDieBR = document.getElementById(dice.length ? "dice" + dice[dice.length - 1].id : 'dice-selector').getBoundingClientRect();
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
                    var targetId = "monster-figure-" + playerId;
                    if (targetToken) {
                        var tokensDivs = document.querySelectorAll("div[id^='token-wrapper-" + playerId + "-" + targetToken + "-token'");
                        targetId = tokensDivs[tokensDivs.length - (i + 1)].id;
                    }
                    var destination = document.getElementById(targetId).getBoundingClientRect();
                    var deltaX = destination.left - originLeft + shift * _this.game.getZoom();
                    var deltaY = destination.top - originTop + shift * _this.game.getZoom();
                    animationDiv.style.transition = "transform 0.5s ease-in";
                    animationDiv.style.transform = "translate(" + deltaX + "px, " + deltaY + "px) scale(" + 0.3 * _this.game.getZoom() + ")";
                    animationDiv.addEventListener('transitionend', function () { var _a; return (_a = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(animationDiv); });
                    // security
                    setTimeout(function () { var _a; return (_a = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(animationDiv); }, 1050);
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
        dojo.place("<div id=\"" + nodeId + "-apply-wrapper\"><button class=\"bgabutton bgabutton_blue\" id=\"" + nodeId + "-apply\">" + _('Apply') + "</button></div>", nodeId);
        document.getElementById(nodeId + "-apply").addEventListener('click', function () { return _this.game.applyHeartActions(_this.selections); });
    }
    HeartActionSelector.prototype.createToggleButtons = function (nodeId, args) {
        var _this = this;
        args.dice.filter(function (die) { return die.value === 4; }).forEach(function (die, index) {
            var html = "<div class=\"die\">\n                <div class=\"die-face\">\n                    <div class=\"dice-icon dice4\"></div>\n                </div>\n                <div id=\"" + nodeId + "-die" + index + "\" class=\"toggle-buttons\"></div>\n            </div>";
            dojo.place(html, nodeId);
            _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-heal", _('Heal'), function () { return _this.healSelected(index); }, false, true);
            if (args.inTokyo) {
                var buttonDiv = document.getElementById(nodeId + "-die" + index + "-heal");
                buttonDiv.style.position = 'relative';
                buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
            }
            _this.selections[index] = { action: 'heal' };
            if (args.shrinkRayTokens > 0) {
                _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-shrink-ray", _('Remove Shrink Ray token'), function () { return _this.shrinkRaySelected(index); }, args.inTokyo);
                if (args.inTokyo) {
                    var buttonDiv = document.getElementById(nodeId + "-die" + index + "-shrink-ray");
                    buttonDiv.style.position = 'relative';
                    buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
                }
            }
            if (args.poisonTokens > 0) {
                _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-poison", _('Remove Poison token'), function () { return _this.poisonSelected(index); }, args.inTokyo);
                if (args.inTokyo) {
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
var PreferencesManager = /** @class */ (function () {
    function PreferencesManager(game) {
        this.game = game;
        this.setupPreferences();
    }
    PreferencesManager.prototype.setupPreferences = function () {
        var _this = this;
        // Extract the ID and value from the UI control
        var onchange = function (e) {
            var match = e.target.id.match(/^preference_control_(\d+)$/);
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
        }
        catch (e) { }
    };
    PreferencesManager.prototype.getGameVersionNumber = function (versionNumber) {
        if (versionNumber > 0) {
            return versionNumber;
        }
        else {
            return this.game.isHalloweenExpansion() ? 2 : 1;
        }
    };
    PreferencesManager.prototype.onPreferenceChange = function (prefId, prefValue) {
        switch (prefId) {
            // KEEP
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
        return this.getGameVersionNumber(this.game.prefs[205].value) == 2 ? '000000' : '96c93c';
    };
    return PreferencesManager;
}());
var TableCenter = /** @class */ (function () {
    function TableCenter(game, visibleCards, topDeckCardBackType, wickednessTiles, tokyoTowerLevels, curseCard) {
        this.game = game;
        this.wickednessTiles = [];
        this.wickednessTilesStocks = [];
        this.createVisibleCards(visibleCards, topDeckCardBackType);
        if (game.isWickednessExpansion()) {
            dojo.place("<div id=\"wickedness-board\"></div>", 'full-board');
            document.getElementById('table-center').style.width = '622px';
            this.createWickednessTiles(wickednessTiles);
        }
        if (game.isKingkongExpansion()) {
            dojo.place("<div id=\"tokyo-tower-0\" class=\"tokyo-tower-wrapper\"></div>", 'board');
            this.tokyoTower = new TokyoTower('tokyo-tower-0', tokyoTowerLevels);
        }
        if (game.isAnubisExpansion()) {
            this.createCurseCard(curseCard);
        }
    }
    TableCenter.prototype.createVisibleCards = function (visibleCards, topDeckCardBackType) {
        var _this = this;
        this.visibleCards = new ebg.stock();
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection';
        this.visibleCards.create(this.game, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = function (card_div, card_type_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
        this.visibleCards.image_items_per_row = 10;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, function (_, item_id) { return _this.game.onVisibleCardClick(_this.visibleCards, item_id); });
        this.game.cards.setupCards([this.visibleCards]);
        this.setVisibleCards(visibleCards);
        this.setTopDeckCardBackType(topDeckCardBackType);
    };
    TableCenter.prototype.createCurseCard = function (curseCard) {
        var _this = this;
        dojo.place("<div id=\"curse-wrapper\">\n            <div id=\"curse-deck\"></div>\n            <div id=\"curse-card\"></div>\n        </div>", 'board', 'before');
        this.curseCard = new ebg.stock();
        this.curseCard.setSelectionAppearance('class');
        this.curseCard.selectionClass = 'no-visible-selection';
        this.curseCard.create(this.game, $('curse-card'), CARD_WIDTH, CARD_HEIGHT);
        this.curseCard.setSelectionMode(0);
        this.curseCard.centerItems = true;
        this.curseCard.onItemCreate = function (card_div, card_type_id) { return _this.game.curseCards.setupNewCard(card_div, card_type_id); };
        this.game.curseCards.setupCards([this.curseCard]);
        this.curseCard.addToStockWithId(curseCard.type, '' + curseCard.id);
    };
    TableCenter.prototype.setVisibleCardsSelectionMode = function (mode) {
        this.visibleCards.setSelectionMode(mode);
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
            dojo.connect(this.pickCard, 'onChangeSelection', this, function (_, item_id) { return _this.game.onVisibleCardClick(_this.pickCard, item_id); });
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
            if (id !== Number(cardId)) {
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
            var html = "<div id=\"wickedness-tiles-reduced-" + level + "\" class=\"wickedness-tiles-reduced\"></div>\n            <div id=\"wickedness-tiles-expanded-" + level + "\" class=\"wickedness-tiles-expanded\">\n                <div id=\"wickedness-tiles-expanded-" + level + "-close\" class=\"close\">\u2716</div>\n                <div id=\"wickedness-tiles-expanded-" + level + "-stock\" class=\"wickedness-tile-stock table-wickedness-tiles\"></div>\n            </div>";
            dojo.place(html, 'wickedness-board');
            _this.setReducedWickednessTiles(level);
            document.getElementById("wickedness-tiles-reduced-" + level).addEventListener('click', function () { return _this.showWickednessTiles(level); });
            _this.wickednessTilesStocks[level] = new ebg.stock();
            _this.wickednessTilesStocks[level].setSelectionAppearance('class');
            _this.wickednessTilesStocks[level].selectionClass = 'no-visible-selection';
            _this.wickednessTilesStocks[level].create(_this.game, $("wickedness-tiles-expanded-" + level + "-stock"), WICKEDNESS_TILES_WIDTH, WICKEDNESS_TILES_HEIGHT);
            _this.wickednessTilesStocks[level].setSelectionMode(0);
            _this.wickednessTilesStocks[level].centerItems = true;
            _this.wickednessTilesStocks[level].onItemCreate = function (card_div, card_type_id) { return _this.game.wickednessTiles.setupNewCard(card_div, card_type_id); };
            dojo.connect(_this.wickednessTilesStocks[level], 'onChangeSelection', _this, function (_, item_id) { return _this.game.takeWickednessTile(Number(item_id)); });
            _this.game.wickednessTiles.setupCards([_this.wickednessTilesStocks[level]]);
            _this.wickednessTiles[level].forEach(function (tile) { return _this.wickednessTilesStocks[level].addToStockWithId(tile.type, '' + tile.id); });
            document.getElementById("wickedness-tiles-expanded-" + level).addEventListener('click', function () { return dojo.removeClass("wickedness-tiles-expanded-" + level, 'visible'); });
        });
    };
    TableCenter.prototype.setWickedness = function (playerId, wickedness) {
        // TODOWI
    };
    TableCenter.prototype.getWickednessTilesStock = function (level) {
        return this.wickednessTilesStocks[level];
    };
    TableCenter.prototype.showWickednessTiles = function (level) {
        dojo.addClass("wickedness-tiles-expanded-" + level, 'visible');
    };
    TableCenter.prototype.setWickednessTilesSelectable = function (level, show, selectable) {
        var _this = this;
        if (show) {
            this.showWickednessTiles(level);
        }
        else {
            WICKEDNESS_LEVELS.forEach(function (level) { return dojo.removeClass("wickedness-tiles-expanded-" + level, 'visible'); });
        }
        if (selectable) {
            dojo.addClass("wickedness-tiles-expanded-" + level, 'selectable');
            this.wickednessTilesStocks[level].setSelectionMode(1);
        }
        else {
            WICKEDNESS_LEVELS.forEach(function (level) {
                _this.wickednessTilesStocks[level].setSelectionMode(0);
                dojo.removeClass("wickedness-tiles-expanded-" + level, 'selectable');
            });
        }
    };
    TableCenter.prototype.setReducedWickednessTiles = function (level) {
        document.getElementById("wickedness-tiles-reduced-" + level).innerHTML = '';
        this.wickednessTiles[level].forEach(function (tile, index) {
            dojo.place("<div id=\"wickedness-tiles-reduced-tile-" + tile.id + "\" class=\"wickedness-tiles-reduced-tile\" style=\"left: " + index * 10 + "px; top: " + index * 10 + "px;\"></div>", "wickedness-tiles-reduced-" + level);
        });
    };
    TableCenter.prototype.removeReducedWickednessTile = function (level, removedTile) {
        this.wickednessTiles[level].splice(this.wickednessTiles[level].findIndex(function (tile) { return tile.id == removedTile.id; }), 1);
        this.setReducedWickednessTiles(level);
    };
    return TableCenter;
}());
var ANIMATION_MS = 1500;
var PUNCH_SOUND_DURATION = 250;
var KingOfTokyo = /** @class */ (function () {
    function KingOfTokyo() {
        this.healthCounters = [];
        this.energyCounters = [];
        this.wickednessCounters = [];
        this.tokyoTowerCounters = [];
        this.cultistCounters = [];
        this.playerTables = [];
        //private rapidHealingSyncHearts: number;
        this.towerLevelsOwners = [];
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
        [1, 2, 3, 4, 5, 6, 7, 8, 9, 10].filter(function (i) { return !players.some(function (player) { return Number(player.monster) === i; }); }).forEach(function (i) {
            _this.dontPreloadImage("monster-board-" + i + ".png");
            _this.dontPreloadImage("monster-figure-" + i + ".png");
        });
        this.dontPreloadImage("tokyo-2pvariant.jpg");
        this.dontPreloadImage("background-halloween.jpg");
        if (!gamedatas.halloweenExpansion) {
            this.dontPreloadImage("costume-cards.jpg");
            this.dontPreloadImage("orange_dice.png");
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
        this.SHINK_RAY_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Shrink ray tokens (given by ${card_name}). Reduce dice count by one per token. Use you [diceHeart] to remove them.")), { 'card_name': this.cards.getCardName(40, 'text-only') });
        this.POISON_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Poison tokens (given by ${card_name}). Make you lose one [heart] per token at the end of your turn. Use you [diceHeart] to remove them.")), { 'card_name': this.cards.getCardName(35, 'text-only') });
        this.createPlayerPanels(gamedatas);
        this.diceManager = new DiceManager(this);
        this.animationManager = new AnimationManager(this, this.diceManager);
        this.tableCenter = new TableCenter(this, gamedatas.visibleCards, gamedatas.topDeckCardBackType, gamedatas.wickednessTiles, gamedatas.tokyoTowerLevels, gamedatas.curseCard);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(function () { return _this.playerTables.forEach(function (playerTable) { return playerTable.initPlacement(); }); }, 200);
        this.setMimicToken(gamedatas.mimickedCard);
        var playerId = this.getPlayerId();
        var currentPlayer = players.find(function (player) { return Number(player.id) === playerId; });
        if (currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.rapidHealing) {
            this.addRapidHealingButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
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
        /* TODOKK if (gamedatas.kingkongExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Tokyo Tower")}</h3>
            <p>${_("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1]")}</p>
            <p>${_("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative).")}</p>
            <p><strong>${_("Claiming the top level automatically wins the game.")}</strong></p>
            `);
            (this as any).addTooltipHtmlToClass('tokyo-tower-tooltip', tooltip);
        }

        /* TODOCY if (gamedatas.cybertoothExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Berserk mode")}</h3>
            <p>${_("When you roll 4 or more [diceSmash], you are in Berserk mode!")}</p>
            <p>${_("You play with the additional Berserk die, until you heal yourself.")}</p>`);
            (this as any).addTooltipHtmlToClass('berserk-tooltip', tooltip); // TODOCY check if healed by Healing Ray
        }*/
        if (gamedatas.cthulhuExpansion) {
            this.CULTIST_TOOLTIP = formatTextIcons("\n            <h3>" + _("Cultists") + "</h3>\n            <p>" + _("After resolving your dice, if you rolled four identical faces, take a Cultist tile") + "</p>\n            <p>" + _("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll.") + "</p>");
            this.addTooltipHtmlToClass('cultist-tooltip', this.CULTIST_TOOLTIP);
        }
        log("Ending game setup");
    };
    ///////////////////////////////////////////////////
    //// Game & client states
    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    KingOfTokyo.prototype.onEnteringState = function (stateName, args) {
        log('Entering state: ' + stateName, args.args);
        this.showActivePlayer(Number(args.active_player));
        if (stateName !== 'pickMonster' && stateName !== 'pickMonsterNextPlayer') {
            this.replaceMonsterChoiceByTable();
        }
        switch (stateName) {
            case 'pickMonster':
                dojo.addClass('kot-table', 'pickMonster');
                this.onEnteringPickMonster(args.args);
                break;
            case 'chooseInitialCard':
                this.onEnteringChooseInitialCard(args.args);
                break;
            case 'startGame':
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
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
            case 'resolveDice':
                this.setDiceSelectorVisibility(true);
                this.diceManager.hideLock();
                break;
            case 'takeWickednessTile':
                this.onEnteringTakeWickednessTile(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveHeartDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveHeartDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'stealCostumeCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringStealCostumeCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'buyCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringBuyCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'sellCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringSellCard(args.args);
                break;
            case 'endTurn':
                this.setDiceSelectorVisibility(false);
                this.onEnteringEndTurn();
                break;
        }
    };
    KingOfTokyo.prototype.showActivePlayer = function (playerId) {
        this.playerTables.forEach(function (playerTable) { return playerTable.setActivePlayer(playerId == playerTable.playerId); });
    };
    KingOfTokyo.prototype.setGamestateDescription = function (property) {
        if (property === void 0) { property = ''; }
        var originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        this.gamedatas.gamestate.description = "" + originalState['description' + property];
        this.gamedatas.gamestate.descriptionmyturn = "" + originalState['descriptionmyturn' + property];
        this.updatePageTitle();
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
            dojo.place("\n            <div id=\"pick-monster-figure-" + monster + "\" class=\"monster-figure monster" + monster + "\"></div>\n            ", "monster-pick");
            document.getElementById("pick-monster-figure-" + monster).addEventListener('click', function () {
                _this.pickMonster(monster);
            });
        });
        var isCurrentPlayerActive = this.isCurrentPlayerActive();
        dojo.toggleClass('monster-pick', 'selectable', isCurrentPlayerActive);
    };
    KingOfTokyo.prototype.onEnteringChooseInitialCard = function (args) {
        this.tableCenter.setInitialCards(args.cards);
        if (this.isCurrentPlayerActive()) {
            this.tableCenter.setVisibleCardsSelectionMode(1);
        }
    };
    KingOfTokyo.prototype.onEnteringThrowDice = function (args) {
        var _this = this;
        var _a, _b;
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? "last" : '');
        this.diceManager.showLock();
        var dice = args.dice;
        var isCurrentPlayerActive = this.isCurrentPlayerActive();
        this.diceManager.setDiceForThrowDice(dice, args.inTokyo, isCurrentPlayerActive);
        if (isCurrentPlayerActive) {
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', dojo.string.substitute(_("Reroll dice (${number} roll(s) remaining)"), { 'number': args.maxThrowNumber - args.throwNumber }), function () { return _this.onRethrow(); }, !args.dice.some(function (dice) { return !dice.locked; }));
                this.addTooltip('rethrow_button', _("Click on dice you want to keep to lock them, then click this button to reroll the others"), _("Ctrl+click to move all dice with same value") + "<br>\n                    " + _("Alt+click to move all dice but clicked die"));
            }
            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3(); }, !args.rethrow3.hasDice3);
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
        }
        if (args.throwNumber === args.maxThrowNumber && !args.hasSmokeCloud && !args.hasCultist && !((_b = args.energyDrink) === null || _b === void 0 ? void 0 : _b.hasCard)) {
            this.diceManager.disableDiceAction();
        }
    };
    KingOfTokyo.prototype.onEnteringChangeDie = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a, _b;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args, args.inTokyo, isCurrentPlayerActive);
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
    KingOfTokyo.prototype.onEnteringPsychicProbeRollDie = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        this.diceManager.setDiceForPsychicProbe(args.dice, args.inTokyo, isCurrentPlayerActive && args.canRoll);
        if (args.dice && ((_a = args.rethrow3) === null || _a === void 0 ? void 0 : _a.hasCard)) {
            if (document.getElementById('rethrow3psychicProbe_button')) {
                dojo.toggleClass('rethrow3psychicProbe_button', 'disabled', !args.rethrow3.hasDice3);
            }
            else {
                this.createButton('dice-actions', 'rethrow3psychicProbe_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3psychicProbe(); }, !args.rethrow3.hasDice3);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringTakeWickednessTile = function (args, isCurrentPlayerActive) {
        this.tableCenter.setWickednessTilesSelectable(args.level, true, isCurrentPlayerActive);
    };
    KingOfTokyo.prototype.onEnteringResolveHeartDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.inTokyo);
            if (isCurrentPlayerActive) {
                dojo.place("<div id=\"heart-action-selector\" class=\"whiteblock\"></div>", 'rolled-dice-and-rapid-actions', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringCancelDamage = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        if (args.dice) {
            this.diceManager.showCamouflageRoll(args.dice);
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
                this.addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + " ( 2[Energy] )", { 'card_name': this.cards.getCardName(48, 'text-only') })), 'useWings');
                document.getElementById('useWings_button').dataset.enableAtEnergy = '2';
                if (args.playerEnergy < 2) {
                    dojo.addClass('useWings_button', 'disabled');
                }
            }
            if (args.canUseRobot && !document.getElementById('useRobot1_button')) {
                var _loop_3 = function (i) {
                    var id = "useRobot" + i + "_button";
                    this_2.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': i, 'card_name': this_2.cards.getCardName(210, 'text-only') })), function () { return _this.useRobot(i); });
                    document.getElementById(id).dataset.enableAtEnergy = '' + i;
                    dojo.toggleClass(id, 'disabled', args.playerEnergy < i);
                };
                var this_2 = this;
                for (var i = args.damage; i > 0; i--) {
                    _loop_3(i);
                }
            }
            if (!args.canThrowDices && !document.getElementById('skipWings_button')) {
                this.addActionButton('skipWings_button', args.canUseWings ? dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cards.getCardName(48, 'text-only') }) : _("Skip"), 'skipWings');
            }
            var rapidHealingSyncButtons = document.querySelectorAll("[id^='rapidHealingSync_button'");
            rapidHealingSyncButtons.forEach(function (rapidHealingSyncButton) { return rapidHealingSyncButton.parentElement.removeChild(rapidHealingSyncButton); });
            if (args.damageToCancelToSurvive) {
                var _loop_4 = function (i) {
                    var cultistCount = i;
                    var rapidHealingCount = args.rapidHealingHearts > 0 ? args.damageToCancelToSurvive - cultistCount : 0;
                    var cardsNames = [];
                    if (cultistCount > 0) {
                        cardsNames.push(_('Cultist'));
                    }
                    if (rapidHealingCount > 0) {
                        cardsNames.push(_(this_3.cards.getCardName(37, 'text-only')));
                    }
                    if (cultistCount + rapidHealingCount >= args.damageToCancelToSurvive) {
                        var text = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons("" + _('Gain ${hearts}[Heart]') + (rapidHealingCount > 0 ? " (" + 2 * rapidHealingCount + "[Energy])" : '')), { 'card_name': cardsNames.join(', '), 'hearts': args.damageToCancelToSurvive });
                        this_3.addActionButton("rapidHealingSync_button_" + i, text, function () { return _this.useRapidHealingSync(cultistCount, rapidHealingCount); });
                    }
                };
                var this_3 = this;
                //this.rapidHealingSyncHearts = args.rapidHealingHearts;
                for (var i = Math.min(args.rapidHealingCultists, args.damageToCancelToSurvive); i >= 0; i--) {
                    _loop_4(i);
                }
            }
        }
    };
    KingOfTokyo.prototype.onEnteringStealCostumeCard = function (args, isCurrentPlayerActive) {
        var _this = this;
        if (isCurrentPlayerActive) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId != _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_" + id + "\"]")) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringBuyCard = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a, _b;
        if (isCurrentPlayerActive) {
            this.tableCenter.setVisibleCardsSelectionMode(1);
            if (args.canBuyFromPlayers) {
                this.playerTables.filter(function (playerTable) { return playerTable.playerId != _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            }
            if ((_b = (_a = args._private) === null || _a === void 0 ? void 0 : _a.pickCards) === null || _b === void 0 ? void 0 : _b.length) {
                this.tableCenter.showPickStock(args._private.pickCards);
            }
            this.setBuyDisabledCard(args);
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_" + id + "\"]")) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringChooseMimickedCard = function (args) {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_" + id + "\"]")) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringSellCard = function (args) {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_" + id + "\"]")) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringEndTurn = function () {
    };
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        log('Leaving state: ' + stateName);
        switch (stateName) {
            case 'chooseInitialCard':
                this.tableCenter.setVisibleCardsSelectionMode(0);
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'opportunistChooseMimicCard':
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
            case 'takeWickednessTile':
                this.onLeavingTakeWickednessTile();
                break;
            case 'resolveHeartDiceAction':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;
            case 'leaveTokyo':
                this.removeSkipBuyPhaseToggle();
                break;
            case 'stealCostumeCard':
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
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
        }
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
    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    KingOfTokyo.prototype.onUpdateActionButtons = function (stateName, args) {
        var _this = this;
        var _a;
        switch (stateName) {
            case 'changeActivePlayerDie':
            case 'psychicProbeRollDie': // TODO remove
                this.setDiceSelectorVisibility(true);
                break;
            case 'cheerleaderSupport':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args, false); // because it's multiplayer, enter action must be set here
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
                break;
        }
        if (this.isCurrentPlayerActive()) {
            switch (stateName) {
                case 'changeMimickedCard':
                    this.addActionButton('skipChangeMimickedCard_button', _("Skip"), 'skipChangeMimickedCard');
                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCard_button', 5);
                    }
                    break;
                case 'throwDice':
                    this.addActionButton('goToChangeDie_button', _("Resolve dice"), 'goToChangeDie', null, null, 'red');
                    var argsThrowDice = args;
                    if (!argsThrowDice.hasActions) {
                        this.startActionTimer('goToChangeDie_button', 5);
                    }
                    break;
                case 'changeDie':
                    this.addActionButton('resolve_button', _("Resolve dice"), 'resolveDice', null, null, 'red');
                    break;
                case 'changeActivePlayerDie':
                case 'psychicProbeRollDie': // TODO remove
                    this.addActionButton('changeActivePlayerDieSkip_button', _("Skip"), 'psychicProbeSkip');
                    this.onEnteringPsychicProbeRollDie(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'cheerleaderSupport':
                    this.addActionButton('support_button', formatTextIcons(_("Support (add [diceSmash] )")), function () { return _this.support(); });
                    this.addActionButton('dontSupport_button', _("Don't support"), function () { return _this.dontSupport(); });
                    this.onEnteringPsychicProbeRollDie(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'takeWickednessTile':
                    this.addActionButton('skipTakeWickednessTile_button', _("Skip"), function () { return _this.skipTakeWickednessTile(); });
                    break;
                case 'leaveTokyo':
                    var label = _("Stay in Tokyo");
                    var argsLeaveTokyo = args;
                    if ((_a = argsLeaveTokyo.jetsPlayers) === null || _a === void 0 ? void 0 : _a.includes(this.getPlayerId())) {
                        label += formatTextIcons(" (- " + argsLeaveTokyo.jetsDamage + " [heart])");
                    }
                    this.addActionButton('stayInTokyo_button', label, 'onStayInTokyo');
                    this.addActionButton('leaveTokyo_button', _("Leave Tokyo"), 'onLeaveTokyo');
                    if (!argsLeaveTokyo.canYieldTokyo) {
                        this.startActionTimer('stayInTokyo_button', 5);
                        dojo.addClass('leaveTokyo_button', 'disabled');
                    }
                    break;
                case 'stealCostumeCard':
                    var argsStealCostumeCard = args;
                    this.addActionButton('endStealCostume_button', _("Skip"), 'endStealCostume', null, null, 'red');
                    if (!argsStealCostumeCard.canBuyFromPlayers) {
                        this.startActionTimer('endStealCostume_button', 5);
                    }
                    break;
                case 'changeForm':
                    var argsChangeForm = args;
                    this.addActionButton('changeForm_button', dojo.string.substitute(/* TODOME _(*/ "Change to ${otherForm}" /*)*/, { 'otherForm': _(argsChangeForm.otherForm) }) + formatTextIcons(" ( 1 [Energy])"), function () { return _this.changeForm(); });
                    this.addActionButton('skipChangeForm_button', /* TODOME _(*/ "Don't change form" /*)*/, function () { return _this.skipChangeForm(); });
                    dojo.toggleClass('changeForm_button', 'disabled', !argsChangeForm.canChangeForm);
                    break;
                case 'buyCard':
                    var argsBuyCard = args;
                    this.addActionButton('renew_button', _("Renew cards") + formatTextIcons(" ( 2 [Energy])"), 'onRenew');
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    if (argsBuyCard.canSell) {
                        this.addActionButton('goToSellCard_button', _("End turn and sell cards"), 'goToSellCard');
                    }
                    this.addActionButton('endTurn_button', argsBuyCard.canSell ? _("End turn without selling") : _("End turn"), 'onEndTurn', null, null, 'red');
                    if (!argsBuyCard.canBuyOrNenew && !argsBuyCard.canSell) {
                        this.startActionTimer('endTurn_button', 5);
                    }
                    break;
                case 'opportunistBuyCard':
                    this.addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');
                    if (!args.canBuy) {
                        this.startActionTimer('opportunistSkip_button', 5);
                    }
                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                    break;
                case 'sellCard':
                    this.addActionButton('endTurnSellCard_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;
                case 'cancelDamage':
                    this.onEnteringCancelDamage(args, true); // because it's multiplayer, enter action must be set here
                    break;
            }
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
    KingOfTokyo.prototype.isDarkEdition = function () {
        return false; // TODODE
    };
    KingOfTokyo.prototype.isDefaultFont = function () {
        return Number(this.prefs[201].value) == 1;
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
                html += "\n                <div id=\"wickedness-counter-wrapper-" + player.id + "\" class=\"counter\">\n                    <div class=\"icon wickedness\"></div> \n                    <span id=\"wickedness-counter-" + player.id + "\"></span>\n                </div>"; // TODOWI
            }
            html += "</div>";
            dojo.place(html, "player_board_" + player.id);
            if (gamedatas.kingkongExpansion || gamedatas.cybertoothExpansion || gamedatas.cthulhuExpansion) {
                var html_1 = "<div class=\"counters\">";
                if (gamedatas.kingkongExpansion) {
                    html_1 += "\n                    <div id=\"tokyo-tower-counter-wrapper-" + player.id + "\" class=\"counter tokyo-tower-tooltip\">\n                        <div class=\"tokyo-tower-icon-wrapper\"><div class=\"tokyo-tower-icon\"></div></div>\n                        <span id=\"tokyo-tower-counter-" + player.id + "\"></span>&nbsp;/&nbsp;3\n                    </div>";
                }
                if (gamedatas.cybertoothExpansion) {
                    html_1 += "\n                    <div id=\"berserk-counter-wrapper-" + player.id + "\" class=\"counter berserk-tooltip\">\n                        <div class=\"berserk-icon-wrapper\">\n                            <div id=\"player-panel-berserk-" + player.id + "\" class=\"berserk icon " + (player.berserk ? 'active' : '') + "\"></div>\n                        </div>\n                    </div>";
                }
                if (gamedatas.cthulhuExpansion) {
                    html_1 += "\n                    <div id=\"cultist-counter-wrapper-" + player.id + "\" class=\"counter cultist-tooltip\">\n                        <div class=\"icon cultist\"></div>\n                        <span id=\"cultist-counter-" + player.id + "\"></span>\n                    </div>";
                }
                html_1 += "</div>";
                dojo.place(html_1, "player_board_" + player.id);
                if (gamedatas.kingkongExpansion) {
                    var tokyoTowerCounter = new ebg.counter();
                    tokyoTowerCounter.create("tokyo-tower-counter-" + player.id);
                    tokyoTowerCounter.setValue(player.tokyoTowerLevels.length);
                    _this.tokyoTowerCounters[playerId] = tokyoTowerCounter;
                }
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
            dojo.place("<div class=\"player-tokens\">\n                <div id=\"player-board-shrink-ray-tokens-" + player.id + "\" class=\"player-token shrink-ray-tokens\"></div>\n                <div id=\"player-board-poison-tokens-" + player.id + "\" class=\"player-token poison-tokens\"></div>\n            </div>", "player_board_" + player.id);
            if (!eliminated) {
                _this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
                _this.setPoisonTokens(playerId, player.poisonTokens);
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
        this.playerTables = this.getOrderedPlayers().map(function (player) {
            var playerId = Number(player.id);
            var playerWithGoldenScarab = gamedatas.anubisExpansion && playerId === gamedatas.playerWithGoldenScarab;
            return new PlayerTable(_this, player, gamedatas.playersCards[playerId], gamedatas.playersWickednessTiles[playerId], playerWithGoldenScarab);
        });
    };
    KingOfTokyo.prototype.getPlayerTable = function (playerId) {
        return this.playerTables.find(function (playerTable) { return playerTable.playerId === Number(playerId); });
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
    KingOfTokyo.prototype.replaceMonsterChoiceByTable = function () {
        if (document.getElementById('monster-pick')) {
            this.fadeOutAndDestroy('monster-pick');
        }
        if (dojo.hasClass('kot-table', 'pickMonster')) {
            dojo.removeClass('kot-table', 'pickMonster');
            this.tableManager.setAutoZoomAndPlacePlayerTables();
            this.tableCenter.getVisibleCards().updateDisplay();
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.updateDisplay(); });
        }
    };
    KingOfTokyo.prototype.getStateName = function () {
        return this.gamedatas.gamestate.name;
    };
    KingOfTokyo.prototype.onVisibleCardClick = function (stock, cardId, from) {
        if (from === void 0) { from = 0; }
        if (!cardId) {
            return;
        }
        if (dojo.hasClass(stock.container_div.id + "_item_" + cardId, 'disabled')) {
            stock.unselectItem(cardId);
            return;
        }
        var stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            this.chooseInitialCard(cardId);
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
        else if (stateName === 'buyCard' || stateName === 'opportunistBuyCard') {
            this.tableCenter.removeOtherCardsFromPick(cardId);
            this.buyCard(cardId, from);
        }
    };
    KingOfTokyo.prototype.setBuyDisabledCard = function (args, playerEnergy) {
        if (args === void 0) { args = null; }
        if (playerEnergy === void 0) { playerEnergy = null; }
        if (!this.isCurrentPlayerActive()) {
            return;
        }
        var stateName = this.getStateName();
        if (stateName !== 'buyCard' && stateName !== 'opportunistBuyCard' && stateName !== 'stealCostumeCard') {
            return;
        }
        if (args === null) {
            args = this.gamedatas.gamestate.args;
        }
        if (playerEnergy === null) {
            playerEnergy = this.energyCounters[this.getPlayerId()].getValue();
        }
        Object.keys(args.cardsCosts).forEach(function (cardId) {
            var id = Number(cardId);
            var disabled = args.unbuyableIds.some(function (disabledId) { return disabledId == id; }) || args.cardsCosts[id] > playerEnergy;
            var cardDiv = document.querySelector("div[id$=\"_item_" + id + "\"]");
            if (cardDiv && cardDiv.closest('.card-stock') !== null) {
                dojo.toggleClass(cardDiv, 'disabled', disabled);
            }
        });
        // renew button
        if (document.getElementById('renew_button')) {
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
            var _loop_5 = function (i) {
                document.getElementById(popinId + "_set" + i).addEventListener('click', function () {
                    _this.setLeaveTokyoUnder(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            };
            for (var i = maxHealth; i > 0; i--) {
                _loop_5(i);
            }
            var _loop_6 = function (i) {
                document.getElementById(popinId + "_setStay" + i).addEventListener('click', function () {
                    _this.setStayTokyoOver(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            };
            for (var i = maxHealth + 1; i > 2; i--) {
                _loop_6(i);
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
        var _loop_7 = function (i) {
            if (!document.getElementById(popinId + "_set" + i)) {
                dojo.place("<button class=\"action-button bgabutton " + (this_4.gamedatas.leaveTokyoUnder === i ? 'bgabutton_blue' : 'bgabutton_gray') + " autoLeaveButton\" id=\"" + popinId + "_set" + i + "\">\n                    " + (i - 1) + "\n                </button>", popinId + "-buttons", 'first');
                document.getElementById(popinId + "_set" + i).addEventListener('click', function () {
                    _this.setLeaveTokyoUnder(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            }
        };
        var this_4 = this;
        for (var i = 11; i <= maxHealth; i++) {
            _loop_7(i);
        }
        var _loop_8 = function (i) {
            if (!document.getElementById(popinId + "_setStay" + i)) {
                dojo.place("<button class=\"action-button bgabutton " + (this_5.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray') + " autoStayButton " + (this_5.gamedatas.leaveTokyoUnder > 0 && i <= this_5.gamedatas.leaveTokyoUnder ? 'disabled' : '') + "\" id=\"" + popinId + "_setStay" + i + "\">\n                    " + (i - 1) + "\n                </button>", popinId + "-stay-buttons", 'first');
                document.getElementById(popinId + "_setStay" + i).addEventListener('click', function () {
                    _this.setStayTokyoOver(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            }
        };
        var this_5 = this;
        for (var i = 12; i <= maxHealth + 1; i++) {
            _loop_8(i);
        }
    };
    KingOfTokyo.prototype.closeAutoLeaveUnderPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoLeaveUnder");
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    };
    KingOfTokyo.prototype.setMimicToken = function (card) {
        var _this = this;
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.items.some(function (item) { return Number(item.id) == card.id; })) {
                _this.cards.placeMimicOnCard(playerTable.cards, card);
            }
        });
        this.setMimicTooltip(card);
    };
    KingOfTokyo.prototype.removeMimicToken = function (card) {
        var _this = this;
        this.setMimicTooltip(null);
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.items.some(function (item) { return Number(item.id) == card.id; })) {
                _this.cards.removeMimicOnCard(playerTable.cards, card);
            }
        });
    };
    KingOfTokyo.prototype.setMimicTooltip = function (mimickedCard) {
        var _this = this;
        this.playerTables.forEach(function (playerTable) {
            var mimicCardItem = playerTable.cards.items.find(function (item) { return Number(item.type) == 27; });
            if (mimicCardItem) {
                _this.cards.changeMimicTooltip("cards-" + playerTable.playerId + "_item_" + mimicCardItem.id, mimickedCard);
            }
        });
    };
    KingOfTokyo.prototype.pickMonster = function (monster) {
        if (!this.checkAction('pickMonster')) {
            return;
        }
        this.takeAction('pickMonster', {
            monster: monster
        });
    };
    KingOfTokyo.prototype.chooseInitialCard = function (id) {
        if (!this.checkAction('chooseInitialCard')) {
            return;
        }
        this.takeAction('chooseInitialCard', {
            id: id
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
        this.takeAction('useRapidHealing');
    };
    KingOfTokyo.prototype.useRapidCultist = function (type) {
        this.takeAction('useRapidCultist', { type: type });
    };
    KingOfTokyo.prototype.setSkipBuyPhase = function (skipBuyPhase) {
        this.takeAction('setSkipBuyPhase', {
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
    KingOfTokyo.prototype.goToChangeDie = function () {
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
    KingOfTokyo.prototype.onStayInTokyo = function () {
        if (!this.checkAction('stay')) {
            return;
        }
        this.takeAction('stay');
    };
    KingOfTokyo.prototype.onLeaveTokyo = function () {
        if (!this.checkAction('leave')) {
            return;
        }
        this.takeAction('leave');
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
    KingOfTokyo.prototype.buyCard = function (id, from) {
        if (!this.checkAction('buyCard')) {
            return;
        }
        this.takeAction('buyCard', {
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
    KingOfTokyo.prototype.changeMimickedCard = function (id) {
        if (!this.checkAction('changeMimickedCard')) {
            return;
        }
        this.takeAction('changeMimickedCard', {
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
    KingOfTokyo.prototype.onRenew = function () {
        if (!this.checkAction('renew')) {
            return;
        }
        this.takeAction('renew');
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
    KingOfTokyo.prototype.psychicProbeSkip = function () {
        if (!this.checkAction('psychicProbeSkip')) {
            return;
        }
        this.takeAction('psychicProbeSkip');
    };
    KingOfTokyo.prototype.skipChangeMimickedCard = function () {
        if (!this.checkAction('skipChangeMimickedCard', true)) {
            return;
        }
        this.takeAction('skipChangeMimickedCard');
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
        this.takeAction('setLeaveTokyoUnder', {
            under: under
        });
    };
    KingOfTokyo.prototype.setStayTokyoOver = function (over) {
        this.takeAction('setStayTokyoOver', {
            over: over
        });
    };
    KingOfTokyo.prototype.takeAction = function (action, data) {
        data = data || {};
        data.lock = true;
        this.ajaxcall("/koth/koth/" + action + ".html", data, this, function () { });
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
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['changeDie', ANIMATION_MS],
            ['rethrow3changeDie', ANIMATION_MS],
            ['changeCurseCard', ANIMATION_MS],
            ['takeWickednessTile', ANIMATION_MS],
            ['resolvePlayerDice', 500],
            ['changeTokyoTowerOwner', 500],
            ['changeForm', 500],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['maxHealth', 1],
            ['wickedness', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['setCardTokens', 1],
            ['removeCards', 1],
            ['setMimicToken', 1],
            ['removeMimicToken', 1],
            ['toggleRapidHealing', 1],
            ['updateLeaveTokyoUnder', 1],
            ['updateStayTokyoOver', 1],
            ['kotPlayerEliminated', 1],
            ['setPlayerBerserk', 1],
            ['cultist', 1],
            ['removeWickednessTiles', 1],
        ];
        notifs.forEach(function (notif) {
            dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
            _this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
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
    KingOfTokyo.prototype.notif_setInitialCards = function (notif) {
        this.tableCenter.setInitialCards(notif.args.cards);
    };
    KingOfTokyo.prototype.notif_resolveNumberDice = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points, ANIMATION_MS);
        this.animationManager.resolveNumberDice(notif.args);
        this.diceManager.resolveNumberDice(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDice = function (notif) {
        this.setHealth(notif.args.playerId, notif.args.health, ANIMATION_MS);
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
        this.setEnergy(notif.args.playerId, notif.args.energy, ANIMATION_MS);
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
    };
    KingOfTokyo.prototype.notif_playerEntersTokyo = function (notif) {
        this.getPlayerTable(notif.args.playerId).enterTokyo(notif.args.location);
        this.setPoints(notif.args.playerId, notif.args.points);
        this.setEnergy(notif.args.playerId, notif.args.energy);
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
            this.cards.moveToAnotherStock(this.getPlayerTable(notif.args.from).cards, this.getPlayerTable(notif.args.playerId).cards, card);
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
        this.tableManager.placePlayerTable(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeCards = function (notif) {
        var _this = this;
        if (notif.args.delay) {
            notif.args.delay = false;
            setTimeout(function () { return _this.notif_removeCards(notif); }, ANIMATION_MS);
        }
        else {
            this.getPlayerTable(notif.args.playerId).removeCards(notif.args.cards);
            this.tableManager.placePlayerTable(); // adapt after removed cards
        }
    };
    KingOfTokyo.prototype.notif_setMimicToken = function (notif) {
        this.setMimicToken(notif.args.card);
    };
    KingOfTokyo.prototype.notif_removeMimicToken = function (notif) {
        this.removeMimicToken(notif.args.card);
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
    KingOfTokyo.prototype.notif_toggleRapidHealing = function (notif) {
        if (notif.args.active) {
            this.addRapidHealingButton(notif.args.playerEnergy, notif.args.isMaxHealth);
        }
        else {
            this.removeRapidHealingButton();
        }
    };
    KingOfTokyo.prototype.notif_useCamouflage = function (notif) {
        if (notif.args.cancelDamageArgs) {
            this.gamedatas.gamestate.args = notif.args.cancelDamageArgs;
            this.updatePageTitle();
            this.onEnteringCancelDamage(notif.args.cancelDamageArgs, this.isCurrentPlayerActive());
        }
        else {
            this.diceManager.showCamouflageRoll(notif.args.diceValues);
        }
    };
    KingOfTokyo.prototype.notif_changeDie = function (notif) {
        if (notif.args.psychicProbeRollDieArgs) {
            var isCurrentPlayerActive = this.isCurrentPlayerActive();
            this.onEnteringPsychicProbeRollDie(notif.args.psychicProbeRollDieArgs, isCurrentPlayerActive);
        }
        else {
            this.diceManager.changeDie(notif.args.dieId, notif.args.inTokyo, notif.args.toValue, notif.args.roll);
        }
    };
    KingOfTokyo.prototype.notif_rethrow3changeDie = function (notif) {
        this.diceManager.changeDie(notif.args.dieId, notif.args.inTokyo, notif.args.toValue, notif.args.roll);
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
    KingOfTokyo.prototype.getTokyoTowerLevels = function (playerId) {
        var levels = [];
        for (var property in this.towerLevelsOwners) {
            if (this.towerLevelsOwners[property] == playerId) {
                levels.push(Number(property));
            }
        }
        return levels;
    };
    KingOfTokyo.prototype.notif_changeTokyoTowerOwner = function (notif) {
        var playerId = notif.args.playerId;
        var previousOwner = this.towerLevelsOwners[notif.args.level];
        this.towerLevelsOwners[notif.args.level] = playerId;
        var previousOwnerTower = previousOwner == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(previousOwner).getTokyoTower();
        var newLevelTower = playerId == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(playerId).getTokyoTower();
        var previousOwnerTowerLevels = this.getTokyoTowerLevels(previousOwner);
        var newLevelTowerLevels = this.getTokyoTowerLevels(playerId);
        previousOwnerTower.setLevels(previousOwnerTowerLevels);
        newLevelTower.setLevels(newLevelTowerLevels);
        if (previousOwner != 0) {
            this.tokyoTowerCounters[previousOwner].toValue(previousOwnerTowerLevels.length);
        }
        if (playerId != 0) {
            this.tokyoTowerCounters[playerId].toValue(newLevelTowerLevels.length);
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
        this.wickednessTiles.moveToAnotherStock(this.tableCenter.getWickednessTilesStock(notif.args.level), this.getPlayerTable(notif.args.playerId).wickednessTiles, notif.args.tile);
        this.tableCenter.removeReducedWickednessTile(notif.args.level, notif.args.tile);
        this.tableManager.placePlayerTable(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeWickednessTiles = function (notif) {
        this.getPlayerTable(notif.args.playerId).removeWickednessTiles(notif.args.tiles);
        this.tableManager.placePlayerTable(); // adapt after removed cards
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
        this.checkHealthCultistButtonState();
    };
    KingOfTokyo.prototype.setMaxHealth = function (playerId, maxHealth) {
        this.gamedatas.players[playerId].maxHealth = maxHealth;
        this.checkRapidHealingButtonState();
        this.checkHealthCultistButtonState();
        var popinId = "discussion_bubble_autoLeaveUnder";
        if (document.getElementById(popinId)) {
            this.updateAutoLeavePopinButtons();
        }
    };
    KingOfTokyo.prototype.setEnergy = function (playerId, energy, delay) {
        if (delay === void 0) { delay = 0; }
        this.energyCounters[playerId].toValue(energy);
        this.getPlayerTable(playerId).setEnergy(energy, delay);
        this.checkBuyEnergyDrinkState(energy); // disable button if energy gets down to 0
        this.checkRapidHealingButtonState();
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
    KingOfTokyo.prototype.eliminatePlayer = function (playerId) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById("overall_player_board_" + playerId).classList.add('eliminated-player');
        if (!document.getElementById("dead-icon-" + playerId)) {
            dojo.place("<div id=\"dead-icon-" + playerId + "\" class=\"icon dead\"></div>", "player_board_" + playerId);
        }
        this.getPlayerTable(playerId).eliminatePlayer();
        this.tableManager.placePlayerTable(); // because all player's card were removed
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
    };
    KingOfTokyo.prototype.getLogCardName = function (logType) {
        if (logType >= 2000) {
            return this.wickednessTiles.getCardName(logType - 2000);
        }
        if (logType >= 1000) {
            return this.curseCards.getCardName(logType - 1000);
        }
        else {
            return this.cards.getCardName(logType, 'text-only');
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
                if (args.card_name) {
                    var types = null;
                    if (typeof args.card_name == 'number') {
                        types = [args.card_name];
                    }
                    else if (typeof args.card_name == 'string' && args.card_name[0] >= '0' && args.card_name[0] <= '9') {
                        types = args.card_name.split(',').map(function (cardType) { return Number(cardType); });
                    }
                    if (types !== null) {
                        var names = types.map(function (cardType) { return _this.getLogCardName(cardType); });
                        args.card_name = "<strong>" + names.join(', ') + "</strong>";
                    }
                }
                for (var property in args) {
                    if (((_b = (_a = args[property]) === null || _a === void 0 ? void 0 : _a.indexOf) === null || _b === void 0 ? void 0 : _b.call(_a, ']')) > 0) {
                        args[property] = formatTextIcons(_(args[property]));
                    }
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
    return declare("bgagame.koth", ebg.core.gamegui, new KingOfTokyo());
});