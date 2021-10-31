class DieFaceSelector {

    public onChange: (value: number) => void;

    private value: number;
    private dieValue: number;

    constructor(private nodeId: string, private die: Dice, inTokyo: boolean) {
        this.dieValue = die.value;
        const colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        for (let face=1; face<=6; face++) {
            const faceId = `${nodeId}-face${face}`;
            let html = `<div id="${faceId}" class="dice-icon dice${face} ${colorClass} ${this.dieValue == face ? 'disabled' : ''}">`;
            if (!die.type && face === 4 && inTokyo) {            
                html += `<div class="icon forbidden"></div>`;
            }
            html += `</div>`;
            dojo.place(html, nodeId);
            document.getElementById(faceId).addEventListener('click', event => {

                if (this.value) {
                    if (this.value === face) {
                        return;
                    }
                    this.reset();
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

    public reset(dieValue?: number) {
        dojo.removeClass(`${this.nodeId}-face${this.value}`, 'selected');

        if (dieValue && dieValue != this.dieValue) {
            dojo.removeClass(`${this.nodeId}-face${this.dieValue}`, 'disabled');
            this.dieValue = dieValue;
            dojo.addClass(`${this.nodeId}-face${this.dieValue}`, 'disabled');
        }
    }

}