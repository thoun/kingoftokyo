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
var Cards = /** @class */ (function () {
    function Cards(game) {
        this.game = game;
    }
    Cards.prototype.setupCards = function (stocks) {
        stocks.forEach(function (stock) {
            var keepcardsurl = g_gamethemeurl + "img/cards0.jpg";
            for (var id = 1; id <= 57; id++) { // keep
                stock.addItemType(id, id, keepcardsurl, id);
            }
            var discardcardsurl = g_gamethemeurl + "img/cards1.jpg";
            for (var id = 101; id <= 120; id++) { // discard
                stock.addItemType(id, id, discardcardsurl, id);
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
            case 13: return 7;
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
            case 28: return 2;
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
            case 49: return 5;
            case 50: return 3;
            case 51: return 4;
            case 52: return 6;
            case 53: return 3;
            case 54: return 4;
            case 55: return 4;
            case 56: return 3;
            case 57: return 3;
            // DISCARD
            case 101: return 5;
            case 102: return 4;
            case 103: return 3;
            case 104: return 5;
            case 105: return 8;
            case 106: return 7;
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
            case 119: return 6;
            case 120: return 2;
        }
        return null;
    };
    Cards.prototype.getCardName = function (cardTypeId) {
        switch (cardTypeId) {
            // KEEP
            case 1: return _("Acid Attack");
            case 2: return _("Alien Metabolism");
            case 3: return _("Alpha Monster");
            case 4: return _("Armor Plating");
            case 5: return _("Background Dweller");
            case 6: return _("Burrowing");
            case 7: return _("Camouflage");
            case 8: return _("Complete Destruction");
            case 9: return _("Dedicated News Team");
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
            case 28: return _("Monster Batteries");
            case 29: return _("Nova Breath");
            case 30: return _("Omnivore");
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
            case 45: return _("Telepath");
            case 46: return _("Urbavore");
            case 47: return _("We're Only Making It Stronger");
            case 48: return _("Wings");
            case 49: return _("Cannibalistic");
            case 50: return _("Intimidating Roar");
            case 51: return _("Monster Sidekick");
            case 52: return _("Reflective Hide");
            case 53: return _("Sleep Walker");
            case 54: return _("Super Jump");
            case 55: return _("Throw a Tanker");
            case 56: return _("Thunder Stomp");
            case 57: return _("Unstable DNA");
            // DISCARD
            case 101: return _("Apartment Building");
            case 102: return _("Commuter Train");
            case 103: return _("Corner Store");
            case 104: return _("Drop From High Altitude");
            case 105: return _("Energize");
            case 106:
            case 107: return _("Evacuation Orders");
            case 108: return _("Fire Blast");
            case 109: return _("Frenzy");
            case 110: return _("Gas Refinery");
            case 111: return _("Heal");
            case 112: return _("High Altitude Bombing");
            case 113: return _("Jet Fighters");
            case 114: return _("National Guard");
            case 115: return _("Nuclear Power Plant");
            case 116: return _("Skyscraper");
            case 117: return _("Tanks");
            case 118: return _("Vast Storm");
            case 119: return _("Amusement Park");
            case 120: return _("Army");
        }
        return null;
    };
    Cards.prototype.getCardDescription = function (cardTypeId) {
        switch (cardTypeId) {
            // KEEP
            case 1: return _("Deal 1 extra damage each turn (even when you don't otherwise attack).");
            case 2: return _("Buying cards costs you 1 less [Energy].");
            case 3: return _("Gain 1[Star] when you attack.");
            case 4: return _("Ignore damage of 1.");
            case 5: return _("You can always reroll any [3] you have.");
            case 6: return _("Deal 1 extra damage on Tokyo. Deal 1 damage when yielding Tokyo to the monster taking it.");
            case 7: return _("If you take damage roll a die for each damage point. On a [Heart] you do not take that damage point.");
            case 8: return _("If you roll [1][2][3][Heart][Attack][Energy] gain 9[Star] in addition to the regular results.");
            case 9: return _("Gain 1[Star] whenever you buy a card.");
            case 10: return _("Gain 3[Star] every time a monster's [Heart] goes to 0.");
            case 11: return _("You gain 1[Star] for every 6[Energy] you have at the end of your turn.");
            case 12: return _("Your maximum [Heart] is increased by 2. Gain 2[Heart] when you get this card.");
            case 13:
            case 14: return _("You get 1 extra die.");
            case 15: return _("Your neighbors take 1 extra damage when you deal damage");
            case 16: return _("On a turn where you score [1][1][1], you can take another turn with one less die.");
            case 17: return _("When you gain any [Energy] gain 1 extra [Energy].");
            case 18: return _("You have one extra reroll each turn.");
            case 19: return _("When scoring [1][1][1] gain 2 extra [Star].");
            case 20: return _("You can heal other monsters with your [Heart] results. They must pay you 2[Energy] for each damage you heal (or their remaining [Energy] if they haven't got enough.");
            case 21: return _("Gain 1[Star] on your turn if you don't damage anyone.");
            case 22: return _("You can change one of your dice to a [1] each turn.");
            case 23: return _("If you are eliminated discard all your cards and lose all your [Star], Heal to 20[Heart] and start again.");
            case 24: return _("You suffer no damage when yielding Tokyo.");
            case 25: return _("When purchasing cards you can peek at and purchase the top card of the deck.");
            case 26: return _("At the end of your turn you can discard any keep cards you have to receive the [Energy] they were purchased for.");
            case 27: return _("Choose a card any monster has in play and put a mimic counter on it. This card counts as a duplicate of that card as if it just had been bought. Spend 1[Energy] at the start of your turn to change the power you are mimicking.");
            case 28: return _("When you purchase this put as many [Energy] as you want on it from your reserve. Match this from the bank. At the start of each turn take 2[Energy] off and add them to your reserve. When there are no [Energy] left discard this card.");
            case 29: return _("Your attacks damage all other monsters.");
            case 30: return _("Once each turn you can score [1][2][3] for 2[Star]. You can use these dice in other combinations.");
            case 31: return _("Whenever a new card is revealed you have the option of purchasing it as soon as it is revealed.");
            case 32: return _("You can purchase cards from other monsters. Pay them the [Energy] cost.");
            case 33: return _("Change one die to any result. Discard when used.");
            case 34: return _("When you score [2][2][2] also deal 2 damage.");
            case 35: return _("When you deal damage to monsters give them a poison counter. Monsters take 1 damage for each poison counter they have at the end of their turn. You can get rid of a poison counter with a [Heart] (that [Heart] doesn't heal a damage also).");
            case 36: return _("You can reroll a die of each other monster once each turn. If the reroll is [Heart] discard this card.");
            case 37: return _("Spend 2[Energy] at any time to heal 1 damage.");
            case 38: return _("When you heal, heal 1 extra damage.");
            case 39: return _("At the end of a turn when you have the fewest [Star] gain 1 [Star].");
            case 40: return _("When you deal damage to monsters give them a shrink counter. A monster rolls one less die for each shrink counter. You can get rid of a shrink counter with a [Heart] (that [Heart] doesn't heal a damage also).");
            case 41: return _("This card starts with 3 charges. Spend a charge for an extra reroll. Discard this card when all charges are spent.");
            case 42: return _("At the end of your turn gain 1[Energy] if you have no [Energy].");
            case 43: return _("When you attack deal 1 extra damage.");
            case 44: return _("You can spend 2[Energy] to change one of your dice to any result.");
            case 45: return _("Spend 1[Energy] to get 1 extra reroll.");
            case 46: return _("Gain 1 extra [Star] when beginning the turn in Tokyo. Deal 1 extra damage when dealing any damage from Tokyo.");
            case 47: return _("When you lose 2[Heart] or more gain 1[Energy].");
            case 48: return _("Spend 2[Energy] to negate damage to you for a turn.");
            case 49: return _("When you do damage gain 1[Heart].");
            case 50: return _("The monsters in Tokyo must yield if you damage them.");
            case 51: return _("If someone kills you, Go back to 10[Heart] and lose all your [Star]. If either of you or your killer win, or all other players are eliminated then you both win. If your killer is eliminated then you are also. If you are eliminated a second time this card has no effect.");
            case 52: return _("If you suffer damage the monster that inflicted the damage suffers 1 as well.");
            case 53: return _("Spend 3[Energy] to gain 1[Star].");
            case 54: return _("Once each turn you may spend 1[Energy] to negate 1 damage you are receiving.");
            case 55: return _("On a turn you deal 3 or more damage gain 2[Star].");
            case 56: return _("If you score 4[Star] in a turn, all players roll one less die until your next turn.");
            case 57: return _("If you yield Tokyo you can take any card the recipient has and give him this card.");
            // DISCARD
            case 101: return _("+ 3[Star]");
            case 102: return _("+ 2[Star]");
            case 103: return _("+ 1[Star]");
            case 104: return _("+ 2[Star] and take control of Tokyo if you don't already control it.");
            case 105: return _("+ 9[Energy]");
            case 106:
            case 107: return _("All other monsters lose 5[Star].");
            case 108: return _("Deal 2 damage to all other monsters.");
            case 109: return _("When you purchase this card Take another turn immediately after this one."); // TODO check spelling
            case 110: return _("+ 2[Star] and deal 3 damage to all other monsters.");
            case 111: return _("Heal 2 damage.");
            case 112: return _("All monsters (including you) take 3 damage.");
            case 113: return _("+ 5[Star] and take 4 damage");
            case 114: return _("+ 2[Star] and take 2 damage.");
            case 115: return _("+ 2[Star] and heal 3 damage.");
            case 116: return _("+ 4[Star]");
            case 117: return _("+ 4[Star] and take 3 damage.");
            case 118: return _("+ 2[Star]. All other monsters lose 1[Energy] for every 2[Energy] they have.");
            case 119: return _("+ 4[Star]");
            case 120: return _("(+ 1[Star] and suffer one damage) for each card you have."); // TODO check spelling
        }
        return null;
    };
    Cards.prototype.formatDescription = function (rawDescription) {
        return rawDescription
            .replace(/\[Star\]/ig, '<span class="icon health"></span>')
            .replace(/\[Energy\]/ig, '<span class="icon energy"></span>');
        // TODO [1][2][3][Heart][Attack]
    };
    Cards.prototype.getTooltip = function (cardTypeId) {
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>" + this.getCardName(cardTypeId) + "</strong></p>\n            <p class=\"cost\">" + dojo.string.substitute(_("Cost : ${cost}"), { 'cost': this.getCardCost(cardTypeId) }) + " <span class=\"icon energy\"></span></p>\n            <p>" + this.formatDescription(this.getCardDescription(cardTypeId)) + "</p>\n        </div>";
        return tooltip;
    };
    Cards.prototype.setupNewCard = function (card_div, card_type_id) {
        var type = card_type_id < 100 ? _('Keep') : _('Discard');
        var name = this.getCardName(card_type_id);
        var description = this.formatDescription(this.getCardDescription(card_type_id));
        card_div.innerHTML = "\n        <div class=\"name-wrapper\">\n            <div class=\"outline\">" + name + "</div>\n            <div class=\"text\">" + name + "</div>\n        </div>\n        <div class=\"type-wrapper " + (card_type_id < 100 ? 'keep' : 'discard') + "\">\n            <div class=\"outline\">" + type + "</div>\n            <div class=\"text\">" + type + "</div>\n        </div>\n        <div class=\"description-wrapper\"><div>" + description + "</div></div>\n        ";
        this.game.addTooltipHtml(card_div.id, this.getTooltip(card_type_id));
    };
    return Cards;
}());
var CARD_WIDTH = 123;
var CARD_HEIGHT = 185;
var isDebug = window.location.host == 'studio.boardgamearena.com';
var log = isDebug ? console.log.bind(window.console) : function () { };
var PlayerTable = /** @class */ (function () {
    function PlayerTable(game, player, order, cards) {
        var _this = this;
        this.game = game;
        this.player = player;
        this.order = order;
        this.playerId = Number(player.id);
        this.playerNo = Number(player.player_no);
        this.monster = Number(player.monster);
        dojo.place("\n        <div id=\"player-table-" + player.id + "\" class=\"player-table\">\n            <div class=\"player-name\" style=\"color: #" + player.color + "\">" + player.name + "</div> \n            <div class=\"monster-board-wrapper\">\n                <div class=\"blue wheel\" id=\"blue-wheel-" + player.id + "\"></div>\n                <div class=\"red wheel\" id=\"red-wheel-" + player.id + "\"></div>\n                <div id=\"monster-board-" + player.id + "\" class=\"monster-board monster" + this.monster + "\">\n                    <div id=\"monster-figure-" + player.id + "\" class=\"monster-figure monster" + this.monster + "\"></div>\n                </div>  \n            </div> \n            <div id=\"cards-" + player.id + "\"></div>      \n        </div>\n\n        ", 'table');
        this.cards = new ebg.stock();
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $("cards-" + this.player.id), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = function (card_div, card_type_id, card_id) { return _this.game.cards.setupNewCard(card_div, card_type_id); };
        //this.cards.image_items_per_row = 13;
        this.cards.centerItems = true;
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
        this.game.slideToObject("monster-figure-" + this.playerId, "tokyo-" + (location == 2 ? 'bay' : 'city')).play();
    };
    PlayerTable.prototype.leaveTokyo = function () {
        this.game.slideToObject("monster-figure-" + this.playerId, "monster-board-" + this.playerId).play();
    };
    PlayerTable.prototype.removeDiscardCards = function () {
        var _this = this;
        var discardCardsIds = this.cards.getAllItems().filter(function (item) { return item.type >= 100; }).map(function (item) { return Number(item.id); });
        discardCardsIds.forEach(function (id) { return _this.cards.removeFromStockById('' + id); });
    };
    PlayerTable.prototype.setPoints = function (points) {
        var deg = 25 + 335 * points / 20;
        document.getElementById("blue-wheel-" + this.playerId).style.transform = "rotate(" + deg + "deg)";
    };
    PlayerTable.prototype.setHealth = function (health) {
        var deg = 360 - 262 * health / 10;
        document.getElementById("red-wheel-" + this.playerId).style.transform = "rotate(" + deg + "deg)";
    };
    return PlayerTable;
}());
var __spreadArray = (this && this.__spreadArray) || function (to, from) {
    for (var i = 0, il = from.length, j = to.length; i < il; i++, j++)
        to[j] = from[i];
    return to;
};
var PLAYER_TABLE_WIDTH = 400;
var PLAYER_BOARD_HEIGHT = 193;
var CARDS_PER_ROW = 3;
var CENTER_TABLE_WIDTH = 400;
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
        var currentPlayerId = Number(this.game.player_id);
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
        tableDiv.style.height = height + "px";
    };
    TableManager.prototype.getPlayerTableHeight = function (playerTable) {
        var cardRows = Math.max(1, Math.ceil(playerTable.cards.items.length / CARDS_PER_ROW));
        return PLAYER_BOARD_HEIGHT + CARD_HEIGHT * cardRows;
    };
    return TableManager;
}());
var DiceManager = /** @class */ (function () {
    function DiceManager(game, setupDices) {
        this.game = game;
        this.dices = [];
        // TODO use setupDices
    }
    DiceManager.prototype.hideLock = function () {
        dojo.addClass('locked-dices', 'hide-lock');
    };
    DiceManager.prototype.showLock = function () {
        dojo.removeClass('locked-dices', 'hide-lock');
    };
    DiceManager.prototype.destroyFreeDices = function () {
        var _this = this;
        var freeDices = this.dices.filter(function (dice) { return !dice.locked; });
        freeDices.forEach(function (dice) { return _this.removeDice(dice); });
        return freeDices.map(function (dice) { return dice.id; });
    };
    DiceManager.prototype.removeAllDices = function () {
        var _this = this;
        this.dices.forEach(function (dice) { return _this.removeDice(dice); });
    };
    DiceManager.prototype.lockFreeDices = function () {
        var _this = this;
        this.dices.filter(function (dice) { return !dice.locked; }).forEach(function (dice) { return _this.toggleLockDice(dice, true); });
    };
    DiceManager.prototype.setDices = function (dices, firstThrow, lastTurn, inTokyo) {
        var _a;
        var _this = this;
        if (firstThrow) {
            $('dices-selector').innerHTML = '';
            this.dices = [];
        }
        var newDices = dices.filter(function (newDice) { return !_this.dices.some(function (dice) { return dice.id === newDice.id; }); });
        (_a = this.dices).push.apply(_a, newDices);
        var selectable = this.game.isCurrentPlayerActive() && !lastTurn;
        newDices.forEach(function (dice) { return _this.createDice(dice, true, selectable, inTokyo); });
        dojo.toggleClass('rolled-dices', 'selectable', selectable);
        if (lastTurn) {
            setTimeout(function () { return _this.lockFreeDices(); }, 1000);
        }
        this.activateRethrowButton();
    };
    DiceManager.prototype.resolveNumberDices = function (args) {
        var _this = this;
        // TODO animation
        this.dices.filter(function (dice) { return dice.value === args.diceValue; }).forEach(function (dice) { return _this.removeDice(dice); });
    };
    DiceManager.prototype.resolveHealthDicesInTokyo = function (args) {
        var _this = this;
        // TODO animation
        this.dices.filter(function (dice) { return dice.value === 4; }).forEach(function (dice) { return _this.removeDice(dice); });
    };
    DiceManager.prototype.resolveHealthDices = function (args) {
        var _this = this;
        var healthDices = this.dices.filter(function (dice) { return dice.value === 4; });
        healthDices.forEach(function (dice) {
            var animationId = "dice" + dice.id + "-animation";
            dojo.place("<div id=\"" + animationId + "\" class=\"animation health\"></div>", "dice" + dice.id);
            setTimeout(function () {
                document.getElementById(animationId).style.transform = 'translate(-200px, 100px) scale(1)';
            }, 50);
            setTimeout(function () {
                document.getElementById(animationId).style.transform = 'translate(200px, 400px) scale(0.15)';
            }, 1500);
            setTimeout(function () { return _this.removeDice(dice); }, 2500);
        });
    };
    DiceManager.prototype.resolveEnergyDices = function (args) {
        var _this = this;
        // TODO animation
        this.dices.filter(function (dice) { return dice.value === 5; }).forEach(function (dice) { return _this.removeDice(dice); });
    };
    DiceManager.prototype.resolveSmashDices = function (args) {
        var _this = this;
        // TODO animation
        this.dices.filter(function (dice) { return dice.value === 6; }).forEach(function (dice) { return _this.removeDice(dice); });
    };
    DiceManager.prototype.toggleLockDice = function (dice, forcedLockValue) {
        if (forcedLockValue === void 0) { forcedLockValue = null; }
        dice.locked = forcedLockValue === null ? !dice.locked : forcedLockValue;
        var diceDiv = document.getElementById("dice" + dice.id);
        slideToObjectAndAttach(this.game, diceDiv, dice.locked ? 'locked-dices' : 'dices-selector');
        this.activateRethrowButton();
    };
    DiceManager.prototype.activateRethrowButton = function () {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.dices.filter(function (dice) { return !dice.locked; }).length);
        }
    };
    DiceManager.prototype.createDiceHtml = function (dice, inTokyo) {
        var html = "<div id=\"dice" + dice.id + "\" class=\"dice dice" + dice.value + "\" data-dice-id=\"" + dice.id + "\" data-dice-value=\"" + dice.value + "\">\n        <ol class=\"die-list\" data-roll=\"" + dice.value + "\">";
        for (var die = 1; die <= 6; die++) {
            html += "<li class=\"die-item " + (dice.extra ? 'green' : 'black') + " side" + die + "\" data-side=\"" + die + "\"></li>";
        }
        html += "</ol>";
        if (dice.value === 4 && inTokyo) {
            html += "<div class=\"icon forbidden\"></div>";
        }
        html += "</div>";
        return html;
    };
    DiceManager.prototype.getDiceDiv = function (dice) {
        return document.getElementById("dice" + dice.id);
    };
    DiceManager.prototype.createDice = function (dice, animated, selectable, inTokyo) {
        var _this = this;
        dojo.place(this.createDiceHtml(dice, inTokyo), dice.locked ? 'locked-dices' : 'dices-selector');
        var diceDiv = this.getDiceDiv(dice);
        if (!dice.locked && animated) {
            diceDiv.classList.add('rolled');
            setTimeout(function () { return diceDiv.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll'); }, 100);
            setTimeout(function () { return diceDiv.classList.remove('rolled'); }, 1200);
        }
        if (selectable) {
            diceDiv.addEventListener('click', function () { return _this.toggleLockDice(dice); });
        }
    };
    DiceManager.prototype.removeDice = function (dice) {
        dojo.destroy("dice" + dice.id);
        this.dices.splice(this.dices.indexOf(dice), 1);
    };
    return DiceManager;
}());
var ANIMATION_MS = 2500;
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
        this.diceManager = new DiceManager(this, gamedatas.dices);
        this.cards = new Cards(this);
        this.createVisibleCards(gamedatas.visibleCards);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(function () { return _this.playerTables.forEach(function (playerTable) { return playerTable.initPlacement(); }); }, 200);
        this.setupNotifications();
        $('test').addEventListener('click', function () { return _this.diceManager.resolveHealthDices({
            playerId: 2343493,
        }); });
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
            case 'throwDices':
                var tdArgs = args.args;
                this.setGamestateDescription(tdArgs.throwNumber >= tdArgs.maxThrowNumber ? "last" : '');
                this.onEnteringThrowDices(args.args);
                break;
            case 'resolveDices':
                this.diceManager.hideLock();
                break;
            case 'pickCard':
                this.onEnteringPickCard(args.args);
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
    KingOfTokyo.prototype.onEnteringThrowDices = function (args) {
        this.diceManager.showLock();
        var dices = args.dices;
        this.diceManager.setDices(dices, args.throwNumber === 1, args.throwNumber === args.maxThrowNumber, args.inTokyo);
    };
    KingOfTokyo.prototype.onEnteringPickCard = function (args) {
        if (this.isCurrentPlayerActive()) {
            this.visibleCards.setSelectionMode(1);
            args.disabledIds.forEach(function (id) { return dojo.query("#visible-cards_item_" + id).addClass('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringEndTurn = function () {
        if (this.isCurrentPlayerActive()) {
            this.playerTables[this.player_id].removeDiscardCards();
            this.tableManager.placePlayerTable(); // adapt to removed card
        }
    };
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        log('Leaving state: ' + stateName);
        switch (stateName) {
            case 'resolveDices':
                this.diceManager.removeAllDices();
                break;
            case 'pickCard':
                this.onLeavingPickCard();
                break;
        }
    };
    KingOfTokyo.prototype.onLeavingPickCard = function () {
        this.visibleCards.setSelectionMode(0);
        dojo.query('#visible-cards .stockitem').removeClass('disabled');
    };
    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    KingOfTokyo.prototype.onUpdateActionButtons = function (stateName, args) {
        if (this.isCurrentPlayerActive()) {
            switch (stateName) {
                case 'throwDices':
                    var tdArgs = args;
                    if (tdArgs.throwNumber < tdArgs.maxThrowNumber) {
                        this.addActionButton('rethrow_button', _("Rethrow dices") + (" " + tdArgs.throwNumber + "/" + tdArgs.maxThrowNumber), 'onRethrow');
                        dojo.addClass('rethrow_button', 'disabled');
                    }
                    this.addActionButton('resolve_button', _("Resolve dices"), 'resolveDices', null, null, 'red');
                    break;
                case 'pickCard':
                    this.addActionButton('renew_button', _("Renew cards") + " ( 2 <span class=\"small icon energy\"></span>)", 'onRenew');
                    if (this.energyCounters[this.player_id].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    this.addActionButton('endTurn_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;
                case 'leaveTokyo':
                    this.addActionButton('stayInTokyo_button', _("Stay in Tokyo"), 'onStayInTokyo');
                    this.addActionButton('leaveTokyo_button', _("Leave Tokyo"), 'onLeaveTokyo');
                    break;
            }
        }
    };
    ///////////////////////////////////////////////////
    //// Utility methods
    ///////////////////////////////////////////////////
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
            if (player.eliminated) {
                setTimeout(function () { return _this.eliminatePlayer(playerId); }, 200);
            }
        });
        // (this as any).addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
    };
    KingOfTokyo.prototype.createPlayerTables = function (gamedatas) {
        var _this = this;
        this.getOrderedPlayers().forEach(function (player, index) {
            return _this.playerTables[Number(player.id)] = new PlayerTable(_this, player, index, gamedatas.playersCards[Number(player.id)]);
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
        //this.visibleCards.image_items_per_row = 13;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, 'onVisibleCardClick');
        this.cards.setupCards([this.visibleCards]);
        visibleCards.forEach(function (card) { return _this.visibleCards.addToStockWithId(card.type, "" + card.id); });
    };
    KingOfTokyo.prototype.onVisibleCardClick = function (control_name, item_id) {
        if (dojo.hasClass("visible-cards_item_" + item_id, 'disabled')) {
            this.visibleCards.unselectItem(item_id);
            return;
        }
        this.pickCard(item_id);
    };
    KingOfTokyo.prototype.onRethrow = function () {
        this.rethrowDices(this.diceManager.destroyFreeDices());
    };
    KingOfTokyo.prototype.rethrowDices = function (dicesIds) {
        if (!this.checkAction('rethrow')) {
            return;
        }
        this.takeAction('rethrow', {
            dicesIds: dicesIds
        });
    };
    KingOfTokyo.prototype.resolveDices = function () {
        if (!this.checkAction('resolve')) {
            return;
        }
        this.diceManager.lockFreeDices();
        this.takeAction('resolve');
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
    KingOfTokyo.prototype.pickCard = function (id) {
        if (!this.checkAction('pick')) {
            return;
        }
        this.takeAction('pick', {
            id: id
        });
    };
    KingOfTokyo.prototype.onRenew = function () {
        if (!this.checkAction('renew')) {
            return;
        }
        this.takeAction('renew');
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
            ['pickCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            /*['newPearlMaster', 1],
            ['discardLordPick', 1],
            ['discardLocationPick', 1],
            ['lastTurn', 1],
            ['scoreLords', SCORE_MS],
            ['scoreLocations', SCORE_MS],
            ['scoreCoalition', SCORE_MS],
            ['scorePearlMaster', SCORE_MS],
            ['scoreTotal', SCORE_MS],*/
        ];
        notifs.forEach(function (notif) {
            dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
            _this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
    };
    KingOfTokyo.prototype.notif_resolveNumberDice = function (notif) {
        this.setPoints(notif.args.playerId, notif.args.points);
        this.diceManager.resolveNumberDices(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDice = function (notif) {
        this.setHealth(notif.args.playerId, notif.args.health);
        this.diceManager.resolveHealthDices(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDiceInTokyo = function (notif) {
        this.diceManager.resolveHealthDicesInTokyo(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveEnergyDice = function (notif) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
        this.diceManager.resolveEnergyDices(notif.args);
    };
    KingOfTokyo.prototype.notif_resolveSmashDice = function (notif) {
        var _this = this;
        notif.args.smashedPlayersIds.forEach(function (playerId) {
            var _a;
            var health = (_a = _this.healthCounters[playerId]) === null || _a === void 0 ? void 0 : _a.getValue();
            if (health) {
                var newHealth = Math.max(0, health - notif.args.number);
                _this.setHealth(notif.args.playerId, newHealth);
            }
            _this.diceManager.resolveSmashDices(notif.args);
        });
    };
    KingOfTokyo.prototype.notif_playerEliminated = function (notif) {
        this.setPoints(notif.args.playerId, 0);
        this.eliminatePlayer(notif.args.playerId);
    };
    KingOfTokyo.prototype.notif_leaveTokyo = function (notif) {
        this.playerTables[notif.args.playerId].leaveTokyo();
    };
    KingOfTokyo.prototype.notif_playerEntersTokyo = function (notif) {
        this.playerTables[notif.args.playerId].enterTokyo(notif.args.location);
    };
    KingOfTokyo.prototype.notif_pickCard = function (notif) {
        var card = notif.args.card;
        var newCard = notif.args.newCard;
        this.setEnergy(notif.args.playerId, notif.args.energy);
        moveToAnotherStock(this.visibleCards, this.playerTables[notif.args.playerId].cards, card.type, "" + card.id);
        this.visibleCards.addToStockWithId(newCard.type, "" + newCard.id);
        this.tableManager.placePlayerTable(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_renewCards = function (notif) {
        var _this = this;
        this.setEnergy(notif.args.playerId, notif.args.energy);
        this.visibleCards.removeAll();
        notif.args.cards.forEach(function (card) { return _this.visibleCards.addToStockWithId(card.type, "" + card.id); });
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
    };
    KingOfTokyo.prototype.eliminatePlayer = function (playerId) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById("overall_player_board_" + playerId).classList.add('eliminated-player');
        dojo.place("<div class=\"icon dead\"></div>", "player_board_" + playerId);
        this.tableManager.placePlayerTable(); // adapt to new player number
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
