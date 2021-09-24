const isDebug = window.location.host == 'studio.boardgamearena.com';
const log = isDebug ? console.log.bind(window.console) : function () { };

const POINTS_DEG = [25, 40, 56, 73, 89, 105, 122, 138, 154, 170, 187, 204, 221, 237, 254, 271, 288, 305, 322, 339, 359];
const HEALTH_DEG = [360, 326, 301, 274, 249, 226, 201, 174, 149, 122, 98, 64, 39];
const SPLIT_ENERGY_CUBES = 6;
type TokenType = 'poison' | 'shrink-ray';

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

        const eliminated = Number(player.eliminated) > 0;

        dojo.place(`
        <div id="player-table-${player.id}" class="player-table whiteblock ${eliminated ? 'eliminated' : ''}">
            <div id="player-name-${player.id}" class="player-name ${game.isDefaultFont() ? 'standard' : 'goodgirl'}" style="color: #${player.color}">
                <div class="outline${player.color === '000000' ? ' white' : ''}">${player.name}</div>
                <div class="text">${player.name}</div>
            </div> 
            <div id="monster-board-wrapper-${player.id}" class="monster-board-wrapper ${player.location > 0 ? 'intokyo' : ''}">
                <div class="blue wheel" id="blue-wheel-${player.id}"></div>
                <div class="red wheel" id="red-wheel-${player.id}"></div>
                <div class="kot-token"></div>
                <div id="monster-board-${player.id}" class="monster-board monster${this.monster}">
                    <div id="monster-board-${player.id}-figure-wrapper" class="monster-board-figure-wrapper">
                        <div id="monster-figure-${player.id}" class="monster-figure monster${this.monster}"></div>
                    </div>
                </div>
                <div id="token-wrapper-${this.playerId}-poison" class="token-wrapper poison"></div>
                <div id="token-wrapper-${this.playerId}-shrink-ray" class="token-wrapper shrink-ray"></div>
            </div> 
            <div id="energy-wrapper-${player.id}-left" class="energy-wrapper left"></div>
            <div id="energy-wrapper-${player.id}-right" class="energy-wrapper right"></div>
            <div id="cards-${player.id}" class="player-cards ${cards.length ? '' : 'empty'}"></div>      
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
        if (!eliminated) {
            this.setEnergy(Number(player.energy));
            this.setPoisonTokens(Number(player.poisonTokens));
            this.setShrinkRayTokens(Number(player.shrinkRayTokens));
        }
    }

    public initPlacement() {
        if (this.initialLocation > 0) {
            this.enterTokyo(this.initialLocation);
        }
    }

    public enterTokyo(location: number) {        
        transitionToObjectAndAttach(document.getElementById(`monster-figure-${this.playerId}`), `tokyo-${location == 2 ? 'bay' : 'city'}`, this.game.getZoom());
    }

    public leaveTokyo() {  
        transitionToObjectAndAttach(document.getElementById(`monster-figure-${this.playerId}`), `monster-board-${this.playerId}-figure-wrapper`, this.game.getZoom());
    }

    public removeCards(cards: Card[]) {
        const cardsIds = cards.map(card => card.id);
        cardsIds.forEach(id => this.cards.removeFromStockById(''+id));
    }

    public setPoints(points: number, delay: number = 0) {
        setTimeout(
            () => document.getElementById(`blue-wheel-${this.playerId}`).style.transform = `rotate(${POINTS_DEG[Math.min(20, points)]}deg)`,
            delay
        );
    }

    public setHealth(health: number, delay: number = 0) {
        setTimeout(
            () => document.getElementById(`red-wheel-${this.playerId}`).style.transform = `rotate(${health > 12 ? 22 : HEALTH_DEG[health]}deg)`,
            delay
        );
    }

    public setEnergy(energy: number, delay: number = 0) {
        setTimeout(
            () => {
                this.setEnergyOnSide('left', Math.min(energy, SPLIT_ENERGY_CUBES));
                this.setEnergyOnSide('right', Math.max(energy - SPLIT_ENERGY_CUBES, 0));
            },
            delay
        );
    }

    public eliminatePlayer() {
        this.setEnergy(0);
        this.cards.removeAll();
        if (document.getElementById(`monster-figure-${this.playerId}`)) {
            (this.game as any).fadeOutAndDestroy(`monster-figure-${this.playerId}`);
        }
        dojo.addClass(`player-table-${this.playerId}`, 'eliminated');
    }
    
    public setActivePlayer(active: boolean): void {
        dojo.toggleClass(`player-table-${this.playerId}`, 'active', active);
        dojo.toggleClass(`overall_player_board_${this.playerId}`, 'active', active);
    }
    
    public setFont(prefValue: number): void {
        const defaultFont = prefValue === 1;
        dojo.toggleClass(`player-name-${this.playerId}`, 'standard', defaultFont);
        dojo.toggleClass(`player-name-${this.playerId}`, 'goodgirl', !defaultFont);
    }

    private getDistance(p1: PlacedTokens, p2: PlacedTokens): number {
        return Math.sqrt((p1.x - p2.x) ** 2 + (p1.y - p2.y) ** 2);
    }

    private getPlaceEnergySide(placed: PlacedTokens[]): PlacedTokens {
        const newPlace = {
            x: Math.random() * 33 + 16,
            y: Math.random() * 188 + 16,
        };
        let protection = 0;
        while (protection < 1000 && placed.some(place => this.getDistance(newPlace, place) < 32)) {
            newPlace.x = Math.random() * 33 + 16;
            newPlace.y = Math.random() * 188 + 16;
            protection++;
        }

        return newPlace;
    }

    private setEnergyOnSide(side: 'left' | 'right', energy: number) {
        const divId = `energy-wrapper-${this.playerId}-${side}`;
        const div = document.getElementById(divId);
        if (!div) {
            return;
        }
        const placed: PlacedTokens[] = div.dataset.placed ? JSON.parse(div.dataset.placed) : [];

        // remove tokens
        for (let i = energy; i < placed.length; i++) {
            (this.game as any).fadeOutAndDestroy(`${divId}-token${i}`);
        }
        placed.splice(energy, placed.length - energy);

        // add tokens
        for (let i = placed.length; i < energy; i++) {
            const newPlace = this.getPlaceEnergySide(placed);

            placed.push(newPlace);
            let html = `<div id="${divId}-token${i}" style="left: ${newPlace.x - 16}px; top: ${newPlace.y - 16}px;" class="energy-cube"></div>`;
            dojo.place(html, divId);
        }

        div.dataset.placed = JSON.stringify(placed);
    }
    
    public setMonster(monster: number) {
        const newMonsterClass = `monster${monster}`;

        dojo.removeClass(`monster-figure-${this.playerId}`, 'monster0');
        dojo.addClass(`monster-figure-${this.playerId}`, newMonsterClass);

        dojo.removeClass(`monster-board-${this.playerId}`, 'monster0');
        dojo.addClass(`monster-board-${this.playerId}`, newMonsterClass);
    }

    private getPlaceToken(placed: PlacedTokens[]): PlacedTokens {
        const newPlace = {
            x: 16,
            y: Math.random() * 138 + 16,
        };
        let protection = 0;
        while (protection < 1000 && placed.some(place => this.getDistance(newPlace, place) < 32)) {
            newPlace.y = Math.random() * 138 + 16;
            protection++;
        }

        return newPlace;
    }

    private setTokens(type: TokenType, tokens: number) {
        const divId = `token-wrapper-${this.playerId}-${type}`;
        const div = document.getElementById(divId);
        if (!div) {
            return;
        }
        const placed: PlacedTokens[] = div.dataset.placed ? JSON.parse(div.dataset.placed) : [];

        // remove tokens
        for (let i = tokens; i < placed.length; i++) {
            (this.game as any).fadeOutAndDestroy(`${divId}-token${i}`);
        }
        placed.splice(tokens, placed.length - tokens);

        // add tokens
        for (let i = placed.length; i < tokens; i++) {
            const newPlace = this.getPlaceToken(placed);

            placed.push(newPlace);
            let html = `<div id="${divId}-token${i}" style="left: ${newPlace.x - 16}px; top: ${newPlace.y - 16}px;" class="${type} token"></div>`;
            dojo.place(html, divId);

            (this.game as any).addTooltipHtml(`${divId}-token${i}`, type === 'poison' ? this.game.POISON_TOKEN_TOOLTIP : this.game.SHINK_RAY_TOKEN_TOOLTIP)
        }

        div.dataset.placed = JSON.stringify(placed);
    }

    public setPoisonTokens(tokens: number) {
        this.setTokens('poison', tokens);
    }

    public setShrinkRayTokens(tokens: number) {
        this.setTokens('shrink-ray', tokens);
    }
}