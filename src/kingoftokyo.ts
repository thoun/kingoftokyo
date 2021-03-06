declare const define;
declare const ebg;
declare const $;
declare const dojo: Dojo;
declare const _;
declare const g_gamethemeurl;
declare const playSound;

declare const board: HTMLDivElement;

const ANIMATION_MS = 1500;
const PUNCH_SOUND_DURATION = 250;
const ACTION_TIMER_DURATION = 5;

type FalseBlessingAnkhAction = 'falseBlessingReroll' | 'falseBlessingDiscard';

class KingOfTokyo implements KingOfTokyoGame {
    private gamedatas: KingOfTokyoGamedatas;
    private healthCounters: Counter[] = [];
    private energyCounters: Counter[] = [];
    private wickednessCounters: Counter[] = [];
    private cultistCounters: Counter[] = [];
    private handCounters: Counter[] = [];
    private diceManager: DiceManager;
    private animationManager: AnimationManager;
    private playerTables: PlayerTable[] = [];
    private preferencesManager: PreferencesManager;
    public tableManager: TableManager;
    public cards: Cards;
    public curseCards: CurseCards;
    public wickednessTiles: WickednessTiles;    
    public evolutionCards: EvolutionCards;
    //private rapidHealingSyncHearts: number;
    public towerLevelsOwners = [];
    private tableCenter: TableCenter;
    private falseBlessingAnkhAction: FalseBlessingAnkhAction = null;
    private choseEvolutionInStock: Stock;
    private inDeckEvolutionsStock: Stock;
    private smashedPlayersStillInTokyo: number[];
        
    public SHINK_RAY_TOKEN_TOOLTIP: string;
    public POISON_TOKEN_TOOLTIP: string;
    public CULTIST_TOOLTIP: string;

    constructor() {
    }
    
    /*
        setup:

        This method must set up the game user interface according to current game situation specified
        in parameters.

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)

        "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
    */

    public setup(gamedatas: KingOfTokyoGamedatas) {
        const players = Object.values(gamedatas.players);
        // ignore loading of some pictures
        [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,21].filter(i => !players.some(player => Number(player.monster) === i)).forEach(i => {
            (this as any).dontPreloadImage(`monster-board-${i}.png`);
            (this as any).dontPreloadImage(`monster-figure-${i}.png`);
        });
        (this as any).dontPreloadImage(`tokyo-2pvariant.jpg`);
        (this as any).dontPreloadImage(`background-halloween.jpg`);
        (this as any).dontPreloadImage(`background-christmas.jpg`);
        (this as any).dontPreloadImage(`animations-halloween.jpg`);
        (this as any).dontPreloadImage(`animations-christmas.jpg`);
        (this as any).dontPreloadImage(`christmas_dice.png`);
        if (!gamedatas.halloweenExpansion) {
            (this as any).dontPreloadImage(`costume-cards.jpg`);
            (this as any).dontPreloadImage(`orange_dice.png`);
        }
        if (!gamedatas.powerUpExpansion) {
            (this as any).dontPreloadImage(`background-powerup.jpg`);
            (this as any).dontPreloadImage(`animations-powerup.jpg`);
            (this as any).dontPreloadImage(`powerup_dice.png`);
        }

        log( "Starting game setup" );
        
        this.gamedatas = gamedatas;

        log('gamedatas', gamedatas);

        if (gamedatas.halloweenExpansion) {
            document.body.classList.add('halloween');
        }
        if (gamedatas.kingkongExpansion) {
            gamedatas.tokyoTowerLevels.forEach(level => this.towerLevelsOwners[level] = 0);
            players.forEach(player => player.tokyoTowerLevels.forEach(level => this.towerLevelsOwners[level] = Number(player.id)));
        }

        if (gamedatas.twoPlayersVariant) {
            this.addTwoPlayerVariantNotice(gamedatas);
        }

        this.cards = new Cards(this);
        this.curseCards = new CurseCards(this);
        this.wickednessTiles = new WickednessTiles(this);
        this.evolutionCards = new EvolutionCards(this);
        this.SHINK_RAY_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Shrink ray tokens (given by ${card_name}). Reduce dice count by one per token. Use you [diceHeart] to remove them.")), {'card_name': this.cards.getCardName(40, 'text-only')});
        this.POISON_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Poison tokens (given by ${card_name}). Make you lose one [heart] per token at the end of your turn. Use you [diceHeart] to remove them.")), {'card_name': this.cards.getCardName(35, 'text-only')});
    
        this.createPlayerPanels(gamedatas); 
        setTimeout(() => new ActivatedExpansionsPopin(gamedatas, (this as any).players_metadata?.[this.getPlayerId()]?.language), 500);
        this.diceManager = new DiceManager(this);
        this.animationManager = new AnimationManager(this, this.diceManager);
        this.tableCenter = new TableCenter(this, players, gamedatas.visibleCards, gamedatas.topDeckCardBackType, gamedatas.wickednessTiles, gamedatas.tokyoTowerLevels, gamedatas.curseCard);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(() => this.playerTables.forEach(playerTable => playerTable.initPlacement()), 200);
        this.setMimicToken('card', gamedatas.mimickedCards.card);
        this.setMimicToken('tile', gamedatas.mimickedCards.tile);
        this.setMimicEvolutionToken(gamedatas.mimickedCards.evolution);

        const playerId = this.getPlayerId();
        const currentPlayer = players.find(player => Number(player.id) === playerId);

        if (currentPlayer?.rapidHealing) {
            this.addRapidHealingButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
        }
        if (currentPlayer?.mothershipSupport) {
            this.addMothershipSupportButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
        }
        if (currentPlayer?.cultists) {
            this.addRapidCultistButtons(currentPlayer.health >= currentPlayer.maxHealth);
        }
        if (currentPlayer?.location > 0) {
            this.addAutoLeaveUnderButton();
        }

        this.setupNotifications();
        this.preferencesManager = new PreferencesManager(this);

        document.getElementById('zoom-out').addEventListener('click', () => this.tableManager?.zoomOut());
        document.getElementById('zoom-in').addEventListener('click', () => this.tableManager?.zoomIn());

        if (gamedatas.kingkongExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Tokyo Tower")}</h3>
            <p>${_("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1] while in Tokyo.")}</p>
            <p>${_("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative).")}</p>
            <p><strong>${_("Claiming the top level automatically wins the game.")}</strong></p>
            `);
            (this as any).addTooltipHtmlToClass('tokyo-tower-tooltip', tooltip);
        }

        if (gamedatas.cybertoothExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Berserk mode")}</h3>
            <p>${_("When you roll 4 or more [diceSmash], you are in Berserk mode!")}</p>
            <p>${_("You play with the additional Berserk die, until you heal yourself.")}</p>`);
            (this as any).addTooltipHtmlToClass('berserk-tooltip', tooltip);
        }

        if (gamedatas.cthulhuExpansion) {
            this.CULTIST_TOOLTIP = formatTextIcons(`
            <h3>${_("Cultists")}</h3>
            <p>${_("After resolving your dice, if you rolled four identical faces, take a Cultist tile")}</p>
            <p>${_("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll.")}</p>`);
            (this as any).addTooltipHtmlToClass('cultist-tooltip', this.CULTIST_TOOLTIP);
        }

        if (gamedatas.darkEdition) {
            document.getElementsByTagName('html')[0].dataset.darkEdition = 'true';
        }

        // override to allow icons in messages
        const oldShowMessage = (this as any).showMessage;
        (this as any).showMessage = (msg, type) => oldShowMessage(formatTextIcons(msg), type);

        log( "Ending game setup" );

