class DiceManager {
    private dice: Dice[] = [];

    constructor(private game: KingOfTokyoGame, setupDice: Dice[]) {
        // TODO use setupDice ?
    }

    public hideLock() {
        dojo.addClass('locked-dice', 'hide-lock');
    }
    public showLock() {
        dojo.removeClass('locked-dice', 'hide-lock');
    }
    
    public destroyFreeDice(): number[] {
        const freeDice = this.dice.filter(die => !die.locked);
        freeDice.forEach(die => this.removeDice(die));
        return freeDice.map(die => die.id);
    }

    public removeAllDice() {
        this.dice.forEach(die => this.removeDice(die));
        this.clearDiceHtml();
        this.dice = [];
    }

    public setDiceForThrowDice(dice: Dice[], lastTurn: boolean, inTokyo: boolean, isCurrentPlayerActive: boolean) {
        this.dice?.forEach(die => this.removeDice(die));
        this.clearDiceHtml();
        this.dice = dice;

        const selectable = isCurrentPlayerActive && !lastTurn;

        dice.forEach(die => this.createDice(die, selectable, inTokyo));

        dojo.toggleClass('rolled-dice', 'selectable', selectable);
    }

    public setDiceForChangeDie(dice: Dice[], args: EnteringChangeDieArgs, inTokyo: boolean, isCurrentPlayerActive: boolean) {
        this.dice?.forEach(die => this.removeDice(die));  
        this.clearDiceHtml();
        this.dice = dice;
        
        const onlyHerdCuller = args.hasHerdCuller && !args.hasPlotTwist && !args.hasStretchy;
        dice.forEach(die => {
            const divId = `dice${die.id}`;
            dojo.place(this.createDiceHtml(die, inTokyo), `dice-selector${die.value}`);
            const selectable = isCurrentPlayerActive && (!onlyHerdCuller || die.value !== 1);
            dojo.toggleClass(divId, 'selectable', selectable);
            this.addDiceRollClass(die);

            if (selectable) {
                dojo.place(`<div id="discussion_bubble_${divId}" class="discussion_bubble change-die-discussion_bubble"></div>`, divId);
                document.getElementById(divId).addEventListener('click', () => this.toggleBubbleChangeDie(die, args));
            }
        });
    }

