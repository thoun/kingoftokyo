const MONSTERS_WITH_POWER_UP_CARDS = [1,2,3,4,5,6,13];

class EvolutionCards {
    EVOLUTION_CARDS_TYPES: number[];
    AUTO_DISCARDED_EVOLUTIONS: number[];

    constructor (private game: KingOfTokyoGame) {
        this.EVOLUTION_CARDS_TYPES = (game as any).gamedatas.EVOLUTION_CARDS_TYPES;
        this.AUTO_DISCARDED_EVOLUTIONS = (game as any).gamedatas.AUTO_DISCARDED_EVOLUTIONS;
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
        switch( cardTypeId ) {
            // Space Penguin : blue 2384c6 grey 4c7c96
            case 17: return /*_TODOPU*/("[2384c6]Black [4c7c96]Diamond");
            // Alienoid : orange e39717 brown aa673d
            case 21: return /*_TODOPU*/("[e39717]Alien [aa673d]Scourge");
            // Cyber Kitty : soft b67392 strong ec008c
            case 31: return /*_TODOPU*/("[b67392]Nine [ec008c]Lives");
            case 37: return /*_TODOPU*/("[b67392]Mouse [ec008c]Hunter");
            case 38: return /*_TODOPU*/("[b67392]Meow [ec008c]Missle");
            // The King : dark a2550b light ca6c39
            case 44: return /*_TODOPU*/("[a2550b]Giant [ca6c39]Banana");
            case 47: return /*_TODOPU*/("[a2550b]I Am [ca6c39]the King!");
            // Gigazaur : dark 00a651 light bed62f
            case 52: return /*_TODOPU*/("[00a651]Radioactive [bed62f]Waste");
            case 53: return /*_TODOPU*/("[00a651]Primal [bed62f]Bellow");
            case 55: return /*_TODOPU*/("[00a651]Defender [bed62f]Of Tokyo");
            case 56: return /*_TODOPU*/("[00a651]Heat [bed62f]Vision");
            // Meka Dragon : gray a68d83 brown aa673d
            case 63: return /*_TODOPU*/("[a68d83]Programmed [aa673d]To Destroy");
            // Boogie Woogie : dark 6c5b55 light a68d83
            // Pumpkin Jack : dark de6428 light f7941d
            // Cthulhu
            // Anubis
            // King Kong
            // Cybertooth
            // Pandakaï : light 6d6e71 dark 231f20
            case 131: return /*_TODOPU*/("[6d6e71]Panda[231f20]Monium");
            case 134: return /*_TODOPU*/("[6d6e71]Bear [231f20]Necessities");
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
        switch( cardTypeId ) {
            // Space Penguin
            case 17: return /*_TODOPU*/("Gain 1 extra [Star] each time you take control of Tokyo or choose to stay in Tokyo when you could have Yielded.");
            // Alienoid
            case 21: return "+2[Star].";
            // Cyber Kitty
            case 31: return /*_TODOPU*/("If you reach 0[Heart] discard your cards (including your Evolutions), lose all your [Energy] and [Star], and leave Tokyo. Gain 9[Heart], 9[Star], and continue playing.");
            case 37: return /*_TODOPU*/("If you roll at least one [dice1], gain 1[Star].");
            case 38: return /*_TODOPU*/("If you roll at least one [dice1], add [diceSmash] to your roll.");
            // The King
            case 44: return "+2[Heart].";
            case 47: return /*_TODOPU*/("Gain 1 extra [Star] if you take control of Tokyo or start your turn in Tokyo.");
            // Gigazaur 
            case 52: return "+2[Energy] +1[Heart]";
            case 53: return /*_TODOPU*/("All other Monsters lose 2[Star].");
            case 55: return /*_TODOPU*/("If you start your turn in Tokyo, all other Monsters lose 1[Star].");
            case 56: return /*_TODOPU*/("Monsters that wound you with their [diceSmash] ???TODOPU CONFIRM??? lose 1[Star].");
            // Meka Dragon
            case 63: return /*_TODOPU*/("Gain 3[Star] and 2[Energy] each time a Monster's health reaches 0[Heart].");
            // Pandakaï
            case 131: return /*_TODOPU*/("Gain 6[Energy]. All other Monsters gain 3[Energy].");  
            case 134: return "-1[Star] +2[Energy] +2[Heart]";
 
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
        // TODOPU cards.filter(card => card.tokens > 0).forEach(card => this.placeTokensOnCard(stock, card));
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

    public removeAfterUseIfNecessary(sourceStock: Stock, card: EvolutionCard) {
        if (this.AUTO_DISCARDED_EVOLUTIONS.includes(card.type)) {
            setTimeout(() => {
                sourceStock.removeFromStockById(`${card.id}`);
                this.game.tableManager.tableHeightChange();
            }, 5000);
        }
    }
}