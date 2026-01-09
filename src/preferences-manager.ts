const BACKGROUND_FILENAME = {
    1: 'base.jpg',
    2: 'halloween.jpg',
    3: 'christmas.jpg',
    4: 'powerup.jpg',
    5: 'dark.jpg',
    6: 'base.jpg', // no special background for Origins
}

class PreferencesManager {

    constructor(private game: KingOfTokyoGame) { 
        this.setupPreferences();
    }

    private setupPreferences() {
        try {
            (document.getElementById('preference_control_203').closest(".preference_choice") as HTMLDivElement).style.display = 'none';
        } catch (e) {}
        try {
            (document.getElementById('preference_fontrol_203').closest(".preference_choice") as HTMLDivElement).style.display = 'none';
        } catch (e) {}
    }

    private getGameVersionNumber(versionNumber: number) {
        if (versionNumber > 0) {
            return versionNumber;
        } else {
            if (this.game.isOrigins()) {
                return 6;
            } else if (this.game.isDarkEdition()) {
                return 5;
            } else if (this.game.isPowerUpExpansion()) {
                return 4;
            } else if (this.game.isHalloweenExpansion()) {
                return 2;
            }
            return 1;
        }
    }

    public getBackgroundFilename() {
        const prefId = this.getGameVersionNumber(this.game.bga.userPreferences.get(205));
        return BACKGROUND_FILENAME[prefId];
    }
      
    public onPreferenceChange(prefId: number, prefValue: number) {
        switch (prefId) {
            case 201: 
                this.game.setFont(prefValue);
                break;
            case 203: 
                if (prefValue == 2) {
                    dojo.destroy('board-corner-highlight');
                    dojo.destroy('twoPlayersVariant-message');
                }
                break;
            case 204:
                document.getElementsByTagName('html')[0].dataset.background = ''+this.getGameVersionNumber(prefValue);
                break;
            case 205:
                document.getElementsByTagName('html')[0].dataset.dice = ''+this.getGameVersionNumber(prefValue);
                break;
        }
    }
    
    public getDiceScoringColor(): string {
        const prefId = this.getGameVersionNumber(this.game.bga.userPreferences.get(205));
        switch (prefId) {
            case 2: return '000000';
            case 3: return '0096CC';
            case 4: return '157597';
            case 5: return 'ecda5f';
            case 6: return '129447';
        }
        
        return '96c93c';        
    }
}