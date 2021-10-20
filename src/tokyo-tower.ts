class TokyoTower {
    private divId: string;

    constructor(divId: string, levels: number[]) {
        this.divId = `${divId}-tokyo-tower`;
        dojo.place(`<div id="${this.divId}" class="tokyo-tower tokyo-tower-tooltip">
            <div class="level level3"></div>
            <div class="level level2"></div>
            <div class="level level1"></div>
        </div>`, divId);
        this.setLevels(levels);
    }

    public setLevels(levels: number[]) {
        for (let i=1; i<=3; i++) {
            (document.getElementById(this.divId).getElementsByClassName(`level${i}`)[0] as HTMLDivElement).dataset.owned = levels.includes(i) ? 'true' : 'false';
        }
    }
}