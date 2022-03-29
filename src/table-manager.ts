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

            // shift background for mobile
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
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
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

    private placePlayerTable() {
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            return;
        }
        const players = this.playerTables.length;
        const tableDiv = document.getElementById('table');
        let tableWidth = tableDiv.clientWidth;
        const tableCenterDiv = document.getElementById('table-center');
        
        const availableColumns = this.getAvailableColumns(tableWidth, tableCenterDiv.clientWidth);

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
        const disposition = dispositionModel.map(columnIndexes => columnIndexes.map(columnIndex => this.playerTables[columnIndex].playerId));
        const centerColumnIndex = columns === 3 ? 1 : 0;

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
            const dispositionColumn: number[] = disposition[columnIndex];

            dispositionColumn.forEach((id, index) => {
                const playerTableDiv = document.getElementById(`player-table-${id}`);

                let columnId = 'center-column';
                if (rightColumn) {
                    columnId = 'right-column';
                } else if (leftColumn) {
                    columnId = 'left-column';
                }
                document.getElementById(columnId).appendChild(playerTableDiv);

                if (centerColumn && playerOverTable && index === 0) {
                    playerTableDiv.after(tableCenterDiv);
                }
            });
        });

        this.tableHeightChange();
    }

    public tableHeightChange() {
        this.playerTables.forEach(playerTable => {
            if (playerTable.visibleEvolutionCards) {
                dojo.toggleClass(`visible-evolution-cards-${playerTable.playerId}`, 'empty', !playerTable.visibleEvolutionCards.items.length);
            }
            if (playerTable.wickednessTiles) {
                dojo.toggleClass(`wickedness-tiles-${playerTable.playerId}`, 'empty', !playerTable.wickednessTiles.items.length);
            }
            dojo.toggleClass(`cards-${playerTable.playerId}`, 'empty', !playerTable.cards.items.length);
        });

        const zoomWrapper = document.getElementById('zoom-wrapper');
        zoomWrapper.style.height = `${document.getElementById('table').clientHeight * this.zoom}px`;
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