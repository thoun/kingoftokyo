const SPACE_BETWEEN_ANIMATION_AT_START = 43;
const ANIMATION_FULL_SIZE = 220;

class AnimationManager {

    constructor(private game: KingOfTokyoGame, private diceManager: DiceManager) {
    }

    private getDice(dieValue: number) {
        const dice = this.diceManager.getDice();
        const filteredDice = this.getDiceShowingFace(dice, dieValue);
        return filteredDice.length ? filteredDice : dice;
    }

    public resolveNumberDice(args: NotifResolveNumberDiceArgs) {
        const dice = this.getDice(args.diceValue);
        (this.game as any).displayScoring( `dice${(dice[Math.floor(dice.length / 2)] || dice[0]).id}`, this.game.getPreferencesManager().getDiceScoringColor(), args.deltaPoints, 1500);
    }

    private getDiceShowingFace(allDice: Die[], face: number) {
        const dice = allDice.filter(die => !die.type && document.getElementById(`dice${die.id}`)?.dataset.animated !== 'true');

        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        } else {
            const berserkDice = this.diceManager.getBerserkDice();
            if (face == 5) { // energy
                return berserkDice.filter(die => die.value >= 1 && die.value <= 2 && document.getElementById(`dice${die.id}`)?.dataset.animated !== 'true');
            } else if (face == 6) { // smash
                return berserkDice.filter(die => die.value >= 3 && die.value <= 5 && document.getElementById(`dice${die.id}`)?.dataset.animated !== 'true');
            } else {
                return [];
            }
        }
    }

    private addDiceAnimation(diceValue: number, playerIds: number[], number: number, targetToken?: TokenType) {
        if (document.visibilityState === 'hidden' || (this.game as any).instantaneousMode) {
            return;
        }
        let dice = this.getDice(diceValue);

        const originTop = (document.getElementById(dice[0] ? `dice${dice[0].id}` : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect().top;
        const leftDieBR = (document.getElementById(dice[0] ? `dice${dice[0].id}` : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect();
        const rightDieBR = (document.getElementById(dice.length ? `dice${dice[dice.length - 1].id}` : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect();
        const originCenter = (leftDieBR.left + rightDieBR.right) / 2;

        playerIds.forEach(playerId => {
            const maxSpaces = SPACE_BETWEEN_ANIMATION_AT_START * number;
            const halfMaxSpaces = maxSpaces / 2;

            const shift = targetToken ? 16 : 59;
            for (let i=0; i<number; i++) {
                const originLeft = originCenter - halfMaxSpaces + SPACE_BETWEEN_ANIMATION_AT_START * i;
                const animationId = `animation${diceValue}-${i}-player${playerId}-${new Date().getTime()}`;
                dojo.place(`<div id="${animationId}" class="animation animation${diceValue}" style="left: ${originLeft + window.scrollX - 94}px; top: ${originTop + window.scrollY - 94}px;"></div>`, document.body);
                const animationDiv = document.getElementById(animationId);
                setTimeout(() => {
                    const middleIndex = number / 2;
                    const deltaX = (i - middleIndex) * ANIMATION_FULL_SIZE;
                    animationDiv.style.transform = `translate(${deltaX}px, 100px) scale(1)`;
                }, 50);

                setTimeout(() => {
                    let targetId = `monster-figure-${playerId}`;
                    if (targetToken) {
                        const tokensDivs = document.querySelectorAll(`div[id^='token-wrapper-${playerId}-${targetToken}-token'`);
                        targetId = tokensDivs[tokensDivs.length - (i + 1)].id;
                    }
                    let destination = document.getElementById(targetId).getBoundingClientRect();

                    const deltaX = destination.left - originLeft + shift * this.game.getZoom();
                    const deltaY = destination.top - originTop + shift * this.game.getZoom();

                    animationDiv.style.transition = `transform 0.5s ease-in`;
                    animationDiv.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(${0.3 * this.game.getZoom()})`;
                    animationDiv.addEventListener('transitionend', () => animationDiv?.parentElement?.removeChild(animationDiv));
                    // security
                    setTimeout(() => animationDiv?.parentElement?.removeChild(animationDiv), 1050);
                }, 1000);
            }
        });
    }

    public resolveHealthDice(playerId: number, number: number, targetToken?: TokenType) {
        this.addDiceAnimation(4, [playerId], number, targetToken);
    }

    public resolveEnergyDice(args: NotifResolveEnergyDiceArgs) {
        this.addDiceAnimation(5, [args.playerId], args.deltaEnergy);
    }

    public resolveSmashDice(args: NotifResolveSmashDiceArgs) {
        this.addDiceAnimation(6, args.smashedPlayersIds, args.number);
    }

}