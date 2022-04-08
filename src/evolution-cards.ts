const MONSTERS_WITH_POWER_UP_CARDS = [1,2,3,4,5,6,13];

class EvolutionCards {
    EVOLUTION_CARDS_TYPES: number[];

    constructor (private game: KingOfTokyoGame) {
        this.EVOLUTION_CARDS_TYPES = (game as any).gamedatas.EVOLUTION_CARDS_TYPES;
    }
    
    public setupCards(stocks: Stock[]) {

        stocks.forEach(stock => {
            const keepcardsurl = `${g_gamethemeurl}img/evolution-cards.jpg`;
            stock.addItemType(0, 0, keepcardsurl, 0);
            MONSTERS_WITH_POWER_UP_CARDS.forEach((monster, index) => {  // keep
                for (let i=1; i <=8; i++) {
                    const uniqueId = monster * 10 + i;
                    stock.addItemType(uniqueId, uniqueId, keepcardsurl, index + 1);
                }
            });
            
        });
    }

    private getColoredCardName(cardTypeId: number): string {
        switch (cardTypeId) {
            // Space Penguin : blue 2384c6 grey 4c7c96
            case 11: return /*_TODOPU*/("[2384c6]Freeze [4c7c96]Ray");
            case 12: return /*_TODOPU*/("[2384c6]Miraculous [4c7c96]Catch");
            case 13: return /*_TODOPU*/("[2384c6]Deep [4c7c96]Dive");
            case 14: return /*_TODOPU*/("[2384c6]Cold [4c7c96]Wave");
            case 15: return /*_TODOPU*/("[2384c6]Encased [4c7c96]in Ice");
            case 16: return /*_TODOPU*/("[2384c6]Blizzard");
            case 17: return /*_TODOPU*/("[2384c6]Black [4c7c96]Diamond");
            case 18: return /*_TODOPU*/("[2384c6]Icy [4c7c96]Reflection");
            // Alienoid : orange e39717 brown aa673d
            case 21: return /*_TODOPU*/("[e39717]Alien [aa673d]Scourge");
            case 22: return /*_TODOPU*/("[e39717]Precision [aa673d]Field Support");
            case 23: return /*_TODOPU*/("[e39717]Anger [aa673d]Batteries");
            case 24: return /*_TODOPU*/("[e39717]Adapting [aa673d]Technology");
            case 25: return /*_TODOPU*/("[e39717]Funny Looking [aa673d]But Dangerous");
            case 26: return /*_TODOPU*/("[e39717]Exotic [aa673d]Arms");
            case 27: return /*_TODOPU*/("[e39717]Mothership [aa673d]Support");
            case 28: return /*_TODOPU*/("[e39717]Superior Alien [aa673d]Technology");
            // Cyber Kitty : soft b67392 strong ec008c
            case 31: return /*_TODOPU*/("[b67392]Nine [ec008c]Lives");
            case 32: return /*_TODOPU*/("[b67392]Mega [ec008c]Purr");
            case 33: return /*_TODOPU*/("[b67392]Electro- [ec008c]Scratch");
            case 34: return /*_TODOPU*/("[b67392]Cat [ec008c]Nip");
            case 35: return /*_TODOPU*/("[b67392]Play with your [ec008c]Food");
            case 36: return /*_TODOPU*/("[b67392]Feline [ec008c]Motor");
            case 37: return /*_TODOPU*/("[b67392]Mouse [ec008c]Hunter");
            case 38: return /*_TODOPU*/("[b67392]Meow [ec008c]Missle");
            // The King : dark a2550b light ca6c39
            case 41: return /*_TODOPU*/("[a2550b]Monkey [ca6c39]Rush")
            case 42: return /*_TODOPU*/("[a2550b]Simian [ca6c39]Scamper");
            case 43: return /*_TODOPU*/("[a2550b]Jungle [ca6c39]Frenzy")
            case 44: return /*_TODOPU*/("[a2550b]Giant [ca6c39]Banana");
            case 45: return /*_TODOPU*/("[a2550b]Chest [ca6c39]Thumping");
            case 46: return /*_TODOPU*/("[a2550b]Alpha [ca6c39]Male");
            case 47: return /*_TODOPU*/("[a2550b]I Am [ca6c39]the King!");
            case 48: return /*_TODOPU*/("[a2550b]Twas Beauty [ca6c39]Killed the Beast");
            // Gigazaur : dark 00a651 light bed62f
            case 51: return /*_TODOPU*/("[00a651]Detachable [bed62f]Tail");
            case 52: return /*_TODOPU*/("[00a651]Radioactive [bed62f]Waste");
            case 53: return /*_TODOPU*/("[00a651]Primal [bed62f]Bellow");
            case 54: return /*_TODOPU*/("[00a651]Saurian [bed62f]Adaptability");
            case 55: return /*_TODOPU*/("[00a651]Defender [bed62f]Of Tokyo");
            case 56: return /*_TODOPU*/("[00a651]Heat [bed62f]Vision");
            case 57: return /*_TODOPU*/("[00a651]Gamma [bed62f]Breath");
            case 58: return /*_TODOPU*/("[00a651]Tail [bed62f]Sweep");
            // Meka Dragon : gray a68d83 brown aa673d
            case 61: return /*_TODOPU*/("[a68d83]Mecha [aa673d]Blast");
            case 62: return /*_TODOPU*/("[a68d83]Destructive [aa673d]Analysis");
            case 63: return /*_TODOPU*/("[a68d83]Programmed [aa673d]To Destroy");
            case 64: return /*_TODOPU*/("[a68d83]Tune [aa673d]-Up");
            case 65: return /*_TODOPU*/("[a68d83]Breath [aa673d]of Doom");
            case 66: return /*_TODOPU*/("[a68d83]Lightning [aa673d]Armor");
            case 67: return /*_TODOPU*/("[a68d83]Claws [aa673d]of Steel");
            case 68: return /*_TODOPU*/("[a68d83]Target [aa673d]Acquired");
            // Boogie Woogie : dark 6c5b55 light a68d83
            // Pumpkin Jack : dark de6428 light f7941d
            // Cthulhu
            // Anubis
            // King Kong
            // Cybertooth
            // Pandakaï : light 6d6e71 dark 231f20
            case 131: return /*_TODOPU*/("[6d6e71]Panda[231f20]Monium");
            case 132: return /*_TODOPU*/("[6d6e71]Eats, Shoots [231f20]and Leaves");
            case 133: return /*_TODOPU*/("[6d6e71]Bam[231f20]Boozle");
            case 134: return /*_TODOPU*/("[6d6e71]Bear [231f20]Necessities");
            case 135: return /*_TODOPU*/("[6d6e71]Panda [231f20]Express");
            case 136: return /*_TODOPU*/("[6d6e71]Bamboo [231f20]Supply");
            case 137: return /*_TODOPU*/("[6d6e71]Pandarwinism [231f20]Survival of the Cutest");
            case 138: return /*_TODOPU*/("[6d6e71]Yin [231f20]& Yang");
            // cyberbunny : soft b67392 strong ec008c
            // kraken : blue 2384c6 gray 4c7c96
            // Baby Gigazaur : dark a5416f light f05a7d
        }
        return null;
    }

