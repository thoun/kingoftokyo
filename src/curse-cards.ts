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
            // TODOAN
        }
        return null;
    }

    private getAnkhEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            // TODOAN
            case 2: 
            case 3:
            case 4:
            case 17:
            case 18:
            case 19:
                return "Take the Golden Scarab.";
            case 11: case 13: return "+2[Heart]";
            case 14: return "+2[Star]";
            case 15: return "+2[Energy]";
            defaut: return "TODO"; // TODO an
        }
        return null;
    }

    private getSnakeEffect(cardTypeId: number): string {
        switch (cardTypeId) {
            // TODOAN
            case 3: return "-2[Energy]";
            case 4: case 9: return "-2[Star]";
            case 20: return "Take the Golden Scarab.";
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