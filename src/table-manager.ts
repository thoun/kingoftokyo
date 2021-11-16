const PLAYER_TABLE_WIDTH = 420;
const PLAYER_BOARD_HEIGHT = 247;
const CARDS_PER_ROW = 3;
const TABLE_MARGIN = 20;
const PLAYER_TABLE_WIDTH_MARGINS = PLAYER_TABLE_WIDTH + 2*TABLE_MARGIN;
const PLAYER_BOARD_HEIGHT_MARGINS = PLAYER_BOARD_HEIGHT + 2*TABLE_MARGIN;

const DISPOSITION_1_COLUMN = [];
const DISPOSITION_2_COLUMNS = [];
const DISPOSITION_3_COLUMNS = [];

DISPOSITION_1_COLUMN[2] = [[0, 1]];
DISPOSITION_1_COLUMN[3] = [[0, 1, 2]];
DISPOSITION_1_COLUMN[4] = [[0, 1, 2, 3]];
DISPOSITION_1_COLUMN[5] = [[0, 1, 2, 3, 4]];
DISPOSITION_1_COLUMN[6] = [[0, 1, 2, 3, 4, 5]];

DISPOSITION_2_COLUMNS[2] = [[0], [1]];
DISPOSITION_2_COLUMNS[3] = [[0], [1, 2]];
DISPOSITION_2_COLUMNS[4] = [[0], [1, 2, 3]];
DISPOSITION_2_COLUMNS[5] = [[0, 4], [1, 2, 3]];
DISPOSITION_2_COLUMNS[6] = [[0, 5], [1, 2, 3, 4]];

DISPOSITION_3_COLUMNS[2] = [[0], [], [1]];
DISPOSITION_3_COLUMNS[3] = [[0, 2], [], [1]];
DISPOSITION_3_COLUMNS[4] = [[0, 3], [], [1, 2]];
DISPOSITION_3_COLUMNS[5] = [[0, 4, 3], [], [1, 2]];
DISPOSITION_3_COLUMNS[6] = [[0, 5, 4], [], [1, 2, 3]];

const ZOOM_LEVELS = [0.25, 0.375, 0.5, 0.625, 0.75, 0.875, 1];
const ZOOM_LEVELS_MARGIN = [-300, -166, -100, -60, -33, -14, 0];
const LOCAL_STORAGE_ZOOM_KEY = 'KingOfTokyo-zoom';

class TableManager {
    private playerTables: PlayerTable[]; // players in order, but starting with player_id
    public zoom: number = 1;

    constructor(private game: KingOfTokyoGame, playerTables: PlayerTable[]) { 
        const zoomStr = localStorage.getItem(LOCAL_STORAGE_ZOOM_KEY);
        if (zoomStr) {
            this.zoom = Number(zoomStr);
        }

        this.setPlayerTables(playerTables);

        (this.game as any).onScreenWidthChange = () => {
            this.setAutoZoomAndPlacePlayerTables();

            let backgroundPositionY = 0;
            if (document.body.classList.contains('mobile_version')) {
                backgroundPositionY = 62 + document.getElementById('right-side').getBoundingClientRect().height;
            }
            document.getElementsByTagName(('html'))[0].style.backgroundPositionY = `${backgroundPositionY}px`; 
        };
    }

    private setPlayerTables(playerTables: PlayerTable[]) {
        const currentPlayerId = Number(this.game.getPlayerId());
        const playerTablesOrdered = playerTables.sort((a, b) => a.playerNo - b.playerNo);
        const playerIndex = playerTablesOrdered.findIndex(playerTable => playerTable.playerId === currentPlayerId);
        
        if (playerIndex > 0) { // not spectator (or 0)            
            this.playerTables = [...playerTablesOrdered.slice(playerIndex), ...playerTablesOrdered.slice(0, playerIndex)];
        } else { // spectator
            this.playerTables = playerTablesOrdered;
        }
    }

    public setAutoZoomAndPlacePlayerTables() {
        if (dojo.hasClass('kot-table', 'pickMonster')) {
            return;
        }
        
        const zoomWrapperWidth = document.getElementById('zoom-wrapper').clientWidth;

        if (!zoomWrapperWidth) {
            setTimeout(() => this.setAutoZoomAndPlacePlayerTables(), 200);
            return;
        }

        const centerTableWidth = document.getElementById('table-center').clientWidth;

        let newZoom = this.zoom;
        while (newZoom > ZOOM_LEVELS[0] && zoomWrapperWidth/newZoom < centerTableWidth) {
            newZoom = ZOOM_LEVELS[ZOOM_LEVELS.indexOf(newZoom) - 1];
        }
        // zoom will also place player tables. we call setZoom even if this method didn't change it because it might have been changed by localStorage zoom
        this.setZoom(newZoom);
    }

