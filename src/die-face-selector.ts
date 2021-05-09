class DieFaceSelector {

    public onChange: (value: number) => void;

    private value: number;

    constructor(nodeId: string, inTokyo: boolean) {
        for (let face=1; face<=6; face++) {
            const faceId = `${nodeId}-face${face}`;
            let html = `<div id="${faceId}" class="dice-icon dice${face}">`;
            if (face === 4 && inTokyo) {            
                html += `<div class="icon forbidden"></div>`;
            }
            html += `</div>`;
            dojo.place(html, nodeId);
            document.getElementById(faceId).addEventListener('click', event => {

                if (this.value) {
                    if (this.value === face) {
                        return;
                    }

                    dojo.removeClass(`${nodeId}-face${this.value}`, 'selected');
                }

                this.value = face;
                dojo.addClass(`${nodeId}-face${this.value}`, 'selected');
                this.onChange?.(face);
                event.stopImmediatePropagation();
            });
        }
    }

    public getValue() {
        return this.value;
    }

}