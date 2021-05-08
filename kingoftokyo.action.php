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

        $dicesIds = self::getArg("dicesIds", AT_numberlist, true);

        $this->game->rethrowDices($dicesIds);

        self::ajaxResponse();
    }
  	
    public function buyEnergyDrink() {
        self::setAjaxMode();

        $this->game->buyEnergyDrink();

        self::ajaxResponse();
    }

    public function rethrow3() {
        self::setAjaxMode();

        $this->game->rethrow3();

        self::ajaxResponse();
    }
  	
    public function resolve() {
        self::setAjaxMode();

        $this->game->resolveDices();

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

        $this->game->buyCard($id);

        self::ajaxResponse();
    }
  	
    public function renew() {
        self::setAjaxMode();

        $this->game->renewCards();

        self::ajaxResponse();
    }
  	
    public function endTurn() {
        self::setAjaxMode();

        $this->game->endTurn();

        self::ajaxResponse();
    }

  }
  

