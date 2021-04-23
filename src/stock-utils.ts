/*declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;

declare const board: HTMLDivElement;*/

const GUILD_IDS = [1,2,3,4,5];

const LORDS_IDS = [1,2,3,4,5,6];

const LOCATIONS_UNIQUE_IDS = [1,2,3,4,5,6,7,8,9,10,11,12,13,14];
const LOCATIONS_GUILDS_IDS = [100,101];

const LORD_WIDTH = 207.26;
const LORD_HEIGHT = 207;

const LOCATION_WIDTH = 186.24;
const LOCATION_HEIGHT = 124;
    
function getUniqueId(type: number, guild: number): number {
    return type * 10 + guild;
}

function setupLordCards(lordStocks: Stock[]) {
    const cardsurl = `${g_gamethemeurl}img/lords.jpg`;

    lordStocks.forEach(lordStock => 
        GUILD_IDS.forEach((guild, guildIndex) => 
            LORDS_IDS.forEach((lordType, index) =>
                lordStock.addItemType(
                    this.getUniqueId(lordType, guild), 
                    0, 
                    cardsurl, 
                    1 + guildIndex * LORDS_IDS.length + index
                )
            )
        )
    );
}

function setupLocationCards(locationStocks: Stock[]) {
    const cardsurl = `${g_gamethemeurl}img/locations.jpg`;

    locationStocks.forEach(locationStock => {

        LOCATIONS_UNIQUE_IDS.forEach((id, index) =>
            locationStock.addItemType(
                getUniqueId(id, 0), 
                0, 
                cardsurl, 
                1 + index
            )
        );

        GUILD_IDS.forEach((guild, guildIndex) => 
            LOCATIONS_GUILDS_IDS.forEach((id, index) =>
                locationStock.addItemType(
                    getUniqueId(id, guild), 
                    0, 
                    cardsurl, 
                    15 + GUILD_IDS.length * index + guildIndex
                )
            )
        );
    });
}

function getGuildName(guild: number) {
    let guildName = null;
    switch (guild) {
        case 1: guildName = _('Farmer'); break;
        case 2: guildName = _('Military'); break;
        case 3: guildName = _('Merchant'); break;
        case 4: guildName = _('Politician'); break;
        case 5: guildName = _('Mage'); break;
    }
    return guildName;
}

function getLocationTooltip(typeWithGuild: number) {
    const type = Math.floor(typeWithGuild / 10);
    const guild = typeWithGuild % 10;
    let message = null;
    switch (type) {
        case 1: message = _("At the end of the game, this Location is worth 7 IP."); break;
        case 2: message = _("Immediately gain 1 Pearl. At the end of the game, this Location is worth 5 IP."); break;
        case 3: message = _("Immediately gain 2 Pearls. At the end of the game, this Location is worth 4 IP."); break;
        case 4: message = _("Immediately gain 3 Pearls. At the end of the game, this Location is worth 3 IP."); break;
        case 5: message = _("At the end of the game, this Location is worth 1 IP per silver key held in your Senate Chamber, regardless of whether or not it has been used to take control of a Location."); break;
        case 6: message = _("At the end of the game, this Location is worth 2 IP per gold key held in your Senate Chamber, regardless of whether or not it has been used to take control of a Location."); break;
        case 7: message = _("At the end of the game, this Location is worth 1 IP per pair of Pearls in your possession."); break;
        case 8: message = _("At the end of the game, this Location is worth 2 IP per Location in your control."); break;
        case 9: message = _("Until your next turn, each opponent MUST only increase the size of their Senate Chamber by taking the first Lord from the deck. At the end of the game, this Location is worth 3 IP."); break;
        case 10: message = _("Until your next turn, each opponent MUST only increase the size of their Senate Chamber by taking first 2 Lords from the deck. Adding one to their Senate Chamber and discarding the other. At the end of the game, this Location is worth 3 IP."); break;
        case 11: message = _("Immediately replace all the discarded Lords in to the Lord deck and reshuffle. At the end of the game, this Location is worth 3 IP."); break;
        case 12: message = _("Immediately replace all the available Locations to the Location deck and reshuffle. At the end of the game, this Location is worth 3 IP."); break;
        case 13: message = _("Until the end of the game, to take control of a Location, only 2 keys are needed, irrespective of their type. At the end of the game, this Location is worth 3 IP."); break;
        case 14: message = _("Until the end of the game, when you take control of a Location, you choose this location from the Location deck (No longer from the available Locations). The deck is then reshuffled. At the end of the game, this Location is worth 3 IP."); break;

        case 100: message = guild ?
            dojo.string.substitute(_("At the end of the game, this Location is worth as many IP as your most influential ${guild_name} Lord."), { guild_name: getGuildName(guild) }) : 
            _("At the end of the game, this Location is worth as many IP as your most influential Lord of the indicated color."); break;
        case 101: message = guild ?
        dojo.string.substitute(_("At the end of the game, this Location is worth 1 IP + a bonus of 1 IP per ${guild_name} Lord present in your Senate Chamber."), { guild_name: getGuildName(guild) }) :
        _("At the end of the game, this Location is worth 1 IP + a bonus of 1 IP per Lord of the indicated color present in your Senate Chamber."); break;
    }
    return message;
}

function getLordTooltip(typeWithGuild: number) {
    const type = Math.floor(typeWithGuild / 10);
    let message = null;
    switch (type) {
        case 1: message = _("When this Lord is placed in the Senate Chamber, two Lords in this Chamber (including this one) can be swapped places, except those with keys."); break;
        case 2: message = _("This Lord gives you 1 silver key."); break;
        case 3: message = _("This Lord gives you 1 gold key."); break;
        case 4: message = _("This Lord gives you 2 Pearls."); break;
        case 5: message = _("This Lord gives you 1 Pearl."); break;
        case 6: message = _("When this Lord is placed in the Senate Chamber, the top Lord card is taken from the Lord deck and placed in the corresponding discard pile."); break;
    }
    return message;
}

function moveToAnotherStock(sourceStock: Stock, destinationStock: Stock, uniqueId: number, cardId: string) {
    if (sourceStock === destinationStock) {
        return;
    }
    
    const sourceStockItemId = `${sourceStock.container_div.id}_item_${cardId}`;
    if (document.getElementById(sourceStockItemId)) {        
        destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
        sourceStock.removeFromStockById(cardId);
    } else {
        console.warn(`${sourceStockItemId} not found in `, sourceStock);
        destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
    }
}