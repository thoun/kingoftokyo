const RULEBOOK_LINKS = [
    { // base game
        'en': 'https://cdn.shopify.com/s/files/1/0049/3351/7425/files/KOT2-rulebook_EN.pdf?1387',
        'fr': 'https://iello.fr/regles/regles_KOTv2.pdf',
    },
    { // halloween
        'en': 'https://www.fgbradleys.com/rules/rules6/King%20of%20Tokyo%20Halloween%20-%20rules.pdf',
        'fr': 'https://www.iello.fr/regles/KOT_HALLOWEEN_regles.pdf',
    },
    { // cthlhu
        'en': 'https://cdn.1j1ju.com/medias/47/0e/7f-king-of-tokyo-new-york-monster-pack-cthulhu-rulebook.pdf',
        'fr': 'https://www.play-in.com/pdf/rules_games/monster_pack_cthulhu_-_extension_king_of_tokyo_regles_fr.pdf',
    },
    { // king kong
        'en': 'https://www.iello.fr/regles/KOT_KingKong-US-Rules.pdf',
        'fr': 'http://iello.fr/regles/KOT_KONG_regles.pdf',
    },
    { // anubis
        'en': 'http://iello.fr/regles/KOT-Anubis-rulebook-EN.pdf',
        'fr': 'http://iello.fr/regles/51530_regles.pdf',
    },
    { // cybertooth
        'en': 'https://cdn.1j1ju.com/medias/6f/b6/07-king-of-tokyo-new-york-monster-pack-cybertooth-rulebook.pdf',
        'fr': 'https://cdn.1j1ju.com/medias/80/e7/99-king-of-tokyo-new-york-monster-pack-cybertooth-regle.pdf',
    },
    { // Even more wicked
        'en': 'https://boardgamegeek.com/filepage/241513/english-rulebook',
        'fr': 'https://iello.fr/regles/KOT_mechancete_Rules_FR.pdf',
    },
    { // Power-Up!
        'en': 'https://cdn.1j1ju.com/medias/69/8c/32-king-of-tokyo-power-up-rulebook.pdf',
        'fr': 'https://cdn.1j1ju.com/medias/8c/62/83-king-of-tokyo-power-up-regle.pdf',
    },
    { // Dark edition
        'en': 'https://cdn.1j1ju.com/medias/53/d4/2e-king-of-tokyo-dark-edition-rulebook.pdf',
        'fr': 'http://iello.fr/regles/KOT%20DARK_rulebook.pdf',
    },
];
const EXPANSION_NUMBER = 8;

class ActivatedExpansionsPopin {
    public activatedExpansions: number[] = [];

    constructor(private gamedatas: KingOfTokyoGamedatas, private language: string = 'en') {

        if (this.gamedatas.halloweenExpansion) {
            this.activatedExpansions.push(1);
        }
        if (this.gamedatas.cthulhuExpansion) {
            this.activatedExpansions.push(2);
        }
        if (this.gamedatas.kingkongExpansion) {
            this.activatedExpansions.push(3);
        }
        if (this.gamedatas.anubisExpansion) {
            this.activatedExpansions.push(4);
        }
        if (this.gamedatas.cybertoothExpansion) {
            this.activatedExpansions.push(5);
        }
        if (this.gamedatas.wickednessExpansion) {
            this.activatedExpansions.push(6);
        }
        if (this.gamedatas.powerUpExpansion) {
            this.activatedExpansions.push(7);
        }
        if (this.gamedatas.darkEdition) {
            this.activatedExpansions.push(8);
        }
        if (this.gamedatas.mindbugExpansion) {
            // TODOMB this.activatedExpansions.push(9);
        }
        

        if (this.activatedExpansions.length) {
            let html = `
            <div>					
                <button id="active-expansions-button" class="bgabutton bgabutton_gray">
                    <div class="title">${_('Active expansions')}</div>
                    <div class="expansion-zone-list">`;

            for (let i = 1; i <= EXPANSION_NUMBER; i++) {
                const activated = this.activatedExpansions.includes(i);
                html += `<div class="expansion-zone" data-expansion="${i}" data-activated="${activated.toString()}"><div class="expansion-icon"></div></div>`;
            }
                    
            html += `        </div>
                </button>
            </div>`

            dojo.place(html, `player_boards`);
            
            document.getElementById(`active-expansions-button`).addEventListener(`click`, () => this.createPopin());
        }
    }

    private getTitle(index: number) {
        switch (index) {
            case 0: return _('Base game');
            case 1: return _('“Halloween” event (Costume cards)');
            case 2: return _('“Battle of the Gods, part I” event (Cultists)');
            case 3: return _('“Nature vs. Machine, part I” event (Tokyo Tower)');
            case 4: return _('“Battle of the Gods: the Revenge!” event (Curse cards)');
            case 5: return _('“Nature vs. Machine: the Comeback!” event (Berserk)');
            case 6: return _('“Even more wicked!” event');
            case 7: return _('Power-Up! (Evolutions)');
            case 8: return _('Dark Edition');
        }
    }

