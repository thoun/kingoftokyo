const PLAYER_TABLE_WIDTH = 420;
const PLAYER_BOARD_HEIGHT = 247;
const CARDS_PER_ROW = 3;
const CENTER_TABLE_WIDTH = 420;
const TABLE_MARGIN = 20;
const PLAYER_TABLE_WIDTH_MARGINS = PLAYER_TABLE_WIDTH + 2*TABLE_MARGIN;
const PLAYER_BOARD_HEIGHT_MARGINS = PLAYER_BOARD_HEIGHT + 2*TABLE_MARGIN;
const CENTER_TABLE_WIDTH_MARGINS = CENTER_TABLE_WIDTH + 2*TABLE_MARGIN;

const DISPOSITION_2_COLUMNS = [];
const DISPOSITION_3_COLUMNS = [];
DISPOSITION_2_COLUMNS[2] = [[0], [1]];
DISPOSITION_2_COLUMNS[3] = [[0], [1, 2]];
DISPOSITION_2_COLUMNS[4] = [[1, 0], [2, 3]];
DISPOSITION_2_COLUMNS[5] = [[1, 0], [2, 3, 4]];
DISPOSITION_2_COLUMNS[6] = [[1, 0], [2, 3, 4, 5]];

DISPOSITION_3_COLUMNS[2] = [[], [0], [1]];
DISPOSITION_3_COLUMNS[3] = [[1], [0], [2]];
DISPOSITION_3_COLUMNS[4] = [[1], [2, 0], [3]];
DISPOSITION_3_COLUMNS[5] = [[2, 1], [0], [3, 4]];
DISPOSITION_3_COLUMNS[6] = [[2, 1], [5, 0], [3, 4]];

const ZOOM_LEVELS = [0.25, 0.375, 0.5, 0.625, 0.75, 0.875, 1];
const ZOOM_LEVELS_MARGIN = [-300, -166, -100, -60, -33, -14, 0];
const LOCAL_STORAGE_ZOOM_KEY = 'KingOfTokyo-zoom';

class TableManager {
    private playerTables: PlayerTable[]; // players in order, but starting with player_id
    public zoom: number = 1;

    constructor(private game: KingOfTokyoGame, playerTables: PlayerTable[]) {
        this.setPlayerTables(playerTables);

        (this.game as any).onScreenWidthChange = () => this.placePlayerTable();
        
        const zoomStr = localStorage.getItem(LOCAL_STORAGE_ZOOM_KEY);
        if (zoomStr) {
            this.setZoom(Number(zoomStr));
        }
    }

    private setPlayerTables(playerTables: PlayerTable[]) {
        const currentPlayerId = Number(this.game.getPlayerId());
        const playerTablesOrdered = playerTables.filter(playerTable => !!playerTable).sort((a, b) => a.playerNo - b.playerNo);
        const playerIndex = playerTablesOrdered.findIndex(playerTable => playerTable.playerId === currentPlayerId);
        if (playerIndex) { // not spectator (or 0)            
            this.playerTables = [...playerTablesOrdered.slice(playerIndex), ...playerTablesOrdered.slice(0, playerIndex)];
        } else { // spectator
            this.playerTables = playerTablesOrdered.filter(playerTable => !!playerTable);
        }
    }

