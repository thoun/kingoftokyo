<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

class ChangeActivePlayerDie extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'psychicProbeRollDie',// 'changeActivePlayerDie'
            description: clienttranslate('Players with special card can reroll a die'),
            descriptionMyTurn: clienttranslate('${you} can reroll a die'),
            transitions: [
                'stay' => \ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE,
                'next' => \ST_PREPARE_RESOLVE_DICE,
                'end' => \ST_PREPARE_RESOLVE_DICE,
                'endAndChangeDieAgain' => \ST_PLAYER_CHANGE_DIE,
            ],
        );
    }

    public function getArgs(): array {
        $intervention = $this->game->getGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);        
        return $this->argChangeActivePlayerDie($intervention);
    }

    public function onEnteringState(int $activePlayerId, array $args): void {
        $this->game->stIntervention(\CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
    }

    #[PossibleAction]
    public function actChangeActivePlayerDie(
        #[IntParam(name: 'id')] int $dieId,
        int $currentPlayerId,
    ): void {
        $intervention = $this->requireIntervention();
        $playerId = $this->getActingPlayerId($intervention, $currentPlayerId);

        $unusedCards = $this->game->getUnusedChangeActivePlayerDieCards($playerId);
        if (count($unusedCards) === 0) {
            throw new \BgaUserException(\clienttranslate('No card allowing to throw a die from active player'));
        }

        $card = $unusedCards[0];

        $die = $this->game->getDieById($dieId);
        if ($die === null) {
            throw new \BgaUserException(\clienttranslate('Die not found.'));
        }

        $value = bga_rand(1, 6);
        $this->game->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$dieId);
        $this->game->DbQuery("UPDATE dice SET `dice_value` = $value, `rolled` = true where `dice_id` = ".$dieId);

        if (property_exists($card, 'id')) {
            $this->game->setUsedCard((int)$card->id);
        }

        $this->endChangeActivePlayerDie($intervention, $playerId, $die, (int)$card->type, $value);
    }

    #[PossibleAction]
    public function actPsychicProbeRollDie(
        #[IntParam(name: 'id')] int $dieId,
        int $currentPlayerId,
    ): void {
        $this->actChangeActivePlayerDie($dieId, $currentPlayerId);
    }

    #[PossibleAction]
    public function actRethrow3PsychicProbe(int $currentPlayerId): void {
        $intervention = $this->requireIntervention();
        $playerId = $this->getActingPlayerId($intervention, $currentPlayerId);

        if ($this->game->countCardOfType($playerId, \BACKGROUND_DWELLER_CARD) == 0) {
            throw new \BgaUserException(\clienttranslate('No Background Dweller card'));
        }

        $die = $intervention->lastRolledDie ?? null;
        if ($die === null) {
            throw new \BgaUserException(\clienttranslate('No 3 die'));
        }

        $newValue = bga_rand(1, 6);
        $this->game->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$die->id);
        $this->game->DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$die->id);

        $this->game->notify->all('rethrow3', clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}'), [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'card_name' => \BACKGROUND_DWELLER_CARD,
            'dieId' => $die->id,
            'die_face_before' => $this->game->getDieFaceLogName($die->value, 0),
            'die_face_after' => $this->game->getDieFaceLogName($newValue, 0),
        ]);

        $this->endChangeActivePlayerDie($intervention, $playerId, $die, \BACKGROUND_DWELLER_CARD, $newValue);
    }

    #[PossibleAction]
    public function actChangeActivePlayerDieSkip(int $currentPlayerId): void {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    #[PossibleAction]
    public function actPsychicProbeSkip(int $currentPlayerId): void {
        $this->actChangeActivePlayerDieSkip($currentPlayerId);
    }

    public function zombie(int $playerId): ?int {
        /*$intervention = $this->game->getGlobalVariable(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
        $this->game->setInterventionNextState(CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, 'next', $this->game->getPsychicProbeInterventionEndState($intervention), $intervention);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');*/
        return $this->actChangeActivePlayerDieSkip($playerId);
    }

    private function requireIntervention(): object {
        $intervention = $this->game->getGlobalVariable(\CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION);
        if ($intervention === null) {
            throw new \BgaVisibleSystemException('No intervention in progress');
        }
        return $intervention;
    }

    private function getActingPlayerId(object $intervention, int $currentPlayerId): int {
        $playerId = $intervention->remainingPlayersId[0] ?? null;
        if ($playerId === null) {
            throw new \BgaVisibleSystemException('No eligible player for Psychic Probe');
        }
        if ($playerId !== $currentPlayerId) {
            throw new \BgaUserException(\clienttranslate('This is not your turn.'));
        }

        return $playerId;
    }

    private function endChangeActivePlayerDie(
        object $intervention,
        int $playerId,
        object $die,
        int $cardType,
        int $value,
    ): void {
        $discardBecauseOfHeart = $die->type == 0 && $value == 4 && ($cardType == \PSYCHIC_PROBE_CARD || $cardType == \MIMIC_CARD);

        if ($discardBecauseOfHeart) {
            $currentPlayerCards = array_values(array_filter($intervention->cards, fn($card) => $card->location_arg == $playerId));
            if (count($currentPlayerCards) > 0) {
                foreach ($currentPlayerCards as $card) {
                    if ($card->type == \PSYCHIC_PROBE_CARD || $card->type == \MIMIC_CARD) {
                        $this->game->removeCard($playerId, $card, false, true);
                    }

                    if ($card->type == \PSYCHIC_PROBE_CARD) {
                        $mimicCard = $this->game->array_find($intervention->cards, fn($candidate) => $candidate->type == \MIMIC_CARD);
                        if ($mimicCard != null) {
                            $this->game->setUsedCard($mimicCard->id);
                        }

                        if ($mimicCard != null && count(array_filter($intervention->cards, fn($candidate) => $candidate->location_arg == $mimicCard->location_arg)) == 1) {
                            $intervention->remainingPlayersId = array_values(array_filter($intervention->remainingPlayersId, fn($remainingId) => $remainingId != $mimicCard->location_arg));
                        }
                    }
                }
            }
        }

        if ($cardType == 3000 + \HEART_OF_THE_RABBIT_EVOLUTION) {
            $heartOfTheRabbitEvolutions = $this->game->getEvolutionsOfType($playerId, \HEART_OF_THE_RABBIT_EVOLUTION, false, true);
            $this->game->applyPlayEvolution($playerId, $heartOfTheRabbitEvolutions[0]);
            $this->game->setEvolutionTokens($playerId, $heartOfTheRabbitEvolutions[0], 1, true);
        }

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        if ($discardBecauseOfHeart) {
            $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after} (${card_name} is discarded)');
        }

        $stayForRethrow3 = $die->type == 0 && $value == 3 && $this->game->countCardOfType($playerId, \BACKGROUND_DWELLER_CARD) > 0;
        $oldValue = $die->value;

        $die->value = $value;
        $intervention->lastRolledDie = $die;

        if ($die->type == 0 && $value == 3) {
            $this->game->setGameStateValue(\PSYCHIC_PROBE_ROLLED_A_3, $die->id);
        }

        $this->game->notify->all("changeDie", $message, [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'card_name' => $cardType === FLUXLING_WICKEDNESS_TILE ? 2000 + $cardType : $cardType,
            'dieId' => $die->id,
            'toValue' => $value,
            'roll' => true,
            'die_face_before' => $this->game->getDieFaceLogName($oldValue, $die->type),
            'die_face_after' => $this->game->getDieFaceLogName($value, $die->type),
            'psychicProbeRollDieArgs' => $stayForRethrow3 ? $this->argChangeActivePlayerDie($intervention) : null,
            'canHealWithDice' => $this->game->canHealWithDice((int)$this->game->getActivePlayerId()),
        ]);

        $unusedCards = $this->game->getUnusedChangeActivePlayerDieCards($playerId);

        if ($stayForRethrow3 || count($unusedCards) > 0) {
            $this->game->setGlobalVariable(\CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, $intervention);
        } else {
            $this->game->setInterventionNextState(
                \CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION,
                'next',
                $this->game->getPsychicProbeInterventionEndState($intervention),
                $intervention,
            );
            $this->gamestate->setPlayerNonMultiactive($playerId, 'stay');
        }
    }

    function argChangeActivePlayerDie($intervention) {
        $activePlayerId = $intervention->activePlayerId;

        $canRoll = true;
        $hasDice3 = false;
        $hasBackgroundDweller = false;

        $playerId = $intervention && count($intervention->remainingPlayersId) > 0 ? $intervention->remainingPlayersId[0] : null;
        if ($playerId) {
            $psychicProbeCards = $this->game->getCardsOfType($playerId, PSYCHIC_PROBE_CARD);
            $witchCards = $this->game->getCardsOfType($playerId, WITCH_CARD);
            $heartOfTheRabbitEvolutions = $this->game->getEvolutionsOfType($playerId, HEART_OF_THE_RABBIT_EVOLUTION, false, true);

            $canRoll = false;
            $usedCards = $this->game->getUsedCard();
            foreach($psychicProbeCards as $psychicProbeCard) {
                if (!in_array($psychicProbeCard->id, $usedCards)) {
                    $canRoll = true;
                }
            }
            foreach($witchCards as $witchCard) {
                if (!in_array($witchCard->id, $usedCards)) {
                    $canRoll = true;
                }
            }
            foreach($heartOfTheRabbitEvolutions as $heartOfTheRabbitEvolution) {
                if (!in_array(3000 + $heartOfTheRabbitEvolution->id, $usedCards)) {
                    $canRoll = true;
                }
            }

            $hasBackgroundDweller = $this->game->countCardOfType($playerId, BACKGROUND_DWELLER_CARD) > 0;
            $hasDice3 = $intervention->lastRolledDie != null && $intervention->lastRolledDie->value == 3;
        }

        $dice = $this->game->getPlayerRolledDice($activePlayerId, true, true, true);
        $canReroll = false;
        if ($this->game->anubisExpansion->isActive()) {
            $curseCardType = $this->game->anubisExpansion->getCurseCardType();
            if ($curseCardType === VENGEANCE_OF_HORUS_CURSE_CARD) {
                $canReroll = false;
            } else if ($curseCardType === SCRIBE_S_PERSEVERANCE_CURSE_CARD) {
                $canReroll = true;
            }
        }
        
        $selectableDice = $canRoll ? $this->game->getSelectableDice($dice, $canReroll, false) : [];

        return [
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
        ];
    }
}

