declare const g_img_preload;
declare const playSound;

declare const board: HTMLDivElement;

const ANIMATION_MS = 1500;
const PUNCH_SOUND_DURATION = 250;
const ACTION_TIMER_DURATION = 5;
const SYMBOL_AS_STRING_PADDED = ['[Star]', null, null, null, '[Heart]', '[Energy]'];

type FalseBlessingAnkhAction = 'actFalseBlessingReroll' | 'actFalseBlessingDiscard';

// @ts-ignore
GameGui = (function () { // this hack required so we fake extend GameGui
  function GameGui() {}
  return GameGui;
})();

class KingOfTokyo extends GameGui<KingOfTokyoGamedatas>implements KingOfTokyoGame {
    public gamedatas: KingOfTokyoGamedatas;
    private healthCounters: Counter[] = [];
    private energyCounters: Counter[] = [];
    private wickednessCounters: Counter[] = [];
    private cultistCounters: Counter[] = [];
    private handCounters: Counter[] = [];
    private monsterSelector: MonsterSelector;
    private diceManager: DiceManager;
    private kotAnimationManager: KingOfTokyoAnimationManager;
    private playerTables: PlayerTable[] = [];
    private preferencesManager: PreferencesManager;
    public animationManager: AnimationManager;
    public tableManager: TableManager;
    public cardsManager: CardsManager;
    public curseCardsManager: CurseCardsManager;
    public wickednessTilesManager: WickednessTilesManager;    
    public evolutionCardsManager: EvolutionCardsManager;
    //private rapidHealingSyncHearts: number;
    public towerLevelsOwners = [];
    private tableCenter: TableCenter;
    private falseBlessingAnkhAction: FalseBlessingAnkhAction = null;
    private choseEvolutionInStock: LineStock<EvolutionCard>;
    private inDeckEvolutionsStock: LineStock<EvolutionCard>;
    private smashedPlayersStillInTokyo: number[];
    private titleBarStock: LineStock<Card>;
        
    public SHINK_RAY_TOKEN_TOOLTIP: string;
    public POISON_TOKEN_TOOLTIP: string;
    public CULTIST_TOOLTIP: string;

    constructor() {
        super();
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
        if (gamedatas.origins) {
            document.getElementsByTagName('html')[0].dataset.origins = 'true';
        } else if (gamedatas.darkEdition) {
            document.getElementsByTagName('html')[0].dataset.darkEdition = 'true';
        }

        // needd to preload background
        this.preferencesManager = new PreferencesManager(this);

        const players = Object.values(gamedatas.players);
        // ignore loading of some pictures
        this.dontPreloadImage(`animations-halloween.jpg`);
        this.dontPreloadImage(`animations-christmas.jpg`);
        this.dontPreloadImage(`christmas_dice.png`);
        if (!gamedatas.halloweenExpansion) {
            this.dontPreloadImage(`costume-cards.jpg`);
            this.dontPreloadImage(`orange_dice.png`);
        }
        if (!gamedatas.powerUpExpansion) {
            this.dontPreloadImage(`animations-powerup.jpg`);
            this.dontPreloadImage(`powerup_dice.png`);
        }

        // load main board
        const boardDir = gamedatas.origins ? `origins` : (gamedatas.darkEdition ? `dark-edition` : `base`);
        const boardFile = gamedatas.twoPlayersVariant ? `2pvariant.jpg` : `standard.jpg`;
        const boardImgUrl = `boards/${boardDir}/${boardFile}`;
        g_img_preload.push(boardImgUrl);
        g_img_preload.push(`backgrounds/${this.preferencesManager.getBackgroundFilename()}`);


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

        this.animationManager = new AnimationManager(this);
        this.cardsManager = new CardsManager(this);
        this.curseCardsManager = new CurseCardsManager(this);
        this.wickednessTilesManager = new WickednessTilesManager(this);
        this.evolutionCardsManager = new EvolutionCardsManager(this);
        this.SHINK_RAY_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Shrink ray tokens (given by ${card_name}). Reduce dice count by one per token. Use you [diceHeart] to remove them.")), {'card_name': this.cardsManager.getCardName(40, 'text-only')});
        this.POISON_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Poison tokens (given by ${card_name}). Make you lose one [heart] per token at the end of your turn. Use you [diceHeart] to remove them.")), {'card_name': this.cardsManager.getCardName(35, 'text-only')});
    
        this.createPlayerPanels(gamedatas); 
        setTimeout(() => new ActivatedExpansionsPopin(gamedatas, (this as any).players_metadata?.[this.getPlayerId()]?.language), 500);
        this.monsterSelector = new MonsterSelector(this);
        this.diceManager = new DiceManager(this);
        this.kotAnimationManager = new KingOfTokyoAnimationManager(this, this.diceManager);
        this.tableCenter = new TableCenter(this, players, boardImgUrl, gamedatas.visibleCards, gamedatas.topDeckCard, gamedatas.deckCardsCount, gamedatas.wickednessTiles, gamedatas.tokyoTowerLevels, gamedatas.curseCard, gamedatas.hiddenCurseCardCount, gamedatas.visibleCurseCardCount, gamedatas.topCurseDeckCard);
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

        document.getElementById('zoom-out').addEventListener('click', () => this.tableManager?.zoomOut());
        document.getElementById('zoom-in').addEventListener('click', () => this.tableManager?.zoomIn());

        if (gamedatas.kingkongExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Tokyo Tower")}</h3>
            <p>${_("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1] while in Tokyo.")}</p>
            <p>${_("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative).")}</p>
            <p><strong>${_("Claiming the top level automatically wins the game.")}</strong></p>
            `);
            this.addTooltipHtmlToClass('tokyo-tower-tooltip', tooltip);
        }

        if (gamedatas.cybertoothExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Berserk mode")}</h3>
            <p>${_("When you roll 4 or more [diceSmash], you are in Berserk mode!")}</p>
            <p>${_("You play with the additional Berserk die, until you heal yourself.")}</p>`);
            this.addTooltipHtmlToClass('berserk-tooltip', tooltip);
        }

        if (gamedatas.cthulhuExpansion) {
            this.CULTIST_TOOLTIP = formatTextIcons(`
            <h3>${_("Cultists")}</h3>
            <p>${_("After resolving your dice, if you rolled four identical faces, take a Cultist tile")}</p>
            <p>${_("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll.")}</p>`);
            this.addTooltipHtmlToClass('cultist-tooltip', this.CULTIST_TOOLTIP);
        }

        // override to allow icons in messages
        const oldShowMessage = this.showMessage;
        this.showMessage = (msg, type) => oldShowMessage(formatTextIcons(msg), type);

        if (gamedatas.mindbug) {
            this.notif_mindbugPlayer(gamedatas.mindbug);
        }

