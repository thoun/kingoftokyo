<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class CheerleaderSupport extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_CHEERLEADER_SUPPORT,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'cheerleaderSupport',
            description: clienttranslate('Player with Cheerleader can support monster'),
            descriptionMyTurn: clienttranslate('${you} can support monster'),
            transitions: [
                'end' => \ST_MULTIPLAYER_ASK_MINDBUG,
            ],
        );
    }

    function getArgs(int $activePlayerId) {
        return [
            'dice' => $this->game->getPlayerRolledDice($activePlayerId, true, true, true),
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args): void {
        $cheerleaderSupportPlayerIds = $this->getCheerleaderSupportPlayers($activePlayerId);

        if (!empty($cheerleaderSupportPlayerIds)) {
            $this->gamestate->setPlayersMultiactive($cheerleaderSupportPlayerIds, 'end', true);
            return;
        }

        $this->gamestate->nextState('end');
    }

    #[PossibleAction]
    public function actSupport(int $currentPlayerId): void {
        $this->game->setGameStateValue(CHEERLEADER_SUPPORT, 1);

        $this->notify->all("cheerleaderChoice", clienttranslate('${player_name} chooses to support ${player_name2} and adds [diceSmash]'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'player_name2' => $this->game->getPlayerNameById((int)$this->game->getActivePlayerId()),
        ]);

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'end');
    }

    #[PossibleAction]
    public function actDontSupport(int $currentPlayerId): void {
        $this->notify->all("cheerleaderChoice", clienttranslate('${player_name} chooses to not support ${player_name2}'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'player_name2' => $this->game->getPlayerNameById((int)$this->game->getActivePlayerId()),
        ]);
        
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'end');
    }

    public function zombie(int $playerId): void {
        $this->actDontSupport($playerId);
    }

    private function getCheerleaderSupportPlayers(int $activePlayerId): array {
        $cheerleaderCards = $this->game->powerCards->getCardsOfType(\CHEERLEADER_CARD);
        if (count($cheerleaderCards) === 0) {
            return [];
        }

        $cheerleaderCard = $cheerleaderCards[0];
        if ($cheerleaderCard->location !== 'hand') {
            return [];
        }

        $playerId = (int)$cheerleaderCard->location_arg;
        if (
            $playerId === $activePlayerId
            || !$this->game->canUseSymbol($activePlayerId, 6)
            || !$this->game->canUseFace($activePlayerId, 6)
        ) {
            return [];
        }

        return [$playerId];
    }
}
