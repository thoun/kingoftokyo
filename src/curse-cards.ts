class CurseCards {
    constructor (private game: KingOfTokyoGame) {}
    
    public setupCards(stocks: Stock[]) {
        stocks.forEach(stock => {
            const anubiscardsurl = `${g_gamethemeurl}img/anubis-cards.jpg`;
            for (let i=1; i<=24; i++) {
                stock.addItemType(i, i, anubiscardsurl, 2);
            }
        });
    }

    public getCardName(cardTypeId: number): string {
        switch (cardTypeId) {
            case 1: return _("Pharaonic Ego");
            case 2: return _("Isis's Disgrace");
            case 3: return _("Thot's Blindness");
            case 4: return _("Tutankhamun's Curse");
            case 5: return _("Buried in Sand");
            case 6: return _("Raging Flood");
            case 7: return _("Hotep's Peace");
            case 8: return _("Set's Storm");
            case 9: return _("Builders' Uprising");
            case 10: return _("Inadequate offering");
            case 11: return _("Bow Before Ra");
            case 12: return _("Vengeance of Horus");
            case 13: return _("Ordeal of the Mighty");
            case 14: return _("Ordeal of the Wealthy");
            case 15: return _("Ordeal of the Spiritual");
            case 16: return _("Resurrection of Osiris");
            case 17: return _("Forbidden Library");
            case 18: return _("Confused Senses");
            case 19: return _("Pharaonic Skin");
            case 20: return _("Khepri's Rebellion");
            case 21: return _("Body, Spirit and Ka");
            case 22: return _("False Blessing");
            case 23: return _("Gaze of the Sphinx");
            case 24: return _("Scribe's Perserverance");
        }
        return null;
    }

    private getPermanentEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            case 1: return _("Monsters cannot Yield Tokyo.");
            case 2: return _("Monsters without the Golden Scarab cannot gain [Heart].");
            case 3: return _("Monsters without the Golden Scarab cannot gain [Energy].");
            case 4: return _("Monsters without the Golden Scarab cannot gain [Star].");
            case 5: return _("Monsters have 1 less Roll (minimum 1 Roll).");
            case 6: return _("Monsters roll 1 less die.");
            case 7: return _("Monsters without the Golden Scarab cannot use [diceSmash].");
            case 8: return _("At the start of your turn, lose 1[Heart].");
            case 9: return _("At the start of your turn, lose 2[Star].");
            case 10: return _("Cards cost 2 extra [Energy].");  
            case 11: return _("Monsters’ maximum [Heart] is 8[Heart] (Monsters that have more than 8[Heart] go down to 8[Heart])." );
            case 12: return _("Monsters cannot reroll [diceSmash].");
            case 13: return _("At the start of each turn, the Monster(s) with the most [Heart] lose 1[Heart].");
            case 14: return _("At the start of each turn, the Monster(s) with the most [Star] lose 1[Star].");
            case 15: return _("At the start of each turn, the Monster(s) with the most [Energy] lose 1[Energy].");
            case 16: return _("Monsters outside of Tokyo cannot use [diceHeart]. Monsters in Tokyo can use their [diceHeart].");
            case 17: return _("Monsters without the Golden Scarab cannot buy Power cards.");
            case 18: return _("After resolving the die of Fate, the Monster with the Golden Scarab can force you to reroll up to 2 dice of his choice.");
            case 19: return _("The Monster with the Golden Scarab cannot lose [Heart].");
            case 20: return _("At the start of each turn, the Monster with the Golden Scarab must give 1[Heart]/[Energy]/[Star] to the Monster whose turn it is.");
            case 21: return _("Only [diceSmash], [diceHeart] and [diceEnergy] faces can be used.");
            case 22: return _("Monsters roll 2 extra dice and have 1 extra die Roll. After resolving their dice, they lose 1[Heart] for each different face they rolled.");
            case 23: return _("[Keep] cards have no effect."); // TODOPU "[Keep] cards and Permanent Evolution cards have no effect."
            case 24: return _("You cannot reroll your [dice1].");
        }
        return null;
    }

    private getAnkhEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            case 1: return _("Yield Tokyo. You can’t enter Tokyo this turn.");
            case 2: case 3: case 4: case 7: case 17: case 18: case 19: return _("Take the Golden Scarab."); 
            case 5: return _("You have 1 extra die Roll.");
            case 6: return _("Take an extra die and put it on the face of your choice.");
            case 8: case 11: case 13: return "+2[Heart]";
            case 9: return _("If you are not in Tokyo, play an extra turn after this one without the die of Fate.");
            case 10: return _("Draw a Power card.");
            case 12: return _("Gain 1[Star] for each [diceSmash] you rolled.");
            case 14: return "+2[Star]";
            case 15: return "+2[Energy]";
            case 16: return _("Take control of Tokyo.");
            case 20: return _("Take the Golden Scarab and give it to the Monster of your choice.");
            case 21: return _("Cancel the Curse effect.");
            case 22: return _("Choose up to 2 dice, you can reroll or discard each of these dice.");
            case 23: return "+3[Energy]."; // TODOPU "Draw an Evolution card or gain 3[Energy]."          
            case 24: return _("Gain 1[Energy] for each [dice1] you rolled.");
        }
        return null;
    }

    private getSnakeEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            case 1: return _("Take control of Tokyo.");
            case 2: case 8: return "-1[Heart]";
            case 3: return "-2[Energy]";
            case 4: case 9: return "-2[Star]";
            case 5: return _("You cannot use your [diceSmash].");
            case 6: return _("Discard 1 die.");
            case 7: return _("Lose 1[Energy] for each [diceSmash] you rolled.");
            case 10: return _("Discard a [Keep] card.");
            case 11: return "-2[Heart]";
            case 12: return _("Lose 1[Heart] for each [diceSmash] you rolled.");
            case 13: return _("The Monster(s) with the most [Heart] lose 1[Heart].");
            case 14: return _("The Monster(s) with the most [Star] lose 1[Star].");
            case 15: return _("The Monster(s) with the most [Energy] lose 1[Energy].");
            case 16: return _("Yield Tokyo. You can’t enter Tokyo this turn.");
            case 17: return _("Discard a [Keep] card.");
            case 18: return _("The Monster with the Golden Scarab, instead of you, gains all [Heart] and [Energy] that you should have gained this turn.");
            case 19: return _("Give any combination of 2[Heart]/[Energy]/[Star] to the Monster with the Golden Scarab.");
            case 20: return _("Take the Golden Scarab.");
            case 21: return _("Cancel the Curse effect. [diceSmash], [diceHeart] and [diceEnergy] faces cannot be used.");
            case 22: return _("The player on your left chooses two of your dice. Reroll these dice.");
            case 23: return "-3[Energy]."; // TODOPU "Discard an Evolution card from your hand or in play or lose 3[Energy]."
            case 24: return _("Discard 1[dice1]");
        }
        return null;
    }

    public getTooltip(cardTypeId: number) {
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId)}</strong></p>
            <p><strong>${_("Permanent effect")} :</strong> ${formatTextIcons(this.getPermanentEffect(cardTypeId))}</p>
            <p><strong>${_("Ankh effect")} :</strong> ${formatTextIcons(this.getAnkhEffect(cardTypeId))}</p>
            <p><strong>${_("Snake effect")} :</strong> ${formatTextIcons(this.getSnakeEffect(cardTypeId))}</p>
        </div>`;
        return tooltip;
    }

    public setupNewCard(cardDiv: HTMLDivElement, cardType: number) {
        this.setDivAsCard(cardDiv, cardType); 
        (this.game as any).addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    }

    public setDivAsCard(cardDiv: HTMLDivElement, cardType: number) {
        const permanentEffect = formatTextIcons(this.getPermanentEffect(cardType));
        const ankhEffect = formatTextIcons(this.getAnkhEffect(cardType));
        const snakeEffect = formatTextIcons(this.getSnakeEffect(cardType));

        cardDiv.innerHTML = `
        <div class="name-wrapper">
            <div class="outline curse">${this.getCardName(cardType)}</div>
            <div class="text">${this.getCardName(cardType)}</div>
        </div>
        
        <div class="effect-wrapper permanent-effect-wrapper"><div class="effect-text">${permanentEffect}</div></div>
        <div class="effect-wrapper ankh-effect-wrapper"><div class="effect-text">${ankhEffect}</div></div>
        <div class="effect-wrapper snake-effect-wrapper"><div class="effect-text">${snakeEffect}</div></div>`;

        (Array.from(cardDiv.getElementsByClassName('effect-wrapper')) as HTMLDivElement[]).forEach(wrapperDiv => {
            if (wrapperDiv.children[0].clientHeight > wrapperDiv.clientHeight) {
                wrapperDiv.style.fontSize = '6pt';
            }
        });

        ['permanent', 'ankh', 'snake'].forEach(effectType => {
            const effectWrapper = cardDiv.getElementsByClassName(`${effectType}-effect-wrapper`)[0] as HTMLDivElement;
            const effectText = effectWrapper.getElementsByClassName('effect-text')[0] as HTMLDivElement;
            if (effectText.clientHeight > effectWrapper.clientHeight) {
                effectText.classList.add('overflow', effectType);
            }
        });
    }
}