const GUILD_COLOR = [];
GUILD_COLOR[1] = '#E0CA4E';
GUILD_COLOR[2] = '#DB6646';
GUILD_COLOR[3] = '#037552';
GUILD_COLOR[4] = '#0096D2';
GUILD_COLOR[5] = '#74549F';

class Minimap {
    constructor(
        private playerId: number,
        spots: PlayerTableSpot[]
    ) {
        let html = `<div id="minimap-${playerId}" class="minimap">`;
        SPOTS_NUMBERS.forEach(spotNumber => 
            html += `<div class="player-table-spot spot${spotNumber}"></div>`
        );
        html += `</div>`;
        dojo.place(html, `lord-counter-wrapper-${playerId}`);

        SPOTS_NUMBERS.filter(spotNumber => !!spots[spotNumber-1].lord).forEach(spotNumber => this.setGuildToSpot(spotNumber, spots[spotNumber-1].lord.guild));
    }
    
    private setGuildToSpot(spotNumber: number, guild: number) {
        (document.getElementById(`minimap-${this.playerId}`).getElementsByClassName(`spot${spotNumber}`)[0] as HTMLDivElement).style.background = GUILD_COLOR[guild];
    }
    
    public addLord(spot: number, lord: Lord) {
        this.setGuildToSpot(spot, lord.guild);
    }
    
    public lordSwapped(args: NotifLordSwappedArgs) {
        const colorLordSpot1 = (document.getElementById(`minimap-${this.playerId}`).getElementsByClassName(`spot${args.spot1}`)[0] as HTMLDivElement).style.background;
        const colorLordSpot2 = (document.getElementById(`minimap-${this.playerId}`).getElementsByClassName(`spot${args.spot2}`)[0] as HTMLDivElement).style.background;

        (document.getElementById(`minimap-${this.playerId}`).getElementsByClassName(`spot${args.spot1}`)[0] as HTMLDivElement).style.background = colorLordSpot2;
        (document.getElementById(`minimap-${this.playerId}`).getElementsByClassName(`spot${args.spot2}`)[0] as HTMLDivElement).style.background = colorLordSpot1;
    }
}