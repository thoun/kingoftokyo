class HeartActionSelector {

    private selections: HeartActionSelection[] = [];

    constructor(private game: KingOfTokyoGame, private nodeId: string, private args: EnteringResolveHeartDiceArgs) {
        this.createToggleButtons(nodeId, args);
        dojo.place(`<div id="${nodeId}-apply-wrapper"><button class="bgabutton bgabutton_blue" id="${nodeId}-apply">${_('Apply')}</button></div>`, nodeId);
        document.getElementById(`${nodeId}-apply`).addEventListener('click', () => this.game.applyHeartActions(this.selections));
    }

    private createToggleButtons(nodeId: string, args: EnteringResolveHeartDiceArgs) {
        args.dice.filter(die => die.value === 4).forEach((die, index) => {
            let html = `<div class="die">
                <div class="die-face">
                    <div class="dice-icon dice4"></div>
                </div>
                <div id="${nodeId}-die${index}" class="toggle-buttons"></div>
            </div>`;
            dojo.place(html, nodeId);

            this.createToggleButton(
                `${nodeId}-die${index}`, 
                `${nodeId}-die${index}-heal`, 
                _('Heal'), 
                () => this.healSelected(index),
                false,
                true
            );
                       
            if (!args.canHealWithDice) {
                const buttonDiv = document.getElementById(`${nodeId}-die${index}-heal`);
                buttonDiv.style.position = 'relative';
                buttonDiv.innerHTML += `<div class="icon forbidden"></div>`;
            }

            this.selections[index] = {action: 'heal' };

            if (args.shrinkRayTokens > 0) {
                this.createToggleButton(
                    `${nodeId}-die${index}`, 
                    `${nodeId}-die${index}-shrink-ray`, 
                    _('Remove Shrink Ray token'), 
                    () => this.shrinkRaySelected(index),
                    !args.canHealWithDice
                );
                       
                if (!args.canHealWithDice) {
                    const buttonDiv = document.getElementById(`${nodeId}-die${index}-shrink-ray`);
                    buttonDiv.style.position = 'relative';
                    buttonDiv.innerHTML += `<div class="icon forbidden"></div>`;
                }
            }
            if (args.poisonTokens > 0) {
                this.createToggleButton(
                    `${nodeId}-die${index}`, 
                    `${nodeId}-die${index}-poison`, 
                    _('Remove Poison token'), 
                    () => this.poisonSelected(index),
                    !args.canHealWithDice
                );
                       
                if (!args.canHealWithDice) {
                    const buttonDiv = document.getElementById(`${nodeId}-die${index}-poison`);
                    buttonDiv.style.position = 'relative';
                    buttonDiv.innerHTML += `<div class="icon forbidden"></div>`;
                }
            }
            if (args.hasHealingRay) {
                args.healablePlayers.forEach(healablePlayer =>
                    this.createToggleButton(
                        `${nodeId}-die${index}`, 
                        `${nodeId}-die${index}-heal-player-${healablePlayer.id}`, 
                        dojo.string.substitute(_('Heal player ${player_name}'), { 'player_name': `<span style="color: #${healablePlayer.color}">${healablePlayer.name}</span>` }),
                        () => this.healPlayerSelected(index, healablePlayer.id),
                        false
                    )
                );
            }
        });
    }

    private createToggleButton(destinationId: string, id: string, text: string, callback: Function, disabled: boolean, selected: boolean = false) {
        const html = `<div class="toggle-button" id="${id}">
            ${text}
        </button>`;
        dojo.place(html, destinationId);
        if (disabled) {
            dojo.addClass(id, 'disabled');
        } else if (selected) {
            dojo.addClass(id, 'selected');
        }
        document.getElementById(id).addEventListener('click', () => callback());
    }

    private removeOldSelection(index: number) {
        const oldSelectionId = this.selections[index].action == 'heal-player' ? `${this.nodeId}-die${index}-heal-player-${this.selections[index].playerId}` : `${this.nodeId}-die${index}-${this.selections[index].action}`;
        dojo.removeClass(oldSelectionId, 'selected');
    }

    private healSelected(index: number) {
        if (this.selections[index].action == 'heal') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'heal';
        dojo.addClass(`${this.nodeId}-die${index}-${this.selections[index].action}`, 'selected');

        this.checkDisabled();
    }

    private shrinkRaySelected(index: number) {
        if (this.selections[index].action == 'shrink-ray') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'shrink-ray';
        dojo.addClass(`${this.nodeId}-die${index}-${this.selections[index].action}`, 'selected');

        this.checkDisabled();
    }

    private poisonSelected(index: number) {
        if (this.selections[index].action == 'poison') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'poison';
        dojo.addClass(`${this.nodeId}-die${index}-${this.selections[index].action}`, 'selected');

        this.checkDisabled();
    }

    private healPlayerSelected(index: number, playerId: number) {
        if (this.selections[index].action == 'heal-player' && this.selections[index].playerId == playerId) {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'heal-player';
        this.selections[index].playerId = playerId;
        dojo.addClass(`${this.nodeId}-die${index}-heal-player-${playerId}`, 'selected');

        this.checkDisabled();
    }

    private checkDisabled() {
        const removedShrinkRays = this.selections.filter(selection => selection.action === 'shrink-ray').length;
        const removedPoisons = this.selections.filter(selection => selection.action === 'poison').length;

        const healedPlayers = [];
        this.args.healablePlayers.forEach(player => healedPlayers[player.id] = this.selections.filter(selection => selection.action === 'heal-player' && selection.playerId == player.id).length);

        this.selections.forEach((selection, index) => {
            if (this.args.shrinkRayTokens > 0) {
                dojo.toggleClass(`${this.nodeId}-die${index}-shrink-ray`, 'disabled', selection.action != 'shrink-ray' && removedShrinkRays >= this.args.shrinkRayTokens);
            }
            if (this.args.poisonTokens > 0) {
                dojo.toggleClass(`${this.nodeId}-die${index}-poison`, 'disabled', selection.action != 'poison' && removedPoisons >= this.args.poisonTokens);
            }
            if (this.args.hasHealingRay) {
                this.args.healablePlayers.forEach(player => dojo.toggleClass(`${this.nodeId}-die${index}-heal-player-${player.id}`, 'disabled', selection.action != 'heal-player' && selection.playerId != player.id && healedPlayers[player.id] >= player.missingHearts));
            }
        });
    }

}