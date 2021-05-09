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
        $('locked-dice').innerHTML = '';
        $('dice-selector').innerHTML = '';
        this.dice = [];
    }

    public setDiceForThrowDice(dice: Dice[], lastTurn: boolean, inTokyo: boolean) { 
        const currentPlayerActive = (this.game as any).isCurrentPlayerActive();

        this.dice?.forEach(die => this.removeDice(die));        
        $('locked-dice').innerHTML = '';
        $('dice-selector').innerHTML = '';
        this.dice = dice;

        const selectable = currentPlayerActive && !lastTurn;

        dice.forEach(die => this.createDice(die, true, selectable, inTokyo));

        dojo.toggleClass('rolled-dice', 'selectable', selectable);
    }

    public setDiceForChangeDie(dice: Dice[], args: EnteringChangeDieArgs, inTokyo: boolean) { 
        const currentPlayerActive = (this.game as any).isCurrentPlayerActive();

        this.dice?.forEach(die => this.removeDice(die));  
        $('dice-selector').innerHTML = '';
        this.dice = dice;
        
        const onlyHerdCuller = args.hasHerdCuller && !args.hasPlotTwist && !args.hasStretchy;
        dice.forEach(die => {
            const divId = `dice${die.id}`;
            dojo.place(this.createDiceHtml(die, inTokyo), 'dice-selector');
            const selectable = currentPlayerActive && (!onlyHerdCuller || die.value !== 1);
            dojo.toggleClass(divId, 'selectable', selectable);

            if (selectable) {
                dojo.place(`<div id="discussion_bubble_${divId}" class="discussion_bubble change-die-discussion_bubble"></div>`, divId);
                document.getElementById(divId).addEventListener('click', () => this.toggleBubbleChangeDie(die));
            }
        });
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
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.30)`;
                }, 1500);

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

        slideToObjectAndAttach(this.game, dieDiv, die.locked ? 'locked-dice' : 'dice-selector');

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

    private createDice(die: Dice, animated: boolean, selectable: boolean, inTokyo: boolean) {
        dojo.place(this.createDiceHtml(die, inTokyo), die.locked ? 'locked-dice' : 'dice-selector');

        const dieDiv = this.getDiceDiv(die);

        if (!die.locked && animated) {
            dieDiv.classList.add('rolled');
            setTimeout(() => dieDiv.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll'), 100); 
            setTimeout(() => dieDiv.classList.remove('rolled'), 1200); 
        } else {
            setTimeout(() => dieDiv.getElementsByClassName('die-list')[0].classList.add('no-roll'), 100); 
        }

        if (selectable) {
            dieDiv.addEventListener('click', () => this.toggleLockDice(die));
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

    private toggleBubbleChangeDie(die: Dice) {
        const bubble = document.getElementById(`discussion_bubble_dice${die.id}`);
        const visible = bubble.dataset.visible == 'true';

        if (visible) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
            
        } else {
            if (bubble.innerHTML == '') {
                dojo.place(`
                    <div>TODO show selectable die faces</div>
                    <div class="action-buttons">TODO buttons</div>
                `, bubble.id); // TODO activate buttons on conditions
            }

            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
        
    }

}