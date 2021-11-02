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
  	
    public function chooseInitialCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->chooseInitialCard($id);

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
  	
    public function useRapidHealing() {
        self::setAjaxMode();

        $this->game->useRapidHealing();

        self::ajaxResponse();
    }
  	
    public function useRapidHealingSync() {
        self::setAjaxMode();

        $this->game->useRapidHealingSync();

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

    public function applyHeartDieChoices() {
        self::setAjaxMode();

        $heartDieChoices = json_decode(base64_decode(self::getArg("selections", AT_base64, true)));

        $this->game->applyHeartDieChoices($heartDieChoices);

        self::ajaxResponse();
    }
  	
    public function stay() {
        self::setAjaxMode();

        $this->game->stayInTokyo();

        self::ajaxResponse();
    }
  	
    public function leave() {
        self::setAjaxMode();

        $this->game->actionLeaveTokyo();

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

        $this->game->buyCard($id, $from);

        self::ajaxResponse();
    }
  	
    public function chooseMimickedCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->chooseMimickedCard($id);

        self::ajaxResponse();
    }

    public function changeMimickedCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);

        $this->game->changeMimickedCard($id);

        self::ajaxResponse();
    }
  	
    public function renew() {
        self::setAjaxMode();

        $this->game->renewCards();

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
  	
    public function skipChangeMimickedCard() {
        self::setAjaxMode();

        $this->game->skipChangeMimickedCard();

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

}