        log( "Ending game setup" );
    }

    // @ts-ignore
    public onGameUserPreferenceChanged(pref_id: number, pref_value: number) {
        this.preferencesManager.onPreferenceChange(pref_id, pref_value);
    }

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    public onEnteringState(stateName: string, args: any) {
        log('Entering state: ' + stateName, args.args);
        this.showActivePlayer(Number(args.active_player));

        const pickMonsterPhase = ['pickMonster', 'PickMonsterNextPlayer'].includes(stateName);
        const pickEvolutionForDeckPhase = ['pickEvolutionForDeck', 'NextPickEvolutionForDeck'].includes(stateName);
        
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
                this.monsterSelector.onEnteringPickMonster(args.args);
                break;
            case 'pickEvolutionForDeck':
                dojo.addClass('kot-table', 'pickMonsterOrEvolutionDeck');
                this.onEnteringPickEvolutionForDeck(args.args);
                break;
            case 'chooseInitialCard':
                this.onEnteringChooseInitialCard(args.args);
                this.showEvolutionsPopinPlayerButtons();
                break;
            case 'StartGame':
                this.showEvolutionsPopinPlayerButtons();
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
                this.onEnteringChangeDie(args.args, this.isCurrentPlayerActive());
                break;
            case 'prepareResolveDice': 
                this.setDiceSelectorVisibility(true);
                this.onEnteringPrepareResolveDice(args.args, this.isCurrentPlayerActive());
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
                if (argsResolveDice.isInHibernation) {
                    this.statusBar.setTitle(
                        this.isCurrentPlayerActive() ? _('${you} can leave Hibernation') : _('${actplayer} can leave Hibernation'), 
                        args
                    );
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
                this.onEnteringTakeWickednessTile(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveHeartDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveHeartDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveSmashDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveSmashDice(args.args, this.isCurrentPlayerActive());
                break;

            case 'chooseEvolutionCard':
                this.onEnteringChooseEvolutionCard(args.args,  this.isCurrentPlayerActive());
                break;   

            case 'stealCostumeCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringStealCostumeCard(args.args, this.isCurrentPlayerActive());
                break;
            
            case 'leaveTokyoExchangeCard':
                this.setDiceSelectorVisibility(false);
                break;

            case 'buyCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringBuyCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'sellCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringSellCard(args.args);
                break;

            case 'answerQuestion':
                this.onEnteringAnswerQuestion(args.args);
                break;

            case 'EndTurn':
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
            this.updatePageTitle();
        }
    }
    
    private removeGamestateDescription() {
        this.gamedatas.gamestate.description = ''; 
        this.gamedatas.gamestate.descriptionmyturn = ''; 
        this.updatePageTitle();        
    }

    private onEnteringPickEvolutionForDeck(args: EnteringPickEvolutionForDeckArgs) {
        if (!document.getElementById('choose-evolution-in')) {
            dojo.place(`
                <div class="whiteblock">
                    <h3>${_("Choose an Evolution in")}</h3>
                    <div id="choose-evolution-in" class="evolution-card-stock player-evolution-cards"></div>
                </div>
                <div class="whiteblock">
                    <h3>${_("Evolutions in your deck")}</h3>
                    <div id="evolutions-in-deck" class="evolution-card-stock player-evolution-cards"></div>
                </div>
            `, 'mutant-evolution-choice');


            this.choseEvolutionInStock = new LineStock<EvolutionCard>(this.evolutionCardsManager, document.getElementById(`choose-evolution-in`));
            this.choseEvolutionInStock.setSelectionMode('single');
            this.choseEvolutionInStock.onCardClick = (card: EvolutionCard) => this.pickEvolutionForDeck(card.id);
            
            this.inDeckEvolutionsStock = new LineStock<EvolutionCard>(this.evolutionCardsManager, document.getElementById(`evolutions-in-deck`));
        }

        this.choseEvolutionInStock.removeAll();
        this.choseEvolutionInStock.addCards(args._private.chooseCardIn);
        this.inDeckEvolutionsStock.addCards(args._private.inDeck.filter(card => !this.inDeckEvolutionsStock.contains(card))); 
    }

    private onEnteringChooseInitialCard(args: EnteringChooseInitialCardArgs) {
        if (args.chooseEvolution && args.chooseCostume) {
            this.statusBar.setTitle(
                this.isCurrentPlayerActive() ? _('${you} must choose a Costume and an Evolution card') : _('${actplayer} must choose a Costume and an Evolution card'), 
                args
            );
        } else if (args.chooseEvolution) {
            this.statusBar.setTitle(
                this.isCurrentPlayerActive() ? _('${you} must choose an Evolution card') : _('${actplayer} must choose an Evolution card'),
                args
            );
        } else if (args.chooseCostume) {
            this.statusBar.setTitle(
                this.isCurrentPlayerActive() ? _('${you} must choose a Costume card') : _('${actplayer} must choose a Costume card'),
                args
            );
        }

        if (args.chooseCostume) {
            this.tableCenter.setInitialCards(args.cards);
            this.tableCenter.setVisibleCardsSelectionClass(args.chooseEvolution);
        }

        if (this.isCurrentPlayerActive()) {
            this.tableCenter.setVisibleCardsSelectionMode('single');

            if (args.chooseEvolution) {
                const playerTable = this.getPlayerTable(this.getPlayerId());
                playerTable.showEvolutionPickStock(args._private.evolutions);
                playerTable.setVisibleCardsSelectionClass(args.chooseCostume);
            }
        }
    }
    
    private onEnteringStepEvolution(args: EnteringStepEvolutionArgs) {
        console.log('onEnteringStepEvolution', args, this.isCurrentPlayerActive());
        if (this.isCurrentPlayerActive()) {
            const playerId = this.getPlayerId();
            this.getPlayerTable(playerId).highlightHiddenEvolutions(args.highlighted.filter(card => card.location_arg === playerId));
        }
    }

    private onEnteringBeforeEndTurn(args: EnteringBeforeEndTurnArgs) {
        if (args._private) {
            Object.keys(args._private).forEach(key => {
                const div = document.getElementById(`hand-evolution-cards_item_${key}`);
                if (div) {
                    const counter = args._private[key];
                    const symbol = SYMBOL_AS_STRING_PADDED[counter[1]];
                    div.insertAdjacentHTML('beforeend', formatTextIcons(`<div class="evolution-inner-counter">${counter[0]} ${symbol}</div>`));
                }
            });
        }
    }

    private onEnteringThrowDice(args: EnteringThrowDiceArgs) {
        if (args.throwNumber >= args.maxThrowNumber) {
            this.statusBar.setTitle(
                this.isCurrentPlayerActive() ? _('${you} must resolve dice') : _('${actplayer} must resolve dice'), 
                args
            );
        }
        this.diceManager.showLock();

        const isCurrentPlayerActive = this.isCurrentPlayerActive();

        this.diceManager.setDiceForThrowDice(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        
        if (isCurrentPlayerActive) {
            const orbOfDoomsSuffix = args.opponentsOrbOfDooms ? formatTextIcons(` (-${args.opponentsOrbOfDooms}[Heart])`)  : '';
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', dojo.string.substitute(_("Reroll dice (${number} roll(s) remaining)"), { 'number': args.maxThrowNumber-args.throwNumber }) + orbOfDoomsSuffix, () => this.onRethrow(), !args.dice.some(dice => !dice.locked));

                this.addTooltip(
                    'rethrow_button', 
                    _("Click on dice you want to keep to lock them, then click this button to reroll the others"),
                    `${_("Ctrl+click to move all dice with same value")}<br>
                    ${_("Alt+click to move all dice but clicked die")}`);
            }

            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]') + ' ('+this.cardsManager.getCardName(5, 'text-only')+')', () => this.rethrow3(), !args.rethrow3.hasDice3);
            }

            if (args.energyDrink?.hasCard && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + formatTextIcons(` ( 1[Energy])`) + orbOfDoomsSuffix, () => this.buyEnergyDrink());
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }

            if (args.hasSmokeCloud && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_smoke_cloud_button', _("Get extra die Roll") + ` (<span class="smoke-cloud token"></span>)` + orbOfDoomsSuffix, () => this.useSmokeCloud());
            }

            if (args.hasCultist && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_cultist_button', _("Get extra die Roll") + ` (${_('Cultist')})` + orbOfDoomsSuffix, () => this.useCultist());
            }

            if (args.rerollDie.isBeastForm) {
                dojo.place(`<div id="beast-form-dice-actions"></div>`, 'dice-actions');

                const simpleFaces = [];
                args.dice.filter(die => die.type < 2).forEach(die => {
                    if (die.canReroll && (die.type > 0 || !simpleFaces.includes(die.value))) {
                        const faceText = die.type == 1 ? BERSERK_DIE_STRINGS[die.value] : DICE_STRINGS[die.value];
                        this.createButton('beast-form-dice-actions', `rerollDie${die.id}_button`, _("Reroll") + formatTextIcons(' ' + faceText) + ' ('+this.cardsManager.getCardName(301, 'text-only', 1)+')', () => this.rerollDie(die.id), !args.rerollDie.canUseBeastForm);

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
            this.diceManager.setDiceForChangeDie(args.dice, args.selectableDice, args, args.canHealWithDice, args.frozenFaces);
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
        this.diceManager.setDiceForPsychicProbe(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);

        if (args.dice && args.rethrow3?.hasCard && this.isCurrentPlayerActive()) {
            if (document.getElementById('rethrow3psychicProbe_button')) {
                dojo.toggleClass('rethrow3psychicProbe_button', 'disabled', !args.rethrow3.hasDice3);
            } else {
                this.createButton('dice-actions', 'rethrow3psychicProbe_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3psychicProbe(), !args.rethrow3.hasDice3);
            }
        }
    }

    private onEnteringDiscardDie(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    }

    private onEnteringSelectExtraDie(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    }

    private onEnteringRerollOrDiscardDie(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces, 'rerollOrDiscard');
        }
    }

    private onEnteringRerollDice(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces, 'rerollDice');
        }
    }

    private onEnteringPrepareResolveDice(args: EnteringPrepareResolveDiceArgs, isCurrentPlayerActive: boolean) {
        if (args.hasEncasedInIce) {            
            this.setGamestateDescription('EncasedInIce');
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, isCurrentPlayerActive ? args.selectableDice : [], args.canHealWithDice, args.frozenFaces, 'freezeDie');
        }
    }

    private onEnteringDiscardKeepCard(args: EnteringDiscardKeepCardArgs) {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode('single'));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringResolveNumberDice(args: EnteringDiceArgs) {
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    }
    
    private onEnteringTakeWickednessTile(args: EnteringTakeWickednessTileArgs, isCurrentPlayerActive: boolean) {
        this.tableCenter.setWickednessTilesSelectable(args.level, true, isCurrentPlayerActive);

        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    }

    private onEnteringResolveHeartDice(args: EnteringResolveHeartDiceArgs, isCurrentPlayerActive: boolean) {
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);

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
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);

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
                this.addActionButton('throwCamouflageDice_button', _("Throw dice"), 'throwCamouflageDice');
            } else if (!args.canThrowDices && document.getElementById('throwCamouflageDice_button')) {
                dojo.destroy('throwCamouflageDice_button');
            }

            if (args.canUseWings && !document.getElementById('useWings_button')) {
                this.addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + " ( 2[Energy] )", { 'card_name': this.cardsManager.getCardName(48, 'text-only')})), () => this.useWings());
                document.getElementById('useWings_button').dataset.enableAtEnergy = '2';
                if (args.playerEnergy < 2) {
                    dojo.addClass('useWings_button', 'disabled');
                }
            }

            if (args.canUseDetachableTail && !document.getElementById('useDetachableTail_button')) {
                this.addActionButton('useDetachableTail_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(51, 'text-only')}), () => this.useInvincibleEvolution(51));
            }

            if (args.canUseRabbitsFoot && !document.getElementById('useRabbitsFoot_button')) {
                this.addActionButton('useRabbitsFoot_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(143, 'text-only')}), () => this.useInvincibleEvolution(143));
            }

            if (args.canUseCandy && !document.getElementById('useCandy_button')) {
                this.addActionButton('useCandy_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(88, 'text-only')}), () => this.useCandyEvolution());
            }

            if (args.countSuperJump > 0 && !document.getElementById('useSuperJump1_button')) {
                Object.keys(args.replaceHeartByEnergyCost).filter(energy => Number(energy) <= args.countSuperJump).forEach(energy => {
                    const energyCost = Number(energy);
                    const remainingDamage = args.replaceHeartByEnergyCost[energy];

                    const id = `useSuperJump${energyCost}_button`;
                    if (!document.getElementById(id)) {
                        this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': this.cardsManager.getCardName(53, 'text-only')}) + (remainingDamage > 0 ? ` (-${remainingDamage}[Heart])` : '')), () => this.useSuperJump(energyCost));
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
                        this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': this.cardsManager.getCardName(210, 'text-only')}) + (remainingDamage > 0 ? ` (-${remainingDamage}[Heart])` : '')), () => this.useRobot(energyCost));
                        document.getElementById(id).dataset.enableAtEnergy = ''+energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }

            if (args.canUseElectricArmor && !document.getElementById('useElectricArmor_button')) {
                Object.keys(args.replaceHeartByEnergyCost).forEach(energy => {
                    const energyCost = Number(energy);
                    const remainingDamage = args.replaceHeartByEnergyCost[energy];

                    const id = `useElectricArmor${energyCost}_button`;
                    if (!document.getElementById(id) && energyCost == 1) {
                        this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': this.cardsManager.getCardName(58, 'text-only')}) + (remainingDamage > 0 ? ` (-${remainingDamage}[Heart])` : '')), () => this.useElectricArmor(energyCost));
                        document.getElementById(id).dataset.enableAtEnergy = ''+energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }

            if (!args.canThrowDices && !document.getElementById('skipWings_button')) {
                const canAvoidDeath = args.canDoAction && args.skipMeansDeath && (args.canCancelDamage || args.canHealToAvoidDeath);
                this.addActionButton(
                    'skipWings_button', 
                    args.canUseWings ? dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cardsManager.getCardName(48, 'text-only')}) : _("Skip"), 
                    () => {
                        if (canAvoidDeath) {
                            this.confirmationDialog(
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
                        cardsNames.push(_(this.cardsManager.getCardName(37, 'text-only')));
                    }

                    if (cultistCount + rapidHealingCount >= args.damageToCancelToSurvive && 2*rapidHealingCount <= args.playerEnergy) {
                        const text = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')}` + (rapidHealingCount > 0 ? ` (${2*rapidHealingCount}[Energy])` : '')), { 'card_name': cardsNames.join(', '), 'hearts': cultistCount + rapidHealingCount });
                        this.addActionButton(`rapidHealingSync_button_${i}`, text, () => this.useRapidHealingSync(cultistCount, rapidHealingCount));
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
        if (!args.canGiveGift && !args.canBuyFromPlayers && !this.isHalloweenExpansion()) {
            this.setGamestateDescription('Give');
        }
        if (args.canGiveGift) {
            this.setGamestateDescription(args.canBuyFromPlayers ? `StealAndGive` : 'Give');

            if (isCurrentPlayerActive) {
                this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards?.setSelectionMode('single');
            }
        }

        if (isCurrentPlayerActive) {
            if (args.canBuyFromPlayers) {
                this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode('single'));
                this.setBuyDisabledCard(args);
            }

            const playerId = this.getPlayerId();
            this.getPlayerTable(playerId).highlightHiddenEvolutions(args.highlighted.filter(card => card.location_arg === playerId));
            this.getPlayerTable(playerId).highlightVisibleEvolutions(args.tableGifts);
        }
    }

    private onEnteringExchangeCard(args: EnteringExchangeCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            args.disabledIds.forEach(id => {
                const cardDiv = this.cardsManager.getCardElement({ id } as Card);
                cardDiv?.classList.add('bga-cards_disabled-card');
            });
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

            this.tableCenter.setVisibleCardsSelectionMode('single');

            if (this.isPowerUpExpansion()) {                
                this.getPlayerTable(playerId).reservedCards.setSelectionMode('single');
            }

            this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(args.canBuyFromPlayers && playerTable.playerId != playerId ? 'single' : 'none'));

            if (args._private?.pickCards?.length) {
                this.tableCenter.showPickStock(args._private.pickCards);
            }

            this.setBuyDisabledCard(args);
        }
    }

    private onEnteringChooseMimickedCard(args: EnteringBuyCardArgs) {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode('single'));
            this.setBuyDisabledCard(args);
        }
    }

    private onEnteringSellCard(args: EnteringBuyCardArgs) {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode('single'));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringAnswerQuestion(args: EnteringAnswerQuestionArgs) {
        const question = args.question;
        this.gamedatas.gamestate.description = question.description; 
        this.gamedatas.gamestate.descriptionmyturn = question.descriptionmyturn; 
        this.updatePageTitle();

        switch (question.code) {
            case 'ChooseMimickedCard':
                this.onEnteringChooseMimickedCard(question.args.mimicArgs);
                break;
            case 'Bamboozle':
                const bamboozleArgs = question.args as BamboozleQuestionArgs;
                this.onEnteringBuyCard(bamboozleArgs.buyCardArgs, this.isCurrentPlayerActive());
                break;

            case 'GazeOfTheSphinxSnake':
                if (this.isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode('single');
                }
                break;

            case 'IcyReflection':
                if (this.isCurrentPlayerActive()) {
                    const icyReflectionArgs = question.args as IcyReflectionQuestionArgs;
                    this.playerTables.forEach(playerTable => playerTable.visibleEvolutionCards.setSelectionMode('single'));
                    icyReflectionArgs.disabledEvolutions.forEach(evolution => {
                        const cardDiv = document.querySelector(`div[id$="_item_${evolution.id}"]`) as HTMLElement;
                        if (cardDiv && cardDiv.closest('.player-evolution-cards') !== null) {
                            cardDiv.classList.add('disabled');
                        }
                    });
                }
                break;
            case 'MiraculousCatch':
                const miraculousCatchArgs = question.args as MiraculousCatchQuestionArgs;                
                dojo.place(`<div id="title-bar-stock" class="card-in-title-wrapper"></div>`, `maintitlebar_content`);
                this.titleBarStock = new LineStock<Card>(this.cardsManager, document.getElementById('title-bar-stock'));
                this.titleBarStock.addCard(miraculousCatchArgs.card);
                this.titleBarStock.setSelectionMode('single');
                this.titleBarStock.onCardClick = () => this.buyCardMiraculousCatch();
                break;
            case 'DeepDive':
                const deepDiveCatchArgs = question.args as DeepDiveQuestionArgs;
                dojo.place(`<div id="title-bar-stock" class="card-in-title-wrapper"></div>`, `maintitlebar_content`);
                this.titleBarStock = new LineStock<Card>(this.cardsManager, document.getElementById('title-bar-stock'));
                this.titleBarStock.addCards(deepDiveCatchArgs.cards, { fromStock: this.tableCenter.getDeck(), originalSide: 'back', rotationDelta: 90 }, undefined, true);
                this.titleBarStock.setSelectionMode('single');
                this.titleBarStock.onCardClick = (card: Card) => this.playCardDeepDive(card.id);
                break;
            case 'Treasure':
                const treasureArgs = question.args as TreasureQuestionArgs;
                dojo.place(`<div id="title-bar-stock" class="card-in-title-wrapper"></div>`, `maintitlebar_content`);
                this.titleBarStock = new LineStock<Card>(this.cardsManager, document.getElementById('title-bar-stock'));
                this.titleBarStock.addCards(treasureArgs.cards, { fromStock: this.tableCenter.getDeck(), originalSide: 'back', rotationDelta: 90 }, undefined, true);
                this.titleBarStock.setSelectionMode('single');
                this.titleBarStock.onCardClick = (card: Card) => this.playCardDeepDive(card.id);
                break;
            case 'MyToy':
                this.tableCenter.setVisibleCardsSelectionMode('single');
                break;
            case 'SuperiorAlienTechnology':
                const superiorAlienTechnologyArgs = question.args as SuperiorAlienTechnologyQuestionArgs;
                this.setTitleBarSuperiorAlienTechnologyCard(superiorAlienTechnologyArgs.card);
                this.setDiceSelectorVisibility(false);
                break;
            case 'FreezeRayChooseOpponent':
                const argsFreezeRayChooseOpponent = question.args as FreezeRayChooseOpponentQuestionArgs;
                argsFreezeRayChooseOpponent.smashedPlayersIds.forEach(playerId => {
                    const player = this.gamedatas.players[playerId];
                    const label = `<div class="monster-icon monster${player.monster}" style="background-color: ${player.monster > 100 ? 'unset' : '#'+player.color};"></div> ${player.name}`;
                    this.addActionButton(`freezeRayChooseOpponent_button_${playerId}`, label, () => this.freezeRayChooseOpponent(playerId));
                });
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
                this.tableCenter.setVisibleCardsSelectionMode('none');
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
            case 'beforeEndTurn':
                this.onLeavingStepEvolution();
                this.onLeavingBeforeEndTurn();
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
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
                break;
            case 'stealCostumeCard':
                this.onLeavingStealCostumeCard();
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
                this.tableCenter.setVisibleCardsSelectionMode('none');
                break;
        }
    }
    
    private onLeavingStepEvolution() {
        const playerId = this.getPlayerId();
        this.getPlayerTable(playerId)?.unhighlightHiddenEvolutions();
    }

    private onLeavingBeforeEndTurn() {
        (Array.from(document.querySelectorAll(`.evolution-inner-counter`)) as HTMLElement[]).forEach(elem => {
            elem?.parentElement?.removeChild(elem);
        });
    }
    
    private onLeavingTakeWickednessTile() {
        this.tableCenter.setWickednessTilesSelectable(null, false, false);
    }

    private onLeavingBuyCard() {
        this.tableCenter.setVisibleCardsSelectionMode('none');
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode('none'));            
        this.tableCenter.hidePickStock();
    }

    private onLeavingStealCostumeCard() {
        this.onLeavingBuyCard();

        const playerId = this.getPlayerId();
        const playerTable = this.getPlayerTable(playerId);
        if (playerTable) {
            playerTable.unhighlightHiddenEvolutions();
            playerTable.unhighlightVisibleEvolutions();
            playerTable.visibleEvolutionCards?.setSelectionMode('none');
        }
    }

    private onLeavingChooseMimickedCard() {
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode('none'));
    }

    private onLeavingSellCard() {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode('none'));
            dojo.query('.stockitem').removeClass('disabled');
        }
    }
    
    private onLeavingAnswerQuestion() {
        const question: Question = this.gamedatas.gamestate.args.question;

        switch(question.code) {
            case 'ChooseMimickedCard':
                this.onLeavingChooseMimickedCard();
                break;
            case 'Bamboozle':
                this.onLeavingBuyCard();
                break;
    
            case 'GazeOfTheSphinxSnake':
                if (this.isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode('none');
                }
                break;

            case 'IcyReflection':
                if (this.isCurrentPlayerActive()) {
                    this.playerTables.forEach(playerTable => playerTable.visibleEvolutionCards.setSelectionMode('none'));
                    dojo.query('.stockitem').removeClass('disabled');
                }
                break;
            case 'MiraculousCatch':
            case 'DeepDive':
            case 'Treasure':
            case 'SuperiorAlienTechnology':                
                this.titleBarStock.removeAll();
                document.getElementById(`title-bar-stock`)?.remove();
                break;
        }
    }

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    public onUpdateActionButtons(stateName: string, args: any) {

        switch (stateName) {
            case 'beforeStartTurn':
            case 'beforeResolveDice':
            case 'beforeEnteringTokyo':
            case 'afterEnteringTokyo':
            case 'cardIsBought':
                this.onEnteringStepEvolution(args); // because it's multiplayer, enter action must be set here
                break;
            case 'beforeEndTurn':
                this.onEnteringStepEvolution(args); // because it's multiplayer, enter action must be set here
                this.onEnteringBeforeEndTurn(args);
                break;

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
                this.addActionButton('support_button', formatTextIcons(_("Support (add [diceSmash] )")), () => this.support());
                this.addActionButton('dontSupport_button', _("Don't support"), () => this.dontSupport());
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
                this.onEnteringCancelDamage(argsCancelDamage, this.isCurrentPlayerActive());
                break;
        }

        if(this.isCurrentPlayerActive()) {
            switch (stateName) {
                case 'chooseInitialCard':
                    if (this.isInitialCardDoubleSelection()) {
                        this.addActionButton('confirmInitialCards_button', _("Confirm"), () => this.chooseInitialCard(
                            Number(this.tableCenter.getVisibleCards().getSelection()[0]?.id),
                            Number(this.getPlayerTable(this.getPlayerId()).pickEvolutionCards.getSelection()[0]?.id),
                        ));
                        document.getElementById(`confirmInitialCards_button`).classList.add('disabled');
                    }
                    break;
                case 'beforeStartTurn':
                    this.addActionButton('skipBeforeStartTurn_button', _("Skip"), () => this.skipBeforeStartTurn());
                    break;
                case 'beforeEndTurn':
                    this.addActionButton('skipBeforeEndTurn_button', _("Skip"), () => this.skipBeforeEndTurn());
                    break;
                case 'changeMimickedCardWickednessTile':
                    this.addActionButton('skipChangeMimickedCardWickednessTile_button', _("Skip"),  () => this.skipChangeMimickedCardWickednessTile());

                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCardWickednessTile_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeMimickedCard':
                    this.addActionButton('skipChangeMimickedCard_button', _("Skip"), () => this.skipChangeMimickedCard());

                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCard_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'giveSymbolToActivePlayer':
                    const argsGiveSymbolToActivePlayer = args as EnteringGiveSymbolToActivePlayerArgs;
                    const SYMBOL_AS_STRING = ['[Heart]', '[Energy]', '[Star]'];
                    [4,5,0].forEach((symbol, symbolIndex) => {
                        this.addActionButton(`giveSymbolToActivePlayer_button${symbol}`, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING[symbolIndex]})), () => this.giveSymbolToActivePlayer(symbol));
                        if (!argsGiveSymbolToActivePlayer.canGive[symbol]) {
                            dojo.addClass(`giveSymbolToActivePlayer_button${symbol}`, 'disabled');
                        }
                    });
                    document.getElementById(`giveSymbolToActivePlayer_button5`).dataset.enableAtEnergy = '1';
                    break;
                case 'throwDice':
                    this.addActionButton('goToChangeDie_button', _("Resolve dice"), () => this.goToChangeDie(), null, null, 'red');

                    const argsThrowDice = args as EnteringThrowDiceArgs;
                    if (!argsThrowDice.hasActions) {
                        this.startActionTimer('goToChangeDie_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeDie':
                    const argsChangeDie = args as EnteringChangeDieArgs;
                    if (argsChangeDie.hasYinYang) {
                        this.statusBar.addActionButton(dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCardsManager.getCardName(138, 'text-only') }), () => this.bgaPerformAction('actUseYinYang'));
                    }

                    this.addActionButton('resolve_button', _("Resolve dice"), () => this.resolveDice(), null, null, 'red');
                    break;
                case 'changeActivePlayerDie': case 'psychicProbeRollDie':
                    this.addActionButton('changeActivePlayerDieSkip_button', _("Skip"), () => this.changeActivePlayerDieSkip());
                    break;
                case 'cheerleaderSupport':
                    this.addActionButton('support_button', formatTextIcons(_("Support (add [diceSmash] )")), () => this.support());
                    this.addActionButton('dontSupport_button', _("Don't support"), () => this.dontSupport());
                    break;
                case 'giveGoldenScarab':
                    const argsGiveGoldenScarab = args as EnteringGiveGoldenScarabArgs;
                    argsGiveGoldenScarab.playersIds.forEach(playerId => {
                        const player = this.gamedatas.players[playerId];
                        const label = `<div class="monster-icon monster${player.monster}" style="background-color: ${player.monster > 100 ? 'unset' : '#'+player.color};"></div> ${player.name}`;
                        this.addActionButton(`giveGoldenScarab_button_${playerId}`, label, () => this.giveGoldenScarab(playerId));
                    });
                    break;
                case 'giveSymbols':
                    const argsGiveSymbols = args as EnteringGiveSymbolsArgs;
                    
                    argsGiveSymbols.combinations.forEach((combination, combinationIndex) => {
                        const symbols = SYMBOL_AS_STRING_PADDED[combination[0]] + (combination.length > 1 ? SYMBOL_AS_STRING_PADDED[combination[1]] : '');
                        this.addActionButton(`giveSymbols_button${combinationIndex}`, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: symbols })), () => this.giveSymbols(combination));
                    });
                    break;
                case 'selectExtraDie':
                    for (let face=1; face<=6; face++) {
                        this.addActionButton(`selectExtraDie_button${face}`, formatTextIcons(DICE_STRINGS[face]), () => this.selectExtraDie(face));
                    }
                    break;
                case 'rerollOrDiscardDie':
                    this.addActionButton('falseBlessingReroll_button', _("Reroll"), () => {
                        dojo.addClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        this.falseBlessingAnkhAction = 'actFalseBlessingReroll';
                    }, null, null, 'gray');
                    this.addActionButton('falseBlessingDiscard_button', _("Discard"), () => {
                        dojo.addClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        this.falseBlessingAnkhAction = 'actFalseBlessingDiscard';
                    }, null, null, 'gray');
                    this.addActionButton('falseBlessingSkip_button', _("Skip"), () => this.falseBlessingSkip());
                    break;
                case 'rerollDice':
                    const argsRerollDice = args as EnteringRerollDiceArgs;
                    this.addActionButton('rerollDice_button', _("Reroll selected dice"), () => this.rerollDice(this.diceManager.getSelectedDiceIds()));
                    dojo.addClass('rerollDice_button', 'disabled');
                    if (argsRerollDice.min === 0) {
                        this.addActionButton('skipRerollDice_button', _("Skip"), () => this.rerollDice([]));
                    }
                    break;

                case 'AskMindbug':
                    const argsAskMindbug = args as EnteringAskMindbugArgs;
                    if (argsAskMindbug.canUseToken.includes(this.getPlayerId())) {
                        this.statusBar.addActionButton(/*TODOMB_*/('Mindbug!'), () => this.bgaPerformAction('actMindbug', { useEvasiveMindbug: false }), { color: 'alert' });
                    }
                    if (argsAskMindbug.canUseEvasiveMindbug.includes(this.getPlayerId())) {
                        this.statusBar.addActionButton(/*TODOMB_*/('Mindbug with ${card_name}').replace('${card_name}', this.cardsManager.getCardName(68, 'text-only')), () => this.bgaPerformAction('actMindbug', { useEvasiveMindbug: true }), { color: 'alert' });
                    }
                    this.statusBar.addActionButton(_('Skip'), () => this.bgaPerformAction('actPassMindbug'));
                    break;
                
                case 'resolveDice': 
                    const argsResolveDice = args as EnteringResolveDiceArgs;
                    if (argsResolveDice.isInHibernation) {
                        this.addActionButton('stayInHibernation_button',_("Stay in Hibernation"), () => this.stayInHibernation());
                        if (argsResolveDice.canLeaveHibernation) {
                            this.addActionButton('leaveHibernation_button',_("Leave Hibernation"), () => this.leaveHibernation(), null, null, 'red');
                        }
                    }
                    break;
                case 'prepareResolveDice':
                    const argsPrepareResolveDice = args as EnteringPrepareResolveDiceArgs;
                    if (argsPrepareResolveDice.hasEncasedInIce) {
                        this.statusBar.addActionButton(_("Skip"), () => this.skipFreezeDie());
                    }
                    break;
                case 'beforeResolveDice':
                    this.statusBar.addActionButton(_("Skip"), () => this.skipBeforeResolveDice());
                    break;
                case 'takeWickednessTile':
                    const argsTakeWickednessTile = args as EnteringTakeWickednessTileArgs;
                    this.statusBar.addActionButton(
                        _("Skip"), 
                        () => this.skipTakeWickednessTile(), 
                        { autoclick: !argsTakeWickednessTile.canTake && this.getGameUserPreference(202) != 2 }
                    );
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
                            this.addActionButton(`useChestThumping_button${playerId}`, dojo.string.substitute(_("Force ${player_name} to Yield Tokyo"), { 'player_name': `<span style="color: #${player.color}">${player.name}</span>`}), () => this.useChestThumping(playerId))
                        });
                        if (this.smashedPlayersStillInTokyo.length) {
                            this.addActionButton('skipChestThumping_button', dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(45, 'text-only')}), () => this.skipChestThumping());
                        }
                    } else {
                        const playerHasJets = argsLeaveTokyo.jetsPlayers?.includes(this.getPlayerId());
                        const playerHasSimianScamper = argsLeaveTokyo.simianScamperPlayers?.includes(this.getPlayerId());
                        if (playerHasJets || playerHasSimianScamper) {
                            label += formatTextIcons(` (- ${argsLeaveTokyo.jetsDamage} [heart])`);
                        }
                        this.addActionButton('stayInTokyo_button', label, () => this.onStayInTokyo());
                        this.addActionButton('leaveTokyo_button', _("Leave Tokyo"), () => this.onLeaveTokyo(playerHasJets ? 24 : undefined));
                        if (playerHasSimianScamper) {
                            this.addActionButton('leaveTokyoSimianScamper_button', _("Leave Tokyo") + ' : ' + dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCardsManager.getCardName(42, 'text-only') }), () => this.onLeaveTokyo(3042));
                        }
                        if (!argsLeaveTokyo.canYieldTokyo[this.getPlayerId()]) {
                            this.startActionTimer('stayInTokyo_button', ACTION_TIMER_DURATION);
                            dojo.addClass('leaveTokyo_button', 'disabled');
                        }
                    }
                    break;

                case 'stealCostumeCard':
                    const argsStealCostumeCard = args as EnteringStealCostumeCardArgs;

                    this.addActionButton('endStealCostume_button', _("Skip"), () => this.endStealCostume(), null, null, 'red');

                    if (!argsStealCostumeCard.canBuyFromPlayers && !argsStealCostumeCard.canGiveGift) {
                        this.startActionTimer('endStealCostume_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeForm':
                    const argsChangeForm = args as EnteringChangeFormArgs;
                    this.addActionButton('changeForm_button',   dojo.string.substitute(_("Change to ${otherForm}"), {'otherForm' : _(argsChangeForm.otherForm)}) + formatTextIcons(` ( 1 [Energy])`), () => this.changeForm());
                    this.addActionButton('skipChangeForm_button', _("Don't change form"), () => this.skipChangeForm());
                    dojo.toggleClass('changeForm_button', 'disabled', !argsChangeForm.canChangeForm);
                    document.getElementById(`changeForm_button`).dataset.enableAtEnergy = '1';
                    break;
                case 'leaveTokyoExchangeCard':
                    const argsExchangeCard = args as EnteringExchangeCardArgs;
                    this.addActionButton('skipExchangeCard_button', _("Skip"), () => this.skipExchangeCard());

                    if (!argsExchangeCard.canExchange) {
                        this.startActionTimer('skipExchangeCard_button', ACTION_TIMER_DURATION);
                    }

                    this.onEnteringExchangeCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'beforeEnteringTokyo':
                    const argsBeforeEnteringTokyo = args as BeforeEnteringTokyoArgs;


                    if (argsBeforeEnteringTokyo.canUseFelineMotor.includes(this.getPlayerId())) {
                        this.addActionButton('useFelineMotor_button', dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCardsManager.getCardName(36, 'text-only') }), () => this.useFelineMotor());
                    } 

                    this.addActionButton('skipBeforeEnteringTokyo_button', _("Skip"), () => this.skipBeforeEnteringTokyo());

                    break;
                case 'afterEnteringTokyo':
                    this.addActionButton('skipAfterEnteringTokyo_button', _("Skip"), () => this.skipAfterEnteringTokyo());
                    break;
                case 'buyCard':
                    const argsBuyCard = args as EnteringBuyCardArgs;
                    if (argsBuyCard.canUseMiraculousCatch) {
                        this.addActionButton('useMiraculousCatch_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(12, 'text-only')}), () => this.useMiraculousCatch());
                        if (!argsBuyCard.unusedMiraculousCatch) {
                            dojo.addClass('useMiraculousCatch_button', 'disabled');
                        }
                    }

                    const discardCards = args._private?.discardCards;
                    if (discardCards) {
                        let label = dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.cardsManager.getCardName(64, 'text-only')});
                        if (!discardCards.length) {
                            label += ` (${/*_TODOORI*/('discard is empty')})`;
                        }
                        this.addActionButton('useScavenger_button', label, () => this.showDiscardCards(discardCards, args));
                        if (!discardCards.length) {
                            dojo.addClass('useScavenger_button', 'disabled');
                        }
                    }

                    if (argsBuyCard.canUseAdaptingTechnology) {
                        this.addActionButton('renewAdaptiveTechnology_button', _("Renew cards") + ' (' + dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(24, 'text-only')}) + ')', () => this.renewPowerCards(3024));
                    }
                    this.addActionButton('renew_button', _("Renew cards") + formatTextIcons(` ( 2 [Energy])`), () => this.renewPowerCards(4));
                    document.getElementById('renew_button').dataset.enableAtEnergy = '2';
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    if (argsBuyCard.canSell) {
                        this.addActionButton('goToSellCard_button', _("End turn and sell cards"), 'goToSellCard');
                    }

                    this.addActionButton('endTurn_button', argsBuyCard.canSell ? _("End turn without selling") : _("End turn"), 'onEndTurn', null, null, 'red');

                    if (!argsBuyCard.canBuyOrNenew && !argsBuyCard.canSell) {
                        this.startActionTimer('endTurn_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'opportunistBuyCard':
                    this.addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');

                    if (!args.canBuy) {
                        this.startActionTimer('opportunistSkip_button', ACTION_TIMER_DURATION);
                    }

                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                    break;
                case 'cardIsBought':
                    this.addActionButton('skipCardIsBought_button', _("Skip"), () => this.skipCardIsBought());
                    break;
                case 'sellCard':
                    this.statusBar.addActionButton(_("End turn"), () => this.bgaPerformAction('actEndSell'), { color: 'alert' });
                    break;

                case 'answerQuestion':
                    this.onUpdateActionButtonsAnswerQuestion(args);
                    break;
            }

        }
    } 

    private onUpdateActionButtonsAnswerQuestion(args: EnteringAnswerQuestionArgs) {
        const question = args.question;

        switch(question.code) {
            case 'BambooSupply':
                const substituteParams = { card_name: this.evolutionCardsManager.getCardName(136, 'text-only')};
                const putLabel = dojo.string.substitute(_("Put ${number}[Energy] on ${card_name}"), {...substituteParams, number: 1});
                const takeLabel = dojo.string.substitute(_("Take all [Energy] from ${card_name}"), substituteParams);
                this.addActionButton('putEnergyOnBambooSupply_button', formatTextIcons(putLabel), () => this.putEnergyOnBambooSupply());
                this.addActionButton('takeEnergyOnBambooSupply_button', formatTextIcons(takeLabel), () => this.takeEnergyOnBambooSupply());
                const bambooSupplyQuestionArgs = question.args as BambooSupplyQuestionArgs;
                if (!bambooSupplyQuestionArgs.canTake) {
                    dojo.addClass('takeEnergyOnBambooSupply_button', 'disabled');
                }
                break;

            case 'GazeOfTheSphinxAnkh':
                this.addActionButton('gazeOfTheSphinxDrawEvolution_button', _("Draw Evolution"), () => this.gazeOfTheSphinxDrawEvolution());
                this.addActionButton('gazeOfTheSphinxGainEnergy_button', formatTextIcons(`${dojo.string.substitute(_('Gain ${energy}[Energy]'), { energy: 3})}`), () => this.gazeOfTheSphinxGainEnergy());
                break;

            case 'GazeOfTheSphinxSnake':
                this.addActionButton('gazeOfTheSphinxLoseEnergy_button', formatTextIcons(`${dojo.string.substitute(_('Lose ${energy}[Energy]'), { energy: 3})}`), () => this.gazeOfTheSphinxLoseEnergy());
                const gazeOfTheSphinxLoseEnergyQuestionArgs = question.args as GazeOfTheSphinxSnakeQuestionArgs;
                if (!gazeOfTheSphinxLoseEnergyQuestionArgs.canLoseEnergy) {
                    dojo.addClass('gazeOfTheSphinxLoseEnergy_button', 'disabled');
                }
                break;

            case 'GiveSymbol':
                const giveSymbolPlayerId = this.getPlayerId();
                const giveSymbolQuestionArgs = question.args as GiveSymbolQuestionArgs;
                giveSymbolQuestionArgs.symbols.forEach((symbol) => {
                    this.addActionButton(`giveSymbol_button${symbol}`, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_PADDED[symbol]})), () => this.giveSymbol(symbol));
                    if (!question.args[`canGive${symbol}`].includes(giveSymbolPlayerId)) {
                        dojo.addClass(`giveSymbol_button${symbol}`, 'disabled');
                    }
                    if (symbol == 5) {
                        const giveEnergyButton = document.getElementById(`giveSymbol_button5`);
                        giveEnergyButton.dataset.enableAtEnergy = '1';                        
                        this.updateEnableAtEnergy(this.getPlayerId());
                    }
                });
                break;
            case 'GiveEnergyOrLoseHearts':
                const giveEnergyOrLoseHeartsPlayerId = this.getPlayerId();
                const giveEnergyOrLoseHeartsQuestionArgs = question.args as GiveEnergyOrLoseHeartsQuestionArgs;
                this.addActionButton(`giveSymbol_button5`, formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_PADDED[5]})), () => this.giveSymbol(5));
                const giveEnergyButton = document.getElementById(`giveSymbol_button5`);
                giveEnergyButton.dataset.enableAtEnergy = '1';                   
                this.updateEnableAtEnergy(this.getPlayerId());
                if (!giveEnergyOrLoseHeartsQuestionArgs.canGiveEnergy.includes(giveEnergyOrLoseHeartsPlayerId)) {
                    giveEnergyButton.classList.add('disabled');
                    
                }
                this.addActionButton(`loseHearts_button`, formatTextIcons(dojo.string.substitute(_("Lose ${symbol}"), { symbol: `${giveEnergyOrLoseHeartsQuestionArgs.heartNumber}[Heart]`})), () => this.loseHearts());
                break;
            case 'FreezeRay':
                for (let face=1; face<=6; face++) {
                    this.addActionButton(`selectFrozenDieFace_button${face}`, formatTextIcons(DICE_STRINGS[face]), () => this.chooseFreezeRayDieFace(face));
                }
                break;
            case 'MiraculousCatch':
                const miraculousCatchArgs = question.args as MiraculousCatchQuestionArgs;
                this.addActionButton('buyCardMiraculousCatch_button', formatTextIcons(dojo.string.substitute(_('Buy ${card_name} for ${cost}[Energy]'), { card_name: this.cardsManager.getCardName(miraculousCatchArgs.card.type, 'text-only'), cost: miraculousCatchArgs.cost })), () => this.buyCardMiraculousCatch(false));
                if (miraculousCatchArgs.costSuperiorAlienTechnology !== null && miraculousCatchArgs.costSuperiorAlienTechnology !== miraculousCatchArgs.cost) {
                    this.addActionButton('buyCardMiraculousCatchUseSuperiorAlienTechnology_button', formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(28, 'text-only'), cost: miraculousCatchArgs.costSuperiorAlienTechnology })), () => this.buyCardMiraculousCatch(true));
                }
                this.addActionButton('skipMiraculousCatch_button', formatTextIcons(dojo.string.substitute(_('Discard ${card_name}'), { card_name: this.cardsManager.getCardName(miraculousCatchArgs.card.type, 'text-only') })), () => this.skipMiraculousCatch());

                document.getElementById('buyCardMiraculousCatch_button').dataset.enableAtEnergy = ''+miraculousCatchArgs.cost;
                dojo.toggleClass('buyCardMiraculousCatch_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < miraculousCatchArgs.cost);
                break;
            case 'DeepDive':
                const deepDiveCatchArgs = question.args as DeepDiveQuestionArgs;
                deepDiveCatchArgs.cards.forEach(card => {
                    this.addActionButton(`playCardDeepDive_button${card.id}`, formatTextIcons(dojo.string.substitute(_('Play ${card_name}'), { card_name: this.cardsManager.getCardName(card.type, 'text-only') })), () => this.playCardDeepDive(card.id));
                });
                break;
            case 'Treasure':
                const treasureArgsArgs = question.args as TreasureQuestionArgs;
                treasureArgsArgs.cards.forEach(card => {
                    this.statusBar.addActionButton(formatTextIcons(dojo.string.substitute(_('Buy ${card_name}'), { card_name: this.cardsManager.getCardName(card.type, 'text-only') })), () => this.bgaPerformAction('actTreasure', { id: card.id }));
                });
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actPassTreasure'), { color: 'secondary' });
                break;
            case 'ExoticArms':
                const useExoticArmsLabel = dojo.string.substitute(_("Put ${number}[Energy] on ${card_name}"), { card_name: this.evolutionCardsManager.getCardName(26, 'text-only'), number: 2 });
                
                this.addActionButton('useExoticArms_button', formatTextIcons(useExoticArmsLabel), () => this.useExoticArms());
                this.addActionButton('skipExoticArms_button', _('Skip'), () => this.skipExoticArms());
                dojo.toggleClass('useExoticArms_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useExoticArms_button').dataset.enableAtEnergy = '2';
                break;
            case 'TargetAcquired':
                const targetAcquiredCatchArgs = question.args as TargetAcquiredQuestionArgs;
                this.addActionButton('giveTarget_button', dojo.string.substitute(_("Give target to ${player_name}"), {'player_name': this.getPlayer(targetAcquiredCatchArgs.playerId).name}), () => this.giveTarget());
                this.addActionButton('skipGiveTarget_button', _('Skip'), () => this.skipGiveTarget());
                break;
            case 'LightningArmor':
                this.addActionButton('useLightningArmor_button', _("Throw dice"), () => this.useLightningArmor());
                this.addActionButton('skipLightningArmor_button', _('Skip'), () => this.skipLightningArmor());
                break;
            case 'EnergySword':
                this.addActionButton('useEnergySword_button',  dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCardsManager.getCardName(147, 'text-only') }), () => this.answerEnergySword(true));
                this.addActionButton('skipEnergySword_button', _('Skip'), () => this.answerEnergySword(false));
                dojo.toggleClass('useEnergySword_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useEnergySword_button').dataset.enableAtEnergy = '2';
                break;
            case 'SunkenTemple':
                this.addActionButton('useSunkenTemple_button',  dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCardsManager.getCardName(157, 'text-only') }), () => this.answerSunkenTemple(true));
                this.addActionButton('skipSunkenTemple_button', _('Skip'), () => this.answerSunkenTemple(false));
                break;
            case 'ElectricCarrot':
                this.addActionButton('answerElectricCarrot5_button',  formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: '[Energy]'})), () => this.answerElectricCarrot(5));
                dojo.toggleClass('answerElectricCarrot5_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 1);
                document.getElementById('answerElectricCarrot5_button').dataset.enableAtEnergy = '1';
                this.addActionButton('answerElectricCarrot4_button',  formatTextIcons(_("Lose 1 extra [Heart]")), () => this.answerElectricCarrot(4));
                break;
            case 'SuperiorAlienTechnology':
                this.addActionButton('throwDieSuperiorAlienTechnology_button', _('Roll a die'), () => this.throwDieSuperiorAlienTechnology());
                break;
        }
    }

    ///////////////////////////////////////////////////
    //// Utility methods


    ///////////////////////////////////////////////////

    public getPlayerId(): number {
        return Number(this.player_id);
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

    public isOrigins(): boolean {
        return this.gamedatas.origins;
    }

    public isDarkEdition(): boolean {
        return this.gamedatas.darkEdition;
    }

    public isDefaultFont(): boolean {
        return this.getGameUserPreference(201) == 1;
    }

    public getPlayer(playerId: number): KingOfTokyoPlayer {
        return this.gamedatas.players[playerId];
    }

    public createButton(destinationId: string, id: string, text: string, callback: Function, disabled: boolean = false, dojoPlace: string = undefined): HTMLElement {
        return this.statusBar.addActionButton(text, callback, {
            id,
            classes: disabled ? 'disabled' : '',
            destination: $(destinationId),
        });
    }

    private addTwoPlayerVariantNotice(gamedatas: KingOfTokyoGamedatas) {
        // 2-players variant notice
        if (Object.keys(gamedatas.players).length == 2 && this.getGameUserPreference(203) == 1) {
            dojo.place(`
                    <div id="board-corner-highlight"></div>
                    <div id="twoPlayersVariant-message">
                        ${_("You are playing the 2-players variant.")}<br>
                        ${_("When entering or starting a turn on Tokyo, you gain 1 energy instead of points")}.<br>
                        ${_("You can check if variant is activated in the bottom left corner of the table.")}<br>
                        <div style="text-align: center"><a id="hide-twoPlayersVariant-message">${_("Dismiss")}</a></div>
                    </div>
                `, 'board');

            document.getElementById('hide-twoPlayersVariant-message').addEventListener('click', () => this.setGameUserPreference(203, 2));
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

            this.addTooltipHtml(`health-counter-wrapper-${player.id}`, _("Health"));
            this.addTooltipHtml(`energy-counter-wrapper-${player.id}`, _("Energy"));
            if (gamedatas.wickednessExpansion) {
                this.addTooltipHtml(`wickedness-counter-wrapper-${player.id}`, _("Wickedness points"));
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
                    <button id="see-monster-evolution-player-${playerId}" class="bgabutton bgabutton_gray ${Number(this.gamedatas.gamestate.id) >= 15 /*ST_PLAYER_CHOOSE_INITIAL_CARD*/ ? 'visible' : ''}">
                        ${_('Show Evolutions')}
                    </button>
                    </div>
                </div>`, `player_board_${player.id}`);

                const handCounter = new ebg.counter();
                handCounter.create(`playerhand-counter-${playerId}`);
                handCounter.setValue(player.hiddenEvolutions.length);
                this.handCounters[playerId] = handCounter;

                this.addTooltipHtml(`playerhand-counter-wrapper-${player.id}`, _("Number of Evolution cards in hand."));

                document.getElementById(`see-monster-evolution-player-${playerId}`).addEventListener('click', () => this.showPlayerEvolutions(playerId));
            }

            dojo.place(`<div class="player-tokens">
                <div id="player-board-target-tokens-${player.id}" class="player-token target-tokens"></div>
                <div id="player-board-shrink-ray-tokens-${player.id}" class="player-token shrink-ray-tokens"></div>
                <div id="player-board-poison-tokens-${player.id}" class="player-token poison-tokens"></div>
                <div id="player-board-mindbug-tokens-${player.id}" class="player-token mindbug-tokens"></div>
            </div>`, `player_board_${player.id}`);

            if (!eliminated) {
                this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
                this.setPoisonTokens(playerId, player.poisonTokens);
                this.setPlayerTokens(playerId, gamedatas.targetedPlayer == playerId ? 1 : 0, 'target');
                this.setPlayerTokens(playerId, player.mindbugTokens, 'mindbug');
            }

            dojo.place(`<div id="player-board-monster-figure-${player.id}" class="monster-figure monster${player.monster}"><div class="kot-token"></div></div>`, `player_board_${player.id}`);

            if (player.location > 0) {
                dojo.addClass(`overall_player_board_${playerId}`, 'intokyo');
            }
            if (eliminated) {
                setTimeout(() => this.eliminatePlayer(playerId), 200);
            }
        });

        this.addTooltipHtmlToClass('shrink-ray-tokens', this.SHINK_RAY_TOKEN_TOOLTIP);
        this.addTooltipHtmlToClass('poison-tokens', this.POISON_TOKEN_TOOLTIP);
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
        const costumeSelected = this.tableCenter.getVisibleCards()?.getSelection().length === 1;
        const evolutionSelected = this.getPlayerTable(this.getPlayerId())?.pickEvolutionCards.getSelection().length === 1;
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
            this.fadeOutAndDestroy('monster-pick');
        }
    }

    private removeMutantEvolutionChoice() {
        if (document.getElementById('mutant-evolution-choice')) {
            this.fadeOutAndDestroy('mutant-evolution-choice');
        }
    }

    private showMainTable() {
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            dojo.removeClass('kot-table', 'pickMonsterOrEvolutionDeck');
            this.tableManager.setAutoZoomAndPlacePlayerTables();
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

    public onVisibleCardClick(stock: CardStock<Card>, card: Card, from: number = 0, warningChecked: boolean = false) { // from : player id
        if (!card?.id) {
            return;
        }

        if (stock.getCardElement(card).classList.contains('disabled')) {
            stock.unselectCard(card);
            return;
        }

        const stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            if (!this.isInitialCardDoubleSelection()) {
                this.chooseInitialCard(card.id, null);
            } else {
                this.confirmDoubleSelectionCheckState();
            }
        } else if (stateName === 'stealCostumeCard') {
            this.stealCostumeCard(card.id);
        } else if (stateName === 'sellCard') {
            this.sellCard(card.id);
        } else if (stateName === 'chooseMimickedCard' || stateName === 'opportunistChooseMimicCard') {
            this.chooseMimickedCard(card.id);
        } else if (stateName === 'changeMimickedCard') {
            this.changeMimickedCard(card.id);
        } else if (stateName === 'chooseMimickedCardWickednessTile') {
            this.chooseMimickedCardWickednessTile(card.id);
        } else if (stateName === 'changeMimickedCardWickednessTile') {
            this.changeMimickedCardWickednessTile(card.id);
        } else if (stateName === 'buyCard' || stateName === 'opportunistBuyCard') {
            const buyCardArgs = this.gamedatas.gamestate.args as EnteringBuyCardArgs;
            const warningIcon = !warningChecked && buyCardArgs.warningIds[card.id];
            if (!warningChecked && buyCardArgs.noExtraTurnWarning.includes(card.type)) {
                this.confirmationDialog(
                    this.getNoExtraTurnWarningMessage(), 
                    () => this.onVisibleCardClick(stock, card, from, true)
                );
            } else if (warningIcon) {
                this.confirmationDialog(
                    formatTextIcons(dojo.string.substitute(_("Are you sure you want to buy that card? You won't gain ${symbol}"), { symbol: warningIcon})), 
                    () => this.onVisibleCardClick(stock, card, from, true)
                );
            } else {
                const cardCostSuperiorAlienTechnology = buyCardArgs.cardsCostsSuperiorAlienTechnology?.[card.id];
                const cardCostBobbingForApples = buyCardArgs.cardsCostsBobbingForApples?.[card.id];
                const canUseSuperiorAlienTechnologyForCard = cardCostSuperiorAlienTechnology !== null && cardCostSuperiorAlienTechnology !== undefined && cardCostSuperiorAlienTechnology !== buyCardArgs.cardsCosts[card.id];
                const canUseBobbingForApplesForCard = cardCostBobbingForApples !== null && cardCostBobbingForApples !== undefined && cardCostBobbingForApples !== buyCardArgs.cardsCosts[card.id];
                if (canUseSuperiorAlienTechnologyForCard || canUseBobbingForApplesForCard) {
                    const both = canUseSuperiorAlienTechnologyForCard && canUseBobbingForApplesForCard;
                    const keys = [
                        formatTextIcons(dojo.string.substitute(_('Don\'t use ${card_name} and pay full cost ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(canUseSuperiorAlienTechnologyForCard ? 28 : 85, 'text-only'), cost: buyCardArgs.cardsCosts[card.id] })),
                        _('Cancel')
                    ];
                    if (cardCostBobbingForApples) {
                        keys.unshift(formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(85, 'text-only'), cost: cardCostBobbingForApples })));
                    }
                    if (canUseSuperiorAlienTechnologyForCard) {
                        keys.unshift(formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(28, 'text-only'), cost: cardCostSuperiorAlienTechnology })));
                    }

                    this.multipleChoiceDialog(
                        dojo.string.substitute(_('Do you want to buy the card at reduced cost with ${card_name} ?'), { 'card_name': this.evolutionCardsManager.getCardName(28, 'text-only')}), 
                        keys, 
                        (choice: string) => {
                            const choiceIndex = Number(choice);
                            if (choiceIndex < (both ? 3 : 2)) {
                                this.tableCenter.removeOtherCardsFromPick(card.id);
                                this.buyCard(card.id, from, canUseSuperiorAlienTechnologyForCard && choiceIndex === 0, canUseBobbingForApplesForCard && choiceIndex === (both ? 1 : 0));
                            }
                        }
                      );

                      if (canUseSuperiorAlienTechnologyForCard && buyCardArgs.canUseSuperiorAlienTechnology === false || cardCostSuperiorAlienTechnology > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById(`choice_btn_0`).classList.add('disabled');
                      }
                      if (canUseBobbingForApplesForCard && cardCostBobbingForApples > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById(`choice_btn_${(both ? 1 : 0)}`).classList.add('disabled');
                      }
                      if (buyCardArgs.cardsCosts[card.id] > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById(`choice_btn_${(both ? 2 : 1)}`).classList.add('disabled');
                      }
                } else {
                    this.tableCenter.removeOtherCardsFromPick(card.id);
                    this.buyCard(card.id, from);
                }
            }
        } else if (stateName === 'discardKeepCard') {
            this.discardKeepCard(card.id);
        } else if (stateName === 'leaveTokyoExchangeCard') {
            this.exchangeCard(card.id);
        } else if (stateName === 'answerQuestion') {
            const args = this.gamedatas.gamestate.args as EnteringAnswerQuestionArgs;
            if (args.question.code === 'Bamboozle') {
                this.buyCardBamboozle(card.id, from);
            } else if (args.question.code === 'ChooseMimickedCard') {
                this.chooseMimickedCard(card.id);
            } else if (args.question.code === 'MyToy') {
                this.reserveCard(card.id);
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

    public onSelectGiftEvolution(cardId: number) {
        let generalActionButtons = Array.from(document.getElementById(`generalactions`).getElementsByClassName(`action-button`)) as HTMLElement[];
        generalActionButtons = generalActionButtons.slice(0, generalActionButtons.findIndex(button => button.id == 'endStealCostume_button'));
        generalActionButtons.forEach(generalActionButton => generalActionButton.remove());
        const args = this.gamedatas.gamestate.args as EnteringStealCostumeCardArgs;
        args.woundedPlayersIds.slice().reverse().forEach(woundedPlayerId => {
            const woundedPlayer = this.getPlayer(woundedPlayerId);
            const cardType = Number((document.querySelector(`[data-evolution-id="${cardId}"]`) as HTMLDivElement).dataset.evolutionType);
            const label = /*TODOPUHA_*/('Give ${card_name} to ${player_name}').replace('${card_name}', this.evolutionCardsManager.getCardName(cardType, 'text-only')).replace('${player_name}', `<strong style="color: #${woundedPlayer.color};">${woundedPlayer.name}</strong>`);
            const button = this.createButton('endStealCostume_button', `giveGift${cardId}to${woundedPlayerId}_button`, label, () => this.giveGiftEvolution(cardId, woundedPlayerId), false, 'before')
            document.getElementById(`giveGift${cardId}to${woundedPlayerId}_button`).insertAdjacentElement('beforebegin', button);
        });
    }

    public onHiddenEvolutionClick(card: EvolutionCard) {
        const stateName = this.getStateName();
        if (stateName === 'answerQuestion') {
            const args = this.gamedatas.gamestate.args as EnteringAnswerQuestionArgs;
            if (args.question.code === 'GazeOfTheSphinxSnake') {
                this.gazeOfTheSphinxDiscardEvolution(Number(card.id));
                this.gazeOfTheSphinxDiscardEvolution(Number(card.id));
                return;
            }
        } else if (stateName === 'stealCostumeCard') {
            this.onSelectGiftEvolution(card.id);
            this.onSelectGiftEvolution(card.id);
            return;
        }
        
        const args = this.gamedatas.gamestate.args as EnteringStepEvolutionArgs;
        if (args.noExtraTurnWarning?.includes(card.type)) {
            this.confirmationDialog(
                this.getNoExtraTurnWarningMessage(), 
                () => this.playEvolution(card.id)
            );
        } else {
            this.playEvolution(card.id);
        }
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
        } else if (stateName === 'stealCostumeCard') {
            this.onSelectGiftEvolution(cardId);
        }
    }
    
    private setBuyDisabledCardByCost(disabledIds: number[], cardsCosts: { [cardId: number]: number }, playerEnergy: number) {
        this.setBuyDisabledCardByCostForStock(disabledIds, cardsCosts, playerEnergy, this.tableCenter.getVisibleCards());
    }
    
    private setBuyDisabledCardByCostForStock(disabledIds: number[], cardsCosts: { [cardId: number]: number }, playerEnergy: number, stock: CardStock<Card>) {
        const disabledCardsIds = [...disabledIds, ...Object.keys(cardsCosts).map(cardId => Number(cardId))];
        disabledCardsIds.forEach(id => {
            const disabled = disabledIds.some(disabledId => disabledId == id) || cardsCosts[id] > playerEnergy;
            const cardDiv = this.cardsManager.getCardElement({ id } as Card);
            cardDiv?.classList.toggle('bga-cards_disabled-card', disabled);
        });

        const selectableCards = stock.getCards().filter(card => {
            const disabled = disabledIds.some(disabledId => disabledId == card.id) || cardsCosts[card.id] > playerEnergy;
            return !disabled;
        });
        stock.setSelectableCards(selectableCards);
    }

    private getCardCosts(args: EnteringBuyCardArgs | EnteringStealCostumeCardArgs) {
        let cardsCosts = {...args.cardsCosts};
        const argsBuyCard = args as EnteringBuyCardArgs;
        if (argsBuyCard.gotSuperiorAlienTechnology) {
            cardsCosts = {...cardsCosts, ...argsBuyCard.cardsCostsSuperiorAlienTechnology};
        }
        if (argsBuyCard.cardsCostsBobbingForApples) {
            Object.keys(argsBuyCard.cardsCostsBobbingForApples).forEach(cardId => {
                if (argsBuyCard.cardsCostsBobbingForApples[cardId] < cardsCosts[cardId]) {
                    cardsCosts[cardId] = argsBuyCard.cardsCostsBobbingForApples[cardId];
                }
            });
        }

        return cardsCosts;
    }

    // called on state enter and when energy number is changed
    private setBuyDisabledCard(args: EnteringBuyCardArgs | EnteringStealCostumeCardArgs = null, playerEnergy: number = null) {
        if (!this.isCurrentPlayerActive()) {
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

        let cardsCosts = this.getCardCosts(args);

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
                dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (2[Energy])`), { card_name: this.cardsManager.getCardName(37, 'text-only'), hearts: 1 }), 
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
                dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (1[Energy])`), { card_name: this.evolutionCardsManager.getCardName(27, 'text-only'), hearts: 1 }), 
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
    

    public addAutoSkipPlayEvolutionButton() {
        if (!document.getElementById('autoSkipPlayEvolutionButton')) {
            this.createButton(
                'autoSkipPlayEvolution-wrapper', 
                'autoSkipPlayEvolutionButton', 
                _("Ask to play evolution") + ' &#x25BE;', 
                () => this.toggleAutoSkipPlayEvolutionPopin(), 
            );
        }
    }

    private toggleAutoSkipPlayEvolutionPopin() {
        const bubble = document.getElementById(`discussion_bubble_autoSkipPlayEvolution`);
        if (bubble?.dataset.visible === 'true') {
            this.closeAutoSkipPlayEvolutionPopin();
        } else {
            this.openAutoSkipPlayEvolutionPopin();
        }
    }

    private openAutoSkipPlayEvolutionPopin() {
        const popinId = `discussion_bubble_autoSkipPlayEvolution`;
        let bubble = document.getElementById(popinId);
        if (!bubble) { 
            let html = `<div id="${popinId}" class="discussion_bubble autoSkipPlayEvolutionBubble">
                <h3>${_("Ask to play Evolution, for Evolutions playable on specific occasions")}</h3>
                <div class="autoSkipPlayEvolution-option">
                    <input type="radio" name="autoSkipPlayEvolution" value="0" id="autoSkipPlayEvolution-all" />
                    <label for="autoSkipPlayEvolution-all">
                        ${_("Ask for every specific occasion even if I don't have the card in my hand.")}
                        <div class="label-detail">
                            ${_("Recommended. You won't be asked when your hand is empty")}
                        </div>
                    </label>
                </div>
                <div class="autoSkipPlayEvolution-option">
                    <input type="radio" name="autoSkipPlayEvolution" value="1" id="autoSkipPlayEvolution-real" />
                    <label for="autoSkipPlayEvolution-real">
                        ${_("Ask only if I have in my hand an Evolution matching the specific occasion.")}<br>
                        <div class="label-detail spe-warning">
                            <strong>${_("Warning:")}</strong> ${_("Your opponent can deduce what you have in hand with this option.")}
                        </div>
                    </label>
                </div>
                <div class="autoSkipPlayEvolution-option">
                    <input type="radio" name="autoSkipPlayEvolution" value="2" id="autoSkipPlayEvolution-turn" />
                    <label for="autoSkipPlayEvolution-turn">
                        ${_("Do not ask until my next turn.")}<br>
                        <div class="label-detail spe-warning">
                            <strong>${_("Warning:")}</strong> ${_("Do it only if you're sure you won't need an Evolution soon.")}
                        </div>
                    </label>
                </div>
                <div class="autoSkipPlayEvolution-option">
                    <input type="radio" name="autoSkipPlayEvolution" value="3" id="autoSkipPlayEvolution-off" />
                    <label for="autoSkipPlayEvolution-off">
                        ${_("Do not ask until I turn it back on.")}
                        <div class="label-detail spe-warning">
                            <strong>${_("Warning:")}</strong> ${_("Do it only if you're sure you won't need an Evolution soon.")}
                        </div>
                    </label>
                </div>
            </div>`;
            dojo.place(html, 'autoSkipPlayEvolutionButton');
            Array.from(document.querySelectorAll('input[name="autoSkipPlayEvolution"]')).forEach((input: HTMLInputElement) => {
                input.addEventListener('change', () => {
                    const value = (document.querySelector('input[name="autoSkipPlayEvolution"]:checked') as HTMLInputElement).value;
                    this.setAskPlayEvolution(Number(value));
                    setTimeout(() => this.closeAutoSkipPlayEvolutionPopin(), 100);
                });
            });

            bubble = document.getElementById(popinId);

            this.notif_updateAskPlayEvolution({
                args: {
                    value: this.gamedatas.askPlayEvolution
                }
            } as any);
        }

        bubble.style.display = 'block';
        bubble.dataset.visible = 'true';
    }

    private closeAutoSkipPlayEvolutionPopin() {
        const bubble = document.getElementById(`discussion_bubble_autoSkipPlayEvolution`);
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
            if (playerTable.cards.getCards().some(item => Number(item.id) == card.id)) {
                this.cardsManager.placeMimicOnCard(type, card, this.wickednessTilesManager);
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
            if (playerTable.cards.getCards().some(item => Number(item.id) == card.id)) {
                this.cardsManager.removeMimicOnCard(type, card);
            }
        });
    }

    private setMimicEvolutionToken(card: EvolutionCard) {
        if (!card) {
            return;
        }

        this.evolutionCardsManager.placeMimicOnCard(card);
        this.setMimicEvolutionTooltip(card);
    }

    private setMimicTooltip(type: 'card' | 'tile', mimickedCard: Card) {
        this.playerTables.forEach(playerTable => {
            const mimicCardId = type === 'tile' ? 106 : 27;
            const cards: any[] = (type === 'tile' ? playerTable.wickednessTiles : playerTable.cards).getCards();
            const mimicCardItem = cards.find(item => Number(item.type) == mimicCardId);
            if (mimicCardItem) {
                const cardManager = type === 'tile' ? this.wickednessTilesManager : this.cardsManager;
                cardManager.changeMimicTooltip(cardManager.getId(mimicCardItem),  this.cardsManager.getMimickedCardText(mimickedCard));
            }
        });
    }

    private setMimicEvolutionTooltip(mimickedCard: EvolutionCard) {
        this.playerTables.forEach(playerTable => {
            const mimicCardItem = playerTable.visibleEvolutionCards.getCards().find(item => Number(item.type) == 18);
            if (mimicCardItem) {
                this.evolutionCardsManager.changeMimicTooltip(this.evolutionCardsManager.getId(mimicCardItem), this.evolutionCardsManager.getMimickedCardText(mimickedCard));
            }
        });
    }

    private removeMimicEvolutionToken(card: EvolutionCard) {
        this.setMimicEvolutionTooltip(null);

        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.getCards().some(item => Number(item.id) == card.id)) {
                this.evolutionCardsManager.removeMimicOnCard(card);
            }
        });
    }
    
    public showEvolutionsPopin(cardsTypes: number[], title: string): void {
        
        const viewCardsDialog = new ebg.popindialog();
        viewCardsDialog.create('kotViewEvolutionsDialog');
        viewCardsDialog.setTitle(title);
        
        var html = `<div id="see-monster-evolutions"></div>`;
        
        // Show the dialog
        viewCardsDialog.setContent(html);
        const stock = new LineStock<EvolutionCard>(this.evolutionCardsManager, document.getElementById('see-monster-evolutions'));
        stock.addCards(cardsTypes.map((cardType, index) => ({ id: 100000 + index, type: cardType } as EvolutionCard)));
        
        viewCardsDialog.show();

        // Replace the function call when it's clicked
        viewCardsDialog.replaceCloseCallback(() => {  
            stock.remove();  
            viewCardsDialog.destroy();
        });
    }
    
    private showPlayerEvolutions(playerId: number) {
        const cardsTypes = this.gamedatas.players[playerId].ownedEvolutions.map(evolution => evolution.type);
        this.showEvolutionsPopin(cardsTypes, dojo.string.substitute(_("Evolution cards owned by ${player_name}"), {'player_name': this.gamedatas.players[playerId].name}));
    }
    
    public showDiscardCards(cards: Card[], args: EnteringBuyCardArgs): void {
        
        const buyCardFromDiscardDialog = new ebg.popindialog();
        buyCardFromDiscardDialog.create('kotDiscardCardsDialog');
        buyCardFromDiscardDialog.setTitle(/*_TODOORI*/('Discard cards'));
        
        var html = `<div id="see-monster-evolutions"></div>`;
        
        // Show the dialog
        buyCardFromDiscardDialog.setContent(html);        
        buyCardFromDiscardDialog.show();

        const stock = new LineStock<Card>(this.cardsManager, document.getElementById('see-monster-evolutions'));
        stock.addCards(cards);
        stock.onCardClick = (card: Card) => {
            this.onVisibleCardClick(stock, card);
            stock.removeAll();          
            buyCardFromDiscardDialog.destroy();
        };
        stock.setSelectionMode('single');
        this.setBuyDisabledCardByCostForStock(args.disabledIds, this.getCardCosts(args), this.energyCounters[this.getPlayerId()].getValue(), this.tableCenter.getVisibleCards());
        
        buyCardFromDiscardDialog.show();

        // Replace the function call when it's clicked
        buyCardFromDiscardDialog.replaceCloseCallback(() => {  
            stock.removeAll();          
            buyCardFromDiscardDialog.destroy();
        });
    }
    
    public getNoExtraTurnWarningMessage(): string {
        return _('As you are in a Mindbug turn, you cannot befenit from the extra turn effect');
    }

    public pickMonster(monster: number): void {
        this.bgaPerformAction('actPickMonster', {
            monster
        });
    }

    public pickEvolutionForDeck(id: number) {
        this.bgaPerformAction('actPickEvolutionForDeck', {
            id
        });
    }

    public chooseInitialCard(id: number | null, evolutionId: number | null) {
        this.bgaPerformAction('actChooseInitialCard', {
            id,
            evolutionId,
        });
    }

    public skipBeforeStartTurn() {
        this.bgaPerformAction('actSkipBeforeStartTurn');
    }

    public skipBeforeEndTurn() {
        this.bgaPerformAction('actSkipBeforeEndTurn');
    }

    public skipBeforeEnteringTokyo() {
        this.bgaPerformAction('actSkipBeforeEnteringTokyo');
    }

    public skipAfterEnteringTokyo() {
        this.bgaPerformAction('actSkipAfterEnteringTokyo');
    }

    public giveSymbolToActivePlayer(symbol: number) {
        this.bgaPerformAction('actGiveSymbolToActivePlayer', {
            symbol
        });
    }

    public giveSymbol(symbol: number) {
        this.bgaPerformAction('actGiveSymbol', {
            symbol
        });
    }

    public onRethrow() {
        this.rethrowDice(this.diceManager.destroyFreeDice());      
    }

    public rethrowDice(diceIds: number[]) {
        this.bgaPerformAction('actRethrow', {
            diceIds: diceIds.join(',')
        });
    }

    public rethrow3() {
        const lockedDice = this.diceManager.getLockedDice();

        this.bgaPerformAction('actRethrow3', {
            diceIds: lockedDice.map(die => die.id).join(',')
        });
    }

    public rerollDie(id: number) {
        const lockedDice = this.diceManager.getLockedDice();

        this.bgaPerformAction('actRerollDie', {
            id,
            diceIds: lockedDice.map(die => die.id).join(',')
        });
    }

    public rethrow3camouflage() {
        this.bgaPerformAction('actRethrow3Camouflage');
    }

    public rethrow3psychicProbe() {
        this.bgaPerformAction('actRethrow3PsychicProbe');
    }

    public rethrow3changeDie() {
        this.bgaPerformAction('actRethrow3ChangeDie');
    }

    public buyEnergyDrink() {
        const diceIds = this.diceManager.destroyFreeDice();

        this.bgaPerformAction('actBuyEnergyDrink', {
            diceIds: diceIds.join(',')
        });
    }

    public useSmokeCloud() {
        const diceIds = this.diceManager.destroyFreeDice();
        
        this.bgaPerformAction('actUseSmokeCloud', {
            diceIds: diceIds.join(',')
        });
    }

    public useCultist() {
        const diceIds = this.diceManager.destroyFreeDice();
        
        this.bgaPerformAction('actUseCultist', {
            diceIds: diceIds.join(',')
        });
    }

    public useRapidHealing() {
        this.bgaPerformAction('actUseRapidHealing', null, { lock: false, checkAction: false });
    }

    public useMothershipSupport() {
        this.bgaPerformAction('actUseMothershipSupport', null, { lock: false, checkAction: false });
    }

    public useRapidCultist(type: number) { // 4 for health, 5 for energy
       this.bgaPerformAction('actUseRapidCultist', { type }, { lock: false, checkAction: false });
    }

    public setSkipBuyPhase(skipBuyPhase: boolean) {
        this.bgaPerformAction('actSetSkipBuyPhase', {
            skipBuyPhase: skipBuyPhase
        }, { lock: false, checkAction: false });
    }

    public changeDie(id: number, value: number, card: number, cardId?: number) {
        this.bgaPerformAction('actChangeDie', {
            id,
            value,
            card,
            cardId,
        });
    }

    public psychicProbeRollDie(id: number) {
        this.bgaPerformAction('actChangeActivePlayerDie', {
            id
        });
    }

    public goToChangeDie(confirmed: boolean = false) {
        const args = this.gamedatas.gamestate.args as EnteringThrowDiceArgs;
        if (!confirmed && args.throwNumber == 1 && args.maxThrowNumber > 1) {
            this.confirmationDialog(
                formatTextIcons(_('Are you sure you want to resolve dice without any reroll? If you want to change your dice, click on the dice you want to keep and use "Reroll dice" button to reroll the others.')), 
                () => this.goToChangeDie(true)
            );
            return;
        }

        this.bgaPerformAction('actGoToChangeDie');
    }

    public resolveDice() {
        this.bgaPerformAction('actResolve');
    }

    public support() {
        this.bgaPerformAction('actSupport');
    }

    public dontSupport() {
        this.bgaPerformAction('actDontSupport');
    }

    public discardDie(id: number) {
        this.bgaPerformAction('actDiscardDie', {
            id: id
        });
    }

    public rerollOrDiscardDie(id: number) {
        if (!this.falseBlessingAnkhAction) {
            return;
        }

        this.bgaPerformAction(this.falseBlessingAnkhAction, {
            id
        });
    }

    public freezeDie(id: number) {
        this.bgaPerformAction('actFreezeDie', {
            id
        });
    }

    public skipFreezeDie() {
        this.bgaPerformAction('actSkipFreezeDie');
    }

    public discardKeepCard(id: number) {
        this.bgaPerformAction('actDiscardKeepCard', {
            id
        });
    }

    public giveGoldenScarab(playerId: number) {
        this.bgaPerformAction('actGiveGoldenScarab', {
            playerId
        });
    }

    public giveSymbols(symbols: number[]) {
        this.bgaPerformAction('actGiveSymbols', {
            symbols: symbols.join(',')
        });
    }

    public selectExtraDie(face: number) {
        this.bgaPerformAction('actSelectExtraDie', {
            face
        });
    }

    public falseBlessingReroll(id: number) {
        this.bgaPerformAction('actFalseBlessingReroll', {
            id
        });
    }

    public falseBlessingDiscard(id: number) {
        this.bgaPerformAction('actFalseBlessingDiscard', {
            id
        });
    }

    public falseBlessingSkip() {
        this.bgaPerformAction('actFalseBlessingSkip');
    }

    public rerollDice(diceIds: number[]) {
        this.bgaPerformAction('actRerollDice', {
            ids: diceIds.join(',')
        });
    }

    public takeWickednessTile(id: number) {
        this.bgaPerformAction('actTakeWickednessTile', {
            id,
        });
    }

    public skipTakeWickednessTile() {
        this.bgaPerformAction('actSkipTakeWickednessTile');
    }

    public applyHeartActions(selections: HeartActionSelection[]) {
        this.bgaPerformAction('actApplyHeartDieChoices', {
            heartDieChoices: JSON.stringify(selections)
        });
    }

    public applySmashActions(selections: { [playerId: number]: SmashAction } ) {
        console.warn(selections);
        this.bgaPerformAction('actApplySmashDieChoices', {
            smashDieChoices: JSON.stringify(selections)
        });
    }

    public chooseEvolutionCard(id: number) {
        this.bgaPerformAction('actChooseEvolutionCard', {
            id
        });
    }

    public onStayInTokyo() {
        this.bgaPerformAction('actStay');
    }
    public onLeaveTokyo(useCard?: number) {
        this.bgaPerformAction('actLeave', { useCard });
    }

    public stealCostumeCard(id: number) {
        this.bgaPerformAction('actStealCostumeCard', {
            id
        });
    }

    public changeForm() {
        this.bgaPerformAction('actChangeForm');
    }

    public skipChangeForm() {
        this.bgaPerformAction('actSkipChangeForm');
    }

    public buyCard(id: number, from: number, useSuperiorAlienTechnology: boolean = false, useBobbingForApples: boolean = false) {
        this.bgaPerformAction('actBuyCard', {
            id,
            from,
            useSuperiorAlienTechnology,
            useBobbingForApples
        });
    }

    public buyCardBamboozle(id: number, from: number) {
        this.bgaPerformAction('actBuyCardBamboozle', {
            id,
            from
        });
    }

    public chooseMimickedCard(id: number) {
        this.bgaPerformAction('actChooseMimickedCard', {
            id
        });
    }

    public chooseMimickedEvolution(id: number) {
        this.bgaPerformAction('actChooseMimickedEvolution', {
            id: id
        });
    }

    public changeMimickedCard(id: number) {
        this.bgaPerformAction('actChangeMimickedCard', {
            id
        });
    }

    public chooseMimickedCardWickednessTile(id: number) {
        this.bgaPerformAction('actChooseMimickedCardWickednessTile', {
            id
        });
    }

    public changeMimickedCardWickednessTile(id: number) {
        this.bgaPerformAction('actChangeMimickedCardWickednessTile', {
            id
        });
    }

    public sellCard(id: number) {
        this.bgaPerformAction('actSellCard', {
            id
        });
    }

    public renewPowerCards(cardType: number) {
        this.bgaPerformAction('actRenewPowerCards', {
            cardType
        });
    }

    public skipCardIsBought() {
        this.bgaPerformAction('actSkipCardIsBought');
    }

    public goToSellCard() {
        this.bgaPerformAction('actGoToSellCard');
    }

    public opportunistSkip() {
        this.bgaPerformAction('actOpportunistSkip');
    }

    public changeActivePlayerDieSkip() {
        this.bgaPerformAction('actChangeActivePlayerDieSkip');
    }

    public skipChangeMimickedCard() {
        this.bgaPerformAction('actSkipChangeMimickedCard');
    }

    public skipChangeMimickedCardWickednessTile() {
        this.bgaPerformAction('actSkipChangeMimickedCardWickednessTile');
    }

    public endStealCostume() {
        this.bgaPerformAction('actEndStealCostume');
    }

    public onEndTurn() {
        this.bgaPerformAction('actEndTurn');
    }

    public throwCamouflageDice() {
        this.bgaPerformAction('actThrowCamouflageDice');
    }

    public useWings() {
        this.bgaPerformAction('actUseWings');
    }

    public useInvincibleEvolution(evolutionType: number) {
        this.bgaPerformAction('actUseInvincibleEvolution', {
            evolutionType
        });
    }

    public useCandyEvolution() {
        this.bgaPerformAction('actUseCandyEvolution');
    }

    public skipWings() {
        this.bgaPerformAction('actSkipWings');
    }

    public useRobot(energy: number) {
        this.bgaPerformAction('actUseRobot', {
            energy
        });
    }

    public useElectricArmor(energy: number) {
        this.bgaPerformAction('actUseElectricArmor', {
            energy
        });
    }

    public useSuperJump(energy: number) {
        this.bgaPerformAction('actUseSuperJump', {
            energy
        });
    }

    public useRapidHealingSync(cultistCount: number, rapidHealingCount: number) {
        this.bgaPerformAction('actUseRapidHealingSync', {
            cultistCount, 
            rapidHealingCount
        });
    }

    public setLeaveTokyoUnder(under: number) {
        this.bgaPerformAction('actSetLeaveTokyoUnder', {
            under
        }, { lock: false, checkAction: false });
    }

    public setStayTokyoOver(over: number) {
        this.bgaPerformAction('actSetStayTokyoOver', {
            over
        }, { lock: false, checkAction: false });
    }

    public setAskPlayEvolution(value: number) {
        this.bgaPerformAction('actSetAskPlayEvolution', {
            value
        }, { lock: false, checkAction: false });
    }
    
    public exchangeCard(id: number) {
        this.bgaPerformAction('actExchangeCard', {
            id
        });
    }

    public skipExchangeCard() {
        this.bgaPerformAction('actSkipExchangeCard');
    }
    
    public stayInHibernation() {
        this.bgaPerformAction('actStayInHibernation');
    }
    
    public leaveHibernation() {
        this.bgaPerformAction('actLeaveHibernation');
    }

    public playEvolution(id: number) {
        this.bgaPerformAction('actPlayEvolution', {
            id
        }, { checkAction: false, lock: false });
    }

    public giveGiftEvolution(id: number, toPlayerId: number) {
        this.bgaPerformAction('actGiveGiftEvolution', {
            id,
            toPlayerId,
        });
    }
    
    public putEnergyOnBambooSupply() {
        this.bgaPerformAction('actPutEnergyOnBambooSupply');
    }
    
    public takeEnergyOnBambooSupply() {
        this.bgaPerformAction('actTakeEnergyOnBambooSupply');
    }
    
    public gazeOfTheSphinxDrawEvolution() {
        this.bgaPerformAction('actGazeOfTheSphinxDrawEvolution');
    }
    
    public gazeOfTheSphinxGainEnergy() {
        this.bgaPerformAction('actGazeOfTheSphinxGainEnergy');
    }
    
    public gazeOfTheSphinxDiscardEvolution(id) {
        this.bgaPerformAction('actGazeOfTheSphinxDiscardEvolution', {
            id
        });
    }
    
    public gazeOfTheSphinxLoseEnergy() {
        this.bgaPerformAction('actGazeOfTheSphinxLoseEnergy');
    }
    
    public useChestThumping(id: number) {
        this.bgaPerformAction('actUseChestThumping', {
            id
        });
    }
    
    public skipChestThumping() {
        this.bgaPerformAction('actSkipChestThumping');
    }
    
    public chooseFreezeRayDieFace(symbol: number) {
        this.bgaPerformAction('actChooseFreezeRayDieFace', {
            symbol
        });
    }
    
    public useMiraculousCatch() {
        this.bgaPerformAction('actUseMiraculousCatch');
    }
    
    public buyCardMiraculousCatch(useSuperiorAlienTechnology: boolean = false) {
        this.bgaPerformAction('actBuyCardMiraculousCatch', {
            useSuperiorAlienTechnology,
        });
    }
    
    public skipMiraculousCatch() {
        this.bgaPerformAction('actSkipMiraculousCatch');
    }
    
    public playCardDeepDive(id: number) {
        this.bgaPerformAction('actPlayCardDeepDive', {
            id
        });
    }
    
    public useExoticArms() {
        this.bgaPerformAction('actUseExoticArms');
    }
    
    public skipExoticArms() {
        this.bgaPerformAction('actSkipExoticArms');
    }
    
    public skipBeforeResolveDice() {
        this.bgaPerformAction('actSkipBeforeResolveDice');
    }
    
    public giveTarget() {
        this.bgaPerformAction('actGiveTarget');
    }
    
    public skipGiveTarget() {
        this.bgaPerformAction('actSkipGiveTarget');
    }
    
    public useLightningArmor() {
        this.bgaPerformAction('actUseLightningArmor');
    }
    
    public skipLightningArmor() {
        this.bgaPerformAction('actSkipLightningArmor');
    }
    
    public answerEnergySword(use: boolean) {
        this.bgaPerformAction('actAnswerEnergySword', { use });
    }
    
    public answerSunkenTemple(use: boolean) {
        this.bgaPerformAction('actAnswerSunkenTemple', { use });
    }
    
    public answerElectricCarrot(choice: 4 | 5) {
        this.bgaPerformAction('actAnswerElectricCarrot', { choice });
    }
    
    public reserveCard(id: number) {
        this.bgaPerformAction('actReserveCard', { id });
    }
    
    public useFelineMotor() {
        this.bgaPerformAction('actUseFelineMotor');
    }
    
    public throwDieSuperiorAlienTechnology() {
        this.bgaPerformAction('actThrowDieSuperiorAlienTechnology');
    }
    
    public freezeRayChooseOpponent(playerId: number) {
        this.bgaPerformAction('actFreezeRayChooseOpponent', { playerId });
    }
    
    public loseHearts() {
        this.bgaPerformAction('actLoseHearts');
    }

    public setFont(prefValue: number): void {
        this.playerTables.forEach(playerTable => playerTable.setFont(prefValue));
    }

    private startActionTimer(buttonId: string, time: number) {
        if (this.getGameUserPreference(202) === 2) {
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
            ['setInitialCards', undefined],
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
            ['renewCards', undefined],
            ['buyCard', ANIMATION_MS],
            ['reserveCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['useLightningArmor', ANIMATION_MS],
            ['changeDie', ANIMATION_MS],
            ['changeDice', ANIMATION_MS],
            ['rethrow3changeDie', ANIMATION_MS],
            ['changeCurseCard', undefined],
            ['takeWickednessTile', ANIMATION_MS],
            ['changeGoldenScarabOwner', ANIMATION_MS],
            ['discardedDie', ANIMATION_MS],
            ['exchangeCard', ANIMATION_MS],
            ['playEvolution', ANIMATION_MS],
            ['superiorAlienTechnologyRolledDie', ANIMATION_MS],
            ['superiorAlienTechnologyLog', ANIMATION_MS],
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
            ['updateAskPlayEvolution', 1],
            ['kotPlayerEliminated', 1],
            ['setPlayerBerserk', 1],
            ['cultist', 1],
            ['removeWickednessTiles', 1],
            ['addEvolutionCardInHand', 1],
            ['addSuperiorAlienTechnologyToken', 1],
            ['giveTarget', 1],
            ['updateCancelDamage', 1],
            ['ownedEvolutions', 1],
            ['resurrect', 1],
            ['mindbugPlayer', 1],
            ['setPlayerCounter', 1],
            ['log500', 500],
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, (notifDetails: Notif<any>) => {
                log(`notif_${notif[0]}`, notifDetails.args);

                const promise = this[`notif_${notif[0]}`](notifDetails.args);

                // tell the UI notification ends, if the function returned a promise
                promise?.then(() => (this as any).notifqueue.onSynchronousNotificationEnd());
            });
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });

        if (isDebug) {
            notifs.forEach((notif) => {
                if (!this[`notif_${notif[0]}`]) {
                    console.warn(`notif_${notif[0]} function is not declared, but listed in setupNotifications`);
                }
            });

            Object.getOwnPropertyNames(KingOfTokyo.prototype).filter(item => item.startsWith('notif_')).map(item => item.slice(6)).forEach(item => {
                if (!notifs.some(notif => notif[0] == item)) {
                    console.warn(`notif_${item} function is declared, but not listed in setupNotifications`);
                }
            });
        }
    }

    notif_log500() {
        // nothing, it's just for the delay
    }

    notif_pickMonster(args: NotifPickMonsterArgs) {
       const monsterDiv = document.getElementById(`pick-monster-figure-${args.monster}`); 
       const destinationId = `player-board-monster-figure-${args.playerId}`;
       const animation = this.slideToObject(monsterDiv, destinationId);

        dojo.connect(animation as any, 'onEnd', dojo.hitch(this, () => {
            this.fadeOutAndDestroy(monsterDiv);
            dojo.removeClass(destinationId, 'monster0');
            dojo.addClass(destinationId, `monster${args.monster}`);
        }));
        animation.play();

        this.getPlayerTable(args.playerId).setMonster(args.monster);
    }

    notif_evolutionPickedForDeck(args: any) {
        this.inDeckEvolutionsStock.addCard(args.card, { fromStock: this.choseEvolutionInStock });
    }

    notif_setInitialCards(args: NotifSetInitialCardsArgs) {
        return this.tableCenter.setVisibleCards(args.cards, false, args.deckCardsCount, args.topDeckCard);
    }

    notif_resolveNumberDice(args: NotifResolveNumberDiceArgs) {
        this.setPoints(args.playerId, args.points, ANIMATION_MS);
        this.kotAnimationManager.resolveNumberDice(args);
        this.diceManager.resolveNumberDice(args);
    }

    notif_resolveHealthDice(args: NotifResolveHealthDiceArgs) {
        this.kotAnimationManager.resolveHealthDice(args.playerId, args.deltaHealth);
        this.diceManager.resolveHealthDice(args.deltaHealth);
    }
    notif_resolveHealthDiceInTokyo(args: NotifResolveHealthDiceInTokyoArgs) {
        this.diceManager.resolveHealthDiceInTokyo();
    }
    notif_resolveHealingRay(args: NotifResolveHealingRayArgs) {
        this.kotAnimationManager.resolveHealthDice(args.healedPlayerId, args.healNumber);
        this.diceManager.resolveHealthDice(args.healNumber);
    }

    notif_resolveEnergyDice(args: NotifResolveEnergyDiceArgs) {
        this.kotAnimationManager.resolveEnergyDice(args);
        this.diceManager.resolveEnergyDice();
    }

    notif_resolveSmashDice(args: NotifResolveSmashDiceArgs) {
        this.kotAnimationManager.resolveSmashDice(args);
        this.diceManager.resolveSmashDice();

        if (args.smashedPlayersIds.length > 0) {
            for (let delayIndex = 0; delayIndex < args.number; delayIndex++) {
                setTimeout(() => playSound('kot-punch'), ANIMATION_MS -(PUNCH_SOUND_DURATION * delayIndex - 1));
            }
        }
    }

    notif_playerEliminated(args: NotifPlayerEliminatedArgs) {
        const playerId = Number(args.who_quits);
        this.setPoints(playerId, 0);
        this.eliminatePlayer(playerId);
    }

    notif_kotPlayerEliminated(args: NotifPlayerEliminatedArgs) {
        this.notif_playerEliminated(args);
    }

    notif_leaveTokyo(args: NotifPlayerLeavesTokyoArgs) {
        this.getPlayerTable(args.playerId).leaveTokyo();
        dojo.removeClass(`overall_player_board_${args.playerId}`, 'intokyo');
        dojo.removeClass(`monster-board-wrapper-${args.playerId}`, 'intokyo');
        if (args.playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }

        if (this.smashedPlayersStillInTokyo) {
            this.smashedPlayersStillInTokyo = this.smashedPlayersStillInTokyo.filter((playerId) => playerId != args.playerId);
        }

        const useChestThumpingButton = document.getElementById(`useChestThumping_button${args.playerId}`);
        useChestThumpingButton?.parentElement.removeChild(useChestThumpingButton);
    }

    notif_playerEntersTokyo(args: NotifPlayerEntersTokyoArgs) {
        this.getPlayerTable(args.playerId).enterTokyo(args.location);
        dojo.addClass(`overall_player_board_${args.playerId}`, 'intokyo');
        dojo.addClass(`monster-board-wrapper-${args.playerId}`, 'intokyo');
        if (args.playerId == this.getPlayerId()) {
            this.addAutoLeaveUnderButton();
        }  
    }

    notif_buyCard(args: NotifBuyCardArgs) {
        const card = args.card;
        const playerId = args.playerId;
        const playerTable = this.getPlayerTable(playerId);

        if (args.energy !== undefined) {
            this.setEnergy(playerId, args.energy);
        }

        if (args.discardCard) { // initial card
            playerTable.cards.addCard(card, { fromStock: this.tableCenter.getVisibleCards() });
        } else if (args.newCard) {
            const newCard = args.newCard;
            playerTable.cards.addCard(card, { fromStock: this.tableCenter.getVisibleCards() }).then(() => {
                this.tableCenter.getVisibleCards().addCard(newCard, { fromElement: document.getElementById('deck'), originalSide: 'back', rotationDelta: 90 });
            });
        } else if (args.from > 0) {
            const fromStock = args.from == playerId ? playerTable.reservedCards : this.getPlayerTable(args.from).cards;
            playerTable.cards.addCard(card, { fromStock });
        } else { // from Made in a lab Pick
            const settings: CardAnimation<Card> = this.tableCenter.getPickCard() ? // active player
                { fromStock: this.tableCenter.getPickCard() } :
                { fromElement: document.getElementById('deck'), originalSide: 'back', rotationDelta: 90 };
            playerTable.cards.addCard(card, settings);
        }
        //this.cardsManager.settings.setupFrontDiv(card, this.cardsManager.getCardElement(card).getElementsByClassName('front')[0]);
        if (card.tokens) {
            this.cardsManager.placeTokensOnCard(card, playerId);
        }

        this.tableCenter.setTopDeckCard(args.topDeckCard, args.deckCardsCount);

        this.tableManager.tableHeightChange(); // adapt to new card
    }

    notif_reserveCard(args: NotifBuyCardArgs) {
        const card = args.card;

        const newCard = args.newCard;
        this.getPlayerTable(args.playerId).reservedCards.addCard(card, { fromStock: this.tableCenter.getVisibleCards() }); // TODOPUBG add under evolution
        this.tableCenter.getVisibleCards().addCard(newCard, { fromElement: document.getElementById('deck'), originalSide: 'back', rotationDelta: 90 });
        

        this.tableCenter.setTopDeckCard(args.topDeckCard, args.deckCardsCount);

        this.tableManager.tableHeightChange(); // adapt to new card
    }

    notif_removeCards(args: NotifRemoveCardsArgs) {
        if (args.delay) {
            args.delay = false;
            setTimeout(() => this.notif_removeCards(args), ANIMATION_MS);
        } else {
            this.getPlayerTable(args.playerId).removeCards(args.cards);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    }

    notif_removeEvolutions(args: NotifRemoveEvolutionsArgs) {
        if (args.delay) {
            setTimeout(() => this.notif_removeEvolutions({
                ...args,
                delay: 0,
            } as NotifRemoveEvolutionsArgs), args.delay);
        } else {
            this.getPlayerTable(args.playerId).removeEvolutions(args.cards);
            this.handCounters[args.playerId].incValue(-args.cards.filter(card => card.location === 'hand').length);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    }

    notif_setMimicToken(args: NotifSetCardTokensArgs) {
        this.setMimicToken(args.type, args.card as Card);
    }

    notif_removeMimicToken(args: NotifSetCardTokensArgs) {
        this.removeMimicToken(args.type, args.card as Card);
    }

    notif_removeMimicEvolutionToken(args: NotifSetCardTokensArgs) {
        this.removeMimicEvolutionToken(args.card as EvolutionCard);
    }

    notif_setMimicEvolutionToken(args: NotifSetCardTokensArgs) {
        this.setMimicEvolutionToken(args.card as EvolutionCard);
    }

    notif_renewCards(args: NotifRenewCardsArgs) {
        this.setEnergy(args.playerId, args.energy);

        return this.tableCenter.renewCards(args.cards, args.topDeckCard, args.deckCardsCount);
    }

    notif_points(args: NotifPointsArgs) {
        this.setPoints(args.playerId, args.points);
    }

    notif_health(args: NotifHealthArgs) {
        this.setHealth(args.playerId, args.health);

        /*const rapidHealingSyncButton = document.getElementById('rapidHealingSync_button');
        if (rapidHealingSyncButton && args.playerId === this.getPlayerId()) {
            this.rapidHealingSyncHearts = Math.max(0, this.rapidHealingSyncHearts - args.delta_health);
            rapidHealingSyncButton.innerHTML = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (${2*this.rapidHealingSyncHearts}[Energy])`), { 'card_name': this.cards.getCardName(37, 'text-only'), 'hearts': this.rapidHealingSyncHearts });
        }*/
    }

    notif_maxHealth(args: NotifMaxHealthArgs) {
        this.setMaxHealth(args.playerId, args.maxHealth);
        this.setHealth(args.playerId, args.health);
    }

    notif_energy(args: NotifEnergyArgs) {
        this.setEnergy(args.playerId, args.energy);
    }

    notif_wickedness(args: NotifWickednessArgs) {
        this.setWickedness(args.playerId, args.wickedness);
    }

    notif_shrinkRayToken(args: NotifSetPlayerTokensArgs) {
        this.setShrinkRayTokens(args.playerId, args.tokens);
    }

    notif_poisonToken(args: NotifSetPlayerTokensArgs) {
        this.setPoisonTokens(args.playerId, args.tokens);
    }

    notif_removeShrinkRayToken(args: NotifSetPlayerTokensArgs) {
        this.kotAnimationManager.resolveHealthDice(args.playerId, args.deltaTokens, 'shrink-ray');
        this.diceManager.resolveHealthDice(args.deltaTokens);
        setTimeout(() => this.notif_shrinkRayToken(args), ANIMATION_MS);
    }

    notif_removePoisonToken(args: NotifSetPlayerTokensArgs) {
        this.kotAnimationManager.resolveHealthDice(args.playerId, args.deltaTokens, 'poison');
        this.diceManager.resolveHealthDice(args.deltaTokens);
        setTimeout(() => this.notif_poisonToken(args), ANIMATION_MS);
    }

    notif_setCardTokens(args: NotifSetCardTokensArgs) {
        this.cardsManager.placeTokensOnCard(args.card as Card, args.playerId);
    }

    notif_setEvolutionTokens(args: NotifSetEvolutionTokensArgs) {
        this.evolutionCardsManager.placeTokensOnCard(args.card, args.playerId);
    }

    notif_setTileTokens(args: NotifSetWickednessTileTokensArgs) {
        this.wickednessTilesManager.placeTokensOnTile(args.card, args.playerId);
    }

    notif_toggleRapidHealing(args: NotifToggleRapidHealingArgs) {
        if (args.active) {
            this.addRapidHealingButton(args.playerEnergy, args.isMaxHealth);
        } else {
            this.removeRapidHealingButton();
        }
    }

    notif_toggleMothershipSupport(args: NotifToggleRapidHealingArgs) {
        if (args.active) {
            this.addMothershipSupportButton(args.playerEnergy, args.isMaxHealth);
        } else {
            this.removeMothershipSupportButton();
        }
    }

    notif_toggleMothershipSupportUsed(args: NotifToggleMothershipSupportUsedArgs) {
        this.gamedatas.players[args.playerId].mothershipSupportUsed = args.used;
        this.checkMothershipSupportButtonState();
    }

    notif_useCamouflage(args: NotifUpdateCancelDamageArgs) {
        this.notif_updateCancelDamage(args);
        this.diceManager.showCamouflageRoll(args.diceValues);
    }

    notif_updateCancelDamage(args: NotifUpdateCancelDamageArgs) {
        if (args.cancelDamageArgs) { 
            this.gamedatas.gamestate.args = args.cancelDamageArgs;
            this.updatePageTitle();
            this.onEnteringCancelDamage(args.cancelDamageArgs, this.isCurrentPlayerActive());
        }
    }

    notif_useLightningArmor(args: NotifUpdateCancelDamageArgs) {
        this.diceManager.showCamouflageRoll(args.diceValues);
    }

    notif_changeDie(args: NotifChangeDieArgs) {
        if (args.psychicProbeRollDieArgs) {
            this.onEnteringPsychicProbeRollDie(args.psychicProbeRollDieArgs);
        } else {
            this.diceManager.changeDie(args.dieId, args.canHealWithDice, args.toValue, args.roll);
        }
    }

    notif_rethrow3changeDie(args: NotifChangeDieArgs) {
        this.diceManager.changeDie(args.dieId, args.canHealWithDice, args.toValue, args.roll);
    }

    notif_changeDice(args: NotifChangeDiceArgs) {
        Object.keys(args.dieIdsToValues).forEach(key => 
            this.diceManager.changeDie(Number(key), args.canHealWithDice, args.dieIdsToValues[key], false)
        );
    }

    notif_resolvePlayerDice() {
        this.diceManager.lockAll();
    }

    notif_updateLeaveTokyoUnder(args: NotifUpdateLeaveTokyoUnderArgs) {                    
        dojo.query('.autoLeaveButton').removeClass('bgabutton_blue');
        dojo.query('.autoLeaveButton').addClass('bgabutton_gray');
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(`${popinId}_set${args.under}`)) {
            dojo.removeClass(`${popinId}_set${args.under}`, 'bgabutton_gray');
            dojo.addClass(`${popinId}_set${args.under}`, 'bgabutton_blue');
        }
        for (let i = 1; i<=15; i++) {
            if (document.getElementById(`${popinId}_setStay${i}`)) {
                dojo.toggleClass(`${popinId}_setStay${i}`, 'disabled', args.under > 0 && i <= args.under);
            }
        }
    }

    notif_updateStayTokyoOver(args: NotifUpdateStayTokyoOverArgs) {                    
        dojo.query('.autoStayButton').removeClass('bgabutton_blue');
        dojo.query('.autoStayButton').addClass('bgabutton_gray');
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(`${popinId}_setStay${args.over}`)) {
            dojo.removeClass(`${popinId}_setStay${args.over}`, 'bgabutton_gray');
            dojo.addClass(`${popinId}_setStay${args.over}`, 'bgabutton_blue');
        }
    }

    notif_updateAskPlayEvolution(args: NotifUpdateAskPlayEvolutionArgs) {  
        const input = document.querySelector(`input[name="autoSkipPlayEvolution"][value="${args.value}"]`) as HTMLInputElement;
        if (input) {
            input.checked = true;  
        }
    }

    notif_changeTokyoTowerOwner(args: NotifChangeTokyoTowerOwnerArgs) {   
        const playerId = args.playerId;
        const previousOwner = this.towerLevelsOwners[args.level];
        this.towerLevelsOwners[args.level] = playerId;

        const newLevelTower = playerId == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(playerId).getTokyoTower();

        transitionToObjectAndAttach(this, document.getElementById(`tokyo-tower-level${args.level}`), `${newLevelTower.divId}-level${args.level}`, this.getZoom());

        if (previousOwner != 0) {
            document.getElementById(`tokyo-tower-icon-${previousOwner}-level-${args.level}`).dataset.owned = 'false';
        }
        if (playerId != 0) {
            document.getElementById(`tokyo-tower-icon-${playerId}-level-${args.level}`).dataset.owned = 'true';
        }
    }

    notif_setPlayerBerserk(args: NotifSetPlayerBerserkArgs) { 
        this.getPlayerTable(args.playerId).setBerserk(args.berserk);
        dojo.toggleClass(`player-panel-berserk-${args.playerId}`, 'active', args.berserk);
    }

    notif_changeForm(args: NotifChangeFormArgs) { 
        this.getPlayerTable(args.playerId).changeForm(args.card);
        this.setEnergy(args.playerId, args.energy);
    }

    notif_cultist(args: NotifCultistArgs) {
        this.setCultists(args.playerId, args.cultists, args.isMaxHealth);
    }

    notif_changeCurseCard(args: NotifChangeCurseCardArgs) {
        return this.tableCenter.changeCurseCard(args.card, args.hiddenCurseCardCount, args.topCurseDeckCard);
    }

    notif_takeWickednessTile(args: NotifTakeWickednessTileArgs) {
        const tile = args.tile;
        this.getPlayerTable(args.playerId).wickednessTiles.addCard(tile, {
            fromStock: this.tableCenter.wickednessDecks.getStock(tile)
        });
        this.tableCenter.removeWickednessTileFromPile(args.level, tile);

        this.tableManager.tableHeightChange(); // adapt to new card
    }

    notif_removeWickednessTiles(args: NotifRemoveWickednessTilesArgs) {
        this.getPlayerTable(args.playerId).removeWickednessTiles(args.tiles);
        this.tableManager.tableHeightChange(); // adapt after removed cards
    }

    notif_changeGoldenScarabOwner(args: NotifChangeGoldenScarabOwnerArgs) {
        this.getPlayerTable(args.playerId).takeGoldenScarab();
        this.tableManager.tableHeightChange(); // adapt after moved card
    }

    notif_discardedDie(args: NotifDiscardedDieArgs) {
        this.diceManager.discardDie(args.die);
    }

    notif_exchangeCard(args: NotifExchangeCardArgs) {
        const previousOwnerCards = this.getPlayerTable(args.previousOwner).cards;
        const playerCards = this.getPlayerTable(args.playerId).cards;
        previousOwnerCards.addCard(args.unstableDnaCard, { fromStock: playerCards });
        playerCards.addCard(args.exchangedCard, { fromStock: playerCards });
    }
    
    notif_addEvolutionCardInHand(args: NotifAddEvolutionCardInHandArgs) {
        const playerId = args.playerId;
        const card = args.card;
        const isCurrentPlayer = this.getPlayerId() === playerId;
        const playerTable = this.getPlayerTable(playerId);
        if (isCurrentPlayer) {
            if (card?.type) {
                playerTable.hiddenEvolutionCards.addCard(card);
            }
        } else if (card?.id) {
            playerTable.hiddenEvolutionCards.addCard(card);
        }
        if (!card || !card.type) {
            this.handCounters[playerId].incValue(1);
        }
        playerTable?.checkHandEmpty();

        this.tableManager.tableHeightChange(); // adapt to new card
    }
    
    notif_playEvolution(args: NotifPlayEvolutionArgs) {
        this.handCounters[args.playerId].incValue(-1);
        let fromStock: CardStock<EvolutionCard> | null = null;
        if (args.fromPlayerId) {
            fromStock = this.getPlayerTable(args.fromPlayerId).visibleEvolutionCards;
        }
        this.getPlayerTable(args.playerId).playEvolution(args.card, fromStock);
        if (args.fromPlayerId) {
            this.getPlayerTable(args.fromPlayerId).visibleEvolutionCards.removeCard(args.card);
        }

        this.tableManager.tableHeightChange(); // adapt to new card
    }
    
    notif_addSuperiorAlienTechnologyToken(args: NotifAddSuperiorAlienTechnologyTokenArgs) {
        this.cardsManager.placeSuperiorAlienTechnologyTokenOnCard(args.card);
    }
    
    notif_giveTarget(args: NotifGiveTargetArgs) {
        if (args.previousOwner) {
            this.getPlayerTable(args.previousOwner).removeTarget();
            this.setPlayerTokens(args.previousOwner, 0, 'target');
        }
        this.getPlayerTable(args.playerId).giveTarget();
        this.setPlayerTokens(args.playerId, 1, 'target');
    }
    
    notif_ownedEvolutions(args: NotifOwnedEvolutionsArgs) {
        this.gamedatas.players[args.playerId].ownedEvolutions = args.evolutions;
    }

    private setTitleBarSuperiorAlienTechnologyCard(card: Card, parent: string = `maintitlebar_content`) {
        dojo.place(`<div id="title-bar-stock" class="card-in-title-wrapper"></div>`, parent);
        this.titleBarStock = new LineStock<Card>(this.cardsManager, document.getElementById('title-bar-stock'));
        this.titleBarStock.addCard({...card, id: 9999 + card.id });
        this.titleBarStock.setSelectionMode('single');
        this.titleBarStock.onCardClick = () => this.throwDieSuperiorAlienTechnology();
    }
    
    notif_superiorAlienTechnologyRolledDie(args: NotifSuperiorAlienTechnologyRolledDieArgs) {
        this.setTitleBarSuperiorAlienTechnologyCard(args.card, 'gameaction_status_wrap');
        this.setDiceSelectorVisibility(true);

        this.diceManager.showCamouflageRoll([{
            id: 0,
            value: args.dieValue,
            extra: false,
            locked: false,
            rolled: true,
            type: 0,
            canReroll: true,
        }]);
    }
    
    notif_superiorAlienTechnologyLog(args: NotifSuperiorAlienTechnologyRolledDieArgs) {
        //this.setTitleBarSuperiorAlienTechnologyCard(args.card, 'gameaction_status_wrap');

        if (document.getElementById('dice0')) {
            const message = args.dieValue == 6 ? 
                _('<strong>${card_name}</strong> card removed!') : 
                _('<strong>${card_name}</strong> card kept!');
            (this as any).doShowBubble('dice0', dojo.string.substitute(message, {
                'card_name': this.cardsManager.getCardName(args.card.type, 'text-only')
            }), 'superiorAlienTechnologyBubble');
        }
    }

    notif_resurrect(args: NotifResurrectArgs) {
        if (args.zombified) {
            this.getPlayerTable(args.playerId).zombify();
        }
    }

    notif_mindbugPlayer(args: NotifMindbugPlayerArgs) {
        if (args.mindbuggedPlayerId) {
            // start of mindbug
            document.getElementById('rolled-dice-and-rapid-actions').insertAdjacentHTML('afterend', `
                <div id="mindbug-notice">
                    ${
                        /*_TODOMB*/('${player_name} mindbugs the turn of ${player_name2}')
                            .replace('${player_name}', this.getFormattedPlayerName(args.activePlayerId))
                            .replace('${player_name2}', this.getFormattedPlayerName(args.mindbuggedPlayerId))
                    }
                </div>
            `);

            document.getElementById(`player-table-${args.mindbuggedPlayerId}`).classList.add('mindbugged');
            
        } else {
            // end of mindbug
            document.querySelector('.player-table.mindbugged')?.classList.remove('mindbugged');
            document.getElementById('mindbug-notice')?.remove();
        }
    }

    notif_setPlayerCounter(args) {
        const { name, playerId, value } = args;
        if (name === 'mindbugTokens') {
            this.setPlayerTokens(playerId, value, 'mindbug');
        }
    }
    
    private setPoints(playerId: number, points: number, delay: number = 0) {
        this.scoreCtrl[playerId]?.toValue(points);
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

    public getPlayerHealth(playerId: number): number {
        return this.healthCounters[playerId].getValue();
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
        
        this.updateEnableAtEnergy(playerId, energy);
    }

    private updateEnableAtEnergy(playerId: number, energy: number | null = null) {
        if (energy === null) {
            energy = this.getPlayerEnergy(playerId);
        }
        (Array.from(document.querySelectorAll(`[data-enable-at-energy]`)) as HTMLElement[]).forEach(button => {
            const enableAtEnergy = Number(button.dataset.enableAtEnergy);
            button.classList.toggle('disabled', energy < enableAtEnergy);
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
            this.fadeOutAndDestroy(`player-board-monster-figure-${playerId}`);
        }
        dojo.removeClass(`overall_player_board_${playerId}`, 'intokyo');
        dojo.removeClass(`monster-board-wrapper-${playerId}`, 'intokyo');
        if (playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }
        
        this.setShrinkRayTokens(playerId, 0);
        this.setPlayerTokens(playerId, 0, 'mindbug');
        this.setPoisonTokens(playerId, 0);
        if (this.isCthulhuExpansion()) {
            this.setCultists(playerId, 0, false);
        }
    }

    private getLogCardName(logType: number) {
        if (logType >= 3000) {
            return this.evolutionCardsManager.getCardName(logType - 3000, 'text-only');
        } else if (logType >= 2000) {
            return this.wickednessTilesManager.getCardName(logType - 2000);
        } else if (logType >= 1000) {
            return this.curseCardsManager.getCardName(logType - 1000);
        } else {
            return this.cardsManager.getCardName(logType, 'text-only');
        }
    }

    private getLogCardTooltip(logType: number) {
        if (logType >= 3000) {
            return this.evolutionCardsManager.getTooltip(logType - 3000);
        } else if (logType >= 2000) {
            return this.wickednessTilesManager.getTooltip(logType - 2000);
        } else if (logType >= 1000) {
            return this.curseCardsManager.getTooltip(logType - 1000);
        } else {
            return this.cardsManager.getTooltip(logType);
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

                                setTimeout(() => this.addTooltipHtml(`card-log-${cardLogId}`, this.getLogCardTooltip(cardType)), 500);

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

                if (args.player_name && typeof args.player_name[0] === 'string' && args.player_name.indexOf('<') === -1) {
                    const player = Object.values(this.gamedatas.players).find(player => player.name == args.player_name);
                    args.player_name = `<span style="font-weight:bold;color:#${player.color};">${args.player_name}</span>`;
                }

                if (args.symbolsToGive && typeof args.symbolsToGive === 'object') {
                    const symbolsStr: string[] = args.symbolsToGive.map((symbol: number) => SYMBOL_AS_STRING_PADDED[symbol]);
                    args.symbolsToGive = formatTextIcons(_('${symbol1} or ${symbol2}')
                      .replace('${symbol1}', symbolsStr.slice(0, symbolsStr.length - 1).join(', '))
                      .replace('${symbol2}', symbolsStr[symbolsStr.length - 1])
                    );
                }

                log = formatTextIcons(_(log));
            }
        } catch (e) {
            console.error(log,args,"Exception thrown", e.stack);
        }
        return (this as any).inherited(arguments);
    }
}