    private getAvailableColumns(tableWidth: number, tableCenterWidth: number) {
        if (tableWidth >= tableCenterWidth + 2*PLAYER_TABLE_WIDTH_MARGINS) {
            return 3;
        } else if (tableWidth >= tableCenterWidth + PLAYER_TABLE_WIDTH_MARGINS) {
            return 2;
        } else {
            return 1;
        }
    }

    public placePlayerTable() {
        if (dojo.hasClass('kot-table', 'pickMonster')) {
            return;
        }
        const players = this.playerTables.length;

        const zoomWrapper = document.getElementById('zoom-wrapper');
        const tableDiv = document.getElementById('table');
        let tableWidth = tableDiv.clientWidth;
        const tableCenterDiv = document.getElementById('table-center');

        this.playerTables.forEach(playerTable => {
            if (playerTable.wickednessTiles) {
                dojo.toggleClass(`wickedness-tiles-${playerTable.playerId}`, 'empty', !playerTable.wickednessTiles.items.length);
            }
            dojo.toggleClass(`cards-${playerTable.playerId}`, 'empty', !playerTable.cards.items.length);
        });
        
        const availableColumns = this.getAvailableColumns(tableWidth, tableCenterDiv.clientWidth);

        const centerTableWidthMargins = tableCenterDiv.clientWidth + 2*TABLE_MARGIN;
        tableCenterDiv.style.left = `${(tableWidth - centerTableWidthMargins) / 2}px`;
        tableCenterDiv.style.top = `0px`;
        let height = tableCenterDiv.clientHeight;

        const columns = Math.min(availableColumns, 3);

        let dispositionModelColumn: number[][][];
        if (columns === 1) {
            dispositionModelColumn = DISPOSITION_1_COLUMN;
        } else if (columns === 2) {
            dispositionModelColumn = DISPOSITION_2_COLUMNS;
        } else {
            dispositionModelColumn = DISPOSITION_3_COLUMNS;
        }
        const dispositionModel = dispositionModelColumn[players];
        const disposition = dispositionModel.map(columnIndexes => columnIndexes.map(columnIndex => ({ 
            id: this.playerTables[columnIndex].playerId,
            height: this.getPlayerTableHeight(this.playerTables[columnIndex]),
        })));
        const tableCenter: number = (columns === 2 ? tableWidth - PLAYER_TABLE_WIDTH_MARGINS : tableWidth) / 2;
        const centerColumnIndex = columns === 3 ? 1 : 0;

        if (columns === 2) {                
            tableCenterDiv.style.left = `${tableCenter - tableCenterDiv.clientWidth / 2}px`;
        }

        // we always compute "center" column first
        let columnOrder: number[];
        if (columns === 1) {
            columnOrder = [0];
        } else if (columns === 2) {
            columnOrder = [0, 1];
        } else {
            columnOrder = [1, 0, 2];
        }
        columnOrder.forEach(columnIndex => {
            const leftColumn = columnIndex === 0 && columns === 3;
            const centerColumn = centerColumnIndex === columnIndex;
            const rightColumn = columnIndex > centerColumnIndex;
            const playerOverTable = centerColumn && disposition[columnIndex].length;
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
                    playerTableDiv.style.left = `${tableCenter + tableCenterDiv.clientWidth / 2}px`;
                } else if (leftColumn) {
                    playerTableDiv.style.left = `${(tableCenter - centerTableWidthMargins / 2) - PLAYER_TABLE_WIDTH_MARGINS}px`;
                }
                playerTableDiv.style.top = `${top}px`;
                top += playerInfos.height;

                if (centerColumn && playerOverTable && index === 0) {
                    tableCenterDiv.style.top = `${playerInfos.height}px`;
                    top += tableCenterDiv.clientHeight + 20;
                }

                height = Math.max(height, top);
            });
        });

        tableDiv.style.height = `${height}px`;
        zoomWrapper.style.height = `${height * this.zoom}px`;
    }

    private getPlayerTableHeight(playerTable: PlayerTable) {
        const tilesRows = playerTable.wickednessTiles ? Math.ceil(playerTable.wickednessTiles.items.length / CARDS_PER_ROW) : 0;
        const tilesHeight = tilesRows === 0 ? 0 : ((WICKEDNESS_TILES_HEIGHT + 5) * tilesRows);
        const cardRows = Math.ceil(playerTable.cards.items.length / CARDS_PER_ROW);
        const cardHeight = cardRows === 0 ? 20 : ((CARD_HEIGHT + 5) * cardRows);
        return PLAYER_BOARD_HEIGHT_MARGINS + tilesHeight + cardHeight;
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