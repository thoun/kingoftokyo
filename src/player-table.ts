const CARD_WIDTH = 123;
const CARD_HEIGHT = 185;

const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

class PlayerTable {
    public playerId: number;
    public playerNo: number;
    private monster: number;
    private initialLocation: number;

    public cards: Stock;

    constructor(private game: KingOfTokyoGame, private player: Player, private order: number, cards: Card[]) {
        this.playerId = Number(player.id);
        this.playerNo = Number((player as any).player_no);
        this.monster = Number((player as any).monster);
        dojo.place(`
        <div id="player-table-${player.id}" class="player-table">
            <div class="player-name" style="color: #${player.color}">${player.name}</div> 
            <div class="monster-board-wrapper">
                <div class="blue wheel" id="blue-wheel-${player.id}"></div>
                <div class="red wheel" id="red-wheel-${player.id}"></div>
                <div id="monster-board-${player.id}" class="monster-board monster${this.monster}">
                    <div id="monster-figure-${player.id}" class="monster-figure monster${this.monster}"></div>
                </div>  
            </div> 
            <div id="cards-${player.id}"></div>      
        </div>

        `, 'table');
        this.cards = new ebg.stock() as Stock;
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $(`cards-${this.player.id}`), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = (card_div, card_type_id, card_id) => this.game.cards.setupNewCard(card_div, card_type_id);
        //this.cards.image_items_per_row = 13;
        this.cards.centerItems = true;

        this.game.cards.setupCards([this.cards]);

        cards.forEach(card => this.cards.addToStockWithId(card.type, `${card.id}`));

        this.initialLocation = Number((player as any).location);

        this.setPoints(Number(player.score));
        this.setHealth(Number((player as any).health));
    }

    public initPlacement() {
        if (this.initialLocation > 0) {
            this.enterTokyo(this.initialLocation);
        }
    }

    public enterTokyo(location: number) {        
        (this.game as any).slideToObject(`monster-figure-${this.playerId}`, `tokyo-${location == 2 ? 'bay' : 'city'}`).play();
    }

    public leaveTokyo() {        
        (this.game as any).slideToObject(`monster-figure-${this.playerId}`, `monster-board-${this.playerId}`).play();
    }
    
    public removeDiscardCards() {
        const discardCardsIds = this.cards.getAllItems().filter(item => item.type >= 100).map(item => Number(item.id));
        discardCardsIds.forEach(id => this.cards.removeFromStockById(''+id));
    }

    public setPoints(points: number) {
        const deg = 25 + 335 * points / 20;
        document.getElementById(`blue-wheel-${this.playerId}`).style.transform = `rotate(${deg}deg)`;
    }

    public setHealth(health: number) {
        const deg = 360 - 262 * health / 10;
        document.getElementById(`red-wheel-${this.playerId}`).style.transform = `rotate(${deg}deg)`;
    }
}