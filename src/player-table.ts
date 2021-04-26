const CARD_WIDTH = 123;
const CARD_HEIGHT = 185;

const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

class PlayerTable {
    private monster: number;

    constructor(private player: Player, private order: number) {
        this.monster = Number((player as any).monster);
        dojo.place(`
        <div class="player-table">
            <div class="player-name" style="color: #${player.color}">${player.name}</div> 
            <div class="monster-board monster${this.monster}"></div>
        </div>
        `, 'players-tables');
    }
}