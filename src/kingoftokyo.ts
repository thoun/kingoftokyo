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

class KingOfTokyo implements KingOfTokyoGame {
    private gamedatas: KingOfTokyoGamedatas;
    private healthCounters: Counter[] = [];
    private energyCounters: Counter[] = [];
    private wickednessCounters: Counter[] = [];
    private tokyoTowerCounters: Counter[] = [];
    private cultistCounters: Counter[] = [];
    private diceManager: DiceManager;
    private animationManager: AnimationManager;
    private playerTables: PlayerTable[] = [];
    private tableManager: TableManager;
    private preferencesManager: PreferencesManager;
    public cards: Cards;
    public curseCards: CurseCards;
    public wickednessTiles: WickednessTiles;
    //private rapidHealingSyncHearts: number;
    public towerLevelsOwners = [];
    private tableCenter: TableCenter;
        
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
        [1,2,3,4,5,6,7,8,9,10].filter(i => !players.some(player => Number(player.monster) === i)).forEach(i => {
            (this as any).dontPreloadImage(`monster-board-${i}.png`);
            (this as any).dontPreloadImage(`monster-figure-${i}.png`);
        });
        (this as any).dontPreloadImage(`tokyo-2pvariant.jpg`);
        (this as any).dontPreloadImage(`background-halloween.jpg`);
        if (!gamedatas.halloweenExpansion) {
            (this as any).dontPreloadImage(`costume-cards.jpg`);
            (this as any).dontPreloadImage(`orange_dice.png`);
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
        this.SHINK_RAY_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Shrink ray tokens (given by ${card_name}). Reduce dice count by one per token. Use you [diceHeart] to remove them.")), {'card_name': this.cards.getCardName(40, 'text-only')});
        this.POISON_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Poison tokens (given by ${card_name}). Make you lose one [heart] per token at the end of your turn. Use you [diceHeart] to remove them.")), {'card_name': this.cards.getCardName(35, 'text-only')});
    
        this.createPlayerPanels(gamedatas); 
        this.diceManager = new DiceManager(this);
        this.animationManager = new AnimationManager(this, this.diceManager);
        this.tableCenter = new TableCenter(this, gamedatas.visibleCards, gamedatas.topDeckCardBackType, gamedatas.wickednessTiles, gamedatas.tokyoTowerLevels, gamedatas.curseCard);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(() => this.playerTables.forEach(playerTable => playerTable.initPlacement()), 200);
        this.setMimicToken(gamedatas.mimickedCard);

        const playerId = this.getPlayerId();
        const currentPlayer = players.find(player => Number(player.id) === playerId);

        if (currentPlayer?.rapidHealing) {
            this.addRapidHealingButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
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

        /* TODOKK if (gamedatas.kingkongExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Tokyo Tower")}</h3>
            <p>${_("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1]")}</p>
            <p>${_("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative).")}</p>
            <p><strong>${_("Claiming the top level automatically wins the game.")}</strong></p>
            `);
            (this as any).addTooltipHtmlToClass('tokyo-tower-tooltip', tooltip);
        }

        /* TODOCY if (gamedatas.cybertoothExpansion) {
            const tooltip = formatTextIcons(`
            <h3>${_("Berserk mode")}</h3>
            <p>${_("When you roll 4 or more [diceSmash], you are in Berserk mode!")}</p>
            <p>${_("You play with the additional Berserk die, until you heal yourself.")}</p>`);
            (this as any).addTooltipHtmlToClass('berserk-tooltip', tooltip); // TODOCY check if healed by Healing Ray       
        }*/

        if (gamedatas.cthulhuExpansion) {
            this.CULTIST_TOOLTIP = formatTextIcons(`
            <h3>${_("Cultists")}</h3>
            <p>${_("After resolving your dice, if you rolled four identical faces, take a Cultist tile")}</p>
            <p>${_("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll.")}</p>`);
            (this as any).addTooltipHtmlToClass('cultist-tooltip', this.CULTIST_TOOLTIP);
        }

        log( "Ending game setup" );
    }

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    public onEnteringState(stateName: string, args: any) {
        log('Entering state: ' + stateName, args.args);
        this.showActivePlayer(Number(args.active_player));

        if (stateName !== 'pickMonster' && stateName !== 'pickMonsterNextPlayer') {
            this.replaceMonsterChoiceByTable();
        }

        switch (stateName) {
            case 'pickMonster':
                dojo.addClass('kot-table', 'pickMonster');
                this.onEnteringPickMonster(args.args);
                break;
            case 'chooseInitialCard':
                this.onEnteringChooseInitialCard(args.args);
                break;
            case 'startGame':
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
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
            case 'resolveDice': 
                this.setDiceSelectorVisibility(true);
                this.diceManager.hideLock();
                break;
            case 'takeWickednessTile':
                this.onEnteringTakeWickednessTile(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'resolveHeartDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveHeartDice(args.args, (this as any).isCurrentPlayerActive());
                break;

            case 'stealCostumeCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringStealCostumeCard(args.args, (this as any).isCurrentPlayerActive());
                break;
            
            case 'buyCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringBuyCard(args.args, (this as any).isCurrentPlayerActive());
                break;
            case 'sellCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringSellCard(args.args);
                break;

            case 'endTurn':
                this.setDiceSelectorVisibility(false);
                this.onEnteringEndTurn();
                break;
        }
    }

    private showActivePlayer(playerId: number) {
        this.playerTables.forEach(playerTable => playerTable.setActivePlayer(playerId == playerTable.playerId));
    }
    
    private setGamestateDescription(property: string = '') {
        const originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        this.gamedatas.gamestate.description = `${originalState['description' + property]}`; 
        this.gamedatas.gamestate.descriptionmyturn = `${originalState['descriptionmyturn' + property]}`;
        (this as any).updatePageTitle();
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
            dojo.place(`
            <div id="pick-monster-figure-${monster}" class="monster-figure monster${monster}"></div>
            `, `monster-pick`);

            document.getElementById(`pick-monster-figure-${monster}`).addEventListener('click', () => {
                this.pickMonster(monster);
            })
        });

        const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();
        dojo.toggleClass('monster-pick', 'selectable', isCurrentPlayerActive);
    }

