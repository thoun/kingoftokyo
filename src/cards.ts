const CARD_WIDTH = 132;
const CARD_HEIGHT = 185;

class Cards {
    constructor (private game: KingOfTokyoGame) {}
    
    public setupCards(stocks: Stock[]) {

        stocks.forEach(stock => {
            const keepcardsurl = `${g_gamethemeurl}img/cards0.jpg`;
            for(let id=1; id<=57; id++) {  // keep
                stock.addItemType(id, id, keepcardsurl, id);
            }

            const discardcardsurl = `${g_gamethemeurl}img/discard-cards.jpg`;
            for(let id=101; id<=118; id++) {  // discard
                stock.addItemType(id, id, discardcardsurl, id - 101);
            }
        });
    }
    
    public getCardUniqueId(color: number, value: number) {
        return color * 100 + value;
    }
    
    public getCardWeight(color: number, value: number) {
        let displayedNumber = value;
        if (displayedNumber === 70 || displayedNumber === 90) {
            displayedNumber /= 10;
        }
        return displayedNumber * 100 + color;
    }

    private getCardCost(cardTypeId: number) {
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
            //case 119: return 6;
            //case 120: return 2;
        }
        return null;
    }

    private getCardName(cardTypeId: number) {
        switch( cardTypeId ) {
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
            case 13: case 14: return _("Extra Head");
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
            case 104: return _("Death From Above");
            case 105: return _("Energize");
            case 106: case 107: return _("Evacuation Orders");
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
    }

    private getCardDescription(cardTypeId: number) {
        switch( cardTypeId ) {
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
            case 13: case 14: return _("You get 1 extra die.");
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
            case 101: return _("<strong>+ 3[Star].</strong>");
            case 102: return _("<strong>+ 2[Star].</strong>");
            case 103: return _("<strong>+ 1[Star].</strong>");
            case 104: return _("<strong>+ 2[Star] and take control of Tokyo</strong> if you don't already control it.");
            case 105: return _("<strong>+ 9[Energy].</strong>");
            case 106: case 107: return _("<strong>All other Monsters lose 5[Star].</strong>");
            case 108: return _("<strong>All other Monsters lose 2[Heart].</strong>");
            case 109: return _("<strong>Take another turn</strong> after this one");
            case 110: return _("<strong>+ 2[Star] and deal all other monsters lose 3[Heart].</strong>");
            case 111: return _("<strong>+ 2[Heart]</strong>");
            case 112: return _("<strong>All Monsters</strong> (including you) <strong>lose 3[Heart].</strong>");
            case 113: return _("<strong>+ 5[Star] -4[Heart].</strong>");
            case 114: return _("<strong>+ 2[Star] -2[Heart].</strong>");
            case 115: return _("<strong>+ 2[Star] +3[Heart].</strong>");
            case 116: return _("<strong>+ 4[Star].");
            case 117: return _("<strong>+ 4[Star] +3[Heart].</strong>");
            case 118: return _("<strong>+ 2[Star] and all other Monsters lose 1[Energy] for every 2[Energy]</strong> they have.");
            //case 119: return _("<strong>+ 4[Star].");
            //case 120: return _("(+ 1[Star] and suffer one damage) for each card you have."); // TODO check spelling
        }
        return null;
    }

    private formatDescription(rawDescription: string) {
        return rawDescription
            .replace(/\[Star\]/ig, '<span class="icon points"></span>')
            .replace(/\[Heart\]/ig, '<span class="icon health"></span>')
            .replace(/\[Energy\]/ig, '<span class="icon energy"></span>');
            // TODO [1][2][3][H][E][S]
    }

    private getTooltip(cardTypeId: number) {
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId)}</strong></p>
            <p class="cost">${ dojo.string.substitute(_("Cost : ${cost}"), {'cost': this.getCardCost(cardTypeId)}) } <span class="icon energy"></span></p>
            <p>${this.formatDescription(this.getCardDescription(cardTypeId))}</p>
        </div>`;
        return tooltip;
    }

    public setupNewCard(card_div: HTMLDivElement, card_type_id: number) {
        const type = card_type_id < 100 ? _('Keep') : _('Discard');
        const name = this.getCardName(card_type_id);
        const description = this.formatDescription(this.getCardDescription(card_type_id));
        card_div.innerHTML = `<div class="bottom"></div>
        <div class="name-wrapper">
            <div class="outline">${name}</div>
            <div class="text">${name}</div>
        </div>
        <div class="type-wrapper ${ card_type_id < 100 ? 'keep' : 'discard'}">
            <div class="outline">${type}</div>
            <div class="text">${type}</div>
        </div>
        
        <div class="description-wrapper"><div>${description}</div></div>
        `;
        
        (this.game as any).addTooltipHtml( card_div.id, this.getTooltip(card_type_id));
    }
}