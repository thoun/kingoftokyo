<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class QuestionsBeforeStartTurn extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_QUESTIONS_BEFORE_START_TURN,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        $worstNightmareEvolution = $this->game->powerUpExpansion->evolutionCards->getGiftEvolutionOfType($activePlayerId, \WORST_NIGHTMARE_EVOLUTION);
        if ($worstNightmareEvolution != null && !in_array(3000 + $worstNightmareEvolution->id, $this->game->getUsedCard())) {
            /** @disregard */
            $applied = $worstNightmareEvolution->applyEffect(new Context($this->game, $activePlayerId));
            if ($applied) {
                return;
            } else {
                $this->game->setUsedCard(3000 + $worstNightmareEvolution->id);
            }
        }

        $unusedBambooSupplyCard = $this->game->getFirstUnusedEvolution($activePlayerId, \BAMBOO_SUPPLY_EVOLUTION);
        if ($unusedBambooSupplyCard != null) {
            /** @disregard */
            $redirect = $unusedBambooSupplyCard->applyEffect(new Context($this->game, $activePlayerId));
            if ($redirect) {
                return;
            }
        }

        $unsetFreezeRayCard = $this->game->getFirstUnsetFreezeRay($activePlayerId);
        if ($unsetFreezeRayCard != null) {
            /** @disregard */
            $redirect = $unsetFreezeRayCard->applyEffect(new Context($this->game, $activePlayerId));
            if ($redirect) {
                return;
            }
        }

        $unusedExoticArmsCard = $this->game->getFirstUnusedEvolution($activePlayerId, \EXOTIC_ARMS_EVOLUTION);
        if ($unusedExoticArmsCard != null) {
            /** @disregard */
            $redirect = $unusedExoticArmsCard->applyEffect(new Context($this->game, $activePlayerId));
            if ($redirect) {
                return;
            }
        }

        $cards = $this->game->powerCards->getPlayerReal($activePlayerId);
        $superiorAlienTechnologyTokens = $this->game->getSuperiorAlienTechnologyTokens($activePlayerId);
        $cardsWithSuperiorAlienTechnologyTokens = array_values(array_filter($cards, fn($card) => in_array($card->id, $superiorAlienTechnologyTokens)));
        $usedCardsIds = $this->game->getUsedCard();
        $cardWithSuperiorAlienTechnologyToken = Arrays::find($cardsWithSuperiorAlienTechnologyTokens, fn($iCard) => !in_array(800 + $iCard->id, $usedCardsIds));

        if ($cardWithSuperiorAlienTechnologyToken != null) {
            $question = new Question(
                'SuperiorAlienTechnology',
                clienttranslate('${actplayer} must roll a die for ${card_name} ([ufoToken] on it)'),
                clienttranslate('${you} must roll a die for ${card_name} ([ufoToken] on it)'),
                [$activePlayerId],
                \ST_QUESTIONS_BEFORE_START_TURN,
                [
                    'card' => $cardWithSuperiorAlienTechnologyToken,
                    '_args' => [
                        'card_name' => $cardWithSuperiorAlienTechnologyToken->type,
                    ],
                ]
            );
            $this->game->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$activePlayerId], 'next', true);

            $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
            return;
        }

        $unusedEnergySwordCard = $this->game->getFirstUnusedEvolution($activePlayerId, \ENERGY_SWORD_EVOLUTION);
        if ($unusedEnergySwordCard != null) {
            /** @disregard */
            $redirect = $unusedEnergySwordCard->applyEffect(new Context($this->game, $activePlayerId));
            if ($redirect) {
                return;
            }
        }

        $unusedTempleEvolutionCard = $this->game->getFirstUnusedEvolution($activePlayerId, \SUNKEN_TEMPLE_EVOLUTION);
        if ($unusedTempleEvolutionCard != null) {
            /** @disregard */
            $redirect = $unusedTempleEvolutionCard->applyEffect(new Context($this->game, $activePlayerId));
            if ($redirect) {
                return;
            }
        }

        $unusedMindbugsOverlordCard = $this->game->getFirstUnusedEvolution($activePlayerId, \MINDBUGS_OVERLORD_EVOLUTION);
        if ($unusedMindbugsOverlordCard != null) {
            /** @disregard */
            $redirect = $unusedMindbugsOverlordCard->applyEffect(new Context($this->game, $activePlayerId));
            if ($redirect) {
                return;
            }
        }

        $this->game->goToState(\ST_START_TURN);
    }
}