        /*if (window.location.host == 'studio.boardgamearena.com') {
            //this.isPowerUpExpansion() && this.evolutionCards.debugSeeAllCards();
            //this.isWickednessExpansion() && this.wickednessTiles.debugSeeAllCards();
        }*/
    }

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    public onEnteringState(stateName: string, args: any) {
        log('Entering state: ' + stateName, args.args);
        this.showActivePlayer(Number(args.active_player));

        const pickMonsterPhase = ['pickMonster', 'pickMonsterNextPlayer'].includes(stateName);
        const pickEvolutionForDeckPhase = ['pickEvolutionForDeck', 'nextPickEvolutionForDeck'].includes(stateName)
        
        if (!pickMonsterPhase) {
            this.removeMonsterChoice();
        }
        if (!pickMonsterPhase && !pickEvolutionForDeckPhase) {
            this.removeMutantEvolutionChoice();
            this.showMainTable();
        }

        if (this.isPowerUpExpansion()) {
            const evolutionCardsSingleState = this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE[stateName];
            if (evolutionCardsSingleState) {
                this.getPlayerTable(this.getPlayerId())?.setEvolutionCardsSingleState(evolutionCardsSingleState, true);
            }
        }

        switch (stateName) {
            case 'pickMonster':
                dojo.addClass('kot-table', 'pickMonsterOrEvolutionDeck');
                this.onEnteringPickMonster(args.args);
                break;
            case 'pickEvolutionForDeck':
                dojo.addClass('kot-table', 'pickMonsterOrEvolutionDeck');
                this.onEnteringPickEvolutionForDeck(args.args);
                break;
            case 'chooseInitialCard':
                this.onEnteringChooseInitialCard(args.args);
                this.showEvolutionsPopinPlayerButtons();
                break;
            case 'startGame':
                this.showEvolutionsPopinPlayerButtons();
                break;
            case 'beforeStartTurn':
            case 'beforeResolveDice':
            case 'beforeEnteringTokyo':
            case 'afterEnteringTokyo':
            case 'cardIsBought':
                this.onEnteringStepEvolution(args.args);
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'changeMimickedCardWickednessTile':
            case 'chooseMimickedCardWickednessTile':
                this.setDiceSelectorVisibility(false);
                this.onEnteringChooseMimickedCard(args.args);
                break;
            case 'throwDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringThrowDice(args.args);
                break;
            case 'changeDie': 
                this.setDiceSelectorVisibility(true);
                this.onEnteringChangeDie(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'prepareResolveDice': 
                this.setDiceSelectorVisibility(true);
                this.onEnteringPrepareResolveDice(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'discardDie': 
                this.setDiceSelectorVisibility(true);
                this.onEnteringDiscardDie(args.args);
                break;
            case 'selectExtraDie': 
                this.setDiceSelectorVisibility(true);
                this.onEnteringSelectExtraDie(args.args);
                break;
            case 'discardKeepCard':
                this.onEnteringDiscardKeepCard(args.args);
                break;
            case 'resolveDice': 
                this.falseBlessingAnkhAction = null;
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollOrDiscardDie(args.args);
                this.diceManager.hideLock();
                const argsResolveDice = args.args as EnteringResolveDiceArgs;
                if (argsResolveDice.canLeaveHibernation) {
                    this.setGamestateDescription('Hibernation');
                }
                break;
            case 'rerollOrDiscardDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollOrDiscardDie(args.args);
                break;
            case 'resolveNumberDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveNumberDice(args.args);
                break;
            case 'takeWickednessTile':
                this.onEnteringTakeWickednessTile(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'resolveHeartDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveHeartDice(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'resolveSmashDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveSmashDice(args.args, (this as any).isCurrentPlayerActive());
                break;

            case 'chooseEvolutionCard':
                this.onEnteringChooseEvolutionCard(args.args,  (this as any).isCurrentPlayerActive());
                break;   

            case 'stealCostumeCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringStealCostumeCard(args.args, (this as any).isCurrentPlayerActive());
                break;
            
            case 'leaveTokyoExchangeCard':
                this.setDiceSelectorVisibility(false);
                break;

            case 'buyCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringBuyCard(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'cardIsBought':
                this.onEnteringStepEvolution(args.args);
                break;
            case 'sellCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringSellCard(args.args);
                break;

            case 'answerQuestion':
                this.onEnteringAnswerQuestion(args.args);
                break;

            case 'endTurn':
                this.setDiceSelectorVisibility(false);
                this.onEnteringEndTurn();
                break;
        }
    }
    
    private showEvolutionsPopinPlayerButtons() {
        if (this.isPowerUpExpansion()) {
            Object.keys(this.gamedatas.players).forEach(playerId => document.getElementById(`see-monster-evolution-player-${playerId}`).classList.toggle('visible', true));
        }
    }

    private showActivePlayer(playerId: number) {
        this.playerTables.forEach(playerTable => playerTable.setActivePlayer(playerId == playerTable.playerId));
    }
    
    private setGamestateDescription(property: string = '') {
        const originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        if (this.gamedatas.gamestate.description !== `${originalState['description' + property]}`) {
            this.gamedatas.gamestate.description = `${originalState['description' + property]}`; 
            this.gamedatas.gamestate.descriptionmyturn = `${originalState['descriptionmyturn' + property]}`;
            (this as any).updatePageTitle();
        }
    }
    
    private removeGamestateDescription() {
        this.gamedatas.gamestate.description = ''; 
        this.gamedatas.gamestate.descriptionmyturn = ''; 
        (this as any).updatePageTitle();        
    }
    
    private onEnteringPickMonster(args: EnteringPickMonsterArgs) {
        // TODO clean only needed
        document.getElementById('monster-pick').innerHTML = '';
        args.availableMonsters.forEach(monster => {
            let html = `
            <div id="pick-monster-figure-${monster}-wrapper">
                <div id="pick-monster-figure-${monster}" class="monster-figure monster${monster}"></div>`;
            if (this.isPowerUpExpansion()) {
                html += `<div><button id="see-monster-evolution-${monster}" class="bgabutton bgabutton_blue see-evolutions-button"><div class="player-evolution-card"></div>${/*TODOPU_*/('Show Evolutions')}</button></div>`;
            }
            html += `</div>`;
            dojo.place(html, `monster-pick`);

            document.getElementById(`pick-monster-figure-${monster}`).addEventListener('click', () => this.pickMonster(monster));
            if (this.isPowerUpExpansion()) {
                document.getElementById(`see-monster-evolution-${monster}`).addEventListener('click', () => this.showMonsterEvolutions(monster));
            }
        });

        const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();
        dojo.toggleClass('monster-pick', 'selectable', isCurrentPlayerActive);
    }

    private onEnteringPickEvolutionForDeck(args: EnteringPickEvolutionForDeckArgs) {
        if (!document.getElementById('choose-evolution-in')) {
            dojo.place(`
                <div class="whiteblock">
                    <h3>${/*TODOPU_*/("Choose an Evolution in")}</h3>
                    <div id="choose-evolution-in" class="evolution-card-stock player-evolution-cards"></div>
                </div>
                <div class="whiteblock">
                    <h3>${/*TODOPU_*/("Evolutions in your deck")}</h3>
                    <div id="evolutions-in-deck" class="evolution-card-stock player-evolution-cards"></div>
                </div>
            `, 'mutant-evolution-choice');

            this.choseEvolutionInStock = new ebg.stock() as Stock;
            this.choseEvolutionInStock.setSelectionAppearance('class');
            this.choseEvolutionInStock.selectionClass = 'no-visible-selection';
            this.choseEvolutionInStock.create(this, $(`choose-evolution-in`), CARD_WIDTH, CARD_WIDTH);
            this.choseEvolutionInStock.setSelectionMode(2);
            this.choseEvolutionInStock.centerItems = true;
            this.choseEvolutionInStock.onItemCreate = (card_div, card_type_id) => this.evolutionCards.setupNewCard(card_div, card_type_id); 
            dojo.connect(this.choseEvolutionInStock, 'onChangeSelection', this, (_, item_id: string) => this.pickEvolutionForDeck(Number(item_id)));
            
            this.inDeckEvolutionsStock = new ebg.stock() as Stock;
            this.inDeckEvolutionsStock.setSelectionAppearance('class');
            this.inDeckEvolutionsStock.selectionClass = 'no-visible-selection';
            this.inDeckEvolutionsStock.create(this, $(`evolutions-in-deck`), CARD_WIDTH, CARD_WIDTH);
            this.inDeckEvolutionsStock.setSelectionMode(0);
            this.inDeckEvolutionsStock.centerItems = true;
            this.inDeckEvolutionsStock.onItemCreate = (card_div, card_type_id) => this.evolutionCards.setupNewCard(card_div, card_type_id); 

            this.evolutionCards.setupCards([this.choseEvolutionInStock, this.inDeckEvolutionsStock]);
        }

        this.choseEvolutionInStock.removeAll();
        args._private.chooseCardIn.forEach(card => this.choseEvolutionInStock.addToStockWithId(card.type, ''+card.id));
        
        args._private.inDeck.filter(card => !this.inDeckEvolutionsStock.items.some(item => Number(item.id) === card.id)).forEach(card => this.inDeckEvolutionsStock.addToStockWithId(card.type, ''+card.id));
    }

    private onEnteringChooseInitialCard(args: EnteringChooseInitialCardArgs) {
        let suffix = '';
        if (args.chooseEvolution) {
            suffix = args.chooseCostume ? 'evocostume' : 'evo';
        }
        this.setGamestateDescription(suffix);

        if (args.chooseCostume) {
            this.tableCenter.setInitialCards(args.cards);
            this.tableCenter.setVisibleCardsSelectionClass(args.chooseEvolution);
        }

        if ((this as any).isCurrentPlayerActive()) {
            this.tableCenter.setVisibleCardsSelectionMode(1);

            if (args.chooseEvolution) {
                const playerTable = this.getPlayerTable(this.getPlayerId());
                playerTable.showEvolutionPickStock(args._private.evolutions);
                playerTable.setVisibleCardsSelectionClass(args.chooseCostume);
            }
        }
    }
    
    private onEnteringStepEvolution(args: EnteringStepEvolutionArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            const playerId = this.getPlayerId();
            this.getPlayerTable(playerId).highlightHiddenEvolutions(args.highlighted.filter(card => card.location_arg === playerId));
        }
    }

    private onEnteringThrowDice(args: EnteringThrowDiceArgs) {
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? `last` : '');

        this.diceManager.showLock();

        const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();

        this.diceManager.setDiceForThrowDice(args.dice, args.selectableDice, args.canHealWithDice);
        
        if (isCurrentPlayerActive) {
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', dojo.string.substitute(_("Reroll dice (${number} roll(s) remaining)"), { 'number': args.maxThrowNumber-args.throwNumber }), () => this.onRethrow(), !args.dice.some(dice => !dice.locked));

                (this as any).addTooltip(
                    'rethrow_button', 
                    _("Click on dice you want to keep to lock them, then click this button to reroll the others"),
                    `${_("Ctrl+click to move all dice with same value")}<br>
                    ${_("Alt+click to move all dice but clicked die")}`);
            }

            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]') + ' ('+this.cards.getCardName(5, 'text-only')+')', () => this.rethrow3(), !args.rethrow3.hasDice3);
            }

            if (args.energyDrink?.hasCard && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + formatTextIcons(` ( 1[Energy])`), () => this.buyEnergyDrink());
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }

            if (args.hasSmokeCloud && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_smoke_cloud_button', _("Get extra die Roll") + ` (<span class="smoke-cloud token"></span>)`, () => this.useSmokeCloud());
            }

            if (args.hasCultist && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_cultist_button', _("Get extra die Roll") + ` (${_('Cultist')})`, () => this.useCultist());
            }

            if (args.rerollDie.isBeastForm) {
                dojo.place(`<div id="beast-form-dice-actions"></div>`, 'dice-actions');

                const simpleFaces = [];
                args.dice.filter(die => die.type < 2).forEach(die => {
                    if (die.canReroll && (die.type > 0 || !simpleFaces.includes(die.value))) {
                        const faceText = die.type == 1 ? BERSERK_DIE_STRINGS[die.value] : DICE_STRINGS[die.value];
                        this.createButton('beast-form-dice-actions', `rerollDie${die.id}_button`, _("Reroll") + formatTextIcons(' ' + faceText) + ' ('+this.cards.getCardName(301, 'text-only', 1)+')', () => this.rerollDie(die.id), !args.rerollDie.canUseBeastForm);

                        if (die.type == 0) {
                            simpleFaces.push(die.value);
                        }
                    }
                });
            }
        }

        if (args.throwNumber === args.maxThrowNumber && !args.hasSmokeCloud && !args.hasCultist && !args.energyDrink?.hasCard && (!args.rerollDie.isBeastForm || !args.rerollDie.canUseBeastForm)) {
            this.diceManager.disableDiceAction();
        }
    }

    private onEnteringChangeDie(args: EnteringChangeDieArgs, isCurrentPlayerActive: boolean) {
        if (args.dice?.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args.selectableDice, args, args.canHealWithDice);
        }

        if (isCurrentPlayerActive && args.dice && args.rethrow3?.hasCard) {
            if (document.getElementById('rethrow3changeDie_button')) {
                dojo.toggleClass('rethrow3changeDie_button', 'disabled', !args.rethrow3.hasDice3);
            } else {
                this.createButton('dice-actions', 'rethrow3changeDie_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3changeDie(), !args.rethrow3.hasDice3);
            }
        }
    }

    private onEnteringPsychicProbeRollDie(args: EnteringPsychicProbeRollDieArgs) {
        this.diceManager.setDiceForPsychicProbe(args.dice, args.selectableDice, args.canHealWithDice);

        if (args.dice && args.rethrow3?.hasCard && (this as any).isCurrentPlayerActive()) {
            if (document.getElementById('rethrow3psychicProbe_button')) {
                dojo.toggleClass('rethrow3psychicProbe_button', 'disabled', !args.rethrow3.hasDice3);
            } else {
                this.createButton('dice-actions', 'rethrow3psychicProbe_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3psychicProbe(), !args.rethrow3.hasDice3);
            }
        }
    }

    private onEnteringDiscardDie(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice);
        }
    }

    private onEnteringSelectExtraDie(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice);
        }
    }

    private onEnteringRerollOrDiscardDie(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, 'rerollOrDiscard');
        }
    }

    private onEnteringRerollDice(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, 'rerollDice');
        }
    }

    private onEnteringPrepareResolveDice(args: EnteringPrepareResolveDiceArgs, isCurrentPlayerActive: boolean) {
        if (args.hasEncasedInIce) {            
            this.setGamestateDescription('EncasedInIce');
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, isCurrentPlayerActive ? args.selectableDice : [], args.canHealWithDice, 'freezeDie');
        }
    }

    private onEnteringDiscardKeepCard(args: EnteringDiscardKeepCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringResolveNumberDice(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice);
        }
    }
    
    private onEnteringTakeWickednessTile(args: EnteringTakeWickednessTileArgs, isCurrentPlayerActive: boolean) {
        this.tableCenter.setWickednessTilesSelectable(args.level, true, isCurrentPlayerActive);

        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice);
        }
    }

    private onEnteringResolveHeartDice(args: EnteringResolveHeartDiceArgs, isCurrentPlayerActive: boolean) {
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice);

            if (isCurrentPlayerActive) {
                dojo.place(`<div id="heart-action-selector" class="whiteblock action-selector"></div>`, 'rolled-dice-and-rapid-actions', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    }

    private onEnteringResolveSmashDice(args: EnteringResolveSmashDiceArgs, isCurrentPlayerActive: boolean) {
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice);

            if (isCurrentPlayerActive) {
                dojo.place(`<div id="smash-action-selector" class="whiteblock action-selector"></div>`, 'rolled-dice-and-rapid-actions', 'after');
                new SmashActionSelector(this, 'smash-action-selector', args);
            }
        }
    }

    private onEnteringCancelDamage(args: EnteringCancelDamageArgs, isCurrentPlayerActive: boolean) {
        if (args.dice) {
            this.diceManager.showCamouflageRoll(args.dice);
        }

        if (!args.canCancelDamage && args.canHealToAvoidDeath) {
            this.setGamestateDescription('HealBeforeDamage');
        } else if (args.canCancelDamage) {
            this.setGamestateDescription('Reduce');
        }
        
        if (isCurrentPlayerActive) {
            if (args.dice && args.rethrow3?.hasCard) {
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.toggleClass('rethrow3camouflage_button', 'disabled', !args.rethrow3.hasDice3);
                } else {
                    this.createButton('dice-actions', 'rethrow3camouflage_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3camouflage(), !args.rethrow3.hasDice3);
                }
            }

            if (args.canThrowDices && !document.getElementById('throwCamouflageDice_button')) {
                (this as any).addActionButton('throwCamouflageDice_button', _("Throw dice"), 'throwCamouflageDice');
            } else if (!args.canThrowDices && document.getElementById('throwCamouflageDice_button')) {
                dojo.destroy('throwCamouflageDice_button');
            }

            if (args.canUseWings && !document.getElementById('useWings_button')) {
                (this as any).addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + " ( 2[Energy] )", { 'card_name': this.cards.getCardName(48, 'text-only')})), () => this.useWings());
                document.getElementById('useWings_button').dataset.enableAtEnergy = '2';
                if (args.playerEnergy < 2) {
                    dojo.addClass('useWings_button', 'disabled');
                }
            }

            if (args.canUseDetachableTail && !document.getElementById('useDetachableTail_button')) {
                (this as any).addActionButton('useDetachableTail_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(51, 'text-only')}), () => this.useInvincibleEvolution(51));
            }

            if (args.canUseRabbitsFoot && !document.getElementById('useRabbitsFoot_button')) {
                (this as any).addActionButton('useRabbitsFoot_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(143, 'text-only')}), () => this.useInvincibleEvolution(143));
            }

            if (args.countSuperJump > 0 && !document.getElementById('useSuperJump1_button')) {
                Object.keys(args.replaceHeartByEnergyCost).filter(energy => Number(energy) <= args.countSuperJump).forEach(energy => {
                    const energyCost = Number(energy);
                    const remainingDamage = args.replaceHeartByEnergyCost[energy];

                    const id = `useSuperJump${energyCost}_button`;
                    if (!document.getElementById(id)) {
                        (this as any).addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': this.cards.getCardName(53, 'text-only')}) + (remainingDamage > 0 ? ` (-${remainingDamage}[Heart])` : '')), () => this.useSuperJump(energyCost));
                        document.getElementById(id).dataset.enableAtEnergy = ''+energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }

            if (args.canUseRobot && !document.getElementById('useRobot1_button')) {
                Object.keys(args.replaceHeartByEnergyCost).forEach(energy => {
                    const energyCost = Number(energy);
                    const remainingDamage = args.replaceHeartByEnergyCost[energy];

                    const id = `useRobot${energyCost}_button`;
                    if (!document.getElementById(id)) {
                        (this as any).addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': this.cards.getCardName(210, 'text-only')}) + (remainingDamage > 0 ? ` (-${remainingDamage}[Heart])` : '')), () => this.useRobot(energyCost));
                        document.getElementById(id).dataset.enableAtEnergy = ''+energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }

            if (!args.canThrowDices && !document.getElementById('skipWings_button')) {
                const canAvoidDeath = args.canDoAction && args.skipMeansDeath && (args.canCancelDamage || args.canHealToAvoidDeath);
                (this as any).addActionButton(
                    'skipWings_button', 
                    args.canUseWings ? dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cards.getCardName(48, 'text-only')}) : _("Skip"), 
                    () => {
                        if (canAvoidDeath) {
                            (this as any).confirmationDialog(
                                formatTextIcons(_("Are you sure you want to Skip? It means [Skull]")), 
                                () => this.skipWings()
                            );
                        } else {
                            this.skipWings();
                        } 
                    },
                    null,
                    null,
                    canAvoidDeath ? 'red' : undefined
                );
                if (!args.canDoAction) {
                    this.startActionTimer('skipWings_button', ACTION_TIMER_DURATION);
                }
            }

            const rapidHealingSyncButtons = document.querySelectorAll(`[id^='rapidHealingSync_button'`);
            rapidHealingSyncButtons.forEach(rapidHealingSyncButton => rapidHealingSyncButton.parentElement.removeChild(rapidHealingSyncButton));
            if (args.canHeal && args.damageToCancelToSurvive > 0) {
                //this.rapidHealingSyncHearts = args.rapidHealingHearts;
                
                for (let i = Math.min(args.rapidHealingCultists, args.canHeal); i >= 0; i--) {
                    const cultistCount = i;
                    const rapidHealingCount = args.rapidHealingHearts > 0 ? args.canHeal - cultistCount : 0;
                    const cardsNames = [];

                    if (cultistCount > 0) {
                        cardsNames.push(_('Cultist'));
                    }
                    if (rapidHealingCount > 0) {
                        cardsNames.push(_(this.cards.getCardName(37, 'text-only')));
                    }

                    if (cultistCount + rapidHealingCount >= args.damageToCancelToSurvive && 2*rapidHealingCount <= args.playerEnergy) {
                        const text = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')}` + (rapidHealingCount > 0 ? ` (${2*rapidHealingCount}[Energy])` : '')), { 'card_name': cardsNames.join(', '), 'hearts': cultistCount + rapidHealingCount });
                        (this as any).addActionButton(`rapidHealingSync_button_${i}`, text, () => this.useRapidHealingSync(cultistCount, rapidHealingCount));
                    }
                }
            }
        }
    }

    private onEnteringChooseEvolutionCard(args: EnteringChooseEvolutionCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            this.getPlayerTable(this.getPlayerId()).showEvolutionPickStock(args._private.evolutions);
        }
    }

    private onEnteringStealCostumeCard(args: EnteringStealCostumeCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            this.setBuyDisabledCard(args);
        }
    }

    private onEnteringExchangeCard(args: EnteringExchangeCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringBuyCard(args: EnteringBuyCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            const stateName = this.getStateName();
            const bamboozle = stateName === 'answerQuestion' && this.gamedatas.gamestate.args.question.code === 'Bamboozle';        
            let playerId = this.getPlayerId();
            if (bamboozle) {
                playerId = this.gamedatas.gamestate.args.question.args.cardBeingBought.playerId;
            }

            this.tableCenter.setVisibleCardsSelectionMode(1);

            if (this.isPowerUpExpansion()) {                
                this.getPlayerTable(playerId).reservedCards.setSelectionMode(1);
            }

            this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(args.canBuyFromPlayers && playerTable.playerId != playerId ? 1 : 0));

            if (args._private?.pickCards?.length) {
                this.tableCenter.showPickStock(args._private.pickCards);
            }

            this.setBuyDisabledCard(args);
        }
    }

    private onEnteringChooseMimickedCard(args: EnteringBuyCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(1));
            this.setBuyDisabledCard(args);
        }
    }

    private onEnteringSellCard(args: EnteringBuyCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringAnswerQuestion(args: EnteringAnswerQuestionArgs) {
        const question = args.question;
        this.gamedatas.gamestate.description = question.description; 
        this.gamedatas.gamestate.descriptionmyturn = question.descriptionmyturn; 
        (this as any).updatePageTitle();

        switch(question.code) {
            case 'ChooseMimickedCard':
                this.onEnteringChooseMimickedCard(question.args.mimicArgs);
                break;
            case 'Bamboozle':
                const bamboozleArgs = question.args as BamboozleQuestionArgs;
                this.onEnteringBuyCard(bamboozleArgs.buyCardArgs, (this as any).isCurrentPlayerActive());
                break;

            case 'GazeOfTheSphinxSnake':
                if ((this as any).isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode(1);
                }
                break;

            case 'IcyReflection':
                if ((this as any).isCurrentPlayerActive()) {
                    const icyReflectionArgs = question.args as IcyReflectionQuestionArgs;
                    this.playerTables.forEach(playerTable => playerTable.visibleEvolutionCards.setSelectionMode(1));
                    icyReflectionArgs.disabledEvolutions.forEach(evolution => {
                        const cardDiv = document.querySelector(`div[id$="_item_${evolution.id}"]`) as HTMLElement;
                        if (cardDiv && cardDiv.closest('.player-evolution-cards') !== null) {
                            dojo.addClass(cardDiv, 'disabled');
                        }
                    });
                }
                break;
            case 'MiraculousCatch':
                const miraculousCatchArgs = question.args as MiraculousCatchQuestionArgs;
                const card = this.cards.generateCardDiv(miraculousCatchArgs.card);
                card.id = `miraculousCatch-card-${miraculousCatchArgs.card.id}`;
                dojo.place(`<div id="card-MiraculousCatch-wrapper" class="card-in-title-wrapper">${card.outerHTML}</div>`, `maintitlebar_content`);
                break;
            case 'DeepDive':
                const deepDiveCatchArgs = question.args as DeepDiveQuestionArgs;
                dojo.place(`<div id="card-DeepDive-wrapper" class="card-in-title-wrapper">${
                    deepDiveCatchArgs.cards.map(card => {
                        const cardDiv = this.cards.generateCardDiv(card);
                        cardDiv.id = `deepDive-card-${card.id}`;
                        return cardDiv.outerHTML;
                    }).join('')
                }</div>`, `maintitlebar_content`);
                break;
            case 'MyToy':
                this.tableCenter.setVisibleCardsSelectionMode(1);
                break;
        }
    }

    private onEnteringEndTurn() {
    }

    public onLeavingState(stateName: string) {
        log( 'Leaving state: '+stateName );

        if (this.isPowerUpExpansion()) {
            const evolutionCardsSingleState = this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE[stateName];
            if (evolutionCardsSingleState) {
                this.getPlayerTable(this.getPlayerId())?.setEvolutionCardsSingleState(evolutionCardsSingleState, false);
            }
        }

        switch (stateName) {
            case 'chooseInitialCard':                
                this.tableCenter.setVisibleCardsSelectionMode(0);
                this.tableCenter.setVisibleCardsSelectionClass(false);
                this.playerTables.forEach(playerTable => {
                    playerTable.hideEvolutionPickStock();
                    playerTable.setVisibleCardsSelectionClass(false);
                });
                break;
            case 'beforeStartTurn':
            case 'beforeResolveDice':
            case 'beforeEnteringTokyo':
            case 'afterEnteringTokyo':
            case 'cardIsBought':
                this.onLeavingStepEvolution();
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'opportunistChooseMimicCard':
            case 'chooseMimickedCardWickednessTile':
            case 'changeMimickedCardWickednessTile':
                this.onLeavingChooseMimickedCard();
                break;            
            case 'throwDice':
                document.getElementById('dice-actions').innerHTML = '';
                break;  
            case 'changeActivePlayerDie': case 'psychicProbeRollDie': // TODO remove
                if (document.getElementById('rethrow3psychicProbe_button')) {
                    dojo.destroy('rethrow3psychicProbe_button');
                }   
                break;
            case 'changeDie':                 
                if (document.getElementById('rethrow3changeDie_button')) {
                    dojo.destroy('rethrow3changeDie_button');
                }
                this.diceManager.removeAllBubbles();
                break; 
            case 'discardKeepCard':
                this.onLeavingSellCard();
                break;
            case 'rerollDice':
                this.diceManager.removeSelection();
                break;
            case 'takeWickednessTile':
                this.onLeavingTakeWickednessTile();
                break;
            case 'resolveHeartDiceAction':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDiceAction':
                if (document.getElementById('smash-action-selector')) {
                    dojo.destroy('smash-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;
            case 'chooseEvolutionCard':
                this.playerTables.forEach(playerTable => playerTable.hideEvolutionPickStock());
                break;         
            case 'leaveTokyo':
                this.removeSkipBuyPhaseToggle();
                break;
            case 'leaveTokyoExchangeCard':
            case 'stealCostumeCard':
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
                break;
            case 'cardIsBought':
                this.onLeavingStepEvolution();
                break;
            case 'sellCard':
                this.onLeavingSellCard();
                break;

            case 'cancelDamage':
                this.diceManager.removeAllDice();
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.destroy('rethrow3camouflage_button');
                }
                break;

            case 'answerQuestion':
                this.onLeavingAnswerQuestion();
                if (this.gamedatas.gamestate.args.question.code === 'Bamboozle') {
                    this.onLeavingBuyCard();
                }
                break;            
            case 'MyToy':
                this.tableCenter.setVisibleCardsSelectionMode(0);
                break;
        }
    }
    
    private onLeavingStepEvolution() {
        const playerId = this.getPlayerId();
        this.getPlayerTable(playerId)?.unhighlightHiddenEvolutions();
    }
    
    private onLeavingTakeWickednessTile() {
        this.tableCenter.setWickednessTilesSelectable(null, false, false);
    }

    private onLeavingBuyCard() {
        this.tableCenter.setVisibleCardsSelectionMode(0);
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(0));            
        this.tableCenter.hidePickStock();
    }

    private onLeavingChooseMimickedCard() {
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(0));
    }

    private onLeavingSellCard() {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(0));
            dojo.query('.stockitem').removeClass('disabled');
        }
    }
    
    private onLeavingAnswerQuestion() {
        const question: Question = this.gamedatas.gamestate.args.question;

        switch(question.code) {
            case 'Bamboozle':
                this.onLeavingBuyCard();
                break;
    
            case 'GazeOfTheSphinxSnake':
                if ((this as any).isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode(0);
                }
                break;

            case 'IcyReflection':
                if ((this as any).isCurrentPlayerActive()) {
                    this.playerTables.forEach(playerTable => playerTable.visibleEvolutionCards.setSelectionMode(0));
                    dojo.query('.stockitem').removeClass('disabled');
                }
                break;
            case 'MiraculousCatch':
                const card = document.getElementById(`card-MiraculousCatch-wrapper`);
                card?.parentElement?.removeChild(card);
                break;
            case 'DeepDive':
                const cards = document.getElementById(`card-DeepDive-wrapper`);
                cards?.parentElement?.removeChild(cards);
                break;
        }
    }

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    public onUpdateActionButtons(stateName: string, args: any) {

        switch (stateName) {
            case 'changeActivePlayerDie': case 'psychicProbeRollDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args); // because it's multiplayer, enter action must be set here
                break;
            case 'rerollDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollDice(args);
                break;
            case 'cheerleaderSupport':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args); // because it's multiplayer, enter action must be set here
                break;
            case 'leaveTokyo':
                this.setDiceSelectorVisibility(false);

                const argsLeaveTokyo = args as EnteringLeaveTokyoArgs;
                if (argsLeaveTokyo._private) {
                    this.addSkipBuyPhaseToggle(argsLeaveTokyo._private.skipBuyPhase);
                }
                break;
            case 'opportunistBuyCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'opportunistChooseMimicCard':
                this.setDiceSelectorVisibility(false);
                break;            
            case 'cancelDamage':
                const argsCancelDamage = args as EnteringCancelDamageArgs;
                this.setDiceSelectorVisibility(argsCancelDamage.canThrowDices || !!argsCancelDamage.dice);                
                this.onEnteringCancelDamage(argsCancelDamage, (this as any).isCurrentPlayerActive());

                // TODOBUG
                if (argsCancelDamage.canCancelDamage === undefined) {
                    try {
                        const tableId = window.location.search.split('=')[1];
                        if (tableId === '277711940' ||tableId === '277304366') {
                            (this as any).addActionButton('debugBlockedTable_button', "Skip error message", () => this.takeAction('debugBlockedTable', { tableId }));
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
                break;
        }

        if((this as any).isCurrentPlayerActive()) {
            switch (stateName) {
                case 'chooseInitialCard':
                    if (this.isInitialCardDoubleSelection()) {
                        (this as any).addActionButton('confirmInitialCards_button', _("Confirm"), () => this.chooseInitialCard(
                            Number(this.tableCenter.getVisibleCards().getSelectedItems()[0]?.id),
                            Number(this.getPlayerTable(this.getPlayerId()).pickEvolutionCards.getSelectedItems()[0]?.id),
                        ));
                        document.getElementById(`confirmInitialCards_button`).classList.add('disabled');
                    }
                    break;
                case 'beforeStartTurn':
                    (this as any).addActionButton('skipBeforeStartTurn_button', _("Skip"), () => this.skipBeforeStartTurn());
                    break;
                case 'changeMimickedCardWickednessTile':
                    (this as any).addActionButton('skipChangeMimickedCardWickednessTile_button', _("Skip"),  () => this.skipChangeMimickedCardWickednessTile());

                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCardWickednessTile_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeMimickedCard':
                    (this as any).addActionButton('skipChangeMimickedCard_button', _("Skip"), () => this.skipChangeMimickedCard());

                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCard_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'giveSymbolToActivePlayer':
                    const argsGiveSymbolToActivePlayer = args as EnteringGiveSymbolToActivePlayerArgs;
                    const SYMBOL_AS_STRING = ['[Heart]', '[Energy]', '[Star]'];
                    [4,5,0].forEach((symbol, symbolIndex) => {
                        (this as any).addActionButton(`giveSymbolToActivePlayer_button${symbol}`, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING[symbolIndex]})), () => this.giveSymbolToActivePlayer(symbol));
                        if (!argsGiveSymbolToActivePlayer.canGive[symbol]) {
                            dojo.addClass(`giveSymbolToActivePlayer_button${symbol}`, 'disabled');
                        }
                    });
                    document.getElementById(`giveSymbolToActivePlayer_button5`).dataset.enableAtEnergy = '1';
                    break;
                case 'throwDice':
                    (this as any).addActionButton('goToChangeDie_button', _("Resolve dice"), 'goToChangeDie', null, null, 'red');

                    const argsThrowDice = args as EnteringThrowDiceArgs;
                    if (!argsThrowDice.hasActions) {
                        this.startActionTimer('goToChangeDie_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeDie':
                    const argsChangeDie = args as EnteringChangeDieArgs;
                    if (argsChangeDie.hasYinYang) {
                        (this as any).addActionButton('useYinYang_button',dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCards.getCardName(138, 'text-only') }), () => this.useYinYang());
                    }

                    (this as any).addActionButton('resolve_button', _("Resolve dice"), () => this.resolveDice(), null, null, 'red');
                    break;
                case 'changeActivePlayerDie': case 'psychicProbeRollDie':
                    (this as any).addActionButton('changeActivePlayerDieSkip_button', _("Skip"), 'psychicProbeSkip');
                    break;
                case 'cheerleaderSupport':
                    (this as any).addActionButton('support_button', formatTextIcons(_("Support (add [diceSmash] )")), () => this.support());
                    (this as any).addActionButton('dontSupport_button', _("Don't support"), () => this.dontSupport());
                    break;
                case 'giveGoldenScarab':
                    const argsGiveGoldenScarab = args as EnteringGiveGoldenScarabArgs;
                    argsGiveGoldenScarab.playersIds.forEach(playerId => {
                        const player = this.gamedatas.players[playerId];
                        const label = `<div class="monster-icon monster${player.monster}" style="background-color: ${player.monster > 100 ? 'unset' : '#'+player.color};"></div> ${player.name}`;
                        (this as any).addActionButton(`giveGoldenScarab_button_${playerId}`, label, () => this.giveGoldenScarab(playerId));
                    });
                    break;
                case 'giveSymbols':
                    const argsGiveSymbols = args as EnteringGiveSymbolsArgs;
                    const SYMBOL_AS_STRING_PADDED = ['[Star]', null, null, null, '[Heart]', '[Energy]'];
                    argsGiveSymbols.combinations.forEach((combination, combinationIndex) => {
                        const symbols = SYMBOL_AS_STRING_PADDED[combination[0]] + (combination.length > 1 ? SYMBOL_AS_STRING_PADDED[combination[1]] : '');
                        (this as any).addActionButton(`giveSymbols_button${combinationIndex}`, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: symbols })), () => this.giveSymbols(combination));
                    });
                    break;
                case 'selectExtraDie':
                    for (let face=1; face<=6; face++) {
                        (this as any).addActionButton(`selectExtraDie_button${face}`, formatTextIcons(DICE_STRINGS[face]), () => this.selectExtraDie(face));
                    }
                    break;
                case 'rerollOrDiscardDie':
                    (this as any).addActionButton('falseBlessingReroll_button', _("Reroll"), () => {
                        dojo.addClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        this.falseBlessingAnkhAction = 'falseBlessingReroll';
                    }, null, null, 'gray');
                    (this as any).addActionButton('falseBlessingDiscard_button', _("Discard"), () => {
                        dojo.addClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        this.falseBlessingAnkhAction = 'falseBlessingDiscard';
                    }, null, null, 'gray');
                    (this as any).addActionButton('falseBlessingSkip_button', _("Skip"), () => this.falseBlessingSkip());
                    break;
                case 'rerollDice':
                    const argsRerollDice = args as EnteringRerollDiceArgs;
                    (this as any).addActionButton('rerollDice_button', _("Reroll selected dice"), () => this.rerollDice(this.diceManager.getSelectedDiceIds()));
                    dojo.addClass('rerollDice_button', 'disabled');
                    if (argsRerollDice.min === 0) {
                        (this as any).addActionButton('skipRerollDice_button', _("Skip"), () => this.rerollDice([]));
                    }
                    break;
                
                case 'resolveDice': 
                    const argsResolveDice = args as EnteringResolveDiceArgs;
                    if (argsResolveDice.canLeaveHibernation) {
                        (this as any).addActionButton('stayInHibernation_button', /*_TODODE*/("Stay in Hibernation"), () => this.stayInHibernation());
                        (this as any).addActionButton('leaveHibernation_button', /*_TODODE*/("Leave Hibernation"), () => this.leaveHibernation(), null, null, 'red');
                    }
                    break;
                case 'prepareResolveDice':
                    const argsPrepareResolveDice = args as EnteringPrepareResolveDiceArgs;
                    if (argsPrepareResolveDice.hasEncasedInIce) {
                        (this as any).addActionButton('skipFreezeDie_button', _("Skip"), () => this.skipFreezeDie());
                    }
                    break;
                case 'beforeResolveDice':
                    (this as any).addActionButton('skipBeforeResolveDice_button', _("Skip"), () => this.skipBeforeResolveDice());
                    break;
                case 'takeWickednessTile':
                    (this as any).addActionButton('skipTakeWickednessTile_button', _("Skip"), () => this.skipTakeWickednessTile());
                    const argsTakeWickednessTile = args as EnteringTakeWickednessTileArgs;
                    if (!argsTakeWickednessTile.canTake) {
                        this.startActionTimer('skipTakeWickednessTile_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'leaveTokyo':
                    let label = _("Stay in Tokyo");
                    const argsLeaveTokyo = args as EnteringLeaveTokyoArgs;
                    if (argsLeaveTokyo.canUseChestThumping && argsLeaveTokyo.activePlayerId == this.getPlayerId()) {
                        if (!this.smashedPlayersStillInTokyo) {
                            this.smashedPlayersStillInTokyo = argsLeaveTokyo.smashedPlayersInTokyo;
                        }
                         
                        this.smashedPlayersStillInTokyo.forEach(playerId => {
                            const player = this.gamedatas.players[playerId];
                            (this as any).addActionButton(`useChestThumping_button${playerId}`, dojo.string.substitute(/*TODOPU_*/("Force ${player_name} to Yield Tokyo"), { 'player_name': `<span style="color: #${player.color}">${player.name}</span>`}), () => this.useChestThumping(playerId))
                        });
                        (this as any).addActionButton('skipChestThumping_button', dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(45, 'text-only')}), () => this.skipChestThumping());
                    } else {
                        const playerHasJets = argsLeaveTokyo.jetsPlayers?.includes(this.getPlayerId());
                        const playerHasSimianScamper = argsLeaveTokyo.simianScamperPlayers?.includes(this.getPlayerId());
                        if (playerHasJets || playerHasSimianScamper) {
                            label += formatTextIcons(` (- ${argsLeaveTokyo.jetsDamage} [heart])`);
                        }
                        (this as any).addActionButton('stayInTokyo_button', label, () => this.onStayInTokyo());
                        (this as any).addActionButton('leaveTokyo_button', _("Leave Tokyo"), () => this.onLeaveTokyo(playerHasJets ? 24 : undefined));
                        if (playerHasSimianScamper) {
                            (this as any).addActionButton('leaveTokyoSimianScamper_button', _("Leave Tokyo") + ' : ' + dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCards.getCardName(42, 'text-only') }), () => this.onLeaveTokyo(3042));
                        }
                        if (!argsLeaveTokyo.canYieldTokyo[this.getPlayerId()]) {
                            this.startActionTimer('stayInTokyo_button', ACTION_TIMER_DURATION);
                            dojo.addClass('leaveTokyo_button', 'disabled');
                        }
                    }
                    break;

                case 'stealCostumeCard':
                    const argsStealCostumeCard = args as EnteringStealCostumeCardArgs;

                    (this as any).addActionButton('endStealCostume_button', _("Skip"), 'endStealCostume', null, null, 'red');

                    if (!argsStealCostumeCard.canBuyFromPlayers) {
                        this.startActionTimer('endStealCostume_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeForm':
                    const argsChangeForm = args as EnteringChangeFormArgs;
                    (this as any).addActionButton('changeForm_button',   dojo.string.substitute(_("Change to ${otherForm}"), {'otherForm' : _(argsChangeForm.otherForm)}) + formatTextIcons(` ( 1 [Energy])`), () => this.changeForm());
                    (this as any).addActionButton('skipChangeForm_button', _("Don't change form"), () => this.skipChangeForm());
                    dojo.toggleClass('changeForm_button', 'disabled', !argsChangeForm.canChangeForm);
                    document.getElementById(`changeForm_button`).dataset.enableAtEnergy = '1';
                    break;
                case 'leaveTokyoExchangeCard':
                    const argsExchangeCard = args as EnteringExchangeCardArgs;
                    (this as any).addActionButton('skipExchangeCard_button', _("Skip"), () => this.skipExchangeCard());

                    if (!argsExchangeCard.canExchange) {
                        this.startActionTimer('skipExchangeCard_button', ACTION_TIMER_DURATION);
                    }

                    this.onEnteringExchangeCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'beforeEnteringTokyo':
                    const argsBeforeEnteringTokyo = args as BeforeEnteringTokyoArgs;


                    if (argsBeforeEnteringTokyo.canUseFelineMotor.includes(this.getPlayerId())) {
                        (this as any).addActionButton('useFelineMotor_button', dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCards.getCardName(36, 'text-only') }), () => this.useFelineMotor());
                    } 

                    (this as any).addActionButton('skipBeforeEnteringTokyo_button', _("Skip"), () => this.skipBeforeEnteringTokyo());

                    break;
                case 'afterEnteringTokyo':
                    (this as any).addActionButton('skipAfterEnteringTokyo_button', _("Skip"), () => this.skipAfterEnteringTokyo());
                    break;
                case 'buyCard':
                    const argsBuyCard = args as EnteringBuyCardArgs;
                    if (argsBuyCard.canUseMiraculousCatch) {
                        (this as any).addActionButton('useMiraculousCatch_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(12, 'text-only')}), () => this.useMiraculousCatch());
                        if (!argsBuyCard.unusedMiraculousCatch) {
                            dojo.addClass('useMiraculousCatch_button', 'disabled');
                        }
                    }
                    if (argsBuyCard.canUseAdaptingTechnology) {
                        (this as any).addActionButton('renewAdaptiveTechnology_button', _("Renew cards") + ' (' + dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCards.getCardName(24, 'text-only')}) + ')', () => this.onRenew(3024));
                    }
                    (this as any).addActionButton('renew_button', _("Renew cards") + formatTextIcons(` ( 2 [Energy])`), () => this.onRenew(4));
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    if (argsBuyCard.canSell) {
                        (this as any).addActionButton('goToSellCard_button', _("End turn and sell cards"), 'goToSellCard');
                    }

                    (this as any).addActionButton('endTurn_button', argsBuyCard.canSell ? _("End turn without selling") : _("End turn"), 'onEndTurn', null, null, 'red');

                    if (!argsBuyCard.canBuyOrNenew && !argsBuyCard.canSell) {
                        this.startActionTimer('endTurn_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'opportunistBuyCard':
                    (this as any).addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');

                    if (!args.canBuy) {
                        this.startActionTimer('opportunistSkip_button', ACTION_TIMER_DURATION);
                    }

                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                    break;
                case 'cardIsBought':
                    (this as any).addActionButton('skipCardIsBought_button', _("Skip"), () => this.skipCardIsBought());
                    break;
                case 'sellCard':
                    (this as any).addActionButton('endTurnSellCard_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;

                case 'answerQuestion':
                    this.onUpdateActionButtonsAnswerQuestion(args);
            }

        }
    } 

    private onUpdateActionButtonsAnswerQuestion(args: EnteringAnswerQuestionArgs) {
        const question = args.question;

        switch(question.code) {
            case 'BambooSupply':
                const substituteParams = { card_name: this.evolutionCards.getCardName(136, 'text-only')};
                const putLabel = dojo.string.substitute(/*TODOPU_*/("Put ${number}[Energy] on ${card_name}"), {...substituteParams, number: 1});
                const takeLabel = dojo.string.substitute(/*TODOPU_*/("Take all [Energy] from ${card_name}"), substituteParams);
                (this as any).addActionButton('putEnergyOnBambooSupply_button', formatTextIcons(putLabel), () => this.putEnergyOnBambooSupply());
                (this as any).addActionButton('takeEnergyOnBambooSupply_button', formatTextIcons(takeLabel), () => this.takeEnergyOnBambooSupply());
                const bambooSupplyQuestionArgs = question.args as BambooSupplyQuestionArgs;
                if (!bambooSupplyQuestionArgs.canTake) {
                    dojo.addClass('takeEnergyOnBambooSupply_button', 'disabled');
                }
                break;

            case 'GazeOfTheSphinxAnkh':
                (this as any).addActionButton('gazeOfTheSphinxDrawEvolution_button', /*TODOPU_*/("Draw Evolution"), () => this.gazeOfTheSphinxDrawEvolution());
                (this as any).addActionButton('gazeOfTheSphinxGainEnergy_button', formatTextIcons(`${dojo.string.substitute(_('Gain ${energy}[Energy]'), { energy: 3})}`), () => this.gazeOfTheSphinxGainEnergy());
                break;

            case 'GazeOfTheSphinxSnake':
                (this as any).addActionButton('gazeOfTheSphinxLoseEnergy_button', formatTextIcons(`${dojo.string.substitute(_('Lose ${energy}[Energy]'), { energy: 3})}`), () => this.gazeOfTheSphinxLoseEnergy());
                const gazeOfTheSphinxLoseEnergyQuestionArgs = question.args as GazeOfTheSphinxSnakeQuestionArgs;
                if (!gazeOfTheSphinxLoseEnergyQuestionArgs.canLoseEnergy) {
                    dojo.addClass('gazeOfTheSphinxLoseEnergy_button', 'disabled');
                }
                break;

            case 'MegaPurr':
                const playerId = this.getPlayerId();
                const SYMBOL_AS_STRING = ['[Energy]', '[Star]'];
                [5,0].forEach((symbol, symbolIndex) => {
                    (this as any).addActionButton(`giveSymbol_button${symbol}`, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING[symbolIndex]})), () => this.giveSymbol(symbol));
                    if (symbol == 5 && !question.args[`canGive${symbol}`].includes(playerId)) {
                        dojo.addClass(`giveSymbol_button${symbol}`, 'disabled');
                    }
                });

            case 'FreezeRay':
                for (let face=1; face<=6; face++) {
                    (this as any).addActionButton(`selectFrozenDieFace_button${face}`, formatTextIcons(DICE_STRINGS[face]), () => this.chooseFreezeRayDieFace(face));
                }
                break;
            case 'MiraculousCatch':
                const miraculousCatchArgs = question.args as MiraculousCatchQuestionArgs;
                (this as any).addActionButton('buyCardMiraculousCatch_button', formatTextIcons(dojo.string.substitute(/*TODOPU_*/('Buy ${card_name} for ${cost}[Energy]'), { card_name: this.cards.getCardName(miraculousCatchArgs.card.type, 'text-only'), cost: miraculousCatchArgs.cost })), () => this.buyCardMiraculousCatch(false));
                if (miraculousCatchArgs.costSuperiorAlienTechnology !== null && miraculousCatchArgs.costSuperiorAlienTechnology !== miraculousCatchArgs.cost) {
                    (this as any).addActionButton('buyCardMiraculousCatchUseSuperiorAlienTechnology_button', formatTextIcons(dojo.string.substitute(/*_TODO*/('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCards.getCardName(28, 'text-only'), cost: miraculousCatchArgs.costSuperiorAlienTechnology })), () => this.buyCardMiraculousCatch(true));
                }
                (this as any).addActionButton('skipMiraculousCatch_button', formatTextIcons(dojo.string.substitute(/*TODOPU_*/('Discard ${card_name}'), { card_name: this.cards.getCardName(miraculousCatchArgs.card.type, 'text-only') })), () => this.skipMiraculousCatch());
                setTimeout(() => document.getElementById(`miraculousCatch-card-${miraculousCatchArgs.card.id}`)?.addEventListener('click', () => this.buyCardMiraculousCatch()), 250);

                document.getElementById('buyCardMiraculousCatch_button').dataset.enableAtEnergy = ''+miraculousCatchArgs.cost;
                dojo.toggleClass('buyCardMiraculousCatch_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < miraculousCatchArgs.cost);
                break;
            case 'DeepDive':
                const deepDiveCatchArgs = question.args as DeepDiveQuestionArgs;
                deepDiveCatchArgs.cards.forEach(card => {
                    (this as any).addActionButton(`playCardDeepDive_button${card.id}`, formatTextIcons(dojo.string.substitute(/*TODOPU_*/('Play ${card_name}'), { card_name: this.cards.getCardName(card.type, 'text-only') })), () => this.playCardDeepDive(card.id));
                    setTimeout(() => document.getElementById(`deepDive-card-${card.id}`)?.addEventListener('click', () => this.playCardDeepDive(card.id)), 250);
                });
                break;
            case 'ExoticArms':
                const useExoticArmsLabel = dojo.string.substitute(/*TODOPU_*/("Put ${number}[Energy] on ${card_name}"), { card_name: this.evolutionCards.getCardName(26, 'text-only'), number: 2 });
                
                (this as any).addActionButton('useExoticArms_button', formatTextIcons(useExoticArmsLabel), () => this.useExoticArms());
                (this as any).addActionButton('skipExoticArms_button', _('Skip'), () => this.skipExoticArms());
                dojo.toggleClass('useExoticArms_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useExoticArms_button').dataset.enableAtEnergy = '2';
                break;
            case 'TargetAcquired':
                const targetAcquiredCatchArgs = question.args as TargetAcquiredQuestionArgs;
                (this as any).addActionButton('giveTarget_button', dojo.string.substitute(/*TODOPU_*/("Give target to ${player_name}"), {'player_name': this.getPlayer(targetAcquiredCatchArgs.playerId).name}), () => this.giveTarget());
                (this as any).addActionButton('skipGiveTarget_button', _('Skip'), () => this.skipGiveTarget());
                break;
            case 'LightningArmor':
                (this as any).addActionButton('useLightningArmor_button', _("Throw dice"), () => this.useLightningArmor());
                (this as any).addActionButton('skipLightningArmor_button', _('Skip'), () => this.skipLightningArmor());
                break;
            case 'EnergySword':
                (this as any).addActionButton('useEnergySword_button',  dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCards.getCardName(147, 'text-only') }), () => this.answerEnergySword(true));
                (this as any).addActionButton('skipEnergySword_button', _('Skip'), () => this.answerEnergySword(false));
                dojo.toggleClass('useEnergySword_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useEnergySword_button').dataset.enableAtEnergy = '2';
                break;
            case 'SunkenTemple':
                (this as any).addActionButton('useSunkenTemple_button',  dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCards.getCardName(157, 'text-only') }), () => this.answerSunkenTemple(true));
                (this as any).addActionButton('skipSunkenTemple_button', _('Skip'), () => this.answerSunkenTemple(false));
                break;
            case 'ElectricCarrot':
                (this as any).addActionButton('answerElectricCarrot5_button',  formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: '[Energy]'})), () => this.answerElectricCarrot(5));
                dojo.toggleClass('answerElectricCarrot5_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 4);
                document.getElementById('answerElectricCarrot5_button').dataset.enableAtEnergy = '1';
                (this as any).addActionButton('answerElectricCarrot4_button',  formatTextIcons(/*TODOPU_*/("Lose 1 extra [Heart]")), () => this.answerElectricCarrot(4));
                break;
        }
    }

    ///////////////////////////////////////////////////
    //// Utility methods


    ///////////////////////////////////////////////////

    public getPlayerId(): number {
        return Number((this as any).player_id);
    }

    public isHalloweenExpansion(): boolean {
        return this.gamedatas.halloweenExpansion;
    }

    public isKingkongExpansion(): boolean {
        return this.gamedatas.kingkongExpansion;
    }

    public isCybertoothExpansion(): boolean {
        return this.gamedatas.cybertoothExpansion;
    }

    public isMutantEvolutionVariant(): boolean {
        return this.gamedatas.mutantEvolutionVariant;
    }

    public isCthulhuExpansion(): boolean {
        return this.gamedatas.cthulhuExpansion;
    }

    public isAnubisExpansion(): boolean {
        return this.gamedatas.anubisExpansion;
    }

    public isWickednessExpansion(): boolean {
        return this.gamedatas.wickednessExpansion;
    }

    public isPowerUpExpansion(): boolean {
        return this.gamedatas.powerUpExpansion;
    }

    public isDarkEdition(): boolean {
        return this.gamedatas.darkEdition;
    }

    public isDefaultFont(): boolean {
        return Number((this as any).prefs[201].value) == 1;
    }

    public getPlayer(playerId: number): KingOfTokyoPlayer {
        return this.gamedatas.players[playerId];
    }

    public createButton(destinationId: string, id: string, text: string, callback: Function, disabled: boolean = false) {
        const html = `<button class="action-button bgabutton bgabutton_blue" id="${id}">
            ${text}
        </button>`;
        dojo.place(html, destinationId);
        if (disabled) {
            dojo.addClass(id, 'disabled');
        }
        document.getElementById(id).addEventListener('click', () => callback());
    }

    private addTwoPlayerVariantNotice(gamedatas: KingOfTokyoGamedatas) {
        dojo.addClass('board', 'twoPlayersVariant');

        // 2-players variant notice
        if (Object.keys(gamedatas.players).length == 2 && (this as any).prefs[203]?.value == 1) {
            dojo.place(`
                    <div id="board-corner-highlight"></div>
                    <div id="twoPlayersVariant-message">
                        ${_("You are playing the 2-players variant.")}<br>
                        ${_("When entering or starting a turn on Tokyo, you gain 1 energy instead of points")}.<br>
                        ${_("You can check if variant is activated in the bottom left corner of the table.")}<br>
                        <div style="text-align: center"><a id="hide-twoPlayersVariant-message">${_("Dismiss")}</a></div>
                    </div>
                `, 'board');

            document.getElementById('hide-twoPlayersVariant-message').addEventListener('click', () => {
                const select = document.getElementById('preference_control_203') as HTMLSelectElement;
                select.value = '2';

                var event = new Event('change');
                select.dispatchEvent(event);
            });
        }
    }

    private getOrderedPlayers(): KingOfTokyoPlayer[] {
        return Object.values(this.gamedatas.players).sort((a,b) => Number(a.player_no) - Number(b.player_no));
    }

    private createPlayerPanels(gamedatas: KingOfTokyoGamedatas) {

        Object.values(gamedatas.players).forEach(player => {
            const playerId = Number(player.id);  

            const eliminated = Number(player.eliminated) > 0 || player.playerDead > 0;

            // health & energy counters
            let html = `<div class="counters">
                <div id="health-counter-wrapper-${player.id}" class="counter">
                    <div class="icon health"></div> 
                    <span id="health-counter-${player.id}"></span>
                </div>
                <div id="energy-counter-wrapper-${player.id}" class="counter">
                    <div class="icon energy"></div> 
                    <span id="energy-counter-${player.id}"></span>
                </div>`;
            if (gamedatas.wickednessExpansion) {
                html += `
                <div id="wickedness-counter-wrapper-${player.id}" class="counter">
                    <div class="icon wickedness"></div> 
                    <span id="wickedness-counter-${player.id}"></span>
                </div>`;
            }
            html += `</div>`;
            dojo.place(html, `player_board_${player.id}`);

            (this as any).addTooltipHtml(`health-counter-wrapper-${player.id}`, _("Health"));
            (this as any).addTooltipHtml(`energy-counter-wrapper-${player.id}`, _("Energy"));
            if (gamedatas.wickednessExpansion) {
                (this as any).addTooltipHtml(`wickedness-counter-wrapper-${player.id}`, _("Wickedness points"));
            }

            if (gamedatas.kingkongExpansion || gamedatas.cybertoothExpansion || gamedatas.cthulhuExpansion) {
                let html = `<div class="counters">`;

                if (gamedatas.cthulhuExpansion) {
                    html += `
                    <div id="cultist-counter-wrapper-${player.id}" class="counter cultist-tooltip">
                        <div class="icon cultist"></div>
                        <span id="cultist-counter-${player.id}"></span>
                    </div>`;
                }

                if (gamedatas.kingkongExpansion) {
                    html += `<div id="tokyo-tower-counter-wrapper-${player.id}" class="counter tokyo-tower-tooltip">`;
                    for (let level = 1; level <= 3 ; level++) {
                        html += `<div id="tokyo-tower-icon-${player.id}-level-${level}" class="tokyo-tower-icon level${level}" data-owned="${player.tokyoTowerLevels.includes(level).toString()}"></div>`;
                    }
                    html += `</div>`;
                }

                if (gamedatas.cybertoothExpansion) {
                    html += `
                    <div id="berserk-counter-wrapper-${player.id}" class="counter berserk-tooltip">
                        <div class="berserk-icon-wrapper">
                            <div id="player-panel-berserk-${player.id}" class="berserk icon ${player.berserk ? 'active' : ''}"></div>
                        </div>
                    </div>`;
                }

                html += `</div>`;
                dojo.place(html, `player_board_${player.id}`);

                if (gamedatas.cthulhuExpansion) {
                    const cultistCounter = new ebg.counter();
                    cultistCounter.create(`cultist-counter-${player.id}`);
                    cultistCounter.setValue(player.cultists);
                    this.cultistCounters[playerId] = cultistCounter;
                }
            }

            const healthCounter = new ebg.counter();
            healthCounter.create(`health-counter-${player.id}`);
            healthCounter.setValue(player.health);
            this.healthCounters[playerId] = healthCounter;

            const energyCounter = new ebg.counter();
            energyCounter.create(`energy-counter-${player.id}`);
            energyCounter.setValue(player.energy);
            this.energyCounters[playerId] = energyCounter;

            if (gamedatas.wickednessExpansion) {
                const wickednessCounter = new ebg.counter();
                wickednessCounter.create(`wickedness-counter-${player.id}`);
                wickednessCounter.setValue(player.wickedness);
                this.wickednessCounters[playerId] = wickednessCounter;
            }

            if (gamedatas.powerUpExpansion) {
                // hand cards counter
                dojo.place(`<div class="counters">
                    <div id="playerhand-counter-wrapper-${player.id}" class="playerhand-counter">
                        <div class="player-evolution-card"></div>
                        <div class="player-hand-card"></div> 
                        <span id="playerhand-counter-${player.id}"></span>
                    </div>
                    <div class="show-evolutions-button">
                    <button id="see-monster-evolution-player-${playerId}" class="bgabutton bgabutton_gray ${this.gamedatas.gamestate.id >= 15 /*ST_PLAYER_CHOOSE_INITIAL_CARD*/ ? 'visible' : ''}">
                        ${/*TODOPU_*/('Show Evolutions')}
                    </button>
                    </div>
                </div>`, `player_board_${player.id}`);

                const handCounter = new ebg.counter();
                handCounter.create(`playerhand-counter-${playerId}`);
                handCounter.setValue(player.hiddenEvolutions.length);
                this.handCounters[playerId] = handCounter;

                (this as any).addTooltipHtml(`playerhand-counter-wrapper-${player.id}`, /* TODOPU_*/("Number of Evolution cards in hand."));

                document.getElementById(`see-monster-evolution-player-${playerId}`).addEventListener('click', () => this.showPlayerEvolutions(playerId));
            }

            dojo.place(`<div class="player-tokens">
                <div id="player-board-shrink-ray-tokens-${player.id}" class="player-token shrink-ray-tokens"></div>
                <div id="player-board-poison-tokens-${player.id}" class="player-token poison-tokens"></div>
            </div>`, `player_board_${player.id}`);

            if (!eliminated) {
                this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
                this.setPoisonTokens(playerId, player.poisonTokens);
            }

            dojo.place(`<div id="player-board-monster-figure-${player.id}" class="monster-figure monster${player.monster}"><div class="kot-token"></div></div>`, `player_board_${player.id}`);

            if (player.location > 0) {
                dojo.addClass(`overall_player_board_${playerId}`, 'intokyo');
            }
            if (eliminated) {
                setTimeout(() => this.eliminatePlayer(playerId), 200);
            }
        });

        (this as any).addTooltipHtmlToClass('shrink-ray-tokens', this.SHINK_RAY_TOKEN_TOOLTIP);
        (this as any).addTooltipHtmlToClass('poison-tokens', this.POISON_TOKEN_TOOLTIP);
    }
    
    private createPlayerTables(gamedatas: KingOfTokyoGamedatas) {
        const evolutionCardsWithSingleState = this.isPowerUpExpansion() ?
          Object.values(this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE).reduce((a1, a2) => [...a1, ...a2], []) :
          null;
        this.playerTables = this.getOrderedPlayers().map(player => {
            const playerId = Number(player.id);
            const playerWithGoldenScarab = gamedatas.anubisExpansion && playerId === gamedatas.playerWithGoldenScarab;
            return new PlayerTable(this, player, playerWithGoldenScarab, evolutionCardsWithSingleState);
        });

        if (gamedatas.targetedPlayer) {
            this.getPlayerTable(gamedatas.targetedPlayer).giveTarget();
        }
    }

    private getPlayerTable(playerId: number): PlayerTable {
        return this.playerTables.find(playerTable => playerTable.playerId === Number(playerId));
    }

    private isInitialCardDoubleSelection() {
        const args = this.gamedatas.gamestate.args as EnteringChooseInitialCardArgs;
        return args.chooseCostume && args.chooseEvolution;
    }

    private confirmDoubleSelectionCheckState() {
        const costumeSelected = this.tableCenter.getVisibleCards()?.getSelectedItems().length === 1;
        const evolutionSelected = this.getPlayerTable(this.getPlayerId())?.pickEvolutionCards.getSelectedItems().length === 1;
        document.getElementById(`confirmInitialCards_button`)?.classList.toggle('disabled', !costumeSelected || !evolutionSelected);
    }

    private setDiceSelectorVisibility(visible: boolean) {
        const div = document.getElementById('rolled-dice');
        div.style.display = visible ? 'flex' : 'none';
    }

    public getZoom() {
        return this.tableManager.zoom;
    }

    public getPreferencesManager(): PreferencesManager {
        return this.preferencesManager;
    }

    private removeMonsterChoice() {
        if (document.getElementById('monster-pick')) {
            (this as any).fadeOutAndDestroy('monster-pick');
        }
    }

    private removeMutantEvolutionChoice() {
        if (document.getElementById('mutant-evolution-choice')) {
            (this as any).fadeOutAndDestroy('mutant-evolution-choice');
        }
    }

    private showMainTable() {
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            dojo.removeClass('kot-table', 'pickMonsterOrEvolutionDeck');
            this.tableManager.setAutoZoomAndPlacePlayerTables();
            this.tableCenter.getVisibleCards().updateDisplay();
            this.playerTables.forEach(playerTable => playerTable.cards.updateDisplay());
        }
    }

    private getStateName() {
        return this.gamedatas.gamestate.name;
    }

    public toggleRerollDiceButton(): void {
        const args = (this.gamedatas.gamestate.args as EnteringRerollDiceArgs);
        const selectedDiceCount = this.diceManager.getSelectedDiceIds().length;
        const canReroll = selectedDiceCount >= args.min && selectedDiceCount <= args.max;
        dojo.toggleClass('rerollDice_button', 'disabled', !canReroll);
    }

    public onVisibleCardClick(stock: Stock, cardId: number, from: number = 0, warningChecked: boolean = false) { // from : player id
        if (!cardId) {
            return;
        }

        if (dojo.hasClass(`${stock.container_div.id}_item_${cardId}`, 'disabled')) {
            stock.unselectItem(''+cardId);
            return;
        }

        const stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            if (!this.isInitialCardDoubleSelection()) {
                this.chooseInitialCard(Number(cardId), null);
            } else {
                this.confirmDoubleSelectionCheckState();
            }
        } else if (stateName === 'stealCostumeCard') {
            this.stealCostumeCard(cardId);
        } else if (stateName === 'sellCard') {
            this.sellCard(cardId);
        } else if (stateName === 'chooseMimickedCard' || stateName === 'opportunistChooseMimicCard') {
            this.chooseMimickedCard(cardId);
        } else if (stateName === 'changeMimickedCard') {
            this.changeMimickedCard(cardId);
        } else if (stateName === 'chooseMimickedCardWickednessTile') {
            this.chooseMimickedCardWickednessTile(cardId);
        } else if (stateName === 'changeMimickedCardWickednessTile') {
            this.changeMimickedCardWickednessTile(cardId);
        } else if (stateName === 'buyCard' || stateName === 'opportunistBuyCard') {
            const buyCardArgs = this.gamedatas.gamestate.args as EnteringBuyCardArgs;
            const warningIcon = !warningChecked && buyCardArgs.warningIds[cardId];
            if (warningIcon) {
                (this as any).confirmationDialog(
                    formatTextIcons(dojo.string.substitute(_("Are you sure you want to buy that card? You won't gain ${symbol}"), { symbol: warningIcon})), 
                    () => this.onVisibleCardClick(stock, cardId, from, true)
                );
            } else {
                const cardCostSuperiorAlienTechnology = buyCardArgs.cardsCostsSuperiorAlienTechnology?.[cardId];
                if (cardCostSuperiorAlienTechnology !== null && cardCostSuperiorAlienTechnology !== undefined && cardCostSuperiorAlienTechnology !== buyCardArgs.cardsCosts[cardId]) {
                    const keys = [
                        formatTextIcons(dojo.string.substitute(/*_TODO*/('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCards.getCardName(28, 'text-only'), cost: cardCostSuperiorAlienTechnology })),
                        formatTextIcons(dojo.string.substitute(/*_TODO*/('Don\'t use ${card_name} and pay full cost ${cost}[Energy]'), { card_name: this.evolutionCards.getCardName(28, 'text-only'), cost: buyCardArgs.cardsCosts[cardId] })),
                        _('Cancel')
                    ];

                    (this as any).multipleChoiceDialog(
                        dojo.string.substitute(_('Do you want to buy the card at reduced cost with ${card_name} ?'), { 'card_name': this.evolutionCards.getCardName(28, 'text-only')}), 
                        keys, 
                        (choice) => {
                            const choiceIndex = Number(choice);
                            if (choiceIndex < 2) {
                                this.tableCenter.removeOtherCardsFromPick(cardId);
                                this.buyCard(cardId, from, choiceIndex === 0);
                            }
                        }
                      );

                      if (buyCardArgs.canUseSuperiorAlienTechnology === false) {
                        document.getElementById(`choice_btn_0`).classList.add('disabled');
                      }
                      if (buyCardArgs.cardsCosts[cardId] > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById(`choice_btn_1`).classList.add('disabled');
                      }
                } else {
                    this.tableCenter.removeOtherCardsFromPick(cardId);
                    this.buyCard(cardId, from);
                }
            }
        } else if (stateName === 'discardKeepCard') {
            this.discardKeepCard(cardId);
        } else if (stateName === 'leaveTokyoExchangeCard') {
            this.exchangeCard(cardId);
        } else if (stateName === 'answerQuestion') {
            const args = this.gamedatas.gamestate.args as EnteringAnswerQuestionArgs;
            if (args.question.code === 'Bamboozle') {
                this.buyCardBamboozle(cardId, from);
            } else if (args.question.code === 'ChooseMimickedCard') {
                this.chooseMimickedCard(cardId);
            } else if (args.question.code === 'MyToy') {
                this.reserveCard(cardId);
            }
        }
    }

    public chooseEvolutionCardClick(id: number) {
        const stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            if (!this.isInitialCardDoubleSelection()) {
                this.chooseInitialCard(null, id);
            } else {
                this.confirmDoubleSelectionCheckState();
            }
        } else if (stateName === 'chooseEvolutionCard') {
            this.chooseEvolutionCard(id);
        }
    }

    public onHiddenEvolutionClick(cardId: number) {
        const stateName = this.getStateName();
        if (stateName === 'answerQuestion') {
            const args = this.gamedatas.gamestate.args as EnteringAnswerQuestionArgs;
            if (args.question.code === 'GazeOfTheSphinxSnake') {
                this.gazeOfTheSphinxDiscardEvolution(Number(cardId));
                return;
            }
        }
        
        this.playEvolution(cardId);
    }

    public onVisibleEvolutionClick(cardId: number) {
        const stateName = this.getStateName();
        if (stateName === 'answerQuestion') {
            const args = this.gamedatas.gamestate.args as EnteringAnswerQuestionArgs;
            if (args.question.code === 'GazeOfTheSphinxSnake') {
                this.gazeOfTheSphinxDiscardEvolution(Number(cardId));
            } else if (args.question.code === 'IcyReflection') {
                this.chooseMimickedEvolution(Number(cardId));
            }
        }
    }
    
    private setBuyDisabledCardByCost(disabledIds: number[], cardsCosts: { [cardId: number]: number }, playerEnergy: number) {
        const disabledCardsIds = [...disabledIds, ...Object.keys(cardsCosts).map(cardId => Number(cardId))];
        disabledCardsIds.forEach(id => {
            const disabled = disabledIds.some(disabledId => disabledId == id) || cardsCosts[id] > playerEnergy;
            const cardDiv = document.querySelector(`.card-stock div[id$="_item_${id}"]`) as HTMLElement;
            cardDiv?.classList.toggle('disabled', disabled);
        });
    }

    // called on state enter and when energy number is changed
    private setBuyDisabledCard(args: EnteringBuyCardArgs | EnteringStealCostumeCardArgs = null, playerEnergy: number = null) {
        if (!(this as any).isCurrentPlayerActive()) {
            return;
        }
        
        const stateName = this.getStateName();
        const buyState = stateName === 'buyCard' || stateName === 'opportunistBuyCard' || stateName === 'stealCostumeCard' || (stateName === 'answerQuestion' && ['ChooseMimickedCard', 'Bamboozle'].includes(this.gamedatas.gamestate.args.question.code));
        const changeMimicState = stateName === 'changeMimickedCard' || stateName === 'changeMimickedCardWickednessTile';
        if (!buyState && !changeMimicState) {
            return;
        }
        const bamboozle = stateName === 'answerQuestion' && this.gamedatas.gamestate.args.question.code === 'Bamboozle';        
        let playerId = this.getPlayerId();
        if (bamboozle) {
            playerId = this.gamedatas.gamestate.args.question.args.cardBeingBought.playerId;
            playerEnergy = this.energyCounters[playerId].getValue();
        }
        if (args === null) {
            args = bamboozle ?
                this.gamedatas.gamestate.args.question.args.buyCardArgs :
                this.gamedatas.gamestate.args;
        }
        if (playerEnergy === null) {
            playerEnergy = this.energyCounters[playerId].getValue();
        }

        let cardsCosts = args.cardsCosts;
        if ((args as EnteringBuyCardArgs).gotSuperiorAlienTechnology) {
            cardsCosts = {...cardsCosts, ...(args as EnteringBuyCardArgs).cardsCostsSuperiorAlienTechnology};
        }

        this.setBuyDisabledCardByCost(args.disabledIds, cardsCosts, playerEnergy);

        // renew button
        if (buyState && document.getElementById('renew_button')) {
            dojo.toggleClass('renew_button', 'disabled', playerEnergy < 2);
        }
    }

    private addRapidHealingButton(userEnergy: number, isMaxHealth: boolean) {
        if (!document.getElementById('rapidHealingButton')) {
            this.createButton(
                'rapid-actions-wrapper', 
                'rapidHealingButton', 
                dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (2[Energy])`), { card_name: this.cards.getCardName(37, 'text-only'), hearts: 1 }), 
                () => this.useRapidHealing(), 
                userEnergy < 2 || isMaxHealth
            );
        }
    }

    private removeRapidHealingButton() {
        if (document.getElementById('rapidHealingButton')) {
            dojo.destroy('rapidHealingButton');
        }
    }

    private addMothershipSupportButton(userEnergy: number, isMaxHealth: boolean) {
        if (!document.getElementById('mothershipSupportButton')) {
            this.createButton(
                'rapid-actions-wrapper', 
                'mothershipSupportButton', 
                dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (1[Energy])`), { card_name: this.evolutionCards.getCardName(27, 'text-only'), hearts: 1 }), 
                () => this.useMothershipSupport(), 
                this.gamedatas.players[this.getPlayerId()].mothershipSupportUsed || userEnergy < 1 || isMaxHealth
            );
        }
    }

    private removeMothershipSupportButton() {
        if (document.getElementById('mothershipSupportButton')) {
            dojo.destroy('mothershipSupportButton');
        }
    }

    private addRapidCultistButtons(isMaxHealth: boolean) {
        if (!document.getElementById('rapidCultistButtons')) {
            dojo.place(`<div id="rapidCultistButtons"><span>${dojo.string.substitute(_('Use ${card_name}'), { card_name: _('Cultist') })} :</span></div>`, 'rapid-actions-wrapper');
            this.createButton(
                'rapidCultistButtons', 
                'rapidCultistHealthButton', 
                formatTextIcons(`${dojo.string.substitute(_('Gain ${hearts}[Heart]'), { hearts: 1})}`), 
                () => this.useRapidCultist(4), 
                isMaxHealth
            );
            
            this.createButton(
                'rapidCultistButtons', 
                'rapidCultistEnergyButton', 
                formatTextIcons(`${dojo.string.substitute(_('Gain ${energy}[Energy]'), { energy: 1})}`), 
                () => this.useRapidCultist(5)
            );
        }
    }

    private removeRapidCultistButtons() {
        if (document.getElementById('rapidCultistButtons')) {
            dojo.destroy('rapidCultistButtons');
        }
    }

    private checkRapidHealingButtonState() {
        if (document.getElementById('rapidHealingButton')) {
            const playerId = this.getPlayerId();
            const userEnergy = this.energyCounters[playerId].getValue();
            const health = this.healthCounters[playerId].getValue();
            const maxHealth = this.gamedatas.players[playerId].maxHealth;
            dojo.toggleClass('rapidHealingButton', 'disabled', userEnergy < 2 || health >= maxHealth);
        }
    }

    private checkMothershipSupportButtonState() {
        if (document.getElementById('mothershipSupportButton')) {
            const playerId = this.getPlayerId();
            const userEnergy = this.energyCounters[playerId].getValue();
            const health = this.healthCounters[playerId].getValue();
            const maxHealth = this.gamedatas.players[playerId].maxHealth;
            const used = this.gamedatas.players[playerId].mothershipSupportUsed;
            dojo.toggleClass('mothershipSupportButton', 'disabled', used || userEnergy < 1 || health >= maxHealth);
        }
    }

    private checkHealthCultistButtonState() {
        if (document.getElementById('rapidCultistHealthButton')) {
            const playerId = this.getPlayerId();
            const health = this.healthCounters[playerId].getValue();
            const maxHealth = this.gamedatas.players[playerId].maxHealth;
            dojo.toggleClass('rapidCultistHealthButton', 'disabled', health >= maxHealth);
        }
    }

    private addSkipBuyPhaseToggle(active: boolean) {
        if (!document.getElementById('skipBuyPhaseWrapper')) {
            dojo.place(`<div id="skipBuyPhaseWrapper">
                <label class="switch">
                    <input id="skipBuyPhaseCheckbox" type="checkbox" ${active ? 'checked' : ''}>
                    <span class="slider round"></span>
                </label>
                <label for="skipBuyPhaseCheckbox" class="text-label">${_("Skip buy phase")}</label>
            </div>`, 'rapid-actions-wrapper');

            document.getElementById('skipBuyPhaseCheckbox').addEventListener('change', (e: any) => this.setSkipBuyPhase(e.target.checked));
        }
    }

    private removeSkipBuyPhaseToggle() {
        if (document.getElementById('skipBuyPhaseWrapper')) {
            dojo.destroy('skipBuyPhaseWrapper');
        }
    }

    private addAutoLeaveUnderButton() {
        if (!document.getElementById('autoLeaveUnderButton')) {
            this.createButton(
                'rapid-actions-wrapper', 
                'autoLeaveUnderButton', 
                _("Leave Tokyo") + ' &#x25BE;', 
                () => this.toggleAutoLeaveUnderPopin(), 
            );
        }
    }

    private removeAutoLeaveUnderButton() {
        if (document.getElementById('autoLeaveUnderButton')) {
            dojo.destroy('autoLeaveUnderButton');
        }
    }

    private toggleAutoLeaveUnderPopin() {
        const bubble = document.getElementById(`discussion_bubble_autoLeaveUnder`);
        if (bubble?.dataset.visible === 'true') {
            this.closeAutoLeaveUnderPopin();
        } else {
            this.openAutoLeaveUnderPopin();
        }
    }

    private openAutoLeaveUnderPopin() {
        const popinId = `discussion_bubble_autoLeaveUnder`;
        let bubble = document.getElementById(popinId);
        if (!bubble) { 
            const maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
            let html = `<div id="${popinId}" class="discussion_bubble autoLeaveUnderBubble">
                <div>${_("Automatically leave tokyo when life goes down to, or under")}</div>
                <div id="${popinId}-buttons" class="button-grid">`;
            for (let i = maxHealth; i>0; i--) {
                html += `<button class="action-button bgabutton ${this.gamedatas.leaveTokyoUnder === i || (i == 1 && !this.gamedatas.leaveTokyoUnder) ? 'bgabutton_blue' : 'bgabutton_gray'} autoLeaveButton ${i == 1 ? 'disable' : ''}" id="${popinId}_set${i}">
                    ${i == 1 ? _('Disabled') : i-1}
                </button>`;
            }
            html += `</div>
            <div>${_("If your life is over it, or if disabled, you'll be asked if you want to stay or leave")}</div>
            <hr>
            <div>${_("Automatically stay in tokyo when life is at least")}</div>
                <div id="${popinId}-stay-buttons" class="button-grid">`;
            for (let i = maxHealth + 1; i>2; i--) {
                html += `<button class="action-button bgabutton ${this.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray'} autoStayButton ${this.gamedatas.leaveTokyoUnder > 0 && i <= this.gamedatas.leaveTokyoUnder ? 'disabled' : ''}" id="${popinId}_setStay${i}">${i-1}</button>`;
            }
            html += `<button class="action-button bgabutton ${!this.gamedatas.stayTokyoOver ? 'bgabutton_blue' : 'bgabutton_gray'} autoStayButton disable" id="${popinId}_setStay0">${_('Disabled')}</button>`;
            html += `</div>
            </div>`;
            dojo.place(html, 'autoLeaveUnderButton');
            for (let i = maxHealth; i>0; i--) {
                document.getElementById(`${popinId}_set${i}`).addEventListener('click', () => {
                    this.setLeaveTokyoUnder(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
            for (let i = maxHealth + 1; i>2; i--) {
                document.getElementById(`${popinId}_setStay${i}`).addEventListener('click', () => {
                    this.setStayTokyoOver(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
            document.getElementById(`${popinId}_setStay0`).addEventListener('click', () => {
                this.setStayTokyoOver(0);
                setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
            });

            bubble = document.getElementById(popinId);
        }

        bubble.style.display = 'block';
        bubble.dataset.visible = 'true';
    }

    private updateAutoLeavePopinButtons() {
        const popinId = `discussion_bubble_autoLeaveUnder`;
        const maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
        for (let i = maxHealth + 1; i<=14; i++) {
            if (document.getElementById(`${popinId}_set${i}`)) {
                dojo.destroy(`${popinId}_set${i}`);
            }
            if (document.getElementById(`${popinId}_setStay${i}`)) {
                dojo.destroy(`${popinId}_setStay${i}`);
            }
        }

        for (let i = 11; i<=maxHealth; i++) {
            if (!document.getElementById(`${popinId}_set${i}`)) {
                dojo.place(`<button class="action-button bgabutton ${this.gamedatas.leaveTokyoUnder === i ? 'bgabutton_blue' : 'bgabutton_gray'} autoLeaveButton" id="${popinId}_set${i}">
                    ${i-1}
                </button>`, `${popinId}-buttons`, 'first');
                document.getElementById(`${popinId}_set${i}`).addEventListener('click', () => {
                    this.setLeaveTokyoUnder(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
        }
        for (let i = 12; i<=maxHealth+1; i++) {
            if (!document.getElementById(`${popinId}_setStay${i}`)) {
                dojo.place(`<button class="action-button bgabutton ${this.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray'} autoStayButton ${this.gamedatas.leaveTokyoUnder > 0 && i <= this.gamedatas.leaveTokyoUnder ? 'disabled' : ''}" id="${popinId}_setStay${i}">
                    ${i-1}
                </button>`, `${popinId}-stay-buttons`, 'first');
                document.getElementById(`${popinId}_setStay${i}`).addEventListener('click', () => {
                    this.setStayTokyoOver(i);
                    setTimeout(() => this.closeAutoLeaveUnderPopin(), 100);
                });
            }
        }
    }

    private closeAutoLeaveUnderPopin() {
        const bubble = document.getElementById(`discussion_bubble_autoLeaveUnder`);
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    }

    private setMimicToken(type: 'card' | 'tile', card: Card) {
        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.items.some(item => Number(item.id) == card.id)) {
                this.cards.placeMimicOnCard(type, playerTable.cards, card, this.wickednessTiles);
            }
        });

        this.setMimicTooltip(type, card);
    }

    private removeMimicToken(type: 'card' | 'tile', card: Card) {
        this.setMimicTooltip(type, null);

        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.items.some(item => Number(item.id) == card.id)) {
                this.cards.removeMimicOnCard(type, playerTable.cards, card);
            }
        });
    }

    private setMimicEvolutionToken(card: Card) {
        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.visibleEvolutionCards.items.some(item => Number(item.id) == card.id)) {
                this.evolutionCards.placeMimicOnCard(playerTable.visibleEvolutionCards, card);
            }
        });

        this.setMimicEvolutionTooltip(card);
    }

    private setMimicTooltip(type: 'card' | 'tile', mimickedCard: Card) {
        this.playerTables.forEach(playerTable => {
            const stock = type === 'tile' ? playerTable.wickednessTiles : playerTable.cards;
            const mimicCardId = type === 'tile' ? 106 : 27;
            const mimicCardItem = stock.items.find(item => Number(item.type) == mimicCardId);
            if (mimicCardItem) {
                const cardManager = type === 'tile' ? this.wickednessTiles : this.cards;
                cardManager.changeMimicTooltip(`${stock.container_div.id}_item_${mimicCardItem.id}`, this.cards.getMimickedCardText(mimickedCard));
            }
        });
    }

    private setMimicEvolutionTooltip(mimickedCard: Card) {
        this.playerTables.forEach(playerTable => {
            const mimicCardItem = playerTable.visibleEvolutionCards.items.find(item => Number(item.type) == 18);
            if (mimicCardItem) {
                this.evolutionCards.changeMimicTooltip(`${playerTable.visibleEvolutionCards.container_div.id}_item_${mimicCardItem.id}`, this.evolutionCards.getMimickedCardText(mimickedCard));
            }
        });
    }

    private removeMimicEvolutionToken(card: Card) {
        this.setMimicEvolutionTooltip(null);

        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.items.some(item => Number(item.id) == card.id)) {
                this.evolutionCards.removeMimicOnCard(playerTable.cards, card);
            }
        });
    }
    
    private showEvolutionsPopin(cardsTypes: number[], title: string) {
        
        const viewCardsDialog = new ebg.popindialog();
        viewCardsDialog.create('kotViewEvolutionsDialog');
        viewCardsDialog.setTitle(title);
        
        var html = `<div id="see-monster-evolutions" class="evolution-card-stock player-evolution-cards"></div>`;
        
        // Show the dialog
        viewCardsDialog.setContent(html);
        
        cardsTypes.forEach(cardType => {
            dojo.place(`
                <div id="see-monster-evolutions_item_${cardType}" class="stockitem stockitem_unselectable" style="background-position: -${(MONSTERS_WITH_POWER_UP_CARDS.indexOf(Math.floor(cardType / 10)) + 1) * 100}% 0%;"></div>
            `, 'see-monster-evolutions');
            this.evolutionCards.setupNewCard(document.getElementById(`see-monster-evolutions_item_${cardType}`) as HTMLDivElement, cardType);
        })
        
        viewCardsDialog.show();

        // Replace the function call when it's clicked
        viewCardsDialog.replaceCloseCallback(() => {            
            viewCardsDialog.destroy();
        });
    }
    
    private showMonsterEvolutions(monster: number) {
        const cardsTypes = [];
        for (let i=1; i<=8; i++) {
            cardsTypes.push(monster * 10 + i);
        }

        this.showEvolutionsPopin(cardsTypes, /*TODOPU_*/("Monster Evolution cards"));
    }
    
    private showPlayerEvolutions(playerId: number) {
        const cardsTypes = this.gamedatas.players[playerId].ownedEvolutions.map(evolution => evolution.type);
        this.showEvolutionsPopin(cardsTypes, dojo.string.substitute(/*TODOPU_*/("Evolution cards owned by ${player_name}"), {'player_name': this.gamedatas.players[playerId].name}));
    }

    public pickMonster(monster: number) {
        if(!(this as any).checkAction('pickMonster')) {
            return;
        }

        this.takeAction('pickMonster', {
            monster
        });
    }

    public pickEvolutionForDeck(id: number) {
        if(!(this as any).checkAction('pickEvolutionForDeck')) {
            return;
        }

        this.takeAction('pickEvolutionForDeck', {
            id
        });
    }

    public chooseInitialCard(id: number | null, evolutionId: number | null) {
        if(!(this as any).checkAction('chooseInitialCard')) {
            return;
        }

        this.takeAction('chooseInitialCard', {
            id,
            evolutionId,
        });
    }

    public skipBeforeStartTurn() {
        if(!(this as any).checkAction('skipBeforeStartTurn')) {
            return;
        }

        this.takeAction('skipBeforeStartTurn');
    }

    public skipBeforeEnteringTokyo() {
        if(!(this as any).checkAction('skipBeforeEnteringTokyo')) {
            return;
        }

        this.takeAction('skipBeforeEnteringTokyo');
    }

    public skipAfterEnteringTokyo() {
        if(!(this as any).checkAction('skipAfterEnteringTokyo')) {
            return;
        }

        this.takeAction('skipAfterEnteringTokyo');
    }

    public giveSymbolToActivePlayer(symbol: number) {
        if(!(this as any).checkAction('giveSymbolToActivePlayer')) {
            return;
        }

        this.takeAction('giveSymbolToActivePlayer', {
            symbol
        });
    }

    public giveSymbol(symbol: number) {
        if(!(this as any).checkAction('giveSymbol')) {
            return;
        }

        this.takeAction('giveSymbol', {
            symbol
        });
    }

    public onRethrow() {
        this.rethrowDice(this.diceManager.destroyFreeDice());      
    }

    public rethrowDice(diceIds: number[]) {
        if(!(this as any).checkAction('rethrow')) {
            return;
        }

        this.takeAction('rethrow', {
            diceIds: diceIds.join(',')
        });
    }

    public rethrow3() {
        const lockedDice = this.diceManager.getLockedDice();

        this.takeAction('rethrow3', {
            diceIds: lockedDice.map(die => die.id).join(',')
        });
    }

    public rerollDie(id: number) {
        const lockedDice = this.diceManager.getLockedDice();

        this.takeAction('rerollDie', {
            id,
            diceIds: lockedDice.map(die => die.id).join(',')
        });
    }

    public rethrow3camouflage() {
        this.takeAction('rethrow3camouflage');
    }

    public rethrow3psychicProbe() {
        this.takeAction('rethrow3psychicProbe');
    }

    public rethrow3changeDie() {
        this.takeAction('rethrow3changeDie');
    }

    public buyEnergyDrink() {
        const diceIds = this.diceManager.destroyFreeDice();

        this.takeAction('buyEnergyDrink', {
            diceIds: diceIds.join(',')
        });
    }

    public useSmokeCloud() {
        const diceIds = this.diceManager.destroyFreeDice();
        
        this.takeAction('useSmokeCloud', {
            diceIds: diceIds.join(',')
        });
    }

    public useCultist() {
        const diceIds = this.diceManager.destroyFreeDice();
        
        this.takeAction('useCultist', {
            diceIds: diceIds.join(',')
        });
    }

    public useRapidHealing() {
        this.takeNoLockAction('useRapidHealing');
    }

    public useMothershipSupport() {
        this.takeNoLockAction('useMothershipSupport');
    }

    public useRapidCultist(type: number) { // 4 for health, 5 for energy
        this.takeNoLockAction('useRapidCultist', { type });
    }

    public setSkipBuyPhase(skipBuyPhase: boolean) {
        this.takeNoLockAction('setSkipBuyPhase', {
            skipBuyPhase
        });
    }

    public changeDie(id: number, value: number, card: number) {
        if(!(this as any).checkAction('changeDie')) {
            return;
        }

        this.takeAction('changeDie', {
            id,
            value,
            card
        });
    }

    public psychicProbeRollDie(id: number) {
        if(!(this as any).checkAction('psychicProbeRollDie')) {
            return;
        }

        this.takeAction('psychicProbeRollDie', {
            id
        });
    }

    public goToChangeDie() {
        if(!(this as any).checkAction('goToChangeDie', true)) {
            return;
        }

        this.takeAction('goToChangeDie');
    }

    public resolveDice() {
        if(!(this as any).checkAction('resolve')) {
            return;
        }

        this.takeAction('resolve');
    }

    public support() {
        if(!(this as any).checkAction('support')) {
            return;
        }

        this.takeAction('support');
    }

    public dontSupport() {
        if(!(this as any).checkAction('dontSupport')) {
            return;
        }

        this.takeAction('dontSupport');
    }

    public discardDie(id: number) {
        if(!(this as any).checkAction('discardDie')) {
            return;
        }

        this.takeAction('discardDie', {
            id
        });
    }

    public rerollOrDiscardDie(id: number) {
        if (!this.falseBlessingAnkhAction) {
            return;
        }

        if(!(this as any).checkAction(this.falseBlessingAnkhAction)) {
            return;
        }

        this.takeAction(this.falseBlessingAnkhAction, {
            id
        });
    }

    public freezeDie(id: number) {
        if(!(this as any).checkAction('freezeDie')) {
            return;
        }

        this.takeAction('freezeDie', {
            id
        });
    }

    public skipFreezeDie() {
        if(!(this as any).checkAction('skipFreezeDie')) {
            return;
        }

        this.takeAction('skipFreezeDie');
    }

    public discardKeepCard(id: number) {
        if(!(this as any).checkAction('discardKeepCard')) {
            return;
        }

        this.takeAction('discardKeepCard', {
            id
        });
    }

    public giveGoldenScarab(playerId: number) {
        if(!(this as any).checkAction('giveGoldenScarab')) {
            return;
        }

        this.takeAction('giveGoldenScarab', {
            playerId
        });
    }

    public giveSymbols(symbols: number[]) {
        if(!(this as any).checkAction('giveSymbols')) {
            return;
        }

        this.takeAction('giveSymbols', {
            symbols: symbols.join(',')
        });
    }

    public selectExtraDie(face: number) {
        if(!(this as any).checkAction('selectExtraDie')) {
            return;
        }

        this.takeAction('selectExtraDie', {
            face
        });
    }

    public falseBlessingReroll(id: number) {
        if(!(this as any).checkAction('falseBlessingReroll')) {
            return;
        }

        this.takeAction('falseBlessingReroll', {
            id
        });
    }

    public falseBlessingDiscard(id: number) {
        if(!(this as any).checkAction('falseBlessingDiscard')) {
            return;
        }

        this.takeAction('falseBlessingDiscard', {
            id
        });
    }

    public falseBlessingSkip() {
        if(!(this as any).checkAction('falseBlessingSkip')) {
            return;
        }

        this.takeAction('falseBlessingSkip');
    }

    public rerollDice(diceIds: number[]) {
        if(!(this as any).checkAction('rerollDice')) {
            return;
        }

        this.takeAction('rerollDice', {
            ids: diceIds.join(',')
        });
    }

    public takeWickednessTile(id: number) {
        if(!(this as any).checkAction('takeWickednessTile')) {
            return;
        }

        this.takeAction('takeWickednessTile', {
            id
        });
    }

    public skipTakeWickednessTile() {
        if(!(this as any).checkAction('skipTakeWickednessTile')) {
            return;
        }

        this.takeAction('skipTakeWickednessTile');
    }

    public applyHeartActions(selections: HeartActionSelection[]) {
        if(!(this as any).checkAction('applyHeartDieChoices')) {
            return;
        }

        const base64 = btoa(JSON.stringify(selections));

        this.takeAction('applyHeartDieChoices', {
            selections: base64
        });
    }

    public applySmashActions(selections: SmashAction[]) {
        if(!(this as any).checkAction('applySmashDieChoices')) {
            return;
        }

        const base64 = btoa(JSON.stringify({ ...selections }));

        this.takeAction('applySmashDieChoices', {
            selections: base64
        });
    }

    public chooseEvolutionCard(id: number) {
        if(!(this as any).checkAction('chooseEvolutionCard')) {
            return;
        }

        this.takeAction('chooseEvolutionCard', {
            id
        });
    }

    public onStayInTokyo() {
        if(!(this as any).checkAction('stay')) {
            return;
        }

        this.takeAction('stay');
    }
    public onLeaveTokyo(useCard?: number) {
        if(!(this as any).checkAction('leave')) {
            return;
        }

        this.takeAction('leave', {
            useCard
        });
    }

    public stealCostumeCard(id: number) {
        if(!(this as any).checkAction('stealCostumeCard')) {
            return;
        }

        this.takeAction('stealCostumeCard', {
            id
        });
    }

    public changeForm() {
        if(!(this as any).checkAction('changeForm')) {
            return;
        }

        this.takeAction('changeForm');
    }

    public skipChangeForm() {
        if(!(this as any).checkAction('skipChangeForm')) {
            return;
        }

        this.takeAction('skipChangeForm');
    }

    public buyCard(id: number, from: number, useSuperiorAlienTechnology: boolean = false) {
        if(!(this as any).checkAction('buyCard')) {
            return;
        }

        this.takeAction('buyCard', {
            id,
            from,
            useSuperiorAlienTechnology
        });
    }

    public buyCardBamboozle(id: number, from: number) {
        if(!(this as any).checkAction('buyCardBamboozle')) {
            return;
        }

        this.takeAction('buyCardBamboozle', {
            id,
            from
        });
    }

    public chooseMimickedCard(id: number) {
        if(!(this as any).checkAction('chooseMimickedCard')) {
            return;
        }

        this.takeAction('chooseMimickedCard', {
            id
        });
    }

    public chooseMimickedEvolution(id: number) {
        if(!(this as any).checkAction('chooseMimickedEvolution')) {
            return;
        }

        this.takeAction('chooseMimickedEvolution', {
            id
        });
    }

    public changeMimickedCard(id: number) {
        if(!(this as any).checkAction('changeMimickedCard')) {
            return;
        }

        this.takeAction('changeMimickedCard', {
            id
        });
    }

    public chooseMimickedCardWickednessTile(id: number) {
        if(!(this as any).checkAction('chooseMimickedCardWickednessTile')) {
            return;
        }

        this.takeAction('chooseMimickedCardWickednessTile', {
            id
        });
    }

    public changeMimickedCardWickednessTile(id: number) {
        if(!(this as any).checkAction('changeMimickedCardWickednessTile')) {
            return;
        }

        this.takeAction('changeMimickedCardWickednessTile', {
            id
        });
    }

    public sellCard(id: number) {
        if(!(this as any).checkAction('sellCard')) {
            return;
        }

        this.takeAction('sellCard', {
            id
        });
    }

    public onRenew(cardType: number) {
        if(!(this as any).checkAction('renew')) {
            return;
        }

        this.takeAction('renew', {
            cardType
        });
    }

    public skipCardIsBought() {
        if(!(this as any).checkAction('skipCardIsBought')) {
            return;
        }

        this.takeAction('skipCardIsBought');
    }

    public goToSellCard() {
        if(!(this as any).checkAction('goToSellCard', true)) {
            return;
        }

        this.takeAction('goToSellCard');
    }

    public opportunistSkip() {
        if(!(this as any).checkAction('opportunistSkip', true)) {
            return;
        }

        this.takeAction('opportunistSkip');
    }

    public psychicProbeSkip() {
        if(!(this as any).checkAction('psychicProbeSkip')) {
            return;
        }

        this.takeAction('psychicProbeSkip');
    }

    public skipChangeMimickedCard() {
        if(!(this as any).checkAction('skipChangeMimickedCard', true)) {
            return;
        }

        this.takeAction('skipChangeMimickedCard');
    }

    public skipChangeMimickedCardWickednessTile() {
        if(!(this as any).checkAction('skipChangeMimickedCardWickednessTile', true)) {
            return;
        }

        this.takeAction('skipChangeMimickedCardWickednessTile');
    }

    public endStealCostume() {
        if(!(this as any).checkAction('endStealCostume')) {
            return;
        }

        this.takeAction('endStealCostume');
    }

    public onEndTurn() {
        if(!(this as any).checkAction('endTurn')) {
            return;
        }

        this.takeAction('endTurn');
    }

    public throwCamouflageDice() {
        if(!(this as any).checkAction('throwCamouflageDice')) {
            return;
        }

        this.takeAction('throwCamouflageDice');
    }

    public useWings() {
        if(!(this as any).checkAction('useWings')) {
            return;
        }

        this.takeAction('useWings');
    }

    public useInvincibleEvolution(evolutionType: number) {
        if(!(this as any).checkAction('useInvincibleEvolution')) {
            return;
        }

        this.takeAction('useInvincibleEvolution', {
            evolutionType
        });
    }

    public skipWings() {
        if(!(this as any).checkAction('skipWings')) {
            return;
        }

        this.takeAction('skipWings');
    }

    public useRobot(energy: number) {
        if(!(this as any).checkAction('useRobot')) {
            return;
        }

        this.takeAction('useRobot', {
            energy
        });
    }

    public useSuperJump(energy: number) {
        if(!(this as any).checkAction('useSuperJump')) {
            return;
        }

        this.takeAction('useSuperJump', {
            energy
        });
    }

    public useRapidHealingSync(cultistCount: number, rapidHealingCount: number) {
        if(!(this as any).checkAction('useRapidHealingSync')) {
            return;
        }

        this.takeAction('useRapidHealingSync', {
            cultistCount, 
            rapidHealingCount
        });
    }

    public setLeaveTokyoUnder(under: number) {
        this.takeNoLockAction('setLeaveTokyoUnder', {
            under
        });
    }

    public setStayTokyoOver(over: number) {
        this.takeNoLockAction('setStayTokyoOver', {
            over
        });
    }
    
    public exchangeCard(id: number) {
        if(!(this as any).checkAction('exchangeCard')) {
            return;
        }

        this.takeAction('exchangeCard', {
            id
        });
    }

    public skipExchangeCard() {
        if(!(this as any).checkAction('skipExchangeCard')) {
            return;
        }

        this.takeAction('skipExchangeCard');
    }
    
    public stayInHibernation() {
        if(!(this as any).checkAction('stayInHibernation')) {
            return;
        }

        this.takeAction('stayInHibernation');
    }
    
    public leaveHibernation() {
        if(!(this as any).checkAction('leaveHibernation')) {
            return;
        }

        this.takeAction('leaveHibernation');
    }

    public playEvolution(id: number) {
        this.takeNoLockAction('playEvolution', {
            id
        });
    }
    
    public useYinYang() {
        if(!(this as any).checkAction('useYinYang')) {
            return;
        }

        this.takeAction('useYinYang');
    }
    
    public putEnergyOnBambooSupply() {
        if(!(this as any).checkAction('putEnergyOnBambooSupply')) {
            return;
        }

        this.takeAction('putEnergyOnBambooSupply');
    }
    
    public takeEnergyOnBambooSupply() {
        if(!(this as any).checkAction('takeEnergyOnBambooSupply')) {
            return;
        }

        this.takeAction('takeEnergyOnBambooSupply');
    }
    
    public gazeOfTheSphinxDrawEvolution() {
        if(!(this as any).checkAction('gazeOfTheSphinxDrawEvolution')) {
            return;
        }

        this.takeAction('gazeOfTheSphinxDrawEvolution');
    }
    
    public gazeOfTheSphinxGainEnergy() {
        if(!(this as any).checkAction('gazeOfTheSphinxGainEnergy')) {
            return;
        }

        this.takeAction('gazeOfTheSphinxGainEnergy');
    }
    
    public gazeOfTheSphinxDiscardEvolution(id) {
        if(!(this as any).checkAction('gazeOfTheSphinxDiscardEvolution')) {
            return;
        }

        this.takeAction('gazeOfTheSphinxDiscardEvolution', {
            id
        });
    }
    
    public gazeOfTheSphinxLoseEnergy() {
        if(!(this as any).checkAction('gazeOfTheSphinxLoseEnergy')) {
            return;
        }

        this.takeAction('gazeOfTheSphinxLoseEnergy');
    }
    
    public useChestThumping(id: number) {
        if(!(this as any).checkAction('useChestThumping')) {
            return;
        }

        this.takeAction('useChestThumping', {
            id
        });
    }
    
    public skipChestThumping() {
        if(!(this as any).checkAction('skipChestThumping')) {
            return;
        }

        this.takeAction('skipChestThumping');
    }
    
    public chooseFreezeRayDieFace(symbol: number) {
        if(!(this as any).checkAction('chooseFreezeRayDieFace')) {
            return;
        }

        this.takeAction('chooseFreezeRayDieFace', {
            symbol
        });
    }
    
    public useMiraculousCatch() {
        if(!(this as any).checkAction('useMiraculousCatch')) {
            return;
        }

        this.takeAction('useMiraculousCatch');
    }
    
    public buyCardMiraculousCatch(useSuperiorAlienTechnology: boolean = false) {
        if(!(this as any).checkAction('buyCardMiraculousCatch')) {
            return;
        }

        this.takeAction('buyCardMiraculousCatch', {
            useSuperiorAlienTechnology
        });
    }
    
    public skipMiraculousCatch() {
        if(!(this as any).checkAction('skipMiraculousCatch')) {
            return;
        }

        this.takeAction('skipMiraculousCatch');
    }
    
    public playCardDeepDive(id: number) {
        if(!(this as any).checkAction('playCardDeepDive')) {
            return;
        }

        this.takeAction('playCardDeepDive', {
            id
        });
    }
    
    public useExoticArms() {
        if(!(this as any).checkAction('useExoticArms')) {
            return;
        }

        this.takeAction('useExoticArms');
    }
    
    public skipExoticArms() {
        if(!(this as any).checkAction('skipExoticArms')) {
            return;
        }

        this.takeAction('skipExoticArms');
    }
    
    public skipBeforeResolveDice() {
        if(!(this as any).checkAction('skipBeforeResolveDice')) {
            return;
        }

        this.takeAction('skipBeforeResolveDice');
    }
    
    public giveTarget() {
        if(!(this as any).checkAction('giveTarget')) {
            return;
        }

        this.takeAction('giveTarget');
    }
    
    public skipGiveTarget() {
        if(!(this as any).checkAction('skipGiveTarget')) {
            return;
        }

        this.takeAction('skipGiveTarget');
    }
    
    public useLightningArmor() {
        if(!(this as any).checkAction('useLightningArmor')) {
            return;
        }

        this.takeAction('useLightningArmor');
    }
    
    public skipLightningArmor() {
        if(!(this as any).checkAction('skipLightningArmor')) {
            return;
        }

        this.takeAction('skipLightningArmor');
    }
    
    public answerEnergySword(use: boolean) {
        if(!(this as any).checkAction('answerEnergySword')) {
            return;
        }

        this.takeAction('answerEnergySword', { use });
    }
    
    public answerSunkenTemple(use: boolean) {
        if(!(this as any).checkAction('answerSunkenTemple')) {
            return;
        }

        this.takeAction('answerSunkenTemple', { use });
    }
    
    public answerElectricCarrot(choice: 4 | 5) {
        if(!(this as any).checkAction('answerElectricCarrot')) {
            return;
        }

        this.takeAction('answerElectricCarrot', { choice });
    }
    
    public reserveCard(id: number) {
        if(!(this as any).checkAction('reserveCard')) {
            return;
        }

        this.takeAction('reserveCard', { id });
    }
    
    public useFelineMotor() {
        if(!(this as any).checkAction('useFelineMotor')) {
            return;
        }

        this.takeAction('useFelineMotor');
    }
    
    public takeAction(action: string, data?: any) {
        data = data || {};
        data.lock = true;
        (this as any).ajaxcall(`/kingoftokyo/kingoftokyo/${action}.html`, data, this, () => {});
    }

    public takeNoLockAction(action: string, data?: any) {
        data = data || {};
        (this as any).ajaxcall(`/kingoftokyo/kingoftokyo/${action}.html`, data, this, () => {});
    }

    public setFont(prefValue: number): void {
        this.playerTables.forEach(playerTable => playerTable.setFont(prefValue));
    }

    private startActionTimer(buttonId: string, time: number) {
        if ((this as any).prefs[202]?.value === 2) {
            return;
        }

        const button = document.getElementById(buttonId);
 
        let actionTimerId = null;
        const _actionTimerLabel = button.innerHTML;
        let _actionTimerSeconds = time;
        const actionTimerFunction = () => {
          const button = document.getElementById(buttonId);
          if (button == null) {
            window.clearInterval(actionTimerId);
          } else if (_actionTimerSeconds-- > 1) {
            button.innerHTML = _actionTimerLabel + ' (' + _actionTimerSeconds + ')';
          } else {
            window.clearInterval(actionTimerId);
            button.click();
          }
        };
        actionTimerFunction();
        actionTimerId = window.setInterval(() => actionTimerFunction(), 1000);
    }

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
        setupNotifications:

        In this method, you associate each of your game notifications with your local method to handle it.

        Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                your pylos.game.php file.

    */
    setupNotifications() {
        //log( 'notifications subscriptions setup' );

        const notifs = [
            ['pickMonster', 500],
            ['setInitialCards', 500],
            ['resolveNumberDice', ANIMATION_MS],
            ['resolveHealthDice', ANIMATION_MS],
            ['resolveHealingRay', ANIMATION_MS],
            ['resolveHealthDiceInTokyo', ANIMATION_MS],
            ['removeShrinkRayToken', ANIMATION_MS],
            ['removePoisonToken', ANIMATION_MS],
            ['resolveEnergyDice', ANIMATION_MS],
            ['resolveSmashDice', ANIMATION_MS],
            ['playerEliminated', ANIMATION_MS],
            ['playerEntersTokyo', ANIMATION_MS],
            ['renewCards', ANIMATION_MS],
            ['buyCard', ANIMATION_MS],
            ['reserveCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['useLightningArmor', ANIMATION_MS],
            ['changeDie', ANIMATION_MS],
            ['changeDice', ANIMATION_MS],
            ['rethrow3changeDie', ANIMATION_MS],
            ['changeCurseCard', ANIMATION_MS],
            ['takeWickednessTile', ANIMATION_MS],
            ['changeGoldenScarabOwner', ANIMATION_MS],
            ['discardedDie', ANIMATION_MS],
            ['exchangeCard', ANIMATION_MS],
            ['playEvolution', ANIMATION_MS],
            ['resolvePlayerDice', 500],
            ['changeTokyoTowerOwner', 500],
            ['changeForm', 500],
            ['evolutionPickedForDeck', 500],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['maxHealth', 1],
            ['wickedness', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['setCardTokens', 1],
            ['setEvolutionTokens', 1],
            ['setTileTokens', 1],
            ['removeCards', 1],
            ['removeEvolutions', 1],
            ['setMimicToken', 1],
            ['setMimicEvolutionToken', 1],
            ['removeMimicToken', 1],
            ['removeMimicEvolutionToken', 1],
            ['toggleRapidHealing', 1],
            ['toggleMothershipSupport', 1],
            ['toggleMothershipSupportUsed', 1],
            ['updateLeaveTokyoUnder', 1],
            ['updateStayTokyoOver', 1],
            ['kotPlayerEliminated', 1],
            ['setPlayerBerserk', 1],
            ['cultist', 1],
            ['removeWickednessTiles', 1],
            ['addEvolutionCardInHand', 1],
            ['addSuperiorAlienTechnologyToken', 1],
            ['giveTarget', 1],
            ['updateCancelDamage', 1],
            ['ownedEvolutions', 1],
            ['log500', 500]
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });
    }

    notif_log500() {
        // nothing, it's just for the delay
    }

    notif_pickMonster(notif: Notif<NotifPickMonsterArgs>) {
       const monsterDiv = document.getElementById(`pick-monster-figure-${notif.args.monster}`); 
       const destinationId = `player-board-monster-figure-${notif.args.playerId}`;
       const animation = (this as any).slideToObject(monsterDiv, destinationId);

        dojo.connect(animation, 'onEnd', dojo.hitch(this, () => {
            (this as any).fadeOutAndDestroy(monsterDiv);
            dojo.removeClass(destinationId, 'monster0');
            dojo.addClass(destinationId, `monster${notif.args.monster}`);
        }));
        animation.play();

        this.getPlayerTable(notif.args.playerId).setMonster(notif.args.monster);
    }

    notif_evolutionPickedForDeck(notif: Notif<any>) {
        this.evolutionCards.moveToAnotherStock(this.choseEvolutionInStock, this.inDeckEvolutionsStock, notif.args.card);
    }

    notif_setInitialCards(notif: Notif<NotifSetInitialCardsArgs>) {
        this.tableCenter.setInitialCards(notif.args.cards);
    }

    notif_resolveNumberDice(notif: Notif<NotifResolveNumberDiceArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points, ANIMATION_MS);
        this.animationManager.resolveNumberDice(notif.args);
        this.diceManager.resolveNumberDice(notif.args);
    }

    notif_resolveHealthDice(notif: Notif<NotifResolveHealthDiceArgs>) {
        this.animationManager.resolveHealthDice(notif.args.playerId, notif.args.deltaHealth);
        this.diceManager.resolveHealthDice(notif.args.deltaHealth);
    }
    notif_resolveHealthDiceInTokyo(notif: Notif<NotifResolveHealthDiceInTokyoArgs>) {
        this.diceManager.resolveHealthDiceInTokyo();
    }
    notif_resolveHealingRay(notif: Notif<NotifResolveHealingRayArgs>) {
        this.animationManager.resolveHealthDice(notif.args.healedPlayerId, notif.args.healNumber);
        this.diceManager.resolveHealthDice(notif.args.healNumber);
    }

    notif_resolveEnergyDice(notif: Notif<NotifResolveEnergyDiceArgs>) {
        this.animationManager.resolveEnergyDice(notif.args);
        this.diceManager.resolveEnergyDice();
    }

    notif_resolveSmashDice(notif: Notif<NotifResolveSmashDiceArgs>) {
        this.animationManager.resolveSmashDice(notif.args);
        this.diceManager.resolveSmashDice();

        if (notif.args.smashedPlayersIds.length > 0) {
            for (let delayIndex = 0; delayIndex < notif.args.number; delayIndex++) {
                setTimeout(() => playSound('kot-punch'), ANIMATION_MS -(PUNCH_SOUND_DURATION * delayIndex - 1));
            }
        }
    }

    notif_playerEliminated(notif: Notif<NotifPlayerEliminatedArgs>) {
        const playerId = Number(notif.args.who_quits);
        this.setPoints(playerId, 0);
        this.eliminatePlayer(playerId);
    }

    notif_kotPlayerEliminated(notif: Notif<NotifPlayerEliminatedArgs>) {
        this.notif_playerEliminated(notif);
    }

    notif_leaveTokyo(notif: Notif<NotifPlayerLeavesTokyoArgs>) {
        this.getPlayerTable(notif.args.playerId).leaveTokyo();
        dojo.removeClass(`overall_player_board_${notif.args.playerId}`, 'intokyo');
        dojo.removeClass(`monster-board-wrapper-${notif.args.playerId}`, 'intokyo');
        if (notif.args.playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }

        if (this.smashedPlayersStillInTokyo) {
            this.smashedPlayersStillInTokyo = this.smashedPlayersStillInTokyo.filter((playerId) => playerId != notif.args.playerId);
        }

        const useChestThumpingButton = document.getElementById(`useChestThumping_button${notif.args.playerId}`);
        useChestThumpingButton?.parentElement.removeChild(useChestThumpingButton);
    }

    notif_playerEntersTokyo(notif: Notif<NotifPlayerEntersTokyoArgs>) {
        this.getPlayerTable(notif.args.playerId).enterTokyo(notif.args.location);
        dojo.addClass(`overall_player_board_${notif.args.playerId}`, 'intokyo');
        dojo.addClass(`monster-board-wrapper-${notif.args.playerId}`, 'intokyo');
        if (notif.args.playerId == this.getPlayerId()) {
            this.addAutoLeaveUnderButton();
        }  
    }

    notif_buyCard(notif: Notif<NotifBuyCardArgs>) {
        const card = notif.args.card;
        this.tableCenter.changeVisibleCardWeight(card);

        if (notif.args.energy !== undefined) {
            this.setEnergy(notif.args.playerId, notif.args.energy);
        }

        if (notif.args.discardCard) { // initial card
            this.cards.moveToAnotherStock(this.tableCenter.getVisibleCards(), this.getPlayerTable(notif.args.playerId).cards, card);
            this.tableCenter.getVisibleCards().removeFromStockById(''+notif.args.discardCard.id);
        } else if (notif.args.newCard) {
        const newCard = notif.args.newCard;
            this.cards.moveToAnotherStock(this.tableCenter.getVisibleCards(), this.getPlayerTable(notif.args.playerId).cards, card);
            this.cards.addCardsToStock(this.tableCenter.getVisibleCards(), [newCard], 'deck');
            this.tableCenter.changeVisibleCardWeight(newCard);
        } else if (notif.args.from > 0) {
            this.cards.moveToAnotherStock(
                notif.args.from == notif.args.playerId ? this.getPlayerTable(notif.args.playerId).reservedCards : this.getPlayerTable(notif.args.from).cards, 
                this.getPlayerTable(notif.args.playerId).cards, 
                card);
        } else { // from Made in a lab Pick
            if (this.tableCenter.getPickCard()) { // active player
                this.cards.moveToAnotherStock(this.tableCenter.getPickCard(), this.getPlayerTable(notif.args.playerId).cards, card);
            } else {
                this.cards.addCardsToStock(this.getPlayerTable(notif.args.playerId).cards, [card], 'deck');
            }
        }

        this.tableCenter.setTopDeckCardBackType(notif.args.topDeckCardBackType);

        this.tableManager.tableHeightChange(); // adapt to new card
    }

    notif_reserveCard(notif: Notif<NotifBuyCardArgs>) {
        const card = notif.args.card;
        this.tableCenter.changeVisibleCardWeight(card);

        const newCard = notif.args.newCard;
        this.cards.moveToAnotherStock(this.tableCenter.getVisibleCards(), this.getPlayerTable(notif.args.playerId).reservedCards, card); // TODOPUBG add under evolution
        this.cards.addCardsToStock(this.tableCenter.getVisibleCards(), [newCard], 'deck');
        this.tableCenter.changeVisibleCardWeight(newCard);
        

        this.tableCenter.setTopDeckCardBackType(notif.args.topDeckCardBackType);

        this.tableManager.tableHeightChange(); // adapt to new card
    }

    notif_removeCards(notif: Notif<NotifRemoveCardsArgs>) {
        if (notif.args.delay) {
            notif.args.delay = false;
            setTimeout(() => this.notif_removeCards(notif), ANIMATION_MS);
        } else {
            this.getPlayerTable(notif.args.playerId).removeCards(notif.args.cards);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    }

    notif_removeEvolutions(notif: Notif<NotifRemoveEvolutionsArgs>) {
        if (notif.args.delay) {
            setTimeout(() => this.notif_removeEvolutions({
                args: {
                    ...notif.args,
                    delay: 0,
                }
            } as Notif<NotifRemoveEvolutionsArgs>), notif.args.delay);
        } else {
            this.getPlayerTable(notif.args.playerId).removeEvolutions(notif.args.cards);
            this.handCounters[notif.args.playerId].incValue(-notif.args.cards.filter(card => card.location === 'hand').length);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    }

    notif_setMimicToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.setMimicToken(notif.args.type, notif.args.card);
    }

    notif_removeMimicToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.removeMimicToken(notif.args.type, notif.args.card);
    }

    notif_removeMimicEvolutionToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.removeMimicEvolutionToken(notif.args.card);
    }

    notif_setMimicEvolutionToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.setMimicEvolutionToken(notif.args.card);
    }

    notif_renewCards(notif: Notif<NotifRenewCardsArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy);

        this.tableCenter.renewCards(notif.args.cards, notif.args.topDeckCardBackType);
    }

    notif_points(notif: Notif<NotifPointsArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points);
    }

    notif_health(notif: Notif<NotifHealthArgs>) {
        this.setHealth(notif.args.playerId, notif.args.health);

        /*const rapidHealingSyncButton = document.getElementById('rapidHealingSync_button');
        if (rapidHealingSyncButton && notif.args.playerId === this.getPlayerId()) {
            this.rapidHealingSyncHearts = Math.max(0, this.rapidHealingSyncHearts - notif.args.delta_health);
            rapidHealingSyncButton.innerHTML = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (${2*this.rapidHealingSyncHearts}[Energy])`), { 'card_name': this.cards.getCardName(37, 'text-only'), 'hearts': this.rapidHealingSyncHearts });
        }*/
    }

    notif_maxHealth(notif: Notif<NotifMaxHealthArgs>) {
        this.setMaxHealth(notif.args.playerId, notif.args.maxHealth);
        this.setHealth(notif.args.playerId, notif.args.health);
    }

    notif_energy(notif: Notif<NotifEnergyArgs>) {
        this.setEnergy(notif.args.playerId, notif.args.energy);
    }

    notif_wickedness(notif: Notif<NotifWickednessArgs>) {
        this.setWickedness(notif.args.playerId, notif.args.wickedness);
    }

    notif_shrinkRayToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.setShrinkRayTokens(notif.args.playerId, notif.args.tokens);
    }

    notif_poisonToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.setPoisonTokens(notif.args.playerId, notif.args.tokens);
    }

    notif_removeShrinkRayToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.animationManager.resolveHealthDice(notif.args.playerId, notif.args.deltaTokens, 'shrink-ray');
        this.diceManager.resolveHealthDice(notif.args.deltaTokens);
        setTimeout(() => this.notif_shrinkRayToken(notif), ANIMATION_MS);
    }

    notif_removePoisonToken(notif: Notif<NotifSetPlayerTokensArgs>) {
        this.animationManager.resolveHealthDice(notif.args.playerId, notif.args.deltaTokens, 'poison');
        this.diceManager.resolveHealthDice(notif.args.deltaTokens);
        setTimeout(() => this.notif_poisonToken(notif), ANIMATION_MS);
    }

    notif_setCardTokens(notif: Notif<NotifSetCardTokensArgs>) {
        this.cards.placeTokensOnCard(this.getPlayerTable(notif.args.playerId).cards, notif.args.card, notif.args.playerId);
    }

    notif_setEvolutionTokens(notif: Notif<NotifSetEvolutionTokensArgs>) {
        this.evolutionCards.placeTokensOnCard(this.getPlayerTable(notif.args.playerId).visibleEvolutionCards, notif.args.card, notif.args.playerId);
    }

    notif_setTileTokens(notif: Notif<NotifSetWickednessTileTokensArgs>) {
        this.wickednessTiles.placeTokensOnTile(this.getPlayerTable(notif.args.playerId).wickednessTiles, notif.args.card, notif.args.playerId);
    }

    notif_toggleRapidHealing(notif: Notif<NotifToggleRapidHealingArgs>) {
        if (notif.args.active) {
            this.addRapidHealingButton(notif.args.playerEnergy, notif.args.isMaxHealth);
        } else {
            this.removeRapidHealingButton();
        }
    }

    notif_toggleMothershipSupport(notif: Notif<NotifToggleRapidHealingArgs>) {
        if (notif.args.active) {
            this.addMothershipSupportButton(notif.args.playerEnergy, notif.args.isMaxHealth);
        } else {
            this.removeMothershipSupportButton();
        }
    }

    notif_toggleMothershipSupportUsed(notif: Notif<NotifToggleMothershipSupportUsedArgs>) {
        this.gamedatas.players[notif.args.playerId].mothershipSupportUsed = notif.args.used;
        this.checkMothershipSupportButtonState();
    }

    notif_useCamouflage(notif: Notif<NotifUpdateCancelDamageArgs>) {
        this.notif_updateCancelDamage(notif);
        this.diceManager.showCamouflageRoll(notif.args.diceValues);
    }

    notif_updateCancelDamage(notif: Notif<NotifUpdateCancelDamageArgs>) {
        if (notif.args.cancelDamageArgs) { 
            this.gamedatas.gamestate.args = notif.args.cancelDamageArgs;
            (this as any).updatePageTitle();
            this.onEnteringCancelDamage(notif.args.cancelDamageArgs, (this as any).isCurrentPlayerActive());
        }
    }

    notif_useLightningArmor(notif: Notif<NotifUpdateCancelDamageArgs>) {
        this.diceManager.showCamouflageRoll(notif.args.diceValues);
    }

    notif_changeDie(notif: Notif<NotifChangeDieArgs>) {
        if (notif.args.psychicProbeRollDieArgs) {
            this.onEnteringPsychicProbeRollDie(notif.args.psychicProbeRollDieArgs);
        } else {
            this.diceManager.changeDie(notif.args.dieId, notif.args.canHealWithDice, notif.args.toValue, notif.args.roll);
        }
    }

    notif_rethrow3changeDie(notif: Notif<NotifChangeDieArgs>) {
        this.diceManager.changeDie(notif.args.dieId, notif.args.canHealWithDice, notif.args.toValue, notif.args.roll);
    }

    notif_changeDice(notif: Notif<NotifChangeDiceArgs>) {
        Object.keys(notif.args.dieIdsToValues).forEach(key => 
            this.diceManager.changeDie(Number(key), notif.args.canHealWithDice, notif.args.dieIdsToValues[key], false)
        );
    }

    notif_resolvePlayerDice() {
        this.diceManager.lockAll();
    }

    notif_updateLeaveTokyoUnder(notif: Notif<NotifUpdateLeaveTokyoUnderArgs>) {                    
        dojo.query('.autoLeaveButton').removeClass('bgabutton_blue');
        dojo.query('.autoLeaveButton').addClass('bgabutton_gray');
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(`${popinId}_set${notif.args.under}`)) {
            dojo.removeClass(`${popinId}_set${notif.args.under}`, 'bgabutton_gray');
            dojo.addClass(`${popinId}_set${notif.args.under}`, 'bgabutton_blue');
        }
        for (let i = 1; i<=15; i++) {
            if (document.getElementById(`${popinId}_setStay${i}`)) {
                dojo.toggleClass(`${popinId}_setStay${i}`, 'disabled', notif.args.under > 0 && i <= notif.args.under);
            }
        }
    }

    notif_updateStayTokyoOver(notif: Notif<NotifUpdateStayTokyoOverArgs>) {                    
        dojo.query('.autoStayButton').removeClass('bgabutton_blue');
        dojo.query('.autoStayButton').addClass('bgabutton_gray');
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(`${popinId}_setStay${notif.args.over}`)) {
            dojo.removeClass(`${popinId}_setStay${notif.args.over}`, 'bgabutton_gray');
            dojo.addClass(`${popinId}_setStay${notif.args.over}`, 'bgabutton_blue');
        }
    }

    notif_changeTokyoTowerOwner(notif: Notif<NotifChangeTokyoTowerOwnerArgs>) {   
        const playerId = notif.args.playerId;
        const previousOwner = this.towerLevelsOwners[notif.args.level];
        this.towerLevelsOwners[notif.args.level] = playerId;

        const newLevelTower = playerId == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(playerId).getTokyoTower();

        transitionToObjectAndAttach(this, document.getElementById(`tokyo-tower-level${notif.args.level}`), `${newLevelTower.divId}-level${notif.args.level}`, this.getZoom());

        if (previousOwner != 0) {
            document.getElementById(`tokyo-tower-icon-${previousOwner}-level-${notif.args.level}`).dataset.owned = 'false';
        }
        if (playerId != 0) {
            document.getElementById(`tokyo-tower-icon-${playerId}-level-${notif.args.level}`).dataset.owned = 'true';
        }
    }

    notif_setPlayerBerserk(notif: Notif<NotifSetPlayerBerserkArgs>) { 
        this.getPlayerTable(notif.args.playerId).setBerserk(notif.args.berserk);
        dojo.toggleClass(`player-panel-berserk-${notif.args.playerId}`, 'active', notif.args.berserk);
    }

    notif_changeForm(notif: Notif<NotifChangeFormArgs>) { 
        this.getPlayerTable(notif.args.playerId).changeForm(notif.args.card);
        this.setEnergy(notif.args.playerId, notif.args.energy);
    }

    notif_cultist(notif: Notif<NotifCultistArgs>) {
        this.setCultists(notif.args.playerId, notif.args.cultists, notif.args.isMaxHealth);
    }

    notif_changeCurseCard(notif: Notif<NotifChangeCurseCardArgs>) {
        this.tableCenter.changeCurseCard(notif.args.card);
    }

    notif_takeWickednessTile(notif: Notif<NotifTakeWickednessTileArgs>) {
        const tile = notif.args.tile;
        this.wickednessTiles.addCardsToStock(this.getPlayerTable(notif.args.playerId).wickednessTiles, [tile], `wickedness-tiles-pile-tile-${tile.id}`);
        this.tableCenter.removeWickednessTileFromPile(notif.args.level, tile);

        this.tableManager.tableHeightChange(); // adapt to new card
    }

    notif_removeWickednessTiles(notif: Notif<NotifRemoveWickednessTilesArgs>) {
        this.getPlayerTable(notif.args.playerId).removeWickednessTiles(notif.args.tiles);
        this.tableManager.tableHeightChange(); // adapt after removed cards
    }

    notif_changeGoldenScarabOwner(notif: Notif<NotifChangeGoldenScarabOwnerArgs>) {
        this.getPlayerTable(notif.args.playerId).takeGoldenScarab(this.getPlayerTable(notif.args.previousOwner).cards);
        this.tableManager.tableHeightChange(); // adapt after moved card
    }

    notif_discardedDie(notif: Notif<NotifDiscardedDieArgs>) {
        this.diceManager.discardDie(notif.args.die);
    }

    notif_exchangeCard(notif: Notif<NotifExchangeCardArgs>) {
        this.cards.exchangeCardFromStocks(
            this.getPlayerTable(notif.args.playerId).cards,
            this.getPlayerTable(notif.args.previousOwner).cards,
            notif.args.unstableDnaCard,
            notif.args.exchangedCard,
        );
    }
    
    notif_addEvolutionCardInHand(notif: Notif<NotifAddEvolutionCardInHandArgs>) {
        const playerId = notif.args.playerId;
        const card = notif.args.card;
        const isCurrentPlayer = this.getPlayerId() === playerId;
        if (isCurrentPlayer) {
            if (card?.type) {
                this.getPlayerTable(playerId).hiddenEvolutionCards.addToStockWithId(card.type, '' + card.id);
            }
        } else if (card?.id) {
            this.getPlayerTable(playerId).hiddenEvolutionCards.addToStockWithId(0, '' + card.id);
        }
        if (!card || !card.type) {
            this.handCounters[playerId].incValue(1);
        }

        this.tableManager.tableHeightChange(); // adapt to new card
    }
    
    notif_playEvolution(notif: Notif<NotifPlayEvolutionArgs>) {
        this.handCounters[notif.args.playerId].incValue(-1);
        let fromStock = null;
        if (notif.args.fromPlayerId) {
            fromStock = this.getPlayerTable(notif.args.fromPlayerId).visibleEvolutionCards;
        }
        this.getPlayerTable(notif.args.playerId).playEvolution(notif.args.card, fromStock);
    }
    
    notif_addSuperiorAlienTechnologyToken(notif: Notif<NotifAddSuperiorAlienTechnologyTokenArgs>) {
        const stock = this.getPlayerTable(notif.args.playerId).cards;
        this.cards.placeSuperiorAlienTechnologyTokenOnCard(stock, notif.args.card);
    }
    
    notif_giveTarget(notif: Notif<NotifGiveTargetArgs>) {
        if (notif.args.previousOwner) {
            this.getPlayerTable(notif.args.previousOwner).removeTarget();
        }
        this.getPlayerTable(notif.args.playerId).giveTarget();
    }
    
    notif_ownedEvolutions(notif: Notif<NotifOwnedEvoltionsArgs>) {
        this.gamedatas.players[notif.args.playerId].ownedEvolutions = notif.args.evolutions;
    }
    
    private setPoints(playerId: number, points: number, delay: number = 0) {
        (this as any).scoreCtrl[playerId]?.toValue(points);
        this.getPlayerTable(playerId).setPoints(points, delay);
    }
    
    private setHealth(playerId: number, health: number, delay: number = 0) {
        this.healthCounters[playerId].toValue(health);
        this.getPlayerTable(playerId).setHealth(health, delay);
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.checkHealthCultistButtonState();
    }
    
    private setMaxHealth(playerId: number, maxHealth: number) {
        this.gamedatas.players[playerId].maxHealth = maxHealth;
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.checkHealthCultistButtonState();
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(popinId)) {
            this.updateAutoLeavePopinButtons();
        }
    }

    public getPlayerEnergy(playerId: number): number {
        return this.energyCounters[playerId].getValue();
    }
    
    private setEnergy(playerId: number, energy: number, delay: number = 0) {
        this.energyCounters[playerId].toValue(energy);
        this.getPlayerTable(playerId).setEnergy(energy, delay);
        this.checkBuyEnergyDrinkState(energy); // disable button if energy gets down to 0
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.setBuyDisabledCard(null, energy);
        
        (Array.from(document.querySelectorAll(`[data-enable-at-energy]`)) as HTMLElement[]).forEach(button => {
            const enableAtEnergy = Number(button.dataset.enableAtEnergy);
            dojo.toggleClass(button, 'disabled', energy < enableAtEnergy);
        });
    }
    
    private setWickedness(playerId: number, wickedness: number) {
        this.wickednessCounters[playerId].toValue(wickedness);
        this.tableCenter.setWickedness(playerId, wickedness);
    }

    private setPlayerTokens(playerId: number, tokens: number, tokenName: string) {
        const containerId = `player-board-${tokenName}-tokens-${playerId}`;
        const container = document.getElementById(containerId);
        while (container.childElementCount > tokens) {
            container.removeChild(container.lastChild);
        }
        for (let i=container.childElementCount; i<tokens; i++) {
            dojo.place(`<div class="${tokenName} token"></div>`, containerId);
        }
    }

    private setShrinkRayTokens(playerId: number, tokens: number) {
        this.setPlayerTokens(playerId, tokens, 'shrink-ray');
        this.getPlayerTable(playerId)?.setShrinkRayTokens(tokens);
    }

    private setPoisonTokens(playerId: number, tokens: number) {
        this.setPlayerTokens(playerId, tokens, 'poison');
        this.getPlayerTable(playerId)?.setPoisonTokens(tokens);
    }
    
    private setCultists(playerId: number, cultists: number, isMaxHealth: boolean) {
        this.cultistCounters[playerId].toValue(cultists);
        this.getPlayerTable(playerId)?.setCultistTokens(cultists);

        if (playerId == this.getPlayerId()) {
            if (cultists > 0) {
                this.addRapidCultistButtons(isMaxHealth);
            } else {        
                this.removeRapidCultistButtons();

                if (document.getElementById('use_cultist_button')) {
                    dojo.addClass('use_cultist_button', 'disabled');
                }
            }
        }
    }

    public checkBuyEnergyDrinkState(energy: number = null) {
        if (document.getElementById('buy_energy_drink_button')) {
            if (energy === null) {
                energy = this.energyCounters[this.getPlayerId()].getValue();
            }
            dojo.toggleClass('buy_energy_drink_button', 'disabled', energy < 1 || !this.diceManager.canRethrow());
        }
    }

    public checkUseSmokeCloudState() {
        if (document.getElementById('use_smoke_cloud_button')) {
            dojo.toggleClass('use_smoke_cloud_button', 'disabled', !this.diceManager.canRethrow());
        }
    }

    public checkUseCultistState() {
        if (document.getElementById('use_cultist_button')) {
            dojo.toggleClass('use_cultist_button', 'disabled', !this.diceManager.canRethrow());
        }
    }

    private eliminatePlayer(playerId: number) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById(`overall_player_board_${playerId}`).classList.add('eliminated-player');
        if (!document.getElementById(`dead-icon-${playerId}`)) {
            dojo.place(`<div id="dead-icon-${playerId}" class="icon dead"></div>`, `player_board_${playerId}`);
        }

        this.getPlayerTable(playerId).eliminatePlayer();
        this.tableManager.tableHeightChange(); // because all player's card were removed

        if (document.getElementById(`player-board-monster-figure-${playerId}`)) {
            (this as any).fadeOutAndDestroy(`player-board-monster-figure-${playerId}`);
        }
        dojo.removeClass(`overall_player_board_${playerId}`, 'intokyo');
        dojo.removeClass(`monster-board-wrapper-${playerId}`, 'intokyo');
        if (playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }
        
        this.setShrinkRayTokens(playerId, 0);
        this.setPoisonTokens(playerId, 0);
        if (this.isCthulhuExpansion()) {
            this.setCultists(playerId, 0, false);
        }
    }

    private getLogCardName(logType: number) {
        if (logType >= 3000) {
            return this.evolutionCards.getCardName(logType - 3000, 'text-only');
        } else if (logType >= 2000) {
            return this.wickednessTiles.getCardName(logType - 2000);
        } else if (logType >= 1000) {
            return this.curseCards.getCardName(logType - 1000);
        } else {
            return this.cards.getCardName(logType, 'text-only');
        }
    }

    private getLogCardTooltip(logType: number) {
        if (logType >= 3000) {
            return this.evolutionCards.getTooltip(logType - 3000);
        } else if (logType >= 2000) {
            return this.wickednessTiles.getTooltip(logType - 2000);
        } else if (logType >= 1000) {
            return this.curseCards.getTooltip(logType - 1000);
        } else {
            return this.cards.getTooltip(logType);
        }
    }

    private cardLogId = 0;
    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    public format_string_recursive(log: string, args: any) {
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                ['card_name', 'card_name2'].forEach(cardArg => {
                    if (args[cardArg]) {
                        let types: number[] = null;
                        if (typeof args[cardArg] == 'number') {
                            types = [args[cardArg]];
                        } else if (typeof args[cardArg] == 'string' && args[cardArg][0] >= '0' && args[cardArg][0] <= '9') {
                            types = args[cardArg].split(',').map((cardType: string) => Number(cardType));
                        }
                        if (types !== null) {
                            const tags: string[] = types.map((cardType: number) => {
                                const cardLogId = this.cardLogId++;

                                setTimeout(() => (this as any).addTooltipHtml(`card-log-${cardLogId}`, this.getLogCardTooltip(cardType)), 500);

                                return `<strong id="card-log-${cardLogId}" data-log-type="${cardType}">${this.getLogCardName(cardType)}</strong>`;
                            });
                            args[cardArg] = tags.join(', ');
                        }
                    }
                });

                for (const property in args) {
                    if (args[property]?.indexOf?.(']') > 0) {
                        args[property] = formatTextIcons(_(args[property]));
                    }
                }

                log = formatTextIcons(_(log));
            }
        } catch (e) {
            console.error(log,args,"Exception thrown", e.stack);
        }
        return (this as any).inherited(arguments);
    }
}