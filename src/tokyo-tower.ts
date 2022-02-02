class TokyoTower {
    public divId: string;

    constructor(divId: string, levels: number[]) {
        this.divId = `${divId}-tokyo-tower`;
        let html = `
        <div id="${this.divId}" class="tokyo-tower tokyo-tower-tooltip">`;
        for(let i=3; i>=1; i--) {
            html += `<div id="${this.divId}-level${i}">`;
            if (levels.includes(i)) {
                html += `<div id="tokyo-tower-level${i}" class="level level${i}">`;
                
                if (i == 1 || i == 2) {
                    html += `<div class="icon health"></div>`;
                }
                if (i == 2) {
                    html += `<div class="icon energy"></div>`;
                }
                if (i == 3) {
                    html += `<div class="icon star"></div>`;
                }
                        
                html += `</div>`;
            }
            html += `</div>`;
        }
        html += `</div>`;
        dojo.place(html, divId);
    }
}