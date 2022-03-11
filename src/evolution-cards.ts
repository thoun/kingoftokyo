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
        switch( cardTypeId ) {
            // Space Penguin : blue 2384c6 grey 4c7c96
            case 14: return /*_TODOPU*/("[2384c6]Cold [4c7c96]Wave");
            case 16: return /*_TODOPU*/("[2384c6]Blizzard");
            case 17: return /*_TODOPU*/("[2384c6]Black [4c7c96]Diamond");
            // Alienoid : orange e39717 brown aa673d
            case 21: return /*_TODOPU*/("[e39717]Alien [aa673d]Scourge");
            case 23: return /*_TODOPU*/("[e39717]Anger [aa673d]Batteries");
            case 27: return /*_TODOPU*/("[e39717]Mothership [aa673d]Support");
            // Cyber Kitty : soft b67392 strong ec008c
            case 31: return /*_TODOPU*/("[b67392]Nine [ec008c]Lives");
            case 37: return /*_TODOPU*/("[b67392]Mouse [ec008c]Hunter");
            case 38: return /*_TODOPU*/("[b67392]Meow [ec008c]Missle");
            // The King : dark a2550b light ca6c39
            case 42: return /*_TODOPU*/("[a2550b]Simian [ca6c39]Scamper");
            case 44: return /*_TODOPU*/("[a2550b]Giant [ca6c39]Banana");
            case 47: return /*_TODOPU*/("[a2550b]I Am [ca6c39]the King!");
            case 48: return /*_TODOPU*/("[a2550b]Twas Beauty [ca6c39]Killed the Beast");
            // Gigazaur : dark 00a651 light bed62f
            case 52: return /*_TODOPU*/("[00a651]Radioactive [bed62f]Waste");
            case 53: return /*_TODOPU*/("[00a651]Primal [bed62f]Bellow");
            case 55: return /*_TODOPU*/("[00a651]Defender [bed62f]Of Tokyo");
            case 56: return /*_TODOPU*/("[00a651]Heat [bed62f]Vision");
            // Meka Dragon : gray a68d83 brown aa673d
            case 62: return /*_TODOPU*/("[a68d83]Destructive [aa673d]Analysis");
            case 63: return /*_TODOPU*/("[a68d83]Programmed [aa673d]To Destroy");
            // Boogie Woogie : dark 6c5b55 light a68d83
            // Pumpkin Jack : dark de6428 light f7941d
            // Cthulhu
            // Anubis
            // King Kong
            // Cybertooth
            // Pandakaï : light 6d6e71 dark 231f20
            case 131: return /*_TODOPU*/("[6d6e71]Panda[231f20]Monium");
            case 132: return /*_TODOPU*/("[6d6e71]Eats, Shoots [231f20]and Leaves");
            case 134: return /*_TODOPU*/("[6d6e71]Bear [231f20]Necessities");
            case 135: return /*_TODOPU*/("[6d6e71]Panda [231f20]Express");
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
            case 14: return /*_TODOPU*/("Until your next turn, other Monsters roll with 1 fewer die.");
            case 16: return /*_TODOPU*/("Play during your turn. Until the start of your next turn, Monsters only have a single Roll and cannot Yield Tokyo.");
            case 17: return /*_TODOPU*/("Gain 1 extra [Star] each time you take control of Tokyo or choose to stay in Tokyo when you could have Yielded.");
            // Alienoid
            case 21: return /*_TODOPU*/("Gain 2[Star].");
            case 23: return /*_TODOPU*/("Gain 1[Energy] for each [Heart] you lost this turn.");
            case 27: return /*_TODOPU*/("Une fois lors de chacun de vos tours, vous pouvez dépenser 1[Energy] pour gagner 1[Heart].");
            // Cyber Kitty
            case 31: return /*_TODOPU*/("If you reach 0[Heart] discard your cards (including your Evolutions), lose all your [Energy] and [Star], and leave Tokyo. Gain 9[Heart], 9[Star], and continue playing.");
            case 37: return /*_TODOPU*/("If you roll at least one [dice1], gain 1[Star].");
            case 38: return /*_TODOPU*/("If you roll at least one [dice1], add [diceSmash] to your roll.");
            // The King
            case 42: return /*_TODOPU*/("If you Yield Tokyo, do not lose [Heart]. You can’t lose [Heart] this turn.");
            case 44: return /*_TODOPU*/("Gain 2[Heart].");
            case 47: return /*_TODOPU*/("Gain 1 extra [Star] if you take control of Tokyo or start your turn in Tokyo.");
            case 48: return /*_TODOPU*/("Play when you enter Tokyo. Gain 1[Star] at the end of each Monster’s turn (including yours). Discard this card and lose all your [Star] if you leave Tokyo.");
            // Gigazaur 
            case 52: return /*_TODOPU*/("Gain 2[Energy] and 1[Heart].");
            case 53: return /*_TODOPU*/("All other Monsters lose 2[Star].");
            case 55: return /*_TODOPU*/("If you start your turn in Tokyo, all other Monsters lose 1[Star].");
            case 56: return /*_TODOPU*/("Monsters that wound you lose 1[Star].");
            // Meka Dragon
            case 62: return /*_TODOPU*/("Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 63: return /*_TODOPU*/("Gain 3[Star] and 2[Energy] each time another Monster reaches 0[Heart].");
            // Pandakaï
            case 131: return /*_TODOPU*/("Gain 6[Energy]. All other Monsters gain 3[Energy].");  
            //case 132: return /*_TODOPU*/("Play when you take control of Tokyo. Make all Monsters outside of Tokyo lose 2[Heart]. Gain 1[Energy], then leave Tokyo. No Monster takes your place.");
            case 134: return /*_TODOPU*/("Lose 1[Star], gain 2[Energy] and 2[Heart].");
            case 135: return /*_TODOPU*/("Each time you roll at least [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy], gain 2[Star] and take another turn.");
 
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
}