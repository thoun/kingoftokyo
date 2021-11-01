class AnimationManager {

    constructor(private game: KingOfTokyoGame, private diceManager: DiceManager) {
    }

    private getDice(dieValue: number) {
        const dice = this.diceManager.getDice();
        const filteredDice = dice.filter(die => die.value === dieValue);
        return filteredDice.length ? filteredDice : dice;
    }

    public resolveNumberDice(args: NotifResolveNumberDiceArgs) {
        const dice = this.getDice(args.diceValue);
        (this.game as any).displayScoring( `dice${(dice[Math.floor(dice.length / 2)] || dice[0]).id}`, this.game.getPreferencesManager().getDiceScoringColor(), args.deltaPoints, 1500);
    }

    private getDiceShowingFace(face: number) {
        const dice = this.getDice(face).filter(die => !die.type && document.getElementById(`dice${die.id}`).dataset.animated !== 'true');

        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        } else {
            const berserkDice = this.diceManager.getBerserkDice();
            if (face == 5) { // energy
                return berserkDice.filter(die => die.value >= 1 && die.value <= 2 && document.getElementById(`dice${die.id}`).dataset.animated !== 'true');
            } else if (face == 6) { // smash
                return berserkDice.filter(die => die.value >= 3 && die.value <= 5 && document.getElementById(`dice${die.id}`).dataset.animated !== 'true');
            } else {
                return [];
            }
        }
    }

    private addDiceAnimation(diceValue: number, playerIds: number[], number?: number, targetToken?: TokenType) {
        let dice = this.getDiceShowingFace(diceValue);
        if (number) {
            dice = dice.slice(0, number);
        }

        playerIds.forEach((playerId, playerIndex) => {

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
                    let targetId = `monster-figure-${playerId}`;
                    if (targetToken) {
                        const tokensDivs = document.querySelectorAll(`div[id^='token-wrapper-${playerId}-${targetToken}-token'`);
                        targetId = tokensDivs[tokensDivs.length - (dieIndex + 1)].id;
                    }
                    let destination = document.getElementById(targetId).getBoundingClientRect();

                    const deltaX = destination.left - origin.left + shift * this.game.getZoom();
                    const deltaY = destination.top - origin.top + shift * this.game.getZoom();

                    document.getElementById(animationId).style.transition = `transform 0.5s ease-in`;
                    document.getElementById(animationId).style.transform = `translate(${deltaX}px, ${deltaY}px) scale(${0.3 * this.game.getZoom()})`;
                }, 1000);

                if (playerIndex === playerIds.length - 1) {
                    // TODO this.removeDice(die, 500, 2500);
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

}