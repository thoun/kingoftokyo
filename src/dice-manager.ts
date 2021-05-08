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
        const freeDice = this.dice.filter(dice => !dice.locked);
        freeDice.forEach(dice => this.removeDice(dice));
        return freeDice.map(dice => dice.id);
    }

    public removeAllDice() {
        console.log('removeAllDice', this.dice);
        this.dice.forEach(dice => this.removeDice(dice));
        $('locked-dice').innerHTML = '';
        $('dice-selector').innerHTML = '';
        this.dice = [];
    }

    public setDice(dice: Dice[], firstThrow: boolean, lastTurn: boolean, inTokyo: boolean) { 
        const currentPlayerActive = (this.game as any).isCurrentPlayerActive();

        if (firstThrow) {
            $('dice-selector').innerHTML = '';
            this.dice = [];
        } else {
            this.dice.forEach(dice => this.removeDice(dice));
            $('locked-dice').innerHTML = '';
            $('dice-selector').innerHTML = '';
            this.dice = [];
        }

        const newDice = dice.filter(newDice => !this.dice.some(dice => dice.id === newDice.id));
        //const oldDice = this.dice.filter(oldDice => !newDice.some(dice => dice.id === oldDice.id));
        this.dice.push(...newDice);

        /*oldDice.forEach(dice => {
            const newDice = dice.find(nd => nd.id === dice.id);
            if (newDice) {
                dice.value = newDice.value;
                dice.locked = newDice.locked;
                const div = document.getElementById(`dice${dice.id}`);
                div.dataset.diceValue = ''+dice.value;
            }
        });*/

        const selectable = currentPlayerActive && !lastTurn;

        newDice.forEach(dice => this.createDice(dice, true, selectable, inTokyo));

        dojo.toggleClass('rolled-dice', 'selectable', selectable);

        //this.dice.forEach(dice => this.toggleLockDice(dice, dice.locked));

        this.activateRethrowButton();
    }

    public resolveNumberDice(args: NotifResolveNumberDiceArgs) {
        const dice = this.dice.filter(dice => dice.value === args.diceValue);
        (this.game as any).displayScoring( `dice${(dice[1] || dice[0]).id}`, '96c93c', args.deltaPoints, 1500);
        this.dice.filter(dice => dice.value === args.diceValue).forEach(dice => this.removeDice(dice, 1000, 1500));
    }

    public resolveHealthDiceInTokyo() {
        this.dice.filter(dice => dice.value === 4).forEach(dice => this.removeDice(dice, 1000));
    }

    private addDiceAnimation(diceValue: number, playerIds: number[]) {
        const dice = this.dice.filter(dice => dice.value === diceValue);
        playerIds.forEach((playerId, playerIndex) => {
            const destination = document.getElementById(`monster-figure-${playerId}`).getBoundingClientRect();
            dice.forEach((dice, diceIndex) => {
                const origin = document.getElementById(`dice${dice.id}`).getBoundingClientRect();
                const animationId = `dice${dice.id}-player${playerId}-animation`;
                dojo.place(`<div id="${animationId}" class="animation animation${diceValue}"></div>`, `dice${dice.id}`);
                setTimeout(() => {
                    const middleIndex = dice.length - 1;
                    const deltaX = (diceIndex - middleIndex) * 220;
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, 100px) scale(1)`;
                }, 50);

                setTimeout(() => {
                    const deltaX = destination.left - origin.left + 59;
                    const deltaY = destination.top - origin.top + 59;
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.30)`;
                }, 1500);

                if (playerIndex === playerIds.length - 1) {
                    setTimeout(() => this.removeDice(dice), 2500);
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

    private toggleLockDice(dice: Dice, forcedLockValue: boolean | null = null) {
        dice.locked = forcedLockValue === null ? !dice.locked : forcedLockValue;
        const diceDiv = document.getElementById(`dice${dice.id}`);

        slideToObjectAndAttach(this.game, diceDiv, dice.locked ? 'locked-dice' : 'dice-selector');

        this.activateRethrowButton();
    }

    private activateRethrowButton() {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.dice.filter(dice => !dice.locked).length);
        }
    }

    private createDiceHtml(dice: Dice, inTokyo: boolean) {
        let html = `<div id="dice${dice.id}" class="dice dice${dice.value}" data-dice-id="${dice.id}" data-dice-value="${dice.value}">
        <ol class="die-list" data-roll="${dice.value}">`;
        for (let die=1; die<=6; die++) {
            html += `<li class="die-item ${dice.extra ? 'green' : 'black'} side${die}" data-side="${die}"></li>`;
        }
        html += `</ol>`;
        if (dice.value === 4 && inTokyo) {            
            html += `<div class="icon forbidden"></div>`;
        }
        html += `</div>`;
        return html;
    }

    private getDiceDiv(dice: Dice) {
        return document.getElementById(`dice${dice.id}`);
    }

    private createDice(dice: Dice, animated: boolean, selectable: boolean, inTokyo: boolean) {
        dojo.place(this.createDiceHtml(dice, inTokyo), dice.locked ? 'locked-dice' : 'dice-selector');

        const diceDiv = this.getDiceDiv(dice);

        if (!dice.locked && animated) {
            diceDiv.classList.add('rolled');
            setTimeout(() => diceDiv.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll'), 100); 
            setTimeout(() => diceDiv.classList.remove('rolled'), 1200); 
        } else {
            setTimeout(() => diceDiv.getElementsByClassName('die-list')[0].classList.add('no-roll'), 100); 
        }

        if (selectable) {
            diceDiv.addEventListener('click', () => this.toggleLockDice(dice));
        }

    }

    private removeDice(dice: Dice, duration?: number, delay?: number) {
        if (duration) {
            (this.game as any).fadeOutAndDestroy(`dice${dice.id}`, duration, delay);
        } else {
            dojo.destroy(`dice${dice.id}`);
        }
        this.dice.splice(this.dice.indexOf(dice), 1);
    }

}