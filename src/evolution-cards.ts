const MONSTERS_WITH_POWER_UP_CARDS = [1,2,3,4,5,6,7,8,13,14,15,18];

class EvolutionCards {
    EVOLUTION_CARDS_TYPES: number[];

    constructor (private game: KingOfTokyoGame) {
        this.EVOLUTION_CARDS_TYPES = (game as any).gamedatas.EVOLUTION_CARDS_TYPES;
    }

    // gameui.evolutionCards.debugSeeAllCards()
    debugSeeAllCards() {
        let html = `<div id="all-evolution-cards" class="evolution-card-stock player-evolution-cards">`;
        MONSTERS_WITH_POWER_UP_CARDS.forEach(monster => 
            html += `<div id="all-evolution-cards-${monster}" style="display: flex; flex-wrap: nowrap;"></div>`
        );
        html += `</div>`;
        dojo.place(html, 'kot-table', 'before');

        MONSTERS_WITH_POWER_UP_CARDS.forEach(monster => {
            const evolutionRow = document.getElementById(`all-evolution-cards-${monster}`);
            for (let i = 1; i <= 8; i++) {
                const tempDiv = this.generateCardDiv({
                    type: monster * 10 + i
                } as Card);
                tempDiv.id = `all-evolution-cards-${monster}-${i}`;
                evolutionRow.appendChild(tempDiv);
                (this.game as any).addTooltipHtml(tempDiv.id, this.getTooltip(monster * 10 + i));
            }
        })
    }
    
    public setupCards(stocks: Stock[]) {

        stocks.forEach(stock => {
            const keepcardsurl = `${g_gamethemeurl}img/evolution-cards.jpg`;
            stock.addItemType(0, 0, keepcardsurl, 0);
            MONSTERS_WITH_POWER_UP_CARDS.forEach((monster, index) => {  // keep
                for (let i=1; i <=8; i++) {
                    const uniqueId = monster * 10 + i;
                    stock.addItemType(uniqueId, uniqueId, keepcardsurl, index + 1);
                }
            });
            
        });
    }

