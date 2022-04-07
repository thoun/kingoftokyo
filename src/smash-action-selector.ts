class SmashActionSelector {

    private selections: SmashAction[] = [];

    constructor(private game: KingOfTokyoGame, private nodeId: string, private args: EnteringResolveSmashDiceArgs) {
        this.createToggleButtons(nodeId, args);
        dojo.place(`<div id="${nodeId}-apply-wrapper" class="action-selector-apply-wrapper"><button class="bgabutton bgabutton_blue action-selector-apply" id="${nodeId}-apply">${_('Apply')}</button></div>`, nodeId);
        document.getElementById(`${nodeId}-apply`).addEventListener('click', () => this.game.applySmashActions(this.selections));
    }

    private createToggleButtons(nodeId: string, args: EnteringResolveSmashDiceArgs) {
        args.willBeWoundedIds.forEach((playerId) => {
            const player = this.game.getPlayer(playerId);
            let html = `<div class="row">
                <div class="legend" style="color: #${player.color}">
                    ${player.name}
                </div>
                <div id="${nodeId}-player${playerId}" class="toggle-buttons"></div>
            </div>`;
            dojo.place(html, nodeId);

            this.selections[playerId] = 'smash';
            this.createToggleButton(
                `${nodeId}-player${playerId}`, 
                `${nodeId}-player${playerId}-smash`, 
                /*_TODOPU*/("Don't steal"), 
                () => this.setSelectedAction(playerId, 'smash'),
                true
            );

            this.createToggleButton(
                `${nodeId}-player${playerId}`, 
                `${nodeId}-player${playerId}-steal`, 
                formatTextIcons(/*_TODOPU*/('Steal 1[Star] and 1[Energy]')),
                () => this.setSelectedAction(playerId, 'steal'),
            );
        });
    }

    private createToggleButton(destinationId: string, id: string, text: string, callback: Function, selected: boolean = false) {
        const html = `<div class="toggle-button" id="${id}">
            ${text}
        </button>`;
        dojo.place(html, destinationId);
        if (selected) {
            dojo.addClass(id, 'selected');
        }
        document.getElementById(id).addEventListener('click', () => callback());
    }

    private removeOldSelection(playerId: number) {
        const oldSelectionId = `${this.nodeId}-player${playerId}-${this.selections[playerId]}`;
        dojo.removeClass(oldSelectionId, 'selected');
    }

    private setSelectedAction(playerId: number, action: SmashAction) {
        if (this.selections[playerId] == action) {
            return;
        }
        this.removeOldSelection(playerId);
        this.selections[playerId] = action;
        dojo.addClass(`${this.nodeId}-player${playerId}-${action}`, 'selected');
    }
}