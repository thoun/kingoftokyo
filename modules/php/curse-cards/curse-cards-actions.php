<?php

namespace KOT\States;

trait CurseCardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
   
  	
    public function giveSymbolToActivePlayer(int $symbol) {
        $this->checkAction('giveSymbolToActivePlayer');  

        $playerId = self::getCurrentPlayerId(); 
        $activePlayerId = self::getActivePlayerId(); 
        $logCardType = 1000 + KHEPRI_S_REBELLION_CURSE_CARD;
        
        switch($symbol) {
            case 4: 
                $this->applyDamage($playerId, 1, 0, $logCardType, $activePlayerId, 0, 0);
                $this->applyGetHealth($activePlayerId, 1, $logCardType, $playerId);
                break;
            case 5:
                $this->applyLoseEnergy($playerId, 1, $logCardType);
                $this->applyGetEnergy($activePlayerId, 1, $logCardType);
                break;
            case 0:
                $this->applyLosePoints($playerId, 1, $logCardType);
                $this->applyGetPoints($activePlayerId, 1, $logCardType);
                break;
            default:
                throw new \BgaUserException('Invalid symbol');
        }

        $this->gamestate->setPlayerNonMultiactive($playerId, '');

        if ($symbol == 4) {
            // if player gave its last heart
            $this->updateKillPlayersScoreAux();
            $this->eliminatePlayers($playerId);
        }
    }

    function discardDie(int $dieId) {
        $this->checkAction('discardDie');  

        $this->applyDiscardDie($dieId);

        $this->gamestate->nextState('next');
    }

    function discardKeepCard(int $cardId) {
        $this->checkAction('discardKeepCard');   
        $playerId = self::getActivePlayerId(); 

        $card = $this->getCardFromDb($this->cards->getCard($cardId));
        $this->applyDiscardKeepCard($playerId, $card);

        $this->gamestate->nextState('next');
    }

    function giveGoldenScarab(int $playerId) {
        $this->checkAction('giveGoldenScarab');   
        
        $this->changeGoldenScarabOwner($playerId);

        $this->gamestate->nextState('next');
    }
}