    private getColoredCardName(cardTypeId: number): string {
        switch (cardTypeId) {
            // Space Penguin : blue 2384c6 grey 4c7c96
            case 11: return /*_TODOPU*/("[2384c6]Freeze [4c7c96]Ray");
            case 12: return /*_TODOPU*/("[2384c6]Miraculous [4c7c96]Catch");
            case 13: return /*_TODOPU*/("[2384c6]Deep [4c7c96]Dive");
            case 14: return /*_TODOPU*/("[2384c6]Cold [4c7c96]Wave");
            case 15: return /*_TODOPU*/("[2384c6]Encased [4c7c96]in Ice");
            case 16: return /*_TODOPU*/("[2384c6]Blizzard");
            case 17: return /*_TODOPU*/("[2384c6]Black [4c7c96]Diamond");
            case 18: return /*_TODOPU*/("[2384c6]Icy [4c7c96]Reflection");
            // Alienoid : orange e39717 brown aa673d
            case 21: return /*_TODOPU*/("[e39717]Alien [aa673d]Scourge");
            case 22: return /*_TODOPU*/("[e39717]Precision [aa673d]Field Support");
            case 23: return /*_TODOPU*/("[e39717]Anger [aa673d]Batteries");
            case 24: return /*_TODOPU*/("[e39717]Adapting [aa673d]Technology");
            case 25: return /*_TODOPU*/("[e39717]Funny Looking [aa673d]But Dangerous");
            case 26: return /*_TODOPU*/("[e39717]Exotic [aa673d]Arms");
            case 27: return /*_TODOPU*/("[e39717]Mothership [aa673d]Support");
            case 28: return /*_TODOPU*/("[e39717]Superior Alien [aa673d]Technology");
            // Cyber Kitty : soft b67392 strong ec008c
            case 31: return /*_TODOPU*/("[b67392]Nine [ec008c]Lives");
            case 32: return /*_TODOPU*/("[b67392]Mega [ec008c]Purr");
            case 33: return /*_TODOPU*/("[b67392]Electro- [ec008c]Scratch");
            case 34: return /*_TODOPU*/("[b67392]Cat [ec008c]Nip");
            case 35: return /*_TODOPU*/("[b67392]Play with your [ec008c]Food");
            case 36: return /*_TODOPU*/("[b67392]Feline [ec008c]Motor");
            case 37: return /*_TODOPU*/("[b67392]Mouse [ec008c]Hunter");
            case 38: return /*_TODOPU*/("[b67392]Meow [ec008c]Missle");
            // The King : dark a2550b light ca6c39
            case 41: return /*_TODOPU*/("[a2550b]Monkey [ca6c39]Rush")
            case 42: return /*_TODOPU*/("[a2550b]Simian [ca6c39]Scamper");
            case 43: return /*_TODOPU*/("[a2550b]Jungle [ca6c39]Frenzy")
            case 44: return /*_TODOPU*/("[a2550b]Giant [ca6c39]Banana");
            case 45: return /*_TODOPU*/("[a2550b]Chest [ca6c39]Thumping");
            case 46: return /*_TODOPU*/("[a2550b]Alpha [ca6c39]Male");
            case 47: return /*_TODOPU*/("[a2550b]I Am [ca6c39]the King!");
            case 48: return /*_TODOPU*/("[a2550b]Twas Beauty [ca6c39]Killed the Beast");
            // Gigazaur : dark 00a651 light bed62f
            case 51: return /*_TODOPU*/("[00a651]Detachable [bed62f]Tail");
            case 52: return /*_TODOPU*/("[00a651]Radioactive [bed62f]Waste");
            case 53: return /*_TODOPU*/("[00a651]Primal [bed62f]Bellow");
            case 54: return /*_TODOPU*/("[00a651]Saurian [bed62f]Adaptability");
            case 55: return /*_TODOPU*/("[00a651]Defender [bed62f]Of Tokyo");
            case 56: return /*_TODOPU*/("[00a651]Heat [bed62f]Vision");
            case 57: return /*_TODOPU*/("[00a651]Gamma [bed62f]Breath");
            case 58: return /*_TODOPU*/("[00a651]Tail [bed62f]Sweep");
            // Meka Dragon : gray a68d83 brown aa673d
            case 61: return /*_TODOPU*/("[a68d83]Mecha [aa673d]Blast");
            case 62: return /*_TODOPU*/("[a68d83]Destructive [aa673d]Analysis");
            case 63: return /*_TODOPU*/("[a68d83]Programmed [aa673d]To Destroy");
            case 64: return /*_TODOPU*/("[a68d83]Tune [aa673d]-Up");
            case 65: return /*_TODOPU*/("[a68d83]Breath [aa673d]of Doom");
            case 66: return /*_TODOPU*/("[a68d83]Lightning [aa673d]Armor");
            case 67: return /*_TODOPU*/("[a68d83]Claws [aa673d]of Steel");
            case 68: return /*_TODOPU*/("[a68d83]Target [aa673d]Acquired");
            // Boogie Woogie : dark 6c5b55 light a68d83
            case 71: return /*_TODOPUHA*/("[6c5b55]Boo!");
            case 72: return /*_TODOPUHA*/("[6c5b55]Worst [a68d83]Nightmare");
            case 73: return /*_TODOPUHA*/("[6c5b55]I Live [a68d83]Under Your Bed");
            case 74: return /*_TODOPUHA*/("[6c5b55]Boogie [a68d83]Dance");
            case 75: return /*_TODOPUHA*/("[6c5b55]Well of [a68d83]Shadow");
            case 76: return /*_TODOPUHA*/("[6c5b55]Woem [a68d83]Invaders");
            case 77: return /*_TODOPUHA*/("[6c5b55]Nighlife!");
            case 78: return /*_TODOPUHA*/("[6c5b55]Dusk [a68d83]Ritual");
            // Pumpkin Jack : dark de6428 light f7941d
            case 81: return /*_TODOPUHA*/("[de6428]Detachable [f7941d]Head");
            case 82: return /*_TODOPUHA*/("[de6428]Ignis [f7941d]Fatus");
            case 83: return /*_TODOPUHA*/("[de6428]Smashing [f7941d]Pumpkin");
            case 84: return /*_TODOPUHA*/("[de6428]Trick [f7941d]or Threat");
            case 85: return /*_TODOPUHA*/("[de6428]Bobbing [f7941d]for Apples");
            case 86: return /*_TODOPUHA*/("[de6428]Feast [f7941d]of Crows");
            case 87: return /*_TODOPUHA*/("[de6428]Scythe");
            case 88: return /*_TODOPUHA*/("[de6428]Candy!");
            // Cthulhu
            // Anubis
            // King Kong
            // Cybertooth
            // Pandakaï : light 6d6e71 dark 231f20
            case 131: return /*_TODOPU*/("[6d6e71]Panda[231f20]Monium");
            case 132: return /*_TODOPU*/("[6d6e71]Eats, Shoots [231f20]and Leaves");
            case 133: return /*_TODOPU*/("[6d6e71]Bam[231f20]Boozle");
            case 134: return /*_TODOPU*/("[6d6e71]Bear [231f20]Necessities");
            case 135: return /*_TODOPU*/("[6d6e71]Panda [231f20]Express");
            case 136: return /*_TODOPU*/("[6d6e71]Bamboo [231f20]Supply");
            case 137: return /*_TODOPU*/("[6d6e71]Pandarwinism [231f20]Survival of the Cutest");
            case 138: return /*_TODOPU*/("[6d6e71]Yin [231f20]& Yang");
            // cyberbunny : soft b67392 strong ec008c
            case 141: return /*_TODODE*/("[b67392]Stroke [ec008c]Of Genius");
            case 142: return /*_TODODE*/("[b67392]Emergency [ec008c]Battery");
            case 143: return /*_TODODE*/("[b67392]Rabbit's [ec008c]Foot");
            case 144: return /*_TODODE*/("[b67392]Heart [ec008c]of the Rabbit");
            case 145: return /*_TODODE*/("[b67392]Secret [ec008c]Laboratory");
            case 146: return /*_TODODE*/("[b67392]King [ec008c]of the Gizmo");
            case 147: return /*_TODODE*/("[b67392]Energy [ec008c]Sword");
            case 148: return /*_TODODE*/("[b67392]Electric [ec008c]Carrot");
            // kraken : blue 2384c6 gray 4c7c96
            case 151: return /*_TODODE*/("[2384c6]Healing [4c7c96]Rain");
            case 152: return /*_TODODE*/("[2384c6]Destructive [4c7c96]Wave");
            case 153: return /*_TODODE*/("[2384c6]Cult [4c7c96]Worshippers");
            case 154: return /*_TODODE*/("[2384c6]High [4c7c96]Tide");
            case 155: return /*_TODODE*/("[2384c6]Terror [4c7c96]of the Deep");
            case 156: return /*_TODODE*/("[2384c6]Eater [4c7c96]of Souls");
            case 157: return /*_TODODE*/("[2384c6]Sunken [4c7c96]Temple");
            case 158: return /*_TODODE*/("[2384c6]Mandibles [4c7c96]of Dread");
            // Baby Gigazaur : dark a5416f light f05a7d
            case 181: return /*_TODOPUBG*/("[a5416f]My [f05a7d]Toy");
            case 182: return /*_TODOPUBG*/("[a5416f]Growing [f05a7d]Fast");
            case 183: return /*_TODOPUBG*/("[a5416f]Nurture [f05a7d]the Young");
            case 184: return /*_TODOPUBG*/("[a5416f]Tiny [f05a7d]Tail");
            case 185: return /*_TODOPUBG*/("[a5416f]Too Cute [f05a7d]to Smash");
            case 186: return /*_TODOPUBG*/("[a5416f]So [f05a7d]Small!");
            case 187: return /*_TODOPUBG*/("[a5416f]Underrated");
            case 188: return /*_TODOPUBG*/("[a5416f]Yummy [f05a7d]Yummy");
        }
        return null;
    }

