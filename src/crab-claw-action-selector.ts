class CrabClawActionSelector {

    private selections: { [cardId: number]: CrabClawAction } = {};

    constructor(private game: KingOfTokyoGame, private nodeId: string, args: Card[]) {
        this.createToggleButtons(nodeId, args);
        dojo.place(`<div id="${nodeId}-apply-wrapper" class="action-selector-apply-wrapper"><button class="bgabutton bgabutton_blue action-selector-apply" id="${nodeId}-apply">${_('Apply')}</button></div>`, nodeId);
        document.getElementById(`${nodeId}-apply`).addEventListener('click', () => this.game.bgaPerformAction('actApplyCrabClawChoices', {
            crabClawChoices: JSON.stringify(this.selections)
        }));
    }

    private createToggleButtons(nodeId: string, args: Card[]) {
        args.forEach((card) => {
            const cardName = this.game.cardsManager.getCardName(card.type, 'text-only');
            let html = `<div class="row">
                <div class="legend">
                    ${cardName}
                </div>
                <div id="${nodeId}-card${card.id}" class="toggle-buttons"></div>
            </div>`;
            dojo.place(html, nodeId);

            this.selections[card.id] = 'discard';
            this.createToggleButton(
                `${nodeId}-card${card.id}`, 
                `${nodeId}-card${card.id}-discard`, 
                _("Discard"), 
                () => this.setSelectedAction(card.id, 'discard'),
                true
            );

            this.createToggleButton(
                `${nodeId}-card${card.id}`, 
                `${nodeId}-card${card.id}-pay`, 
                formatTextIcons(_('Keep') + ' (1[Energy])'),
                () => this.setSelectedAction(card.id, 'pay'),
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

    private removeOldSelection(cardId: number) {
        const oldSelectionId = `${this.nodeId}-card${cardId}-${this.selections[cardId]}`;
        dojo.removeClass(oldSelectionId, 'selected');
    }

    private setSelectedAction(cardId: number, action: CrabClawAction) {
        if (this.selections[cardId] == action) {
            return;
        }
        this.removeOldSelection(cardId);
        this.selections[cardId] = action;
        dojo.addClass(`${this.nodeId}-card${cardId}-${action}`, 'selected');
    }
}