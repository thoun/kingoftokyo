
const WICKEDNESS_MONSTER_ICON_POSITION = [
    [2, 270],
    [32, 317],
    [84, 312],
    [124, 280],
    [103, 235],
    [82, 191],
    [124, 164],
    [83, 130],
    [41, 96],
    [84, 58],
    [124, 33],
];

class TableCenter {
    private visibleCards: Stock;
    private curseCard: Stock;
    private pickCard: Stock;
    private wickednessTiles: WickednessTile[][] = [];
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

            document.getElementById(`table-cards`).dataset.wickednessBoard = 'true';

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
        } else {
            document.getElementById('table-curse-cards').style.display = 'none';
        }
    }

    public createVisibleCards(visibleCards: Card[], topDeckCardBackType: string) {
        this.visibleCards = new ebg.stock() as Stock;
        this.visibleCards.setSelectionAppearance('class');
        this.visibleCards.selectionClass = 'no-visible-selection-except-double-selection';
        this.visibleCards.create(this.game, $('visible-cards'), CARD_WIDTH, CARD_HEIGHT);
        this.visibleCards.setSelectionMode(0);
        this.visibleCards.onItemCreate = (card_div, card_type_id) => this.game.cards.setupNewCard(card_div, card_type_id); 
        this.visibleCards.image_items_per_row = 10;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, (_, item_id: string) => this.game.onVisibleCardClick(this.visibleCards, Number(item_id)));

        this.game.cards.setupCards([this.visibleCards]);
        this.setVisibleCards(visibleCards);

        this.setTopDeckCardBackType(topDeckCardBackType);
    }

    public createCurseCard(curseCard: Card) {
        dojo.place(`<div id="curse-wrapper">
            <div id="curse-deck"></div>
            <div id="curse-card"></div>
        </div>`, 'table-curse-cards');

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

    public setVisibleCardsSelectionClass(visible: boolean) {
        document.getElementById('table-center').classList.toggle('double-selection', visible);
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
            dojo.connect(this.pickCard, 'onChangeSelection', this, (_, item_id: string) => this.game.onVisibleCardClick(this.pickCard, Number(item_id)));
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
    
    public removeOtherCardsFromPick(cardId: number) {        
        const removeFromPickIds = this.pickCard?.items.map(item => Number(item.id));
        removeFromPickIds?.forEach(id => {
            if (id !== cardId) {
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

            dojo.place(`<div id="wickedness-tiles-pile-${level}" class="wickedness-tiles-pile wickedness-tile-stock"></div>`, 'wickedness-board');
            this.setWickednessTilesPile(level);
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
    
    public showWickednessTiles(level: number) {
        WICKEDNESS_LEVELS.filter(l => l !== level).forEach(l => dojo.removeClass(`wickedness-tiles-pile-${l}`, 'opened'));
        dojo.addClass(`wickedness-tiles-pile-${level}`, 'opened');
    }
    
    public setWickednessTilesSelectable(level: number, show: boolean, selectable: boolean) {
        if (show) {
            this.showWickednessTiles(level);
        } else {
            WICKEDNESS_LEVELS.forEach(level => dojo.removeClass(`wickedness-tiles-pile-${level}`, 'opened'));
        }

        if (selectable) {
            dojo.addClass(`wickedness-tiles-pile-${level}`, 'selectable');
        } else {
            WICKEDNESS_LEVELS.forEach(level => {
                dojo.removeClass(`wickedness-tiles-pile-${level}`, 'selectable');
            });
        }
    }

    public setWickednessTilesPile(level: number) {
        const pileDiv = document.getElementById(`wickedness-tiles-pile-${level}`);
        pileDiv.innerHTML = '';
        this.wickednessTiles[level].forEach((tile, index) => {
            dojo.place(
                `<div id="wickedness-tiles-pile-tile-${tile.id}" class="stockitem wickedness-tile" data-side="${tile.side}" data-background-index="${wickenessTilesIndex[(tile.type % 100) - 1]}"></div>`, 
                pileDiv
            );
            const tileDiv = document.getElementById(`wickedness-tiles-pile-tile-${tile.id}`) as HTMLDivElement;
            this.game.wickednessTiles.setDivAsCard(tileDiv, tile.type);
            (this.game as any).addTooltipHtml(tileDiv.id, this.game.wickednessTiles.getTooltip(tile.type));
            tileDiv.style.setProperty('--order', ''+index);
            tileDiv.addEventListener('click', () => {
                if (tileDiv.closest('.wickedness-tiles-pile').classList.contains('selectable')) {
                    this.game.takeWickednessTile(tile.id);
                }
            });
        });
        pileDiv.style.setProperty('--tile-count', ''+this.wickednessTiles[level].length);
    }

    public removeWickednessTileFromPile(level: number, removedTile: WickednessTile) {
        this.wickednessTiles[level].splice(this.wickednessTiles[level].findIndex(tile => tile.id == removedTile.id), 1);
        this.setWickednessTilesPile(level);
        dojo.removeClass(`wickedness-tiles-pile-${level}`, 'opened')
    }
}