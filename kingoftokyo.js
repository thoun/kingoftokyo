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
/*declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;

declare const board: HTMLDivElement;*/
var GUILD_IDS = [1, 2, 3, 4, 5];
var LORDS_IDS = [1, 2, 3, 4, 5, 6];
var LOCATIONS_UNIQUE_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
var LOCATIONS_GUILDS_IDS = [100, 101];
var LORD_WIDTH = 207.26;
var LORD_HEIGHT = 207;
var LOCATION_WIDTH = 186.24;
var LOCATION_HEIGHT = 124;
function getUniqueId(type, guild) {
    return type * 10 + guild;
}
function setupLordCards(lordStocks) {
    var _this = this;
    var cardsurl = g_gamethemeurl + "img/lords.jpg";
    lordStocks.forEach(function (lordStock) {
        return GUILD_IDS.forEach(function (guild, guildIndex) {
            return LORDS_IDS.forEach(function (lordType, index) {
                return lordStock.addItemType(_this.getUniqueId(lordType, guild), 0, cardsurl, 1 + guildIndex * LORDS_IDS.length + index);
            });
        });
    });
}
function setupLocationCards(locationStocks) {
    var cardsurl = g_gamethemeurl + "img/locations.jpg";
    locationStocks.forEach(function (locationStock) {
        LOCATIONS_UNIQUE_IDS.forEach(function (id, index) {
            return locationStock.addItemType(getUniqueId(id, 0), 0, cardsurl, 1 + index);
        });
        GUILD_IDS.forEach(function (guild, guildIndex) {
            return LOCATIONS_GUILDS_IDS.forEach(function (id, index) {
                return locationStock.addItemType(getUniqueId(id, guild), 0, cardsurl, 15 + GUILD_IDS.length * index + guildIndex);
            });
        });
    });
}
function getGuildName(guild) {
    var guildName = null;
    switch (guild) {
        case 1:
            guildName = _('Farmer');
            break;
        case 2:
            guildName = _('Military');
            break;
        case 3:
            guildName = _('Merchant');
            break;
        case 4:
            guildName = _('Politician');
            break;
        case 5:
            guildName = _('Mage');
            break;
    }
    return guildName;
}
function getLocationTooltip(typeWithGuild) {
    var type = Math.floor(typeWithGuild / 10);
    var guild = typeWithGuild % 10;
    var message = null;
    switch (type) {
        case 1:
            message = _("At the end of the game, this Location is worth 7 IP.");
            break;
        case 2:
            message = _("Immediately gain 1 Pearl. At the end of the game, this Location is worth 5 IP.");
            break;
        case 3:
            message = _("Immediately gain 2 Pearls. At the end of the game, this Location is worth 4 IP.");
            break;
        case 4:
            message = _("Immediately gain 3 Pearls. At the end of the game, this Location is worth 3 IP.");
            break;
        case 5:
            message = _("At the end of the game, this Location is worth 1 IP per silver key held in your Senate Chamber, regardless of whether or not it has been used to take control of a Location.");
            break;
        case 6:
            message = _("At the end of the game, this Location is worth 2 IP per gold key held in your Senate Chamber, regardless of whether or not it has been used to take control of a Location.");
            break;
        case 7:
            message = _("At the end of the game, this Location is worth 1 IP per pair of Pearls in your possession.");
            break;
        case 8:
            message = _("At the end of the game, this Location is worth 2 IP per Location in your control.");
            break;
        case 9:
            message = _("Until your next turn, each opponent MUST only increase the size of their Senate Chamber by taking the first Lord from the deck. At the end of the game, this Location is worth 3 IP.");
            break;
        case 10:
            message = _("Until your next turn, each opponent MUST only increase the size of their Senate Chamber by taking first 2 Lords from the deck. Adding one to their Senate Chamber and discarding the other. At the end of the game, this Location is worth 3 IP.");
            break;
        case 11:
            message = _("Immediately replace all the discarded Lords in to the Lord deck and reshuffle. At the end of the game, this Location is worth 3 IP.");
            break;
        case 12:
            message = _("Immediately replace all the available Locations to the Location deck and reshuffle. At the end of the game, this Location is worth 3 IP.");
            break;
        case 13:
            message = _("Until the end of the game, to take control of a Location, only 2 keys are needed, irrespective of their type. At the end of the game, this Location is worth 3 IP.");
            break;
        case 14:
            message = _("Until the end of the game, when you take control of a Location, you choose this location from the Location deck (No longer from the available Locations). The deck is then reshuffled. At the end of the game, this Location is worth 3 IP.");
            break;
        case 100:
            message = guild ?
                dojo.string.substitute(_("At the end of the game, this Location is worth as many IP as your most influential ${guild_name} Lord."), { guild_name: getGuildName(guild) }) :
                _("At the end of the game, this Location is worth as many IP as your most influential Lord of the indicated color.");
            break;
        case 101:
            message = guild ?
                dojo.string.substitute(_("At the end of the game, this Location is worth 1 IP + a bonus of 1 IP per ${guild_name} Lord present in your Senate Chamber."), { guild_name: getGuildName(guild) }) :
                _("At the end of the game, this Location is worth 1 IP + a bonus of 1 IP per Lord of the indicated color present in your Senate Chamber.");
            break;
    }
    return message;
}
function getLordTooltip(typeWithGuild) {
    var type = Math.floor(typeWithGuild / 10);
    var message = null;
    switch (type) {
        case 1:
            message = _("When this Lord is placed in the Senate Chamber, two Lords in this Chamber (including this one) can be swapped places, except those with keys.");
            break;
        case 2:
            message = _("This Lord gives you 1 silver key.");
            break;
        case 3:
            message = _("This Lord gives you 1 gold key.");
            break;
        case 4:
            message = _("This Lord gives you 2 Pearls.");
            break;
        case 5:
            message = _("This Lord gives you 1 Pearl.");
            break;
        case 6:
            message = _("When this Lord is placed in the Senate Chamber, the top Lord card is taken from the Lord deck and placed in the corresponding discard pile.");
            break;
    }
    return message;
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
var LORD_OVERLAP_WIDTH = 35;
var LORD_OVERLAP_HEIGHT = 35;
function updateDisplay(from) {
    var _this = this;
    if (!$(this.control_name)) {
        return;
    }
    var topDestination = 0;
    var leftDestination = 0;
    var itemWidth = this.item_width;
    var itemHeight = this.item_height;
    var topDestinations = [];
    var leftDestinations = [];
    this.items.forEach(function (item, iIndex) {
        ;
        if (typeof item.loc == "undefined") {
            topDestinations[iIndex] = iIndex * LORD_OVERLAP_HEIGHT;
            leftDestinations[iIndex] = (_this.items.length - iIndex - 1) * LORD_OVERLAP_WIDTH;
        }
    });
    for (var i in this.items) {
        topDestination = topDestinations[i];
        leftDestination = leftDestinations[i];
        var item = this.items[i];
        var itemDivId = this.getItemDivId(item.id);
        var $itemDiv = $(itemDivId);
        if ($itemDiv) {
            if (typeof item.loc == "undefined") {
                dojo.fx.slideTo({
                    node: $itemDiv,
                    top: topDestination,
                    left: leftDestination,
                    duration: 1000,
                    unit: "px"
                }).play();
            }
            else {
                this.page.slideToObject($itemDiv, item.loc, 1000).play();
            }
            dojo.style($itemDiv, "width", itemWidth + "px");
            dojo.style($itemDiv, "height", itemHeight + "px");
            //dojo.style($itemDiv, "z-index", i);
            // dojo.style($itemDiv, "background-size", "100% auto");
        }
        else {
            var type = this.item_type[item.type];
            if (!type) {
                console.error("Stock control: Unknow type: " + type);
            }
            if (typeof itemDivId == "undefined") {
                console.error("Stock control: Undefined item id");
            }
            else {
                if (typeof itemDivId == "object") {
                    console.error("Stock control: Item id with 'object' type");
                    console.error(itemDivId);
                }
            }
            var additional_style = "";
            var jstpl_stock_item_template = dojo.trim(dojo.string.substitute(this.jstpl_stock_item, {
                id: itemDivId,
                width: itemWidth,
                height: itemHeight,
                top: topDestination,
                left: leftDestination,
                image: type.image,
                position: '',
                extra_classes: this.extraClasses,
                additional_style: additional_style
            }));
            dojo.place(jstpl_stock_item_template, this.control_name);
            $itemDiv = $(itemDivId);
            if (typeof item.loc != "undefined") {
                this.page.placeOnObject($itemDiv, item.loc);
            }
            if (this.selectable == 0) {
                dojo.addClass($itemDiv, "stockitem_unselectable");
            }
            dojo.connect($itemDiv, "onclick", this, "onClickOnItem");
            if (Number(type.image_position) !== 0) {
                var backgroundPositionWidth = 0;
                var backgroundPositionHeight = 0;
                if (this.image_items_per_row) {
                    var rowNumber = Math.floor(type.image_position / this.image_items_per_row);
                    if (!this.image_in_vertical_row) {
                        backgroundPositionWidth = (type.image_position - (rowNumber * this.image_items_per_row)) * 100;
                        backgroundPositionHeight = rowNumber * 100;
                    }
                    else {
                        backgroundPositionHeight = (type.image_position - (rowNumber * this.image_items_per_row)) * 100;
                        backgroundPositionWidth = rowNumber * 100;
                    }
                    dojo.style($itemDiv, "backgroundPosition", "-" + backgroundPositionWidth + "% -" + backgroundPositionHeight + "%");
                }
                else {
                    backgroundPositionWidth = type.image_position * 100;
                    dojo.style($itemDiv, "backgroundPosition", "-" + backgroundPositionWidth + "% 0%");
                }
            }
            if (this.onItemCreate) {
                this.onItemCreate($itemDiv, item.type, itemDivId);
            }
            if (typeof from != "undefined") {
                this.page.placeOnObject($itemDiv, from);
                if (typeof item.loc == "undefined") {
                    var anim = dojo.fx.slideTo({
                        node: $itemDiv,
                        top: topDestination,
                        left: leftDestination,
                        duration: 1000,
                        unit: "px"
                    });
                    anim = this.page.transformSlideAnimTo3d(anim, $itemDiv, 1000, null);
                    anim.play();
                }
                else {
                    this.page.slideToObject($itemDiv, item.loc, 1000).play();
                }
            }
            else {
                dojo.style($itemDiv, "opacity", 0);
                dojo.fadeIn({
                    node: $itemDiv
                }).play();
            }
        }
    }
    /*const controlHeight = (itemHeight + itemMargin) + (this.items.length - 1) * LORD_OVERLAP_HEIGHT;
    const controlWidth = (itemWidth + itemMargin) + (this.items.length - 1) * LORD_OVERLAP_WIDTH;
    if (this.autowidth) {
        if (controlWidth > 0) {
            dojo.style(this.control_name, "width", controlWidth + "px");
        }
        if (controlHeight > 0) {
            dojo.style(this.control_name, "height", controlHeight + "px");
        }
        
    }

    dojo.style(this.control_name, "minHeight", (itemHeight + itemMargin) + "px");*/
}
var LordStock = /** @class */ (function () {
    function LordStock(lordsStacks, guild, visibleLords) {
        var _this = this;
        this.lordsStacks = lordsStacks;
        this.guild = guild;
        this.stock = new ebg.stock();
        this.stock.setSelectionAppearance('class');
        this.stock.selectionClass = 'no-visible-selection';
        this.stock.create(this.lordsStacks.game, this.div, LORD_WIDTH, LORD_HEIGHT);
        this.stock.setSelectionMode(0);
        this.stock.onItemCreate = dojo.hitch(this, 'setupNewLordCard');
        this.stock.image_items_per_row = 16;
        this.stock.updateDisplay = function (from) {
            updateDisplay.apply(_this.stock, [from]);
            _this.updateSize();
        };
        dojo.connect(this.stock, 'onChangeSelection', this, 'click');
        setupLordCards([this.stock]);
        visibleLords.forEach(function (lord) { return _this.stock.addToStockWithId(_this.lordsStacks.getCardUniqueId(lord), "" + lord.id); });
        //this.updateSize();
        this.div.addEventListener('click', function () { return _this.click(); });
    }
    LordStock.prototype.getStock = function () {
        return this.stock;
    };
    LordStock.prototype.addLords = function (lords) {
        var _this = this;
        lords.forEach(function (lord) { return _this.stock.addToStockWithId(_this.lordsStacks.getCardUniqueId(lord), "" + lord.id); });
    };
    LordStock.prototype.removeAllTo = function (to) {
        this.stock.removeAllTo(to);
    };
    LordStock.prototype.updateSize = function () {
        var size = this.stock.items.length;
        this.div.style.width = LORD_WIDTH + (Math.max(size - 1, 0) * LORD_OVERLAP_WIDTH) + "px";
        this.div.style.height = LORD_HEIGHT + (Math.max(size - 1, 0) * LORD_OVERLAP_HEIGHT) + "px";
        this.div.style.display = size ? 'inline-block' : 'none';
    };
    Object.defineProperty(LordStock.prototype, "div", {
        get: function () {
            return document.getElementById("lord-visible-stock" + this.guild);
        },
        enumerable: false,
        configurable: true
    });
    LordStock.prototype.setSelectable = function (selectable) {
        this.selectable = selectable;
        var action = selectable ? 'add' : 'remove';
        this.div.classList[action]('selectable');
        this.stock.setSelectionMode(selectable ? 2 : 0);
    };
    LordStock.prototype.click = function () {
        if (!this.selectable) {
            return;
        }
        this.lordsStacks.game.lordStockPick(this.guild);
    };
    LordStock.prototype.setupNewLordCard = function (card_div, card_type_id, card_id) {
        var message = getLordTooltip(card_type_id);
        if (message) {
            this.lordsStacks.game.addTooltipHtml(card_div.id, message);
        }
    };
    return LordStock;
}());
var AbstractStacks = /** @class */ (function () {
    function AbstractStacks(game) {
        this.game = game;
        this.max = 3;
        this.allHidden = false;
    }
    AbstractStacks.prototype.setSelectable = function (selectable, limitToHidden, allHidden) {
        this.selectable = selectable;
        var action = selectable ? 'add' : 'remove';
        this.pileDiv.classList[action]('selectable');
        var buttons = Array.from(this.pileDiv.getElementsByClassName('button'));
        if (limitToHidden) {
            var adjustedLimitToHidden_1 = Math.min(this.max, limitToHidden);
            if (selectable) {
                buttons.filter(function (button) { return parseInt(button.dataset.number) !== adjustedLimitToHidden_1; })
                    .forEach(function (button) { return button.classList.add('hidden'); });
            }
        }
        if (!selectable) {
            buttons.forEach(function (button) { return button.classList.remove('hidden'); });
        }
        // if player has all hidden location, we replace the 3 buttons by one special for the rest of the game
        if (allHidden && buttons.length > 1) {
            this.allHidden = true;
            document.getElementById('location-hidden-pile').innerHTML = '<div class="button eye location-hidden-pile-eye-tooltip" data-number="0"></div>';
            this.game.addTooltipHtml('location-hidden-pile-eye-tooltip', _("As you have the See all deck location, you can pick a location from all deck, but you cannot pick visible locations."));
        }
    };
    AbstractStacks.prototype.setMax = function (max) {
        this.max = max;
        if (max === 0) {
            this.pileDiv.style.visibility = 'hidden';
        }
        else if (!this.allHidden && max < 3) {
            var buttons = Array.from(this.pileDiv.getElementsByClassName('button'));
            buttons.filter(function (button) { return parseInt(button.dataset.number) > max; })
                .forEach(function (button) { return button.classList.add('max'); });
        }
    };
    AbstractStacks.prototype.setPick = function (showPick, pickSelectable, collection) {
        var _this = this;
        if (collection) {
            this.pickStock.items.filter(function (item) { return !collection.some(function (i) { return item.id === "" + i.id; }); }).forEach(function (item) { return _this.pickStock.removeFromStockById("" + item.id); });
            setTimeout(function () { return _this.pickStock.updateDisplay(); }, 100);
        }
        this.pickDiv.style.display = showPick ? 'block' : 'none';
        var action = pickSelectable ? 'add' : 'remove';
        this.pickDiv.classList[action]('selectable');
        this.pickSelectable = pickSelectable;
        collection === null || collection === void 0 ? void 0 : collection.filter(function (item) { return !_this.pickStock.items.some(function (i) { return i.id === "" + item.id; }); }).forEach(function (item) {
            var from = _this.getStockContaining("" + item.id);
            if (from) {
                moveToAnotherStock(from, _this.pickStock, _this.getCardUniqueId(item), "" + item.id);
            }
            else {
                _this.pickStock.addToStockWithId(_this.getCardUniqueId(item), "" + item.id, _this.pileDiv.id);
            }
        });
    };
    AbstractStacks.prototype.getGuildStock = function (guild) {
        throw new Error("Must be overriden");
    };
    AbstractStacks.prototype.setPickStockClick = function () {
        dojo.connect(this.pickStock, 'onChangeSelection', this, 'pickClick');
    };
    return AbstractStacks;
}());
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var LordsStacks = /** @class */ (function (_super) {
    __extends(LordsStacks, _super);
    function LordsStacks(game, visibleLords, pickLords) {
        var _this = _super.call(this, game) || this;
        _this.lordsStocks = [];
        _this.pileDiv.addEventListener('click', function (e) { return _this.onHiddenLordsClick(e); });
        Array.from(_this.pileDiv.getElementsByClassName('button')).forEach(function (button) { return button.addEventListener('click', function (e) { return _this.onHiddenLordsClick(e); }); });
        GUILD_IDS.forEach(function (guild) { return _this.lordsStocks[guild] = new LordStock(_this, guild, visibleLords[guild]); });
        _this.pickStock = new ebg.stock();
        _this.pickStock.setSelectionAppearance('class');
        _this.pickStock.selectionClass = 'no-visible-selection';
        _this.pickStock.create(_this.game, _this.pickDiv.children[0], LORD_WIDTH, LORD_HEIGHT);
        _this.pickStock.centerItems = true;
        _this.pickStock.image_items_per_row = 16;
        _this.pickStock.onItemCreate = dojo.hitch(_this, 'setupNewLordCard');
        setupLordCards([_this.pickStock]);
        _this.setPickStockClick();
        pickLords.forEach(function (lord) { return _this.pickStock.addToStockWithId(_this.getCardUniqueId(lord), "" + lord.id); });
        _this.game.addTooltipHtmlToClass('lord-hidden-pile-tooltip', _("Reveal 1 to 3 hidden lords. Choose one, the others are discarded"));
        return _this;
    }
    Object.defineProperty(LordsStacks.prototype, "pileDiv", {
        get: function () {
            return document.getElementById('lord-hidden-pile');
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(LordsStacks.prototype, "pickDiv", {
        get: function () {
            return document.getElementById('lord-pick');
        },
        enumerable: false,
        configurable: true
    });
    LordsStacks.prototype.getStockContaining = function (lordId) {
        var _this = this;
        if (this.pickStock.items.some(function (item) { return item.id === lordId; })) {
            return this.pickStock;
        }
        else {
            var guild = GUILD_IDS.find(function (guild) { return _this.lordsStocks[guild].getStock().items.some(function (item) { return item.id === lordId; }); });
            if (guild) {
                return this.lordsStocks[guild].getStock();
            }
        }
        return null;
    };
    LordsStacks.prototype.discardVisible = function () {
        var _this = this;
        GUILD_IDS.forEach(function (guild) { return _this.lordsStocks[guild].removeAllTo('lord-hidden-pile'); });
    };
    LordsStacks.prototype.addLords = function (lords) {
        var _this = this;
        var guilds = new Set(lords.map(function (lord) { return lord.guild; }));
        guilds.forEach(function (guild) { return _this.lordsStocks[guild].addLords(lords.filter(function (lord) { return lord.guild === guild; })); });
    };
    LordsStacks.prototype.setSelectable = function (selectable, limitToHidden, allHidden) {
        _super.prototype.setSelectable.call(this, selectable, limitToHidden, allHidden);
        if (!selectable || !limitToHidden) {
            this.lordsStocks.forEach(function (lordStock) { return lordStock.setSelectable(selectable); });
        }
    };
    LordsStacks.prototype.hasPickCards = function () {
        return this.pickStock.items.length > 0;
    };
    LordsStacks.prototype.discardPick = function (lords) {
        var _this = this;
        var guilds = new Set(lords.map(function (lord) { return lord.guild; }));
        guilds.forEach(function (guild) {
            return lords.filter(function (lord) { return lord.guild === guild; }).forEach(function (lord) {
                return moveToAnotherStock(_this.pickStock, _this.lordsStocks[guild].getStock(), _this.getCardUniqueId(lord), "" + lord.id);
            });
        });
    };
    LordsStacks.prototype.getCardUniqueId = function (lord) {
        return getUniqueId(lord.type, lord.guild);
    };
    LordsStacks.prototype.pickClick = function (control_name, item_id) {
        this.game.lordPick(Number(item_id));
    };
    LordsStacks.prototype.onHiddenLordsClick = function (event) {
        if (!this.selectable) {
            return;
        }
        var number = parseInt(event.target.dataset.number);
        if (isNaN(number)) {
            return;
        }
        if (!this.game.checkAction('chooseDeckStack')) {
            return;
        }
        this.game.takeAction('chooseLordDeckStack', {
            number: number
        });
        event.stopPropagation();
    };
    LordsStacks.prototype.setupNewLordCard = function (card_div, card_type_id, card_id) {
        var message = getLordTooltip(card_type_id);
        if (message) {
            this.game.addTooltipHtml(card_div.id, message);
        }
    };
    LordsStacks.prototype.getGuildStock = function (guild) {
        return this.lordsStocks[guild].getStock();
    };
    return LordsStacks;
}(AbstractStacks));
var LocationsStacks = /** @class */ (function (_super) {
    __extends(LocationsStacks, _super);
    function LocationsStacks(game, visibleLocations, pickLocations) {
        var _this = _super.call(this, game) || this;
        _this.pileDiv.addEventListener('click', function (e) { return _this.onHiddenLocationClick(e); });
        Array.from(_this.pileDiv.getElementsByClassName('button')).forEach(function (button) { return button.addEventListener('click', function (e) { return _this.onHiddenLocationClick(e); }); });
        _this.visibleLocationsStock = new ebg.stock();
        _this.visibleLocationsStock.setSelectionAppearance('class');
        _this.visibleLocationsStock.selectionClass = 'no-visible-selection';
        _this.visibleLocationsStock.create(_this.game, $('location-visible-stock'), LOCATION_WIDTH, LOCATION_HEIGHT);
        _this.visibleLocationsStock.setSelectionMode(0);
        _this.visibleLocationsStock.onItemCreate = dojo.hitch(_this, 'setupNewLocationCard');
        _this.visibleLocationsStock.image_items_per_row = 13;
        dojo.connect(_this.visibleLocationsStock, 'onChangeSelection', _this, 'onVisibleLocationClick');
        _this.pickStock = new ebg.stock();
        _this.pickStock.setSelectionAppearance('class');
        _this.pickStock.selectionClass = 'no-visible-selection';
        _this.pickStock.create(_this.game, _this.pickDiv.children[0], LOCATION_WIDTH, LOCATION_HEIGHT);
        _this.pickStock.centerItems = true;
        _this.pickStock.onItemCreate = dojo.hitch(_this, 'setupNewLocationCard');
        _this.pickStock.image_items_per_row = 13;
        _this.setPickStockClick();
        setupLocationCards([_this.visibleLocationsStock, _this.pickStock]);
        visibleLocations.forEach(function (location) { return _this.visibleLocationsStock.addToStockWithId(_this.getCardUniqueId(location), "" + location.id); });
        pickLocations.forEach(function (location) { return _this.pickStock.addToStockWithId(_this.getCardUniqueId(location), "" + location.id); });
        _this.game.addTooltipHtmlToClass('location-hidden-pile-tooltip', _("Reveal 1 to 3 hidden locations. Choose one, the others are discarded"));
        return _this;
    }
    Object.defineProperty(LocationsStacks.prototype, "pileDiv", {
        get: function () {
            return document.getElementById('location-hidden-pile');
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(LocationsStacks.prototype, "pickDiv", {
        get: function () {
            return document.getElementById('location-pick');
        },
        enumerable: false,
        configurable: true
    });
    LocationsStacks.prototype.getStockContaining = function (locationId) {
        if (this.pickStock.items.some(function (item) { return item.id === locationId; })) {
            return this.pickStock;
        }
        else if (this.visibleLocationsStock.items.some(function (item) { return item.id === locationId; })) {
            return this.visibleLocationsStock;
        }
        return null;
    };
    LocationsStacks.prototype.setSelectable = function (selectable, limitToHidden, allHidden) {
        _super.prototype.setSelectable.call(this, selectable, limitToHidden, allHidden);
        var visibleSelectable = selectable && !allHidden;
        this.visibleLocationsStock.setSelectionMode(visibleSelectable ? 1 : 0);
        var action = visibleSelectable && this.visibleLocationsStock.items.length ? 'add' : 'remove';
        this.visibleLocationsStock.container_div.classList[action]('selectable');
    };
    LocationsStacks.prototype.discardVisible = function () {
        this.visibleLocationsStock.removeAllTo('location-hidden-pile');
    };
    LocationsStacks.prototype.discardPick = function (locations) {
        var _this = this;
        locations.forEach(function (location) { return moveToAnotherStock(_this.pickStock, _this.visibleLocationsStock, _this.getCardUniqueId(location), "" + location.id); });
    };
    LocationsStacks.prototype.getCardUniqueId = function (location) {
        var _a;
        return getUniqueId(location.type, (_a = location.passivePowerGuild) !== null && _a !== void 0 ? _a : 0);
    };
    LocationsStacks.prototype.pickClick = function (control_name, item_id) {
        this.game.locationPick(Number(item_id));
    };
    LocationsStacks.prototype.setupNewLocationCard = function (card_div, card_type_id, card_id) {
        var message = getLocationTooltip(card_type_id);
        if (message) {
            this.game.addTooltipHtml(card_div.id, message);
        }
    };
    LocationsStacks.prototype.onHiddenLocationClick = function (event) {
        if (!this.selectable) {
            return;
        }
        var number = parseInt(event.target.dataset.number);
        if (isNaN(number)) {
            return;
        }
        if (!this.game.checkAction('chooseDeckStack')) {
            return;
        }
        this.game.takeAction('chooseLocationDeckStack', {
            number: number
        });
        event.stopPropagation();
    };
    LocationsStacks.prototype.onVisibleLocationClick = function (control_name, item_id) {
        if (!item_id || !this.game.checkAction('chooseVisibleLocation')) {
            return;
        }
        this.game.takeAction('chooseVisibleLocation', {
            id: item_id
        });
    };
    return LocationsStacks;
}(AbstractStacks));
var PlayerTableSpotStock = /** @class */ (function () {
    function PlayerTableSpotStock(game, playerTable, player, spot, spotNumber) {
        var _a;
        this.game = game;
        this.playerTable = playerTable;
        this.spot = spot;
        this.spotNumber = spotNumber;
        this.playerId = Number(player.id);
        dojo.place("<div id=\"player-table-" + this.playerId + "-spot" + spotNumber + "\" class=\"player-table-spot spot" + spotNumber + "\">\n                <div id=\"player" + this.playerId + "-spot" + spotNumber + "-lord-stock\"></div>\n                <div id=\"player" + this.playerId + "-spot" + spotNumber + "-location-stock\" class=\"player-table-spot-location\"></div>\n                <div id=\"player" + this.playerId + "-spot" + spotNumber + "-token\" class=\"player-table-spot-token\"></div>\n        </div>", "player-table-" + this.playerId);
        this.lordsStock = new ebg.stock();
        this.lordsStock.create(this.game, $("player" + this.playerId + "-spot" + spotNumber + "-lord-stock"), LORD_WIDTH, LORD_HEIGHT);
        this.lordsStock.setSelectionMode(0);
        this.lordsStock.setSelectionAppearance('class');
        this.lordsStock.onItemCreate = dojo.hitch(this, 'setupNewLordCard');
        this.lordsStock.image_items_per_row = 16;
        dojo.connect(this.lordsStock, 'onChangeSelection', this, 'onLordSelection');
        setupLordCards([this.lordsStock]);
        var lord = spot.lord;
        if (lord) {
            this.lordsStock.addToStockWithId(getUniqueId(lord.type, lord.guild), "" + lord.id);
        }
        this.locationsStock = new ebg.stock();
        this.locationsStock.create(this.game, $("player" + this.playerId + "-spot" + spotNumber + "-location-stock"), LOCATION_WIDTH, LOCATION_HEIGHT);
        this.locationsStock.setSelectionMode(0);
        this.locationsStock.onItemCreate = dojo.hitch(this, 'setupNewLocationCard');
        this.locationsStock.image_items_per_row = 13;
        setupLocationCards([this.locationsStock]);
        var location = spot.location;
        if (location) {
            this.locationsStock.addToStockWithId(getUniqueId(location.type, (_a = location.passivePowerGuild) !== null && _a !== void 0 ? _a : 0), "" + location.id);
        }
    }
    PlayerTableSpotStock.prototype.hasLord = function () {
        return !!this.spot.lord;
    };
    PlayerTableSpotStock.prototype.hasLocation = function () {
        return !!this.spot.location;
    };
    PlayerTableSpotStock.prototype.getLordStock = function () {
        return this.lordsStock;
    };
    Object.defineProperty(PlayerTableSpotStock.prototype, "tokenWrapper", {
        get: function () {
            return document.getElementById("player" + this.playerId + "-spot" + this.spotNumber + "-token");
        },
        enumerable: false,
        configurable: true
    });
    PlayerTableSpotStock.prototype.getLord = function () {
        return this.spot.lord;
    };
    PlayerTableSpotStock.prototype.setLord = function (lord, fromStock) {
        if (fromStock) {
            moveToAnotherStock(fromStock, this.lordsStock, getUniqueId(lord.type, lord.guild), "" + lord.id);
        }
        else {
            this.lordsStock.addToStockWithId(getUniqueId(lord.type, lord.guild), "" + lord.id, 'lord-hidden-pile');
        }
        this.spot.lord = lord;
    };
    PlayerTableSpotStock.prototype.setLocation = function (location, fromStock) {
        var _a, _b;
        if (fromStock) {
            moveToAnotherStock(fromStock, this.locationsStock, getUniqueId(location.type, (_a = location.passivePowerGuild) !== null && _a !== void 0 ? _a : 0), "" + location.id);
        }
        else {
            this.locationsStock.addToStockWithId(getUniqueId(location.type, (_b = location.passivePowerGuild) !== null && _b !== void 0 ? _b : 0), "" + location.id, 'location-hidden-pile');
        }
        this.spot.location = location;
    };
    PlayerTableSpotStock.prototype.setSelectableForSwap = function (selectable) {
        if (!this.spot.lord) {
            return;
        }
        if (this.spot.lord.key) { // can't swap
            dojo.toggleClass("player" + this.playerId + "-spot" + this.spotNumber + "-lord-stock_item_" + this.spot.lord.id, 'disabled', selectable);
        }
        else { // can swap
            this.lordsStock.setSelectionMode(selectable ? 2 : 0);
            dojo.toggleClass("player" + this.playerId + "-spot" + this.spotNumber + "-lord-stock_item_" + this.spot.lord.id, 'selectable', selectable);
            if (!selectable) {
                this.lordsStock.unselectAll();
            }
        }
    };
    PlayerTableSpotStock.prototype.onLordSelection = function () {
        var items = this.lordsStock.getSelectedItems();
        if (items.length == 1) {
            this.playerTable.addSelectedSpot(this.spotNumber);
        }
        else if (items.length == 0) {
            this.playerTable.removeSelectedSpot(this.spotNumber);
        }
    };
    PlayerTableSpotStock.prototype.placeTopLordToken = function () {
        var guild = this.spot.lord.guild;
        var tokenDiv = document.getElementById("top-lord-token-" + guild + "-" + this.playerId);
        this.addTokenDiv(tokenDiv);
    };
    PlayerTableSpotStock.prototype.setupNewLordCard = function (card_div, card_type_id, card_id) {
        var message = getLordTooltip(card_type_id);
        if (message) {
            this.game.addTooltipHtml(card_div.id, message);
        }
    };
    PlayerTableSpotStock.prototype.setupNewLocationCard = function (card_div, card_type_id, card_id) {
        var message = getLocationTooltip(card_type_id);
        if (message) {
            this.game.addTooltipHtml(card_div.id, message);
        }
    };
    PlayerTableSpotStock.prototype.addTokenDiv = function (tokenDiv) {
        slideToObjectAndAttach(this.game, tokenDiv, this.tokenWrapper.id);
    };
    PlayerTableSpotStock.prototype.getTokenDiv = function () {
        return this.tokenWrapper.getElementsByTagName('div')[0];
    };
    PlayerTableSpotStock.prototype.highlightLord = function (guild) {
        var _a;
        if (guild === void 0) { guild = null; }
        var cardId = (_a = this.lordsStock.items[0]) === null || _a === void 0 ? void 0 : _a.id;
        cardId && document.getElementById(this.lordsStock.container_div.id + "_item_" + cardId).classList.add("highlight" + (guild ? "-guild" + guild : ''));
    };
    PlayerTableSpotStock.prototype.clearLordHighlight = function () {
        var _a;
        var cardId = (_a = this.lordsStock.items[0]) === null || _a === void 0 ? void 0 : _a.id;
        cardId && document.getElementById(this.lordsStock.container_div.id + "_item_" + cardId).classList.remove('highlight');
    };
    PlayerTableSpotStock.prototype.highlightLocation = function () {
        var _a;
        var cardId = (_a = this.locationsStock.items[0]) === null || _a === void 0 ? void 0 : _a.id;
        cardId && document.getElementById(this.locationsStock.container_div.id + "_item_" + cardId).classList.add('highlight');
    };
    return PlayerTableSpotStock;
}());
var SPOTS_NUMBERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
var PlayerTable = /** @class */ (function () {
    function PlayerTable(game, player, spots) {
        var _this = this;
        this.game = game;
        this.spotsStock = [];
        this.swapSpots = null;
        this.playerId = Number(player.id);
        dojo.place("<div id=\"player-table-wrapper-" + this.playerId + "\" class=\"player-table-wrapper\">\n            <div id=\"player-table-mat-" + this.playerId + "\" class=\"player-table-mat mat" + player.mat + "\">\n                <div id=\"player-table-" + this.playerId + "\" class=\"player-table\">\n                    <div class=\"player-name mat" + player.mat + "\" style=\"color: #" + player.color + ";\">\n                        " + player.name + "\n                    </div>\n                </div>\n            </div>\n        </div>", 'players-tables');
        SPOTS_NUMBERS.forEach(function (spotNumber) {
            _this.spotsStock[spotNumber] = new PlayerTableSpotStock(game, _this, player, spots[spotNumber], spotNumber);
        });
        this.checkTopLordToken();
    }
    PlayerTable.prototype.checkTopLordToken = function () {
        var lordsSpots = this.spotsStock.filter(function (spotStock) { return spotStock.getLord(); });
        var guilds = new Set(lordsSpots.map(function (spotStock) { return spotStock.getLord().guild; }));
        guilds.forEach(function (guild) {
            var guildLordsSpots = lordsSpots.filter(function (spotStock) { return spotStock.getLord().guild === guild; });
            var topLordSpot = guildLordsSpots[0];
            guildLordsSpots.forEach(function (spot) {
                if (spot.getLord().points > topLordSpot.getLord().points) {
                    topLordSpot = spot;
                }
            });
            topLordSpot.placeTopLordToken();
        });
    };
    PlayerTable.prototype.addLord = function (spot, lord, fromStock) {
        var _this = this;
        this.spotsStock[spot].setLord(lord, fromStock);
        setTimeout(function () { return _this.checkTopLordToken(); }, 500);
    };
    PlayerTable.prototype.addLocation = function (spot, location, fromStock) {
        this.spotsStock[spot].setLocation(location, fromStock);
    };
    PlayerTable.prototype.setSelectableForSwap = function (selectable) {
        var _this = this;
        this.swapSpots = selectable ? [] : null;
        SPOTS_NUMBERS.forEach(function (spotNumber) { return _this.spotsStock[spotNumber].setSelectableForSwap(selectable); });
    };
    PlayerTable.prototype.removeSelectedSpot = function (spot) {
        if (!this.swapSpots) {
            return false;
        }
        var index = this.swapSpots.indexOf(spot);
        if (index !== -1) {
            this.swapSpots.splice(index, 1);
            this.setCanSwap();
        }
    };
    PlayerTable.prototype.addSelectedSpot = function (spot) {
        if (!this.swapSpots) {
            return false;
        }
        if (!this.swapSpots.some(function (val) { return val === spot; })) {
            this.swapSpots.push(spot);
            this.setCanSwap();
        }
    };
    PlayerTable.prototype.setCanSwap = function () {
        this.game.setCanSwap(this.swapSpots);
    };
    PlayerTable.prototype.lordSwapped = function (args) {
        var _this = this;
        var lordSpot1 = this.spotsStock[args.spot1].getLord();
        var lordSpot2 = this.spotsStock[args.spot2].getLord();
        var tokenSpot1 = this.spotsStock[args.spot1].getTokenDiv();
        var tokenSpot2 = this.spotsStock[args.spot2].getTokenDiv();
        this.spotsStock[args.spot1].setLord(lordSpot2, this.spotsStock[args.spot2].getLordStock());
        this.spotsStock[args.spot2].setLord(lordSpot1, this.spotsStock[args.spot1].getLordStock());
        if (tokenSpot2) {
            setTimeout(function () { return _this.spotsStock[args.spot1].addTokenDiv(tokenSpot2); }, 500);
        }
        if (tokenSpot1) {
            setTimeout(function () { return _this.spotsStock[args.spot2].addTokenDiv(tokenSpot1); }, 500);
        }
    };
    PlayerTable.prototype.highlightCoalition = function (coalition) {
        var _this = this;
        this.spotsStock.filter(function (spotStock) { return spotStock.hasLord(); }).forEach(function (spotStock) { return spotStock.clearLordHighlight(); });
        coalition.alreadyCounted.forEach(function (spotNumber) { return _this.spotsStock[spotNumber].highlightLord(coalition.guild); });
    };
    PlayerTable.prototype.highlightLocations = function () {
        this.spotsStock.filter(function (spotStock) { return spotStock.hasLocation(); }).forEach(function (spotStock) { return spotStock.highlightLocation(); });
    };
    PlayerTable.prototype.highlightTopLords = function () {
        this.spotsStock.filter(function (spotStock) { return spotStock.hasLord() && !!spotStock.getTokenDiv(); }).forEach(function (spotStock) { return spotStock.highlightLord(); });
    };
    return PlayerTable;
}());
var GUILD_COLOR = [];
GUILD_COLOR[1] = '#E0CA4E';
GUILD_COLOR[2] = '#DB6646';
GUILD_COLOR[3] = '#037552';
GUILD_COLOR[4] = '#0096D2';
GUILD_COLOR[5] = '#74549F';
var Minimap = /** @class */ (function () {
    function Minimap(playerId, spots) {
        var _this = this;
        this.playerId = playerId;
        var html = "<div id=\"minimap-" + playerId + "\" class=\"minimap\">";
        SPOTS_NUMBERS.forEach(function (spotNumber) {
            return html += "<div class=\"player-table-spot spot" + spotNumber + "\"></div>";
        });
        html += "</div>";
        dojo.place(html, "lord-counter-wrapper-" + playerId);
        SPOTS_NUMBERS.filter(function (spotNumber) { return !!spots[spotNumber - 1].lord; }).forEach(function (spotNumber) { return _this.setGuildToSpot(spotNumber, spots[spotNumber - 1].lord.guild); });
    }
    Minimap.prototype.setGuildToSpot = function (spotNumber, guild) {
        document.getElementById("minimap-" + this.playerId).getElementsByClassName("spot" + spotNumber)[0].style.background = GUILD_COLOR[guild];
    };
    Minimap.prototype.addLord = function (spot, lord) {
        this.setGuildToSpot(spot, lord.guild);
    };
    Minimap.prototype.lordSwapped = function (args) {
        var colorLordSpot1 = document.getElementById("minimap-" + this.playerId).getElementsByClassName("spot" + args.spot1)[0].style.background;
        var colorLordSpot2 = document.getElementById("minimap-" + this.playerId).getElementsByClassName("spot" + args.spot2)[0].style.background;
        document.getElementById("minimap-" + this.playerId).getElementsByClassName("spot" + args.spot1)[0].style.background = colorLordSpot2;
        document.getElementById("minimap-" + this.playerId).getElementsByClassName("spot" + args.spot2)[0].style.background = colorLordSpot1;
    };
    return Minimap;
}());
var ANIMATION_MS = 500;
var SCORE_MS = 1500;
var isDebug = window.location.host == 'studio.boardgamearena.com';
var log = isDebug ? console.log.bind(window.console) : function () { };
var LOG_GUILD_COLOR = [];
LOG_GUILD_COLOR[1] = '#c1950b';
LOG_GUILD_COLOR[2] = '#770405';
LOG_GUILD_COLOR[3] = '#097138';
LOG_GUILD_COLOR[4] = '#011d4d';
LOG_GUILD_COLOR[5] = '#522886';
var KingOfTokyo = /** @class */ (function () {
    function KingOfTokyo() {
        this.playersTables = [];
        this.minimaps = [];
        this.pearlCounters = [];
        this.silverKeyCounters = [];
        this.goldKeyCounters = [];
        this.playerInPopin = null;
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
        /*this.createPlayerPanels(gamedatas);

        this.lordsStacks = new LordsStacks(this, gamedatas.visibleLords, gamedatas.pickLords);
        this.locationsStacks = new LocationsStacks(this, gamedatas.visibleLocations, gamedatas.pickLocations);

        this.createPlayerTables(gamedatas);

        if (gamedatas.endTurn) {
            this.notif_lastTurn();
        }

        if (Number(gamedatas.gamestate.id) >= 80) { // score or end
            this.onEnteringShowScore(true);
        }

        this.addHelp();

        this.setupNotifications();*/
        log("Ending game setup");
    };
    ///////////////////////////////////////////////////
    //// Game & client states
    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    KingOfTokyo.prototype.onEnteringState = function (stateName, args) {
        var _this = this;
        var _a;
        log('Entering state: ' + stateName, args.args);
        switch (stateName) {
            case 'lordStackSelection':
                var limitToHidden = args.args.limitToHidden;
                this.setGamestateDescription(limitToHidden ? "limitToHidden" + limitToHidden : '');
                this.onEnteringLordStackSelection(args.args);
                break;
            case 'lordSelection':
                var multiple = args.args.multiple;
                var number = (_a = args.args.lords) === null || _a === void 0 ? void 0 : _a.length;
                this.setGamestateDescription(multiple ? (number > 1 ? 'multiple' : 'last') : '');
                this.onEnteringLordSelection(args.args);
                break;
            case 'lordSwap':
                this.onEnteringLordSwap();
                break;
            case 'locationStackSelection':
                var allHidden = args.args.allHidden;
                this.setGamestateDescription(allHidden ? 'allHidden' : '');
                this.onEnteringLocationStackSelection(args.args);
                break;
            case 'locationSelection':
                this.onEnteringLocationSelection(args.args);
                break;
            case 'showScore':
                Object.keys(this.gamedatas.players).forEach(function (playerId) { return _this.scoreCtrl[playerId].setValue(0); });
                this.onEnteringShowScore();
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
    KingOfTokyo.prototype.onEnteringLordStackSelection = function (args) {
        this.lordsStacks.setMax(args.max);
        if (this.isCurrentPlayerActive()) {
            this.lordsStacks.setSelectable(true, args.limitToHidden);
        }
    };
    KingOfTokyo.prototype.onEnteringLordSelection = function (args) {
        this.lordsStacks.setPick(true, this.isCurrentPlayerActive(), args.lords);
    };
    KingOfTokyo.prototype.onEnteringLordSwap = function () {
        if (this.isCurrentPlayerActive()) {
            this.swapSpots = [];
            this.playersTables[this.player_id].setSelectableForSwap(true);
        }
    };
    KingOfTokyo.prototype.onEnteringLocationStackSelection = function (args) {
        this.locationsStacks.setMax(args.max);
        if (this.isCurrentPlayerActive()) {
            this.locationsStacks.setSelectable(true, null, args.allHidden);
        }
    };
    KingOfTokyo.prototype.onEnteringLocationSelection = function (args) {
        this.locationsStacks.setPick(true, this.isCurrentPlayerActive(), args.locations);
    };
    KingOfTokyo.prototype.onEnteringShowScore = function (fromReload) {
        if (fromReload === void 0) { fromReload = false; }
        this.closePopin();
        var lastTurnBar = document.getElementById('last-round');
        if (lastTurnBar) {
            lastTurnBar.style.display = 'none';
        }
        document.getElementById('stacks').style.display = 'none';
        document.getElementById('score').style.display = 'flex';
        Object.values(this.gamedatas.players).forEach(function (player) {
            //if we are a reload of end state, we display values, else we wait for notifications
            var score = fromReload ? player.newScore : null;
            dojo.place("<tr id=\"score" + player.id + "\">\n                <td class=\"player-name\" style=\"color: #" + player.color + "\">" + player.name + "</td>\n                <td id=\"lords-score" + player.id + "\" class=\"score-number lords-score\">" + ((score === null || score === void 0 ? void 0 : score.lords) !== undefined ? score.lords : '') + "</td>\n                <td id=\"locations-score" + player.id + "\" class=\"score-number locations-score\">" + ((score === null || score === void 0 ? void 0 : score.locations) !== undefined ? score.locations : '') + "</td>\n                <td id=\"coalition-score" + player.id + "\" class=\"score-number coalition-score\">" + ((score === null || score === void 0 ? void 0 : score.coalition) !== undefined ? score.coalition : '') + "</td>\n                <td id=\"masterPearl-score" + player.id + "\" class=\"score-number masterPearl-score\">" + ((score === null || score === void 0 ? void 0 : score.pearlMaster) !== undefined ? score.pearlMaster : '') + "</td>\n                <td class=\"score-number total\">" + ((score === null || score === void 0 ? void 0 : score.total) !== undefined ? score.total : '') + "</td>\n            </tr>", 'score-table-body');
        });
        this.addTooltipHtmlToClass('lords-score', _("The total of Influence Points from the Lords with the Coat of Arms tokens (the most influential Lord of each color in your Senate Chamber)."));
        this.addTooltipHtmlToClass('locations-score', _("The total of Influence Points from the Locations you control."));
        this.addTooltipHtmlToClass('coalition-score', _("The biggest area of adjacent Lords of the same color is identified and 3 points are scored for each Lord within it"));
        this.addTooltipHtmlToClass('masterPearl-score', _("The player who has the Pearl Master token gains a bonus of 5 Influence Points."));
        if (!document.getElementById('page-content').style.zoom) {
            // scale down 
            Array.from(document.getElementsByClassName('player-table-wrapper')).forEach(function (elem) { return elem.classList.add('scaled-down'); });
        }
    };
    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        log('Leaving state: ' + stateName);
        switch (stateName) {
            case 'lordStackSelection':
                this.onLeavingLordStackSelection();
                break;
            case 'lordSelection':
                this.onLeavingLordSelection();
                break;
            case 'lordSwap':
                this.onLeavingLordSwap();
                break;
            case 'locationStackSelection':
                this.onLeavingLocationStackSelection();
                break;
            case 'locationSelection':
                this.onLeavingLocationSelection();
                break;
        }
    };
    KingOfTokyo.prototype.onLeavingLordStackSelection = function () {
        this.lordsStacks.setSelectable(false, null);
    };
    KingOfTokyo.prototype.onLeavingLordSelection = function () {
        this.lordsStacks.setPick(this.lordsStacks.hasPickCards(), false);
    };
    KingOfTokyo.prototype.onLeavingLordSwap = function () {
        if (this.isCurrentPlayerActive()) {
            this.playersTables[this.player_id].setSelectableForSwap(false);
        }
        this.swapSpots = null;
    };
    KingOfTokyo.prototype.onLeavingLocationStackSelection = function () {
        this.locationsStacks.setSelectable(false);
    };
    KingOfTokyo.prototype.onLeavingLocationSelection = function () {
        this.locationsStacks.setSelectable(false);
    };
    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    KingOfTokyo.prototype.onUpdateActionButtons = function (stateName, args) {
        if (this.isCurrentPlayerActive()) {
            switch (stateName) {
                case 'lordSwap':
                    this.addActionButton('dontSwap_button', _("Don't swap"), 'onDontSwap');
                    break;
            }
        }
    };
    ///////////////////////////////////////////////////
    //// Utility methods
    ///////////////////////////////////////////////////
    KingOfTokyo.prototype.createViewPlayermatPopin = function () {
        var _this = this;
        dojo.place("<div id=\"popin_showPlayermat_container\" class=\"conspiracy_popin_container\">\n            <div id=\"popin_showPlayermat_underlay\" class=\"conspiracy_popin_underlay\"></div>\n                <div id=\"popin_showPlayermat_wrapper\" class=\"conspiracy_popin_wrapper\">\n                <div id=\"popin_showPlayermat\" class=\"conspiracy_popin\">\n                    <a id=\"popin_showPlayermat_close\" class=\"closeicon\"><i class=\"fa fa-times fa-2x\" aria-hidden=\"true\"></i></a>\n                    <a id=\"popin_showPlayermat_left\" class=\"left arrow\"></a>\n                    <a id=\"popin_showPlayermat_right\" class=\"right arrow\"></a>\n                                \n                    <div id=\"playermat-container-modal\" class=\"player-table-wrapper\" style=\"touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);\">\n                    </div>\n                </div>\n            </div>\n        </div>", $(document.body));
        dojo.connect($("popin_showPlayermat_close"), 'onclick', this, function () { return _this.closePopin(); });
        dojo.connect($("popin_showPlayermat_left"), 'onclick', this, function () { return _this.changePopinPlayer(-1); });
        dojo.connect($("popin_showPlayermat_right"), 'onclick', this, function () { return _this.changePopinPlayer(1); });
    };
    KingOfTokyo.prototype.movePlayerTableToPopin = function (playerId) {
        document.getElementById('playermat-container-modal').style.zoom = document.getElementById('page-content').style.zoom;
        this.playerInPopin = playerId;
        document.getElementById('popin_showPlayermat_container').style.display = 'block';
        document.getElementById('playermat-container-modal').appendChild(document.getElementById("player-table-mat-" + playerId));
    };
    KingOfTokyo.prototype.closePopin = function () {
        if (!this.playerInPopin) {
            return;
        }
        document.getElementById('popin_showPlayermat_container').style.display = 'none';
        document.getElementById("player-table-wrapper-" + this.playerInPopin).appendChild(document.getElementById("player-table-mat-" + this.playerInPopin));
        this.playerInPopin = null;
    };
    KingOfTokyo.prototype.changePopinPlayer = function (delta) {
        document.getElementById("player-table-wrapper-" + this.playerInPopin).appendChild(document.getElementById("player-table-mat-" + this.playerInPopin));
        var playerIds = this.gamedatas.playerorder.map(function (val) { return Number(val); });
        this.playerInPopin = playerIds[(playerIds.indexOf(this.playerInPopin) + delta) % playerIds.length];
        document.getElementById('playermat-container-modal').appendChild(document.getElementById("player-table-mat-" + this.playerInPopin));
    };
    KingOfTokyo.prototype.createPlayerPanels = function (gamedatas) {
        var _this = this;
        this.createViewPlayermatPopin();
        Object.values(gamedatas.players).forEach(function (player) {
            var playerId = Number(player.id);
            var playerTable = Object.values(gamedatas.playersTables[playerId]);
            // Lord & pearl counters
            dojo.place("<div class=\"counters\">\n                <div id=\"lord-counter-wrapper-" + player.id + "\" class=\"lord-counter\"></div>\n                <div id=\"pearl-counter-wrapper-" + player.id + "\" class=\"pearl-counter\">\n                    <div class=\"token pearl\"></div> \n                    <span id=\"pearl-counter-" + player.id + "\" class=\"left\"></span>\n                </div>\n            </div>", "player_board_" + player.id);
            _this.minimaps[playerId] = new Minimap(playerId, playerTable);
            var pearlCounter = new ebg.counter();
            pearlCounter.create("pearl-counter-" + player.id);
            pearlCounter.setValue(player.pearls);
            _this.pearlCounters[playerId] = pearlCounter;
            // keys counters
            dojo.place("<div class=\"counters\">\n                <div id=\"silver-key-counter-wrapper-" + player.id + "\" class=\"key-counter silver-key-counter\">\n                    <div id=\"silver-key-" + player.id + "\" class=\"token silver key\"></div> \n                    <span id=\"silver-key-counter-" + player.id + "\" class=\"left\"></span>\n                </div>\n                <div id=\"gold-key-counter-wrapper-" + player.id + "\" class=\"key-counter gold-key-counter\">\n                    <div id=\"gold-key-" + player.id + "\"  class=\"token gold key\"></div> \n                    <span id=\"gold-key-counter-" + player.id + "\" class=\"left\"></span>\n                </div>\n            </div>", "player_board_" + player.id);
            var lastLocationSpotIndex = playerTable.map(function (spot, spotIndex) { return spot.location ? spotIndex : -1; }).reduce(function (a, b) { return a > b ? a : b; }, -1);
            var silverKeyAvailable = playerTable.filter(function (spot, spotIndex) { var _a; return spotIndex > lastLocationSpotIndex && ((_a = spot.lord) === null || _a === void 0 ? void 0 : _a.key) === 1; }).length > 0;
            dojo.toggleClass("silver-key-counter-wrapper-" + player.id, 'available', silverKeyAvailable);
            var silverKeyCounter = new ebg.counter();
            silverKeyCounter.create("silver-key-counter-" + player.id);
            silverKeyCounter.setValue(playerTable.filter(function (spot) { var _a; return ((_a = spot.lord) === null || _a === void 0 ? void 0 : _a.key) === 1; }).length);
            _this.silverKeyCounters[playerId] = silverKeyCounter;
            var goldKeyAvailable = playerTable.filter(function (spot, spotIndex) { var _a; return spotIndex > lastLocationSpotIndex && ((_a = spot.lord) === null || _a === void 0 ? void 0 : _a.key) === 2; }).length > 0;
            dojo.toggleClass("gold-key-counter-wrapper-" + player.id, 'available', goldKeyAvailable);
            var goldKeyCounter = new ebg.counter();
            goldKeyCounter.create("gold-key-counter-" + player.id);
            goldKeyCounter.setValue(playerTable.filter(function (spot) { var _a; return ((_a = spot.lord) === null || _a === void 0 ? void 0 : _a.key) === 2; }).length);
            _this.goldKeyCounters[playerId] = goldKeyCounter;
            // top lord tokens
            var html = "<div class=\"top-lord-tokens\">";
            GUILD_IDS.forEach(function (guild) { return html += "<div class=\"token guild" + guild + " token-guild" + guild + "\" id=\"top-lord-token-" + guild + "-" + player.id + "\"></div>"; });
            html += "</div>";
            dojo.place(html, "player_board_" + player.id);
            // pearl master token
            dojo.place("<div id=\"player_board_" + player.id + "_pearlMasterWrapper\" class=\"pearlMasterWrapper\"></div>", "player_board_" + player.id);
            if (gamedatas.pearlMasterPlayer === playerId) {
                _this.placePearlMasterToken(gamedatas.pearlMasterPlayer);
            }
            // vision popup button
            /*if (playerId !== Number((this as any).player_id)) {*/
            dojo.place("<div id=\"show-playermat-" + player.id + "\" class=\"show-playermat-button\">\n                    <svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 85.333343 145.79321\">\n                    <path fill=\"currentColor\" d=\"M 1.6,144.19321 C 0.72,143.31321 0,141.90343 0,141.06039 0,140.21734 5.019,125.35234 11.15333,108.02704 L 22.30665,76.526514 14.626511,68.826524 C 8.70498,62.889705 6.45637,59.468243 4.80652,53.884537 0.057,37.810464 3.28288,23.775161 14.266011,12.727735 23.2699,3.6711383 31.24961,0.09115725 42.633001,0.00129225 c 15.633879,-0.123414 29.7242,8.60107205 36.66277,22.70098475 8.00349,16.263927 4.02641,36.419057 -9.54327,48.363567 l -6.09937,5.36888 10.8401,30.526466 c 5.96206,16.78955 10.84011,32.03102 10.84011,33.86992 0,1.8389 -0.94908,3.70766 -2.10905,4.15278 -1.15998,0.44513 -19.63998,0.80932 -41.06667,0.80932 -28.52259,0 -39.386191,-0.42858 -40.557621,-1.6 z M 58.000011,54.483815 c 3.66666,-1.775301 9.06666,-5.706124 11.99999,-8.735161 l 5.33334,-5.507342 -6.66667,-6.09345 C 59.791321,26.035633 53.218971,23.191944 43.2618,23.15582 33.50202,23.12041 24.44122,27.164681 16.83985,34.94919 c -4.926849,5.045548 -5.023849,5.323672 -2.956989,8.478106 3.741259,5.709878 15.032709,12.667218 24.11715,14.860013 4.67992,1.129637 13.130429,-0.477436 20,-3.803494 z m -22.33337,-2.130758 c -2.8907,-1.683676 -6.3333,-8.148479 -6.3333,-11.893186 0,-11.58942 14.57544,-17.629692 22.76923,-9.435897 8.41012,8.410121 2.7035,22.821681 -9,22.728685 -2.80641,-0.0223 -6.15258,-0.652121 -7.43593,-1.399602 z m 14.6667,-6.075289 c 3.72801,-4.100734 3.78941,-7.121364 0.23656,-11.638085 -2.025061,-2.574448 -3.9845,-3.513145 -7.33333,-3.513145 -10.93129,0 -13.70837,13.126529 -3.90323,18.44946 3.50764,1.904196 7.30574,0.765377 11,-3.29823 z m -11.36999,0.106494 c -3.74071,-2.620092 -4.07008,-7.297494 -0.44716,-6.350078 3.2022,0.837394 4.87543,-1.760912 2.76868,-4.29939 -1.34051,-1.615208 -1.02878,-1.94159 1.85447,-1.94159 4.67573,0 8.31873,5.36324 6.2582,9.213366 -1.21644,2.27295 -5.30653,5.453301 -7.0132,5.453301 -0.25171,0 -1.79115,-0.934022 -3.42099,-2.075605 z\"></path>\n                    </svg>\n                </div>", "player_board_" + player.id);
            dojo.connect($("show-playermat-" + player.id), 'onclick', _this, function () { return _this.movePlayerTableToPopin(Number(player.id)); });
            /*}*/
            _this.setNewScore({
                playerId: playerId,
                newScore: player.newScore
            });
        });
        this.addTooltipHtmlToClass('lord-counter', _("Number of lords in player table"));
        this.addTooltipHtmlToClass('pearl-counter', _("Number of pearls"));
        this.addTooltipHtmlToClass('silver-key-counter', _("Number of silver keys (highlighted if a silver key is available)"));
        this.addTooltipHtmlToClass('gold-key-counter', _("Number of gold keys (highlighted if a gold key is available)"));
        GUILD_IDS.forEach(function (guild) { return _this.addTooltipHtmlToClass("token-guild" + guild, _("The Coat of Arms token indicates the most influential Lord of each color.")); });
    };
    KingOfTokyo.prototype.updateKeysForPlayer = function (playerId) {
        var playerTable = this.playersTables[playerId];
        var lastLocationSpotIndex = playerTable.spotsStock.map(function (spotStock, spotIndex) { return spotStock.hasLocation() ? spotIndex : -1; }).reduce(function (a, b) { return a > b ? a : b; }, -1);
        var silverKeyAvailable = playerTable.spotsStock.filter(function (spotStock, spotIndex) { var _a; return spotIndex > lastLocationSpotIndex && ((_a = spotStock.getLord()) === null || _a === void 0 ? void 0 : _a.key) === 1; }).length > 0;
        dojo.toggleClass("silver-key-counter-wrapper-" + playerId, 'available', silverKeyAvailable);
        var totalSilverKeyCounter = playerTable.spotsStock.filter(function (spotStock) { var _a; return ((_a = spotStock.getLord()) === null || _a === void 0 ? void 0 : _a.key) === 1; }).length;
        this.silverKeyCounters[playerId].toValue(totalSilverKeyCounter);
        var goldKeyAvailable = playerTable.spotsStock.filter(function (spotStock, spotIndex) { var _a; return spotIndex > lastLocationSpotIndex && ((_a = spotStock.getLord()) === null || _a === void 0 ? void 0 : _a.key) === 2; }).length > 0;
        dojo.toggleClass("gold-key-counter-wrapper-" + playerId, 'available', goldKeyAvailable);
        var totalGoldKeyCounter = playerTable.spotsStock.filter(function (spotStock) { var _a; return ((_a = spotStock.getLord()) === null || _a === void 0 ? void 0 : _a.key) === 2; }).length;
        this.goldKeyCounters[playerId].toValue(totalGoldKeyCounter);
    };
    KingOfTokyo.prototype.createPlayerTables = function (gamedatas) {
        var _this = this;
        var currentPlayer = Object.values(gamedatas.players).find(function (player) { return Number(player.id) === Number(_this.player_id); });
        if (currentPlayer) {
            this.createPlayerTable(gamedatas, Number(currentPlayer.id));
        }
        Object.values(gamedatas.players).filter(function (player) { return Number(player.id) !== Number(_this.player_id); }).forEach(function (player) {
            return _this.createPlayerTable(gamedatas, Number(player.id));
        });
    };
    KingOfTokyo.prototype.createPlayerTable = function (gamedatas, playerId) {
        this.playersTables[playerId] = new PlayerTable(this, gamedatas.players[playerId], gamedatas.playersTables[playerId]);
    };
    KingOfTokyo.prototype.lordPick = function (id) {
        if (!this.checkAction('addLord')) {
            return;
        }
        this.takeAction('pickLord', {
            id: id
        });
    };
    KingOfTokyo.prototype.lordStockPick = function (guild) {
        if (!this.checkAction('chooseVisibleStack')) {
            return;
        }
        this.takeAction('chooseVisibleStack', {
            guild: guild
        });
    };
    KingOfTokyo.prototype.locationPick = function (id) {
        if (!this.checkAction('addLocation')) {
            return;
        }
        this.takeAction('pickLocation', {
            id: id
        });
    };
    KingOfTokyo.prototype.takeAction = function (action, data) {
        data = data || {};
        data.lock = true;
        this.ajaxcall("/conspiracy/conspiracy/" + action + ".html", data, this, function () { });
    };
    KingOfTokyo.prototype.placePearlMasterToken = function (playerId) {
        var pearlMasterToken = document.getElementById('pearlMasterToken');
        if (pearlMasterToken) {
            slideToObjectAndAttach(this, pearlMasterToken, "player_board_" + playerId + "_pearlMasterWrapper");
        }
        else {
            dojo.place('<div id="pearlMasterToken" class="token"></div>', "player_board_" + playerId + "_pearlMasterWrapper");
            this.addTooltipHtml('pearlMasterToken', _("Pearl Master token. At the end of the game, the player possessing the Pearl Master token gains a bonus of 5 Influence Points."));
        }
    };
    KingOfTokyo.prototype.setCanSwap = function (swapSpots) {
        if (this.swapSpots.length !== 2 && swapSpots.length === 2) {
            this.addActionButton('swap_button', _("Swap"), 'onSwap');
        }
        else if (this.swapSpots.length === 2 && swapSpots.length !== 2) {
            dojo.destroy('swap_button');
        }
        this.swapSpots = swapSpots.slice();
    };
    KingOfTokyo.prototype.onSwap = function () {
        if (!this.checkAction('next')) {
            return;
        }
        this.takeAction('swap', { spots: this.swapSpots.join(',') });
    };
    KingOfTokyo.prototype.onDontSwap = function () {
        /*if(!(this as any).checkAction('next')) {
            return;
        }*/
        this.takeAction('dontSwap');
    };
    KingOfTokyo.prototype.setScore = function (playerId, column, score) {
        var cell = document.getElementById("score" + playerId).getElementsByTagName('td')[column];
        cell.innerHTML = "" + score;
        cell.classList.add('highlight');
    };
    KingOfTokyo.prototype.addHelp = function () {
        var _this = this;
        dojo.place("<button id=\"conspiracy-help-button\">?</button>", 'left-side');
        dojo.connect($('conspiracy-help-button'), 'onclick', this, function () { return _this.showHelp(); });
    };
    KingOfTokyo.prototype.showHelp = function () {
        if (!this.helpDialog) {
            this.helpDialog = new ebg.popindialog();
            this.helpDialog.create('conspiracyHelpDialog');
            this.helpDialog.setTitle(_("Cards help"));
            var html = "<div id=\"help-popin\">\n                <h1>" + _("Lords") + "</h1>\n                <div id=\"help-lords\" class=\"help-section\">\n                    <table>";
            LORDS_IDS.forEach(function (number) { return html += "<tr><td><div id=\"lord" + number + "\" class=\"lord\"></div></td><td>" + getLordTooltip(number * 10) + "</td></tr>"; });
            html += "</table>\n                </div>\n                <h1>" + _("Locations") + "</h1>\n                <div id=\"help-locations\" class=\"help-section\">\n                    <table>";
            LOCATIONS_UNIQUE_IDS.forEach(function (number) { return html += "<tr><td><div id=\"location" + number + "\" class=\"location\"></div></td><td>" + getLocationTooltip(number * 10) + "</td></tr>"; });
            LOCATIONS_GUILDS_IDS.forEach(function (number) { return html += "<tr><td><div id=\"location" + number + "\" class=\"location\"></div></td><td>" + getLocationTooltip(number * 10) + "</td></tr>"; });
            html += "</table>\n                </div>\n            </div>";
            // Show the dialog
            this.helpDialog.setContent(html);
        }
        this.helpDialog.show();
    };
    KingOfTokyo.prototype.setNewScoreTooltip = function (playerId) {
        var score = this.gamedatas.players[playerId].newScore;
        var html = "\n            " + _("Lords points") + " : <strong>" + score.lords + "</strong><br>\n            " + _("Locations points") + " : <strong>" + score.locations + "</strong><br>\n            " + _("Coalition points") + " : <strong>" + score.coalition + "</strong><br>\n            " + _("Pearl Master points") + " : <strong>" + score.pearlMaster + "</strong><br>\n        ";
        this.addTooltipHtml("player_score_" + playerId, html);
        this.addTooltipHtml("icon_point_" + playerId, html);
    };
    KingOfTokyo.prototype.setNewScore = function (args) {
        var _a;
        var score = args.newScore;
        this.gamedatas.players[args.playerId].newScore = score;
        if (!isNaN(score.total)) {
            (_a = this.scoreCtrl[args.playerId]) === null || _a === void 0 ? void 0 : _a.toValue(score.total);
        }
        this.setNewScoreTooltip(args.playerId);
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
            ['lordPlayed', ANIMATION_MS],
            ['lordSwapped', ANIMATION_MS],
            ['extraLordRevealed', ANIMATION_MS],
            ['locationPlayed', ANIMATION_MS],
            ['discardLords', ANIMATION_MS],
            ['discardLocations', ANIMATION_MS],
            ['newPearlMaster', 1],
            ['discardLordPick', 1],
            ['discardLocationPick', 1],
            ['lastTurn', 1],
            ['scoreLords', SCORE_MS],
            ['scoreLocations', SCORE_MS],
            ['scoreCoalition', SCORE_MS],
            ['scorePearlMaster', SCORE_MS],
            ['scoreTotal', SCORE_MS],
        ];
        notifs.forEach(function (notif) {
            dojo.subscribe(notif[0], _this, "notif_" + notif[0]);
            _this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
    };
    KingOfTokyo.prototype.notif_lordPlayed = function (notif) {
        var from = this.lordsStacks.getStockContaining("" + notif.args.lord.id);
        this.playersTables[notif.args.playerId].addLord(notif.args.spot, notif.args.lord, from);
        this.minimaps[notif.args.playerId].addLord(notif.args.spot, notif.args.lord);
        this.setNewScore(notif.args);
        this.pearlCounters[notif.args.playerId].incValue(notif.args.pearls);
        if (notif.args.stackSelection || !notif.args.discardedLords.length) {
            this.lordsStacks.discardPick(notif.args.discardedLords);
            this.lordsStacks.setPick(false, false);
        }
        if (notif.args.lord.key) {
            this.updateKeysForPlayer(notif.args.playerId);
        }
    };
    KingOfTokyo.prototype.notif_lordSwapped = function (notif) {
        this.playersTables[notif.args.playerId].lordSwapped(notif.args);
        this.minimaps[notif.args.playerId].lordSwapped(notif.args);
        this.setNewScore(notif.args);
    };
    KingOfTokyo.prototype.notif_extraLordRevealed = function (notif) {
        this.lordsStacks.addLords([notif.args.lord]);
    };
    KingOfTokyo.prototype.notif_locationPlayed = function (notif) {
        var _a;
        var from = this.locationsStacks.getStockContaining("" + notif.args.location.id);
        this.playersTables[notif.args.playerId].addLocation(notif.args.spot, notif.args.location, from);
        this.setNewScore(notif.args);
        this.pearlCounters[notif.args.playerId].incValue(notif.args.pearls);
        if ((_a = notif.args.discardedLocations) === null || _a === void 0 ? void 0 : _a.length) {
            this.locationsStacks.discardPick(notif.args.discardedLocations);
        }
        this.locationsStacks.setPick(false, false);
        this.updateKeysForPlayer(notif.args.playerId);
    };
    KingOfTokyo.prototype.notif_discardLords = function () {
        this.lordsStacks.discardVisible();
    };
    KingOfTokyo.prototype.notif_discardLordPick = function (notif) {
        // log('notif_discardLordPick', notif.args);
        this.lordsStacks.discardPick(notif.args.discardedLords);
        this.lordsStacks.setPick(false, false);
    };
    KingOfTokyo.prototype.notif_discardLocationPick = function (notif) {
        // log('notif_discardLordPick', notif.args);
        this.locationsStacks.discardPick(notif.args.discardedLocations);
        this.locationsStacks.setPick(false, false);
    };
    KingOfTokyo.prototype.notif_discardLocations = function () {
        this.locationsStacks.discardVisible();
    };
    KingOfTokyo.prototype.notif_newPearlMaster = function (notif) {
        var _a;
        this.placePearlMasterToken(notif.args.playerId);
        this.scoreCtrl[notif.args.playerId].incValue(5);
        this.gamedatas.players[notif.args.playerId].newScore.pearlMaster = 5;
        this.setNewScoreTooltip(notif.args.playerId);
        (_a = this.scoreCtrl[notif.args.previousPlayerId]) === null || _a === void 0 ? void 0 : _a.incValue(-5);
        if (this.gamedatas.players[notif.args.previousPlayerId]) {
            this.gamedatas.players[notif.args.previousPlayerId].newScore.pearlMaster = 0;
            this.setNewScoreTooltip(notif.args.previousPlayerId);
        }
    };
    KingOfTokyo.prototype.notif_lastTurn = function () {
        dojo.place("<div id=\"last-round\">\n            " + _("This is the last round of the game!") + "\n        </div>", 'page-title');
    };
    KingOfTokyo.prototype.notif_scoreLords = function (notif) {
        log('notif_scoreLords', notif.args);
        this.setScore(notif.args.playerId, 1, notif.args.points);
        this.scoreCtrl[notif.args.playerId].incValue(notif.args.points);
        this.playersTables[notif.args.playerId].highlightTopLords();
    };
    KingOfTokyo.prototype.notif_scoreLocations = function (notif) {
        log('notif_scoreLocations', notif.args);
        this.setScore(notif.args.playerId, 2, notif.args.points);
        this.scoreCtrl[notif.args.playerId].incValue(notif.args.points);
        this.playersTables[notif.args.playerId].highlightLocations();
    };
    KingOfTokyo.prototype.notif_scoreCoalition = function (notif) {
        log('notif_scoreCoalition', notif.args);
        this.setScore(notif.args.playerId, 3, notif.args.points);
        this.scoreCtrl[notif.args.playerId].incValue(notif.args.points);
        this.playersTables[notif.args.playerId].highlightCoalition(notif.args.coalition);
    };
    KingOfTokyo.prototype.notif_scorePearlMaster = function (notif) {
        var _this = this;
        log('notif_scorePearlMaster', notif.args);
        Object.keys(this.gamedatas.players).forEach(function (playerId) {
            var isPearlMaster = notif.args.playerId == Number(playerId);
            _this.setScore(playerId, 4, isPearlMaster ? 5 : 0);
            if (isPearlMaster) {
                _this.scoreCtrl[notif.args.playerId].incValue(5);
            }
        });
        document.getElementById('pearlMasterToken').classList.add('highlight');
    };
    KingOfTokyo.prototype.notif_scoreTotal = function (notif) {
        log('notif_scoreTotal', notif.args);
        this.setScore(notif.args.playerId, 5, notif.args.points);
    };
    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    KingOfTokyo.prototype.format_string_recursive = function (log, args) {
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                if (args.guild !== undefined && args.guild_name !== undefined && args.guild_name[0] !== '<') {
                    args.guild_name = "<span class='log-guild-name' style='color: " + LOG_GUILD_COLOR[args.guild] + "'>" + _(args.guild_name) + "</span>";
                }
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
