type DieClickAction = 'move' | 'change' | 'psychicProbeRoll' | 'discard' | 'rerollOrDiscard' | 'rerollDice' | 'freezeDie';

const DIE4_ICONS = [
    null,
    [1, 3, 2],
    [1, 2, 4],
    [1, 4, 3],
    [4, 3, 2],
];

const DICE_STRINGS = [null, '[dice1]', '[dice2]', '[dice3]', '[diceHeart]', '[diceEnergy]', '[diceSmash]'];
const BERSERK_DIE_STRINGS = [null, '[berserkDieEnergy]', '[berserkDieDoubleEnergy]', '[berserkDieSmash]', '[berserkDieSmash]', '[berserkDieDoubleSmash]', '[berserkDieSkull]'];

class DiceManager {
    private dice: Die[] = [];
    private dieFaceSelectors: DieFaceSelector[] = [];
    private action: DieClickAction;
    private changeDieArgs: EnteringChangeDieArgs;
    private selectedDice: Die[];

    constructor(private game: KingOfTokyoGame) {
    }

    public hideLock() {
        dojo.addClass('locked-dice', 'hide-lock');
    }
    public showLock() {
        dojo.removeClass('locked-dice', 'hide-lock');
    }

    public getDice() {
        return this.dice;
    }

    public getBerserkDice() {
        return this.dice.filter(die => die.type === 1);
    }

    public getLockedDice() {
        return this.dice.filter(die => die.locked);
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

    public setDiceForThrowDice(dice: Die[], selectableDice: Die[], canHealWithDice: boolean, frozenFaces: number[]) {
        this.action = 'move';
        this.dice?.forEach(die => this.removeDice(die));
        this.clearDiceHtml();
        this.dice = dice;

        dice.forEach(die => this.createDice(die, canHealWithDice, frozenFaces));
        this.setSelectableDice(selectableDice);
    }

    public disableDiceAction() {
        this.setSelectableDice();
        this.action = undefined;
    }

    private getLockedDiceId(die: Die) {
        return `locked-dice${this.getDieFace(die)}`;
    }

    public discardDie(die: Die) {
        this.removeDice(die, ANIMATION_MS);
    }

    public setDiceForChangeDie(dice: Die[], selectableDice: Die[], args: EnteringChangeDieArgs, canHealWithDice: boolean, frozenFaces: number[]) {
        this.action = args.hasHerdCuller || args.hasPlotTwist || args.hasStretchy || args.hasClown || args.hasSaurianAdaptability || args.hasGammaBreath || args.hasTailSweep || args.hasTinyTail || args.hasBiofuel || args.hasShrinky ? 'change' : null;
        this.changeDieArgs = args;

        if (this.dice.length) {
            this.setSelectableDice(selectableDice);
            return;
        }

        this.dice?.forEach(die => this.removeDice(die));  
        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, this.getLockedDiceId(die));
            this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    }