    private getDescription(index: number) {
        switch (index) {
            case 1: return formatTextIcons(_('Halloween expansion brings a new set of Costume cards. Each player start with a Costume card (chosen between 2). When you smash a player with at least 3 [diceSmash], you can steal their Costumes cards (by paying its cost).'));
            case 2: return formatTextIcons(`<p>${_("After resolving your dice, if you rolled four identical faces, take a Cultist tile")}</p>
            <p>${_("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll.")}</p>`);
            case 3: return formatTextIcons(`<p>${_("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1] while in Tokyo.")}</p>
            <p>${_("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative).")}</p>
            <p><strong>${_("Claiming the top level automatically wins the game.")}</strong></p>`);
            case 4: return formatTextIcons(_("Anubis brings the Curse cards and the Die of Fate. The Curse card on the table show a permanent effect, applied to all players, and the Die of Fate can trigger the Ankh effect or the Snake effect."));
            case 5: return formatTextIcons(`<p>${_("When you roll 4 or more [diceSmash], you are in Berserk mode!")}</p>
            <p>${_("You play with the additional Berserk die, until you heal yourself.")}</p>`);
            case 6: return formatTextIcons(_("When you roll 3 or more [dice1] or [dice2], gain Wickeness points to get special Tiles."));
            case 7: return formatTextIcons(_("Power-Up! expansion brings new sets of Evolution cards, giving each Monster special abilities. Each player start with an Evolution card (chosen between 2). You can play this Evolution card any time. When you roll 3 or more [diceHeart], you can choose a new Evolution card."));
            case 8: return _("Dark Edition brings gorgeous art, and the wickedness track is included in the game, with a new set of cards.");
        }
        return '';
    }

    private viewRulebook(index: number) {
        const rulebookContainer = document.getElementById(`rulebook-${index}`);
        const show = rulebookContainer.innerHTML === '';
        if (show) {
            const url = RULEBOOK_LINKS[index][this.language] ?? RULEBOOK_LINKS[index]['en'];
            const html = `<iframe src="${url}" style="width: 100%; height: 60vh"></iframe>`;
            rulebookContainer.innerHTML = html;
        } else {
            rulebookContainer.innerHTML = '';
        }
        document.getElementById(`show-rulebook-${index}`).innerHTML = show ? _('Hide rulebook') : _('Show rulebook');
    }

    private createBlock(index: number) {
        const url = RULEBOOK_LINKS[index][this.language] ?? RULEBOOK_LINKS[index]['en'];
        const activated = this.activatedExpansions.includes(index);
        const html = `
        <details data-expansion="${index}" data-activated="${activated.toString()}">
            <summary><span class="activation-status">${activated ? _('Enabled') : _('Disabled')}</span>${this.getTitle(index)}</summary>
            <div class="description">${this.getDescription(index)}</div>
            <p class="block-buttons">
                <button id="show-rulebook-${index}" class="bgabutton bgabutton_blue">${_('Show rulebook')}</button>
                <a href="${url}" target="_blank" class="bgabutton bgabutton_blue">${_('Open rulebook in a new tab')}</a>
            </p>
            <div id="rulebook-${index}"></div>
        </details>`;

        dojo.place(html, `playermat-container-modal`);

        document.getElementById(`show-rulebook-${index}`).addEventListener(`click`, () => this.viewRulebook(index));
    }

    private createPopin()  {
        let html = `
        <div id="popin_showActivatedExpansions_container" class="kingoftokyo_popin_container">
            <div id="popin_showActivatedExpansions_underlay" class="kingoftokyo_popin_underlay"></div>
                <div id="popin_showActivatedExpansions_wrapper" class="kingoftokyo_popin_wrapper">
                <div id="popin_showActivatedExpansions" class="kingoftokyo_popin">
                    <a id="popin_showActivatedExpansions_close" class="closeicon"><i class="fa fa-times fa-2x" aria-hidden="true"></i></a>
                                
                    <h2>${_('Active expansions')}</h2>
                    <div id="playermat-container-modal"></div>
                </div>
            </div>
        </div>`;
        dojo.place(html, $(document.body));

        document.getElementById(`popin_showActivatedExpansions_close`).addEventListener(`click`, () => this.closePopin());
        document.getElementById(`popin_showActivatedExpansions_underlay`).addEventListener(`click`, () => this.closePopin());

        for (let i = 0; i <= EXPANSION_NUMBER; i++) {
            html += this.createBlock(i);
        }
    }

    private closePopin() {
        document.getElementById('popin_showActivatedExpansions_container').remove();
    }
}