    private onEnteringChooseInitialCard(args: EnteringChooseInitialCardArgs) {
        this.tableCenter.setInitialCards(args.cards);

        if ((this as any).isCurrentPlayerActive()) {
            this.tableCenter.setVisibleCardsSelectionMode(1);
        }
    }

    private onEnteringThrowDice(args: EnteringThrowDiceArgs) {
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? `last` : '');

        this.diceManager.showLock();

        const dice = args.dice;
        const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();

        this.diceManager.setDiceForThrowDice(dice, args.inTokyo, isCurrentPlayerActive);
        
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
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3(), !args.rethrow3.hasDice3);
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
        }

        if (args.throwNumber === args.maxThrowNumber && !args.hasSmokeCloud && !args.hasCultist && !args.energyDrink?.hasCard) {
            this.diceManager.disableDiceAction();
        }
    }

    private onEnteringChangeDie(args: EnteringChangeDieArgs, isCurrentPlayerActive: boolean) {
        if (args.dice?.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args, args.inTokyo, isCurrentPlayerActive);
        }

        if (isCurrentPlayerActive && args.dice && args.rethrow3?.hasCard) {
            if (document.getElementById('rethrow3changeDie_button')) {
                dojo.toggleClass('rethrow3changeDie_button', 'disabled', !args.rethrow3.hasDice3);
            } else {
                this.createButton('dice-actions', 'rethrow3changeDie_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3changeDie(), !args.rethrow3.hasDice3);
            }
        }
    }

    private onEnteringPsychicProbeRollDie(args: EnteringPsychicProbeRollDieArgs, isCurrentPlayerActive: boolean) {
        this.diceManager.setDiceForPsychicProbe(args.dice, args.inTokyo, isCurrentPlayerActive && args.canRoll);

        if (args.dice && args.rethrow3?.hasCard) {
            if (document.getElementById('rethrow3psychicProbe_button')) {
                dojo.toggleClass('rethrow3psychicProbe_button', 'disabled', !args.rethrow3.hasDice3);
            } else {
                this.createButton('dice-actions', 'rethrow3psychicProbe_button', _("Reroll") + formatTextIcons(' [dice3]'), () => this.rethrow3psychicProbe(), !args.rethrow3.hasDice3);
            }
        }
    }
    
    private onEnteringTakeWickednessTile(args: EnteringTakeWickednessTileArgs, isCurrentPlayerActive: boolean) {
        this.tableCenter.setWickednessTilesSelectable(args.level, true, isCurrentPlayerActive);
    }

    private onEnteringResolveHeartDice(args: EnteringResolveHeartDiceArgs, isCurrentPlayerActive: boolean) {
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if (args.dice?.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.inTokyo);

            if (isCurrentPlayerActive) {
                dojo.place(`<div id="heart-action-selector" class="whiteblock"></div>`, 'rolled-dice-and-rapid-actions', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    }

    private onEnteringCancelDamage(args: EnteringCancelDamageArgs, isCurrentPlayerActive: boolean) {
        if (args.dice) {
            this.diceManager.showCamouflageRoll(args.dice);
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
                (this as any).addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + " ( 2[Energy] )", { 'card_name': this.cards.getCardName(48, 'text-only')})), 'useWings');
                document.getElementById('useWings_button').dataset.enableAtEnergy = '2';
                if (args.playerEnergy < 2) {
                    dojo.addClass('useWings_button', 'disabled');
                }
            }

            if (args.canUseRobot && !document.getElementById('useRobot1_button')) {
                for (let i=args.damage; i>0; i--) {
                    const id = `useRobot${i}_button`;
                    (this as any).addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': i, 'card_name': this.cards.getCardName(210, 'text-only')})), () => this.useRobot(i));
                    document.getElementById(id).dataset.enableAtEnergy = ''+i;
                    dojo.toggleClass(id, 'disabled', args.playerEnergy < i);
                }
            }

            if (!args.canThrowDices && !document.getElementById('skipWings_button')) {
                (this as any).addActionButton('skipWings_button', args.canUseWings ? dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cards.getCardName(48, 'text-only')}) : _("Skip"), 'skipWings');
            }

            const rapidHealingSyncButtons = document.querySelectorAll(`[id^='rapidHealingSync_button'`);
            rapidHealingSyncButtons.forEach(rapidHealingSyncButton => rapidHealingSyncButton.parentElement.removeChild(rapidHealingSyncButton));
            if (args.damageToCancelToSurvive) {
                //this.rapidHealingSyncHearts = args.rapidHealingHearts;
                
                for (let i = Math.min(args.rapidHealingCultists, args.damageToCancelToSurvive); i >= 0; i--) {
                    const cultistCount = i;
                    const rapidHealingCount = args.rapidHealingHearts > 0 ? args.damageToCancelToSurvive - cultistCount : 0;
                    const cardsNames = [];

                    if (cultistCount > 0) {
                        cardsNames.push(_('Cultist'));
                    }
                    if (rapidHealingCount > 0) {
                        cardsNames.push(_(this.cards.getCardName(37, 'text-only')));
                    }

                    if (cultistCount + rapidHealingCount >= args.damageToCancelToSurvive) {
                        const text = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')}` + (rapidHealingCount > 0 ? ` (${2*rapidHealingCount}[Energy])` : '')), { 'card_name': cardsNames.join(', '), 'hearts': args.damageToCancelToSurvive });
                        (this as any).addActionButton(`rapidHealingSync_button_${i}`, text, () => this.useRapidHealingSync(cultistCount, rapidHealingCount));
                    }
                }
            }
        }
    }

    private onEnteringStealCostumeCard(args: EnteringStealCostumeCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringBuyCard(args: EnteringBuyCardArgs, isCurrentPlayerActive: boolean) {
        if (isCurrentPlayerActive) {
            this.tableCenter.setVisibleCardsSelectionMode(1);

            if (args.canBuyFromPlayers) {
                this.playerTables.filter(playerTable => playerTable.playerId != this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            }

            if (args._private?.pickCards?.length) {
                this.tableCenter.showPickStock(args._private.pickCards);
            }

            this.setBuyDisabledCard(args);
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringChooseMimickedCard(args: EnteringBuyCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringSellCard(args: EnteringBuyCardArgs) {
        if ((this as any).isCurrentPlayerActive()) {
            this.playerTables.filter(playerTable => playerTable.playerId === this.getPlayerId()).forEach(playerTable => playerTable.cards.setSelectionMode(1));
            args.disabledIds.forEach(id => document.querySelector(`div[id$="_item_${id}"]`)?.classList.add('disabled'));
        }
    }

    private onEnteringEndTurn() {
    }

    public onLeavingState(stateName: string) {
        log( 'Leaving state: '+stateName );

        switch (stateName) {
            case 'chooseInitialCard':                
                this.tableCenter.setVisibleCardsSelectionMode(0);
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'opportunistChooseMimicCard':
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
            case 'takeWickednessTile':
                this.onLeavingTakeWickednessTile();
                break;
            case 'resolveHeartDiceAction':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;            
            case 'leaveTokyo':
                this.removeSkipBuyPhaseToggle();
                break;
            case 'stealCostumeCard':
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
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
        }
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

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    public onUpdateActionButtons(stateName: string, args: any) {

        switch (stateName) {
            case 'changeActivePlayerDie': case 'psychicProbeRollDie': // TODO remove
                this.setDiceSelectorVisibility(true);
                break;
            case 'cheerleaderSupport':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args, false); // because it's multiplayer, enter action must be set here
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
                break;
        }

        if((this as any).isCurrentPlayerActive()) {
            switch (stateName) {
                case 'changeMimickedCard':
                    (this as any).addActionButton('skipChangeMimickedCard_button', _("Skip"), 'skipChangeMimickedCard');

                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCard_button', 5);
                    }
                    break;
                case 'throwDice':
                    (this as any).addActionButton('goToChangeDie_button', _("Resolve dice"), 'goToChangeDie', null, null, 'red');

                    const argsThrowDice = args as EnteringThrowDiceArgs;
                    if (!argsThrowDice.hasActions) {
                        this.startActionTimer('goToChangeDie_button', 5);
                    }
                    break;
                case 'changeDie':
                    (this as any).addActionButton('resolve_button', _("Resolve dice"), 'resolveDice', null, null, 'red');
                    break;
                    case 'changeActivePlayerDie': case 'psychicProbeRollDie': // TODO remove
                    (this as any).addActionButton('changeActivePlayerDieSkip_button', _("Skip"), 'psychicProbeSkip');
                    this.onEnteringPsychicProbeRollDie(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'cheerleaderSupport':
                    (this as any).addActionButton('support_button', formatTextIcons(_("Support (add [diceSmash] )")), () => this.support());
                    (this as any).addActionButton('dontSupport_button', _("Don't support"), () => this.dontSupport());
                    this.onEnteringPsychicProbeRollDie(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'takeWickednessTile':
                    (this as any).addActionButton('skipTakeWickednessTile_button', _("Skip"), () => this.skipTakeWickednessTile());
                    break;
                case 'leaveTokyo':
                    let label = _("Stay in Tokyo");
                    const argsLeaveTokyo = args as EnteringLeaveTokyoArgs;
                    if (argsLeaveTokyo.jetsPlayers?.includes(this.getPlayerId())) {
                        label += formatTextIcons(` (- ${argsLeaveTokyo.jetsDamage} [heart])`);
                    }
                    (this as any).addActionButton('stayInTokyo_button', label, 'onStayInTokyo');
                    (this as any).addActionButton('leaveTokyo_button', _("Leave Tokyo"), 'onLeaveTokyo');
                    if (!argsLeaveTokyo.canYieldTokyo) {
                        this.startActionTimer('stayInTokyo_button', 5);
                        dojo.addClass('leaveTokyo_button', 'disabled');
                    }
                    break;

                case 'stealCostumeCard':
                    const argsStealCostumeCard = args as EnteringStealCostumeCardArgs;

                    (this as any).addActionButton('endStealCostume_button', _("Skip"), 'endStealCostume', null, null, 'red');

                    if (!argsStealCostumeCard.canBuyFromPlayers) {
                        this.startActionTimer('endStealCostume_button', 5);
                    }
                    break;
                case 'changeForm':
                    const argsChangeForm = args as EnteringChangeFormArgs;

                    (this as any).addActionButton('changeForm_button',   dojo.string.substitute(/* TODOME _(*/"Change to ${otherForm}"/*)*/, {'otherForm' : _(argsChangeForm.otherForm)}) + formatTextIcons(` ( 1 [Energy])`), () => this.changeForm());
                    (this as any).addActionButton('skipChangeForm_button', /* TODOME _(*/"Don't change form"/*)*/, () => this.skipChangeForm());
                    dojo.toggleClass('changeForm_button', 'disabled', !argsChangeForm.canChangeForm);
                    break;
                case 'buyCard':
                    const argsBuyCard = args as EnteringBuyCardArgs;

                    (this as any).addActionButton('renew_button', _("Renew cards") + formatTextIcons(` ( 2 [Energy])`), 'onRenew');
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    if (argsBuyCard.canSell) {
                        (this as any).addActionButton('goToSellCard_button', _("End turn and sell cards"), 'goToSellCard');
                    }

                    (this as any).addActionButton('endTurn_button', argsBuyCard.canSell ? _("End turn without selling") : _("End turn"), 'onEndTurn', null, null, 'red');

                    if (!argsBuyCard.canBuyOrNenew && !argsBuyCard.canSell) {
                        this.startActionTimer('endTurn_button', 5);
                    }
                    break;
                case 'opportunistBuyCard':
                    (this as any).addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');

                    if (!args.canBuy) {
                        this.startActionTimer('opportunistSkip_button', 5);
                    }

                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                    break;
                case 'sellCard':
                    (this as any).addActionButton('endTurnSellCard_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;
                
                case 'cancelDamage':
                    this.onEnteringCancelDamage(args, true); // because it's multiplayer, enter action must be set here
                    break;
            }

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

    public isDarkEdition(): boolean {
        return false; // TODODE
    }

    public isDefaultFont(): boolean {
        return Number((this as any).prefs[201].value) == 1;
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
                </div>`; // TODOWI
            }
            html += `</div>`;
            dojo.place(html, `player_board_${player.id}`);

            if (gamedatas.kingkongExpansion || gamedatas.cybertoothExpansion || gamedatas.cthulhuExpansion) {
                let html = `<div class="counters">`;

                if (gamedatas.kingkongExpansion) {
                    html += `
                    <div id="tokyo-tower-counter-wrapper-${player.id}" class="counter tokyo-tower-tooltip">
                        <div class="tokyo-tower-icon-wrapper"><div class="tokyo-tower-icon"></div></div>
                        <span id="tokyo-tower-counter-${player.id}"></span>&nbsp;/&nbsp;3
                    </div>`;
                }

                if (gamedatas.cybertoothExpansion) {
                    html += `
                    <div id="berserk-counter-wrapper-${player.id}" class="counter berserk-tooltip">
                        <div class="berserk-icon-wrapper">
                            <div id="player-panel-berserk-${player.id}" class="berserk icon ${player.berserk ? 'active' : ''}"></div>
                        </div>
                    </div>`;
                }

                if (gamedatas.cthulhuExpansion) {
                    html += `
                    <div id="cultist-counter-wrapper-${player.id}" class="counter cultist-tooltip">
                        <div class="icon cultist"></div>
                        <span id="cultist-counter-${player.id}"></span>
                    </div>`;
                }

                html += `</div>`;
                dojo.place(html, `player_board_${player.id}`);

                if (gamedatas.kingkongExpansion) {
                    const tokyoTowerCounter = new ebg.counter();
                    tokyoTowerCounter.create(`tokyo-tower-counter-${player.id}`);
                    tokyoTowerCounter.setValue(player.tokyoTowerLevels.length);
                    this.tokyoTowerCounters[playerId] = tokyoTowerCounter;
                }

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
        this.playerTables = this.getOrderedPlayers().map(player => {
            const playerId = Number(player.id);
            const playerWithGoldenScarab = gamedatas.anubisExpansion && playerId === gamedatas.playerWithGoldenScarab;
            return new PlayerTable(this, player, gamedatas.playersCards[playerId], gamedatas.playersWickednessTiles?.[playerId], playerWithGoldenScarab);
        });
    }

    private getPlayerTable(playerId: number): PlayerTable {
        return this.playerTables.find(playerTable => playerTable.playerId === Number(playerId));
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

    private replaceMonsterChoiceByTable() {
        if (document.getElementById('monster-pick')) {
            (this as any).fadeOutAndDestroy('monster-pick');
        }
        if (dojo.hasClass('kot-table', 'pickMonster')) {
            dojo.removeClass('kot-table', 'pickMonster');
            this.tableManager.setAutoZoomAndPlacePlayerTables();
            this.tableCenter.getVisibleCards().updateDisplay();
            this.playerTables.forEach(playerTable => playerTable.cards.updateDisplay());
        }
    }

    private getStateName() {
        return this.gamedatas.gamestate.name;
    }

    public onVisibleCardClick(stock: Stock, cardId: string, from: number = 0) { // from : player id
        if (!cardId) {
            return;
        }

        if (dojo.hasClass(`${stock.container_div.id}_item_${cardId}`, 'disabled')) {
            stock.unselectItem(cardId);
            return;
        }

        const stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            this.chooseInitialCard(cardId);
        } else if (stateName === 'stealCostumeCard') {
            this.stealCostumeCard(cardId);
        } else if (stateName === 'sellCard') {
            this.sellCard(cardId);
        } else if (stateName === 'chooseMimickedCard' || stateName === 'opportunistChooseMimicCard') {
            this.chooseMimickedCard(cardId);
        } else if (stateName === 'changeMimickedCard') {
            this.changeMimickedCard(cardId);
        } else if (stateName === 'buyCard' || stateName === 'opportunistBuyCard') {
            this.tableCenter.removeOtherCardsFromPick(cardId);
            this.buyCard(cardId, from);
        }
    }

    setBuyDisabledCard(args: EnteringBuyCardArgs = null, playerEnergy: number = null) {
        if (!(this as any).isCurrentPlayerActive()) {
            return;
        }
        
        const stateName = this.getStateName();
        if (stateName !== 'buyCard' && stateName !== 'opportunistBuyCard' && stateName !== 'stealCostumeCard') {
            return;
        }
        if (args === null) {
            args = this.gamedatas.gamestate.args;
        }
        if (playerEnergy === null) {
            playerEnergy = this.energyCounters[this.getPlayerId()].getValue();
        }

        Object.keys(args.cardsCosts).forEach(cardId => {
            const id = Number(cardId);
            const disabled = args.unbuyableIds.some(disabledId => disabledId == id) || args.cardsCosts[id] > playerEnergy;
            const cardDiv = document.querySelector(`div[id$="_item_${id}"]`) as HTMLElement;
            if (cardDiv && cardDiv.closest('.card-stock') !== null) {
                dojo.toggleClass(cardDiv, 'disabled', disabled);
            }
        });

        // renew button
        if (document.getElementById('renew_button')) {
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

    private setMimicToken(card: Card) {
        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.items.some(item => Number(item.id) == card.id)) {
                this.cards.placeMimicOnCard(playerTable.cards, card);
            }
        });

        this.setMimicTooltip(card);
    }

    private removeMimicToken(card: Card) {
        this.setMimicTooltip(null);

        if (!card) {
            return;
        }

        this.playerTables.forEach(playerTable => {
            if (playerTable.cards.items.some(item => Number(item.id) == card.id)) {
                this.cards.removeMimicOnCard(playerTable.cards, card);
            }
        });
    }

    private setMimicTooltip(mimickedCard: Card) {
        this.playerTables.forEach(playerTable => {
            const mimicCardItem = playerTable.cards.items.find(item => Number(item.type) == 27);
            if (mimicCardItem) {
                this.cards.changeMimicTooltip(`cards-${playerTable.playerId}_item_${mimicCardItem.id}`, mimickedCard);
            }
        });
    }

    public pickMonster(monster: number) {
        if(!(this as any).checkAction('pickMonster')) {
            return;
        }

        this.takeAction('pickMonster', {
            monster
        });
    }

    public chooseInitialCard(id: number | string) {
        if(!(this as any).checkAction('chooseInitialCard')) {
            return;
        }

        this.takeAction('chooseInitialCard', {
            id
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
        this.takeAction('useRapidHealing');
    }

    public useRapidCultist(type: number) { // 4 for health, 5 for energy
        this.takeAction('useRapidCultist', { type });
    }

    public setSkipBuyPhase(skipBuyPhase: boolean) {
        this.takeAction('setSkipBuyPhase', {
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

    public onStayInTokyo() {
        if(!(this as any).checkAction('stay')) {
            return;
        }

        this.takeAction('stay');
    }
    public onLeaveTokyo() {
        if(!(this as any).checkAction('leave')) {
            return;
        }

        this.takeAction('leave');
    }

    public stealCostumeCard(id: number | string) {
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

    public buyCard(id: number | string, from: number) {
        if(!(this as any).checkAction('buyCard')) {
            return;
        }

        this.takeAction('buyCard', {
            id,
            from
        });
    }

    public chooseMimickedCard(id: number | string) {
        if(!(this as any).checkAction('chooseMimickedCard')) {
            return;
        }

        this.takeAction('chooseMimickedCard', {
            id
        });
    }

    public changeMimickedCard(id: number | string) {
        if(!(this as any).checkAction('changeMimickedCard')) {
            return;
        }

        this.takeAction('changeMimickedCard', {
            id
        });
    }

    public sellCard(id: number | string) {
        if(!(this as any).checkAction('sellCard')) {
            return;
        }

        this.takeAction('sellCard', {
            id
        });
    }

    public onRenew() {
        if(!(this as any).checkAction('renew')) {
            return;
        }

        this.takeAction('renew');
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
        this.takeAction('setLeaveTokyoUnder', {
            under
        });
    }

    public setStayTokyoOver(over: number) {
        this.takeAction('setStayTokyoOver', {
            over
        });
    }

    public takeAction(action: string, data?: any) {
        data = data || {};
        data.lock = true;
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
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['changeDie', ANIMATION_MS],
            ['rethrow3changeDie', ANIMATION_MS],
            ['changeCurseCard', ANIMATION_MS],
            ['takeWickednessTile', ANIMATION_MS],
            ['resolvePlayerDice', 500],
            ['changeTokyoTowerOwner', 500],
            ['changeForm', 500],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['maxHealth', 1],
            ['wickedness', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['setCardTokens', 1],
            ['removeCards', 1],
            ['setMimicToken', 1],
            ['removeMimicToken', 1],
            ['toggleRapidHealing', 1],
            ['updateLeaveTokyoUnder', 1],
            ['updateStayTokyoOver', 1],
            ['kotPlayerEliminated', 1],
            ['setPlayerBerserk', 1],
            ['cultist', 1],
            ['removeWickednessTiles', 1],
        ];
    
        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            (this as any).notifqueue.setSynchronous(notif[0], notif[1]);
        });
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

    notif_setInitialCards(notif: Notif<NotifSetInitialCardsArgs>) {
        this.tableCenter.setInitialCards(notif.args.cards);
    }

    notif_resolveNumberDice(notif: Notif<NotifResolveNumberDiceArgs>) {
        this.setPoints(notif.args.playerId, notif.args.points, ANIMATION_MS);
        this.animationManager.resolveNumberDice(notif.args);
        this.diceManager.resolveNumberDice(notif.args);
    }

    notif_resolveHealthDice(notif: Notif<NotifResolveHealthDiceArgs>) {
        this.setHealth(notif.args.playerId, notif.args.health, ANIMATION_MS);
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
        this.setEnergy(notif.args.playerId, notif.args.energy, ANIMATION_MS);
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
    }

    notif_playerEntersTokyo(notif: Notif<NotifPlayerEntersTokyoArgs>) {
        this.getPlayerTable(notif.args.playerId).enterTokyo(notif.args.location);
        this.setPoints(notif.args.playerId, notif.args.points);
        this.setEnergy(notif.args.playerId, notif.args.energy);
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
            this.cards.moveToAnotherStock(this.getPlayerTable(notif.args.from).cards, this.getPlayerTable(notif.args.playerId).cards, card);
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

    notif_removeCards(notif: Notif<NotifRemoveCardsArgs>) {
        if (notif.args.delay) {
            notif.args.delay = false;
            setTimeout(() => this.notif_removeCards(notif), ANIMATION_MS);
        } else {
            this.getPlayerTable(notif.args.playerId).removeCards(notif.args.cards);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    }

    notif_setMimicToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.setMimicToken(notif.args.card);
    }

    notif_removeMimicToken(notif: Notif<NotifSetCardTokensArgs>) {
        this.removeMimicToken(notif.args.card);
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

    notif_toggleRapidHealing(notif: Notif<NotifToggleRapidHealingArgs>) {
        if (notif.args.active) {
            this.addRapidHealingButton(notif.args.playerEnergy, notif.args.isMaxHealth);
        } else {
            this.removeRapidHealingButton();
        }
    }

    notif_useCamouflage(notif: Notif<NotifUseCamouflageArgs>) {
        if (notif.args.cancelDamageArgs) { 
            this.gamedatas.gamestate.args = notif.args.cancelDamageArgs;
            (this as any).updatePageTitle();
            this.onEnteringCancelDamage(notif.args.cancelDamageArgs, (this as any).isCurrentPlayerActive());
        } else {            
            this.diceManager.showCamouflageRoll(notif.args.diceValues);
        }
    }

    notif_changeDie(notif: Notif<NotifChangeDieArgs>) {
        if (notif.args.psychicProbeRollDieArgs) {
            const isCurrentPlayerActive = (this as any).isCurrentPlayerActive();
            this.onEnteringPsychicProbeRollDie(notif.args.psychicProbeRollDieArgs, isCurrentPlayerActive);
        } else {
            this.diceManager.changeDie(notif.args.dieId, notif.args.inTokyo, notif.args.toValue, notif.args.roll);
        }
    }

    notif_rethrow3changeDie(notif: Notif<NotifChangeDieArgs>) {
        this.diceManager.changeDie(notif.args.dieId, notif.args.inTokyo, notif.args.toValue, notif.args.roll);
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

    private getTokyoTowerLevels(playerId: number) {
        const levels = [];
        for (const property in this.towerLevelsOwners) {
            if (this.towerLevelsOwners[property] == playerId) {
                levels.push(Number(property));
            }
        }
        return levels;
    }

    notif_changeTokyoTowerOwner(notif: Notif<NotifChangeTokyoTowerOwnerArgs>) {   
        const playerId = notif.args.playerId;
        const previousOwner = this.towerLevelsOwners[notif.args.level];
        this.towerLevelsOwners[notif.args.level] = playerId;

        const previousOwnerTower = previousOwner == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(previousOwner).getTokyoTower();
        const newLevelTower = playerId == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(playerId).getTokyoTower();

        const previousOwnerTowerLevels = this.getTokyoTowerLevels(previousOwner);
        const newLevelTowerLevels = this.getTokyoTowerLevels(playerId);

        previousOwnerTower.setLevels(previousOwnerTowerLevels);
        newLevelTower.setLevels(newLevelTowerLevels);
        if (previousOwner != 0) {
            this.tokyoTowerCounters[previousOwner].toValue(previousOwnerTowerLevels.length);
        }
        if (playerId != 0) {
            this.tokyoTowerCounters[playerId].toValue(newLevelTowerLevels.length);
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
        this.wickednessTiles.moveToAnotherStock(this.tableCenter.getWickednessTilesStock(notif.args.level), this.getPlayerTable(notif.args.playerId).wickednessTiles, notif.args.tile);
        this.tableCenter.removeReducedWickednessTile(notif.args.level, notif.args.tile);

        this.tableManager.placePlayerTable(); // adapt to new card
    }

    notif_removeWickednessTiles(notif: Notif<NotifRemoveWickednessTilesArgs>) {
        this.getPlayerTable(notif.args.playerId).removeWickednessTiles(notif.args.tiles);
        this.tableManager.placePlayerTable(); // adapt after removed cards
    }
    
    private setPoints(playerId: number, points: number, delay: number = 0) {
        (this as any).scoreCtrl[playerId]?.toValue(points);
        this.getPlayerTable(playerId).setPoints(points, delay);
    }
    
    private setHealth(playerId: number, health: number, delay: number = 0) {
        this.healthCounters[playerId].toValue(health);
        this.getPlayerTable(playerId).setHealth(health, delay);
        this.checkRapidHealingButtonState();
        this.checkHealthCultistButtonState();
    }
    
    private setMaxHealth(playerId: number, maxHealth: number) {
        this.gamedatas.players[playerId].maxHealth = maxHealth;
        this.checkRapidHealingButtonState();
        this.checkHealthCultistButtonState();
        const popinId = `discussion_bubble_autoLeaveUnder`;
        if (document.getElementById(popinId)) {
            this.updateAutoLeavePopinButtons();
        }
    }
    
    private setEnergy(playerId: number, energy: number, delay: number = 0) {
        this.energyCounters[playerId].toValue(energy);
        this.getPlayerTable(playerId).setEnergy(energy, delay);
        this.checkBuyEnergyDrinkState(energy); // disable button if energy gets down to 0
        this.checkRapidHealingButtonState();
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
    }

    private getLogCardName(logType: number) {
        if (logType >= 2000) {
            return this.wickednessTiles.getCardName(logType - 2000);
        } if (logType >= 1000) {
            return this.curseCards.getCardName(logType - 1000);
        } else {
            return this.cards.getCardName(logType, 'text-only');
        }
    }

    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    public format_string_recursive(log: string, args: any) {
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                if (args.card_name) {
                    let types: number[] = null;
                    if (typeof args.card_name == 'number') {
                        types = [args.card_name];
                    } else if (typeof args.card_name == 'string' && args.card_name[0] >= '0' && args.card_name[0] <= '9') {
                        types = args.card_name.split(',').map((cardType: string) => Number(cardType));
                    }
                    if (types !== null) {
                        const names: string[] = types.map((cardType: number) => this.getLogCardName(cardType));
                        args.card_name = `<strong>${names.join(', ')}</strong>`;
                    }
                }

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