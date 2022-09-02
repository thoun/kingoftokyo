class PreferencesManager {

    constructor(private game: KingOfTokyoGame) { 
        this.setupPreferences();
    }

    private setupPreferences() {
        // Extract the ID and value from the UI control
        const onchange = (e) => {
          var match = e.target.id.match(/^preference_control_(\d+)$/);
          if (!match) {
            return;
          }
          var prefId = +match[1];
          var prefValue = +e.target.value;
          (this.game as any).prefs[prefId].value = prefValue;
          this.onPreferenceChange(prefId, prefValue);
        }
        
        // Call onPreferenceChange() when any value changes
        dojo.query(".preference_control").connect("onchange", onchange);
        
        // Call onPreferenceChange() now
        dojo.forEach(
          dojo.query("#ingame_menu_content .preference_control"),
          el => onchange({ target: el })
        );

        try {
            (document.getElementById('preference_control_203').closest(".preference_choice") as HTMLDivElement).style.display = 'none';
            (document.getElementById('preference_fontrol_203').closest(".preference_choice") as HTMLDivElement).style.display = 'none';
        } catch (e) {}
    }

    private getGameVersionNumber(versionNumber: number) {
        if (versionNumber > 0) {
            return versionNumber;
        } else {
            if (this.game.isDarkEdition()) {
                return 5;
            } else if (this.game.isPowerUpExpansion()) {
                return 4;
            } else if (this.game.isHalloweenExpansion()) {
                return 2;
            }
            return 1;
        }
    }
      
    private onPreferenceChange(prefId: number, prefValue: number) {
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
    
    public getDiceScoringColor(): any {
        const prefId = this.getGameVersionNumber((this.game as any).prefs[205].value);
        switch (prefId) {
            case 2: return '000000';
            case 3: return '0096CC';
            case 4: return '157597';
            case 5: return 'ecda5f';
        }
        
        return '96c93c';        
    }
}