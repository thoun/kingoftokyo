const CARD_WIDTH = 123;
const CARD_HEIGHT = 185;

const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

class PlayerTable {

    constructor(private player: Player, private order: number) {
        dojo.place(`<div>player ${player.name}, monster ${(player as any).monster}</div>`, 'players-tables');
    }
}