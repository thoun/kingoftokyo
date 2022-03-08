const MONSTERS_WITH_POWER_UP_CARDS = [1,2,3,4,5,6];

class EvolutionCards {
    constructor (private game: KingOfTokyoGame) {}
    
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
        switch( cardTypeId ) {
            // Cyber Kitty
            case 37: return /*_TODOPU*/("[b67392]Mouse [ec008c]Hunter");
            case 38: return /*_TODOPU*/("[b67392]Meow [ec008c]Missle");
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
        switch( cardTypeId ) {
            // Cyber Kitty
            case 37: return /*_TODOPU*/("If you roll at least one [dice1], gain 1[Star].");
            case 38: return /*_TODOPU*/("If you roll at least one [dice1], add [diceSmash] to your roll.");
        }
        return null;
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

    private getTooltip(cardTypeId: number) {
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
        if (cardType < 100) {
            return /*_ TODOPU */('<strong>Temporary</strong> evolution');
        }
    }
}