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
        
        $this->applyGiveSymbols([$symbol], $playerId, $activePlayerId, 1000 + KHEPRI_S_REBELLION_CURSE_CARD);

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

    function applyGiveSymbols(array $symbols, int $from, int $to, int $logCardType) {
        $symbolsCount = [];
        foreach($symbols as $symbol) {
            if (!array_key_exists($symbol, $symbolsCount)) {
                $symbolsCount[$symbol] = 0;
            }
            $symbolsCount[$symbol]++;
        }

        foreach($symbolsCount as $symbol => $count) {
            switch($symbol) {
                case 4: 
                    $this->applyDamage($from, $count, 0, $logCardType, $to, 0, 0);
                    $this->applyGetHealth($to, $count, $logCardType, $from);
                    break;
                case 5:
                    $this->applyLoseEnergy($from, $count, $logCardType);
                    $this->applyGetEnergy($to, $count, $logCardType);
                    break;
                case 0:
                    $this->applyLosePoints($from, $count, $logCardType);
                    $this->applyGetPoints($to, $count, $logCardType);
                    break;
                default:
                    throw new \BgaUserException('Invalid symbol');
            }
        }
    }

    function giveSymbols(array $symbols) {
        $this->checkAction('giveSymbols');  

        $playerId = self::getActivePlayerId(); 
        $playerWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();

        $this->applyGiveSymbols($symbols, $playerId, $playerWithGoldenScarab, 1000 + PHARAONIC_SKIN_CURSE_CARD);

        if (in_array(4, $symbols)) {
            // if player gave its last heart
            $this->updateKillPlayersScoreAux();
            $this->eliminatePlayers($playerId);
        }

        $this->gamestate->nextState('next');
    }
}