    public getCardName(cardTypeId: number, state: 'text-only' | 'span') {
        const coloredCardName = this.getColoredCardName(cardTypeId);
        if (state == 'text-only') {
            return coloredCardName?.replace(/\[(\w+)\]/g, '');
        } else if (state == 'span') {
            let first = true;
            return coloredCardName?.replace(/\[(\w+)\]/g, (index, color) => {
                let span = `<span style="-webkit-text-stroke-color: #${color};">`;
                if (first) {
                    first = false;
                } else {
                    span = `</span>` + span;
                }
                return span;
            }) + `${first ? '' : '</span>'}`;
        }
        return null;
    }

    private getCardDescription(cardTypeId: number) {
        switch (cardTypeId) {
            // Space Penguin
            case 11: return /*_TODOPU*/("When you wound a Monster in <i>Tokyo</i>, give them this card. At the start of their turn, choose a die face. This face has no effect this turn. Take back this card at the end of their turn.");
            case 12: return /*_TODOPU*/("Once per turn, during the Buy Power Cards phase, you can shuffle the discard pile and reveal one card randomly. You can buy this card for 1[Energy] less than the normal price or discard it. Put back the rest of the discard pile.");
            case 13: return /*_TODOPU*/("Look at the top 3 Power cards of the deck. Choose one and play it in front of you for free. Put the other Power cards on the bottom of the deck.");
            case 14: return /*_TODOPU*/("Until your next turn, other Monsters roll with 1 less die.");
            case 15: return /*_TODOPU*/("Spend 1[Energy] to choose one of the dice you rolled. This die is frozen until the beginning of your next turn: it cannot be changed and is used normally by Monsters during the Resolve Dice phase.");
            case 16: return /*_TODOPU*/("Play during your turn. Until the start of your next turn, Monsters only have a single Roll and cannot Yield <i>Tokyo</i>.");
            case 17: return /*_TODOPU*/("Gain 1 extra [Star] each time you take control of <i>Tokyo</i> or choose to stay in <i>Tokyo</i> when you could have Yielded.");
            case 18: return /*_TODOPU*/("Choose an Evolution Card in front of a Monster and put a [snowflakeToken] on it. Icy Reflection becomes a copy of that card as if you had played it. If the copied card is removed from play, discard <i>Icy Reflection</i>.");
            // Alienoid
            case 21: return "+2[Star]";
            case 22: return /*_TODOPU*/("Draw Power cards from the top of the deck until you reveal a [keep] card that costs 4[Energy] or less. Play this card in front of you and discard the other cards you drew.");
            case 23: return /*_TODOPU*/("Gain 1[Energy] for each [Heart] you lost this turn.");
            case 24: return /*_TODOPU*/("Put 3 [alienoidToken] tokens on this card. On your turn, you can remove a [alienoidToken] token to discard the 3 face-up Power cards and reveal 3 new ones. Discard this card when there are no more tokens on it.");
            case 25: return /*_TODOPU*/("If you roll at least [dice2][dice2][dice2] each of the other Monster loses 1[Heart].");
            case 26: return /*_TODOPU*/("Before you roll, you can put 2[Energy] on this card. If you do, and roll at least [diceSmash][diceSmash][diceSmash], you can take back your two [Energy] and make the Monsters you wound lose 2 extra [Heart]. Otherwise you lose your 2[Energy] and lose 2[Heart].");
            case 27: return /*_TODOPU*/("Once during your turn, you can spend 1[Energy] to gain 1[Heart].");
            case 28: return /*_TODOPU*/("You can buy [keep] cards by paying half of their cost (rounding up). When you do so, place a [UfoToken] on it. At the start of you turn, roll a die for each of your [keep] cards with a [UfoToken]. Discard each [keep] card for which you rolled a [diceSmash]. You cannot have more than 3 [keep] cards with [UfoToken] at a time.");
            // Cyber Kitty
            case 31: return /*_TODOPU*/("If you reach [Skull] discard your cards (including your Evolutions), lose all your [Energy] and [Star], and leave <i>Tokyo</i>. Gain 9[Heart], 9[Star], and continue playing.");
            case 32: return /*_TODOPU*/("Each of the other Monsters give you 1[Energy] or 1[Star] if they have any (they choose which to give you).");
            case 33: return /*_TODOPU*/("Each of the other Monsters lose 1[Heart].");
            case 34: return /*_TODOPU*/("Play at the start of your turn. You only have one roll this turn. Double the result.");
            case 35: return /*_TODOPU*/("When you wound a Monster in <i>Tokyo</i>, if they must lose at least 2[Heart], you may make them lose 2[Heart] less and steal 1[Star] and 1[Energy] from them instead.");
            case 36: return /*_TODOPU*/("During other Monsters' Enter Tokyo phases, if <i>Tokyo</i> is empty and you were not inside at the start of the turn, you can enter <i>Tokyo</i> instead of the Monster whose turn it is.");
            case 37: return /*_TODOPU*/("If you roll at least one [dice1], gain 1[Star].");
            case 38: return /*_TODOPU*/("If you roll at least one [dice1], add [diceSmash] to your roll.");
            // The King
            case 41: return /*_TODOPU*/("Play when a Monster who controls <i>Tokyo</i> leaves or is eliminated. Take control of <i>Tokyo</i>.");
            case 42: return /*_TODOPU*/("If you Yield <i>Tokyo</i>, do not lose [Heart]. You can’t lose [Heart] this turn.");
            case 43: return /*_TODOPU*/("Play at the end of your Enter Tokyo phase. If you wounded a Monster who controls <i>Tokyo</i> and you didn't take control of <i>Tokyo</i>, take an extra turn after this one.");
            case 44: return /*_TODOPU*/("+2[Heart]");
            case 45: return /*_TODOPU*/("You can force Monsters you wound to Yield <i>Tokyo</i>.");
            case 46: return /*_TODOPU*/("Each turn you wound at least one Monster, gain 1[Star].");
            case 47: return /*_TODOPU*/("Gain 1 extra [Star] if you take control of <i>Tokyo</i> or if you start your turn in <i>Tokyo</i>.");
            case 48: return /*_TODOPU*/("Play when you are in <i>Tokyo</i>. Gain 1[Star] at the end of each Monster’s turn (including yours). Discard this card and lose all your [Star] if you leave <i>Tokyo</i>.");
            // Gigazaur 
            case 51: case 143: return /*_TODOPU*/("You can’t lose [Heart] this turn.");
            case 52: return "+2[Energy] +1[Heart].";
            case 53: return /*_TODOPU*/("Each of the other Monsters lose 2[Star].");
            case 54: return /*_TODOPU*/("Choose a die face. Take all dice with this face and flip them to a (single) face of your choice.");
            case 55: return /*_TODOPU*/("If you start your turn in <i>Tokyo</i>, each of the other Monsters lose 1[Star].");
            case 56: case 185: return /*_TODOPU*/("Monsters that wound you lose 1[Star].");
            case 57: return /*_TODOPU*/("Once per turn, you can change one of the dice you rolled to [diceSmash].");
            case 58: return /*_TODOPU*/("Once per turn, you can change one of the dice you rolled to [dice1] or [dice2].");
            // Meka Dragon
            case 61: return /*_TODOPU*/("Each Monster you wound this turn loses 2 extra [Heart].");
            case 62: return /*_TODOPU*/("Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 63: return /*_TODOPU*/("Gain 3[Star] and 2[Energy] each time another Monster reaches [Skull].");
            case 64: return /*_TODOPU*/("Play before rolling dice. If you are not in <i>Tokyo</i>, skip your turn, gain 4[Heart] and 2[Energy].");
            case 65: return /*_TODOPU*/("When you make Monsters in <i>Tokyo</i> lose at least 1[Heart], Monsters who aren't in <i>Tokyo</i> also lose 1[Heart] each (except you).");
            case 66: return /*_TODOPU*/("When you lose [Heart], you can roll a die for each [Heart] lost. For each [diceSmash] rolled this way, the Monster whose turn it is also loses 1[Heart].");
            case 67: return /*_TODOPU*/("On your turn, if you make another Monster lose at least 3[Heart], they lose 1 extra [Heart].");
            case 68: return /*_TODOPU*/("When a Monster wounds you, you can give them the [targetToken] token. The Monster who has the [targetToken] token loses 1 extra [Heart] each time you make them lose [Heart].");
            // Boogie Woogie
            case 73: return /*_TODOPUHA*/("You play with one less die.");
            case 75: return "+2[Heart]";
            case 76: return /*_TODOPUHA*/("Each of the other Monsters loses 2[Heart].");
            case 77: return /*_TODOPUHA*/("When you enter <i>Tokyo</i>, gain 1[Heart].");
            // Pumpkin Jack 
            case 81: return /*_TODOPUHA*/("Every time the <i>Owner</i> of this card wounds you, lose an extra [Heart].");
            case 82: return /*_TODOPUHA*/("You have one less Roll each turn.");
            case 83: return /*_TODOPUHA*/("All Monsters with 12 or more [Star] lose 2[Heart].");
            // Pandakaï
            case 131: return /*_TODOPU*/("Gain 6[Energy]. All other Monsters gain 3[Energy].");  
            case 132: return /*_TODOPU*/("Play when you enter <i>Tokyo</i>. All Monsters outside of <i>Tokyo</i> lose 2[Heart] each. Gain 1[Energy], then leave <i>Tokyo</i>. No Monster takes your place.");
            case 133: return /*_TODOPU*/("Play when a player buys a Power card. They do not spend [Energy] and cannot buy that card this turn. Choose a different Power card they can afford to buy. They must purchase that card.");
            case 134: return "-1[Star] +2[Energy] +2[Heart].";
            case 135: return /*_TODOPU*/("If you rolled at least [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy], gain 2[Star] and take another turn.");
            case 136: return /*_TODOPU*/("At the start of your turn, you can put 1[Energy] from the bank on this card OR take all of the [Energy] off this card.");
            case 137: return /*_TODOPU*/("If you roll at least [diceHeart][diceHeart][diceHeart], gain 1[Star]. Also gain 1[Star] for each extra [diceHeart] you roll.");
            case 138: return /*_TODOPU*/("Before resolving your dice, you can choose to flip all your dice to the opposite side.") + `<div>[dice1]↔[dice3] &nbsp; [dice2]↔[diceHeart] &nbsp; [diceSmash]↔[diceEnergy]</div>`;
            // Cyber Bunny
            case 141: return /*_TODODE*/("Gain 1[Energy] for each [Energy] you already gained this turn.");
            case 142: return "+3[Energy]";
            // 143 same as 51
            case 144: return /*_TODODE*/("Play when another Monster finishes Rolling. Reroll one of this Monster’s dice. Take back <i>Heart of the Rabbit</i> from your discard when you take control of <i>Tokyo</i>.");
            case 145: return /*_TODODE*/("The price of Power cards you buy is reduced by 1[Energy].");
            case 146: return /*_TODODE*/("Gain 1[Star] each time you buy a Power card.");
            case 147: return /*_TODODE*/("Before rolling dice, you can pay 2[Energy]. If you do so and you roll at least 1 [diceSmash], add [diceSmash] to your Roll. Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 148: return /*_TODODE*/("If you are in <i>Tokyo</i>, Monsters you wound lose one extra [Heart] unless they give you 1[Energy].");
            // Kraken
            case 151: return "+2[Heart]";
            case 152: return /*_TODODE*/("Play when you enter <i>Tokyo</i>. All other Monsters lose 2[Heart].");
            case 153: return /*_TODODE*/("Gain 1[Star] for each [Heart] gained this turn.");
            case 154: return /*_TODODE*/("For each [diceHeart] you rolled, add [diceHeart] to your Roll");
            case 155: return /*_TODODE*/("Roll one die for each [Heart] you lost this turn. Don’t lose [Heart] for each [diceHeart] you roll.");
            case 156: return /*_TODODE*/("Gain 1[Heart] each time you enter <i>Tokyo</i>. You can have up to 12[Heart] as long as you own this card.");
            case 157: return /*_TODODE*/("Before rolling dice, if you are not in <i>Tokyo</i>, you can pass your turn to gain 3[Heart] and 3[Energy].");
            case 158: return /*_TODODE*/("Monsters you wound lose 1[Star].");
            // Baby Gigazaur
            case 181: return /*_TODOPUBG*/("Take one of the three face-up Power cards and put it under this card. It is reserved for your purchase. Once purchased, choose another card to reserve."); // TODOPUBG
            case 182: return /*_TODOPUBG*/("If you roll no [diceHeart], gain 1[Heart].");
            case 183: return /*_TODOPUBG*/("Each Monster who has more [Star] than you has to give you 1[Star].");
            case 184: return /*_TODOPUBG*/("Once per turn, you may change two dice you rolled to [dice1].");
            // 185 same as 56
            case 186: return /*_TODOPUBG*/("When a Monster wounds you, roll a die for each [diceSmash]. If any of the results is [diceHeart], you lose no [Heart].");
            case 187: return /*_TODOPUBG*/("Add 2 [diceSmash] to your Roll.");
            case 188: return "+2[Heart] +1[Energy].";
        }
        return null;
    }

    private getDistance(p1: PlacedTokens, p2: PlacedTokens): number {
        return Math.sqrt((p1.x - p2.x) ** 2 + (p1.y - p2.y) ** 2);
    }

    public placeMimicOnCard(stock: Stock, card: Card) {
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);

        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        
        cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);

        let html = `<div id="${divId}-mimic-token" style="left: ${cardPlaced.mimicToken.x - 16}px; top: ${cardPlaced.mimicToken.y - 16}px;" class="card-token icy-reflection token"></div>`;
        dojo.place(html, divId);

        div.dataset.placed = JSON.stringify(cardPlaced);
    }

