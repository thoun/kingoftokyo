<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/evolution-card.php');
require_once(__DIR__.'/../objects/question.php');

use KOT\Objects\EvolutionCard;
use KOT\Objects\Question;

trait EvolutionCardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */  

    function pickEvolutionForDeck(int $id) {
        $this->checkAction('pickEvolutionForDeck');

        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($id));

        if (strpos($card->location, 'mutant') !== 0) {
            throw new \BgaUserException("Card is not selectable");
        }

        $this->evolutionCards->moveCard($id, 'deck'.$playerId);

        $this->notifyPlayer($playerId, 'evolutionPickedForDeck', '', [
            'card' => $card,
        ]);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function skipBeforeStartTurn() {
        // TODOPU find why it blocks $this->checkAction('skipBeforeStartTurn');

        $this->goToState($this->redirectAfterBeforeStartTurn());
    }

    function skipHalfMovePhase() {
        $this->checkAction('skipHalfMovePhase');

        $playerId = $this->getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function applyChooseEvolutionCard(int $playerId, int $id) {
        $topCards = $this->pickEvolutionCards($playerId);
        $card = $this->array_find($topCards, fn($topCard) => $topCard->id == $id);
        if ($card == null) {
            throw new \BgaUserException('Evolution card not available');
        }
        $otherCard = $this->array_find($topCards, fn($topCard) => $topCard->id != $id);

        $this->evolutionCards->moveCard($id, 'hand', $playerId);
        $this->evolutionCards->moveCard($otherCard->id, 'dicard'.$playerId);

        $this->notifNewEvolutionCard($playerId, $card, /*client TODOPU translate(*/'${player_name} ends his rolls with at least 3 [diceHeart] and takes a new Evolution card'/*)*/);
        
    } 

    function chooseEvolutionCard(int $id) {
        $this->checkAction('chooseEvolutionCard');

        $playerId = $this->getActivePlayerId();

        $this->applyChooseEvolutionCard($playerId, $id);

        $nextState = intval($this->getGameStateValue(STATE_AFTER_RESOLVE));
        $this->gamestate->jumpToState($nextState);
    }

    function applyPlayEvolution(int $playerId, EvolutionCard $card) {
        $countMothershipSupportBefore = $this->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);

        $this->evolutionCards->moveCard($card->id, 'table', $playerId);
        
        $damages = $this->applyEvolutionEffects($card, $playerId);

        $this->playEvolutionToTable($playerId, $card);
        
        if (in_array($card->type, $this->AUTO_DISCARDED_EVOLUTIONS)) {
            $this->removeEvolution($playerId, $card, false, 5000);
        }
        
        $this->toggleMothershipSupport($playerId, $countMothershipSupportBefore);

        if ($damages != null && count($damages) > 0) {
            $this->addStackedState();
            $this->goToState(-1, $damages);
        }
    }

    function playEvolution(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($id));

        if (!$this->canPlayEvolution($card->type, $playerId)) {
            throw new \BgaUserException("You can't play this Evolution.");
        }

        $this->applyPlayEvolution($playerId, $card);
    }

    function useYinYang() {
        $this->checkAction('useYinYang');

        $playerId = $this->getActivePlayerId();

        $hasYinYang = $this->isPowerUpExpansion() && $this->countEvolutionOfType($playerId, YIN_YANG_EVOLUTION) > 0;
        if (!$hasYinYang) {
            throw new \BgaUserException("You can't play Yin & Yang without this Evolution.");
        }

        $this->applyYinYang($playerId);

        $this->gamestate->nextState('changeDie');
    }
    
    function useDetachableTail() {
        $this->checkAction('useDetachableTail');

        $playerId = $this->getCurrentPlayerId();

        if ($this->countEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, false, true) == 0) {
            throw new \BgaUserException('No Detachable Tail Evolution');
        }

        if ($this->canLoseHealth($playerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->removePlayerFromSmashedPlayersInTokyo($playerId);

        $card = $this->getEvolutionsOfType($playerId, DETACHABLE_TAIL_EVOLUTION, true, true)[0];

        $this->evolutionCards->moveCard($card->id, 'table', $playerId);

        $this->playEvolutionToTable($playerId, $card, clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'));

        $intervention = $this->getDamageIntervention();
        $this->setInterventionNextState(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix(), 'next', null, $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
    }
  	
    function putEnergyOnBambooSupply() {
        $this->checkAction('putEnergyOnBambooSupply');

        $playerId = $this->getCurrentPlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedEvolution($playerId, BAMBOO_SUPPLY_EVOLUTION);

        $this->setEvolutionTokens($playerId, $unusedBambooSupplyCard, $unusedBambooSupplyCard->tokens + 1);

        $this->setUsedCard(3000 + $unusedBambooSupplyCard->id);

        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }
  	
    function takeEnergyOnBambooSupply() {
        $this->checkAction('takeEnergyOnBambooSupply');

        $playerId = $this->getCurrentPlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedEvolution($playerId, BAMBOO_SUPPLY_EVOLUTION);

        $this->applyGetEnergyIgnoreCards($playerId, $unusedBambooSupplyCard->tokens, 3000 + BAMBOO_SUPPLY_EVOLUTION);
        $this->setEvolutionTokens($playerId, $unusedBambooSupplyCard, 0);

        $this->setUsedCard(3000 + $unusedBambooSupplyCard->id);

        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    function skipCardIsBought() {
        $this->checkAction('skipCardIsBought');

        $playerId = $this->getCurrentPlayerId();

        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function buyCardBamboozle(int $id, int $from) {
        $currentPlayerId = $this->getCurrentPlayerId();
        $activePlayerId = $this->getActivePlayerId();

        $forcedCard = $this->getCardFromDb($this->cards->getCard($id));
        $forbiddenCard = $this->getCardFromDb($this->cards->getCard($this->getGlobalVariable(CARD_BEING_BOUGHT)->cardId));

        $this->notifyAllPlayers('log', /*client TODOPU translate(*/'${player_name} force ${player_name2} to buy ${card_name} instead of ${card_name2}. ${player_name2} cannot buy ${card_name2} this turn'/*)*/, [
            'player_name' => $this->getPlayerName($currentPlayerId),
            'player_name2' => $this->getPlayerName($activePlayerId),
            'card_name' => $forcedCard->type,
            'card_name2' => $forbiddenCard->type,
        ]);

        // applyBuyCard do the redirection
        $this->applyBuyCard($activePlayerId, $id, $from, false);
    }
  	
    public function giveSymbol(int $symbol) {
        $this->checkAction('giveSymbol');  

        $playerId = $this->getCurrentPlayerId(); 

        $question = $this->getQuestion();
        $evolutionPlayerId = $question->args->playerId;
        
        $this->applyGiveSymbols([$symbol], $playerId, $evolutionPlayerId, 3000 + MEGA_PURR_EVOLUTION);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function chooseMimickedEvolution(int $mimickedEvolutionId) {
        $this->checkAction('chooseMimickedEvolution');

        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($mimickedEvolutionId));        
        if ($this->EVOLUTION_CARDS_TYPES[$card->type] != 1) {
            throw new \BgaUserException("You can only mimic Permanent evolutions");
        }

        $this->setMimickedEvolution($playerId, $card);

        $this->goToState(-1);
    }
  	
    public function useChestThumping(int $playerId) {
        $this->checkAction('useChestThumping');

        $this->applyActionLeaveTokyo($playerId, null, true);

        $this->goToState(ST_MULTIPLAYER_LEAVE_TOKYO);
    }
  	
    public function skipChestThumping() {
        $this->checkAction('skipChestThumping');

        $playerId = $this->getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive($playerId, 'resume');
    }
  	
    public function chooseFreezeRayDieFace(int $symbol) {
        $this->checkAction('chooseFreezeRayDieFace');

        $question = $this->getQuestion();
        $evolutionId = $question->args->card->id;
        $evolution = $this->getEvolutionCardFromDb($this->evolutionCards->getCard($evolutionId));
        $this->setEvolutionTokens($this->getActivePlayerId(), $evolution, $symbol, true);

        $this->notifyAllPlayers('log', /*client TODOPU translate(*/'${player_name} choses that ${die_face} will have no effect this turn'/*)*/, [
            'player_name' => $this->getPlayerName($this->getCurrentPlayerId()),
            'die_face' => $this->getDieFaceLogName($symbol, 0),

        ]);

        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }
  	
    public function useMiraculousCatch() {
        $this->checkAction('useMiraculousCatch');

        $playerId = $this->getActivePlayerId();

        $evolution = $this->getFirstUnusedEvolution($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true);
        if ($evolution === null) {
            throw new \BgaUserException("No unused Miraculous catch");
        }
        if (intval($this->cards->countCardInLocation('discard')) === 0) {
            throw new \BgaUserException("No cards in discard pile");
        }
        
        if ($evolution->location === 'hand') {
            $this->applyPlayEvolution($playerId, $evolution);
        }

        $this->cards->shuffle('discard');
        $card = $this->getCardFromDb($this->cards->getCardOnTop('discard'));

        $question = new Question(
            'MiraculousCatch',
            /* client TODOPU translate(*/'${actplayer} can buy ${card_name} from the discard pile for 1[Energy] less'/*)*/,
            /* client TODOPU translate(*/'${you} can buy ${card_name} from the discard pile for 1[Energy] less'/*)*/,
            [$playerId],
            ST_QUESTIONS_BEFORE_START_TURN,
            [
                'card' => $card,
                'cost' => $this->getCardCost($playerId, $card->type) - 1,
                '_args' => [
                    'card_name' => $card->type,
                ],
            ]
        );
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function buyCardMiraculousCatch() {
        $this->checkAction('buyCardMiraculousCatch');

        $playerId = $this->getActivePlayerId();
        $evolution = $this->getFirstUnusedEvolution($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true);

        $card = $this->getCardFromDb($this->cards->getCardOnTop('discard'));

        $this->setUsedCard(3000 + $evolution->id);
        $this->cards->shuffle('discard');

        $playerId = $this->getActivePlayerId();
        $this->applyBuyCard(
            $playerId,
            $card->id,
            0,
            false,
            $this->getCardCost($playerId, $card->type) - 1
        );

        $this->goToState(ST_PLAYER_BUY_CARD);
    }

    public function skipMiraculousCatch() {
        $this->checkAction('skipMiraculousCatch');

        $playerId = $this->getActivePlayerId();
        $evolution = $this->getFirstUnusedEvolution($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true);

        $this->setUsedCard(3000 + $evolution->id);
        $this->cards->shuffle('discard');

        $this->goToState(ST_PLAYER_BUY_CARD);
    }

    public function playCardDeepDive(int $id) {
        $this->checkAction('playCardDeepDive');

        $playerId = $this->getActivePlayerId();
        $card = $this->getCardFromDb($this->cards->getCard($id));

        $damages = $this->applyPlayCard($playerId, $card);

        $this->goToState(-1, $damages);
    }
}