    public getCardName(cardTypeId: number, state: 'text-only' | 'span') {
        const coloredCardName = this.getColoredCardName(cardTypeId);
        if (state == 'text-only') {
            return coloredCardName?.replace(/\[(\w+)\]/g, '');
        } else if (state == 'span') {
            let first = true;
            return coloredCardName?.replace(/\[(\w+)\]/g, (index, color) => {
                let span = `<span style="-webkit-text-stroke-color: #${color};">`;
                if (first) {
                    first = false;
                } else {
                    span = `</span>` + span;
                }
                return span;
            }) + `${first ? '' : '</span>'}`;
        }
        return null;
    }

    private getCardDescription(cardTypeId: number) {
        switch (cardTypeId) {
            // Space Penguin
            case 11: return /*_TODOPU*/("When you wound a Monster in Tokyo, give them this card. At the start of their turn, choose a die face. That face has no effect this turn. Take this card back at the end of their turn.");
            case 12: return /*_TODOPU*/("Once per turn, during the Buy Power Cards phase, you can shuffle the discard pile and reveal one card randomly. You can buy this card for 1[Energy] less than the normal price or discard it. Put back the rest of the discard pile.");
            case 13: return /*_TODOPU*/("Look at the top 3 Power cards of the deck. Choose one and play it in front of you for free. Put the other Power cards on the bottom of the deck.");
            case 14: return /*_TODOPU*/("Until your next turn, other Monsters roll with 1 fewer die.");
            case 15: return /*_TODOPU*/("Spend 1[Energy] to choose one of the dice you rolled. This die is frozen until the beginning of your next turn: it cannot be changed and is used normally by Monsters during the Resolve Dice phase.");
            case 16: return /*_TODOPU*/("Play during your turn. Until the start of your next turn, Monsters only have a single Roll and cannot Yield Tokyo.");
            case 17: return /*_TODOPU*/("Gain 1 extra [Star] each time you take control of Tokyo or choose to stay in Tokyo when you could have Yielded.");
            case 18: return /*_TODOPU*/("Choose an Evolution Card in front of a Monster and put a [snowflakeToken] on it. Icy Reflection becomes a copy of that card as if you had played it. If the copied card is removed from play, discard Icy Reflection.");
            // Alienoid
            case 21: return /*_TODOPU*/("Gain 2[Star].");
            case 22: return /*_TODOPU*/("Draw Power cards from the top of the deck until you reveal a [keep] card that costs 4[Energy] or less. Play this card in front of you and discard the other cards you drew.");
            case 23: return /*_TODOPU*/("Gain 1[Energy] for each [Heart] you lost this turn.");
            case 24: return /*_TODOPU*/("Put 3 [alienoidToken] tokens on this card. On your turn, you can remove an [alienoidToken] token to discard the 3 face-up Power cards and reveal 3 new ones. Discard this card when there are no more tokens on it.");
            case 25: return /*_TODOPU*/("If you roll at least [dice2][dice2][dice2] each other Monster loses 1[Heart].");
            case 26: return /*_TODOPU*/("Before you roll, you can put 2[Energy] on this card. If you do, and roll at least [diceSmash][diceSmash][diceSmash], you can take back your two [Energy] and make the Monsters you wound lose 2 extra [Heart]. Otherwise you lose your 2[Energy] and lose 2[Heart].");
            case 27: return /*_TODOPU*/("Once during your turn, you can spend 1[Energy] to gain 1[Heart].");
            case 28: return /*_TODOPU*/("You can buy [keep] cards by paying half of their cost (rounding up). When you do so, place a [UfoToken] on it. At the start of you turn, roll a die for each of your [keep] cards with a [UfoToken]. Discard each [keep] card for which you rolled a [diceSmash]. You cannot have more than 3 [keep] cards with [UfoToken] at a time.");
            // Cyber Kitty
            case 31: return /*_TODOPU*/("If you reach 0[Heart] discard your cards (including your Evolutions), lose all your [Energy] and [Star], and leave Tokyo. Gain 9[Heart], 9[Star], and continue playing.");
            case 32: return /*_TODOPU*/("All other Monsters give you 1[Energy] or 1[Star] if they have any (they choose which to give you).");
            case 33: return /*_TODOPU*/("All other Monsters lose 1[Heart].");
            case 34: return /*_TODOPU*/("Play at the start of your turn. You only have one roll this turn. Double the result.");
            case 35: return /*_TODOPU*/("When a Monster in Tokyo must lose at least 2[Heart] from your [diceSmash], you can make them lose 2[Heart] fewer and steal 1[Star] and 1[Energy] from them instead.");
            case 36: return /*_TODOPU*/("During other Monsters' movement phases, if Tokyo is empty, you can take control of it instead of the Monster whose turn it is.");
            case 37: return /*_TODOPU*/("If you roll at least one [dice1], gain 1[Star].");
            case 38: return /*_TODOPU*/("If you roll at least one [dice1], add [diceSmash] to your roll.");
            // The King
            case 41: return /*_TODOPU*/("Play when a Monster who controls Tokyo leaves or is eliminated. Take control of Tokyo.");
            case 42: return /*_TODOPU*/("If you Yield Tokyo, do not lose [Heart]. You can’t lose [Heart] this turn.");
            case 43: return /*_TODOPU*/("Play at the end of your movement phase, if you wounded a Monster who controls Tokyo with [diceSmash] and you didn't take control of Tokyo. Take an extra turn.");
            case 44: return /*_TODOPU*/("Gain 2[Heart].");
            case 45: return /*_TODOPU*/("You can force Monsters you wound with your [diceSmash] to Yield Tokyo.");
            case 46: return /*_TODOPU*/("If you wound at least one Monster with your [diceSmash], gain 1[Star].");
            case 47: return /*_TODOPU*/("Gain 1 extra [Star] if you take control of Tokyo or start your turn in Tokyo.");
            case 48: return /*_TODOPU*/("Play when you enter Tokyo. Gain 1[Star] at the end of each Monster’s turn (including yours). Discard this card and lose all your [Star] if you leave Tokyo.");
            // Gigazaur 
            case 51: return /*_TODOPU*/("You can’t lose [Heart] this turn.");
            case 52: return /*_TODOPU*/("Gain 2[Energy] and 1[Heart].");
            case 53: return /*_TODOPU*/("All other Monsters lose 2[Star].");
            case 54: return /*_TODOPU*/("Choose a die face. Take all dice with this face and flip them to a (single) face of your choice.");
            case 55: return /*_TODOPU*/("If you start your turn in Tokyo, all other Monsters lose 1[Star].");
            case 56: return /*_TODOPU*/("Monsters that wound you lose 1[Star].");
            case 57: return /*_TODOPU*/("Once per turn, you can change one of the dice you rolled to [diceSmash].");
            case 58: return /*_TODOPU*/("Once per turn, you can change one of the dice you rolled to [dice1] or [dice2]."); // TODOPU check label
            // Meka Dragon
            case 61: return /*_TODOPU*/("Each Monster you make lose [Heart] with your [diceSmash] loses 2 extra [Heart].");
            case 62: return /*_TODOPU*/("Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 63: return /*_TODOPU*/("Gain 3[Star] and 2[Energy] each time another Monster reaches 0[Heart].");
            case 64: return /*_TODOPU*/("Play before rolling dice. If you are not in Tokyo, skip your turn, gain 4[Heart] and 2[Energy].");
            case 65: return /*_TODOPU*/("When you make Monsters in Tokyo lose at least 1[Heart], Monsters who aren't in Tokyo also lose 1[Heart] (except you).");
            case 67: return /*_TODOPU*/("On your turn, if you make another Monster lose at least 3[Heart], they lose 1 extra [Heart].");
            case 68: return /*_TODOPU*/("When a Monster makes you lose [Heart] with [diceSmash], you can give them the [targetToken] token. The Monster who has the [targetToken] token loses 1 extra [Heart] each time you make them lose [Heart].");
            // Pandakaï
            case 131: return /*_TODOPU*/("Gain 6[Energy]. All other Monsters gain 3[Energy].");  
            case 132: return /*_TODOPU*/("Play when you take control of Tokyo. Make all Monsters outside of Tokyo lose 2[Heart]. Gain 1[Energy], then leave Tokyo. No Monster takes your place.");
            case 133: return /*_TODOPU*/("Play when a player buys a Power card. They do not spend [Energy] and cannot buy that card this turn. Choose a different Power card they can afford to buy. They must purchase that card.");
            case 134: return /*_TODOPU*/("Lose 1[Star], gain 2[Energy] and 2[Heart].");
            case 135: return /*_TODOPU*/("Each time you roll at least [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy], gain 2[Star] and take another turn.");
            case 136: return /*_TODOPU*/("At the start of your turn, you can put 1[Energy] from the bank on this card OR take all of the [Energy] off this card.");
            case 137: return /*_TODOPU*/("If you roll at least [diceHeart][diceHeart][diceHeart], gain 1[Star]. Also gain 1[Star] for each extra [diceHeart] you roll.");
            case 138: return /*_TODOPU*/("Before resolving your dice, you can choose to flip all your dice to the opposite side.") + `<div>[dice1]↔[dice3] &nbsp; [dice2]↔[diceHeart] &nbsp; [diceSmash]↔[diceEnergy]</div>`;
        }
        return null;
    }

    private getDistance(p1: PlacedTokens, p2: PlacedTokens): number {
        return Math.sqrt((p1.x - p2.x) ** 2 + (p1.y - p2.y) ** 2);
    }

    public placeMimicOnCard(stock: Stock, card: Card) {
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);

        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        
        cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);

