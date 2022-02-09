
const WICKEDNESS_MONSTER_ICON_POSITION = [ // TODOWI test with official image
    [2, 270],
    [35, 321],
    [87, 316],
    [129, 284],
    [107, 237],
    [86, 193],
    [128, 165],
    [86, 130],
    [44, 96],
    [88, 58],
    [128, 31],
];

class TableCenter {
    private visibleCards: Stock;
    private curseCard: Stock;
    private pickCard: Stock;
    private wickednessTiles: WickednessTile[][] = [];
    private wickednessTilesStocks: Stock[] = [];
    private tokyoTower: TokyoTower;
    private wickednessPoints = new Map<number, number>();

    constructor(private game: KingOfTokyoGame, players: KingOfTokyoPlayer[], visibleCards: Card[], topDeckCardBackType: string, wickednessTiles: WickednessTile[], tokyoTowerLevels: number[], curseCard: Card) {        
        this.createVisibleCards(visibleCards, topDeckCardBackType);

        if (game.isWickednessExpansion()) {
            dojo.place(`
            <div id="wickedness-board-wrapper">
                <div id="wickedness-board"></div>
            </div>`, 'full-board');
            this.createWickednessTiles(wickednessTiles);

            players.forEach(player => {
                dojo.place(`<div id="monster-icon-${player.id}" class="monster-icon monster${player.monster}" style="background-color: #${player.color};"></div>`, 'wickedness-board');
                this.wickednessPoints.set(Number(player.id), Number(player.wickedness));
            });
            this.moveWickednessPoints();
        }

        if (game.isKingkongExpansion()) {
            dojo.place(`<div id="tokyo-tower-0" class="tokyo-tower-wrapper"></div>`, 'full-board');
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
        </div>`, 'full-board', 'before');

        this.curseCard = new ebg.stock() as Stock;
        this.curseCard.setSelectionAppearance('class');
        this.curseCard.selectionClass = 'no-visible-selection';
        this.curseCard.create(this.game, $('curse-card'), CARD_WIDTH, CARD_HEIGHT);
        this.curseCard.setSelectionMode(0);
        this.curseCard.centerItems = true;
        this.curseCard.onItemCreate = (card_div, card_type_id) => this.game.curseCards.setupNewCard(card_div, card_type_id); 

        this.game.curseCards.setupCards([this.curseCard]);
        this.curseCard.addToStockWithId(curseCard.type, '' + curseCard.id);

        (this.game as any).addTooltipHtml(`curse-deck`, `
        <strong>${_("Curse card pile.")}</strong>
        <div> ${dojo.string.substitute(_("Discard the current Curse and reveal the next one by rolling ${changeCurseCard}."), {'changeCurseCard': '<div class="anubis-icon anubis-icon1"></div>'})}</div>
        `);
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
    
    private createWickednessTiles(wickednessTiles: WickednessTile[]) {
        WICKEDNESS_LEVELS.forEach(level => {
            this.wickednessTiles[level] = wickednessTiles.filter(tile => this.game.wickednessTiles.getCardLevel(tile.type) === level);

            let html = `<div id="wickedness-tiles-reduced-${level}" class="wickedness-tiles-reduced wickedness-tile-stock"></div>
            <div id="wickedness-tiles-expanded-${level}" class="wickedness-tiles-expanded">
                <div id="wickedness-tiles-expanded-${level}-close" class="close">✖</div>
                <div id="wickedness-tiles-expanded-${level}-stock" class="wickedness-tile-stock table-wickedness-tiles"></div>
            </div>`;
            dojo.place(html, 'wickedness-board');
            this.setReducedWickednessTiles(level);

            document.getElementById(`wickedness-tiles-reduced-${level}`).addEventListener('click', () => this.showWickednessTiles(level));

            this.wickednessTilesStocks[level] = new ebg.stock() as Stock;
            this.wickednessTilesStocks[level].setSelectionAppearance('class');
            this.wickednessTilesStocks[level].selectionClass = 'no-visible-selection';
            this.wickednessTilesStocks[level].create(this.game, $(`wickedness-tiles-expanded-${level}-stock`), WICKEDNESS_TILES_WIDTH, WICKEDNESS_TILES_HEIGHT);
            this.wickednessTilesStocks[level].setSelectionMode(0);
            this.wickednessTilesStocks[level].centerItems = true;
            this.wickednessTilesStocks[level].onItemCreate = (card_div, card_type_id) => this.game.wickednessTiles.setupNewCard(card_div, card_type_id); 
            dojo.connect(this.wickednessTilesStocks[level], 'onChangeSelection', this, (_, item_id: string) => this.game.takeWickednessTile(Number(item_id)));
    
            this.game.wickednessTiles.setupCards([this.wickednessTilesStocks[level]]);
            this.wickednessTiles[level].forEach(tile => this.wickednessTilesStocks[level].addToStockWithId(tile.type, '' + tile.id));

            document.getElementById(`wickedness-tiles-expanded-${level}`).addEventListener('click', () => dojo.removeClass(`wickedness-tiles-expanded-${level}`, 'visible'));
        });
    }

    private moveWickednessPoints() {
        this.wickednessPoints.forEach((wickedness, playerId) => {
            const markerDiv = document.getElementById(`monster-icon-${playerId}`);

            const position = WICKEDNESS_MONSTER_ICON_POSITION[wickedness];
    
            let topShift = 0;
            let leftShift = 0;
            this.wickednessPoints.forEach((iWickedness, iPlayerId) => {
                if (iWickedness === wickedness && iPlayerId < playerId) {
                    topShift += 5;
                    leftShift += 5;
                }
            });
    
            markerDiv.style.left = `${position[0] + leftShift}px`;
            markerDiv.style.top = `${position[1] + topShift}px`;
        });
    }
    
    public setWickedness(playerId: number, wickedness: number) {
        this.wickednessPoints.set(playerId, wickedness);
        this.moveWickednessPoints();
    }

    public getWickednessTilesStock(level: number): Stock {
        return this.wickednessTilesStocks[level];
    }
    
    public showWickednessTiles(level: number) {
        dojo.addClass(`wickedness-tiles-expanded-${level}`, 'visible');
    }
    
    public setWickednessTilesSelectable(level: number, show: boolean, selectable: boolean) {
        if (show) {
            this.showWickednessTiles(level);
        } else {
            WICKEDNESS_LEVELS.forEach(level => dojo.removeClass(`wickedness-tiles-expanded-${level}`, 'visible'));
        }

        if (selectable) {
            dojo.addClass(`wickedness-tiles-expanded-${level}`, 'selectable');
            this.wickednessTilesStocks[level].setSelectionMode(1);
        } else {
            WICKEDNESS_LEVELS.forEach(level => {
                this.wickednessTilesStocks[level].setSelectionMode(0);
                dojo.removeClass(`wickedness-tiles-expanded-${level}`, 'selectable');
            });
        }
    }

    public setReducedWickednessTiles(level: number) {
        document.getElementById(`wickedness-tiles-reduced-${level}`).innerHTML = '';
        this.wickednessTiles[level].forEach((tile, index) => {
            dojo.place(`<div id="wickedness-tiles-reduced-tile-${tile.id}" class="stockitem wickedness-tiles-reduced-tile" style="left: ${index*5}px; top: ${index*5}px;"></div>`, `wickedness-tiles-reduced-${level}`);
            this.game.wickednessTiles.setDivAsCard(document.getElementById(`wickedness-tiles-reduced-tile-${tile.id}`) as HTMLDivElement, tile.type);
        });
    }

    public removeReducedWickednessTile(level: number, removedTile: WickednessTile) {
        this.wickednessTiles[level].splice(this.wickednessTiles[level].findIndex(tile => tile.id == removedTile.id), 1);
        this.setReducedWickednessTiles(level);
    }
}