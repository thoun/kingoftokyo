<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

class LeaveTokyo extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_LEAVE_TOKYO,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'leaveTokyo',
            description: clienttranslate('Players in Tokyo must choose to stay or leave Tokyo'),
            descriptionMyTurn: clienttranslate('${you} must choose to stay or leave Tokyo'),
            transitions: [
                'resume' => \ST_LEAVE_TOKYO_APPLY_JETS,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        return $this->game->argLeaveTokyo();
    }

    public function onEnteringState(int $activePlayerId, array $args): ?int {
        if ($this->game->autoSkipImpossibleActions()) {
            $canYieldTokyo = $args['canYieldTokyo'] ?? [];
            $oneCanYield = Arrays::some($canYieldTokyo, fn($canYield) => $canYield);
            if (!$oneCanYield) {
                return \ST_LEAVE_TOKYO_APPLY_JETS;
            }
        }

        $smashedPlayersInTokyo = $this->game->getGlobalVariable(\SMASHED_PLAYERS_IN_TOKYO, true);
        $aliveSmashedPlayersInTokyo = [];

        foreach ($smashedPlayersInTokyo as $smashedPlayerInTokyo) {
            if ($this->game->inTokyo($smashedPlayerInTokyo) && $this->game->canYieldTokyo($smashedPlayerInTokyo)) {
                $player = $this->game->getPlayer($smashedPlayerInTokyo);
                if ($player->eliminated) {
                    $this->game->leaveTokyo($smashedPlayerInTokyo);
                } else {
                    if (
                        !$this->game->autoLeave($smashedPlayerInTokyo, $player->health)
                        && !$this->game->autoStay($smashedPlayerInTokyo, $player->health)
                    ) {
                        $aliveSmashedPlayersInTokyo[] = $smashedPlayerInTokyo;
                    }
                }
            }
        }

        if (!empty($aliveSmashedPlayersInTokyo) && $this->game->powerUpExpansion->isActive()) {
            $countChestThumping = $this->game->countEvolutionOfType($activePlayerId, \CHEST_THUMPING_EVOLUTION);
            if (
                $countChestThumping > 0
                && $this->game->anubisExpansion->isActive()
                && $this->game->anubisExpansion->getCurseCardType() == \PHARAONIC_EGO_CURSE_CARD
            ) {
                $countChestThumping = 0;
            }
            if ($countChestThumping > 0) {
                $aliveSmashedPlayersInTokyo[] = $activePlayerId;
            }
        }

        if (!empty($aliveSmashedPlayersInTokyo)) {
            $this->gamestate->setPlayersMultiactive($aliveSmashedPlayersInTokyo, 'resume', true);
            return null;
        }

        return \ST_LEAVE_TOKYO_APPLY_JETS;
    }

    #[PossibleAction]
    function actStay(int $currentPlayerId) {
        $this->game->notifStayInTokyo($currentPlayerId);

        if ($this->game->powerUpExpansion->isActive()) {
            $countBlackDiamond = $this->game->countEvolutionOfType($currentPlayerId, BLACK_DIAMOND_EVOLUTION);
            if ($countBlackDiamond > 0) {
                $this->game->applyGetPoints($currentPlayerId, $countBlackDiamond, 3000 + BLACK_DIAMOND_EVOLUTION);
            }
        }

        $countBullHeaded = $this->game->countCardOfType($currentPlayerId, BULL_HEADED_CARD);
        if ($countBullHeaded > 0) {
            $this->game->applyGetPoints($currentPlayerId, $countBullHeaded, BULL_HEADED_CARD);
        }
        
        // Make this player unactive now (and tell the machine state to use transtion "resume" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, "resume");
    }

    #[PossibleAction]
    function actLeave(?int $useCard, int $currentPlayerId) {
        $this->game->yieldTokyo($currentPlayerId, $useCard);
    }

    #[PossibleAction]
    public function actUseChestThumping(#[IntParam(name: 'id')] int $playerId) {
        $this->game->leaveTokyo($playerId);
        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'resume');

        $this->game->checkOnlyChestThumpingRemaining();
    }

    #[PossibleAction]
    public function actSkipChestThumping(int $currentPlayerId): void {
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'resume');
    }

    public function zombie(int $playerId): void {
        if ($this->game->canYieldTokyo($playerId)) {
            $this->actLeave(null, $playerId);
        } else {
            $this->actStay($playerId);
        }
    }
}
