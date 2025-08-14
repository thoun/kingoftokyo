<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/damage.php');

use KOT\Objects\Damage;

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

        $playerId = $this->getCurrentPlayerId(); 
        $activePlayerId = $this->getActivePlayerId(); 
        
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

        $this->anubisExpansion->applyDiscardDie($dieId);

        $this->gamestate->nextState('next');
    }

    function discardKeepCard(int $cardId) {
        $this->checkAction('discardKeepCard');   
        $playerId = $this->getActivePlayerId(); 

        $card = $this->powerCards->getItemById($cardId);
        $this->anubisExpansion->applyDiscardKeepCard($playerId, $card);

        $this->gamestate->nextState('next');
    }

    function giveGoldenScarab(int $playerId) {
        $this->checkAction('giveGoldenScarab');   
        
        $this->anubisExpansion->changeGoldenScarabOwner($playerId);

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
                    $damage = new Damage($from, $count, 0, $logCardType);
                    $this->applyDamage($damage);
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

        $playerId = $this->getActivePlayerId(); 
        $playerWithGoldenScarab = $this->anubisExpansion->getPlayerIdWithGoldenScarab();

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

        $playerId = $this->getActivePlayerId(); 

        $this->setGameStateValue(RAGING_FLOOD_EXTRA_DIE_SELECTED, 1);

        $dice = $this->getPlayerRolledDice($playerId, false, false, false);
        $die = end($dice);
        $dieId = $die->id; 
        $this->DbQuery("UPDATE dice SET `dice_value` = $face WHERE dice_id = $dieId");

        $this->notifyAllPlayers("selectExtraDie", clienttranslate('${player_name} choses ${die_face} as the extra die'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'die_face' => $this->getDieFaceLogName($face, $die->type),
        ]);

        $this->gamestate->nextState('next');
    }
  	
    public function falseBlessingReroll(int $dieId) {
        $this->checkAction('falseBlessingReroll'); 

        if ($dieId == intval($this->getGameStateValue(FALSE_BLESSING_USED_DIE))) {
            throw new \BgaUserException(self::_('You already made an action for this die'));
        }

        $playerId = $this->getActivePlayerId(); 
        
        $die = $this->getDieById($dieId);
        $value = bga_rand(1, 6);
        $this->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
        $this->DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$dieId);

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        $oldValue = $die->value;

        $this->notifyAllPlayers("changeDie", $message, [
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

        if ($dieId == intval($this->getGameStateValue(FALSE_BLESSING_USED_DIE))) {
            throw new \BgaUserException(self::_('You already made an action for this die'));
        }

        $this->anubisExpansion->applyDiscardDie($dieId);

        $this->endFalseBlessingAction($dieId);
    }

    private function endFalseBlessingAction(int $dieId) {
        if (!boolval($this->getGameStateValue(FALSE_BLESSING_USED_DIE))) {
            $this->setGameStateValue(FALSE_BLESSING_USED_DIE, $dieId);
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

        $playerId = $this->getCurrentPlayerId();
        $activePlayerId = $this->getActivePlayerId();

        foreach($diceIds as $dieId) {
            if ($dieId > 0) {
                $die = $this->getDieById($dieId);
                $value = bga_rand(1, 6);
                $this->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
                $this->DbQuery("UPDATE dice SET `dice_value` = ".$value.", `rolled` = true where `dice_id` = ".$dieId);

                $message = clienttranslate('${player_name} force ${player_name2} to reroll ${die_face_before} die and obtained ${die_face_after}');
                $oldValue = $die->value;

                $this->notifyAllPlayers("changeDie", $message, [
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

        $this->gamestate->setPlayerNonMultiactive($playerId, 'end');
    }

    
  	
    public function gazeOfTheSphinxDrawEvolution() {
        $this->checkAction('gazeOfTheSphinxDrawEvolution'); 

        $playerId = $this->getActivePlayerId();

        $this->drawEvolution($playerId);

        $this->goToState(ST_RESOLVE_DICE);
    }
  	
    public function gazeOfTheSphinxGainEnergy() {
        $this->checkAction('gazeOfTheSphinxGainEnergy'); 

        $playerId = $this->getActivePlayerId();

        $this->applyGetEnergy($playerId, 3, 1000 + GAZE_OF_THE_SPHINX_CURSE_CARD);

        $this->goToState(ST_RESOLVE_DICE);
    }
  	
    public function gazeOfTheSphinxDiscardEvolution(int $id) {
        $this->checkAction('gazeOfTheSphinxDiscardEvolution');

        $playerId = $this->getActivePlayerId(); 

        $card = $this->getEvolutionCardById($id);

        $this->removeEvolution($playerId, $card);

        $this->goToState(ST_RESOLVE_DICE);
    }
  	
    public function gazeOfTheSphinxLoseEnergy() {
        $this->checkAction('gazeOfTheSphinxLoseEnergy'); 

        $playerId = $this->getActivePlayerId(); 

        $this->applyLoseEnergy($playerId, 3, 1000 + GAZE_OF_THE_SPHINX_CURSE_CARD);

        $this->goToState(ST_RESOLVE_DICE);
    }
}