    public setDiceForSelectHeartAction(dice: Dice[], inTokyo: boolean) { 
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            dojo.place(this.createDiceHtml(die, inTokyo), `dice-selector${die.value}`);
            this.addDiceRollClass(die);
        });
    }

    setDiceForPsychicProbe(dice: Dice[], inTokyo: boolean, isCurrentPlayerActive: boolean) {
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            dojo.place(this.createDiceHtml(die, inTokyo), `dice-selector${die.value}`);
            this.addDiceRollClass(die);

            if (isCurrentPlayerActive) {
                const divId = `dice${die.id}`;
                document.getElementById(divId).addEventListener('click', () => this.game.psychicProbeRollDie(die.id));
            }
        });

        dojo.toggleClass('rolled-dice', 'selectable', isCurrentPlayerActive);
    }

    showCamouflageRoll(diceValues: number[]) {
        this.clearDiceHtml();
        diceValues.forEach((dieValue, index) => {
            const die: Dice = {
                id: index,
                value: dieValue,
                extra: false,
                locked: false,
                rolled: true,
            };
            dojo.place(this.createDiceHtml(die, false), `dice-selector${die.value}`);
            this.addDiceRollClass(die);
        });
    }

    private clearDiceHtml() {        
        for (let i=1; i<=6; i++) {
            document.getElementById(`locked-dice${i}`).innerHTML = '';
            document.getElementById(`dice-selector${i}`).innerHTML = '';
        }
    }

    public resolveNumberDice(args: NotifResolveNumberDiceArgs) {
        const dice = this.dice.filter(die => die.value === args.diceValue);
        (this.game as any).displayScoring( `dice${(dice[1] || dice[0]).id}`, '96c93c', args.deltaPoints, 1500);
        this.dice.filter(die => die.value === args.diceValue).forEach(die => this.removeDice(die, 1000, 1500));
    }

    public resolveHealthDiceInTokyo() {
        this.dice.filter(die => die.value === 4).forEach(die => this.removeDice(die, 1000));
    }

    private addDiceAnimation(diceValue: number, playerIds: number[]) {
        const dice = this.dice.filter(die => die.value === diceValue);
        playerIds.forEach((playerId, playerIndex) => {
            const destination = document.getElementById(`monster-figure-${playerId}`).getBoundingClientRect();
            dice.forEach((die, dieIndex) => {
                const origin = document.getElementById(`dice${die.id}`).getBoundingClientRect();
                const animationId = `dice${die.id}-player${playerId}-animation`;
                dojo.place(`<div id="${animationId}" class="animation animation${diceValue}"></div>`, `dice${die.id}`);
                setTimeout(() => {
                    const middleIndex = dice.length - 1;
                    const deltaX = (dieIndex - middleIndex) * 220;
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, 100px) scale(1)`;
                }, 50);

                setTimeout(() => {
                    const deltaX = destination.left - origin.left + 59;
                    const deltaY = destination.top - origin.top + 59;
                    
                    document.getElementById(animationId).style.transition = `transform 0.5s ease-in`;
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.30)`;
                }, 1000);

                if (playerIndex === playerIds.length - 1) {
                    setTimeout(() => this.removeDice(die), 2500);
                }
            });
        });
    }

    public resolveHealthDice(args: NotifResolveHealthDiceArgs) {
        this.addDiceAnimation(4, [args.playerId]);
    }

    public resolveEnergyDice(args: NotifResolveEnergyDiceArgs) {
        this.addDiceAnimation(5, [args.playerId]);
    }

    public resolveSmashDice(args: NotifResolveSmashDiceArgs) {
        this.addDiceAnimation(6, args.smashedPlayersIds);
    }

    private toggleLockDice(die: Dice, forcedLockValue: boolean | null = null) {
        die.locked = forcedLockValue === null ? !die.locked : forcedLockValue;
        const dieDiv = document.getElementById(`dice${die.id}`);

        slideToObjectAndAttach(this.game, dieDiv, die.locked ? `locked-dice${die.value}` : `dice-selector${die.value}`);

        this.activateRethrowButton();
    }

    private activateRethrowButton() {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.dice.some(die => !die.locked));
        }
    }

    private createDiceHtml(die: Dice, inTokyo: boolean) {
        let html = `<div id="dice${die.id}" class="dice dice${die.value}" data-dice-id="${die.id}" data-dice-value="${die.value}">
        <ol class="die-list" data-roll="${die.value}">`;
        for (let dieFace=1; dieFace<=6; dieFace++) {
            html += `<li class="die-item ${die.extra ? 'green' : 'black'} side${dieFace}" data-side="${dieFace}"></li>`;
        }
        html += `</ol>`;
        if (die.value === 4 && inTokyo) {
            html += `<div class="icon forbidden"></div>`;
        }
        html += `</div>`;
        return html;
    }

    private getDiceDiv(die: Dice) {
        return document.getElementById(`dice${die.id}`);
    }

    private createDice(die: Dice, selectable: boolean, inTokyo: boolean) {
        dojo.place(this.createDiceHtml(die, inTokyo), die.locked ? `locked-dice${die.value}` : `dice-selector${die.value}`);

        this.addDiceRollClass(die);

        if (selectable) {
            this.getDiceDiv(die).addEventListener('click', () => this.toggleLockDice(die));
        }
    }

    private addDiceRollClass(die: Dice) {
        const dieDiv = this.getDiceDiv(die);

        if (die.rolled) {
            dieDiv.classList.add('rolled');
            setTimeout(() => dieDiv.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll'), 200); 
            setTimeout(() => dieDiv.classList.remove('rolled'), 1200); 
        } else {
            dieDiv.getElementsByClassName('die-list')[0].classList.add('no-roll');
        }
    }

    private removeDice(die: Dice, duration?: number, delay?: number) {
        if (duration) {
            (this.game as any).fadeOutAndDestroy(`dice${die.id}`, duration, delay);
        } else {
            dojo.destroy(`dice${die.id}`);
        }
        this.dice.splice(this.dice.indexOf(die), 1);
    }

    private hideBubble(dieId: number) {
        const bubble = document.getElementById(`discussion_bubble_dice${dieId}`);
        bubble.style.display = 'none';
        bubble.dataset.visible = 'false';
    }

    private toggleBubbleChangeDie(die: Dice, args: EnteringChangeDieArgs) {
        const bubble = document.getElementById(`discussion_bubble_dice${die.id}`);
        const visible = bubble.dataset.visible == 'true';

        if (visible) {
            this.hideBubble(die.id);
        } else {
            if (bubble.innerHTML == '') {
                const bubbleActionButtonsId = `discussion_bubble_dice${die.id}-action-buttons`;
                const bubbleDieFaceSelectorId = `discussion_bubble_dice${die.id}-die-face-selector`;
                dojo.place(`
                <div id="${bubbleDieFaceSelectorId}" class="die-face-selector"></div>
                <div id="${bubbleActionButtonsId}" class="action-buttons"></div>
                `, bubble.id);

                const herdCullerButtonId = `${bubbleActionButtonsId}-herdCuller`;
                const plotTwistButtonId = `${bubbleActionButtonsId}-plotTwist`;
                const stretchyButtonId = `${bubbleActionButtonsId}-stretchy`;
                const dieFaceSelector = new DieFaceSelector(bubbleDieFaceSelectorId, args.inTokyo);

                const buttonText = _("Change die face with ${card_name}");
                
                if (args.hasHerdCuller) {
                    this.game.createButton(
                        bubbleActionButtonsId, 
                        herdCullerButtonId, 
                        dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cards.getCardName(22, 'text-only')}</strong>` }),
                        () => {
                            this.game.changeDie(die.id, dieFaceSelector.getValue(), 22);
                            this.toggleBubbleChangeDie(die, args);
                        },
                        true
                    );
                }
                if (args.hasPlotTwist) {
                    this.game.createButton(
                        bubbleActionButtonsId, 
                        plotTwistButtonId, 
                        dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cards.getCardName(33, 'text-only')}</strong>` }),
                        () => {
                            this.game.changeDie(die.id, dieFaceSelector.getValue(), 33),
                            this.toggleBubbleChangeDie(die, args);
                        },
                        true
                    );
                }
                if (args.hasStretchy) {
                    this.game.createButton(
                        bubbleActionButtonsId, 
                        stretchyButtonId, 
                        dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cards.getCardName(44, 'text-only')}</strong>` }) + formatTextIcons(' (2 [Energy])'),
                        () => {
                            this.game.changeDie(die.id, dieFaceSelector.getValue(), 44),
                            this.toggleBubbleChangeDie(die, args);
                        },
                        true
                    );
                }

                dieFaceSelector.onChange = value => {
                    if (args.hasHerdCuller && die.value > 1) {
                        dojo.toggleClass(herdCullerButtonId, 'disabled', value != 1);
                    }
                    if (args.hasPlotTwist) {
                        dojo.toggleClass(plotTwistButtonId, 'disabled', value < 1);
                    }
                    if (args.hasStretchy) {
                        dojo.toggleClass(stretchyButtonId, 'disabled', value < 1);
                    }
                };

                bubble.addEventListener('click', event => event.stopImmediatePropagation());
            }

            args.dice.filter(idie => idie.id != die.id).forEach(idie => this.hideBubble(idie.id));

            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
        
    }

}