const CARD_WIDTH = 123;
const CARD_HEIGHT = 185;

const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

class PlayerTable {
    private playerId : number;
    private monster: number;

    public cards: Stock;

    constructor(private game: Game, private player: Player, private order: number, cards: Card[]) {
        this.playerId = Number((player as any).id);
        this.monster = Number((player as any).monster);
        dojo.place(`
        <div id="player-table-${player.id}" class="player-table">
            <div class="player-name" style="color: #${player.color}">${player.name}</div> 
            <div id="monster-board-${player.id}" class="monster-board monster${this.monster}">
                <div id="monster-figure-${player.id}" class="monster-figure monster${this.monster}"></div>
            </div>   
            <div id="cards-${player.id}"></div>      
        </div>

        `, 'players-tables');
        this.cards = new ebg.stock() as Stock;
        this.cards.setSelectionAppearance('class');
        this.cards.selectionClass = 'no-visible-selection';
        this.cards.create(this.game, $(`cards-${this.player.id}`), CARD_WIDTH, CARD_HEIGHT);
        this.cards.setSelectionMode(0);
        this.cards.onItemCreate = (card_div, card_type_id, card_id) => setupNewCard(card_div, card_type_id, card_id);
        this.cards.image_items_per_row = 13;
        this.cards.centerItems = true;

        setupCards([this.cards]);

        cards.forEach(card => this.cards.addToStockWithId(card.type, `${card.id}`));

        const location = Number((player as any).location);
        if (location > 0) {
            this.enterTokyo(location);
        }
    }

    public enterTokyo(location: number) {        
        (this.game as any).slideToObject(`monster-figure-${this.playerId}`, `tokyo-${location == 2 ? 'bay' : 'city'}`).play();
    }

    public leaveTokyo() {        
        (this.game as any).slideToObject(`monster-figure-${this.playerId}`, `monster-board-${this.playerId}`).play();
    }
}