<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFramework\UserException;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;

class ChangeDie extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_CHANGE_DIE,
            type: StateType::ACTIVE_PLAYER,
            name: 'changeDie',
            description: clienttranslate('${actplayer} can change die result'),
            descriptionMyTurn: clienttranslate('${you} can change die result (click on a die to change it)'),
            transitions: [
                'changeDie' => \ST_PLAYER_CHANGE_DIE,
                'changeDieWithPsychicProbe' => \ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE,
                'resolve' => \ST_PREPARE_RESOLVE_DICE,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $cardsArg = $this->game->getChangeDieCards($activePlayerId);

        $hasBackgroundDweller = $this->game->countCardOfType($activePlayerId, BACKGROUND_DWELLER_CARD) > 0;
        $canRetrow3 = $hasBackgroundDweller && intval($this->game->getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0;

        $dice = $this->game->getPlayerRolledDice($activePlayerId, true, true, true);
        $selectableDice = $this->game->getSelectableDice($dice, true, false);
        // TODO add _no_notify flag when the player can't do anything and use it in onEnteringState
        $diceArg = [
            'playerId' => $activePlayerId,
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $canRetrow3,
            ],
        ];

        return $cardsArg + $diceArg;
    }

    public function onEnteringState(int $activePlayerId, array $args): ?int {
        $canChangeWithCards = $this->game->canChangeDie($this->game->getChangeDieCards($activePlayerId));
        $canRethrow3 = (int)$this->game->getGameStateValue(\PSYCHIC_PROBE_ROLLED_A_3) > 0
            && $this->game->countCardOfType($activePlayerId, \BACKGROUND_DWELLER_CARD) > 0;

        if (!$canChangeWithCards && !$canRethrow3) {
            return \ST_PREPARE_RESOLVE_DICE;
        }

        return null;
    }

    #[PossibleAction]
    public function actRethrow3ChangeDie(int $currentPlayerId): int {
        $dieId = (int)$this->game->getGameStateValue(\PSYCHIC_PROBE_ROLLED_A_3);

        if ($dieId === 0) {
            throw new UserException('No 3 die');
        }

        $this->game->setGameStateValue(\PSYCHIC_PROBE_ROLLED_A_3, 0);

        $newValue = bga_rand(1, 6);
        $this->game->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
        $this->game->DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$dieId);

        $this->game->notify->all('rethrow3changeDie', clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'card_name' => \BACKGROUND_DWELLER_CARD,
            'dieId' => $dieId,
            'die_face_before' => $this->game->getDieFaceLogName(3, 0),
            'die_face_after' => $this->game->getDieFaceLogName($newValue, 0),
        ]);

        $intervention = $this->game->getChangeActivePlayerDieIntervention($currentPlayerId);
        if ($intervention !== null) {
            $this->game->setGlobalVariable(\CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, $intervention);
            return \ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE;
        }

        return \ST_PLAYER_CHANGE_DIE;
    }

    #[PossibleAction]
    public function actChangeDie(
        #[IntParam(name: 'id')] int $dieId,
        #[IntParam(name: 'value')] int $value,
        #[IntParam(name: 'card')] int $cardType,
        int $currentPlayerId,
    ): int {
        $selectedDie = $this->game->getDieById($dieId);

        if ($selectedDie === null) {
            throw new UserException('No selected die');
        }

        if ($cardType == \HERD_CULLER_CARD) {
            if ($value != 1) {
                throw new UserException('You can only change the value to [die1]');
            }
            $usedCards = $this->game->getUsedCard();
            $herdCullerCards = $this->game->getCardsOfType($currentPlayerId, \HERD_CULLER_CARD);
            $usedCardOnThisTurn = null;
            foreach ($herdCullerCards as $herdCullerCard) {
                if (!in_array($herdCullerCard->id, $usedCards)) {
                    $usedCardOnThisTurn = $herdCullerCard->id;
                }
            }
            if ($usedCardOnThisTurn === null) {
                throw new UserException('No unused Herd Culler for this player');
            } else {
                $this->game->setUsedCard($usedCardOnThisTurn);
            }
        } else if ($cardType == \PLOT_TWIST_CARD) {
            $cards = $this->game->getCardsOfType($currentPlayerId, \PLOT_TWIST_CARD);
            if (empty($cards)) {
                throw new UserException('No Plot Twist card for this player');
            }
            $this->game->removeCard($currentPlayerId, $cards[0]);
        } else if ($cardType == \STRETCHY_CARD) {
            $this->game->applyLoseEnergyIgnoreCards($currentPlayerId, 2, 0);
        } else if ($cardType == \BIOFUEL_CARD) {
            if ($selectedDie->value != 4) {
                throw new UserException('You can only change a [dieHeart]');
            }
        } else if ($cardType == \SHRINKY_CARD) {
            if ($selectedDie->value != 2) {
                throw new UserException('You can only change a [die2]');
            }
        } else if ($cardType == \SNEAKY_ALLOY_CARD) {
            if ($selectedDie->value != 1) {
                throw new UserException('You can only change a [die1]');
            }
            $usedCards = $this->game->getUsedCard();
            $sneakyAlloyCards = $this->game->getCardsOfType($currentPlayerId, \SNEAKY_ALLOY_CARD);
            $usedCardOnThisTurn = null;
            foreach ($sneakyAlloyCards as $sneakyAlloyCard) {
                if (!in_array($sneakyAlloyCard->id, $usedCards)) {
                    $usedCardOnThisTurn = $sneakyAlloyCard->id;
                }
            }
            if ($usedCardOnThisTurn === null) {
                throw new UserException('No unused Sneaky Alloy for this player');
            } else {
                $this->game->setUsedCard($sneakyAlloyCard->id);
            }
        } else if ($cardType == 3000 + \SAURIAN_ADAPTABILITY_EVOLUTION) {
            $saurianAdaptabilityCard = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($currentPlayerId, \SAURIAN_ADAPTABILITY_EVOLUTION, false, true)[0];
            $this->game->playEvolutionToTable($currentPlayerId, $saurianAdaptabilityCard, '');
            $this->game->removeEvolution($currentPlayerId, $saurianAdaptabilityCard, false, 5000);
        } else if ($cardType == 3000 + \GAMMA_BREATH_EVOLUTION) {
            if ($value != 6) {
                throw new UserException('You can only change the value to [dieSmash]');
            }
            $gammaBreathCards = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($currentPlayerId, \GAMMA_BREATH_EVOLUTION, true, true);
            $gammaBreathCard = Arrays::find($gammaBreathCards, fn($card) => $card->type == \ICY_REFLECTION_EVOLUTION) ?? $gammaBreathCards[0];

            if ($gammaBreathCard->location === 'hand') {
                $this->game->playEvolutionToTable($currentPlayerId, $gammaBreathCard);
            }
            $this->game->setUsedCard(3000 + $gammaBreathCard->id);
        } else if ($cardType == 3000 + \TAIL_SWEEP_EVOLUTION) {
            if (!in_array($value, [1, 2])) {
                throw new UserException('You can only change the value to [die1] or [die2]');
            }
            $tailSweepCards = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($currentPlayerId, \TAIL_SWEEP_EVOLUTION, true, true);
            $tailSweepCard = Arrays::find($tailSweepCards, fn($card) => $card->type == \ICY_REFLECTION_EVOLUTION) ?? $tailSweepCards[0];

            if ($tailSweepCard->location === 'hand') {
                $this->game->playEvolutionToTable($currentPlayerId, $tailSweepCard);
            }
            $this->game->setUsedCard(3000 + $tailSweepCard->id);
        } else if ($cardType == 3000 + \TINY_TAIL_EVOLUTION) {
            if ($value != 1) {
                throw new UserException('You can only change the value to [die1]');
            }
            $tinyTailCards = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($currentPlayerId, \TINY_TAIL_EVOLUTION, true, true);
            $tinyTailCard = Arrays::find($tinyTailCards, fn($card) => $card->type == \ICY_REFLECTION_EVOLUTION) ?? $tinyTailCards[0];

            if ($tinyTailCard->location === 'hand') {
                $this->game->playEvolutionToTable($currentPlayerId, $tinyTailCard);
            }
            $this->game->setUsedCard(3000 + $tinyTailCard->id);
        } else if ($cardType == 3000 + \ENERGY_DEVOURER_EVOLUTION) {
            if ($selectedDie->value != 4) {
                throw new UserException('You can only change a [dieHeart]');
            }
            if ($value != 6) {
                throw new UserException('You can only change the value to [dieSmash]');
            }
            $energyDevourerCards = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($currentPlayerId, \ENERGY_DEVOURER_EVOLUTION, true, true);
            $energyDevourerCard = Arrays::find($energyDevourerCards, fn($card) => $card->type == \ICY_REFLECTION_EVOLUTION) ?? $energyDevourerCards[0];

            if ($energyDevourerCard->location === 'hand') {
                $this->game->playEvolutionToTable($currentPlayerId, $energyDevourerCard);
            }
            $this->game->setUsedCard(3000 + $energyDevourerCard->id);
            $this->game->applyLoseEnergyIgnoreCards($currentPlayerId, 1, 0);
        } else if ($cardType != \CLOWN_CARD) {
            throw new UserException('Invalid card to change die');
        }

        $activePlayerId = (int)$this->game->getActivePlayerId();

        $dice = [$selectedDie];
        if ($cardType == 3000 + \SAURIAN_ADAPTABILITY_EVOLUTION) {
            $allDice = $this->game->getPlayerRolledDice($currentPlayerId, false, false, false);
            $dice = array_values(array_filter($allDice, fn($die) => $die->value == $selectedDie->value));
        }

        foreach ($dice as $die) {
            $this->game->DbQuery("UPDATE dice SET `rolled` = false, `dice_value` = ".$value." where `dice_id` = ".$die->id);

            $this->game->notify->all('changeDie', clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}'), [
                'playerId' => $currentPlayerId,
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'card_name' => $cardType,
                'dieId' => $die->id,
                'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
                'frozenFaces' => $this->game->frozenFaces($activePlayerId),
                'toValue' => $value,
                'die_face_before' => $this->game->getDieFaceLogName($die->value, $die->type),
                'die_face_after' => $this->game->getDieFaceLogName($value, $die->type),
            ]);
        }

        return \ST_PLAYER_CHANGE_DIE;
    }

    #[PossibleAction]
    public function actResolve(): int {
        return \ST_PREPARE_RESOLVE_DICE;
    }

    #[PossibleAction]
    function actUseYinYang(int $activePlayerId) {
        $yinYangEvolutions = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($activePlayerId, YIN_YANG_EVOLUTION, true, false);
        if (empty($yinYangEvolutions)) {
            throw new UserException("You can't play Yin & Yang without this Evolution.");
        }
        
        /** @disregard */
        $yinYangEvolutions[0]->applyEffect(new Context($this->game, $activePlayerId));

        return ChangeDie::class;
    }

    #[PossibleAction]
    function actUseEnergyInfusedMonster(int $id, int $activePlayerId) {
        $evolution = $this->game->powerUpExpansion->evolutionCards->getCardById($id);
        
        /** @disregard */
        $evolution->applyEffect(new Context($this->game, $activePlayerId));
    }

    public function zombie(int $playerId): int {
        return $this->actResolve();
    }
}
