<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use KOT\Objects\Damage;

class AnswerQuestion extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_ANSWER_QUESTION,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'answerQuestion',
            transitions: [
                'next' => \ST_AFTER_ANSWER_QUESTION,
            ],
        );
    }

    public function getArgs(): array {
        $question = $this->game->getQuestion();

        if ($question === null) {
            return [ 'question' => null ];
        }

        $args = [
            'question' => $question,
        ];

        if (is_object($question->args) && property_exists($question->args, '_args')) {
            $args = array_merge($args, (array)$question->args->{'_args'});
        }

        return $args;
    }

    public function onEnteringState(): void {
        $activePlayers = $this->gamestate->getActivePlayerList();
        if (count($activePlayers) > 0) {
            return;
        }

        $question = $this->game->getQuestion();
        if ($question === null) {
            $this->game->removeStackedStateAndRedirect();
            return;
        }

        if (empty($question->playersIds)) {
            $this->game->removeStackedStateAndRedirect();
            return;
        }

        $this->gamestate->setPlayersMultiactive($question->playersIds, 'next', true);
    }

    #[PossibleAction]
    public function actChooseMimickedCard(
        int $currentPlayerId,
        #[IntParam(name: 'id')] int $cardId,
    ) {
        $card = $this->game->powerCards->getItemById($cardId);
        if ($card->type > 100 || $card->type == \MIMIC_CARD) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->location != 'hand') {
            throw new \BgaUserException("You must select a player card");
        }

        $question = $this->game->getQuestion();
        $this->game->setMimickedCardId($question->args->mimicCardType, $currentPlayerId, $cardId);

        $this->game->removeStackedStateAndRedirect();
    }

    #[PossibleAction]
    public function actGazeOfTheSphinxDrawEvolution(int $activePlayerId) {
        $this->game->drawEvolution($activePlayerId);

        $this->game->goToState(\ST_RESOLVE_DICE);
    }

    #[PossibleAction]
    public function actGazeOfTheSphinxGainEnergy(int $activePlayerId) {
        $this->game->applyGetEnergy($activePlayerId, 3, 1000 + \GAZE_OF_THE_SPHINX_CURSE_CARD);

        $this->game->goToState(\ST_RESOLVE_DICE);
    }

    #[PossibleAction]
    public function actGazeOfTheSphinxDiscardEvolution(
        int $activePlayerId,
        #[IntParam(name: 'id')] int $id,
    ) {

        $card = $this->game->getEvolutionCardById($id);

        $this->game->removeEvolution($activePlayerId, $card);

        $this->game->goToState(\ST_RESOLVE_DICE);
    }

    #[PossibleAction]
    public function actGazeOfTheSphinxLoseEnergy(int $currentPlayerId) {
        $this->game->applyLoseEnergy($currentPlayerId, 3, 1000 + \GAZE_OF_THE_SPHINX_CURSE_CARD);

        $this->game->goToState(\ST_RESOLVE_DICE);
    }

    #[PossibleAction]
    public function actPutEnergyOnBambooSupply(int $currentPlayerId) {
        $unusedBambooSupplyCard = $this->game->getFirstUnusedEvolution($currentPlayerId, BAMBOO_SUPPLY_EVOLUTION);

        if ($unusedBambooSupplyCard) {
            $this->game->setEvolutionTokens($currentPlayerId, $unusedBambooSupplyCard, $unusedBambooSupplyCard->tokens + 1);
            $this->game->setUsedCard(3000 + $unusedBambooSupplyCard->id);
        }

        $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    #[PossibleAction]
    public function actTakeEnergyOnBambooSupply(int $currentPlayerId) {
        $unusedBambooSupplyCard = $this->game->getFirstUnusedEvolution($currentPlayerId, BAMBOO_SUPPLY_EVOLUTION);

        $this->game->applyGetEnergyIgnoreCards($currentPlayerId, $unusedBambooSupplyCard->tokens, 3000 + BAMBOO_SUPPLY_EVOLUTION);
        $this->game->setEvolutionTokens($currentPlayerId, $unusedBambooSupplyCard, 0);

        $this->game->setUsedCard(3000 + $unusedBambooSupplyCard->id);

        $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    #[PossibleAction]
    public function actBuyCardBamboozle(
        int $id,
        int $from,
        int $currentPlayerId,
        int $activePlayerId,
    ) {
        $forcedCard = $this->game->powerCards->getItemById($id);
        $forbiddenCard = $this->game->powerCards->getItemById($this->game->getGlobalVariable(CARD_BEING_BOUGHT)->cardId);

        $this->notify->all('log', clienttranslate('${player_name} force ${player_name2} to buy ${card_name} instead of ${card_name2}. ${player_name2} cannot buy ${card_name2} this turn'), [
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'player_name2' => $this->game->getPlayerNameById($activePlayerId),
            'card_name' => $forcedCard->type,
            'card_name2' => $forbiddenCard->type,
        ]);

        // applyBuyCard do the redirection
        $this->game->applyBuyCard($activePlayerId, $id, $from);
    }

    #[PossibleAction]
    public function actGiveSymbol(
        int $symbol,
        int $currentPlayerId,
    ) {
        $question = $this->game->getQuestion();
        $evolutionPlayerId = $question->args->playerId;
        
        $this->game->applyGiveSymbols([$symbol], $currentPlayerId, $evolutionPlayerId, 3000 + $question->args->card->type);

        if ($question->args->card->type == WORST_NIGHTMARE_EVOLUTION) {
            $this->game->setUsedCard(3000 + $question->args->card->id);
        }

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    public function actChooseMimickedEvolution(
        #[IntParam(name: 'id')] int $mimickedEvolutionId,
        int $currentPlayerId,
    ) {
        $card = $this->game->getEvolutionCardById($mimickedEvolutionId);
        if ($this->game->EVOLUTION_CARDS_TYPES[$card->type] != 1) {
            throw new \BgaUserException("You can only mimic Permanent evolutions");
        }
        if ($card->type === ICY_REFLECTION_EVOLUTION) {
            throw new \BgaUserException("You cannot mimic Icy Reflection");
        }

        $this->game->setMimickedEvolution($currentPlayerId, $card);

        $this->game->goToState(-1);
    }

    #[PossibleAction]
    public function actChooseFreezeRayDieFace(
        int $symbol,
        int $currentPlayerId,
        int $activePlayerId,
    ) {
        $question = $this->game->getQuestion();
        $evolutionId = $question->args->card->id;
        $evolution = $this->game->getEvolutionCardById($evolutionId);
        $this->game->setEvolutionTokens($activePlayerId, $evolution, $symbol, true);

        $this->notify->all('log', clienttranslate('${player_name} chooses that ${die_face} will have no effect this turn'), [
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'die_face' => $this->game->getDieFaceLogName($symbol, 0),
        ]);

        $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    #[PossibleAction]
    public function actBuyCardMiraculousCatch(
        bool $useSuperiorAlienTechnology,
        int $currentPlayerId,
    ) {
        $evolution = $this->game->getFirstUnusedEvolution($currentPlayerId, MIRACULOUS_CATCH_EVOLUTION, true, true);

        $card = $this->game->powerCards->getCardOnTop('discard');

        $this->game->setUsedCard(3000 + $evolution->id);
        $this->game->powerCards->shuffle('discard');

        $cost = $this->game->getCardCost($currentPlayerId, $card->type) - 1;        
        if ($useSuperiorAlienTechnology) {
            $cost = ceil($cost / 2);
        }

        // applyBuyCard do the redirection
        $this->game->applyBuyCard(
            $currentPlayerId,
            $card->id,
            0,
            $cost,
            $useSuperiorAlienTechnology,
            false,
        );
    }

    #[PossibleAction]
    public function actSkipMiraculousCatch(int $currentPlayerId) {
        $evolution = $this->game->getFirstUnusedEvolution($currentPlayerId, MIRACULOUS_CATCH_EVOLUTION, true, true);

        $this->game->setUsedCard(3000 + $evolution->id);
        $this->game->powerCards->shuffle('discard');

        $this->game->goToState(ST_PLAYER_BUY_CARD);
    }

    #[PossibleAction]
    public function actPlayCardDeepDive(
        int $id,
        int $currentPlayerId,
    ) {
        $card = $this->game->powerCards->getItemById($id);

        $evolutions = $this->game->getEvolutionCardsByType(DEEP_DIVE_EVOLUTION);
        foreach($evolutions as $evolution) {
            $this->game->removeEvolution($currentPlayerId, $evolution);
        }

        $damages = $this->game->applyPlayCard($currentPlayerId, $card);

        // move other cards to bottom deck
        $question = $this->game->getQuestion();
        $cards = $this->game->powerCards->getItemsByIds(Arrays::map($question->args->cards, fn($card) => $card->id));
        $otherCards = Arrays::filter($cards, fn($otherCard) => $otherCard->id != $card->id);
        $this->game->DbQuery("UPDATE `card` SET `card_location_arg` = card_location_arg + ".count($otherCards)." WHERE `card_location` = 'deck'");
        foreach($otherCards as $index => $otherCard) {
            $this->game->powerCards->moveItem($otherCard, 'deck', $index);
        }

        $mimic = false;
        if ($card->type == MIMIC_CARD) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->game->getPlayersIds();
            foreach($playersIds as $pId) {
                $cardsOfPlayer = $this->game->powerCards->getPlayer($pId);
                $countAvailableCardsForMimic += Arrays::count($cardsOfPlayer, fn($card) => $card->type != MIMIC_CARD && $card->type < 100);
            }

            $mimic = $countAvailableCardsForMimic > 0;
        }

        if ($mimic) {
            $this->game->goToMimicSelection($currentPlayerId, MIMIC_CARD, -1);
        } else {
            $this->game->goToState(-1, $damages);
        }
    }

    #[PossibleAction]
    public function actUseExoticArms(int $currentPlayerId, int $activePlayerId) {
        if ($this->game->getPlayerEnergy($currentPlayerId) < 2) {
            throw new \BgaUserException("Not enough energy");
        }
        $this->game->applyLoseEnergy($currentPlayerId, 2, 0);

        $question = $this->game->getQuestion();
        $evolutionId = $question->args->card->id;
        $evolution = $this->game->getEvolutionCardById($evolutionId);
        $this->game->setEvolutionTokens($activePlayerId, $evolution, 2);

        $this->game->setUsedCard(3000 + $evolutionId);
        $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    #[PossibleAction]
    public function actSkipExoticArms() {
        $question = $this->game->getQuestion();
        $evolutionId = $question->args->card->id;

        $this->game->setUsedCard(3000 + $evolutionId);
        $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    #[PossibleAction]
    public function actGiveTarget(int $currentPlayerId) {
        $question = $this->game->getQuestion();
        $this->notify->all('giveTarget', clienttranslate('${player_name} gives target to ${player_name2}'), [
            'playerId' => $question->args->playerId,
            'previousOwner' => intval($this->game->getGameStateValue(TARGETED_PLAYER)),
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'player_name2' => $this->game->getPlayerNameById($question->args->playerId),
        ]);
        $this->game->setGameStateValue(TARGETED_PLAYER, $question->args->playerId);

        foreach($question->playersIds as $pId) {
            $this->gamestate->setPlayerNonMultiactive($pId, 'next');
        }
    }

    #[PossibleAction]
    public function actSkipGiveTarget(int $currentPlayerId) {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    public function actUseLightningArmor(int $currentPlayerId) {
        $question = $this->game->getQuestion();
        $damageAmountForPlayer = ((array)$question->args->damageAmountByPlayer)[$currentPlayerId];
        $damageDealerIdForPlayer = ((array)$question->args->damageDealerIdByPlayer)[$currentPlayerId];
        
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
            $diceStr .= $this->game->getDieFaceLogName($dieValue, 0);
        }

        if ($damageDealerIdForPlayer) {
            $this->notify->all("useLightningArmor", clienttranslate('${player_name} uses ${card_name}, rolls ${dice} and makes ${player_name2} lose ${damage}[Heart]'), [
                'playerId' => $currentPlayerId,
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'player_name2' => $this->game->getPlayerNameById($damageDealerIdForPlayer),
                'card_name' => 3000 + LIGHTNING_ARMOR_EVOLUTION,
                'damage' => $smashCount,
                'diceValues' => $dice,
                'dice' => $diceStr,
            ]);
            if ($smashCount > 0) {
                $damage = new Damage($damageDealerIdForPlayer, $smashCount, $currentPlayerId, 3000 + LIGHTNING_ARMOR_EVOLUTION);
                $this->game->applyDamage($damage);
            }
        }/* else {
            $this->warn('useLightningArmor damageDealerIdForPlayer is null');
            $this->dump('$question->args', $question->args);
        }*/

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    public function actSkipLightningArmor(int $currentPlayerId) {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    public function actAnswerEnergySword(
        int $currentPlayerId,
        bool $use,
    ) {
        $unusedEvolutionCard = $this->game->getFirstUnusedEvolution($currentPlayerId, ENERGY_SWORD_EVOLUTION);
        $this->game->setUsedCard(3000 + $unusedEvolutionCard->id);

        if ($use) {
            if ($this->game->getPlayerEnergy($currentPlayerId) < 2) {
                throw new \BgaUserException("Not enough energy");
            }
            $this->game->applyLoseEnergy($currentPlayerId, 2, 0);
            $this->game->setEvolutionTokens($currentPlayerId, $unusedEvolutionCard, 2, true);
        } else {
            $this->game->setEvolutionTokens($currentPlayerId, $unusedEvolutionCard, 0, true);
        }
        
        $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    #[PossibleAction]
    public function actAnswerSunkenTemple(
        int $currentPlayerId,
        bool $use,
    ) {
        $unusedEvolutionCard = $this->game->getFirstUnusedEvolution($currentPlayerId, SUNKEN_TEMPLE_EVOLUTION);
        $this->game->setUsedCard(3000 + $unusedEvolutionCard->id);

        if ($use) {
            $this->game->applyGetHealth($currentPlayerId, 3, 3000 + SUNKEN_TEMPLE_EVOLUTION, $currentPlayerId);
            $this->game->applyGetEnergy($currentPlayerId, 3, 3000 + SUNKEN_TEMPLE_EVOLUTION);
            $this->game->goToState(ST_NEXT_PLAYER);
        } else {
            $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
        }
    }

    #[PossibleAction]
    public function actAnswerElectricCarrot(
        int $currentPlayerId,
        int $choice,
    ) {
        if (!in_array($choice, [4, 5])) {
            throw new \BgaUserException("Invalid choice");
        } else if ($choice === 5 && $this->game->getPlayerEnergy($currentPlayerId) < 1) {
            throw new \BgaUserException("Not enough energy");
        }

        $electricCarrotChoices = $this->game->getGlobalVariable(ELECTRIC_CARROT_CHOICES, true) ?? [];
        $electricCarrotChoices[$currentPlayerId] = $choice;
        $this->game->setGlobalVariable(ELECTRIC_CARROT_CHOICES, $electricCarrotChoices);

        if ($choice === 5) {
            $this->game->applyGiveSymbols([5], $currentPlayerId, (int)$this->game->getActivePlayerId(), 3000 + ELECTRIC_CARROT_EVOLUTION);
        }

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    public function actReserveCard(
        int $currentPlayerId,
        int $id,
    ) {
        $card = $this->game->powerCards->getItemById($id);
        $cardLocationArg = $card->location_arg;
        if ($card->location !== 'table') {
            throw new \BgaUserException("Card is not on table");
        }

        $question = $this->game->getQuestion();
        $evolution = $question->args->card;

        $this->game->powerCards->moveItem($card, 'reserved'.$currentPlayerId, $evolution->id);

        $newCard = $this->game->powerCards->pickCardForLocation('deck', 'table', $cardLocationArg);

        $this->notify->all("reserveCard", /*client TODOPUBG translate(*/'${player_name} puts ${card_name} to reserve'/*)*/, [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'card' => $card,
            'card_name' => $card->type,
            'newCard' => $newCard,
            'deckCardsCount' => $this->game->powerCards->getDeckCount(),
            'topDeckCard' => $this->game->powerCards->getTopDeckCard(),
        ]);

        $this->game->removeStackedStateAndRedirect();
    }

    #[PossibleAction]
    public function actThrowDieSuperiorAlienTechnology(int $currentPlayerId) {
        $question = $this->game->getQuestion();
        $card = $question->args->card;
        
        $dieFace = bga_rand(1, 6);

        $remove = $dieFace == 6;

        $message = $remove ? 
            clienttranslate('${player_name} rolls ${die_face} for the card ${card_name} with a [ufoToken] on it and loses it') :
            clienttranslate('${player_name} rolls ${die_face} for the card ${card_name} with a [ufoToken] on it and keeps it');

        $this->notify->all('superiorAlienTechnologyRolledDie', '', [
            'dieValue' => $dieFace,
            'card' => $card,
        ]);
        $this->notify->all('superiorAlienTechnologyLog', $message, [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'card_name' => $card->type,
            'die_face' => $this->game->getDieFaceLogName($dieFace, 0),
            'dieValue' => $dieFace,
            'card' => $card,
        ]);

        if ($remove) {
            $this->game->removeCard($currentPlayerId, $card);
            $superiorAlienTechnologyTokens = $this->game->getSuperiorAlienTechnologyTokens($currentPlayerId);
            $this->game->setGlobalVariable(SUPERIOR_ALIEN_TECHNOLOGY_TOKENS.$currentPlayerId, $superiorAlienTechnologyTokens);
        }
        $this->game->setUsedCard(800 + $card->id);
        $this->game->goToState(ST_QUESTIONS_BEFORE_START_TURN);
    }

    #[PossibleAction]
    public function actFreezeRayChooseOpponent(
        int $currentPlayerId,
        #[IntParam(name: 'playerId')] int $toPlayerId,
    ) {
        $question = $this->game->getQuestion();
        $card = $this->game->getEvolutionCardById($question->args->card->id);
        $this->game->giveFreezeRay($currentPlayerId, $toPlayerId, $card);

        $this->game->removeStackedStateAndRedirect();
    }

    #[PossibleAction]
    public function actLoseHearts(int $currentPlayerId) {
        $question = $this->game->getQuestion();
        $card = $question->args->card;

        if ($card->type == TRICK_OR_THREAT_EVOLUTION) {
            $damage = new Damage($currentPlayerId, 2, 0, 3000 + TRICK_OR_THREAT_EVOLUTION);
            $this->game->applyDamage($damage);
        } else if ($card->type == WORST_NIGHTMARE_EVOLUTION) {
            $damage = new Damage($currentPlayerId, 1, 0, 3000 + WORST_NIGHTMARE_EVOLUTION);
            $this->game->applyDamage($damage);
            $this->game->setUsedCard(3000 + $card->id);
        }

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    public function actTreasure(
        int $currentPlayerId,
        int $id,
    ) {
        $card = $this->game->powerCards->getItemById($id);

        $this->game->applyBuyCard($currentPlayerId, $card->id, null, $this->game->getCardCost($currentPlayerId, $card->type) - 3);

        $this->game->goToState(-1);    
    }

    #[PossibleAction]
    public function actPassTreasure(int $currentPlayerId) {
        $this->game->goToState(-1);
    }

    public function zombie(int $playerId) {
        // TODO
    }
}
