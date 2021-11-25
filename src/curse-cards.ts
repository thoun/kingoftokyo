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
            // TODOAN
            case 1: return "Pharaonic Ego";
            case 2: return "Isis's Disgrace";
            case 3: return "Thot's Blindness";
            case 4: return "Tutankhamun's Curse";
            case 5: return "Buried in Sand";
            case 6: return "Raging Flood";
            case 7: return "Hotep's Peace";
            case 8: return "Set's Storm";
            case 9: return "Builders' Uprising";
            case 10: return "Inadequate offering";
            case 11: return "Bow Before Ra";
            case 12: return "Vengeance of Horus";
            case 13: return "Ordeal of the Mighty";
            case 14: return "Ordeal of the Wealthy";
            case 15: return "Ordeal of the Spiritual";
            case 16: return "Resurrection of Osiris";
            case 17: return "Forbidden Library";
            case 18: return "Confused Senses";
            case 19: return "Pharaonic Skin";
            case 20: return "Khepri's Rebellion";
            case 21: return "Body, Spirit and Ka";
            case 22: return "False Blessing";
            case 23: return "Gaze of the Sphinx";
            case 24: return "Scribe's Perserverance";
        }
        return null;
    }

    private getPermanentEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            // TODOAN translate
            case 1: return "Monsters cannot Yield Tokyo/Manhattan."; // TODOAN keep manhattan ?
            case 2: return "Monsters without the Golden Scarab cannot gain [Heart].";
            case 3: return "Monsters without the Golden Scarab cannot gain [Energy].";
            case 4: return "Monsters without the Golden Scarab cannot gain [Star].";
            case 5: return "Monsters have 1 less Roll (minimum 1 Roll).";
            case 6: return "Monsters roll 1 less die.";
            // TODOAN case 7
            case 8: return "At the start of your turn, lose 1[Heart].";
            case 9: return "At the start of your turn, lose 2[Star].";
            case 10: return "Cards cost 2 extra [Energy].";   
            // TODOAN case 11 12   
            case 13: return "At the start of each turn, the Monster(s) with the most [Heart] lose 1[Heart].";
            case 14: return "At the start of each turn, the Monster(s) with the most [Star] lose 1[Star].";
            case 15: return "At the start of each turn, the Monster(s) with the most [Energy] lose 1[Energy].";
            case 16: return "Monsters outside of Tokyo/Manhattan cannot use [diceHeart]. Monsters in Tokyo/Manhattan can use their [diceHeart]."; // TODOAN keep manhattan ? TODOAN adapt front forbidden icon
            case 17: return "Monsters without the Golden Scarab cannot buy Power cards.";
            // TODOAN case 18 19 21 22
            case 23: return "[Keep] cards have no effect."; // TODOPU "[Keep] cards and Permanent Evolution cards have no effect."
            // TODOAN 24
            defaut: return "TODO"; // TODO an
        }
        return null;
    }

    private getAnkhEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            // TODOAN translate
            case 1: return "Yield Tokyo. You can’t enter Tokyo this turn.";
            case 2: case 3: case 4: case 17: case 18: case 19: return "Take the Golden Scarab."; 
            case 5: return "You have 1 extra die Roll.";
            // TODOAN case 6 7               
            case 8: case 11: case 13: return "+2[Heart]";
            // TODOAN case 10 12
            case 14: return "+2[Star]";
            case 15: return "+2[Energy]";
            // TODOAN case 16 17 18 19 21 22
            case 23: return "+3[Energy]."; // TODOPU "Draw an Evolution card or gain 3[Energy]."          
            // TODOAN 24
            defaut: return "TODO"; // TODO an
        }
        return null;
    }

    private getSnakeEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            // TODOAN translate
            case 1: return "Take control of Tokyo.";
            case 2: case 8: return "-1[Heart]";
            case 3: return "-2[Energy]";
            case 4: case 9: return "-2[Star]";
            // TODOAN case 5 6 7         
            // TODOAN case 10 11 12      
            case 13: return "The Monster(s) with the most [Heart] lose 1[Heart].";
            case 14: return "The Monster(s) with the most [Star] lose 1[Star].";
            case 15: return "The Monster(s) with the most [Energy] lose 1[Energy].";
            // TODOAN case 16 17 18 19
            case 20: return "Take the Golden Scarab.";
            // TODOAN case 21 22
            case 23: return "-3[Energy]."; // TODOPU "Discard an Evolution card from your hand or in play or lose 3[Energy]."         
            // TODOAN 24
            defaut: return "TODO"; // TODO an
        }
        return null;
    }

    private getTooltip(cardTypeId: number) {
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId)}</strong></p>
            <p>${/* TODOAN */ "Permanent effect"} : ${formatTextIcons(this.getPermanentEffect(cardTypeId))}</p>
            <p>${/* TODOAN */ "Ankh effect"} : ${formatTextIcons(this.getAnkhEffect(cardTypeId))}</p>
            <p>${/* TODOAN */ "Snake effect"} : ${formatTextIcons(this.getSnakeEffect(cardTypeId))}</p>
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
        
        <div class="effect-wrapper permanent-effect-wrapper"><div>${permanentEffect}</div></div>
        <div class="effect-wrapper ankh-effect-wrapper"><div>${ankhEffect}</div></div>
        <div class="effect-wrapper snake-effect-wrapper"><div>${snakeEffect}</div></div>`;
    }
}