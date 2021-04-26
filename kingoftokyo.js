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
function setupCards(stocks) {
    stocks.forEach(function (stock) {
        var keepcardsurl = g_gamethemeurl + "img/cards0.jpg";
        for (var id = 1; id <= 48; id++) { // keep
            stock.addItemType(id, id, keepcardsurl, id);
        }
        var discardcardsurl = g_gamethemeurl + "img/cards1.jpg";
        for (var id = 101; id <= 118; id++) { // keep
            stock.addItemType(id, id, discardcardsurl, id);
        }
    });
}
function setupNewCard(card_div, card_type_id, card_id) {
    var type = card_type_id < 100 ? _('Keep') : _('Discard');
    var name = 'Name';
    card_div.innerHTML = "\n    <div class=\"name-wrapper\">\n        <div class=\"outline\">" + name + "</div>\n        <div class=\"text\">" + name + "</div>\n    </div>\n    <div class=\"type-wrapper " + (card_type_id < 100 ? 'keep' : 'discard') + "\">\n        <div class=\"outline\">" + type + "</div>\n        <div class=\"text\">" + type + "</div>\n    </div>\n    <div class=\"description-wrapper\">\n        description\n    </div>\n    ";
}
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
        this.monster = Number(player.monster);
        dojo.place("\n        <div id=\"player-table-" + player.id + "\" class=\"player-table\">\n            <div class=\"player-name\" style=\"color: #" + player.color + "\">" + player.name + "</div> \n            <div id=\"monster-board-" + player.id + "\" class=\"monster-board monster" + this.monster + "\">\n                <div id=\"monster-figure-" + player.id + "\" class=\"monster-figure monster" + this.monster + "\"></div>\n            </div>   \n            <div id=\"cards-" + player.id + "\"></div>      \n        </div>\n\n        ", 'players-tables');
        this.cards = new ebg.stock();
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $("cards-" + this.player.id), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = function (card_div, card_type_id, card_id) { return setupNewCard(card_div, card_type_id, card_id); };
        this.cards.image_items_per_row = 13;
        this.cards.centerItems = true;
        setupCards([this.cards]);
        cards.forEach(function (card) { return _this.cards.addToStockWithId(card.type, "" + card.id); });
    }
    return PlayerTable;
}());
var ANIMATION_MS = 1500;
var KingOfTokyo = /** @class */ (function () {
    function KingOfTokyo() {
        this.healthCounters = [];
        this.energyCounters = [];
        this.selectedDicesIds = null;
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
        // ignore loading of some pictures
        /*(this as any).dontPreloadImage('eye-shadow.png');
        (this as any).dontPreloadImage('publisher.png');
        [1,2,3,4,5,6,7,8,9,10].filter(i => !Object.values(gamedatas.players).some(player => Number((player as any).mat) === i)).forEach(i => (this as any).dontPreloadImage(`playmat_${i}.jpg`));
*/
        log("Starting game setup");
        this.gamedatas = gamedatas;
        log('gamedatas', gamedatas);
        this.createPlayerPanels(gamedatas);
        this.createVisibleCards(gamedatas.visibleCards);
        this.createPlayerTables(gamedatas);
        /*this.lordsStacks = new LordsStacks(this, gamedatas.visibleLords, gamedatas.pickLords);
        this.locationsStacks = new LocationsStacks(this, gamedatas.visibleLocations, gamedatas.pickLocations);

        this.createPlayerTables(gamedatas);

        if (gamedatas.endTurn) {
            this.notif_lastTurn();
        }

        if (Number(gamedatas.gamestate.id) >= 80) { // score or end
            this.onEnteringShowScore(true);
        }

        this.addHelp();*/
        this.setupNotifications();
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
            case 'pickCard':
                this.onEnteringPickCard(args.args);
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
        var _this = this;
        if (args.throwNumber === 1) {
            $('dices-selector').innerHTML = '';
        }
        var dices = args.dices;
        var addedDicesIds = [];
        var _loop_1 = function (i) {
            dices.filter(function (dice) { return dice.value == i && !document.getElementById("dice" + dice.id); }).forEach(function (dice) {
                addedDicesIds.push("dice" + dice.id);
                dojo.place(_this.createDiceHtml(dice), 'dices-selector');
            });
        };
        for (var i = 1; i <= 6; i++) {
            _loop_1(i);
        }
        var selectable = this.isCurrentPlayerActive() && args.throwNumber < args.maxThrowNumber;
        addedDicesIds.map(function (id) { return document.getElementById(id); }).forEach(function (dice) {
            dice.classList.add('rolled');
            setTimeout(function () {
                dice.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll');
            }, 100);
            if (selectable) {
                dice.addEventListener('click', function () { return _this.toggleDiceSelection(dice); });
            }
        });
        this.selectedDicesIds = [];
        dojo.toggleClass('dices-selector', 'selectable', selectable);
    };
    KingOfTokyo.prototype.onEnteringPickCard = function (args) {
        if (this.isCurrentPlayerActive()) {
            this.visibleCards.setSelectionMode(1);
            args.disabledIds.forEach(function (id) { return dojo.query("#visible-cards_item_" + id).addClass('disabled'); });
        }
    };
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        log('Leaving state: ' + stateName);
        switch (stateName) {
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
                        this.addActionButton('rethrow_button', _("Rethrow selected dices") + (" " + tdArgs.throwNumber + "/" + tdArgs.maxThrowNumber), 'onRethrow');
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
        });
        // (this as any).addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
    };
    KingOfTokyo.prototype.createPlayerTables = function (gamedatas) {
        var _this = this;
        this.playerTables = this.getOrderedPlayers().map(function (player, index) { return new PlayerTable(_this, player, index, gamedatas.playersCards[Number(player.id)]); });
    };
    KingOfTokyo.prototype.createVisibleCards = function (visibleCards) {
        var _this = this;
        this.visibleCards = new ebg.stock();
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection';
        this.visibleCards.create(this, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = function (card_div, card_type_id, card_id) { return setupNewCard(card_div, card_type_id, card_id); };
        this.visibleCards.image_items_per_row = 13;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, 'onVisibleCardClick');
        setupCards([this.visibleCards]);
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
        this.rethrowDices(this.selectedDicesIds);
        this.selectedDicesIds.forEach(function (id) { return dojo.destroy("dice" + id); });
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
    KingOfTokyo.prototype.createDiceHtml = function (dice) {
        var html = "<div id=\"dice" + dice.id + "\" class=\"dice dice" + dice.value + "\" data-dice-id=\"" + dice.id + "\" data-dice-value=\"" + dice.value + "\">\n        <ol class=\"die-list\" data-roll=\"" + dice.value + "\">";
        for (var die = 1; die <= 6; die++) {
            html += "<li class=\"die-item " + (dice.extra ? 'green' : 'black') + " side" + die + "\" data-side=\"" + die + "\"></li>";
        }
        html += "</ol></div>";
        return html;
    };
    KingOfTokyo.prototype.toggleDiceSelection = function (dice) {
        var divId = dice.id;
        var selected = !dojo.hasClass(divId, 'selected');
        dojo.toggleClass(divId, 'selected', selected);
        var id = parseInt(dice.dataset.diceId);
        if (selected) {
            this.selectedDicesIds.push(id);
        }
        else {
            this.selectedDicesIds.splice(this.selectedDicesIds.indexOf(id), 1);
        }
        dojo.toggleClass(divId, 'selected', selected);
        dojo.toggleClass('rethrow_button', 'disabled', !this.selectedDicesIds.length);
        dojo.toggleClass('resolve_button', 'disabled', !!this.selectedDicesIds.length);
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
        var _a;
        (_a = this.scoreCtrl[notif.args.playerId]) === null || _a === void 0 ? void 0 : _a.incValue(notif.args.points);
        // TODO animation
    };
    KingOfTokyo.prototype.notif_resolveHealthDice = function (notif) {
        this.healthCounters[notif.args.playerId].incValue(notif.args.health);
        // TODO animation
    };
    KingOfTokyo.prototype.notif_resolveHealthDiceInTokyo = function (notif) {
        // TODO animation
    };
    KingOfTokyo.prototype.notif_resolveEnergyDice = function (notif) {
        this.energyCounters[notif.args.playerId].incValue(notif.args.number);
        // TODO animation
    };
    KingOfTokyo.prototype.notif_resolveSmashDice = function (notif) {
        var _this = this;
        notif.args.smashedPlayersIds.forEach(function (playerId) {
            var _a;
            var health = (_a = _this.healthCounters[playerId]) === null || _a === void 0 ? void 0 : _a.getValue();
            if (health) {
                var newHealth = Math.max(0, health - notif.args.number);
                _this.healthCounters[playerId].toValue(newHealth);
                // TODO animation
            }
        });
    };
    KingOfTokyo.prototype.notif_playerEliminated = function (notif) {
        var _a;
        (_a = this.scoreCtrl[notif.args.playerId]) === null || _a === void 0 ? void 0 : _a.toValue(0);
        // TODO animation? or strike player's name
    };
    KingOfTokyo.prototype.notif_leaveTokyo = function (notif) {
        this.slideToObject("monster-figure-" + notif.args.playerId, "monster-board-" + notif.args.playerId);
    };
    KingOfTokyo.prototype.notif_playerEntersTokyo = function (notif) {
        this.slideToObject("monster-figure-" + notif.args.playerId, "tokyo-" + (notif.args.location == 2 ? 'bay' : 'city'));
    };
    KingOfTokyo.prototype.notif_pickCard = function (notif) {
        var card = notif.args.card;
        var newCard = notif.args.newCard;
        this.energyCounters[notif.args.playerId].incValue(-card.cost);
        this.visibleCards.removeFromStockById("" + card.id); // TODO remove to player hand
        this.visibleCards.addToStockWithId(newCard.type, "" + newCard.id);
    };
    KingOfTokyo.prototype.notif_renewCards = function (notif) {
        var _this = this;
        this.energyCounters[notif.args.playerId].incValue(-2);
        this.visibleCards.removeAll();
        notif.args.cards.forEach(function (card) { return _this.visibleCards.addToStockWithId(card.type, "" + card.id); });
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