    public setDiceForDiscardDie(dice: Die[], selectableDice: Die[], canHealWithDice: boolean, frozenFaces: number[], action: DieClickAction = 'discard') {
        this.action = action;
        this.selectedDice = [];
        
        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, this.getLockedDiceId(die));
            this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    }

    public setDiceForSelectHeartAction(dice: Die[], selectableDice: Die[], canHealWithDice: boolean, frozenFaces: number[]) { 
        this.action = null;
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, this.getLockedDiceId(die));
            this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    }

    setDiceForPsychicProbe(dice: Die[], selectableDice: Die[], canHealWithDice: boolean, frozenFaces: number[]) {
        this.action = 'psychicProbeRoll';

        /*if (this.dice.length) { if active, event are not reset and roll is not applied
            this.setSelectableDice(selectableDice);
            return;
        }*/

        this.clearDiceHtml();
        this.dice = dice;
        
        dice.forEach(die => {
            this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, this.getLockedDiceId(die));
            this.addDiceRollClass(die);
        });

        this.setSelectableDice(selectableDice);
    }

    public changeDie(dieId: number, canHealWithDice: boolean, toValue: number, roll?: boolean) {
        const die = this.dice.find(die => die.id == dieId);
        const divId = `dice${dieId}`;
        const div = document.getElementById(divId);
        if (div) {
            dojo.removeClass(div, `dice${div.dataset.diceValue}`);
            div.dataset.diceValue = ''+toValue;
            dojo.addClass(div, `dice${toValue}`);
            const list = div.getElementsByTagName('ol')[0];
            list.dataset.rollType = roll ? 'odd' : 'change';
            if (roll) {
                this.addDiceRollClass({
                    id: dieId,
                    rolled: roll
                } as Die);
            }
            if (!canHealWithDice && !die.type) {
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

    showCamouflageRoll(dice: Die[]) {
        this.clearDiceHtml();
        dice.forEach((dieValue, index) => {
            const die: Die = {
                id: index,
                value: dieValue.value,
                extra: false,
                locked: false,
                rolled: dieValue.rolled,
                type: 0,
                canReroll: true,
            };
            this.createAndPlaceDiceHtml(die, true, [], `dice-selector`);
            this.addDiceRollClass(die);
        });
    }

    private clearDiceHtml() {
        const ids = [];      
        for (let i=1; i<=7; i++) {
            ids.push(`locked-dice${i}`);
        }
        ids.push(`locked-dice10`, `dice-selector`);
        ids.forEach(id => {
            const div = document.getElementById(id);
            if (div) {
                div.innerHTML = '';
            }
        });
    }

    public resolveNumberDice(args: NotifResolveNumberDiceArgs) {
        this.dice.filter(die => die.value === args.diceValue).forEach(die => this.removeDice(die, 1000, 1500));
    }

    public resolveHealthDiceInTokyo() {
        this.dice.filter(die => die.value === 4).forEach(die => this.removeDice(die, 1000));
    }

    private getDieFace(die: Die) {
        if (die.type === 2) {
            return 10;
        } else if (die.type === 1) {
            if (die.value <= 2) {
                return 5;
            } else if (die.value <= 5) {
                return 6;
            } else {
                return 7;
            }
        } else {
            return die.value;
        }
    }

    private getDiceShowingFace(face: number) {
        const dice = this.dice.filter(die => !die.type && die.value === face && document.getElementById(`dice${die.id}`)?.dataset.animated !== 'true');

        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        } else {
            const berserkDice = this.dice.filter(die => die.type === 1);
            if (face == 5) { // energy
                return berserkDice.filter(die => die.value >= 1 && die.value <= 2 && document.getElementById(`dice${die.id}`)?.dataset.animated !== 'true');
            } else if (face == 6) { // smash
                return berserkDice.filter(die => die.value >= 3 && die.value <= 5 && document.getElementById(`dice${die.id}`)?.dataset.animated !== 'true');
            } else {
                return [];
            }
        }
    }

    private addDiceAnimation(diceValue: number, number?: number) {
        let dice = this.getDiceShowingFace(diceValue);
        if (number) {
            dice = dice.slice(0, number);
        }
        dice.forEach(die => {
            document.getElementById(`dice${die.id}`).dataset.animated !== 'true';
            this.removeDice(die, 500, 2500);
        });
    }

    public resolveHealthDice(number: number) {
        this.addDiceAnimation(4, number);
    }

    public resolveEnergyDice() {
        this.addDiceAnimation(5);
    }

    public resolveSmashDice() {
        this.addDiceAnimation(6);
    }

    private toggleLockDice(die: Die, event: MouseEvent, forcedLockValue: boolean | null = null) {
        if (event?.altKey || event?.ctrlKey) {
            let dice = [];
            
            if (event.ctrlKey && event.altKey) { // move everything but die.value dice
                dice = this.dice.filter(idie => idie.locked === die.locked && this.getDieFace(idie) !== this.getDieFace(die));
            } else if (event.ctrlKey) { // move everything with die.value dice
                dice = this.dice.filter(idie => idie.locked === die.locked && this.getDieFace(idie) === this.getDieFace(die));
            } else { // move everything but die
                dice = this.dice.filter(idie => idie.locked === die.locked && idie.id !== die.id);
            }

            dice.forEach(idie => this.toggleLockDice(idie, null));
            return;
        }

        if (!die.canReroll) {
            return;
        }


        die.locked = forcedLockValue === null ? !die.locked : forcedLockValue;
        const dieDivId = `dice${die.id}`;
        const dieDiv = document.getElementById(dieDivId);
        dieDiv.dataset.rolled = 'false';

        const destinationId = die.locked ? this.getLockedDiceId(die) : `dice-selector`;
        const tempDestinationId = `temp-destination-wrapper-${destinationId}-${die.id}`;
        const tempOriginId = `temp-origin-wrapper-${destinationId}-${die.id}`;

        if (document.getElementById(destinationId)) {
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
        }

        this.activateRethrowButton();
        this.game.checkBuyEnergyDrinkState();
        this.game.checkUseSmokeCloudState();
        this.game.checkUseCultistState();
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

    private createAndPlaceDie4Html(die: Die, destinationId: string) {
        let html = `
        <div id="dice${die.id}" class="die4" data-dice-id="${die.id}" data-dice-value="${die.value}">
            <ol class="die-list" data-roll="${die.value}">`;
            for (let dieFace=1; dieFace<=4; dieFace++) {
                html += `<li class="face" data-side="${dieFace}">`;
                    DIE4_ICONS[dieFace].forEach(icon => html += `<span class="number face${icon}"><div class="anubis-icon anubis-icon${icon}"></div></span>`);
                html += `</li>`;
            }
        html += `    </ol>`;
        if (true) {
            html += `<div class="dice-icon die-of-fate"></div>`;
        }
        html += `</div>`;

        dojo.place(html, destinationId);

        (this.game as any).addTooltipHtml(`dice${die.id}`, `
        <strong>${_("Die of Fate effects")}</strong>
        <div><div class="anubis-icon anubis-icon1"></div> ${_("Change Curse: Discard the current Curse and reveal the next one.")}</div>
        <div><div class="anubis-icon anubis-icon2"></div> ${_("No effect. The card's permanent effect remains active, however.")}</div>
        <div><div class="anubis-icon anubis-icon3"></div> ${_("Suffer the Snake effect.")}</div>
        <div><div class="anubis-icon anubis-icon4"></div> ${_("Receive the blessing of the Ankh effect.")}</div>
        `);
    }

    private createAndPlaceDie6Html(die: Die, canHealWithDice: boolean, frozenFaces: number[], destinationId: string) {
        let html = `<div id="dice${die.id}" class="dice dice${die.value}" data-dice-id="${die.id}" data-dice-value="${die.value}">
        <ol class="die-list" data-roll="${die.value}">`;
        const colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        for (let dieFace=1; dieFace<=6; dieFace++) {
            html += `<li class="die-item ${colorClass} side${dieFace}" data-side="${dieFace}"></li>`;
        }
        html += `</ol>`;
        if (!die.type && frozenFaces?.includes(die.value)) {
            html += `<div class="icon frozen"></div>`;
        } else if (!die.type && die.value === 4 && !canHealWithDice) {
            html += `<div class="icon forbidden"></div>`;
        }
        if (!die.canReroll) {
            html += `<div class="icon lock"></div>`;
        }
        html += `</div>`;

        // security to destroy pre-existing die with same id
        const dieDiv = document.getElementById(`dice${die.id}`);
        dieDiv?.parentNode.removeChild(dieDiv);

        dojo.place(html, destinationId);
    }


    private createAndPlaceDiceHtml(die: Die, canHealWithDice: boolean, frozenFaces: number[], destinationId: string) {
        if (die.type == 2) {
            this.createAndPlaceDie4Html(die, destinationId);
        } else {
            this.createAndPlaceDie6Html(die, canHealWithDice, frozenFaces, destinationId);
        }

        this.getDieDiv(die).addEventListener('click', event => this.dieClick(die, event));
    }

    private getDieDiv(die: Die): HTMLDivElement {
        return document.getElementById(`dice${die.id}`) as HTMLDivElement;
    }

    private createDice(die: Die, canHealWithDice: boolean, frozenFaces) {
        this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, die.locked ? this.getLockedDiceId(die) : `dice-selector`);

        const div = this.getDieDiv(die);
        div.addEventListener('animationend', (e: AnimationEvent) => {
            if (e.animationName == 'rolled-dice') {
                div.dataset.rolled = 'false';
            }
        });

        this.addDiceRollClass(die);
    }

    private dieClick(die: Die, event: MouseEvent) {
        if (this.action === 'move') {
            this.toggleLockDice(die, event);
        } else if (this.action === 'change') {
            this.toggleBubbleChangeDie(die);
        } else if (this.action === 'psychicProbeRoll') {
            this.game.psychicProbeRollDie(die.id);
        } else if (this.action === 'discard') {
            this.game.discardDie(die.id);
        } else if (this.action === 'rerollOrDiscard') {
            this.game.rerollOrDiscardDie(die.id);
        } else if (this.action === 'rerollDice') {
            if (die.type < 2) {
                dojo.toggleClass(this.getDieDiv(die), 'die-selected');
                const selectedDieIndex = this.selectedDice.findIndex(d => d.id == die.id);
                if (selectedDieIndex !== -1) {
                    this.selectedDice.splice(selectedDieIndex, 1);
                } else {
                    this.selectedDice.push(die);
                }
                this.game.toggleRerollDiceButton();
            }
        } else if (this.action === 'freezeDie') {
            this.game.freezeDie(die.id);
        }
    }

    public getSelectedDiceIds() {
        return this.selectedDice.map(die => die.id);
    }
    
    public removeSelection() {
        this.selectedDice.forEach(die => dojo.removeClass(this.getDieDiv(die), 'die-selected'));
        this.selectedDice = [];
    }

    private addRollToDiv(dieDiv: HTMLDivElement, rollType: string, attempt: number = 0) {
        const dieList = (dieDiv.getElementsByClassName('die-list')[0] as HTMLDivElement);
        if (dieList) {
            dieList.dataset.rollType = rollType;
        } else if (attempt < 5) {
            setTimeout(() => this.addRollToDiv(dieDiv, rollType, attempt + 1), 200); 
        }
    }

    private addDiceRollClass(die: Die) {
        const dieDiv = this.getDieDiv(die);

        dieDiv.dataset.rolled = die.rolled ? 'true' : 'false';
        if (die.rolled) {            
            setTimeout(() => this.addRollToDiv(dieDiv, Math.random() < 0.5 && die.type != 2 ? 'odd' : 'even'), 200); 
        } else {
            this.addRollToDiv(dieDiv, '-');
        }
    }

    private removeDice(die: Die, duration?: number, delay?: number) {
        this.dice.splice(this.dice.findIndex(d => d.id == die.id), 1);
        if (duration) {
            (this.game as any).fadeOutAndDestroy(`dice${die.id}`, duration, delay);
        } else {
            const dieDiv = document.getElementById(`dice${die.id}`);
            dieDiv?.parentNode.removeChild(dieDiv);
        }
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

    private toggleBubbleChangeDie(die: Die) {
        if (die.type === 2) {
            // die of fate cannot be changed by power cards
            return;
        }
        const divId = `dice${die.id}`;
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
            const gammaBreathButtonId = `${bubbleActionButtonsId}-gammaBreath`;
            const tailSweepButtonId = `${bubbleActionButtonsId}-tailSweep`;
            const tinyTailButtonId = `${bubbleActionButtonsId}-tinyTail`;
            const plotTwistButtonId = `${bubbleActionButtonsId}-plotTwist`;
            const stretchyButtonId = `${bubbleActionButtonsId}-stretchy`;
            const biofuelButtonId = `${bubbleActionButtonsId}-biofuel`;
            const shrinkyButtonId = `${bubbleActionButtonsId}-shrinky`;
            const saurianAdaptabilityButtonId = `${bubbleActionButtonsId}-saurianAdaptability`;
            const clownButtonId = `${bubbleActionButtonsId}-clown`;

            const args = this.changeDieArgs;

            if (!this.dieFaceSelectors[die.id]) {
                this.dieFaceSelectors[die.id] = new DieFaceSelector(bubbleDieFaceSelectorId, die, args.canHealWithDice);
            }
            const dieFaceSelector = this.dieFaceSelectors[die.id];

            if (creation) {

                const buttonText = _("Change die face with ${card_name}");
                
                if (args.hasClown) {
                    this.game.createButton(
                        bubbleActionButtonsId, 
                        clownButtonId, 
                        dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cardsManager.getCardName(212, 'text-only')}</strong>` }),
                        () => {
                            this.game.changeDie(die.id, dieFaceSelector.getValue(), 212),
                            this.toggleBubbleChangeDie(die);
                        },
                        true
                    );
                } else {
                    if (args.hasHerdCuller) {
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            herdCullerButtonId, 
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cardsManager.getCardName(22, 'text-only')}</strong>` }),
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 22);
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                    if (args.hasGammaBreath) {
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            gammaBreathButtonId, 
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.evolutionCardsManager.getCardName(57, 'text-only')}</strong>` }),
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 3057);
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                    if (args.hasTailSweep) {
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            tailSweepButtonId, 
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.evolutionCardsManager.getCardName(58, 'text-only')}</strong>` }),
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 3058);
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                    if (args.hasTinyTail) {
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            tinyTailButtonId, 
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.evolutionCardsManager.getCardName(184, 'text-only')}</strong>` }),
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 3058);
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                    if (args.hasPlotTwist) {
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            plotTwistButtonId, 
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cardsManager.getCardName(33, 'text-only')}</strong>` }),
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
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cardsManager.getCardName(44, 'text-only')}</strong>` }) + formatTextIcons(' (2 [Energy])'),
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 44),
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                    if (args.hasBiofuel) {
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            biofuelButtonId, 
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cardsManager.getCardName(56, 'text-only')}</strong>` }),
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 56),
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                    if (args.hasShrinky) {
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            shrinkyButtonId, 
                            dojo.string.substitute(buttonText, {'card_name': `<strong>${this.game.cardsManager.getCardName(65, 'text-only')}</strong>` }),
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 65),
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                    if (args.hasSaurianAdaptability) {
                        const saurianAdaptabilityButtonLabel = dojo.string.substitute(_("Change all ${die_face} with ${card_name}"), {
                            'card_name': `<strong>${this.game.evolutionCardsManager.getCardName(54, 'text-only')}</strong>`, 
                            'die_face': formatTextIcons(DICE_STRINGS[die.value]),
                        });
                        this.game.createButton(
                            bubbleActionButtonsId, 
                            saurianAdaptabilityButtonId, 
                            saurianAdaptabilityButtonLabel,
                            () => {
                                this.game.changeDie(die.id, dieFaceSelector.getValue(), 3054),
                                this.toggleBubbleChangeDie(die);
                            },
                            true
                        );
                    }
                }

                dieFaceSelector.onChange = value => {
                    if (args.hasClown) {
                        dojo.toggleClass(clownButtonId, 'disabled', value < 1);
                    } else {
                        if (args.hasHerdCuller && die.value != 1) {
                            dojo.toggleClass(herdCullerButtonId, 'disabled', value != 1);
                        }
                        if (args.hasGammaBreath && die.value != 6) {
                            dojo.toggleClass(gammaBreathButtonId, 'disabled', value != 6);
                        }
                        if (args.hasTailSweep) {
                            dojo.toggleClass(tailSweepButtonId, 'disabled', value != 1 && value != 2);
                        }
                        if (args.hasTinyTail && die.value != 1) {
                            dojo.toggleClass(tinyTailButtonId, 'disabled', value != 1);
                        }
                        if (args.hasPlotTwist) {
                            dojo.toggleClass(plotTwistButtonId, 'disabled', value < 1);
                        }
                        if (args.hasStretchy) {
                            const couldUseStretchy = value >= 1;
                            dojo.toggleClass(stretchyButtonId, 'disabled', !couldUseStretchy || this.game.getPlayerEnergy(args.playerId) < 2);
                            if (couldUseStretchy) {
                                document.getElementById(stretchyButtonId).dataset.enableAtEnergy = '2';
                            } else {
                                document.getElementById(stretchyButtonId).removeAttribute('data-enable-at-energy');
                            }
                        }
                        if (args.hasBiofuel && die.value == 4) {
                            dojo.toggleClass(biofuelButtonId, 'disabled', value != 5);
                        }
                        if (args.hasShrinky && die.value == 2) {
                            dojo.toggleClass(shrinkyButtonId, 'disabled', value != 1);
                        }
                        if (args.hasSaurianAdaptability) {
                            dojo.removeClass(saurianAdaptabilityButtonId, 'disabled');
                        }
                    }
                };

                bubble.addEventListener('click', event => event.stopImmediatePropagation());
            }

            if (die.value == dieFaceSelector.getValue()) {
                dieFaceSelector.reset(die.value);
                
                if (args.hasClown) {
                    dojo.addClass(stretchyButtonId, 'disabled');
                } else {
                    if (args.hasHerdCuller) {
                        dojo.addClass(herdCullerButtonId, 'disabled');
                    }
                    if (args.hasGammaBreath) {
                        dojo.addClass(gammaBreathButtonId, 'disabled');
                    }
                    if (args.hasTailSweep) {
                        dojo.addClass(tailSweepButtonId, 'disabled');
                    }
                    if (args.hasTinyTail) {
                        dojo.addClass(tinyTailButtonId, 'disabled');
                    }
                    if (args.hasPlotTwist) {
                        dojo.addClass(plotTwistButtonId, 'disabled');
                    }
                    if (args.hasStretchy) {
                        dojo.addClass(stretchyButtonId, 'disabled');
                    }
                    if (args.hasSaurianAdaptability) {
                        dojo.addClass(saurianAdaptabilityButtonId, 'disabled');
                    }
                    if (args.hasBiofuel) {
                        dojo.addClass(biofuelButtonId, 'disabled');
                    }
                    if (args.hasShrinky) {
                        dojo.addClass(shrinkyButtonId, 'disabled');
                    }
                }
            }

            args.dice.filter(idie => idie.id != die.id).forEach(idie => this.hideBubble(idie.id));

            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
        
    }

    private setSelectableDice(selectableDice: Die[] = null) {
        const playerIsActive = (this.game as any).isCurrentPlayerActive();
        this.dice.forEach(die => this.getDieDiv(die).classList.toggle('selectable', playerIsActive && selectableDice?.some(d => d.id == die.id)));
    }

}