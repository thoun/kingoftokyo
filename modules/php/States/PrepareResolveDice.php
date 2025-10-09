<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class PrepareResolveDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PREPARE_RESOLVE_DICE,
            type: StateType::ACTIVE_PLAYER,
            name: 'prepareResolveDice',
            description: clienttranslate('${actplayer} can freeze a die'),
            descriptionMyTurn: clienttranslate('${you} can freeze a die'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $hasEncasedInIce = $this->game->powerUpExpansion->isActive() && $this->game->countEvolutionOfType($activePlayerId, ENCASED_IN_ICE_EVOLUTION) > 0;

        $dice = $this->game->getPlayerRolledDice($activePlayerId, true, true, false);
        $selectableDice = $this->game->getSelectableDice($dice, false, false);
        $canHealWithDice = $this->game->canHealWithDice($activePlayerId);

        $canFreeze = $hasEncasedInIce && $this->game->getPlayerPotentialEnergy($activePlayerId) >= 1;
        return [ 
            'dice' => $dice,
            'canHealWithDice' => $canHealWithDice,
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'selectableDice' => $selectableDice,
            'hasEncasedInIce' => $hasEncasedInIce,
            '_no_notify' => !$canFreeze,
        ];
    }

    public function onEnteringState(array $args) {
        if ($args['_no_notify']) {
            return $this->actSkipFreezeDie();
        }
    }

    #[PossibleAction]
    public function actFreezeDie(
        #[IntParam(name: 'id')] int $dieId,
        int $currentPlayerId,
    ): int {
        if ($this->game->getPlayerEnergy($currentPlayerId) < 1) {
            throw new \BgaUserException(\clienttranslate('Not enough energy'));
        }

        $this->game->applyLoseEnergy($currentPlayerId, 1, 0);
        $this->game->setGameStateValue(\ENCASED_IN_ICE_DIE_ID, $dieId);

        $die = $this->game->getDieById($dieId);
        if ($die === null) {
            throw new \BgaUserException(\clienttranslate('Die not found.'));
        }

        $this->game->notify->all(
            'log',
            clienttranslate('${player_name} freeze die ${die_face}'),
            [
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'die_face' => $this->game->getDieFaceLogName($die->value, $die->type),
            ],
        );

        return $this->redirectAfterPrepareResolveDice();
    }

    #[PossibleAction]
    public function actSkipFreezeDie(): int {
        return $this->redirectAfterPrepareResolveDice();
    }

    public function zombie(int $playerId): int {
        return $this->actSkipFreezeDie();
    }

    function redirectAfterPrepareResolveDice() {
        if ($this->game->isHalloweenExpansion()) {
            return ST_MULTIPLAYER_CHEERLEADER_SUPPORT;
        } else if ($this->game->mindbugExpansion->isActive()) {
            return ST_MULTIPLAYER_ASK_MINDBUG;
        } else {
            return ST_RESOLVE_DIE_OF_FATE;
        }
    }
}
