const SPOTS_NUMBERS = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];

class PlayerTable {
    private playerId: number;
    public spotsStock: PlayerTableSpotStock[] = [];

    private swapSpots: number[] = null;

    constructor(
        private game: ConspiracyGame, 
        player: Player,
        spots: PlayerTableSpot[]) {

        this.playerId = Number(player.id);

        dojo.place(`<div id="player-table-wrapper-${this.playerId}" class="player-table-wrapper">
            <div id="player-table-mat-${this.playerId}" class="player-table-mat mat${(player as any).mat}">
                <div id="player-table-${this.playerId}" class="player-table">
                    <div class="player-name mat${(player as any).mat}" style="color: #${player.color};">
                        ${player.name}
                    </div>
                </div>
            </div>
        </div>`, 'players-tables');

        SPOTS_NUMBERS.forEach(spotNumber => {
            this.spotsStock[spotNumber] = new PlayerTableSpotStock(game, this, player, spots[spotNumber], spotNumber);
        });

        this.checkTopLordToken();
    }

    private checkTopLordToken() {

        const lordsSpots = this.spotsStock.filter(spotStock => spotStock.getLord());
        const guilds = new Set(lordsSpots.map(spotStock => spotStock.getLord().guild));
        guilds.forEach(guild => {
            const guildLordsSpots = lordsSpots.filter(spotStock => spotStock.getLord().guild === guild);
            let topLordSpot = guildLordsSpots[0];
            guildLordsSpots.forEach(spot => {
                if (spot.getLord().points > topLordSpot.getLord().points) {
                    topLordSpot = spot;
                }
            });

            topLordSpot.placeTopLordToken();
        });
    }
    
    public addLord(spot: number, lord: Lord, fromStock: Stock | null) {
        this.spotsStock[spot].setLord(lord, fromStock);
        setTimeout(() => this.checkTopLordToken(), 500);
    }

    public addLocation(spot: number, location: Location, fromStock: Stock | null) {
        this.spotsStock[spot].setLocation(location, fromStock);
    }

    public setSelectableForSwap(selectable: boolean) {
        this.swapSpots = selectable ? [] : null;
        SPOTS_NUMBERS.forEach(spotNumber => this.spotsStock[spotNumber].setSelectableForSwap(selectable));
    }

    public removeSelectedSpot(spot: number) {
        if (!this.swapSpots) {
            return false;
        }
        const index = this.swapSpots.indexOf(spot);
        if (index !== -1) {
            this.swapSpots.splice(index, 1);
            this.setCanSwap();
        }
    }

    public addSelectedSpot(spot: number) {
        if (!this.swapSpots) {
            return false;
        }
        if (!this.swapSpots.some(val => val === spot)) {
            this.swapSpots.push(spot);
            this.setCanSwap();
        }
    }

    public setCanSwap() {
        this.game.setCanSwap(this.swapSpots);
    }
    
    public lordSwapped(args: NotifLordSwappedArgs) {
        const lordSpot1 = this.spotsStock[args.spot1].getLord();
        const lordSpot2 = this.spotsStock[args.spot2].getLord();

        const tokenSpot1 = this.spotsStock[args.spot1].getTokenDiv();
        const tokenSpot2 = this.spotsStock[args.spot2].getTokenDiv();

        this.spotsStock[args.spot1].setLord(lordSpot2, this.spotsStock[args.spot2].getLordStock());
        this.spotsStock[args.spot2].setLord(lordSpot1, this.spotsStock[args.spot1].getLordStock());

        if (tokenSpot2) {
            setTimeout(() => this.spotsStock[args.spot1].addTokenDiv(tokenSpot2), 500);
        }
        if (tokenSpot1) {
            setTimeout(() => this.spotsStock[args.spot2].addTokenDiv(tokenSpot1), 500);
        }
    }

    public highlightCoalition(coalition: Coalition) {
        this.spotsStock.filter(spotStock => spotStock.hasLord()).forEach(spotStock => spotStock.clearLordHighlight());
        coalition.alreadyCounted.forEach(spotNumber => this.spotsStock[spotNumber].highlightLord(coalition.guild));
    }

    public highlightLocations() {
        this.spotsStock.filter(spotStock => spotStock.hasLocation()).forEach(spotStock => spotStock.highlightLocation());
    }

    public highlightTopLords() {
        this.spotsStock.filter(spotStock => spotStock.hasLord() && !!spotStock.getTokenDiv()).forEach(spotStock => spotStock.highlightLord());
    }
}