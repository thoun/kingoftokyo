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
  
  
  class action_kingoftokyo extends APP_GameAction
  { 
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
  	
    public function rethrow() {
        self::setAjaxMode();

        $diceIds = self::getArg("diceIds", AT_numberlist, true);

        $this->game->rethrowDice($diceIds);

        self::ajaxResponse();
    }
  	
    public function buyEnergyDrink() {
        self::setAjaxMode();

        $this->game->buyEnergyDrink();

        self::ajaxResponse();
    }
  	
    public function useSmokeCloud() {
        self::setAjaxMode();

        $this->game->useSmokeCloud();

        self::ajaxResponse();
    }

    public function rethrow3() {
        self::setAjaxMode();

        $this->game->rethrow3();

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
  	
    public function resolve() {
        self::setAjaxMode();

        $this->game->resolveDice();

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
  	
    public function buyCard() {
        self::setAjaxMode();

        $id = self::getArg("id", AT_posint, true);
        $from = self::getArg("from", AT_posint, true);

        $this->game->buyCard($id, $from);

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

  }
  

