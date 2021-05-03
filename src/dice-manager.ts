class DiceManager {
    private dices: Dice[] = [];

    constructor(private game: KingOfTokyoGame, setupDices: Dice[]) {
        // TODO use setupDices
    }

    public hideLock() {
        dojo.addClass('locked-dices', 'hide-lock');
    }
    public showLock() {
        dojo.removeClass('locked-dices', 'hide-lock');
    }
    
    public destroyFreeDices(): number[] {
        const freeDices = this.dices.filter(dice => !dice.locked);
        freeDices.forEach(dice => this.removeDice(dice));
        return freeDices.map(dice => dice.id);
    }

    public removeAllDices() {
        this.dices.forEach(dice => this.removeDice(dice));
    }

    public lockFreeDices() {
        this.dices.filter(dice => !dice.locked).forEach(dice => this.toggleLockDice(dice, true));
    }

    public setDices(dices: Dice[], firstThrow: boolean, lastTurn: boolean) { 
        if (firstThrow) {
            $('dices-selector').innerHTML = '';
            this.dices = [];
        }

        const newDices = dices.filter(newDice => !this.dices.some(dice => dice.id === newDice.id));
        this.dices.push(...newDices);

        const selectable = (this.game as any).isCurrentPlayerActive() && !lastTurn;

        newDices.forEach(dice => this.createDice(dice, true, selectable));

        dojo.toggleClass('rolled-dices', 'selectable', selectable);

        if (lastTurn) {
            setTimeout(() => this.lockFreeDices(), 1000);
        }

        this.activateRethrowButton();
    }

    public resolveNumberDices(args: NotifResolveNumberDiceArgs) {
        // TODO animation
        this.dices.filter(dice => dice.value === args.diceValue).forEach(dice => this.removeDice(dice));
    }

    public resolveHealthDicesInTokyo(args: NotifResolveHealthDiceInTokyoArgs) {
        // TODO animation
        this.dices.filter(dice => dice.value === 4).forEach(dice => this.removeDice(dice));
    }

    public resolveHealthDices(args: NotifResolveHealthDiceArgs) {
        // TODO animation
        this.dices.filter(dice => dice.value === 4).forEach(dice => this.removeDice(dice));
    }

    public resolveEnergyDices(args: NotifResolveEnergyDiceArgs) {
        // TODO animation
        this.dices.filter(dice => dice.value === 5).forEach(dice => this.removeDice(dice));
    }

    public resolveSmashDices(args: NotifResolveSmashDiceArgs) {
        // TODO animation
        this.dices.filter(dice => dice.value === 6).forEach(dice => this.removeDice(dice));
    }

    private toggleLockDice(dice: Dice, forcedLockValue: boolean | null = null) {
        dice.locked = forcedLockValue === null ? !dice.locked : forcedLockValue;
        const diceDiv = document.getElementById(`dice${dice.id}`);

        slideToObjectAndAttach(this.game, diceDiv, dice.locked ? 'locked-dices' : 'dices-selector');

        this.activateRethrowButton();
    }

    private activateRethrowButton() {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.dices.filter(dice => !dice.locked).length);
        }
    }

    private createDiceHtml(dice: Dice) {
        let html = `<div id="dice${dice.id}" class="dice dice${dice.value}" data-dice-id="${dice.id}" data-dice-value="${dice.value}">
        <ol class="die-list" data-roll="${dice.value}">`;
        for (let die=1; die<=6; die++) {
            html += `<li class="die-item ${dice.extra ? 'green' : 'black'} side${die}" data-side="${die}"></li>`;
        }
        html += `</ol></div>`;
        return html;
    }

    private createDice(dice: Dice, animated: boolean, selectable: boolean) {
        dojo.place(this.createDiceHtml(dice), dice.locked ? 'locked-dices' : 'dices-selector');
        // TODO if player is in tokyo, add symbol &#x1f6ab; on heart dices

        const diceDiv = document.getElementById(`dice${dice.id}`);

        if (!dice.locked && animated) {
            diceDiv.classList.add('rolled');
            setTimeout(() => diceDiv.getElementsByClassName('die-list')[0].classList.add(Math.random() < 0.5 ? 'odd-roll' : 'even-roll'), 100); 
            setTimeout(() => diceDiv.classList.remove('rolled'), 1200); 
        }

        if (selectable) {
            diceDiv.addEventListener('click', () => this.toggleLockDice(dice));
        }

    }

    private removeDice(dice: Dice) {
        dojo.destroy(`dice${dice.id}`);
        this.dices.splice(this.dices.indexOf(dice), 1);
    }

}