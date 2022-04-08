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

        $this->game->pickMonster($monster);

        self::ajaxResponse();
    }
  	
    public function pickEvolutionForDeck() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->pickEvolutionForDeck($id);

        self::ajaxResponse();
    }
  	
    public function chooseInitialCard() {
        self::setAjaxMode();

        $costumeId = self::getArg("id", AT_posint, false);
        $evolutionId = self::getArg("evolutionId", AT_posint, false);


        $this->game->chooseInitialCard($costumeId, $evolutionId);

        self::ajaxResponse();
    }
  	
    public function skipBeforeStartTurn() {
        self::setAjaxMode();

        $this->game->skipBeforeStartTurn();

        self::ajaxResponse();
    }
  	
    public function skipHalfMovePhase() {
        self::setAjaxMode();

        $this->game->skipHalfMovePhase();

        self::ajaxResponse();
    }
  	
    public function giveSymbolToActivePlayer() {
        self::setAjaxMode();

        $symbol = self::getArg("symbol", AT_posint, true);

        $this->game->giveSymbolToActivePlayer($symbol);

        self::ajaxResponse();
    }
  	
    public function giveSymbol() {
        self::setAjaxMode();

        $symbol = self::getArg("symbol", AT_posint, true);

        $this->game->giveSymbol($symbol);

        self::ajaxResponse();
    }
  	
    public function rethrow() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);

        $this->game->actionRethrowDice($diceIds);

        self::ajaxResponse();
    }
  	
    public function buyEnergyDrink() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);

        $this->game->buyEnergyDrink($diceIds);

        self::ajaxResponse();
    }
  	
    public function useSmokeCloud() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);

        $this->game->useSmokeCloud($diceIds);

        self::ajaxResponse();
    }
  	
    public function useCultist() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);

        $this->game->useCultist($diceIds);

        self::ajaxResponse();
    }
  	
    public function useRapidHealing() {
        self::setAjaxMode();

        $this->game->useRapidHealing();

        self::ajaxResponse();
    }
  	
    public function useMothershipSupport() {
        self::setAjaxMode();

        $this->game->useMothershipSupport();

        self::ajaxResponse();
    }
  	
    public function useRapidHealingSync() {
        self::setAjaxMode();

        $cultistCount = self::getArg("cultistCount", AT_posint, true);
        $rapidHealingCount = self::getArg("rapidHealingCount", AT_posint, true);

        $this->game->useRapidHealingSync($cultistCount, $rapidHealingCount);

        self::ajaxResponse();
    }
  	
    public function useRapidCultist() {
        self::setAjaxMode();

        $type = self::getArg("type", AT_posint, true);

        $this->game->useRapidCultist($type);

        self::ajaxResponse();
    }

    public function rethrow3() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, false);

        $this->game->rethrow3($diceIds);

        self::ajaxResponse();
    }

    public function rethrow3camouflage() {
        self::setAjaxMode();

        $this->game->rethrow3camouflage();

        self::ajaxResponse();
    }

    public function rethrow3psychicProbe() {
        self::setAjaxMode();

        $this->game->rethrow3changeActivePlayerDie();

        self::ajaxResponse();
    }

    public function rethrow3changeDie() {
        self::setAjaxMode();

        $this->game->rethrow3changeDie();

        self::ajaxResponse();
    }

    public function rerollDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $diceIds = self::getArg("diceIds", AT_numberlist, false);

        $this->game->rerollDie($id, $diceIds);

        self::ajaxResponse();
    }
  	
    public function goToChangeDie() {
        self::setAjaxMode();

        $this->game->goToChangeDie();

        self::ajaxResponse();
    }
  	
    public function changeDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $value = self::getArg("value", AT_posint, true);
        $card = self::getArg("card", AT_posint, true);

        $this->game->changeDie($id, $value, $card);

        self::ajaxResponse();
    }
  	
    public function psychicProbeRollDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->changeActivePlayerDie($id);

        self::ajaxResponse();
    }
  	
    public function psychicProbeSkip() {
        self::setAjaxMode();

        $this->game->psychicProbeSkip();

        self::ajaxResponse();
    }
  	
    public function resolve() {
        self::setAjaxMode();

        $this->game->resolveDice();

        self::ajaxResponse();
    }
  	
    public function discardDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->discardDie($id);

        self::ajaxResponse();
    }
  	
    public function giveGoldenScarab() {
        self::setAjaxMode();

        $playerId = self::getArg("playerId", AT_posint, true);

        $this->game->giveGoldenScarab($playerId);

        self::ajaxResponse();
    }
  	
    public function giveSymbols() {
        self::setAjaxMode();

        $symbols = self::getArg("symbols", AT_numberlist, true);

        $this->game->giveSymbols(array_map(function($idStr) { return intval($idStr); }, explode(',', $symbols)));

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

        $this->game->discardKeepCard($id);

        self::ajaxResponse();
    }
  	
    public function falseBlessingReroll() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->falseBlessingReroll($id);

        self::ajaxResponse();
    }
  	
    public function falseBlessingDiscard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->falseBlessingDiscard($id);

        self::ajaxResponse();
    }
  	
    public function falseBlessingSkip() {
        self::setAjaxMode();

        $this->game->falseBlessingSkip();

        self::ajaxResponse();
    }

    public function rerollDice() {
        self::setAjaxMode();

        $ids = self::getArg("ids", AT_numberlist, true);

        $this->game->rerollDice(array_map(function($idStr) { return intval($idStr); }, explode(',', $ids)));

        self::ajaxResponse();
    }
  	
    public function support() {
        self::setAjaxMode();

        $this->game->support();

        self::ajaxResponse();
    }
  	
    public function dontSupport() {
        self::setAjaxMode();

        $this->game->dontSupport();

        self::ajaxResponse();
    }
  	
    public function takeWickednessTile() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->takeWickednessTile($id);

        self::ajaxResponse();
    }
  	
    public function skipTakeWickednessTile() {
        self::setAjaxMode();

        $this->game->skipTakeWickednessTile();

        self::ajaxResponse();
    }

    public function applyHeartDieChoices() {
        self::setAjaxMode();

        $heartDieChoices = json_decode(base64_decode(self::getArg("selections", AT_base64, true)));

        $this->game->applyHeartDieChoices($heartDieChoices);

        self::ajaxResponse();
    }

    public function applySmashDieChoices() {
        self::setAjaxMode();

        $smashDieChoices = json_decode(base64_decode(self::getArg("selections", AT_base64, true)), true);

        $this->game->applySmashDieChoices($smashDieChoices);

        self::ajaxResponse();
    }
  	
    public function chooseEvolutionCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->chooseEvolutionCard($id);

        self::ajaxResponse();
    }
  	
    public function stay() {
        self::setAjaxMode();

        $this->game->stayInTokyo();

        self::ajaxResponse();
    }
  	
    public function leave() {
        self::setAjaxMode();
        
        $useCard = self::getArg("useCard", AT_posint, false);

        $this->game->actionLeaveTokyo($useCard);

        self::ajaxResponse();
    }
  	
    public function stealCostumeCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->stealCostumeCard($id);

        self::ajaxResponse();
    }
  	
    public function endStealCostume() {
        self::setAjaxMode();

        $this->game->endStealCostume();

        self::ajaxResponse();
    }
  	
    public function buyCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $from = self::getArg("from", AT_posint, true);
        $useSuperiorAlienTechnology = self::getArg("useSuperiorAlienTechnology", AT_bool, false); // TODOPU set required to true

        $this->game->buyCard($id, $from, $useSuperiorAlienTechnology);

        self::ajaxResponse();
    }
  	
    public function buyCardBamboozle() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $from = self::getArg("from", AT_posint, true);

        $this->game->buyCardBamboozle($id, $from);

        self::ajaxResponse();
    }
  	
    public function chooseMimickedCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->chooseMimickedCard($id);

        self::ajaxResponse();
    }
  	
    public function chooseMimickedEvolution() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->chooseMimickedEvolution($id);

        self::ajaxResponse();
    }

    public function changeMimickedCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->changeMimickedCard($id);

        self::ajaxResponse();
    }
  	
    public function skipChangeMimickedCard() {
        self::setAjaxMode();

        $this->game->skipChangeMimickedCard();

        self::ajaxResponse();
    }

    public function changeMimickedCardWickednessTile() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->changeMimickedCardWickednessTile($id);

        self::ajaxResponse();
    }
  	
    public function skipChangeMimickedCardWickednessTile() {
        self::setAjaxMode();

        $this->game->skipChangeMimickedCardWickednessTile();

        self::ajaxResponse();
    }
  	
    public function chooseMimickedCardWickednessTile() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->chooseMimickedCardWickednessTile($id);

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

        $this->game->goToSellCard();

        self::ajaxResponse();
    }
  	
    public function opportunistSkip() {
        self::setAjaxMode();

        $this->game->opportunistSkip();

        self::ajaxResponse();
    }
  	
    public function sellCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->sellCard($id);

        self::ajaxResponse();
    }
  	
    public function endTurn() {
        self::setAjaxMode();

        $this->game->endTurn();

        self::ajaxResponse();
    }

    public function throwCamouflageDice() {
        self::setAjaxMode();

        $this->game->throwCamouflageDice();

        self::ajaxResponse();
    }
    
    public function useWings() {
        self::setAjaxMode();

        $this->game->useWings();

        self::ajaxResponse();
    }
    
    public function useDetachableTail() {
        self::setAjaxMode();

        $this->game->useDetachableTail();

        self::ajaxResponse();
    }

    public function skipWings() {
        self::setAjaxMode();

        $this->game->skipWings();

        self::ajaxResponse();
    }
  	
    public function useRobot() {
        self::setAjaxMode();

        $energy = self::getArg("energy", AT_posint, true);

        $this->game->useRobot($energy);

        self::ajaxResponse();
    }
  	
    public function useSuperJump() {
        self::setAjaxMode();

        $energy = self::getArg("energy", AT_posint, true);

        $this->game->useSuperJump($energy);

        self::ajaxResponse();
    }
  	
    public function setLeaveTokyoUnder() {
        self::setAjaxMode();

        $under = self::getArg("under", AT_posint, true);

        $this->game->setLeaveTokyoUnder($under);

        self::ajaxResponse();
    }
  	
    public function setStayTokyoOver() {
        self::setAjaxMode();

        $over = self::getArg("over", AT_posint, true);

        $this->game->setStayTokyoOver($over);

        self::ajaxResponse();
    }
  	
    public function setSkipBuyPhase() {
        self::setAjaxMode();

        $skipBuyPhase = self::getArg("skipBuyPhase", AT_bool, true);

        $this->game->setSkipBuyPhase($skipBuyPhase);

        self::ajaxResponse();
    }
  	
    public function changeForm() {
        self::setAjaxMode();

        $this->game->changeForm();

        self::ajaxResponse();
    }
  	
    public function skipChangeForm() {
        self::setAjaxMode();

        $this->game->skipChangeForm();

        self::ajaxResponse();
    }
  	
    public function exchangeCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->exchangeCard($id);

        self::ajaxResponse();
    }
  	
    public function skipExchangeCard() {
        self::setAjaxMode();

        $this->game->skipExchangeCard();

        self::ajaxResponse();
    }
  	
    public function stayInHibernation() {
        self::setAjaxMode();

        $this->game->stayInHibernation();

        self::ajaxResponse();
    }
  	
    public function leaveHibernation() {
        self::setAjaxMode();

        $this->game->leaveHibernation();

        self::ajaxResponse();
    }
  	
    public function playEvolution() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->playEvolution($id);

        self::ajaxResponse();
    }
  	
    public function useYinYang() {
        self::setAjaxMode();

        $this->game->useYinYang();

        self::ajaxResponse();
    }
  	
    public function putEnergyOnBambooSupply() {
        self::setAjaxMode();

        $this->game->putEnergyOnBambooSupply();

        self::ajaxResponse();
    }
  	
    public function takeEnergyOnBambooSupply() {
        self::setAjaxMode();

        $this->game->takeEnergyOnBambooSupply();

        self::ajaxResponse();
    }
  	
    public function skipCardIsBought() {
        self::setAjaxMode();

        $this->game->skipCardIsBought();

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxDrawEvolution() {
        self::setAjaxMode();

        $this->game->gazeOfTheSphinxDrawEvolution();

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxGainEnergy() {
        self::setAjaxMode();

        $this->game->gazeOfTheSphinxGainEnergy();

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxDiscardEvolution() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->gazeOfTheSphinxDiscardEvolution($id);

        self::ajaxResponse();
    }
  	
    public function gazeOfTheSphinxLoseEnergy() {
        self::setAjaxMode();

        $this->game->gazeOfTheSphinxLoseEnergy();

        self::ajaxResponse();
    }
  	
    public function useChestThumping() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->useChestThumping($id);

        self::ajaxResponse();
    }
  	
    public function skipChestThumping() {
        self::setAjaxMode();

        $this->game->skipChestThumping();

        self::ajaxResponse();
    }
  	
    public function chooseFreezeRayDieFace() {
        self::setAjaxMode();

        $symbol = self::getArg("symbol", AT_posint, true);

        $this->game->chooseFreezeRayDieFace($symbol);

        self::ajaxResponse();
    }
  	
    public function useMiraculousCatch() {
        self::setAjaxMode();

        $this->game->useMiraculousCatch();

        self::ajaxResponse();
    }
  	
    public function buyCardMiraculousCatch() {
        self::setAjaxMode();

        $this->game->buyCardMiraculousCatch();

        self::ajaxResponse();
    }
  	
    public function skipMiraculousCatch() {
        self::setAjaxMode();

        $this->game->skipMiraculousCatch();

        self::ajaxResponse();
    }
  	
    public function playCardDeepDive() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->playCardDeepDive($id);

        self::ajaxResponse();
    }
  	
    public function freezeDie() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->freezeDie($id);

        self::ajaxResponse();
    }
  	
    public function skipFreezeDie() {
        self::setAjaxMode();

        $this->game->skipFreezeDie();

        self::ajaxResponse();
    }
  	
    public function useExoticArms() {
        self::setAjaxMode();

        $this->game->useExoticArms();

        self::ajaxResponse();
    }
  	
    public function skipExoticArms() {
        self::setAjaxMode();

        $this->game->skipExoticArms();

        self::ajaxResponse();
    }
  	
    public function skipBeforeResolveDice() {
        self::setAjaxMode();

        $this->game->skipBeforeResolveDice();

        self::ajaxResponse();
    }
  	
    public function giveTarget() {
        self::setAjaxMode();

        $this->game->giveTarget();

        self::ajaxResponse();
    }
  	
    public function skipGiveTarget() {
        self::setAjaxMode();

        $this->game->skipGiveTarget();

        self::ajaxResponse();
    }

}
