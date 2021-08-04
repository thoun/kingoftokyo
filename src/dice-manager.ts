type DieClickAction = 'move' | 'change' | 'psychicProbeRoll';

class DiceManager {
    private dice: Dice[] = [];
    private dieFaceSelectors: DieFaceSelector[] = [];
    private action: DieClickAction;
    private changeDieArgs: EnteringChangeDieArgs;

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

    public setDiceForThrowDice(dice: Dice[], inTokyo: boolean, isCurrentPlayerActive: boolean) {
        this.action = 'move';
        this.dice?.forEach(die => this.removeDice(die));
        this.clearDiceHtml();
        this.dice = dice;

        const selectable = isCurrentPlayerActive;

        dice.forEach(die => this.createDice(die, selectable, inTokyo));

        dojo.toggleClass('rolled-dice', 'selectable', selectable);
    }

    public disableDiceAction() {
        dojo.removeClass('rolled-dice', 'selectable');
        this.action = undefined;
    }

    public setDiceForChangeDie(dice: Dice[], args: EnteringChangeDieArgs, inTokyo: boolean, isCurrentPlayerActive: boolean) {
        this.action = args.hasHerdCuller || args.hasPlotTwist || args.hasStretchy ? 'change' : null;
        this.changeDieArgs = args;

        if (this.dice.length) {
            dice.forEach(die => {
                const divId = `dice${die.id}`;
                const selectable = isCurrentPlayerActive && this.action !== null && (!onlyHerdCuller || die.value !== 1);
                dojo.toggleClass(divId, 'selectable', selectable);
            });
            return;
        }

        this.dice?.forEach(die => this.removeDice(die));  
        this.clearDiceHtml();
        this.dice = dice;
        
        const onlyHerdCuller = args.hasHerdCuller && !args.hasPlotTwist && !args.hasStretchy;
        dice.forEach(die => {
            const divId = `dice${die.id}`;
            this.createAndPlaceDiceHtml(die, inTokyo, `locked-dice${die.value}`);
            const selectable = isCurrentPlayerActive && this.action !== null && (!onlyHerdCuller || die.value !== 1);
            dojo.toggleClass(divId, 'selectable', selectable);
            this.addDiceRollClass(die);

            if (selectable) {
                document.getElementById(divId).addEventListener('click', event => this.dieClick(die, event));
            }
        });
    }

