<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
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
            $applied = $this->game->applyWorstNightmare($activePlayerId, $worstNightmareEvolution);
            if ($applied) {
                return;
            } else {
                $this->game->setUsedCard(3000 + $worstNightmareEvolution->id);
            }
        }

        $unusedBambooSupplyCard = $this->game->getFirstUnusedEvolution($activePlayerId, \BAMBOO_SUPPLY_EVOLUTION);
        if ($unusedBambooSupplyCard != null) {
            $question = new Question(
                'BambooSupply',
                clienttranslate('${actplayer} can put or take [Energy]'),
                clienttranslate('${you} can put or take [Energy]'),
                [$activePlayerId],
                \ST_QUESTIONS_BEFORE_START_TURN,
                [ 'canTake' => $unusedBambooSupplyCard->tokens > 0 ]
            );
            $this->game->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$activePlayerId], 'next', true);

            $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
            return;
        }

        $unsetFreezeRayCard = $this->game->getFirstUnsetFreezeRay($activePlayerId);
        if ($unsetFreezeRayCard != null) {
            $ownerId = $unsetFreezeRayCard->ownerId;
            if ($activePlayerId != $ownerId && !$this->game->getPlayer($ownerId)->eliminated) {
                $question = new Question(
                    'FreezeRay',
                    clienttranslate('${actplayer} must choose a die face that will have no effect that turn'),
                    clienttranslate('${you} must choose a die face that will have no effect that turn'),
                    [$ownerId],
                    \ST_QUESTIONS_BEFORE_START_TURN,
                    [ 'card' => $unsetFreezeRayCard ]
                );
                $this->game->setQuestion($question);
                $this->gamestate->setPlayersMultiactive([$ownerId], 'next', true);

                $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
                return;
            } else {
                $this->game->setUsedCard(3000 + $unsetFreezeRayCard->id);
            }
        }

        $unusedExoticArmsCard = $this->game->getFirstUnusedEvolution($activePlayerId, \EXOTIC_ARMS_EVOLUTION);
        if ($unusedExoticArmsCard != null) {
            $potentialEnergy = $this->game->getPlayerPotentialEnergy($activePlayerId);

            if ($potentialEnergy >= 2) {
                $question = new Question(
                    'ExoticArms',
                    clienttranslate('${actplayer} can put 2[Energy] on ${card_name}'),
                    clienttranslate('${you} can put 2[Energy] on ${card_name}'),
                    [$activePlayerId],
                    \ST_QUESTIONS_BEFORE_START_TURN,
                    [
                        'card' => $unusedExoticArmsCard,
                        '_args' => [
                            'card_name' => 3000 + \EXOTIC_ARMS_EVOLUTION,
                        ],
                    ]
                );
                $this->game->setQuestion($question);
                $this->gamestate->setPlayersMultiactive([$activePlayerId], 'next', true);

                $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
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
            $potentialEnergy = $this->game->getPlayerPotentialEnergy($activePlayerId);

            if ($potentialEnergy >= 2) {
                $question = new Question(
                    'EnergySword',
                    clienttranslate('${actplayer} can pay 2[Energy] for ${card_name}'),
                    clienttranslate('${you} can pay 2[Energy] for ${card_name}'),
                    [$activePlayerId],
                    \ST_QUESTIONS_BEFORE_START_TURN,
                    [
                        '_args' => [
                            'card_name' => 3000 + \ENERGY_SWORD_EVOLUTION,
                        ],
                    ]
                );
                $this->game->setQuestion($question);
                $this->gamestate->setPlayersMultiactive([$activePlayerId], 'next', true);

                $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
                return;
            } else {
                $this->game->setEvolutionTokens($activePlayerId, $unusedEnergySwordCard, 0, true);
            }
        }

        $unusedTempleEvolutionCard = $this->game->getFirstUnusedEvolution($activePlayerId, \SUNKEN_TEMPLE_EVOLUTION);
        if ($unusedTempleEvolutionCard != null && !$this->game->inTokyo($activePlayerId)) {
            $question = new Question(
                'SunkenTemple',
                clienttranslate('${actplayer} can pass turn to gain 3[Heart] and 3[Energy]'),
                clienttranslate('${you} can pass your turn to gain 3[Heart] and 3[Energy]'),
                [$activePlayerId],
                \ST_QUESTIONS_BEFORE_START_TURN,
                []
            );
            $this->game->setQuestion($question);
            $this->gamestate->setPlayersMultiactive([$activePlayerId], 'next', true);

            $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
            return;
        }

        $this->game->goToState(\ST_START_TURN);
    }
}

