class MonsterGroup {
    constructor(public monsters: number[], public title: string, public color: string) {}
}

class MonsterSelector {
    private BONUS_GROUP = new MonsterGroup([], _('Bonus'), '#ffffff');

    private MONSTER_GROUPS: MonsterGroup[];

    constructor(private game: KingOfTokyoGame) {
        this.MONSTER_GROUPS = [
            new MonsterGroup([1,2,3,4,5,6, 102,104,105,106,114,115], this.game.isDarkEdition() ? 'King of Tokyo Dark Edition' : 'King of Tokyo', '#ffcf13'),
            new MonsterGroup([7,8], _('Halloween expansion'), '#ff8200'),
            new MonsterGroup([18], _('Monster Box exclusive'), '#dd4271'),
            new MonsterGroup([9,10,11,12], _('Monster Packs'), '#a9e9ae'),
            new MonsterGroup([13], _('Power-Up! expansion'), '#5d7b38'),
            new MonsterGroup([21,22,23,24,25,26], 'King of New-York', '#645195'),
            new MonsterGroup([41, 42, 43, 44, 45], 'King of Monster Island', '#e82519'),
            new MonsterGroup([51,52,53,54], _('King of Tokyo Origins'), '#f78d33'),
        ];
    }

    public onEnteringPickMonster(args: EnteringPickMonsterArgs) {
        // TODO clean only needed
        let html = ``;

        const bonusMonsters = args.availableMonsters.filter(monster => !this.MONSTER_GROUPS.some(monsterGroup => monsterGroup.monsters.includes(monster)));

        [...this.MONSTER_GROUPS, this.BONUS_GROUP].filter(group => {
            const bonus = !group.monsters.length;
            return args.availableMonsters.some(monster => (bonus ? bonusMonsters : group.monsters).includes(monster));
        }).forEach(group => {
            const bonus = !group.monsters.length;
            
            html += `
            <div class="monster-group">
                <div class="title" style="--title-color: ${group.color};">${group.title}</div>      
                <div class="monster-group-monsters">`;

                const groupMonsters = args.availableMonsters.filter(monster => (bonus ? bonusMonsters : group.monsters).includes(monster));

                groupMonsters.forEach(monster => {
                    html += `
                    <div id="pick-monster-figure-${monster}-wrapper">
                        <div id="pick-monster-figure-${monster}" class="monster-figure monster${monster}"></div>`;
                    if (this.game.isPowerUpExpansion()) {
                        html += `<div><button id="see-monster-evolution-${monster}" class="bgabutton bgabutton_blue see-evolutions-button"><div class="player-evolution-card"></div>${_('Show Evolutions')}</button></div>`;
                    }
                    html += `</div>`;
                });
                
                html += `    </div>      
            </div>
            `;
        });

        document.getElementById('monster-pick').innerHTML = html;
        args.availableMonsters.forEach(monster => {
            document.getElementById(`pick-monster-figure-${monster}`).addEventListener('click', () => this.game.pickMonster(monster));
            if (this.game.isPowerUpExpansion()) {
                document.getElementById(`see-monster-evolution-${monster}`).addEventListener('click', () => this.showMonsterEvolutions(monster % 100));
            }
        });

        const isCurrentPlayerActive = (this.game as any).isCurrentPlayerActive();
        dojo.toggleClass('monster-pick', 'selectable', isCurrentPlayerActive);
    }
    
    private showMonsterEvolutions(monster: number) {
        const cardsTypes = [];
        for (let i=1; i<=8; i++) {
            cardsTypes.push(monster * 10 + i);
        }

        this.game.showEvolutionsPopin(cardsTypes, _("Monster Evolution cards"));
    }

}