    public placePlayerTable() {
        let height = 0;
        const players = this.playerTables.length;
        const zoomWrapper = document.getElementById('zoom-wrapper');
        const tableDiv = document.getElementById('table');
        let tableWidth = tableDiv.clientWidth;

        while (this.zoom > ZOOM_LEVELS[0] && tableWidth/this.zoom < CENTER_TABLE_WIDTH) {
            this.zoomOut();
            tableWidth = tableDiv.clientWidth;
        }

        const availableColumns = Math.max(1, Math.min(3, Math.floor(tableWidth / PLAYER_TABLE_WIDTH_MARGINS)));
        const idealColumns = players == 2 ? 2 : 3;

        const tableCenterDiv = document.getElementById('table-center');
        tableCenterDiv.style.left = `${(tableWidth - CENTER_TABLE_WIDTH_MARGINS) / 2}px`;
        tableCenterDiv.style.top = `0px`;

        if (availableColumns === 1) {
            let top = tableCenterDiv.clientHeight;
            this.playerTables.forEach(playerTable => {
                const playerTableDiv = document.getElementById(`player-table-${playerTable.playerId}`);
                playerTableDiv.style.left = `${(tableWidth - CENTER_TABLE_WIDTH_MARGINS) / 2}px`;
                playerTableDiv.style.top = `${top}px`;
                top += this.getPlayerTableHeight(playerTable);
                height = Math.max(height, top);
            });
        } else {
            const columns = Math.min(availableColumns, idealColumns);

            const dispositionModel = (columns === 3 ? DISPOSITION_3_COLUMNS : DISPOSITION_2_COLUMNS)[players];
            const disposition = dispositionModel.map(columnIndexes => columnIndexes.map(columnIndex => ({ 
                id: this.playerTables[columnIndex].playerId,
                height: this.getPlayerTableHeight(this.playerTables[columnIndex]),
            })));
            const tableCenter: number = (columns === 3 ? tableWidth : tableWidth - PLAYER_TABLE_WIDTH_MARGINS) / 2;
            const centerColumnIndex = columns === 3 ? 1 : 0;

            if (columns === 2) {                
                tableCenterDiv.style.left = `${tableCenter - CENTER_TABLE_WIDTH_MARGINS / 2}px`;
            }

            // we always compute "center" column first
            (columns === 3 ? [1, 0, 2] : [0, 1]).forEach(columnIndex => {
                const leftColumn = columnIndex === 0 && columns === 3;
                const centerColumn = centerColumnIndex === columnIndex;
                const rightColumn = columnIndex > centerColumnIndex;
                const playerOverTable = centerColumn && disposition[columnIndex].length > 1;
                const dispositionColumn: { id: number, height: number }[] = disposition[columnIndex];

                let top: number;
                if (centerColumn) {
                    top = !playerOverTable ? tableCenterDiv.clientHeight + 20 : 0;
                } else {
                    top = Math.max(0, (height - dispositionColumn.map(dc => dc.height).reduce((a, b) => a + b, 0)) / 2);
                }
                dispositionColumn.forEach((playerInfos, index) => {
                    const playerTableDiv = document.getElementById(`player-table-${playerInfos.id}`);
                    if (centerColumn) {
                        playerTableDiv.style.left = `${tableCenter - PLAYER_TABLE_WIDTH_MARGINS / 2}px`;
                    } else if (rightColumn) {
                        playerTableDiv.style.left = `${tableCenter + PLAYER_TABLE_WIDTH_MARGINS / 2}px`;
                    } else if (leftColumn) {
                        playerTableDiv.style.left = `${(tableCenter - PLAYER_TABLE_WIDTH_MARGINS / 2) - PLAYER_TABLE_WIDTH_MARGINS}px`;
                    }
                    playerTableDiv.style.top = `${top}px`;
                    top += playerInfos.height;

                    if (centerColumn && index == 0 && disposition[columnIndex].length > 1) {
                        tableCenterDiv.style.top = `${playerInfos.height}px`;
                        top += tableCenterDiv.clientHeight + 20;
                    }

                    height = Math.max(height, top);
                });
            });
        }
        
        tableDiv.style.height = `${height}px`;
        zoomWrapper.style.height = `${height * this.zoom}px`;
    }

    private getPlayerTableHeight(playerTable: PlayerTable) {
        const cardRows = Math.max(1, Math.ceil(playerTable.cards.items.length / CARDS_PER_ROW));
        return PLAYER_BOARD_HEIGHT_MARGINS + ((CARD_HEIGHT + 5) * cardRows);
    }

    private setZoom(zoom: number = 1) {
        this.zoom = zoom;
        localStorage.setItem(LOCAL_STORAGE_ZOOM_KEY, ''+this.zoom);
        const newIndex = ZOOM_LEVELS.indexOf(this.zoom);
        dojo.toggleClass('zoom-in', 'disabled', newIndex === ZOOM_LEVELS.length - 1);
        dojo.toggleClass('zoom-out', 'disabled', newIndex === 0);

        const div = document.getElementById('table');
        if (zoom === 1) {
            div.style.transform = '';
            div.style.margin = '';
        } else {
            div.style.transform = `scale(${zoom})`;
            div.style.margin = `0 ${ZOOM_LEVELS_MARGIN[newIndex]}% ${(1-zoom)*-100}% 0`;
        }
        this.placePlayerTable();
    }

    public zoomIn() {
        if (this.zoom === ZOOM_LEVELS[ZOOM_LEVELS.length - 1]) {
            return;
        }
        const newIndex = ZOOM_LEVELS.indexOf(this.zoom) + 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    }

    public zoomOut() {
        if (this.zoom === ZOOM_LEVELS[0]) {
            return;
        }
        const newIndex = ZOOM_LEVELS.indexOf(this.zoom) - 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    }
}