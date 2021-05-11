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
function moveToAnotherStock(sourceStock, destinationStock, uniqueId, cardId) {
    if (sourceStock === destinationStock) {
        return;
    }
    var sourceStockItemId = sourceStock.container_div.id + "_item_" + cardId;
    if (document.getElementById(sourceStockItemId)) {
        destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
        sourceStock.removeFromStockById(cardId);
    }
    else {
        console.warn(sourceStockItemId + " not found in ", sourceStock);
        destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
    }
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
    Cards.prototype.getCardName = function (cardTypeId) {
        switch (cardTypeId) {
            // KEEP
            case 1: return _("Acid Attack");
            case 2: return _("Alien Origin");
            case 3: return _("Alpha Monster");
            case 4: return _("Armor Plating");
            case 5: return _("Background Dweller");
            case 6: return _("Burrowing");
            case 7: return _("Camouflage");
            case 8: return _("Complete Destruction");
            case 9: return _("Media Friendly");
            case 10: return _("Eater of the Dead");
            case 11: return _("Energy Hoarder");
            case 12: return _("Even Bigger");
            case 13:
            case 14: return _("Extra Head");
            case 15: return _("Fire Breathing");
            case 16: return _("Freeze Time");
            case 17: return _("Friend of Children");
            case 18: return _("Giant Brain");
            case 19: return _("Gourmet");
            case 20: return _("Healing Ray");
            case 21: return _("Herbivore");
            case 22: return _("Herd Culler");
            case 23: return _("It Has a Child");
            case 24: return _("Jets");
            case 25: return _("Made in a Lab");
            case 26: return _("Metamorph");
            case 27: return _("Mimic");
            case 28: return _("Battery Monster");
            case 29: return _("Nova Breath");
            case 30: return _("Detritivore");
            case 31: return _("Opportunist");
            case 32: return _("Parasitic Tentacles");
            case 33: return _("Plot Twist");
            case 34: return _("Poison Quills");
            case 35: return _("Poison Spit");
            case 36: return _("Psychic Probe");
            case 37: return _("Rapid Healing");
            case 38: return _("Regeneration");
            case 39: return _("Rooting for the Underdog");
            case 40: return _("Shrink Ray");
            case 41: return _("Smoke Cloud");
            case 42: return _("Solar Powered");
            case 43: return _("Spiked Tail");
            case 44: return _("Stretchy");
            case 45: return _("Energy Drink");
            case 46: return _("Urbavore");
            case 47: return _("We're Only Making It Stronger");
            case 48: return _("Wings");
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
            case 101: return _("Apartment Building");
            case 102: return _("Commuter Train");
            case 103: return _("Corner Store");
            case 104: return _("Death From Above");
            case 105: return _("Energize");
            case 106:
            case 107: return _("Evacuation Orders");
            case 108: return _("Flame Thrower");
            case 109: return _("Frenzy");
            case 110: return _("Gas Refinery");
            case 111: return _("Heal");
            case 112: return _("High Altitude Bombing");
            case 113: return _("Jet Fighters");
            case 114: return _("National Guard");
            case 115: return _("Nuclear Power Plant");
            case 116: return _("Skyscraper");
            case 117: return _("Tank");
            case 118: return _("Vast Storm");
            //case 119: return _("Amusement Park");
            //case 120: return _("Army");
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
            case 28: return dojo.string.substitute(_("When you buy <i>${card_name}</i>, put 6[Energy] on it from the bank. At the start of your turn <strong>take 2[Energy] off and add them to your pool.</strong> When there are no [Energy] left discard this card."), { 'card_name': this.getCardName(cardTypeId) });
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
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId) + "</strong></p>\n            <p class=\"cost\">" + dojo.string.substitute(_("Cost : ${cost}"), { 'cost': this.getCardCost(cardTypeId) }) + " <span class=\"icon energy\"></span></p>\n            <p>" + formatTextIcons(this.getCardDescription(cardTypeId)) + "</p>\n        </div>";
        return tooltip;
    };
    Cards.prototype.setupNewCard = function (card_div, card_type_id) {
        var type = card_type_id < 100 ? _('Keep') : _('Discard');
        var name = this.getCardName(card_type_id);
        var description = formatTextIcons(this.getCardDescription(card_type_id));
        card_div.innerHTML = "<div class=\"bottom\"></div>\n        <div class=\"name-wrapper\">\n            <div class=\"outline\">" + name + "</div>\n            <div class=\"text\">" + name + "</div>\n        </div>\n        <div class=\"type-wrapper " + (card_type_id < 100 ? 'keep' : 'discard') + "\">\n            <div class=\"outline\">" + type + "</div>\n            <div class=\"text\">" + type + "</div>\n        </div>\n        \n        <div class=\"description-wrapper\"><div>" + description + "</div></div>\n        ";
        this.game.addTooltipHtml(card_div.id, this.getTooltip(card_type_id));
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
        dojo.place("\n        <div id=\"player-table-" + player.id + "\" class=\"player-table " + (Number(player.eliminated) > 0 ? 'eliminated' : '') + "\">\n            <div class=\"player-name\" style=\"color: #" + player.color + "\">" + player.name + "</div> \n            <div class=\"monster-board-wrapper\">\n                <div class=\"blue wheel\" id=\"blue-wheel-" + player.id + "\"></div>\n                <div class=\"red wheel\" id=\"red-wheel-" + player.id + "\"></div>\n                <div id=\"monster-board-" + player.id + "\" class=\"monster-board monster" + this.monster + "\">\n                    <div id=\"monster-figure-" + player.id + "\" class=\"monster-figure monster" + this.monster + "\"></div>\n                </div>  \n            </div> \n            <div id=\"cards-" + player.id + "\" class=\"player-cards\"></div>      \n        </div>\n\n        ", 'table');
        this.cards = new ebg.stock();
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $("cards-" + this.player.id), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = function (card_div, card_type_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
        this.cards.image_items_per_row = 10;
        this.cards.centerItems = true;
        dojo.connect(this.cards, 'onChangeSelection', this, function (_, item_id) { return _this.game.onVisibleCardClick(_this.cards, item_id, _this.playerId); });
        this.game.cards.setupCards([this.cards]);
        cards.forEach(function (card) { return _this.cards.addToStockWithId(card.type, "" + card.id); });
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
    PlayerTable.prototype.removeDiscardCards = function () {
        var _this = this;
        var discardCardsIds = this.cards.getAllItems().filter(function (item) { return item.type >= 100; }).map(function (item) { return Number(item.id); });
        discardCardsIds.forEach(function (id) { return _this.cards.removeFromStockById('' + id); });
    };
    PlayerTable.prototype.removeCards = function (cards) {
        var _this = this;
        var cardsIds = cards.map(function (card) { return card.id; });
        cardsIds.forEach(function (id) { return _this.cards.removeFromStockById('' + id); });
    };
    PlayerTable.prototype.setPoints = function (points) {
        document.getElementById("blue-wheel-" + this.playerId).style.transform = "rotate(" + POINTS_DEG[points] + "deg)";
    };
    PlayerTable.prototype.setHealth = function (health) {
        document.getElementById("red-wheel-" + this.playerId).style.transform = "rotate(" + (health > 12 ? 22 : HEALTH_DEG[health]) + "deg)";
    };
    PlayerTable.prototype.eliminatePlayer = function () {
        this.cards.removeAll();
        this.game.fadeOutAndDestroy("player-board-monster-figure-" + this.playerId);
        dojo.addClass("player-table-" + this.playerId, 'eliminated');
    };
    return PlayerTable;
}());
var __spreadArray = (this && this.__spreadArray) || function (to, from) {
    for (var i = 0, il = from.length, j = to.length; i < il; i++, j++)
        to[j] = from[i];
    return to;
};
var PLAYER_TABLE_WIDTH = 420;
var PLAYER_BOARD_HEIGHT = 193;
var CARDS_PER_ROW = 3;
var CENTER_TABLE_WIDTH = 420;
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
        var playerTablesOrdered = playerTables.filter(function (playerTable) { return !!playerTable; }).sort(function (a, b) { return b.playerNo - a.playerNo; });
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
        var availableColumns = Math.min(3, Math.floor(tableWidth / 420));
        var idealColumns = players == 2 ? 2 : 3;
        var tableCenterDiv = document.getElementById('table-center');
        tableCenterDiv.style.left = (tableWidth - CENTER_TABLE_WIDTH) / 2 + "px";
        tableCenterDiv.style.top = "0px";
        if (availableColumns === 1) {
            var top_1 = tableCenterDiv.clientHeight;
            this.playerTables.forEach(function (playerTable) {
                var playerTableDiv = document.getElementById("player-table-" + playerTable.playerId);
                playerTableDiv.style.left = (tableWidth - CENTER_TABLE_WIDTH) / 2 + "px";
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
            var tableCenter_1 = (columns_1 === 3 ? tableWidth : tableWidth - PLAYER_TABLE_WIDTH) / 2;
            var centerColumnIndex_1 = columns_1 === 3 ? 1 : 0;
            if (columns_1 === 2) {
                tableCenterDiv.style.left = tableCenter_1 - CENTER_TABLE_WIDTH / 2 + "px";
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
                        playerTableDiv.style.left = tableCenter_1 - PLAYER_TABLE_WIDTH / 2 + "px";
                    }
                    else if (rightColumn) {
                        playerTableDiv.style.left = tableCenter_1 + PLAYER_TABLE_WIDTH / 2 + "px";
                    }
                    else if (leftColumn) {
                        playerTableDiv.style.left = (tableCenter_1 - PLAYER_TABLE_WIDTH / 2) - PLAYER_TABLE_WIDTH + "px";
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
        tableDiv.style.height = height + 50 + "px";
    };
    TableManager.prototype.getPlayerTableHeight = function (playerTable) {
        var cardRows = Math.max(1, Math.ceil(playerTable.cards.items.length / CARDS_PER_ROW));
        return PLAYER_BOARD_HEIGHT + CARD_HEIGHT * cardRows;
    };
    return TableManager;
}());
var DieFaceSelector = /** @class */ (function () {
    function DieFaceSelector(nodeId, inTokyo) {
        var _this = this;
        var _loop_1 = function (face) {
            var faceId = nodeId + "-face" + face;
            var html = "<div id=\"" + faceId + "\" class=\"dice-icon dice" + face + "\">";
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
                    dojo.removeClass(nodeId + "-face" + _this.value, 'selected');
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
    return DieFaceSelector;
}());
var DiceManager = /** @class */ (function () {
    function DiceManager(game, setupDice) {
        this.game = game;
        this.dice = [];
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
        $('locked-dice').innerHTML = '';
        $('dice-selector').innerHTML = '';
        this.dice = [];
    };
    DiceManager.prototype.setDiceForThrowDice = function (dice, lastTurn, inTokyo) {
        var _this = this;
        var _a;
        var currentPlayerActive = this.game.isCurrentPlayerActive();
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        $('locked-dice').innerHTML = '';
        $('dice-selector').innerHTML = '';
        this.dice = dice;
        var selectable = currentPlayerActive && !lastTurn;
        dice.forEach(function (die) { return _this.createDice(die, true, selectable, inTokyo); });
        dojo.toggleClass('rolled-dice', 'selectable', selectable);
    };
    DiceManager.prototype.setDiceForChangeDie = function (dice, args, inTokyo) {
        var _this = this;
        var _a;
        var currentPlayerActive = this.game.isCurrentPlayerActive();
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        $('dice-selector').innerHTML = '';
        this.dice = dice;
        var onlyHerdCuller = args.hasHerdCuller && !args.hasPlotTwist && !args.hasStretchy;
        dice.forEach(function (die) {
            var divId = "dice" + die.id;
            dojo.place(_this.createDiceHtml(die, inTokyo), 'dice-selector');
            var selectable = currentPlayerActive && (!onlyHerdCuller || die.value !== 1);
            dojo.toggleClass(divId, 'selectable', selectable);
            setTimeout(function () { return document.getElementById(divId).getElementsByClassName('die-list')[0].classList.add('no-roll'); }, 100);
            if (selectable) {
                dojo.place("<div id=\"discussion_bubble_" + divId + "\" class=\"discussion_bubble change-die-discussion_bubble\"></div>", divId);
                document.getElementById(divId).addEventListener('click', function () { return _this.toggleBubbleChangeDie(die, args); });
            }
        });
    };
    DiceManager.prototype.setDiceForSelectHeartAction = function (dice, inTokyo) {
        var _this = this;
        if (this.dice.length) {
            return;
        }
        //this.dice?.forEach(die => this.removeDice(die));  
        $('dice-selector').innerHTML = '';
        this.dice = dice;
        dice.forEach(function (die) {
            var divId = "dice" + die.id;
            dojo.place(_this.createDiceHtml(die, inTokyo), 'dice-selector');
            setTimeout(function () { return document.getElementById(divId).getElementsByClassName('die-list')[0].classList.add('no-roll'); }, 100);
        });
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
        slideToObjectAndAttach(this.game, dieDiv, die.locked ? 'locked-dice' : 'dice-selector');
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
    DiceManager.prototype.createDice = function (die, animated, selectable, inTokyo) {
        var _this = this;
        dojo.place(this.createDiceHtml(die, inTokyo), die.locked ? 'locked-dice' : 'dice-selector');
        var dieDiv = this.getDiceDiv(die);
        if (!die.locked && animated) {
            dieDiv.classList.add('rolled');
            setTimeout(function () { return dieDiv.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll'); }, 100);
            setTimeout(function () { return dieDiv.classList.remove('rolled'); }, 1200);
        }
        else {
            setTimeout(function () { return dieDiv.getElementsByClassName('die-list')[0].classList.add('no-roll'); }, 100);
        }
        if (selectable) {
            dieDiv.addEventListener('click', function () { return _this.toggleLockDice(die); });
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
    DiceManager.prototype.toggleBubbleChangeDie = function (die, args) {
        var _this = this;
        var bubble = document.getElementById("discussion_bubble_dice" + die.id);
        var visible = bubble.dataset.visible == 'true';
        if (visible) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
        else {
            if (bubble.innerHTML == '') {
                var bubbleActionButtonsId = "discussion_bubble_dice" + die.id + "-action-buttons";
                var bubbleDieFaceSelectorId = "discussion_bubble_dice" + die.id + "-die-face-selector";
                dojo.place("\n                <div id=\"" + bubbleDieFaceSelectorId + "\" class=\"die-face-selector\"></div>\n                <div id=\"" + bubbleActionButtonsId + "\" class=\"action-buttons\"></div>\n                ", bubble.id);
                var herdCullerButtonId_1 = bubbleActionButtonsId + "-herdCuller";
                var plotTwistButtonId_1 = bubbleActionButtonsId + "-plotTwist";
                var stretchyButtonId_1 = bubbleActionButtonsId + "-stretchy";
                var dieFaceSelector_1 = new DieFaceSelector(bubbleDieFaceSelectorId, args.inTokyo);
                var buttonText = _("Change die face with ${card_name}");
                if (args.hasHerdCuller) {
                    this.game.createButton(bubbleActionButtonsId, herdCullerButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.cards.getCardName(22) + "</strong>" }), function () {
                        _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 22);
                        _this.toggleBubbleChangeDie(die, args);
                    }, true);
                }
                if (args.hasPlotTwist) {
                    this.game.createButton(bubbleActionButtonsId, plotTwistButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.cards.getCardName(33) + "</strong>" }), function () {
                        _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 33),
                            _this.toggleBubbleChangeDie(die, args);
                    }, true);
                }
                if (args.hasStretchy) {
                    this.game.createButton(bubbleActionButtonsId, stretchyButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>" + this.game.cards.getCardName(44) + "</strong>" }) + formatTextIcons(' (2 [Energy])'), function () {
                        _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 44),
                            _this.toggleBubbleChangeDie(die, args);
                    }, true);
                }
                dieFaceSelector_1.onChange = function (value) {
                    if (args.hasHerdCuller && die.value > 1) {
                        dojo.toggleClass(herdCullerButtonId_1, 'disabled', value != 1);
                    }
                    if (args.hasPlotTwist) {
                        dojo.toggleClass(plotTwistButtonId_1, 'disabled', value < 1);
                    }
                    if (args.hasStretchy && args.hasEnergyForStretchy) {
                        dojo.toggleClass(stretchyButtonId_1, 'disabled', value < 1);
                    }
                };
                bubble.addEventListener('click', function (event) { return event.stopImmediatePropagation(); });
            }
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
            dojo.toggleClass(_this.nodeId + "-die" + index + "-shrink-ray", 'disabled', selection.action != 'shrink-ray' && removedShrinkRays >= _this.args.shrinkRayTokens);
            dojo.toggleClass(_this.nodeId + "-die" + index + "-poison", 'disabled', selection.action != 'poison' && removedPoisons >= _this.args.poisonTokens);
            _this.args.healablePlayers.forEach(function (player) { return dojo.toggleClass(_this.nodeId + "-die" + index + "-heal-player-" + player.id, 'disabled', selection.action != 'heal-player' && selection.playerId != player.id && healedPlayers[player.id] >= player.missingHearts); });
        });
    };
    return HeartActionSelector;
}());
var ANIMATION_MS = 1500;
var PUNCH_SOUND_DURATION = 250;
var KingOfTokyo = /** @class */ (function () {
    function KingOfTokyo() {
        this.healthCounters = [];
        this.energyCounters = [];
        this.playerTables = [];
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
        // ignore loading of some pictures
        [1, 2, 3, 4, 5, 6].filter(function (i) { return !Object.values(gamedatas.players).some(function (player) { return Number(player.mmonster) === i; }); }).forEach(function (i) {
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
        this.setupNotifications();
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
        switch (stateName) {
            case 'throwDice':
                this.onEnteringThrowDice(args.args);
                break;
            case 'changeDie':
                this.onEnteringChangeDie(args.args);
                break;
            case 'resolveDice':
                this.diceManager.hideLock();
                break;
            case 'resolveHeartDice':
                this.onEnteringResolveHeartDice(args.args);
                break;
            case 'buyCard':
                this.onEnteringBuyCard(args.args);
                break;
            case 'sellCard':
                this.onEnteringSellCard();
                break;
            case 'endTurn':
                this.onEnteringEndTurn();
                break;
        }
    };
    KingOfTokyo.prototype.setGamestateDescription = function (property) {
        if (property === void 0) { property = ''; }
        var originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        this.gamedatas.gamestate.description = "" + originalState['description' + property];
        this.gamedatas.gamestate.descriptionmyturn = "" + originalState['descriptionmyturn' + property];
        this.updatePageTitle();
    };
    KingOfTokyo.prototype.onEnteringThrowDice = function (args) {
        var _this = this;
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? "last" : '');
        this.diceManager.showLock();
        var dice = args.dice;
        this.diceManager.setDiceForThrowDice(dice, args.throwNumber === args.maxThrowNumber, args.inTokyo);
        if (this.isCurrentPlayerActive()) {
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', _("Rethrow dice") + (" (" + args.throwNumber + "/" + args.maxThrowNumber + ")"), function () { return _this.onRethrow(); }, !args.dice.some(function (dice) { return !dice.locked; }));
            }
            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3(); }, !args.rethrow3.hasDice3);
            }
            if (args.energyDrink.hasCard && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + " ( 1 <span class=\"small icon energy\"></span>)", function () { return _this.buyEnergyDrink(); });
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringChangeDie = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args, args.inTokyo);
        }
    };
    KingOfTokyo.prototype.onEnteringResolveHeartDice = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.inTokyo);
            if (this.isCurrentPlayerActive()) {
                dojo.place("<div id=\"heart-action-selector\" class=\"whiteblock\"></div>", 'rolled-dice', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringBuyCard = function (args) {
        var _this = this;
        var _a;
        if (this.isCurrentPlayerActive()) {
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
    KingOfTokyo.prototype.onEnteringSellCard = function () {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode(1); });
        }
    };
    KingOfTokyo.prototype.onEnteringEndTurn = function () {
        // clean discard cards
        this.playerTables.forEach(function (playerTable) { return playerTable.removeDiscardCards(); });
        this.tableManager.placePlayerTable(); // adapt to removed card
    };
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        log('Leaving state: ' + stateName);
        switch (stateName) {
            case 'throwDice':
                document.getElementById('dice-actions').innerHTML = '';
                break;
            case 'resolveHeartDice':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;
            case 'buyCard':
                this.onLeavingBuyCard();
                break;
            case 'sellCard':
                this.onLeavingSellCard();
                break;
        }
    };
    KingOfTokyo.prototype.onLeavingBuyCard = function () {
        this.visibleCards.setSelectionMode(0);
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(0); });
        this.hidePickStock();
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
                case 'throwDice':
                    this.addActionButton('resolve_button', _("Resolve dice"), 'goToChangeDie', null, null, 'red');
                    break;
                case 'changeDie':
                    this.addActionButton('resolve_button', _("Resolve dice"), 'resolveDice', null, null, 'red');
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
                case 'sellCard':
                    this.addActionButton('endTurn_button', _("End turn"), 'onEndTurn', null, null, 'red');
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
            dojo.place("<div id=\"player-board-monster-figure-" + player.id + "\" class=\"monster-figure monster" + player.monster + "\"></div>", "player_board_" + player.id);
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
        visibleCards.forEach(function (card) { return _this.visibleCards.addToStockWithId(card.type, "" + card.id); });
    };
    KingOfTokyo.prototype.onVisibleCardClick = function (stock, cardId, from) {
        if (from === void 0) { from = 0; }
        if (dojo.hasClass(stock.container_div.id + "_item_" + cardId, 'disabled')) {
            stock.unselectItem(cardId);
            return;
        }
        if (this.gamedatas.gamestate.name === 'sellCard') {
            this.sellCard(cardId);
        }
        else {
            this.buyCard(cardId, from);
        }
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
        this.takeAction('buyEnergyDrink');
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
    KingOfTokyo.prototype.onEndTurn = function () {
        if (!this.checkAction('endTurn')) {
            return;
        }
        this.takeAction('endTurn');
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
        this.pickCard.addToStockWithId(card.type, "" + card.id);
    };
    KingOfTokyo.prototype.hidePickStock = function () {
        var div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
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
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['removeCards', 1],
        ];
        notifs.forEach(function (notif) {
            dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
            _this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
    };
    KingOfTokyo.prototype.notif_resolveNumberDice = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points);
        this.diceManager.resolveNumberDice(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDice = function (notif) {
        this.setHealth(notif.args.playerId, notif.args.health);
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
            moveToAnotherStock(this.visibleCards, this.playerTables[notif.args.playerId].cards, card.type, "" + card.id);
            this.visibleCards.addToStockWithId(newCard.type, "" + newCard.id, 'deck');
        }
        else if (notif.args.from > 0) {
            moveToAnotherStock(this.playerTables[notif.args.from].cards, this.playerTables[notif.args.playerId].cards, card.type, "" + card.id);
        }
        else { // from Made in a lab Pick
            if (this.pickCard) { // active player
                moveToAnotherStock(this.pickCard, this.playerTables[notif.args.playerId].cards, card.type, "" + card.id);
            }
            else {
                this.playerTables[notif.args.playerId].cards.addToStockWithId(card.type, "" + card.id, 'deck');
            }
        }
        this.tableManager.placePlayerTable(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeCards = function (notif) {
        this.playerTables[notif.args.playerId].removeCards(notif.args.cards);
        this.tableManager.placePlayerTable(); // adapt after removed cards
    };
    KingOfTokyo.prototype.notif_renewCards = function (notif) {
        var _this = this;
        this.setEnergy(notif.args.playerId, notif.args.energy);
        this.visibleCards.removeAll();
        notif.args.cards.forEach(function (card) { return _this.visibleCards.addToStockWithId(card.type, "" + card.id); });
    };
    KingOfTokyo.prototype.notif_points = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points);
    };
    KingOfTokyo.prototype.notif_health = function (notif) {
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
    KingOfTokyo.prototype.setPoints = function (playerId, points) {
        var _a;
        (_a = this.scoreCtrl[playerId]) === null || _a === void 0 ? void 0 : _a.toValue(points);
        this.playerTables[playerId].setPoints(points);
    };
    KingOfTokyo.prototype.setHealth = function (playerId, health) {
        this.healthCounters[playerId].toValue(health);
        this.playerTables[playerId].setHealth(health);
    };
    KingOfTokyo.prototype.setEnergy = function (playerId, energy) {
        this.energyCounters[playerId].toValue(energy);
        this.checkBuyEnergyDrinkState(this.energyCounters[this.getPlayerId()].getValue()); // disable button if energy gets down to 0
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
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                if (args.card_name && args.card_name[0] != '<') {
                    args.card_name = "<strong>" + args.card_name + "</strong>";
                }
                if (args.dice_value && args.dice_value.indexOf(']') > 0) {
                    args.dice_value = formatTextIcons(args.dice_value);
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
