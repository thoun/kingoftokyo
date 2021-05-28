const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

const POINTS_DEG = [25, 40, 56, 73, 89, 105, 122, 138, 154, 170, 187, 204, 221, 237, 254, 271, 288, 305, 322, 339, 359];
const HEALTH_DEG = [360, 326, 301, 274, 249, 226, 201, 174, 149, 122, 98, 64, 39];

class PlayerTable {
    public playerId: number;
    public playerNo: number;
    private monster: number;
    private initialLocation: number;

    public cards: Stock;

    constructor(private game: KingOfTokyoGame, private player: KingOfTokyoPlayer, cards: Card[]) {
        this.playerId = Number(player.id);
        this.playerNo = Number(player.player_no);
        this.monster = Number(player.monster);
        dojo.place(`
        <div id="player-table-${player.id}" class="player-table whiteblock ${Number(player.eliminated) > 0 ? 'eliminated' : ''}">
            <div class="player-name goodgirl" style="color: #${player.color}">
                <div class="outline${player.color === '000000' ? ' white' : ''}">${player.name}</div>
                <div class="text">${player.name}</div>
            </div> 
            <div class="monster-board-wrapper">
                <div class="blue wheel" id="blue-wheel-${player.id}"></div>
                <div class="red wheel" id="red-wheel-${player.id}"></div>
                <div id="monster-board-${player.id}" class="monster-board monster${this.monster}">
                    <div id="monster-figure-${player.id}" class="monster-figure monster${this.monster}"></div>
                </div>  
            </div> 
            <div id="cards-${player.id}" class="player-cards"></div>      
        </div>

        `, 'table');
        this.cards = new ebg.stock() as Stock;
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $(`cards-${this.player.id}`), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = (card_div, card_type_id) => this.game.cards.setupNewCard(card_div, card_type_id);
        this.cards.image_items_per_row = 10;
        this.cards.centerItems = true;
        dojo.connect(this.cards, 'onChangeSelection', this, (_, itemId: string) => this.game.onVisibleCardClick(this.cards, itemId, this.playerId));

        this.game.cards.setupCards([this.cards]);
        this.game.cards.addCardsToStock(this.cards, cards);

        this.initialLocation = Number(player.location);

        this.setPoints(Number(player.score));
        this.setHealth(Number(player.health));
    }

    public initPlacement() {
        if (this.initialLocation > 0) {
            this.enterTokyo(this.initialLocation);
        }
    }

    public enterTokyo(location: number) {        
        slideToObjectAndAttach(this.game, document.getElementById(`monster-figure-${this.playerId}`), `tokyo-${location == 2 ? 'bay' : 'city'}`);
    }

    public leaveTokyo() {  
        slideToObjectAndAttach(this.game, document.getElementById(`monster-figure-${this.playerId}`), `monster-board-${this.playerId}`);
    }

    public removeCards(cards: Card[]) {
        const cardsIds = cards.map(card => card.id);
        cardsIds.forEach(id => this.cards.removeFromStockById(''+id));
    }

    public setPoints(points: number, delay: number = 0) {
        setTimeout(
            () => document.getElementById(`blue-wheel-${this.playerId}`).style.transform = `rotate(${POINTS_DEG[points]}deg)`,
            delay
        );
    }

    public setHealth(health: number, delay: number = 0) {
        setTimeout(
            () => document.getElementById(`red-wheel-${this.playerId}`).style.transform = `rotate(${health > 12 ? 22 : HEALTH_DEG[health]}deg)`,
            delay
        );
    }

    public eliminatePlayer() {
        this.cards.removeAll();
        (this.game as any).fadeOutAndDestroy(`player-board-monster-figure-${this.playerId}`);
        dojo.addClass(`player-table-${this.playerId}`, 'eliminated');
    }
    
    public setActivePlayer(active: boolean): void {
        dojo.toggleClass(`monster-board-${this.playerId}`, 'active', active);
    }
}