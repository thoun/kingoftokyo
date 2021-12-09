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

        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');

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

    function selectExtraDie(int $face) {
        $this->checkAction('selectExtraDie');  

        $playerId = self::getActivePlayerId(); 

        $dice = $this->getPlayerRolledDice($playerId, false, false, false);
        $dieId = end($dice)->id; 
        self::DbQuery("UPDATE dice SET `dice_value` = $face WHERE dice_id = $dieId");        

        $this->gamestate->nextState('next');
    }
  	
    public function falseBlessingReroll(int $dieId) {
        $this->checkAction('falseBlessingReroll'); 

        if ($dieId == intval(self::getGameStateValue(FALSE_BLESSING_USED_DIE))) {
            throw new \BgaUserException(/* TODOAN self::_(*/'You already made an action for this die'/*)*/);
        }

        $playerId = self::getActivePlayerId(); 
        
        $die = $this->getDieById($dieId);
        $value = bga_rand(1, 6);
        self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
        self::DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$dieId);

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        $oldValue = $die->value;

        self::notifyAllPlayers("changeDie", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => 1000 + FALSE_BLESSING_CURSE_CARD,
            'dieId' => $die->id,
            'toValue' => $value,
            'roll' => true,
            'die_face_before' => $this->getDieFaceLogName($oldValue, $die->type),
            'die_face_after' => $this->getDieFaceLogName($value, $die->type),
        ]);

        $this->endFalseBlessingAction($dieId);
    }
  	
    public function falseBlessingDiscard(int $dieId) {
        $this->checkAction('falseBlessingDiscard'); 

        if ($dieId == intval(self::getGameStateValue(FALSE_BLESSING_USED_DIE))) {
            throw new \BgaUserException(/* TODOAN self::_(*/'You already made an action for this die'/*)*/);
        }

        $this->applyDiscardDie($dieId);

        $this->endFalseBlessingAction($dieId);
    }

    private function endFalseBlessingAction(int $dieId) {
        if (!boolval(self::getGameStateValue(FALSE_BLESSING_USED_DIE))) {
            self::setGameStateValue(FALSE_BLESSING_USED_DIE, $dieId);
        } else {
            $this->gamestate->nextState('next');
        }
    }
  	
    public function falseBlessingSkip() {
        $this->checkAction('falseBlessingSkip'); 

        $this->gamestate->nextState('next');
    }

    function rerollDice(array $diceIds) {
        $this->checkAction('rerollDice'); 

        $playerId = self::getCurrentPlayerId();
        $activePlayerId = self::getActivePlayerId();

        foreach($diceIds as $dieId) {
            if ($dieId > 0) {
                $die = $this->getDieById($dieId);
                $value = bga_rand(1, 6);
                self::DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
                self::DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$dieId);

                $message = /*client TODOAN translate(*/'${player_name} force ${player_name2} to reroll ${die_face_before} die and obtained ${die_face_after}'/*)*/;
                $oldValue = $die->value;

                self::notifyAllPlayers("changeDie", $message, [
                    'playerId' => $playerId,
                    'player_name' => $this->getPlayerName($playerId),
                    'player_name2' => $this->getPlayerName($activePlayerId),
                    'dieId' => $die->id,
                    'toValue' => $value,
                    'roll' => true,
                    'die_face_before' => $this->getDieFaceLogName($oldValue, $die->type),
                    'die_face_after' => $this->getDieFaceLogName($value, $die->type),
                ]);
            }
        }

        $this->gamestate->setPlayerNonMultiactive($playerId, '');
    }
}
