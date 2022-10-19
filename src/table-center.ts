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

const WICKEDNESS_MONSTER_ICON_POSITION_DARK_EDITION = [
    [-28, 324],
    [24, 410],
    [-2, 370],
    [39, 328],
    [22, 284],
    [-5, 236],
    [38, 197],
    [1, 156],
    [32, 107],
    [1, 70],
    [37, 29],
];

class TableCenter {
    private visibleCards: Stock;
    private curseCard: CardStock<CurseCard>;
    private curseDeck: CardStock<CurseCard>;
    private pickCard: Stock;
    public wickednessDecks: WickednessDecks;
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

            if (!game.isDarkEdition()) {
                document.getElementById(`table-cards`).dataset.wickednessBoard = 'true';
            }

            players.forEach(player => {
                dojo.place(`<div id="monster-icon-${player.id}-wickedness" class="monster-icon monster${player.monster}" style="background-color: ${player.monster > 100 ? 'unset' : '#'+player.color};"></div>`, 'wickedness-board');
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
        this.visibleCards.onItemCreate = (card_div, card_type_id) => this.game.cardsManager.setupNewCard(card_div, card_type_id); 
        this.visibleCards.image_items_per_row = 10;
        this.visibleCards.centerItems = true;
        dojo.connect(this.visibleCards, 'onChangeSelection', this, (_, item_id: string) => this.game.onVisibleCardClick(this.visibleCards, Number(item_id)));

        this.game.cardsManager.setupCards([this.visibleCards]);
        this.setVisibleCards(visibleCards);

        this.setTopDeckCardBackType(topDeckCardBackType);
    }

    public createCurseCard(curseCard: Card) {
        dojo.place(`<div id="curse-wrapper">
            <div id="curse-deck"></div>
            <div id="curse-card"></div>
        </div>`, 'table-curse-cards');


        this.curseCard = new VisibleDeck<CurseCard>(this.game.curseCardsManager, document.getElementById('curse-card'));
        this.curseCard.addCard(curseCard);
        this.curseDeck = new HiddenDeck<CurseCard>(this.game.curseCardsManager, document.getElementById('curse-deck'));

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
            this.pickCard.onItemCreate = (card_div, card_type_id) => this.game.cardsManager.setupNewCard(card_div, card_type_id); 
            this.pickCard.image_items_per_row = 10;
            this.pickCard.centerItems = true;
            dojo.connect(this.pickCard, 'onChangeSelection', this, (_, item_id: string) => this.game.onVisibleCardClick(this.pickCard, Number(item_id)));
        } else {
            document.getElementById('pick-stock').style.display = 'block';
        }

        this.game.cardsManager.setupCards([this.pickCard]);
        this.game.cardsManager.addCardsToStock(this.pickCard, cards);
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
        this.game.cardsManager.addCardsToStock(this.visibleCards, cards, 'deck');
    }

    private setVisibleCards(cards: Card[]) {
        const newWeights = {};
        cards.forEach(card => newWeights[card.type] = card.location_arg);
        this.visibleCards.changeItemsWeight(newWeights);

        this.game.cardsManager.addCardsToStock(this.visibleCards, cards, 'deck');
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
        this.curseCard.addCard(card, { fromStock: this.curseDeck, originalSide: 'back' });
    }
    
    private createWickednessTiles(wickednessTiles: WickednessTile[]) {
        this.wickednessDecks = new WickednessDecks(this.game.wickednessTilesManager);
        this.wickednessDecks.onSelectionChange = (selection: WickednessTile[], lastChange: WickednessTile) => this.game.takeWickednessTile(lastChange.id);
        this.wickednessDecks.addCards(wickednessTiles);
    }

    private moveWickednessPoints() {
        this.wickednessPoints.forEach((wickedness, playerId) => {
            const markerDiv = document.getElementById(`monster-icon-${playerId}-wickedness`);
            markerDiv.dataset.wickedness = ''+wickedness;

            const positionArray = this.game.isDarkEdition() ? WICKEDNESS_MONSTER_ICON_POSITION_DARK_EDITION : WICKEDNESS_MONSTER_ICON_POSITION;
            const position = positionArray[wickedness];
    
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
    
    public showWickednessTiles(level: number | null) {
        WICKEDNESS_LEVELS.filter(l => l !== level).forEach(l => this.wickednessDecks.setOpened(l, false));
        if (level !== null) {
            this.wickednessDecks.setOpened(level, true);
        }
    }
    
    public setWickednessTilesSelectable(level: number, show: boolean, selectable: boolean) {
        this.showWickednessTiles(show ? level : null);
        this.wickednessDecks.setSelectableLevel(selectable ? level : null);
        console.log($('wickedness-tiles-pile-6').innerHTML);
    }

    public removeWickednessTileFromPile(level: number, removedTile: WickednessTile) {
        this.wickednessDecks.removeCard(removedTile);
        this.wickednessDecks.setOpened(level, false);
        this.wickednessDecks.setSelectableLevel(null);
    }
}