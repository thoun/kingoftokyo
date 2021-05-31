function slideToObjectAndAttach(game, object, destinationId, posX, posY) {
    var destination = document.getElementById(destinationId);
    if (destination.contains(object)) {
        return;
    }
    object.style.zIndex = '10';
    var animation = (posX || posY) ?
        game.slideToObjectPos(object, destinationId, posX, posY) :
        game.slideToObject(object, destinationId);
    dojo.connect(animation, 'onEnd', dojo.hitch(this, function () {
        object.style.top = 'unset';
        object.style.left = 'unset';
        object.style.position = 'relative';
        object.style.zIndex = 'unset';
        destination.appendChild(object);
    }));
    animation.play();
}
function formatTextIcons(rawText) {
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
        .replace(/\[keep\]/ig, '<span class="card-keep-text"><span class="outline">Keep</span><span class="text">Keep</span></span>');
}
var CARD_WIDTH = 132;
var CARD_HEIGHT = 185;
var Cards = /** @class */ (function () {
    function Cards(game) {
        this.game = game;
    }
    Cards.prototype.setupCards = function (stocks) {
        stocks.forEach(function (stock) {
            var keepcardsurl = g_gamethemeurl + "img/keep-cards.jpg";
            for (var id = 1; id <= 48; id++) { // keep
                stock.addItemType(id, id, keepcardsurl, id - 1);
            }
            var discardcardsurl = g_gamethemeurl + "img/discard-cards.jpg";
            for (var id = 101; id <= 118; id++) { // discard
                stock.addItemType(id, id, discardcardsurl, id - 101);
            }
        });
    };
    Cards.prototype.getCardUniqueId = function (color, value) {
        return color * 100 + value;
    };
    Cards.prototype.getCardWeight = function (color, value) {
        var displayedNumber = value;
        if (displayedNumber === 70 || displayedNumber === 90) {
            displayedNumber /= 10;
        }
        return displayedNumber * 100 + color;
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
        cards.forEach(function (card) { return stock.addToStockWithId(card.type, "" + card.id, from); });
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
    Cards.prototype.getCardNamePoisition = function (cardTypeId) {
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
            case 102: return [30, 80];
            case 106:
            case 107: return [35, 65];
            case 111: return [35, 95];
            case 112: return [35, 35];
            case 113: return [35, 65];
            case 114: return [35, 95];
            case 115: return [0, 80];
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
            //case 49: return 5;
            //case 50: return 3;
            //case 51: return 4;
            //case 52: return 6;
            //case 53: return 3;
            //case 54: return 4;
            //case 55: return 4;
            //case 56: return 3;
            //case 57: return 3;
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
            //case 119: return 6;
            //case 120: return 2;
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
            //case 49: return _("Cannibalistic");
            //case 50: return _("Intimidating Roar");
            //case 51: return _("Monster Sidekick");
            //case 52: return _("Reflective Hide");
            //case 53: return _("Sleep Walker");
            //case 54: return _("Super Jump");
            //case 55: return _("Throw a Tanker");
            //case 56: return _("Thunder Stomp");
            //case 57: return _("Unstable DNA");
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
            //case 119: return _("Amusement Park");
            //case 120: return _("Army");
        }
        return null;
    };
    Cards.prototype.getCardName = function (cardTypeId, state) {
        var coloredCardName = this.getColoredCardName(cardTypeId);
        if (state == 'text-only') {
            return coloredCardName.replace(/\[(\w+)\]/g, '');
        }
        else if (state == 'span') {
            var first_1 = true;
            return coloredCardName.replace(/\[(\w+)\]/g, function (index, color) {
                var span = "<span style=\"-webkit-text-stroke-color: #" + color + ";\">";
                if (first_1) {
                    first_1 = false;
                }
                else {
                    span = "</span>" + span;
                }
                return span;
            }) + ("" + (first_1 ? '' : '</span>'));
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
            case 32: return _("<strong>You can buy Power cards from other monsters.</strong> Pay them the [Energy] cost.");
            case 33: return _("Before resolving your dice, you may <strong>change one die to any result</strong>. Discard when used.");
            case 34: return _("When you score [dice2][dice2][dice2] or more, <strong>add [diceSmash][diceSmash] to your Roll</strong>.");
            case 35: return _("Give one <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each <i>Poison</i> token they have on them.</strong> A <i>Poison</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 36: return _("You can reroll a die of your choice after the last Roll of each other Monster. If the reroll [diceHeart], discard this card.");
            case 37: return _("Spend 2[Energy] at any time to <strong>gain 1[Heart].</strong>");
            case 38: return _("When gain [Heart], <strong>gain 1 extra [Heart].</strong>");
            case 39: return _("At the end of a turn, if you have the fewest [Star], <strong>gain 1 [Star].</strong>");
            case 40: return _("Give 1 <i>Shrink Ray</i> to each Monster you Smash with your [diceSmash]. <strong>At the beginning of their turn, Monster roll 1 less dice for each <i>Shrink Ray</i> token they have on them</strong>. A <i>Shrink Ray</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 41: return _("Place 3 <i>Smoke</i> counters on this card. <strong>Spend 1 <i>Smoke</i> counter for an extra Roll.</strong> Discard this card when all <i>Smoke</i> counters are spent.");
            case 42: return _("At the end of your turn <strong>gain 1[Energy] if you have no [Energy].</strong>");
            case 43: return _("<strong>If you roll at least one [diceSmash], add [diceSmash]</strong> to your Roll.");
            case 44: return _("Before resolving your dice, you can spend 2[Energy] to <strong>change one of your dice to any result.</strong>");
            case 45: return _("Spend 1[Energy] to <strong>get 1 extra die Roll.</strong>");
            case 46: return _("<strong>Gain 1 extra [Star]</strong> when beginning your turn in Tokyo. If you are in Tokyo and you roll at least one [diceSmash], <strong>add [diceSmash] to your Roll.</strong>");
            case 47: return _("When you lose 2[Heart] or more <strong>gain 1[Energy].</strong>");
            case 48: return _("<strong>Spend 2[Energy] to lose [Heart]<strong> this turn.");
            //case 49: return _("When you do damage gain 1[Heart].");
            //case 50: return _("The monsters in Tokyo must yield if you damage them.");
            //case 51: return _("If someone kills you, Go back to 10[Heart] and lose all your [Star]. If either of you or your killer win, or all other players are eliminated then you both win. If your killer is eliminated then you are also. If you are eliminated a second time this card has no effect.");
            //case 52: return _("If you suffer damage the monster that inflicted the damage suffers 1 as well.");
            //case 53: return _("Spend 3[Energy] to gain 1[Star].");
            //case 54: return _("Once each turn you may spend 1[Energy] to negate 1 damage you are receiving.");
            //case 55: return _("On a turn you deal 3 or more damage gain 2[Star].");
            //case 56: return _("If you score 4[Star] in a turn, all players roll one less die until your next turn.");
            //case 57: return _("If you yield Tokyo you can take any card the recipient has and give him this card.");
            // DISCARD
            case 101: return _("<strong>+ 3[Star].</strong>");
            case 102: return _("<strong>+ 2[Star].</strong>");
            case 103: return _("<strong>+ 1[Star].</strong>");
            case 104: return _("<strong>+ 2[Star] and take control of Tokyo</strong> if you don't already control it.");
            case 105: return _("<strong>+ 9[Energy].</strong>");
            case 106:
            case 107: return _("<strong>All other Monsters lose 5[Star].</strong>");
            case 108: return _("<strong>All other Monsters lose 2[Heart].</strong>");
            case 109: return _("<strong>Take another turn</strong> after this one");
            case 110: return _("<strong>+ 2[Star] and deal all other monsters lose 3[Heart].</strong>");
            case 111: return _("<strong>+ 2[Heart]</strong>");
            case 112: return _("<strong>All Monsters</strong> (including you) <strong>lose 3[Heart].</strong>");
            case 113: return _("<strong>+ 5[Star] -4[Heart].</strong>");
            case 114: return _("<strong>+ 2[Star] -2[Heart].</strong>");
            case 115: return _("<strong>+ 2[Star] +3[Heart].</strong>");
            case 116: return _("<strong>+ 4[Star].");
            case 117: return _("<strong>+ 4[Star] -3[Heart].</strong>");
            case 118: return _("<strong>+ 2[Star] and all other Monsters lose 1[Energy] for every 2[Energy]</strong> they have.");
            //case 119: return _("<strong>+ 4[Star].");
            //case 120: return _("(+ 1[Star] and suffer one damage) for each card you have.");
        }
        return null;
    };
    Cards.prototype.getTooltip = function (cardTypeId) {
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId, 'text-only') + "</strong></p>\n            <p class=\"cost\">" + dojo.string.substitute(_("Cost : ${cost}"), { 'cost': this.getCardCost(cardTypeId) }) + " <span class=\"icon energy\"></span></p>\n            <p>" + formatTextIcons(this.getCardDescription(cardTypeId)) + "</p>\n        </div>";
        return tooltip;
    };
    Cards.prototype.setupNewCard = function (cardDiv, cardType) {
        var type = cardType < 100 ? _('Keep') : _('Discard');
        var description = formatTextIcons(this.getCardDescription(cardType));
        var position = this.getCardNamePoisition(cardType);
        cardDiv.innerHTML = "<div class=\"bottom\"></div>\n        <div class=\"name-wrapper\" " + (position ? "style=\"left: " + position[0] + "px; top: " + position[1] + "px;\"" : '') + ">\n            <div class=\"outline\">" + this.getCardName(cardType, 'span') + "</div>\n            <div class=\"text\">" + this.getCardName(cardType, 'text-only') + "</div>\n        </div>\n        <div class=\"type-wrapper " + (cardType < 100 ? 'keep' : 'discard') + "\">\n            <div class=\"outline\">" + type + "</div>\n            <div class=\"text\">" + type + "</div>\n        </div>\n        \n        <div class=\"description-wrapper\"><div>" + description + "</div></div>\n        ";
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    return Cards;
}());
var isDebug = window.location.host == 'studio.boardgamearena.com';
var log = isDebug ? console.log.bind(window.console) : function () { };
var POINTS_DEG = [25, 40, 56, 73, 89, 105, 122, 138, 154, 170, 187, 204, 221, 237, 254, 271, 288, 305, 322, 339, 359];
var HEALTH_DEG = [360, 326, 301, 274, 249, 226, 201, 174, 149, 122, 98, 64, 39];
var PlayerTable = /** @class */ (function () {
    function PlayerTable(game, player, cards) {
        var _this = this;
        this.game = game;
        this.player = player;
        this.playerId = Number(player.id);
        this.playerNo = Number(player.player_no);
        this.monster = Number(player.monster);
        dojo.place("\n        <div id=\"player-table-" + player.id + "\" class=\"player-table whiteblock " + (Number(player.eliminated) > 0 ? 'eliminated' : '') + "\">\n            <div id=\"player-name-" + player.id + "\" class=\"player-name " + (game.isDefaultFont() ? 'standard' : 'goodgirl') + "\" style=\"color: #" + player.color + "\">\n                <div class=\"outline" + (player.color === '000000' ? ' white' : '') + "\">" + player.name + "</div>\n                <div class=\"text\">" + player.name + "</div>\n            </div> \n            <div class=\"monster-board-wrapper\">\n                <div class=\"blue wheel\" id=\"blue-wheel-" + player.id + "\"></div>\n                <div class=\"red wheel\" id=\"red-wheel-" + player.id + "\"></div>\n                <div id=\"monster-board-" + player.id + "\" class=\"monster-board monster" + this.monster + "\">\n                    <div id=\"monster-figure-" + player.id + "\" class=\"monster-figure monster" + this.monster + "\"></div>\n                </div>  \n            </div> \n            <div id=\"cards-" + player.id + "\" class=\"player-cards\"></div>      \n        </div>\n\n        ", 'table');
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
        this.initialLocation = Number(player.location);
        this.setPoints(Number(player.score));
        this.setHealth(Number(player.health));
    }
    PlayerTable.prototype.initPlacement = function () {
        if (this.initialLocation > 0) {
            this.enterTokyo(this.initialLocation);
        }
    };
    PlayerTable.prototype.enterTokyo = function (location) {
        slideToObjectAndAttach(this.game, document.getElementById("monster-figure-" + this.playerId), "tokyo-" + (location == 2 ? 'bay' : 'city'));
    };
    PlayerTable.prototype.leaveTokyo = function () {
        slideToObjectAndAttach(this.game, document.getElementById("monster-figure-" + this.playerId), "monster-board-" + this.playerId);
    };
    PlayerTable.prototype.removeCards = function (cards) {
        var _this = this;
        var cardsIds = cards.map(function (card) { return card.id; });
        cardsIds.forEach(function (id) { return _this.cards.removeFromStockById('' + id); });
    };
    PlayerTable.prototype.setPoints = function (points, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        setTimeout(function () { return document.getElementById("blue-wheel-" + _this.playerId).style.transform = "rotate(" + POINTS_DEG[points] + "deg)"; }, delay);
    };
    PlayerTable.prototype.setHealth = function (health, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        setTimeout(function () { return document.getElementById("red-wheel-" + _this.playerId).style.transform = "rotate(" + (health > 12 ? 22 : HEALTH_DEG[health]) + "deg)"; }, delay);
    };
    PlayerTable.prototype.eliminatePlayer = function () {
        this.cards.removeAll();
        this.game.fadeOutAndDestroy("player-board-monster-figure-" + this.playerId);
        dojo.addClass("player-table-" + this.playerId, 'eliminated');
    };
    PlayerTable.prototype.setActivePlayer = function (active) {
        dojo.toggleClass("monster-board-" + this.playerId, 'active', active);
    };
    PlayerTable.prototype.setFont = function (prefValue) {
        var defaultFont = prefValue === 1;
        dojo.toggleClass("player-name-" + this.playerId, 'standard', defaultFont);
        dojo.toggleClass("player-name-" + this.playerId, 'goodgirl', !defaultFont);
    };
    return PlayerTable;
}());
var __spreadArray = (this && this.__spreadArray) || function (to, from) {
    for (var i = 0, il = from.length, j = to.length; i < il; i++, j++)
        to[j] = from[i];
    return to;
};
var PLAYER_TABLE_WIDTH = 420;
var PLAYER_BOARD_HEIGHT = 247;
var CARDS_PER_ROW = 3;
var CENTER_TABLE_WIDTH = 420;
var TABLE_MARGIN = 20;
var PLAYER_TABLE_WIDTH_MARGINS = PLAYER_TABLE_WIDTH + 2 * TABLE_MARGIN;
var PLAYER_BOARD_HEIGHT_MARGINS = PLAYER_BOARD_HEIGHT + 2 * TABLE_MARGIN;
var CENTER_TABLE_WIDTH_MARGINS = CENTER_TABLE_WIDTH + 2 * TABLE_MARGIN;
var DISPOSITION_2_COLUMNS = [];
var DISPOSITION_3_COLUMNS = [];
DISPOSITION_2_COLUMNS[2] = [[0], [1]];
DISPOSITION_2_COLUMNS[3] = [[0], [1, 2]];
DISPOSITION_2_COLUMNS[4] = [[1, 0], [2, 3]];
DISPOSITION_2_COLUMNS[5] = [[1, 0], [2, 3, 4]];
DISPOSITION_2_COLUMNS[6] = [[1, 0], [2, 3, 4, 5]];
DISPOSITION_3_COLUMNS[2] = [[], [0], [1]];
DISPOSITION_3_COLUMNS[3] = [[1], [0], [2]];
DISPOSITION_3_COLUMNS[4] = [[1], [2, 0], [3]];
DISPOSITION_3_COLUMNS[5] = [[2, 1], [0], [3, 4]];
DISPOSITION_3_COLUMNS[6] = [[2, 1], [5, 0], [3, 4]];
var TableManager = /** @class */ (function () {
    function TableManager(game, playerTables) {
        var _this = this;
        this.game = game;
        this.setPlayerTables(playerTables);
        this.game.onScreenWidthChange = function () { return _this.placePlayerTable(); };
    }
    TableManager.prototype.setPlayerTables = function (playerTables) {
        var currentPlayerId = Number(this.game.getPlayerId());
        var playerTablesOrdered = playerTables.filter(function (playerTable) { return !!playerTable; }).sort(function (a, b) { return a.playerNo - b.playerNo; });
        var playerIndex = playerTablesOrdered.findIndex(function (playerTable) { return playerTable.playerId === currentPlayerId; });
        if (playerIndex) { // not spectator (or 0)            
            this.playerTables = __spreadArray(__spreadArray([], playerTablesOrdered.slice(playerIndex)), playerTablesOrdered.slice(0, playerIndex));
        }
        else { // spectator
            this.playerTables = playerTablesOrdered.filter(function (playerTable) { return !!playerTable; });
        }
    };
    TableManager.prototype.placePlayerTable = function () {
        var _this = this;
        var height = 0;
        var players = this.playerTables.length;
        var tableDiv = document.getElementById('table');
        var tableWidth = tableDiv.clientWidth;
        var availableColumns = Math.min(3, Math.floor(tableWidth / PLAYER_TABLE_WIDTH_MARGINS));
        var idealColumns = players == 2 ? 2 : 3;
        var tableCenterDiv = document.getElementById('table-center');
        tableCenterDiv.style.left = (tableWidth - CENTER_TABLE_WIDTH_MARGINS) / 2 + "px";
        tableCenterDiv.style.top = "0px";
        if (availableColumns === 1) {
            var top_1 = tableCenterDiv.clientHeight;
            this.playerTables.forEach(function (playerTable) {
                var playerTableDiv = document.getElementById("player-table-" + playerTable.playerId);
                playerTableDiv.style.left = (tableWidth - CENTER_TABLE_WIDTH_MARGINS) / 2 + "px";
                playerTableDiv.style.top = top_1 + "px";
                top_1 += _this.getPlayerTableHeight(playerTable);
                height = Math.max(height, top_1);
            });
        }
        else {
            var columns_1 = Math.min(availableColumns, idealColumns);
            var dispositionModel = (columns_1 === 3 ? DISPOSITION_3_COLUMNS : DISPOSITION_2_COLUMNS)[players];
            var disposition_1 = dispositionModel.map(function (columnIndexes) { return columnIndexes.map(function (columnIndex) { return ({
                id: _this.playerTables[columnIndex].playerId,
                height: _this.getPlayerTableHeight(_this.playerTables[columnIndex]),
            }); }); });
            var tableCenter_1 = (columns_1 === 3 ? tableWidth : tableWidth - PLAYER_TABLE_WIDTH_MARGINS) / 2;
            var centerColumnIndex_1 = columns_1 === 3 ? 1 : 0;
            if (columns_1 === 2) {
                tableCenterDiv.style.left = tableCenter_1 - CENTER_TABLE_WIDTH_MARGINS / 2 + "px";
            }
            // we always compute "center" column first
            (columns_1 === 3 ? [1, 0, 2] : [0, 1]).forEach(function (columnIndex) {
                var leftColumn = columnIndex === 0 && columns_1 === 3;
                var centerColumn = centerColumnIndex_1 === columnIndex;
                var rightColumn = columnIndex > centerColumnIndex_1;
                var playerOverTable = centerColumn && disposition_1[columnIndex].length > 1;
                var dispositionColumn = disposition_1[columnIndex];
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
                        playerTableDiv.style.left = tableCenter_1 - PLAYER_TABLE_WIDTH_MARGINS / 2 + "px";
                    }
                    else if (rightColumn) {
                        playerTableDiv.style.left = tableCenter_1 + PLAYER_TABLE_WIDTH_MARGINS / 2 + "px";
                    }
                    else if (leftColumn) {
                        playerTableDiv.style.left = (tableCenter_1 - PLAYER_TABLE_WIDTH_MARGINS / 2) - PLAYER_TABLE_WIDTH_MARGINS + "px";
                    }
                    playerTableDiv.style.top = top + "px";
                    top += playerInfos.height;
                    if (centerColumn && index == 0 && disposition_1[columnIndex].length > 1) {
                        tableCenterDiv.style.top = playerInfos.height + "px";
                        top += tableCenterDiv.clientHeight + 20;
                    }
                    height = Math.max(height, top);
                });
            });
        }
        tableDiv.style.height = height + "px";
    };
    TableManager.prototype.getPlayerTableHeight = function (playerTable) {
        var cardRows = Math.max(1, Math.ceil(playerTable.cards.items.length / CARDS_PER_ROW));
        return PLAYER_BOARD_HEIGHT_MARGINS + ((CARD_HEIGHT + 5) * cardRows);
    };
    return TableManager;
}());
var DieFaceSelector = /** @class */ (function () {
    function DieFaceSelector(nodeId, dieValue, inTokyo) {
        var _this = this;
        this.nodeId = nodeId;
        this.dieValue = dieValue;
        var _loop_1 = function (face) {
            var faceId = nodeId + "-face" + face;
            var html = "<div id=\"" + faceId + "\" class=\"dice-icon dice" + face + " " + (dieValue == face ? 'disabled' : '') + "\">";
            if (face === 4 && inTokyo) {
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
var DiceManager = /** @class */ (function () {
    function DiceManager(game, setupDice) {
        this.game = game;
        this.dice = [];
        this.dieFaceSelectors = [];
        // TODO use setupDice ?
    }
    DiceManager.prototype.hideLock = function () {
        dojo.addClass('locked-dice', 'hide-lock');
    };
    DiceManager.prototype.showLock = function () {
        dojo.removeClass('locked-dice', 'hide-lock');
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
    DiceManager.prototype.setDiceForChangeDie = function (dice, args, inTokyo, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        this.action = 'change';
        if (this.dice.length) {
            return;
        }
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = dice;
        var onlyHerdCuller = args.hasHerdCuller && !args.hasPlotTwist && !args.hasStretchy;
        this.changeDieArgs = args;
        dice.forEach(function (die) {
            var divId = "dice" + die.id;
            dojo.place(_this.createDiceHtml(die, inTokyo), "dice-selector" + die.value);
            var selectable = isCurrentPlayerActive && (!onlyHerdCuller || die.value !== 1);
            dojo.toggleClass(divId, 'selectable', selectable);
            _this.addDiceRollClass(die);
            if (selectable) {
                document.getElementById(divId).addEventListener('click', function () { return _this.dieClick(die); });
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
            dojo.place(_this.createDiceHtml(die, inTokyo), "dice-selector" + die.value);
            _this.addDiceRollClass(die);
        });
    };
    DiceManager.prototype.setDiceForPsychicProbe = function (dice, inTokyo, isCurrentPlayerActive) {
        var _this = this;
        this.action = 'psychicProbeRoll';
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            dojo.place(_this.createDiceHtml(die, inTokyo), "dice-selector" + die.value);
            _this.addDiceRollClass(die);
            if (isCurrentPlayerActive) {
                var divId = "dice" + die.id;
                document.getElementById(divId).addEventListener('click', function () { return _this.dieClick(die); });
            }
        });
        dojo.toggleClass('rolled-dice', 'selectable', isCurrentPlayerActive);
    };
    DiceManager.prototype.changeDie = function (dieId, toValue) {
        var die = this.dice.find(function (die) { return die.id == dieId; });
        if (die) {
            die.value = toValue;
        }
        var divId = "dice" + dieId;
        var div = document.getElementById(divId);
        if (div) {
            dojo.removeClass(div, "dice" + div.dataset.diceValue);
            div.dataset.diceValue = '' + toValue;
            dojo.addClass(div, "dice" + toValue);
            var list = div.getElementsByTagName('ol')[0];
            dojo.removeClass(list, 'no-roll');
            dojo.addClass(list, 'change-die-roll');
            list.dataset.roll = '' + toValue;
        }
    };
    DiceManager.prototype.showCamouflageRoll = function (diceValues) {
        var _this = this;
        this.clearDiceHtml();
        diceValues.forEach(function (dieValue, index) {
            var die = {
                id: index,
                value: dieValue,
                extra: false,
                locked: false,
                rolled: true,
            };
            dojo.place(_this.createDiceHtml(die, false), "dice-selector" + die.value);
            _this.addDiceRollClass(die);
        });
    };
    DiceManager.prototype.clearDiceHtml = function () {
        for (var i = 1; i <= 6; i++) {
            document.getElementById("locked-dice" + i).innerHTML = '';
            document.getElementById("dice-selector" + i).innerHTML = '';
        }
    };
    DiceManager.prototype.resolveNumberDice = function (args) {
        var _this = this;
        var dice = this.dice.filter(function (die) { return die.value === args.diceValue; });
        this.game.displayScoring("dice" + (dice[1] || dice[0]).id, '96c93c', args.deltaPoints, 1500);
        this.dice.filter(function (die) { return die.value === args.diceValue; }).forEach(function (die) { return _this.removeDice(die, 1000, 1500); });
    };
    DiceManager.prototype.resolveHealthDiceInTokyo = function () {
        var _this = this;
        this.dice.filter(function (die) { return die.value === 4; }).forEach(function (die) { return _this.removeDice(die, 1000); });
    };
    DiceManager.prototype.addDiceAnimation = function (diceValue, playerIds) {
        var _this = this;
        var dice = this.dice.filter(function (die) { return die.value === diceValue; });
        playerIds.forEach(function (playerId, playerIndex) {
            var destination = document.getElementById("monster-figure-" + playerId).getBoundingClientRect();
            dice.forEach(function (die, dieIndex) {
                var origin = document.getElementById("dice" + die.id).getBoundingClientRect();
                var animationId = "dice" + die.id + "-player" + playerId + "-animation";
                dojo.place("<div id=\"" + animationId + "\" class=\"animation animation" + diceValue + "\"></div>", "dice" + die.id);
                setTimeout(function () {
                    var middleIndex = dice.length - 1;
                    var deltaX = (dieIndex - middleIndex) * 220;
                    document.getElementById(animationId).style.transform = "translate(" + deltaX + "px, 100px) scale(1)";
                }, 50);
                setTimeout(function () {
                    var deltaX = destination.left - origin.left + 59;
                    var deltaY = destination.top - origin.top + 59;
                    document.getElementById(animationId).style.transition = "transform 0.5s ease-in";
                    document.getElementById(animationId).style.transform = "translate(" + deltaX + "px, " + deltaY + "px) scale(0.30)";
                }, 1000);
                if (playerIndex === playerIds.length - 1) {
                    setTimeout(function () { return _this.removeDice(die); }, 2500);
                }
            });
        });
    };
    DiceManager.prototype.resolveHealthDice = function (args) {
        this.addDiceAnimation(4, [args.playerId]);
    };
    DiceManager.prototype.resolveEnergyDice = function (args) {
        this.addDiceAnimation(5, [args.playerId]);
    };
    DiceManager.prototype.resolveSmashDice = function (args) {
        this.addDiceAnimation(6, args.smashedPlayersIds);
    };
    DiceManager.prototype.toggleLockDice = function (die, forcedLockValue) {
        if (forcedLockValue === void 0) { forcedLockValue = null; }
        die.locked = forcedLockValue === null ? !die.locked : forcedLockValue;
        var dieDiv = document.getElementById("dice" + die.id);
        slideToObjectAndAttach(this.game, dieDiv, die.locked ? "locked-dice" + die.value : "dice-selector" + die.value);
        this.activateRethrowButton();
    };
    DiceManager.prototype.activateRethrowButton = function () {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.dice.some(function (die) { return !die.locked; }));
        }
    };
    DiceManager.prototype.createDiceHtml = function (die, inTokyo) {
        var html = "<div id=\"dice" + die.id + "\" class=\"dice dice" + die.value + "\" data-dice-id=\"" + die.id + "\" data-dice-value=\"" + die.value + "\">\n        <ol class=\"die-list\" data-roll=\"" + die.value + "\">";
        for (var dieFace = 1; dieFace <= 6; dieFace++) {
            html += "<li class=\"die-item " + (die.extra ? 'green' : 'black') + " side" + dieFace + "\" data-side=\"" + dieFace + "\"></li>";
        }
        html += "</ol>";
        if (die.value === 4 && inTokyo) {
            html += "<div class=\"icon forbidden\"></div>";
        }
        html += "</div>";
        return html;
    };
    DiceManager.prototype.getDiceDiv = function (die) {
        return document.getElementById("dice" + die.id);
    };
    DiceManager.prototype.createDice = function (die, selectable, inTokyo) {
        var _this = this;
        dojo.place(this.createDiceHtml(die, inTokyo), die.locked ? "locked-dice" + die.value : "dice-selector" + die.value);
        this.addDiceRollClass(die);
        if (selectable) {
            this.getDiceDiv(die).addEventListener('click', function () { return _this.dieClick(die); });
        }
    };
    DiceManager.prototype.dieClick = function (die) {
        if (this.action === 'move') {
            this.toggleLockDice(die);
        }
        else if (this.action === 'change') {
            this.toggleBubbleChangeDie(die);
        }
        else if (this.action === 'psychicProbeRoll') {
            this.game.psychicProbeRollDie(die.id);
        }
    };
    DiceManager.prototype.addDiceRollClass = function (die) {
        var dieDiv = this.getDiceDiv(die);
        if (die.rolled) {
            dieDiv.classList.add('rolled');
            setTimeout(function () { return dieDiv.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll'); }, 200);
            setTimeout(function () { return dieDiv.classList.remove('rolled'); }, 1200);
        }
        else {
            dieDiv.getElementsByClassName('die-list')[0].classList.add('no-roll');
        }
    };
    DiceManager.prototype.removeDice = function (die, duration, delay) {
        if (duration) {
            this.game.fadeOutAndDestroy("dice" + die.id, duration, delay);
        }
        else {
            dojo.destroy("dice" + die.id);
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
    DiceManager.prototype.toggleBubbleChangeDie = function (die) {
        var _this = this;
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
            var args_1 = this.changeDieArgs;
            if (!this.dieFaceSelectors[die.id]) {
                this.dieFaceSelectors[die.id] = new DieFaceSelector(bubbleDieFaceSelectorId, die.value, args_1.inTokyo);
            }
            var dieFaceSelector_1 = this.dieFaceSelectors[die.id];
            if (creation) {
                var buttonText = _("Change die face with ${card_name}");
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
                dieFaceSelector_1.onChange = function (value) {
                    if (args_1.hasHerdCuller && die.value > 1) {
                        dojo.toggleClass(herdCullerButtonId_1, 'disabled', value != 1);
                    }
                    if (args_1.hasPlotTwist) {
                        dojo.toggleClass(plotTwistButtonId_1, 'disabled', value < 1);
                    }
                    if (args_1.hasStretchy) {
                        dojo.toggleClass(stretchyButtonId_1, 'disabled', value < 1);
                    }
                };
                bubble.addEventListener('click', function (event) { return event.stopImmediatePropagation(); });
            }
            if (die.value == dieFaceSelector_1.getValue()) {
                dieFaceSelector_1.reset(die.value);
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
            args_1.dice.filter(function (idie) { return idie.id != die.id; }).forEach(function (idie) { return _this.hideBubble(idie.id); });
            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
    };
    return DiceManager;
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
            var html = "<div class=\"die\">\n                <div class=\"die-face\">\n                    <div class=\"dice-icon dice4\">";
            if (args.inTokyo) {
                html += "<div class=\"icon forbidden\"></div>";
            }
            html += "</div>\n                </div>\n                <div id=\"" + nodeId + "-die" + index + "\" class=\"toggle-buttons\"></div>\n            </div>";
            dojo.place(html, nodeId);
            _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-heal", _('Heal'), function () { return _this.healSelected(index); }, false, true);
            _this.selections[index] = { action: 'heal' };
            if (args.shrinkRayTokens > 0) {
                _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-shrink-ray", _('Remove Shrink Ray token'), function () { return _this.shrinkRaySelected(index); }, args.inTokyo);
            }
            if (args.poisonTokens > 0) {
                _this.createToggleButton(nodeId + "-die" + index, nodeId + "-die" + index + "-poison", _('Remove Poison token'), function () { return _this.poisonSelected(index); }, args.inTokyo);
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
var ANIMATION_MS = 1500;
var PUNCH_SOUND_DURATION = 250;
var ZOOM_LEVELS = [0.25, 0.375, 0.5, 0.625, 0.75, 0.875, 1];
var ZOOM_LEVELS_MARGIN = [-300, -166, -100, -60, -33, -14, 0];
var LOCAL_STORAGE_ZOOM_KEY = 'KingOfTokyo-zoom';
var KingOfTokyo = /** @class */ (function () {
    function KingOfTokyo() {
        this.healthCounters = [];
        this.energyCounters = [];
        this.playerTables = [];
        this.zoom = 1;
        var zoomStr = localStorage.getItem(LOCAL_STORAGE_ZOOM_KEY);
        if (zoomStr) {
            this.setZoom(Number(zoomStr));
        }
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
        [1, 2, 3, 4, 5, 6].filter(function (i) { return !players.some(function (player) { return Number(player.monster) === i; }); }).forEach(function (i) {
            _this.dontPreloadImage("monster-board-" + (i + 1) + ".png");
            _this.dontPreloadImage("monster-figure-" + (i + 1) + ".png");
        });
        log("Starting game setup");
        this.gamedatas = gamedatas;
        log('gamedatas', gamedatas);
        this.createPlayerPanels(gamedatas);
        this.diceManager = new DiceManager(this, gamedatas.dice);
        this.cards = new Cards(this);
        this.createVisibleCards(gamedatas.visibleCards);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(function () { return _this.playerTables.forEach(function (playerTable) { return playerTable.initPlacement(); }); }, 200);
        this.setMimicToken(gamedatas.mimickedCard);
        var playerId = this.getPlayerId();
        if (players.some(function (player) { return player.rapidHealing && Number(player.id) === playerId; })) {
            var player = players.find(function (player) { return Number(player.id) === playerId; });
            this.addRapidHealingButton(player.energy, player.health >= player.maxHealth);
        }
        this.setupNotifications();
        this.setupPreferences();
        document.getElementById('zoom-out').addEventListener('click', function () { return _this.zoomOut(); });
        document.getElementById('zoom-in').addEventListener('click', function () { return _this.zoomIn(); });
        /*document.getElementById('test').addEventListener('click', () => this.notif_resolveSmashDice({
            args: {
                number: 3,
                smashedPlayersIds: [2343492, 2343493]
            }
        } as any));*/
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
        switch (stateName) {
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
                this.onEnteringChooseMimickedCard(args.args);
                break;
            case 'throwDice':
                this.onEnteringThrowDice(args.args);
                break;
            case 'changeDie':
                this.onEnteringChangeDie(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveDice':
                this.diceManager.hideLock();
                break;
            case 'resolveHeartDiceAction':
                this.onEnteringResolveHeartDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'buyCard':
                this.onEnteringBuyCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'sellCard':
                this.onEnteringSellCard();
                break;
            case 'endTurn':
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
    KingOfTokyo.prototype.onEnteringThrowDice = function (args) {
        var _this = this;
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? "last" : '');
        this.diceManager.showLock();
        var dice = args.dice;
        var isCurrentPlayerActive = this.isCurrentPlayerActive();
        this.diceManager.setDiceForThrowDice(dice, args.inTokyo, isCurrentPlayerActive);
        if (isCurrentPlayerActive) {
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', _("Rethrow dice") + (" (" + args.throwNumber + "/" + args.maxThrowNumber + ")"), function () { return _this.onRethrow(); }, !args.dice.some(function (dice) { return !dice.locked; }));
            }
            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3(); }, !args.rethrow3.hasDice3);
            }
            if (args.energyDrink.hasCard && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + formatTextIcons(" ( 1[Energy])"), function () { return _this.buyEnergyDrink(); });
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }
            if (args.hasSmokeCloud && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_smoke_cloud_button', _("Get extra die Roll") + " (<span class=\"smoke-cloud token\"></span>)", function () { return _this.useSmokeCloud(); });
            }
        }
    };
    KingOfTokyo.prototype.onEnteringChangeDie = function (args, isCurrentPlayerActive) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args, args.inTokyo, isCurrentPlayerActive);
        }
    };
    KingOfTokyo.prototype.onEnteringPsychicProbeRollDie = function (args, isCurrentPlayerActive) {
        this.diceManager.setDiceForPsychicProbe(args.dice, args.inTokyo, isCurrentPlayerActive);
    };
    KingOfTokyo.prototype.onEnteringResolveHeartDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.inTokyo);
            if (isCurrentPlayerActive) {
                dojo.place("<div id=\"heart-action-selector\" class=\"whiteblock\"></div>", 'rolled-dice-and-rapid-healing', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringCancelDamage = function (args) {
        if (args.dice) {
            this.diceManager.showCamouflageRoll(args.dice);
        }
        if (args.canThrowDices && !document.getElementById('throwCamouflageDice_button')) {
            this.addActionButton('throwCamouflageDice_button', _("Throw dice"), 'throwCamouflageDice');
        }
        else if (!args.canThrowDices && document.getElementById('throwCamouflageDice_button')) {
            dojo.destroy('throwCamouflageDice_button');
        }
        if (args.canUseWings && !document.getElementById('useWings_button')) {
            this.addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name} ( 2[Energy] )"), { 'card_name': this.cards.getCardName(48, 'text-only') })), 'useWings');
            if (args.playerEnergy < 2) {
                dojo.addClass('useWings_button', 'disabled');
            }
        }
        if (args.canSkipWings && !document.getElementById('skipWings_button')) {
            this.addActionButton('skipWings_button', dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cards.getCardName(48, 'text-only') }), 'skipWings');
        }
    };
    KingOfTokyo.prototype.onEnteringBuyCard = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        if (isCurrentPlayerActive) {
            this.visibleCards.setSelectionMode(1);
            if (args.canBuyFromPlayers) {
                this.playerTables.filter(function (playerTable) { return playerTable.playerId != _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            }
            if ((_a = args._private) === null || _a === void 0 ? void 0 : _a.pickCard) {
                this.showPickStock(args._private.pickCard);
            }
            args.disabledIds.forEach(function (id) { return document.querySelector("div[id$=\"_item_" + id + "\"]").classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringChooseMimickedCard = function (args) {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
            args.disabledIds.forEach(function (id) { return document.querySelector("div[id$=\"_item_" + id + "\"]").classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringSellCard = function () {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
        }
    };
    KingOfTokyo.prototype.onEnteringEndTurn = function () {
    };
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        log('Leaving state: ' + stateName);
        switch (stateName) {
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'opportunistChooseMimicCard':
                this.onLeavingChooseMimickedCard();
                break;
            case 'throwDice':
                document.getElementById('dice-actions').innerHTML = '';
                break;
            case 'resolveHeartDiceAction':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
                break;
            case 'sellCard':
                this.onLeavingSellCard();
                break;
            case 'cancelDamage':
                this.diceManager.removeAllDice();
                break;
        }
    };
    KingOfTokyo.prototype.onLeavingBuyCard = function () {
        this.visibleCards.setSelectionMode(0);
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(0); });
        this.hidePickStock();
    };
    KingOfTokyo.prototype.onLeavingChooseMimickedCard = function () {
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(0); });
    };
    KingOfTokyo.prototype.onLeavingSellCard = function () {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(0); });
        }
    };
    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    KingOfTokyo.prototype.onUpdateActionButtons = function (stateName, args) {
        if (this.isCurrentPlayerActive()) {
            switch (stateName) {
                case 'changeMimickedCard':
                    this.addActionButton('skipChangeMimickedCard_button', _("Skip"), 'skipChangeMimickedCard');
                    break;
                case 'throwDice':
                    this.addActionButton('resolve_button', _("Resolve dice"), 'goToChangeDie', null, null, 'red');
                    break;
                case 'changeDie':
                    this.addActionButton('resolve_button', _("Resolve dice"), 'resolveDice', null, null, 'red');
                    break;
                case 'psychicProbeRollDie':
                    this.addActionButton('psychicProbeSkip_button', _("Skip"), 'psychicProbeSkip');
                    this.onEnteringPsychicProbeRollDie(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'leaveTokyo':
                    this.addActionButton('stayInTokyo_button', _("Stay in Tokyo"), 'onStayInTokyo');
                    this.addActionButton('leaveTokyo_button', _("Leave Tokyo"), 'onLeaveTokyo');
                    break;
                case 'buyCard':
                    this.addActionButton('renew_button', _("Renew cards") + formatTextIcons(" ( 2 [Energy])"), 'onRenew');
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    this.addActionButton('endTurn_button', _("End turn"), 'goToSellCard', null, null, 'red');
                    break;
                case 'opportunistBuyCard':
                    this.addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');
                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                case 'sellCard':
                    this.addActionButton('endTurn_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;
                case 'cancelDamage':
                    this.onEnteringCancelDamage(args); // because it's multiplayer, enter action must be set here
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
    KingOfTokyo.prototype.getOrderedPlayers = function () {
        var _this = this;
        return this.gamedatas.playerorder.map(function (id) { return _this.gamedatas.players[Number(id)]; });
    };
    KingOfTokyo.prototype.createPlayerPanels = function (gamedatas) {
        var _this = this;
        Object.values(gamedatas.players).forEach(function (player) {
            var playerId = Number(player.id);
            // health & energy counters
            dojo.place("<div class=\"counters\">\n                <div id=\"health-counter-wrapper-" + player.id + "\" class=\"health-counter\">\n                    <div class=\"icon health\"></div> \n                    <span id=\"health-counter-" + player.id + "\"></span>\n                </div>\n                <div id=\"energy-counter-wrapper-" + player.id + "\" class=\"energy-counter\">\n                    <div class=\"icon energy\"></div> \n                    <span id=\"energy-counter-" + player.id + "\"></span>\n                </div>\n            </div>", "player_board_" + player.id);
            var healthCounter = new ebg.counter();
            healthCounter.create("health-counter-" + player.id);
            healthCounter.setValue(player.health);
            _this.healthCounters[playerId] = healthCounter;
            var energyCounter = new ebg.counter();
            energyCounter.create("energy-counter-" + player.id);
            energyCounter.setValue(player.energy);
            _this.energyCounters[playerId] = energyCounter;
            dojo.place("<div class=\"player-tokens\">\n                <div id=\"player-board-shrink-ray-tokens-" + player.id + "\" class=\"player-token\"></div>\n                <div id=\"player-board-poison-tokens-" + player.id + "\" class=\"player-token\"></div>\n            </div>", "player_board_" + player.id);
            _this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
            _this.setPoisonTokens(playerId, player.poisonTokens);
            dojo.place("<div id=\"player-board-monster-figure-" + player.id + "\" class=\"monster-figure monster" + player.monster + "\"><div class=\"kot-token\"></div></div>", "player_board_" + player.id);
            if (player.location > 0) {
                dojo.addClass("overall_player_board_" + playerId, 'intokyo');
            }
            if (player.eliminated) {
                setTimeout(function () { return _this.eliminatePlayer(playerId); }, 200);
            }
        });
        // (this as any).addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
    };
    KingOfTokyo.prototype.createPlayerTables = function (gamedatas) {
        var _this = this;
        this.getOrderedPlayers().forEach(function (player) {
            return _this.playerTables[Number(player.id)] = new PlayerTable(_this, player, gamedatas.playersCards[Number(player.id)]);
        });
    };
    KingOfTokyo.prototype.createVisibleCards = function (visibleCards) {
        var _this = this;
        this.visibleCards = new ebg.stock();
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection';
        this.visibleCards.create(this, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = function (card_div, card_type_id) { return _this.cards.setupNewCard(card_div, card_type_id); };
        this.visibleCards.image_items_per_row = 10;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, function (_, item_id) { return _this.onVisibleCardClick(_this.visibleCards, item_id); });
        this.cards.setupCards([this.visibleCards]);
        this.cards.addCardsToStock(this.visibleCards, visibleCards);
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
        if (this.gamedatas.gamestate.name === 'sellCard') {
            this.sellCard(cardId);
        }
        else if (this.gamedatas.gamestate.name === 'chooseMimickedCard' || this.gamedatas.gamestate.name === 'opportunistChooseMimicCard') {
            this.chooseMimickedCard(cardId);
        }
        else if (this.gamedatas.gamestate.name === 'changeMimickedCard') {
            this.changeMimickedCard(cardId);
        }
        else {
            this.buyCard(cardId, from);
        }
    };
    KingOfTokyo.prototype.addRapidHealingButton = function (userEnergy, isMaxHealth) {
        var _this = this;
        if (!document.getElementById('rapidHealingButton')) {
            this.createButton('rapid-healing-wrapper', 'rapidHealingButton', formatTextIcons(_('Gain 1[Heart]') + " (2[Energy])"), function () { return _this.useRapidHealing(); }, userEnergy < 2 || isMaxHealth);
        }
    };
    KingOfTokyo.prototype.removeRapidHealingButton = function () {
        if (document.getElementById('rapidHealingButton')) {
            dojo.destroy('rapidHealingButton');
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
    };
    KingOfTokyo.prototype.removeMimicToken = function (card) {
        var _this = this;
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.items.some(function (item) { return Number(item.id) == card.id; })) {
                _this.cards.removeMimicOnCard(playerTable.cards, card);
            }
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
        this.takeAction('rethrow3');
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
    KingOfTokyo.prototype.useRapidHealing = function () {
        this.takeAction('useRapidHealing');
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
        if (!this.checkAction('goToChangeDie')) {
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
        if (!this.checkAction('goToSellCard')) {
            return;
        }
        this.takeAction('goToSellCard');
    };
    KingOfTokyo.prototype.opportunistSkip = function () {
        if (!this.checkAction('opportunistSkip')) {
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
        if (!this.checkAction('skipChangeMimickedCard')) {
            return;
        }
        this.takeAction('skipChangeMimickedCard');
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
    KingOfTokyo.prototype.takeAction = function (action, data) {
        data = data || {};
        data.lock = true;
        this.ajaxcall("/kingoftokyo/kingoftokyo/" + action + ".html", data, this, function () { });
    };
    KingOfTokyo.prototype.showPickStock = function (card) {
        var _this = this;
        if (!this.pickCard) {
            dojo.place('<div id="pick-stock"></div>', 'deck');
            this.pickCard = new ebg.stock();
            this.pickCard.setSelectionAppearance('class');
            this.pickCard.selectionClass = 'no-visible-selection';
            this.pickCard.create(this, $('pick-stock'), CARD_WIDTH, CARD_HEIGHT);
            this.pickCard.setSelectionMode(1);
            this.pickCard.onItemCreate = function (card_div, card_type_id) { return _this.cards.setupNewCard(card_div, card_type_id); };
            this.pickCard.image_items_per_row = 10;
            this.pickCard.centerItems = true;
            dojo.connect(this.pickCard, 'onChangeSelection', this, function (_, item_id) { return _this.onVisibleCardClick(_this.pickCard, item_id); });
        }
        else {
            document.getElementById('pick-stock').style.display = 'block';
        }
        this.cards.setupCards([this.pickCard]);
        this.cards.addCardsToStock(this.pickCard, [card]);
    };
    KingOfTokyo.prototype.hidePickStock = function () {
        var div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
        }
    };
    KingOfTokyo.prototype.setZoom = function (zoom) {
        var _a;
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
        (_a = this.tableManager) === null || _a === void 0 ? void 0 : _a.placePlayerTable();
    };
    KingOfTokyo.prototype.zoomIn = function () {
        if (this.zoom === ZOOM_LEVELS[ZOOM_LEVELS.length - 1]) {
            return;
        }
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom) + 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    };
    KingOfTokyo.prototype.zoomOut = function () {
        if (this.zoom === ZOOM_LEVELS[0]) {
            return;
        }
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom) - 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    };
    KingOfTokyo.prototype.setupPreferences = function () {
        var _this = this;
        // Extract the ID and value from the UI control
        var onchange = function (e) {
            var match = e.target.id.match(/^preference_control_(\d+)$/);
            if (!match) {
                return;
            }
            var prefId = +match[1];
            var prefValue = +e.target.value;
            _this.prefs[prefId].value = prefValue;
            _this.onPreferenceChange(prefId, prefValue);
        };
        // Call onPreferenceChange() when any value changes
        dojo.query(".preference_control").connect("onchange", onchange);
        // Call onPreferenceChange() now
        dojo.forEach(dojo.query("#ingame_menu_content .preference_control"), function (el) { return onchange({ target: el }); });
    };
    KingOfTokyo.prototype.onPreferenceChange = function (prefId, prefValue) {
        switch (prefId) {
            // KEEP
            case 201:
                this.playerTables.forEach(function (playerTable) { return playerTable.setFont(prefValue); });
                break;
        }
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
            ['resolveNumberDice', ANIMATION_MS],
            ['resolveHealthDice', ANIMATION_MS],
            ['resolveHealthDiceInTokyo', ANIMATION_MS],
            ['resolveEnergyDice', ANIMATION_MS],
            ['resolveSmashDice', ANIMATION_MS],
            ['playerEliminated', ANIMATION_MS],
            ['playerEntersTokyo', ANIMATION_MS],
            ['renewCards', ANIMATION_MS],
            ['buyCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['maxHealth', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['setCardTokens', 1],
            ['removeCards', 1],
            ['setMimicToken', 1],
            ['removeMimicToken', 1],
            ['toggleRapidHealing', 1],
            ['changeDie', 1],
        ];
        notifs.forEach(function (notif) {
            dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
            _this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
    };
    KingOfTokyo.prototype.notif_resolveNumberDice = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points, ANIMATION_MS);
        this.diceManager.resolveNumberDice(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDice = function (notif) {
        this.setHealth(notif.args.playerId, notif.args.health, ANIMATION_MS);
        this.diceManager.resolveHealthDice(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDiceInTokyo = function (notif) {
        this.diceManager.resolveHealthDiceInTokyo();
    };
    KingOfTokyo.prototype.notif_resolveEnergyDice = function (notif) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
        this.diceManager.resolveEnergyDice(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveSmashDice = function (notif) {
        this.diceManager.resolveSmashDice(notif.args);
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
    KingOfTokyo.prototype.notif_leaveTokyo = function (notif) {
        this.playerTables[notif.args.playerId].leaveTokyo();
        dojo.removeClass("overall_player_board_" + notif.args.playerId, 'intokyo');
    };
    KingOfTokyo.prototype.notif_playerEntersTokyo = function (notif) {
        this.playerTables[notif.args.playerId].enterTokyo(notif.args.location);
        this.setPoints(notif.args.playerId, notif.args.points);
        dojo.addClass("overall_player_board_" + notif.args.playerId, 'intokyo');
    };
    KingOfTokyo.prototype.notif_buyCard = function (notif) {
        var card = notif.args.card;
        var newCard = notif.args.newCard;
        this.setEnergy(notif.args.playerId, notif.args.energy);
        if (newCard) {
            this.cards.moveToAnotherStock(this.visibleCards, this.playerTables[notif.args.playerId].cards, card);
            this.cards.addCardsToStock(this.visibleCards, [newCard], 'deck');
        }
        else if (notif.args.from > 0) {
            this.cards.moveToAnotherStock(this.playerTables[notif.args.from].cards, this.playerTables[notif.args.playerId].cards, card);
        }
        else { // from Made in a lab Pick
            if (this.pickCard) { // active player
                this.cards.moveToAnotherStock(this.pickCard, this.playerTables[notif.args.playerId].cards, card);
            }
            else {
                this.cards.addCardsToStock(this.playerTables[notif.args.playerId].cards, [card], 'deck');
            }
        }
        this.tableManager.placePlayerTable(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeCards = function (notif) {
        this.playerTables[notif.args.playerId].removeCards(notif.args.cards);
        this.tableManager.placePlayerTable(); // adapt after removed cards
    };
    KingOfTokyo.prototype.notif_setMimicToken = function (notif) {
        this.setMimicToken(notif.args.card);
    };
    KingOfTokyo.prototype.notif_removeMimicToken = function (notif) {
        this.removeMimicToken(notif.args.card);
    };
    KingOfTokyo.prototype.notif_renewCards = function (notif) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
        this.visibleCards.removeAll();
        this.cards.addCardsToStock(this.visibleCards, notif.args.cards, 'deck');
    };
    KingOfTokyo.prototype.notif_points = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points);
    };
    KingOfTokyo.prototype.notif_health = function (notif) {
        this.setHealth(notif.args.playerId, notif.args.health);
    };
    KingOfTokyo.prototype.notif_maxHealth = function (notif) {
        this.setMaxHealth(notif.args.playerId, notif.args.maxHealth);
        this.setHealth(notif.args.playerId, notif.args.health);
    };
    KingOfTokyo.prototype.notif_energy = function (notif) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
    };
    KingOfTokyo.prototype.notif_shrinkRayToken = function (notif) {
        this.setShrinkRayTokens(notif.args.playerId, notif.args.tokens);
    };
    KingOfTokyo.prototype.notif_poisonToken = function (notif) {
        this.setPoisonTokens(notif.args.playerId, notif.args.tokens);
    };
    KingOfTokyo.prototype.notif_setCardTokens = function (notif) {
        this.cards.placeTokensOnCard(this.playerTables[notif.args.playerId].cards, notif.args.card, notif.args.playerId);
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
        this.diceManager.showCamouflageRoll(notif.args.diceValues);
        if (notif.args.cancelDamageArgs) {
            this.onEnteringCancelDamage(notif.args.cancelDamageArgs);
        }
    };
    KingOfTokyo.prototype.notif_changeDie = function (notif) {
        this.diceManager.changeDie(notif.args.dieId, notif.args.toValue);
    };
    KingOfTokyo.prototype.setPoints = function (playerId, points, delay) {
        var _a;
        if (delay === void 0) { delay = 0; }
        (_a = this.scoreCtrl[playerId]) === null || _a === void 0 ? void 0 : _a.toValue(points);
        this.playerTables[playerId].setPoints(points, delay);
    };
    KingOfTokyo.prototype.setHealth = function (playerId, health, delay) {
        if (delay === void 0) { delay = 0; }
        this.healthCounters[playerId].toValue(health);
        this.playerTables[playerId].setHealth(health, delay);
        this.checkRapidHealingButtonState();
    };
    KingOfTokyo.prototype.setMaxHealth = function (playerId, maxHealth) {
        this.gamedatas.players[playerId].maxHealth = maxHealth;
        this.checkRapidHealingButtonState();
    };
    KingOfTokyo.prototype.setEnergy = function (playerId, energy) {
        this.energyCounters[playerId].toValue(energy);
        this.checkBuyEnergyDrinkState(energy); // disable button if energy gets down to 0
        this.checkRapidHealingButtonState();
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
        this.setPlayerTokens(playerId, tokens, 'shrink-ray');
    };
    KingOfTokyo.prototype.setPoisonTokens = function (playerId, tokens) {
        this.setPlayerTokens(playerId, tokens, 'poison');
    };
    KingOfTokyo.prototype.checkBuyEnergyDrinkState = function (energy) {
        if (document.getElementById('buy_energy_drink_button')) {
            dojo.toggleClass('buy_energy_drink_button', 'disabled', energy < 1);
        }
    };
    KingOfTokyo.prototype.eliminatePlayer = function (playerId) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById("overall_player_board_" + playerId).classList.add('eliminated-player');
        dojo.place("<div class=\"icon dead\"></div>", "player_board_" + playerId);
        this.playerTables[playerId].eliminatePlayer();
        this.tableManager.placePlayerTable(); // because all player's card were removed
    };
    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    KingOfTokyo.prototype.format_string_recursive = function (log, args) {
        var _a, _b;
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                if (args.card_name && args.card_name[0] != '<') {
                    args.card_name = "<strong>" + _(args.card_name) + "</strong>";
                }
                for (var property in args) {
                    if (((_b = (_a = args[property]) === null || _a === void 0 ? void 0 : _a.indexOf) === null || _b === void 0 ? void 0 : _b.call(_a, ']')) > 0) {
                        args[property] = formatTextIcons(args[property]);
                    }
                }
                log = formatTextIcons(log);
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
