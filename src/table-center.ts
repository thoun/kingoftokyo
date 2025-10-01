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
    private deck: Deck<Card>;
    private visibleCards: SlotStock<Card>;
    private curseCard: Deck<CurseCard>;
    private curseDeck: Deck<CurseCard>;
    private pickCard: LineStock<Card>;
    public wickednessDecks: WickednessDecks;
    private tokyoTower: TokyoTower;
    private wickednessPoints = new Map<number, number>();

    constructor(
        private game: KingOfTokyoGame, 
        players: KingOfTokyoPlayer[],
        boardImgUrl: string,
        visibleCards: Card[], 
        topDeckCard: Card | null, 
        deckCardsCount: number, 
        wickednessTiles: WickednessTile[], 
        tokyoTowerLevels: number[], 
        curseCard: Card, 
        hiddenCurseCardCount: number, 
        visibleCurseCardCount: number, 
        topCurseDeckCard: Card | null
    ) {
        document.getElementById(`board`).style.backgroundImage = `url(${g_gamethemeurl}img/${boardImgUrl})`;

        this.createVisibleCards(visibleCards, topDeckCard, deckCardsCount);

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
            this.createCurseCard(curseCard,  hiddenCurseCardCount, visibleCurseCardCount, topCurseDeckCard);
        } else {
            document.getElementById('table-curse-cards').style.display = 'none';
        }
    }

    public createVisibleCards(visibleCards: Card[], topDeckCard: Card | null, deckCardsCount: number) {
        this.deck = new Deck<Card>(this.game.cardsManager, document.getElementById('deck'), {
            cardNumber: deckCardsCount,
            topCard: topDeckCard,
            shadowDirection: 'top-right',
        });

        this.visibleCards = new SlotStock<Card>(this.game.cardsManager, document.getElementById('visible-cards'), {
            slotsIds: [1, 2, 3],
            mapCardToSlot: (card: Card) => card.location_arg,
        });
        this.visibleCards.onCardClick = (card: Card) => this.game.onVisibleCardClick(this.visibleCards, card);

        this.setVisibleCards(visibleCards, true);
    }

    public createCurseCard(curseCard: Card, hiddenCurseCardCount: number, visibleCurseCardCount: number, topCurseDeckCard: Card | null) {
        dojo.place(`<div id="curse-wrapper">
            <div id="curse-deck"></div>
            <div id="curse-card"></div>
        </div>`, 'table-curse-cards');

        this.curseCard = new Deck<CurseCard>(this.game.curseCardsManager, document.getElementById('curse-card'), {
            cardNumber: visibleCurseCardCount,
            topCard: curseCard,
        });
        this.curseDeck = new Deck<CurseCard>(this.game.curseCardsManager, document.getElementById('curse-deck'), {
            cardNumber: hiddenCurseCardCount,
            topCard: topCurseDeckCard,
        });

        (this.game as any).addTooltipHtml(`curse-deck`, `
        <strong>${_("Curse card pile.")}</strong>
        <div> ${dojo.string.substitute(_("Discard the current Curse and reveal the next one by rolling ${changeCurseCard}."), {'changeCurseCard': '<div class="anubis-icon anubis-icon1"></div>'})}</div>
        `);
    }

    public setVisibleCardsSelectionMode(mode: CardSelectionMode) {
        this.visibleCards.setSelectionMode(mode);
    }

    public setVisibleCardsSelectionClass(visible: boolean) {
        document.getElementById('table-center').classList.toggle('double-selection', visible);
    }
    
    public showPickStock(cards: Card[]) {
        if (!this.pickCard) { 
            dojo.place('<div id="pick-stock" class="card-stock"></div>', 'deck-wrapper');

            this.pickCard = new LineStock<Card>(this.game.cardsManager, document.getElementById('pick-stock'));
            this.pickCard.setSelectionMode('single');
            this.pickCard.onCardClick = (card: Card) => this.game.onVisibleCardClick(this.pickCard, card);
        } else {
            document.getElementById('pick-stock').style.display = null;
        }

        this.pickCard.addCards(cards);
    }

    public hidePickStock() {
        const div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
        }
    }
    
    public renewCards(cards: Card[], topDeckCard: Card | null, deckCount: number): Promise<any> {
        this.visibleCards.removeAll();

        const promise = this.setVisibleCards(cards, false, deckCount, topDeckCard);

        return promise;
    }

    public setTopDeckCard(topDeckCard: Card | null, deckCount: number) {
        this.deck.setCardNumber(deckCount, topDeckCard);
    }
    
    public setInitialCards(cards: Card[]): Promise<any> {   
        this.deck.addCards(cards, undefined, { visible: false });
        this.visibleCards.removeAll();
        this.visibleCards.setSlotsIds([0, 1]);
        const cardsWithSlot = cards.map((card, index) => ({ ...card, location_arg: index }));
        return this.visibleCards.addCards(cardsWithSlot, { fromStock: this.deck, rotationDelta: 90 }, undefined, true);
    }

    public setVisibleCards(cards: Card[], init: boolean, deckCount: number = null, topDeckCard: Card | null = null): Promise<any> {
        if (init) {
            return this.visibleCards.addCards(cards);
        } else {
            this.setTopDeckCard(topDeckCard, deckCount);
            const cardsForDeck = cards.slice();
            cardsForDeck.sort((a, b) => b.location_arg - a.location_arg);
            // add 3 - 2 - 1
            this.deck.addCards(cardsForDeck, undefined, { visible: false, autoUpdateCardNumber: false, autoRemovePreviousCards: false } as AddCardToDeckSettings);
            // reveal 1 - 2 - 3
            this.visibleCards.setSlotsIds([1, 2, 3]);
            return this.visibleCards.addCards(cards, { fromStock: this.deck, rotationDelta: 90 }, undefined, true).then(() => 
                this.setTopDeckCard(topDeckCard, deckCount)
            );
        }
    }
    
    public removeOtherCardsFromPick(cardId: number) {        
        const removeFromPickIds = this.pickCard?.getCards().map(item => Number(item.id));
        removeFromPickIds?.forEach(id => {
            if (id !== cardId) {
                this.pickCard.removeCard({ id } as Card);
            }
        });
    }

    public getVisibleCards(): SlotStock<Card> {
        return this.visibleCards;
    }
    
    public getDeck(): Deck<Card> {
        return this.deck;
    }
    
    public getPickCard(): LineStock<Card> {
        return this.pickCard;
    }
    
    public getTokyoTower() {
        return this.tokyoTower;
    }
    
    public changeCurseCard(card: Card, hiddenCurseCardCount: number, topCurseDeckCard?: Card): Promise<any> {
        const promise = this.curseCard.addCard(card, { fromStock: this.curseDeck, originalSide: 'back' });

        this.curseDeck.setCardNumber(hiddenCurseCardCount, topCurseDeckCard);

        return promise;
    }
    
    private createWickednessTiles(wickednessTiles: WickednessTile[]) {
        this.wickednessDecks = new WickednessDecks(this.game.wickednessTilesManager);
        this.wickednessDecks.onTileClick = (card: WickednessTile) => {
            const args = this.game.gamedatas.gamestate.args as EnteringTakeWickednessTileArgs;
            if (args.noExtraTurnWarning.includes(card.type)) {
                this.game.confirmationDialog(
                    this.game.getNoExtraTurnWarningMessage(), 
                    () => this.game.takeWickednessTile(card.id)
                )
            } else {
                this.game.takeWickednessTile(card.id);
            }
        }
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
    }

    public removeWickednessTileFromPile(level: number, removedTile: WickednessTile) {
        this.wickednessDecks.removeCard(removedTile);
        this.wickednessDecks.setOpened(level, false);
        this.wickednessDecks.setSelectableLevel(null);
    }
}