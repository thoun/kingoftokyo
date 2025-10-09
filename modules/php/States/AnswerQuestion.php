<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\BoolParam;
use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
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
                'end' => \ST_AFTER_ANSWER_QUESTION,
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
        $playerId = $this->game->getActivePlayerId();

        $this->game->applyLoseEnergy($currentPlayerId, 3, 1000 + \GAZE_OF_THE_SPHINX_CURSE_CARD);

        $this->game->goToState(\ST_RESOLVE_DICE);
    }

    #[PossibleAction]
    public function actPutEnergyOnBambooSupply(int $currentPlayerId) {
        return $this->game->actPutEnergyOnBambooSupply();
    }

    #[PossibleAction]
    public function actTakeEnergyOnBambooSupply(int $currentPlayerId) {
        return $this->game->actTakeEnergyOnBambooSupply();
    }

    #[PossibleAction]
    public function actBuyCardBamboozle(
        int $currentPlayerId,
        #[IntParam(name: 'id')] int $cardId,
        #[IntParam(name: 'from')] int $from,
    ) {
        return $this->game->actBuyCardBamboozle($cardId, $from);
    }

    #[PossibleAction]
    public function actGiveSymbol(
        int $currentPlayerId,
        #[IntParam(name: 'symbol')] int $symbol,
    ) {
        return $this->game->actGiveSymbol($symbol);
    }

    #[PossibleAction]
    public function actChooseMimickedEvolution(
        int $currentPlayerId,
        #[IntParam(name: 'id')] int $mimickedEvolutionId,
    ) {
        return $this->game->actChooseMimickedEvolution($mimickedEvolutionId);
    }

    #[PossibleAction]
    public function actChooseFreezeRayDieFace(
        int $currentPlayerId,
        #[IntParam(name: 'symbol')] int $symbol,
    ) {
        return $this->game->actChooseFreezeRayDieFace($symbol);
    }

    #[PossibleAction]
    public function actBuyCardMiraculousCatch(
        int $currentPlayerId,
        #[BoolParam(name: 'useSuperiorAlienTechnology')] bool $useSuperiorAlienTechnology,
    ) {
        return $this->game->actBuyCardMiraculousCatch($useSuperiorAlienTechnology);
    }

    #[PossibleAction]
    public function actSkipMiraculousCatch(int $currentPlayerId) {
        return $this->game->actSkipMiraculousCatch();
    }

    #[PossibleAction]
    public function actPlayCardDeepDive(
        int $currentPlayerId,
        #[IntParam(name: 'id')] int $id,
    ) {
        return $this->game->actPlayCardDeepDive($id);
    }

    #[PossibleAction]
    public function actUseExoticArms(int $currentPlayerId) {
        return $this->game->actUseExoticArms();
    }

    #[PossibleAction]
    public function actSkipExoticArms(int $currentPlayerId) {
        return $this->game->actSkipExoticArms();
    }

    #[PossibleAction]
    public function actGiveTarget(int $currentPlayerId) {
        return $this->game->actGiveTarget();
    }

    #[PossibleAction]
    public function actSkipGiveTarget(int $currentPlayerId) {
        return $this->game->actSkipGiveTarget();
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