    public setDiceForSelectHeartAction(dice: Dice[], inTokyo: boolean) { 
        this.action = null;
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            this.createAndPlaceDiceHtml(die, inTokyo, `locked-dice${die.value}`);
            this.addDiceRollClass(die);
        });
    }

    setDiceForPsychicProbe(dice: Dice[], inTokyo: boolean, isCurrentPlayerActive: boolean) {
        this.action = 'psychicProbeRoll';

        /*if (this.dice.length) { if active, event are not reset and roll is not applied
            return;
        }*/

        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            this.createAndPlaceDiceHtml(die, inTokyo, `locked-dice${die.value}`);
            this.addDiceRollClass(die);

            if (isCurrentPlayerActive) {
                const divId = `dice${die.id}`;
                document.getElementById(divId).addEventListener('click', event => this.dieClick(die, event));
            }
        });

        dojo.toggleClass('rolled-dice', 'selectable', isCurrentPlayerActive);
    }

    public changeDie(dieId: number, inTokyo: boolean, toValue: number, roll?: boolean) {
        const die = this.dice.find(die => die.id == dieId);
        const divId = `dice${dieId}`;
        const div = document.getElementById(divId);
        if (div) {
            dojo.removeClass(div, `dice${div.dataset.diceValue}`);
            div.dataset.diceValue = ''+toValue;
            dojo.addClass(div, `dice${toValue}`);
            const list = div.getElementsByTagName('ol')[0];
            dojo.removeClass(list, 'no-roll');
            dojo.addClass(list, roll ? 'odd-roll' : 'change-die-roll');
            if (roll) {
                this.addDiceRollClass({
                    id: dieId,
                    rolled: roll
                } as Dice);
            }
            if (inTokyo) {
                if (die.value !== 4 && toValue === 4) {
                    dojo.place('<div class="icon forbidden"></div>', divId);
                } else if (die.value === 4 && toValue !== 4) {
                    Array.from(div.getElementsByClassName('forbidden')).forEach((elem: HTMLElement) => dojo.destroy(elem));
                }
            }
            list.dataset.roll = ''+toValue;
        }
        if (die) {
            die.value = toValue;
        }
    }

    showCamouflageRoll(dice: Dice[]) {
        this.clearDiceHtml();
        dice.forEach((dieValue, index) => {
            const die: Dice = {
                id: index,
                value: dieValue.value,
                extra: false,
                locked: false,
                rolled: dieValue.rolled,
            };
            this.createAndPlaceDiceHtml(die, false, `dice-selector`);
            this.addDiceRollClass(die);
        });
    }

    private clearDiceHtml() {        
        for (let i=1; i<=6; i++) {
            document.getElementById(`locked-dice${i}`).innerHTML = '';
        }
        document.getElementById(`dice-selector`).innerHTML = '';
    }

    public resolveNumberDice(args: NotifResolveNumberDiceArgs) {
        const dice = this.dice.filter(die => die.value === args.diceValue);
        (this.game as any).displayScoring( `dice${(dice[1] || dice[0]).id}`, '96c93c', args.deltaPoints, 1500);
        this.dice.filter(die => die.value === args.diceValue).forEach(die => this.removeDice(die, 1000, 1500));
    }

    public resolveHealthDiceInTokyo() {
        this.dice.filter(die => die.value === 4).forEach(die => this.removeDice(die, 1000));
    }

    private addDiceAnimation(diceValue: number, playerIds: number[], number?: number, targetToken?: TokenType) {
        let dice = this.dice.filter(die => die.value === diceValue && document.getElementById(`dice${die.id}`).dataset.animated !== 'true');
        if (number) {
            dice = dice.slice(0, number);
        }

        playerIds.forEach((playerId, playerIndex) => {
            const destination = document.getElementById(targetToken ? `token-wrapper-${playerId}-${targetToken}-token0` : `monster-figure-${playerId}`).getBoundingClientRect();
            const shift = targetToken ? 16 : 59;
            dice.forEach((die, dieIndex) => {
                const dieDiv = document.getElementById(`dice${die.id}`);
                dieDiv.dataset.animated = 'true';
                const origin = dieDiv.getBoundingClientRect();
                const animationId = `dice${die.id}-player${playerId}-animation`;
                dojo.place(`<div id="${animationId}" class="animation animation${diceValue}"></div>`, `dice${die.id}`);
                setTimeout(() => {
                    const middleIndex = dice.length - 1;
                    const deltaX = (dieIndex - middleIndex) * 220;
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, 100px) scale(1)`;
                }, 50);

                setTimeout(() => {
                    const deltaX = destination.left - origin.left + shift * this.game.getZoom();
                    const deltaY = destination.top - origin.top + shift * this.game.getZoom();
                    
                    document.getElementById(animationId).style.transition = `transform 0.5s ease-in`;
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, ${deltaY}px) scale(${0.3 * this.game.getZoom()})`;
                }, 1000);

                if (playerIndex === playerIds.length - 1) {
                    this.removeDice(die, 500, 2500);
                }
            });
        });
    }

    public resolveHealthDice(playerId: number, number: number, targetToken?: TokenType) {
        this.addDiceAnimation(4, [playerId], number, targetToken);
    }

    public resolveEnergyDice(args: NotifResolveEnergyDiceArgs) {
        this.addDiceAnimation(5, [args.playerId]);
    }

    public resolveSmashDice(args: NotifResolveSmashDiceArgs) {
        this.addDiceAnimation(6, args.smashedPlayersIds);
    }

    private toggleLockDice(die: Dice, event: MouseEvent, forcedLockValue: boolean | null = null) {
        if (event?.altKey || event?.ctrlKey) {
            let dice = [];
            
            if (event.ctrlKey && event.altKey) { // move everything but die.value dice
                dice = this.dice.filter(idie => idie.locked === die.locked && idie.value !== die.value);
            } else if (event.ctrlKey) { // move everything with die.value dice
                dice = this.dice.filter(idie => idie.locked === die.locked && idie.value === die.value);
            } else { // move everything but die
                dice = this.dice.filter(idie => idie.locked === die.locked && idie.id !== die.id);
            }

            dice.forEach(idie => this.toggleLockDice(idie, null));
            return;
        }


        die.locked = forcedLockValue === null ? !die.locked : forcedLockValue;
        const dieDivId = `dice${die.id}`;
        const dieDiv = document.getElementById(dieDivId);

        const destinationId = die.locked ? `locked-dice${die.value}` : `dice-selector`;
        const tempDestinationId = `temp-destination-wrapper-${destinationId}-${die.id}`;
        const tempOriginId = `temp-origin-wrapper-${destinationId}-${die.id}`;
        dojo.place(`<div id="${tempDestinationId}" style="width: 0px; height: ${dieDiv.clientHeight}px; display: inline-block; margin: 0;"></div>`, destinationId);
        dojo.place(`<div id="${tempOriginId}" style="width: ${dieDiv.clientWidth}px; height: ${dieDiv.clientHeight}px; display: inline-block; margin: -3px 6px 3px -3px;"></div>`, dieDivId, 'after');
        
        const destination = document.getElementById(destinationId);
        const tempDestination = document.getElementById(tempDestinationId);
        const tempOrigin = document.getElementById(tempOriginId);
        tempOrigin.appendChild(dieDiv);

        dojo.animateProperty({
            node: tempDestinationId,
            properties: {
                width: dieDiv.clientHeight,
            }
        }).play();
        dojo.animateProperty({
            node: tempOriginId,
            properties: {
                width: 0,
            }
        }).play();
        dojo.animateProperty({
            node: dieDivId,
            properties: {
                marginLeft: -13
            }
        }).play();
        slideToObjectAndAttach(this.game, dieDiv, tempDestinationId).then(() => {
            dieDiv.style.marginLeft = '3px';
            if (tempDestination.parentElement) { // we only attach if temp div still exists (not deleted)
                destination.append(tempDestination.childNodes[0]);
            }
            dojo.destroy(tempDestination);
            dojo.destroy(tempOrigin);
        });

        this.activateRethrowButton();
        this.game.checkBuyEnergyDrinkState();
        this.game.checkUseSmokeCloudState();
    }

    public lockAll() {
        this.dice?.filter(die => !die.locked).forEach(die => this.toggleLockDice(die, null, true));
    }

    private activateRethrowButton() {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.canRethrow());
        }
    }

    public canRethrow(): boolean {
        return this.dice.some(die => !die.locked);
    }

    private createAndPlaceDiceHtml(die: Dice, inTokyo: boolean, destinationId: string) {
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

        // security to destroy pre-existing die with same id
        const dieDiv = document.getElementById(`dice${die.id}`);
        dieDiv?.parentNode.removeChild(dieDiv);

        dojo.place(html, destinationId);
    }

    private getDiceDiv(die: Dice): HTMLDivElement {
        return document.getElementById(`dice${die.id}`) as HTMLDivElement;
    }

    private createDice(die: Dice, selectable: boolean, inTokyo: boolean) {
        this.createAndPlaceDiceHtml(die, inTokyo, die.locked ? `locked-dice${die.value}` : `dice-selector`);

        this.addDiceRollClass(die);

        if (selectable) {
            this.getDiceDiv(die).addEventListener('click', event => this.dieClick(die, event));
        }
    }

    private dieClick(die: Dice, event: MouseEvent) {
        if (this.action === 'move') {
            this.toggleLockDice(die, event);
        } else if (this.action === 'change') {
            this.toggleBubbleChangeDie(die);
        } else if (this.action === 'psychicProbeRoll') {
            this.game.psychicProbeRollDie(die.id);
        }
    }

    private addRollToDiv(dieDiv: HTMLDivElement, rollClass: string, attempt: number = 0) {
        const dieList = dieDiv.getElementsByClassName('die-list')[0];
        if (dieList) {
            dieList.classList.add(rollClass);
        } else if (attempt < 5) {
            setTimeout(() => this.addRollToDiv(dieDiv, rollClass, attempt + 1), 200); 
        }
    }

    private addDiceRollClass(die: Dice) {
        const dieDiv = this.getDiceDiv(die);

        if (die.rolled) {            
            dojo.removeClass(dieDiv, 'no-roll');
            dieDiv.classList.add('rolled');
            setTimeout(() => this.addRollToDiv(dieDiv, Math.random() < 0.5 ? 'odd-roll' : 'even-roll'), 200); 
            setTimeout(() => dieDiv.classList.remove('rolled'), 1200); 
        } else {
            this.addRollToDiv(dieDiv, 'no-roll');
        }
    }

    private removeDice(die: Dice, duration?: number, delay?: number) {
        if (duration) {
            (this.game as any).fadeOutAndDestroy(`dice${die.id}`, duration, delay);
        } else {
            const dieDiv = document.getElementById(`dice${die.id}`);
            dieDiv?.parentNode.removeChild(dieDiv);
        }
        this.dice.splice(this.dice.indexOf(die), 1);
    }

    private hideBubble(dieId: number) {
        const bubble = document.getElementById(`discussion_bubble_dice${dieId}`);
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    }

    public removeAllBubbles() {
        this.dieFaceSelectors = [];
        Array.from(document.getElementsByClassName('change-die-discussion_bubble')).forEach(elem => elem.parentElement.removeChild(elem));
    }

    private toggleBubbleChangeDie(die: Dice) {
        const divId = `dice${die.id}`;    
        console.log('exists ', `discussion_bubble_${divId}`, document.getElementById(`discussion_bubble_${divId}`));       
        if (!document.getElementById(`discussion_bubble_${divId}`)) { 
            dojo.place(`<div id="discussion_bubble_${divId}" class="discussion_bubble change-die-discussion_bubble"></div>`, divId);
        }
        const bubble = document.getElementById(`discussion_bubble_${divId}`);
        const visible = bubble.dataset.visible == 'true';

        if (visible) {
            this.hideBubble(die.id);
        } else {
            const bubbleActionButtonsId = `discussion_bubble_${divId}-action-buttons`;
            const bubbleDieFaceSelectorId = `discussion_bubble_${divId}-die-face-selector`;
            const creation = bubble.innerHTML == '';
            if (creation) {
                dojo.place(`
                <div id="${bubbleDieFaceSelectorId}" class="die-face-selector"></div>
                <div id="${bubbleActionButtonsId}" class="action-buttons"></div>
                `, bubble.id);
            }

            const herdCullerButtonId = `${bubbleActionButtonsId}-herdCuller`;
            const plotTwistButtonId = `${bubbleActionButtonsId}-plotTwist`;
            const stretchyButtonId = `${bubbleActionButtonsId}-stretchy`;

            const args = this.changeDieArgs;

            if (!this.dieFaceSelectors[die.id]) {
                this.dieFaceSelectors[die.id] = new DieFaceSelector(bubbleDieFaceSelectorId, die.value, args.inTokyo);
            }
            const dieFaceSelector = this.dieFaceSelectors[die.id];

            if (creation) {

                const buttonText = _("Change die face with ${card_name}");
                
                if (args.hasHerdCuller) {
                    this.game.createButton(
                        bubbleActionButtonsId, 
                        herdCullerButtonId, 
                        dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cards.getCardName(22, 'text-only')}</strong>` }),
                        () => {
                            this.game.changeDie(die.id, dieFaceSelector.getValue(), 22);
                            this.toggleBubbleChangeDie(die);
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
                            this.toggleBubbleChangeDie(die);
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
                            this.toggleBubbleChangeDie(die);
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

            if (die.value == dieFaceSelector.getValue()) {
                dieFaceSelector.reset(die.value);
                if (args.hasHerdCuller) {
                    dojo.addClass(herdCullerButtonId, 'disabled');
                }
                if (args.hasPlotTwist) {
                    dojo.addClass(plotTwistButtonId, 'disabled');
                }
                if (args.hasStretchy) {
                    dojo.addClass(stretchyButtonId, 'disabled');
                }
            }

            args.dice.filter(idie => idie.id != die.id).forEach(idie => this.hideBubble(idie.id));

            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
        
    }

}