        let html = `<div id="${divId}-mimic-token" style="left: ${cardPlaced.mimicToken.x - 16}px; top: ${cardPlaced.mimicToken.y - 16}px;" class="card-token icy-reflection token"></div>`;
        dojo.place(html, divId);

        div.dataset.placed = JSON.stringify(cardPlaced);
    }

    public removeMimicOnCard(stock: Stock, card: Card) { 
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);

        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        cardPlaced.mimicToken = null;

        if (document.getElementById(`${divId}-mimic-token`)) {
            (this.game as any).fadeOutAndDestroy(`${divId}-mimic-token`);
        }

        div.dataset.placed = JSON.stringify(cardPlaced);
    }

    private getPlaceOnCard(cardPlaced: CardPlacedTokens): PlacedTokens {
        const newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 100 + 16,
        };
        let protection = 0;
        const otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(place => this.getDistance(newPlace, place) < 32)) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }

        return newPlace;
    }

    public placeTokensOnCard(stock: Stock, card: EvolutionCard, playerId?: number) {
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);
        if (!div) {
            return;
        }
        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        const placed: PlacedTokens[] = cardPlaced.tokens;

        const cardType = /* TODOPU card.mimicType ||*/ card.type;

        // remove tokens
        for (let i = card.tokens; i < placed.length; i++) {
            if (cardType === 136 && playerId) {
                (this.game as any).slideToObjectAndDestroy(`${divId}-token${i}`, `energy-counter-${playerId}`);
            } else {
                (this.game as any).fadeOutAndDestroy(`${divId}-token${i}`);
            }
        }
        placed.splice(card.tokens, placed.length - card.tokens);

        // add tokens
        for (let i = placed.length; i < card.tokens; i++) {
            const newPlace = this.getPlaceOnCard(cardPlaced);

            placed.push(newPlace);
            let html = `<div id="${divId}-token${i}" style="left: ${newPlace.x - 16}px; top: ${newPlace.y - 16}px;" class="card-token `;
            if (cardType === 24) {
                html += `ufo token`;
            } else if (cardType === 26 || cardType === 136) {
                html += `energy-cube`;
            }
            html += `"></div>`;
            dojo.place(html, divId);
        }

        div.dataset.placed = JSON.stringify(cardPlaced);
    }

    public setDivAsCard(cardDiv: HTMLDivElement, cardType: number) {
        const type = this.getCardTypeName(cardType);
        const description = formatTextIcons(this.getCardDescription(cardType));

        cardDiv.innerHTML = `
        <div class="name-wrapper">
            <div class="outline">${this.getCardName(cardType, 'span')}</div>
            <div class="text">${this.getCardName(cardType, 'text-only')}</div>
        </div>
        <div class="evolution-type">${type}</div>        
        <div class="description-wrapper">${description}</div>`;

        let textHeight = (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).clientHeight;

        if (textHeight > 80) { // TODOPU check limit
            (cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement).style.fontSize = '6pt';
        }
    }

    public getTooltip(cardTypeId: number) {
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId, 'text-only')}</strong></p>
            <p>${formatTextIcons(this.getCardDescription(cardTypeId))}</p>
        </div>`;
        return tooltip;
    }

    public setupNewCard(cardDiv: HTMLDivElement, cardType: number) {
        if (cardType == 0) {
            return;
        }

        this.setDivAsCard(cardDiv, cardType); 
        (this.game as any).addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    }

    private getCardTypeName(cardType: number) {
        const type = this.EVOLUTION_CARDS_TYPES[cardType];
        switch (type) {
            case 1: return /*_ TODOPU */('<strong>Permanent</strong> evolution');
            case 2: return /*_ TODOPU */('<strong>Temporary</strong> evolution');
            case 3: return /*_ TODOPU */('<strong>Gift</strong> evolution');
        }
        return null;
    }
    
    public addCardsToStock(stock: Stock, cards: EvolutionCard[], from?: string) {
        if (!cards.length) {
            return;
        }

        cards.forEach(card => {
            stock.addToStockWithId(card.type, `${card.id}`, from);
            //const cardDiv = document.getElementById(`${stock.container_div.id}_item_${card.id}`) as HTMLDivElement;
        });
        cards.filter(card => card.tokens > 0).forEach(card => this.placeTokensOnCard(stock, card));
    }

    public moveToAnotherStock(sourceStock: Stock, destinationStock: Stock, card: EvolutionCard) {
        if (sourceStock === destinationStock) {
            return;
        }
        
        const sourceStockItemId = `${sourceStock.container_div.id}_item_${card.id}`;
        if (document.getElementById(sourceStockItemId)) {     
            this.addCardsToStock(destinationStock, [card], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById(`${card.id}`);
        } else {
            console.warn(`${sourceStockItemId} not found in `, sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [card], sourceStock.container_div.id);
        }

        this.game.tableManager.tableHeightChange();
    }

    public getMimickedCardText(mimickedCard: Card): string {
        let mimickedCardText = '-';
        if (mimickedCard) {
            const tempDiv: HTMLDivElement = document.createElement('div');
            tempDiv.classList.add('stockitem');
            tempDiv.style.width = `${CARD_WIDTH}px`;
            tempDiv.style.height = `${CARD_WIDTH}px`;
            tempDiv.style.position = `relative`;
            tempDiv.style.backgroundImage = `url('${g_gamethemeurl}img/evolution-cards.jpg')`;
            const imagePosition = MONSTERS_WITH_POWER_UP_CARDS.indexOf(Math.floor(mimickedCard.type / 10)) + 1;
            const xBackgroundPercent = imagePosition * 100;
            tempDiv.style.backgroundPosition = `-${xBackgroundPercent}% 0%`;

            document.body.appendChild(tempDiv);
            this.setDivAsCard(tempDiv, mimickedCard.type + (mimickedCard.side || 0));
            document.body.removeChild(tempDiv);

            mimickedCardText = `<br>${tempDiv.outerHTML}`;
        }

        return mimickedCardText;
    }

    public changeMimicTooltip(mimicCardId: string, mimickedCardText: string) {
        (this.game as any).addTooltipHtml(mimicCardId, this.getTooltip(18) + `<br>${_('Mimicked card:')} ${mimickedCardText}`);
    }
}