    public removeMimicOnCard(stock: Stock, card: Card) { 
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);

        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        cardPlaced.mimicToken = null;

        if (document.getElementById(`${divId}-mimic-token`)) {
            (this.game as any).fadeOutAndDestroy(`${divId}-mimic-token`);
        }

        div.dataset.placed = JSON.stringify(cardPlaced);
    }

    private getPlaceOnCard(cardPlaced: CardPlacedTokens): PlacedTokens {
        const newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 100 + 16,
        };
        let protection = 0;
        const otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(place => this.getDistance(newPlace, place) < 32)) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }

        return newPlace;
    }

    public placeTokensOnCard(stock: Stock, card: EvolutionCard, playerId?: number) {
        const divId = `${stock.container_div.id}_item_${card.id}`;
        const div = document.getElementById(divId);
        if (!div) {
            return;
        }
        const cardPlaced: CardPlacedTokens = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: []};
        const placed: PlacedTokens[] = cardPlaced.tokens;

        const cardType = /* TODOPU card.mimicType ||*/ card.type;

        // remove tokens
        for (let i = card.tokens; i < placed.length; i++) {
            if (cardType === 136 && playerId) {
                (this.game as any).slideToObjectAndDestroy(`${divId}-token${i}`, `energy-counter-${playerId}`);
            } else {
                (this.game as any).fadeOutAndDestroy(`${divId}-token${i}`);
            }
        }
        placed.splice(card.tokens, placed.length - card.tokens);

        // add tokens
        for (let i = placed.length; i < card.tokens; i++) {
            const newPlace = this.getPlaceOnCard(cardPlaced);

            placed.push(newPlace);
            let html = `<div id="${divId}-token${i}" style="left: ${newPlace.x - 16}px; top: ${newPlace.y - 16}px;" class="card-token `;
            if (cardType === 24) {
                html += `ufo token`;
            } else if (cardType === 26 || cardType === 136) {
                html += `energy-cube`;
            }
            html += `"></div>`;
            dojo.place(html, divId);
        }

        div.dataset.placed = JSON.stringify(cardPlaced);
    }

    public setDivAsCard(cardDiv: HTMLDivElement, cardType: number) {
        const type = this.getCardTypeName(cardType);
        const description = formatTextIcons(this.getCardDescription(cardType));

        cardDiv.innerHTML = `
        <div class="evolution-type">${type}</div>
        <div class="name-and-description">
            <div class="name-row">
                <div class="name-wrapper">
                    <div class="outline">${this.getCardName(cardType, 'span')}</div>
                    <div class="text">${this.getCardName(cardType, 'text-only')}</div>
                </div>
            </div>
            <div class="description-row">
                <div class="description-wrapper">${description}</div>
            </div>
        </div>      
        `;

        const evolutionType = cardDiv.getElementsByClassName('evolution-type')[0] as HTMLDivElement;
        const nameAndDescription = cardDiv.getElementsByClassName('name-and-description')[0] as HTMLDivElement;
        const nameWrapper = cardDiv.getElementsByClassName('name-wrapper')[0] as HTMLDivElement;
        const outline = cardDiv.getElementsByClassName('outline')[0] as HTMLDivElement;
        const descriptionWrapper = cardDiv.getElementsByClassName('description-wrapper')[0] as HTMLDivElement;

        let textHeight = descriptionWrapper.clientHeight;

        if (textHeight > 50) {
            descriptionWrapper.style.fontSize = '6pt';
            textHeight = descriptionWrapper.clientHeight;
        } else {
            return;
        }

        let nameHeight = outline.clientHeight;

        if (71 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '8pt';
            nameHeight = outline.clientHeight;
        }

        if (71 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '7pt';
            nameHeight = outline.clientHeight;
        }

        if (71 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '6pt';
            outline.style.webkitTextStroke = '3px #a6c136';
        }

        if (textHeight > 50 || nameWrapper.getBoundingClientRect().top < evolutionType.getBoundingClientRect().bottom) {
            descriptionWrapper.style.width = '120px';
            descriptionWrapper.style.background = '#FFFFFFCC';
            nameAndDescription.style.height = '87px';
        }
    }

    // TODOPU set ownerId
    public getTooltip(cardTypeId: number, ownerId?: number) {
        let tooltip = `<div class="card-tooltip">
            <p><strong>${this.getCardName(cardTypeId, 'text-only')}</strong></p>
            <p>${this.getCardTypeName(cardTypeId)}</p>`;
            if (ownerId) {
                const owner = this.game.getPlayer(ownerId);
                tooltip += `<p>${_('Owner:')} <strong style="color: #${owner.color};">${owner.name}</strong></p>`;
            }
            tooltip += `<p>${formatTextIcons(this.getCardDescription(cardTypeId))}</p>
        </div>`;
        return tooltip;
    }

    public setupNewCard(cardDiv: HTMLDivElement, cardType: number) {
        if (cardType == 0) {
            return;
        }

        this.setDivAsCard(cardDiv, cardType); 
        (this.game as any).addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    }

    private getCardTypeName(cardType: number) {
        const type = this.EVOLUTION_CARDS_TYPES[cardType];
        switch (type) {
            case 1: return /*_ TODOPU */('<strong>Permanent</strong> evolution');
            case 2: return /*_ TODOPU */('<strong>Temporary</strong> evolution');
            case 3: return /*_ TODOPU */('<strong>Gift</strong> evolution');
        }
        return null;
    }
    
    public addCardsToStock(stock: Stock, cards: EvolutionCard[], from?: string) {
        if (!cards.length) {
            return;
        }

        cards.forEach(card => {
            stock.addToStockWithId(card.type, `${card.id}`, from);
            //const cardDiv = document.getElementById(`${stock.container_div.id}_item_${card.id}`) as HTMLDivElement;
        });
        cards.filter(card => card.tokens > 0).forEach(card => this.placeTokensOnCard(stock, card));
    }

    public moveToAnotherStock(sourceStock: Stock, destinationStock: Stock, card: EvolutionCard) {
        if (sourceStock === destinationStock) {
            return;
        }
        
        const sourceStockItemId = `${sourceStock.container_div.id}_item_${card.id}`;
        if (document.getElementById(sourceStockItemId)) {     
            this.addCardsToStock(destinationStock, [card], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById(`${card.id}`);
        } else {
            console.warn(`${sourceStockItemId} not found in `, sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [card], sourceStock.container_div.id);
        }

        this.game.tableManager.tableHeightChange();
    }

    public generateCardDiv(card: Card): HTMLDivElement {
        const tempDiv: HTMLDivElement = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = `${CARD_WIDTH}px`;
        tempDiv.style.height = `${CARD_WIDTH}px`;
        tempDiv.style.position = `relative`;
        tempDiv.style.backgroundImage = `url('${g_gamethemeurl}img/evolution-cards.jpg')`;
        const imagePosition = MONSTERS_WITH_POWER_UP_CARDS.indexOf(Math.floor(card.type / 10)) + 1;
        const xBackgroundPercent = imagePosition * 100;
        tempDiv.style.backgroundPosition = `-${xBackgroundPercent}% 0%`;

        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type);
        document.body.removeChild(tempDiv);
            
        return tempDiv;
    }

    public getMimickedCardText(mimickedCard: Card): string {
        let mimickedCardText = '-';
        if (mimickedCard) {
            const tempDiv = this.generateCardDiv(mimickedCard);

            mimickedCardText = `<br>${tempDiv.outerHTML}`;
        }

        return mimickedCardText;
    }

    public changeMimicTooltip(mimicCardId: string, mimickedCardText: string) {
        (this.game as any).addTooltipHtml(mimicCardId, this.getTooltip(18) + `<br>${_('Mimicked card:')} ${mimickedCardText}`);
    }
}