<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/question.php');
require_once(__DIR__.'/../Objects/damage.php');

use Bga\GameFramework\Actions\Types\BoolParam;
use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use KOT\Objects\Question;
use KOT\Objects\Damage;

trait EvolutionCardsActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */  

    function actPickEvolutionForDeck(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardById($id);

        if (strpos($card->location, 'mutant') !== 0) {
            throw new \BgaUserException("Card is not selectable");
        }

        $this->powerUpExpansion->evolutionCards->moveItem($card, 'deck'.$playerId);

        $this->notifyPlayer($playerId, 'evolutionPickedForDeck', '', [
            'card' => $card,
        ]);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function actSkipBeforeStartTurn() {
        $this->goToState($this->redirectAfterBeforeStartTurn());
    }

    function actSkipBeforeEndTurn() {
        $this->goToState($this->redirectAfterBeforeEndTurn());
    }

    function actSkipBeforeEnteringTokyo() {
        $playerId = $this->getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function actSkipAfterEnteringTokyo() {
        $playerId = $this->getCurrentPlayerId();

        $this->goToState($this->redirectAfterEnterTokyo($playerId));
    }

    function applyChooseEvolutionCard(int $playerId, int $id, bool $init) {
        $topCards = $this->pickEvolutionCards($playerId);
        $card = $this->array_find($topCards, fn($topCard) => $topCard->id == $id);
        if ($card == null) {
            throw new \BgaUserException('Evolution card not available');
        }
        $otherCard = $this->array_find($topCards, fn($topCard) => $topCard->id != $id);

        $this->powerUpExpansion->evolutionCards->moveItem($card, 'hand', $playerId);
        $this->powerUpExpansion->evolutionCards->moveItem($otherCard, 'discard'.$playerId);

        $this->incStat(1, 'picked'.$this->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);

        $message = $init ? '' : clienttranslate('${player_name} ends his rolls with at least 3 [diceHeart] and takes a new Evolution card');
        $this->notifNewEvolutionCard($playerId, $card, $message);
        
    } 

    function actChooseEvolutionCard(int $id) {
        $playerId = $this->getActivePlayerId();

        $this->applyChooseEvolutionCard($playerId, $id, false);

        $nextState = intval($this->getGameStateValue(STATE_AFTER_RESOLVE));
        $this->gamestate->jumpToState($nextState);
    }

    function applyPlayEvolution(int $playerId, EvolutionCard $card) {
        $countMothershipSupportBefore = $this->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);

        $this->powerUpExpansion->evolutionCards->moveItem($card, 'table', $playerId);

        $this->playEvolutionToTable($playerId, $card);
        
        $damages = $this->applyEvolutionEffects($card, $playerId);
        $this->updateCancelDamageIfNeeded($playerId);
        
        if (in_array($card->type, $this->AUTO_DISCARDED_EVOLUTIONS)) {
            $this->removeEvolution($playerId, $card, false, 5000);
        }
        
        $this->toggleMothershipSupport($playerId, $countMothershipSupportBefore);

        if ($damages != null && count($damages) > 0) {
            $this->addStackedState();
            $this->goToState(-1, $damages);
        }
    }

    function actPlayEvolution(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardById($id);

        if ($card->location != 'hand') {
            throw new \BgaUserException('Evolution card is not in your hand');
        }

        $this->checkCanPlayEvolution($card->type, $playerId);

        $this->applyPlayEvolution($playerId, $card);

        // if the player has no more evolution cards, we skip the state for him
        if ($this->powerUpExpansion->evolutionCards->countItemsInLocation('hand', $playerId) == 0) {
            $stateId = intval($this->gamestate->state_id());

            switch($stateId) {
                case ST_PLAYER_BEFORE_START_TURN:
                    $this->skipBeforeStartTurn(true);
                    break;
                case ST_PLAYER_BEFORE_RESOLVE_DICE:
                    $this->skipBeforeResolveDice(true);
                    break;
                case ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO:
                    $this->skipBeforeEnteringTokyo(true);
                    break;
                case ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT:
                    $this->skipCardIsBought(true);
                    break;
                case ST_MULTIPLAYER_BEFORE_END_TURN:
                    $this->skipBeforeEndTurn(true);
                    break;
            }
        }
    }

    function actGiveGiftEvolution(int $id, int $toPlayerId) {
        $fromPlayerId = $this->getCurrentPlayerId();
        $evolution = $this->getEvolutionCardById($id);

        $this->giveEvolution($fromPlayerId, $toPlayerId, $evolution);

        $this->goToState(ST_PLAYER_STEAL_COSTUME_CARD_OR_GIVE_GIFT_EVOLUTION);
    }


    function actUseYinYang() {
        $playerId = $this->getActivePlayerId();

        $hasYinYang = $this->powerUpExpansion->isActive() && $this->countEvolutionOfType($playerId, YIN_YANG_EVOLUTION) > 0;
        if (!$hasYinYang) {
            throw new \BgaUserException("You can't play Yin & Yang without this Evolution.");
        }

        $this->applyYinYang($playerId);

        $this->gamestate->nextState('changeDie');
    }
    
    function actUseInvincibleEvolution(int $evolutionType) {
        $playerId = $this->getCurrentPlayerId();

        if (!in_array($evolutionType, [DETACHABLE_TAIL_EVOLUTION, RABBIT_S_FOOT_EVOLUTION]) || $this->countEvolutionOfType($playerId, $evolutionType, false, true) == 0) {
            throw new \BgaUserException('No Detachable Tail / Rabbits Foot Evolution');
        }

        if ($this->canLoseHealth($playerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->removePlayerFromSmashedPlayersInTokyo($playerId);

        $card = $this->getEvolutionsOfType($playerId, $evolutionType, true, true)[0];

        $this->powerUpExpansion->evolutionCards->moveItem($card, 'table', $playerId);

        $this->playEvolutionToTable($playerId, $card, clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'));

        $intervention = $this->getDamageIntervention();
        $this->reduceInterventionDamages($playerId, $intervention, -1);
        $this->resolveRemainingDamages($intervention, true, false);
    }
    
    function actUseCandyEvolution() {
        $playerId = $this->getCurrentPlayerId();

        if ($this->countEvolutionOfType($playerId, CANDY_EVOLUTION, true, true) == 0) {
            throw new \BgaUserException('No Candy Evolution');
        }

        if ($this->canLoseHealth($playerId, 999) != null) {
            throw new \BgaUserException('You already invincible');
        }

        $this->removePlayerFromSmashedPlayersInTokyo($playerId);

        $evolution = $this->getEvolutionsOfType($playerId, CANDY_EVOLUTION, true, true)[0];

        $this->powerUpExpansion->evolutionCards->moveItem($evolution, 'table', $playerId);

        $this->playEvolutionToTable($playerId, $evolution, clienttranslate('${player_name} uses ${card_name} to not lose [Heart] this turn'));

        $intervention = $this->getDamageIntervention();
        $damageDealerId = null;
        $clawDamage = null;
        foreach($intervention->damages as $damage) {
            if ($damage->playerId == $playerId) {
                $damageDealerId = $damage->damageDealerId;
                $clawDamage = $damage->clawDamage;
            }
        }

        if ($clawDamage === null || $damageDealerId === null || $damageDealerId === 0) {
            throw new \BgaUserException('You can only use it when wounded');
        }

        $fromPlayerId = $playerId;
        $toPlayerId = $damageDealerId;
        $this->removeEvolution($fromPlayerId, $evolution);
        $this->powerUpExpansion->evolutionCards->moveItem($evolution, 'hand', $toPlayerId);
        $message = /*client TODOPUHA translate*/('${player_name2} use ${card_name} to avoid damages and gives ${card_name} to ${player_name}');
        $this->notifNewEvolutionCard($toPlayerId, $evolution, $message, [
            'card_name' => 3000 + $evolution->type,
            'player_name2' => $this->getPlayerName($fromPlayerId),
        ]);

        $this->reduceInterventionDamages($playerId, $intervention, -1);
        $this->resolveRemainingDamages($intervention, true, false);
    }
  	
    function actPutEnergyOnBambooSupply() {
        $playerId = $this->getCurrentPlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedEvolution($playerId, BAMBOO_SUPPLY_EVOLUTION);

        if ($unusedBambooSupplyCard) {
            $this->setEvolutionTokens($playerId, $unusedBambooSupplyCard, $unusedBambooSupplyCard->tokens + 1);
            $this->setUsedCard(3000 + $unusedBambooSupplyCard->id);
        }

        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }
  	
    function actTakeEnergyOnBambooSupply() {
        $playerId = $this->getCurrentPlayerId();

        $unusedBambooSupplyCard = $this->getFirstUnusedEvolution($playerId, BAMBOO_SUPPLY_EVOLUTION);

        $this->applyGetEnergyIgnoreCards($playerId, $unusedBambooSupplyCard->tokens, 3000 + BAMBOO_SUPPLY_EVOLUTION);
        $this->setEvolutionTokens($playerId, $unusedBambooSupplyCard, 0);

        $this->setUsedCard(3000 + $unusedBambooSupplyCard->id);

        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    function actSkipCardIsBought() {
        $playerId = $this->getCurrentPlayerId();

        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function actBuyCardBamboozle(int $id, int $from) {
        $currentPlayerId = $this->getCurrentPlayerId();
        $activePlayerId = $this->getActivePlayerId();

        $forcedCard = $this->powerCards->getItemById($id);
        $forbiddenCard = $this->powerCards->getItemById($this->getGlobalVariable(CARD_BEING_BOUGHT)->cardId);

        $this->notifyAllPlayers('log', clienttranslate('${player_name} force ${player_name2} to buy ${card_name} instead of ${card_name2}. ${player_name2} cannot buy ${card_name2} this turn'), [
            'player_name' => $this->getPlayerName($currentPlayerId),
            'player_name2' => $this->getPlayerName($activePlayerId),
            'card_name' => $forcedCard->type,
            'card_name2' => $forbiddenCard->type,
        ]);

        // applyBuyCard do the redirection
        $this->applyBuyCard($activePlayerId, $id, $from);
    }
  	
    public function actGiveSymbol(int $symbol) {
        $playerId = $this->getCurrentPlayerId(); 

        $question = $this->getQuestion();
        $evolutionPlayerId = $question->args->playerId;
        
        $this->applyGiveSymbols([$symbol], $playerId, $evolutionPlayerId, 3000 + $question->args->card->type);

        if ($question->args->card->type == WORST_NIGHTMARE_EVOLUTION) {
            $this->setUsedCard(3000 + $question->args->card->id);
        }

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }

    function actChooseMimickedEvolution(#[IntParam(name: 'id')] int $mimickedEvolutionId) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->getEvolutionCardById($mimickedEvolutionId);
        if ($this->EVOLUTION_CARDS_TYPES[$card->type] != 1) {
            throw new \BgaUserException("You can only mimic Permanent evolutions");
        }
        if ($card->type === ICY_REFLECTION_EVOLUTION) {
            throw new \BgaUserException("You cannot mimic Icy Reflection");
        }

        $this->setMimickedEvolution($playerId, $card);

        $this->goToState(-1);
    }
  	
    public function actUseChestThumping(#[IntParam(name: 'id')] int $playerId) {
        $this->leaveTokyo($playerId);
        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'resume');

        $this->checkOnlyChestThumpingRemaining();
    }
  	
    public function actSkipChestThumping() {
        $playerId = $this->getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive($playerId, 'resume');
    }
  	
    public function actChooseFreezeRayDieFace(int $symbol) {
        $question = $this->getQuestion();
        $evolutionId = $question->args->card->id;
        $evolution = $this->getEvolutionCardById($evolutionId);
        $this->setEvolutionTokens($this->getActivePlayerId(), $evolution, $symbol, true);

        $this->notifyAllPlayers('log', clienttranslate('${player_name} chooses that ${die_face} will have no effect this turn'), [
            'player_name' => $this->getPlayerName($this->getCurrentPlayerId()),
            'die_face' => $this->getDieFaceLogName($symbol, 0),
        ]);

        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }
  	
    public function actUseMiraculousCatch() {
        $playerId = $this->getActivePlayerId();

        $evolution = $this->getFirstUnusedEvolution($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true);
        if ($evolution === null) {
            throw new \BgaUserException("No unused Miraculous catch");
        }
        if ($this->powerCards->countItemsInLocation('discard') === 0) {
            throw new \BgaUserException("No cards in discard pile");
        }
        
        if ($evolution->location === 'hand') {
            $this->applyPlayEvolution($playerId, $evolution);
        }

        $this->powerCards->shuffle('discard');
        $card = $this->powerCards->getCardOnTop('discard');

        $cost = $this->getCardCost($playerId, $card->type) - 1;
        $canUseSuperiorAlienTechnology = $card->type < 100 && $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, true, true) > 0 && count($this->getSuperiorAlienTechnologyTokens($playerId)) < 3 * $this->countEvolutionOfType($playerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION);

        $question = new Question(
            'MiraculousCatch',
            clienttranslate('${actplayer} can buy ${card_name} from the discard pile for 1[Energy] less'),
            clienttranslate('${you} can buy ${card_name} from the discard pile for 1[Energy] less'),
            [$playerId],
            ST_QUESTIONS_BEFORE_START_TURN,
            [
                'card' => $card,
                'cost' => $cost,
                'costSuperiorAlienTechnology' => $canUseSuperiorAlienTechnology ? ceil($cost / 2) : null,
                '_args' => [
                    'card_name' => $card->type,
                ],
            ]
        );
        $this->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$playerId], 'next', true);

        $this->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function actBuyCardMiraculousCatch(bool $useSuperiorAlienTechnology) {
        $playerId = $this->getCurrentPlayerId();
        $evolution = $this->getFirstUnusedEvolution($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true);

        $card = $this->powerCards->getCardOnTop('discard');

        $this->setUsedCard(3000 + $evolution->id);
        $this->powerCards->shuffle('discard');

        $cost = $this->getCardCost($playerId, $card->type) - 1;        
        if ($useSuperiorAlienTechnology) {
            $cost = ceil($cost / 2);
        }

        // applyBuyCard do the redirection
        $this->applyBuyCard(
            $playerId,
            $card->id,
            0,
            $cost,
            $useSuperiorAlienTechnology,
            false,
        );
    }

    public function skipMiraculousCatch() {
        $this->checkAction('skipMiraculousCatch');

        $playerId = $this->getCurrentPlayerId();
        $evolution = $this->getFirstUnusedEvolution($playerId, MIRACULOUS_CATCH_EVOLUTION, true, true);

        $this->setUsedCard(3000 + $evolution->id);
        $this->powerCards->shuffle('discard');

        $this->goToState(ST_PLAYER_BUY_CARD);
    }

    public function actPlayCardDeepDive(int $id) {
        $playerId = $this->getCurrentPlayerId();
        $card = $this->powerCards->getItemById($id);

        $evolutions = $this->getEvolutionCardsByType(DEEP_DIVE_EVOLUTION);
        foreach($evolutions as $evolution) {
            $this->removeEvolution($playerId, $evolution);
        }

        $damages = $this->applyPlayCard($playerId, $card);

        // move other cards to bottom deck
        $question = $this->getQuestion();
        $cards = $this->powerCards->getItemsByIds(Arrays::map($question->args->cards, fn($card) => $card->id));
        $otherCards = Arrays::filter($cards, fn($otherCard) => $otherCard->id != $card->id);
        $this->DbQuery("UPDATE `card` SET `card_location_arg` = card_location_arg + ".count($otherCards)." WHERE `card_location` = 'deck'");
        foreach($otherCards as $index => $otherCard) {
            $this->powerCards->moveItem($otherCard, 'deck', $index);
        }

        $mimic = false;
        if ($card->type == MIMIC_CARD) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $pId) {
                $cardsOfPlayer = $this->powerCards->getPlayer($pId);
                $countAvailableCardsForMimic += Arrays::count($cardsOfPlayer, fn($card) => $card->type != MIMIC_CARD && $card->type < 100);
            }

            $mimic = $countAvailableCardsForMimic > 0;
        }

        if ($mimic) {
            $this->goToMimicSelection($playerId, MIMIC_CARD, -1);
        } else {
            $this->goToState(-1, $damages);
        }        
    }
  	
    public function actFreezeDie(int $id) {
        $playerId = $this->getCurrentPlayerId();

        if ($this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException("Not enough energy");
        }
        $this->applyLoseEnergy($playerId, 1, 0);

        $this->setGameStateValue(ENCASED_IN_ICE_DIE_ID, $id);

        $die = $this->getDieById($id);
        $this->notifyAllPlayers('log', clienttranslate('${player_name} freeze die ${die_face}'), [
            'player_name' => $this->getPlayerName($playerId),
            'die_face' => $this->getDieFaceLogName($die->value, $die->type),
        ]);

        $this->goToState($this->redirectAfterPrepareResolveDice());
    }
  	
    public function actSkipFreezeDie() {
        $this->goToState($this->redirectAfterPrepareResolveDice());
    }

    
  	
    public function actUseExoticArms() {
        $playerId = $this->getCurrentPlayerId();

        if ($this->getPlayerEnergy($playerId) < 2) {
            throw new \BgaUserException("Not enough energy");
        }
        $this->applyLoseEnergy($playerId, 2, 0);

        $question = $this->getQuestion();
        $evolutionId = $question->args->card->id;
        $evolution = $this->getEvolutionCardById($evolutionId);
        $this->setEvolutionTokens($this->getActivePlayerId(), $evolution, 2);

        $this->setUsedCard(3000 + $evolutionId);
        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }
  	
    public function actSkipExoticArms() {
        $question = $this->getQuestion();
        $evolutionId = $question->args->card->id;

        $this->setUsedCard(3000 + $evolutionId);
        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }
  	
    public function actSkipBeforeResolveDice() {
        $this->goToState($this->redirectAfterBeforeResolveDice());
    }    
  	
    public function actGiveTarget() {
        $playerId = $this->getCurrentPlayerId();

        $question = $this->getQuestion();
        $this->notifyAllPlayers('giveTarget', clienttranslate('${player_name} gives target to ${player_name2}'), [
            'playerId' => $question->args->playerId,
            'previousOwner' => intval($this->getGameStateValue(TARGETED_PLAYER)),
            'player_name' => $this->getPlayerName($playerId),
            'player_name2' => $this->getPlayerName($question->args->playerId),
        ]);
        $this->setGameStateValue(TARGETED_PLAYER, $question->args->playerId);

        foreach($question->playersIds as $pId) {
            $this->gamestate->setPlayerNonMultiactive($pId, 'next');
        }
    }
  	
    public function actSkipGiveTarget() {
        $playerId = $this->getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }
  	
    public function actUseLightningArmor() {
        $playerId = $this->getCurrentPlayerId();

        $question = $this->getQuestion();
        $damageAmountForPlayer = ((array)$question->args->damageAmountByPlayer)[$playerId];
        $damageDealerIdForPlayer = ((array)$question->args->damageDealerIdByPlayer)[$playerId];
        
        $dice = [];
        for ($i=0; $i<$damageAmountForPlayer; $i++) {
            $face = bga_rand(1, 6);
            $newDie = new \stdClass(); // Dice-like
            $newDie->value = $face;
            $newDie->rolled = true;
            $dice[] = $newDie;
        }
        $rolledDiceValues = array_map(fn($die) => $die->value, $dice);
        $smashCount = count(array_values(array_filter($rolledDiceValues, fn($face) => $face === 6)));

        $diceStr = '';
        foreach ($rolledDiceValues as $dieValue) {
            $diceStr .= $this->getDieFaceLogName($dieValue, 0);
        }

        if ($damageDealerIdForPlayer) {
            $this->notifyAllPlayers("useLightningArmor", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and makes ${player_name2} lose ${damage}[Heart]'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'player_name2' => $this->getPlayerName($damageDealerIdForPlayer),
                'card_name' => 3000 + LIGHTNING_ARMOR_EVOLUTION,
                'damage' => $smashCount,
                'diceValues' => $dice,
                'dice' => $diceStr,
            ]);
            if ($smashCount > 0) {
                $damage = new Damage($damageDealerIdForPlayer, $smashCount, $playerId, 3000 + LIGHTNING_ARMOR_EVOLUTION);
                $this->applyDamage($damage);
            }
        } else {
            $this->warn('useLightningArmor damageDealerIdForPlayer is null');
            $this->dump('$question->args', $question->args);
        }

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }
  	
    public function actSkipLightningArmor() {
        $playerId = $this->getCurrentPlayerId();

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }
  	
    public function actAnswerEnergySword(bool $use) {
        $playerId = $this->getCurrentPlayerId();

        $unusedEvolutionCard = $this->getFirstUnusedEvolution($playerId, ENERGY_SWORD_EVOLUTION);
        $this->setUsedCard(3000 + $unusedEvolutionCard->id);

        if ($use) {
            if ($this->getPlayerEnergy($playerId) < 2) {
                throw new \BgaUserException("Not enough energy");
            }
            $this->applyLoseEnergy($playerId, 2, 0);
            $this->setEvolutionTokens($playerId, $unusedEvolutionCard, 2, true);
        } else {
            $this->setEvolutionTokens($playerId, $unusedEvolutionCard, 0, true);
        }
        
        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }
  	
    public function actAnswerSunkenTemple(bool $use) {
        $playerId = $this->getCurrentPlayerId();

        $unusedEvolutionCard = $this->getFirstUnusedEvolution($playerId, SUNKEN_TEMPLE_EVOLUTION);
        $this->setUsedCard(3000 + $unusedEvolutionCard->id);

        if ($use) {
            $this->applyGetHealth($playerId, 3, 3000 + SUNKEN_TEMPLE_EVOLUTION, $playerId);
            $this->applyGetEnergy($playerId, 3, 3000 + SUNKEN_TEMPLE_EVOLUTION);
            $this->goToState(ST_NEXT_PLAYER);
        } else {
            $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
        }
    }
  	
    public function actAnswerElectricCarrot(int $choice) {
        $playerId = $this->getCurrentPlayerId();
        
        if (!in_array($choice, [4, 5])) {
            throw new \BgaUserException("Invalid choice");
        } else if ($choice === 5 && $this->getPlayerEnergy($playerId) < 1) {
            throw new \BgaUserException("Not enough energy");
        }

        $electricCarrotChoices = $this->getGlobalVariable(ELECTRIC_CARROT_CHOICES, true) ?? [];
        $electricCarrotChoices[$playerId] = $choice;
        $this->setGlobalVariable(ELECTRIC_CARROT_CHOICES, $electricCarrotChoices);

        if ($choice === 5) {
            $this->applyGiveSymbols([5], $playerId, $this->getActivePlayerId(), 3000 + ELECTRIC_CARROT_EVOLUTION);
        }

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }
  	
    public function actReserveCard(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $card = $this->powerCards->getItemById($id);
        $cardLocationArg = $card->location_arg;
        if ($card->location !== 'table') {
            throw new \BgaUserException("Card is not on table");
        }

        $question = $this->getQuestion();
        $evolution = $question->args->card;

        $this->powerCards->moveItem($card, 'reserved'.$playerId, $evolution->id);

        $newCard = $this->powerCards->pickCardForLocation('deck', 'table', $cardLocationArg);

        $this->notifyAllPlayers("reserveCard", /*client TODOPUBG translate(*/'${player_name} puts ${card_name} to reserve'/*)*/, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card' => $card,
            'card_name' => $card->type,
            'newCard' => $newCard,
            'deckCardsCount' => $this->powerCards->getDeckCount(),
            'topDeckCard' => $this->powerCards->getTopDeckCard(),
        ]);

        $this->removeStackedStateAndRedirect();
    }

    public function actUseFelineMotor() {
        $playerId = $this->getCurrentPlayerId();

        $this->applyFelineMotor($playerId);
    }

    public function actThrowDieSuperiorAlienTechnology() {
        $playerId = $this->getCurrentPlayerId();

        $question = $this->getQuestion();
        $card = $question->args->card;
        
        $dieFace = bga_rand(1, 6);

        $remove = $dieFace == 6;

        $message = $remove ? 
            clienttranslate('${player_name} rolls ${die_face} for the card ${card_name} with a [ufoToken] on it and loses it') :
            clienttranslate('${player_name} rolls ${die_face} for the card ${card_name} with a [ufoToken] on it and keeps it');

        $this->notifyAllPlayers('superiorAlienTechnologyRolledDie', '', [
            'dieValue' => $dieFace,
            'card' => $card,
        ]);
        $this->notifyAllPlayers('superiorAlienTechnologyLog', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $card->type,
            'die_face' => $this->getDieFaceLogName($dieFace, 0),
            'dieValue' => $dieFace,
            'card' => $card,
        ]);

        if ($remove) {
            $this->removeCard($playerId, $card);
            $superiorAlienTechnologyTokens = $this->getSuperiorAlienTechnologyTokens($playerId);
            $this->setGlobalVariable(SUPERIOR_ALIEN_TECHNOLOGY_TOKENS.$playerId, $superiorAlienTechnologyTokens);
        }
        $this->setUsedCard(800 + $card->id);
        $this->goToState(ST_QUESTIONS_BEFORE_START_TURN);
        return;
    }

    public function actFreezeRayChooseOpponent(#[IntParam(name: 'playerId')] int $toPlayerId) {
        $playerId = $this->getCurrentPlayerId();

        $question = $this->getQuestion();
        $card = $this->getEvolutionCardById($question->args->card->id);
        $this->giveFreezeRay($playerId, $toPlayerId, $card);

        $this->removeStackedStateAndRedirect();
    }

    public function actLoseHearts() {
        $playerId = $this->getCurrentPlayerId();

        $question = $this->getQuestion();
        $card = $question->args->card;

        if ($card->type == TRICK_OR_THREAT_EVOLUTION) {
            $damage = new Damage($playerId, 2, 0, 3000 + TRICK_OR_THREAT_EVOLUTION);
            $this->applyDamage($damage);
        } else if ($card->type == WORST_NIGHTMARE_EVOLUTION) {
            $damage = new Damage($playerId, 1, 0, 3000 + WORST_NIGHTMARE_EVOLUTION);
            $this->applyDamage($damage);
            $this->setUsedCard(3000 + $card->id);
        }

        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
    }
}
