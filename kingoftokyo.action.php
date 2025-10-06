<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * KingOfTokyo implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * kingoftokyo.action.php
 *
 * KingOfTokyo main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/kingoftokyo/kingoftokyo/myAction.html", ...)
 *
 */
  
  
  class action_kingoftokyo extends APP_GameAction { 
    // Constructor: please do not modify
   	public function __default() {
        if (self::isArg( 'notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        } else {
            $this->view = "kingoftokyo_kingoftokyo";
            self::trace( "Complete reinitialization of board game" );
        }
  	} 
  	
  	// defines your action entry points there
  	
    public function pickMonster() {
        self::setAjaxMode();

        $monster = self::getArg("monster", AT_posint, true);

        $this->game->actPickMonster($monster);

        self::ajaxResponse();
    }
  	
    public function pickEvolutionForDeck() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actPickEvolutionForDeck($id);

        self::ajaxResponse();
    }
  	
    public function chooseInitialCard() {
        self::setAjaxMode();

        $costumeId = self::getArg("id", AT_posint, false);
        $evolutionId = self::getArg("evolutionId", AT_posint, false);


        $this->game->actChooseInitialCard($costumeId, $evolutionId);

        self::ajaxResponse();
    }
  	
    public function skipBeforeStartTurn() {
        self::setAjaxMode();

        $this->game->actSkipBeforeStartTurn();

        self::ajaxResponse();
    }
  	
    public function skipBeforeEndTurn() {
        self::setAjaxMode();

        $this->game->actSkipBeforeEndTurn();

        self::ajaxResponse();
    }
  	
    public function skipBeforeEnteringTokyo() {
        self::setAjaxMode();

        $this->game->actSkipBeforeEnteringTokyo();

        self::ajaxResponse();
    }
  	
    public function skipAfterEnteringTokyo() {
        self::setAjaxMode();

        $this->game->actSkipAfterEnteringTokyo();

        self::ajaxResponse();
    }
  	
    public function giveSymbolToActivePlayer() {
        self::setAjaxMode();

        $symbol = self::getArg("symbol", AT_posint, true);

        $this->game->actGiveSymbolToActivePlayer($symbol);

        self::ajaxResponse();
    }
  	
    public function giveSymbol() {
        self::setAjaxMode();

        $symbol = self::getArg("symbol", AT_posint, true);

        $this->game->actGiveSymbol($symbol);

        self::ajaxResponse();
    }
  	
    public function rethrow() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);
        if ($diceIds === '') {
            throw new \BgaUserException('No dice to reroll');
        }
        $diceIds = array_map(fn($v) => intval($v), explode(',', $diceIds));

        $this->game->actRethrow($diceIds);

        self::ajaxResponse();
    }
  	
    public function buyEnergyDrink() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);
        if ($diceIds === '') {
            throw new \BgaUserException('No dice to reroll');
        }
        $diceIds = array_map(fn($v) => intval($v), explode(',', $diceIds));

        $this->game->actBuyEnergyDrink($diceIds);

        self::ajaxResponse();
    }
  	
    public function useSmokeCloud() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);
        if ($diceIds === '') {
            throw new \BgaUserException('No dice to reroll');
        }
        $diceIds = array_map(fn($v) => intval($v), explode(',', $diceIds));

        $this->game->actUseSmokeCloud($diceIds);

        self::ajaxResponse();
    }
  	
    public function useCultist() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);
        if ($diceIds === '') {
            throw new \BgaUserException('No dice to reroll');
        }
        $diceIds = array_map(fn($v) => intval($v), explode(',', $diceIds));

        $this->game->actUseCultist($diceIds);

        self::ajaxResponse();
    }
  	
    public function useRapidHealing() {
        self::setAjaxMode();

        $this->game->actUseRapidHealing();

        self::ajaxResponse();
    }
  	
    public function useMothershipSupport() {
        self::setAjaxMode();

        $this->game->actUseMothershipSupport();

        self::ajaxResponse();
    }
  	
    public function useRapidHealingSync() {
        self::setAjaxMode();

        $cultistCount = self::getArg("cultistCount", AT_posint, true);
        $rapidHealingCount = self::getArg("rapidHealingCount", AT_posint, true);

        $this->game->actUseRapidHealingSync($cultistCount, $rapidHealingCount);

        self::ajaxResponse();
    }
  	
    public function useRapidCultist() {
        self::setAjaxMode();

        $type = self::getArg("type", AT_posint, true);

        $this->game->actUseRapidCultist($type);

        self::ajaxResponse();
    }

    public function rethrow3() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, false);
        if ($diceIds === '') {
            throw new \BgaUserException('No dice to reroll');
        }
        $diceIds = array_map(fn($v) => intval($v), explode(',', $diceIds));

        $this->game->actRethrow3($diceIds);

        self::ajaxResponse();
    }

    public function rethrow3camouflage() {
        self::setAjaxMode();

        $this->game->actRethrow3Camouflage();

        self::ajaxResponse();
    }

    public function rethrow3psychicProbe() {
        self::setAjaxMode();

        $this->game->actRethrow3PsychicProbe();

        self::ajaxResponse();
    }

    public function rethrow3changeDie() {
        self::setAjaxMode();

        $this->game->actRethrow3ChangeDie();

        self::ajaxResponse();
    }

    public function rerollDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $diceIds = self::getArg("diceIds", AT_numberlist, false);
        if ($diceIds === '') {
            throw new \BgaUserException('No dice to reroll');
        }
        $diceIds = array_map(fn($v) => intval($v), explode(',', $diceIds));

        $this->game->actRerollDie($id, $diceIds);

        self::ajaxResponse();
    }
  	
    public function goToChangeDie() {
        self::setAjaxMode();

        $this->game->actGoToChangeDie();

        self::ajaxResponse();
    }
  	
    public function psychicProbeRollDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChangeActivePlayerDie($id);

        self::ajaxResponse();
    }
  	
    public function changeActivePlayerDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChangeActivePlayerDie($id);

        self::ajaxResponse();
    }
  	
    public function psychicProbeSkip() {
        self::setAjaxMode();

        $this->game->actChangeActivePlayerDieSkip();

        self::ajaxResponse();
    }
  	
    public function changeActivePlayerDieSkip() {
        self::setAjaxMode();
  	
        $this->game->actChangeActivePlayerDieSkip();

        self::ajaxResponse();
    }
  	
    public function resolve() {
        self::setAjaxMode();

        $this->game->actResolve();

        self::ajaxResponse();
    }
  	
    public function discardDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actDiscardDie($id);

        self::ajaxResponse();
    }
  	
    public function giveGoldenScarab() {
        self::setAjaxMode();

        $playerId = self::getArg("playerId", AT_posint, true);

        $this->game->actGiveGoldenScarab($playerId);

        self::ajaxResponse();
    }
  	
    public function giveSymbols() {
        self::setAjaxMode();

        $symbols = self::getArg("symbols", AT_numberlist, true);

        $this->game->actGiveSymbols(array_map(fn($idStr) => intval($idStr), explode(',', $symbols)));

        self::ajaxResponse();
    }
  	
    public function selectExtraDie() {
        self::setAjaxMode();

        $face = self::getArg("face", AT_posint, true);

        $this->game->selectExtraDie($face);

        self::ajaxResponse();
    }
  	
    public function discardKeepCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actDiscardKeepCard($id);

        self::ajaxResponse();
    }
  	
    public function falseBlessingReroll() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actFalseBlessingReroll($id);

        self::ajaxResponse();
    }
  	
    public function falseBlessingDiscard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actFalseBlessingDiscard($id);

        self::ajaxResponse();
    }
  	
    public function falseBlessingSkip() {
        self::setAjaxMode();

        $this->game->actFalseBlessingSkip();

        self::ajaxResponse();
    }

    public function rerollDice() {
        self::setAjaxMode();

        $ids = self::getArg("ids", AT_numberlist, true);

        $this->game->actRerollDice(array_map(fn($idStr) => intval($idStr), explode(',', $ids)));

        self::ajaxResponse();
    }
  	
    public function support() {
        self::setAjaxMode();

        $this->game->actSupport();

        self::ajaxResponse();
    }
  	
    public function dontSupport() {
        self::setAjaxMode();

        $this->game->actDontSupport();

        self::ajaxResponse();
    }
  	
    public function takeWickednessTile() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actTakeWickednessTile($id);

        self::ajaxResponse();
    }
  	
    public function skipTakeWickednessTile() {
        self::setAjaxMode();

        $this->game->actSkipTakeWickednessTile();

        self::ajaxResponse();
    }
  	
    public function chooseEvolutionCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChooseEvolutionCard($id);

        self::ajaxResponse();
    }
  	
    public function actStay() {
        self::setAjaxMode();

        $this->game->actStay();

        self::ajaxResponse();
    }
  	
    public function leave() {
        self::setAjaxMode();
        
        $useCard = self::getArg("useCard", AT_posint, false);

        $this->game->actLeave($useCard);

        self::ajaxResponse();
    }
  	
    public function stealCostumeCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actStealCostumeCard($id);

        self::ajaxResponse();
    }
  	
    public function endStealCostume() {
        self::setAjaxMode();

        $this->game->actEndStealCostume();

        self::ajaxResponse();
    }
  	
    public function buyCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $from = self::getArg("from", AT_posint, true);
        $useSuperiorAlienTechnology = self::getArg("useSuperiorAlienTechnology", AT_bool, true);
        $useBobbingForApples = self::getArg("useBobbingForApples", AT_bool, false); // TODOPUHA true

        $this->game->actBuyCard($id, $from, $useSuperiorAlienTechnology, $useBobbingForApples);

        self::ajaxResponse();
    }
  	
    public function buyCardBamboozle() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $from = self::getArg("from", AT_posint, true);

        $this->game->actBuyCardBamboozle($id, $from);

        self::ajaxResponse();
    }
  	
    public function chooseMimickedCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChooseMimickedCard($id);

        self::ajaxResponse();
    }
  	
    public function chooseMimickedEvolution() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChooseMimickedEvolution($id);

        self::ajaxResponse();
    }

    public function changeMimickedCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChangeMimickedCard($id);

        self::ajaxResponse();
    }
  	
    public function skipChangeMimickedCard() {
        self::setAjaxMode();

        $this->game->actSkipChangeMimickedCard();

        self::ajaxResponse();
    }

    public function changeMimickedCardWickednessTile() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChangeMimickedCardWickednessTile($id);

        self::ajaxResponse();
    }
  	
    public function skipChangeMimickedCardWickednessTile() {
        self::setAjaxMode();

        $this->game->actSkipChangeMimickedCardWickednessTile();

        self::ajaxResponse();
    }
  	
    public function chooseMimickedCardWickednessTile() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actChooseMimickedCardWickednessTile($id);

        self::ajaxResponse();
    }
  	
    public function renew() {
        self::setAjaxMode();

        $cardType = self::getArg("cardType", AT_posint, false);

        $this->game->renewCards($cardType);

        self::ajaxResponse();
    }
  	
    public function goToSellCard() {
        self::setAjaxMode();

        $this->game->actGoToSellCard();

        self::ajaxResponse();
    }
  	
    public function opportunistSkip() {
        self::setAjaxMode();

        $this->game->actOpportunistSkip();

        self::ajaxResponse();
    }
  	
    public function sellCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actSellCard($id);

        self::ajaxResponse();
    }
  	
    public function actEndTurn() {
        self::setAjaxMode();

        $this->game->actEndTurn();

        self::ajaxResponse();
    }

    public function throwCamouflageDice() {
        self::setAjaxMode();

        $this->game->actThrowCamouflageDice();

        self::ajaxResponse();
    }
    
    public function useWings() {
        self::setAjaxMode();

        $this->game->actUseWings();

        self::ajaxResponse();
    }
    
    public function useInvincibleEvolution() {
        self::setAjaxMode();

        $evolutionType = self::getArg("evolutionType", AT_posint, true);
        $this->game->actUseInvincibleEvolution($evolutionType);

        self::ajaxResponse();
    }
    
    public function useCandyEvolution() {
        self::setAjaxMode();

        $this->game->actUseCandyEvolution();

        self::ajaxResponse();
    }

    public function skipWings() {
        self::setAjaxMode();

        $this->game->actSkipWings();

        self::ajaxResponse();
    }
  	
    public function useRobot() {
        self::setAjaxMode();

        $energy = self::getArg("energy", AT_posint, true);

        $this->game->actUseRobot($energy);

        self::ajaxResponse();
    }
  	
    public function useElectricArmor() {
        self::setAjaxMode();

        $energy = self::getArg("energy", AT_posint, true);

        $this->game->actUseElectricArmor($energy);

        self::ajaxResponse();
    }
  	
    public function useSuperJump() {
        self::setAjaxMode();

        $energy = self::getArg("energy", AT_posint, true);

        $this->game->actUseSuperJump($energy);

        self::ajaxResponse();
    }
  	
    public function setLeaveTokyoUnder() {
        self::setAjaxMode();

        $under = self::getArg("under", AT_posint, true);

        $this->game->actSetLeaveTokyoUnder($under);

        self::ajaxResponse();
    }
  	
    public function setStayTokyoOver() {
        self::setAjaxMode();

        $over = self::getArg("over", AT_posint, true);

        $this->game->actSetStayTokyoOver($over);

        self::ajaxResponse();
    }
  	
    public function setAskPlayEvolution() {
        self::setAjaxMode();

        $value = self::getArg("value", AT_posint, true);

        $this->game->actSetAskPlayEvolution($value);

        self::ajaxResponse();
    }
  	
    public function setSkipBuyPhase() {
        self::setAjaxMode();

        $skipBuyPhase = self::getArg("skipBuyPhase", AT_bool, true);

        $this->game->actSetSkipBuyPhase($skipBuyPhase);

        self::ajaxResponse();
    }
  	
    public function changeForm() {
        self::setAjaxMode();

        $this->game->actChangeForm();

        self::ajaxResponse();
    }
  	
    public function skipChangeForm() {
        self::setAjaxMode();

        $this->game->actSkipChangeForm();

        self::ajaxResponse();
    }
  	
    public function exchangeCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actExchangeCard($id);

        self::ajaxResponse();
    }
  	
    public function skipExchangeCard() {
        self::setAjaxMode();

        $this->game->actSkipExchangeCard();

        self::ajaxResponse();
    }
  	
    public function stayInHibernation() {
        self::setAjaxMode();

        $this->game->actStayInHibernation();

        self::ajaxResponse();
    }
  	
    public function leaveHibernation() {
        self::setAjaxMode();

        $this->game->actLeaveHibernation();

        self::ajaxResponse();
    }
  	
    public function playEvolution() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actPlayEvolution($id);

        self::ajaxResponse();
    }
  	
    public function giveGiftEvolution() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $toPlayerId = self::getArg("toPlayerId", AT_posint, true);

        $this->game->actGiveGiftEvolution($id, $toPlayerId);

        self::ajaxResponse();
    }
  	
    public function useYinYang() {
        self::setAjaxMode();

        $this->game->actUseYinYang();

        self::ajaxResponse();
    }
  	
    public function putEnergyOnBambooSupply() {
        self::setAjaxMode();

        $this->game->actPutEnergyOnBambooSupply();

        self::ajaxResponse();
    }
  	
    public function takeEnergyOnBambooSupply() {
        self::setAjaxMode();

        $this->game->actTakeEnergyOnBambooSupply();

        self::ajaxResponse();
    }
  	
    public function skipCardIsBought() {
        self::setAjaxMode();

        $this->game->actSkipCardIsBought();

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxDrawEvolution() {
        self::setAjaxMode();

        $this->game->actGazeOfTheSphinxDrawEvolution();

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxGainEnergy() {
        self::setAjaxMode();

        $this->game->actGazeOfTheSphinxGainEnergy();

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxDiscardEvolution() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actGazeOfTheSphinxDiscardEvolution($id);

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxLoseEnergy() {
        self::setAjaxMode();

        $this->game->actGazeOfTheSphinxLoseEnergy();

        self::ajaxResponse();
    }
  	
    public function useChestThumping() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actUseChestThumping($id);

        self::ajaxResponse();
    }
  	
    public function skipChestThumping() {
        self::setAjaxMode();

        $this->game->actSkipChestThumping();

        self::ajaxResponse();
    }
  	
    public function chooseFreezeRayDieFace() {
        self::setAjaxMode();

        $symbol = self::getArg("symbol", AT_posint, true);

        $this->game->actChooseFreezeRayDieFace($symbol);

        self::ajaxResponse();
    }
  	
    public function useMiraculousCatch() {
        self::setAjaxMode();

        $this->game->actUseMiraculousCatch();

        self::ajaxResponse();
    }
  	
    public function buyCardMiraculousCatch() {
        self::setAjaxMode();
        
        $useSuperiorAlienTechnology = self::getArg("useSuperiorAlienTechnology", AT_bool, true);

        $this->game->actBuyCardMiraculousCatch($useSuperiorAlienTechnology);

        self::ajaxResponse();
    }
  	
    public function skipMiraculousCatch() {
        self::setAjaxMode();

        $this->game->actSkipMiraculousCatch();

        self::ajaxResponse();
    }
  	
    public function playCardDeepDive() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actPlayCardDeepDive($id);

        self::ajaxResponse();
    }
  	
    public function freezeDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->actFreezeDie($id);

        self::ajaxResponse();
    }
  	
    public function skipFreezeDie() {
        self::setAjaxMode();

        $this->game->actSkipFreezeDie();

        self::ajaxResponse();
    }
  	
    public function useExoticArms() {
        self::setAjaxMode();

        $this->game->actUseExoticArms();

        self::ajaxResponse();
    }
  	
    public function skipExoticArms() {
        self::setAjaxMode();

        $this->game->actSkipExoticArms();

        self::ajaxResponse();
    }
  	
    public function skipBeforeResolveDice() {
        self::setAjaxMode();

        $this->game->actSkipBeforeResolveDice();

        self::ajaxResponse();
    }
  	
    public function giveTarget() {
        self::setAjaxMode();

        $this->game->actGiveTarget();

        self::ajaxResponse();
    }
  	
    public function skipGiveTarget() {
        self::setAjaxMode();

        $this->game->actSkipGiveTarget();

        self::ajaxResponse();
    }
  	
    public function useLightningArmor() {
        self::setAjaxMode();

        $this->game->actUseLightningArmor();

        self::ajaxResponse();
    }
  	
    public function skipLightningArmor() {
        self::setAjaxMode();

        $this->game->actSkipLightningArmor();

        self::ajaxResponse();
    }
  	
    public function answerEnergySword() {
        self::setAjaxMode();

        $use = self::getArg("use", AT_bool, true);
        $this->game->actAnswerEnergySword($use);

        self::ajaxResponse();
    }
  	
    public function answerSunkenTemple() {
        self::setAjaxMode();

        $use = self::getArg("use", AT_bool, true);
        $this->game->actAnswerSunkenTemple($use);

        self::ajaxResponse();
    }
  	
    public function answerElectricCarrot() {
        self::setAjaxMode();

        $choice = self::getArg("choice", AT_posint, true);
        $this->game->actAnswerElectricCarrot($choice);

        self::ajaxResponse();
    }
  	
    public function reserveCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $this->game->actReserveCard($id);

        self::ajaxResponse();
    }
  	
    public function useFelineMotor() {
        self::setAjaxMode();

        $this->game->actUseFelineMotor();

        self::ajaxResponse();
    }
  	
    public function throwDieSuperiorAlienTechnology() {
        self::setAjaxMode();

        $this->game->actThrowDieSuperiorAlienTechnology();

        self::ajaxResponse();
    }
  	
    public function freezeRayChooseOpponent() {
        self::setAjaxMode();

        $playerId = self::getArg("playerId", AT_posint, true);
        $this->game->actFreezeRayChooseOpponent($playerId);

        self::ajaxResponse();
    }
  	
    public function loseHearts() {
        self::setAjaxMode();

        $this->game->actLoseHearts();

        self::ajaxResponse();
    }

}
