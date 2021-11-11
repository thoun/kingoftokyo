class TableCenter {
    private visibleCards: Stock;
    private curseCard: Stock;
    private pickCard: Stock;
    private tokyoTower: TokyoTower;

    constructor(private game: KingOfTokyoGame, visibleCards: Card[], topDeckCardBackType: string, tokyoTowerLevels: number[], curseCard: Card) {        
        this.createVisibleCards(visibleCards, topDeckCardBackType);

        if (game.isKingkongExpansion()) {
            dojo.place(`<div id="tokyo-tower-0" class="tokyo-tower-wrapper"></div>`, 'board');
            this.tokyoTower = new TokyoTower('tokyo-tower-0', tokyoTowerLevels);
        }

        if (game.isAnubisExpansion()) {
            this.createCurseCard(curseCard);
        }
    }

    public createVisibleCards(visibleCards: Card[], topDeckCardBackType: string) {
        this.visibleCards = new ebg.stock() as Stock;
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection';
        this.visibleCards.create(this.game, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = (card_div, card_type_id) => this.game.cards.setupNewCard(card_div, card_type_id); 
        this.visibleCards.image_items_per_row = 10;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, (_, item_id: string) => this.game.onVisibleCardClick(this.visibleCards, item_id));

        this.game.cards.setupCards([this.visibleCards]);
        this.setVisibleCards(visibleCards);

        this.setTopDeckCardBackType(topDeckCardBackType);
    }

    public createCurseCard(curseCard: Card) {
        dojo.place(`<div id="curse-wrapper">
            <div id="curse-deck"></div>
            <div id="curse-card"></div>
        </div>`, 'board', 'before');

        this.curseCard = new ebg.stock() as Stock;
        this.curseCard.setSelectionAppearance('class');
        this.curseCard.selectionClass = 'no-visible-selection';
        this.curseCard.create(this.game, $('curse-card'), CARD_WIDTH, CARD_HEIGHT);
        this.curseCard.setSelectionMode(0);
        this.curseCard.centerItems = true;
        this.curseCard.onItemCreate = (card_div, card_type_id) => this.game.curseCards.setupNewCard(card_div, card_type_id); 

        this.game.curseCards.setupCards([this.curseCard]);
        this.curseCard.addToStockWithId(curseCard.type, '' + curseCard.id);
    }

    public setVisibleCardsSelectionMode(mode: number) {
        this.visibleCards.setSelectionMode(mode);
    }
    
    public showPickStock(cards: Card[]) {
        if (!this.pickCard) { 
            dojo.place('<div id="pick-stock" class="card-stock"></div>', 'deck-wrapper');

            this.pickCard = new ebg.stock() as Stock;
            this.pickCard.setSelectionAppearance('class');
            this.pickCard.selectionClass = 'no-visible-selection';
            this.pickCard.create(this.game, $('pick-stock'), CARD_WIDTH, CARD_HEIGHT);
            this.pickCard.setSelectionMode(1);
            this.pickCard.onItemCreate = (card_div, card_type_id) => this.game.cards.setupNewCard(card_div, card_type_id); 
            this.pickCard.image_items_per_row = 10;
            this.pickCard.centerItems = true;
            dojo.connect(this.pickCard, 'onChangeSelection', this, (_, item_id: string) => this.game.onVisibleCardClick(this.pickCard, item_id));
        } else {
            document.getElementById('pick-stock').style.display = 'block';
        }

        this.game.cards.setupCards([this.pickCard]);
        this.game.cards.addCardsToStock(this.pickCard, cards);
    }

    public hidePickStock() {
        const div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
        }
    }
    
    public renewCards(cards: Card[], topDeckCardBackType: string) {
        this.visibleCards.removeAll();

        this.setVisibleCards(cards);

        this.setTopDeckCardBackType(topDeckCardBackType);
    }

    public setTopDeckCardBackType(topDeckCardBackType: string) {
        if (topDeckCardBackType !== undefined && topDeckCardBackType !== null) {
            document.getElementById('deck').dataset.type = topDeckCardBackType;
        }
    }
    
    public setInitialCards(cards: Card[]) {        
        this.game.cards.addCardsToStock(this.visibleCards, cards, 'deck');
    }

    private setVisibleCards(cards: Card[]) {
        const newWeights = {};
        cards.forEach(card => newWeights[card.type] = card.location_arg);
        this.visibleCards.changeItemsWeight(newWeights);

        this.game.cards.addCardsToStock(this.visibleCards, cards, 'deck');
    }
    
    public removeOtherCardsFromPick(cardId: string) {        
        const removeFromPickIds = this.pickCard?.items.map(item => Number(item.id));
        removeFromPickIds?.forEach(id => {
            if (id !== Number(cardId)) {
                this.pickCard.removeFromStockById(''+id);
            }
        });
    }

    public changeVisibleCardWeight(card: Card) {
        this.visibleCards.changeItemsWeight( { [card.type]: card.location_arg } );
    }

    public getVisibleCards(): Stock {
        return this.visibleCards;
    }
    
    public getPickCard(): Stock {
        return this.pickCard;
    }
    
    public getTokyoTower() {
        return this.tokyoTower;
    }
    
    public changeCurseCard(card: Card) {
        this.curseCard.removeAll();
        this.curseCard.addToStockWithId(card.type, '' + card.id, 'curse-deck');
    }
    
    public setWickedness(playerId: number, wickedness: number) {
        // TODOWI
    }
}