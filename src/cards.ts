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
            case 13: case 14: return 7;
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
            //case 119: return 6;
            //case 120: return 2;
        }
        return null;
    }

    private getCardName(cardTypeId: number) {
        switch( cardTypeId ) {
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
            case 13: case 14: return _("<strong>You get 1 extra die.</strong>");
            case 15: return _("<strong>Your neighbors lose 1[heart]</strong> when you roll at least one [diceSmash].");
            case 16: return _("On a turn where you score [dice1][dice1][dice1], <strong>you can take another turn</strong> with one less die.");
            case 17: return _("When you gain any [Energy] <strong>gain 1 extra [Energy].</strong>");
            case 18: return _("<strong>You have one extra die Roll</strong> each turn.");
            case 19: return _("When you roll [dice1][dice1][dice1] or more <strong>gain 2 extra [Star].</strong>");
            case 20: return _("<strong>You can use your [diceHeart] to make other Monsters gain [Heart].</strong> Each Monster must pay you 2[Energy] (or 1[Energy] if it's their last one) for each [Heart] they gain this way");
            case 21: return _("<strong>Gain 1[Star]</strong> at the end of your turn if you don't make anyone lose [Heart].");
            case 22: return _("You can <strong>change one of your dice to a [dice1]</strong> each turn.");
            case 23: return _("If you reach 0[Heart] discard all your cards and lose all your [Star]. <strong>Gain 10[Heart] and continueplaying outside Tokyo.</strong>");
            case 24: return _("<strong>You don't lose [Heart]<strong> if you decide to Yield Tokyo.");
            case 25: return _("During the Buy Power cards step, you can <strong>peek at the top card of the deck and buy it</strong> or put it back on top of the deck.");
            case 26: return _("At the end of your turn you can <strong>discard any [keep] cards you have to gain their full cost in [Energy].</strong>");
            case 27: return _("<strong>Choose a [keep] card any monster has in play</strong> and put a Mimic token on it. <strong>This card counts as a duplicate of that card as if you had just bought it.</strong> Spend 1[Energy] at the start of your turn to move the Mimic token and change the card you are mimicking.");
            case 28: return dojo.string.substitute(_("When you buy <i>${card_name}</i>, put 6[Energy] on it from the bank. At the start of your turn <strong>take 2[Energy] off and add them to your pool.</strong> When there are no [Energy] left discard this card."), {'card_name': this.getCardName(cardTypeId)});
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
            //case 120: return _("(+ 1[Star] and suffer one damage) for each card you have.");
        }
        return null;
    }

    private formatDescription(rawDescription: string) {
        return rawDescription
            .replace(/\[Star\]/ig, '<span class="icon points"></span>')
            .replace(/\[Heart\]/ig, '<span class="icon health"></span>')
            .replace(/\[Energy\]/ig, '<span class="icon energy"></span>')
            // TODO [dice1][dice2][dice3][diceHeart][diceEnergy][diceSmash]
            .replace(/\[keep\]/ig, 'KEEP'); // TODO [keep]
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