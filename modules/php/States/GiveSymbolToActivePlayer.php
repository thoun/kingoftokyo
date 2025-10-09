<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class GiveSymbolToActivePlayer extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_GIVE_SYMBOL_TO_ACTIVE_PLAYER,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'giveSymbolToActivePlayer',
            description: clienttranslate('Player with Golden Scarab must give 1[Heart]/[Energy]/[Star]'),
            descriptionMyTurn: clienttranslate('${you} must give 1[Heart]/[Energy]/[Star]'),
            transitions: [
                'end' => \ST_INITIAL_DICE_ROLL,
            ],
        );
    }

    public function getArgs(): array {
        $playerId = $this->game->anubisExpansion->getPlayerIdWithGoldenScarab();

        $canGiveHeart = $this->game->getPlayerHealth($playerId) > 0;
        $canGiveEnergy = $this->game->getPlayerEnergy($playerId) > 0;
        $canGivePoint = $this->game->getPlayerScore($playerId) > 0;

        return [
            'canGive' => [
                4 => $canGiveHeart,
                5 => $canGiveEnergy,
                0 => $canGivePoint,
            ],
        ];
    }

    public function onEnteringState(): void {
        $activePlayerId = (int)$this->game->getActivePlayerId();
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            $this->game->goToState(\ST_INITIAL_DICE_ROLL);
            return;
        }

        $playerWithScarab = $this->game->anubisExpansion->getPlayerIdWithGoldenScarab();
        if ($playerWithScarab === null) {
            $this->game->goToState(\ST_INITIAL_DICE_ROLL);
            return;
        }

        $this->gamestate->setPlayersMultiactive([$playerWithScarab], 'end', true);
    }

    #[PossibleAction]
    public function actGiveSymbolToActivePlayer(
        #[IntParam(name: 'symbol')] int $symbol,
        int $currentPlayerId,
        array $args,
    ): void {
        $allowedSymbols = [0, 4, 5];
        if (!in_array($symbol, $allowedSymbols, true)) {
            throw new \BgaUserException('Invalid symbol');
        }

        $canGive = $args['canGive'];
        if (($canGive[$symbol] ?? false) === false) {
            throw new \BgaUserException('Symbol not available');
        }

        $activePlayerId = (int)$this->game->getActivePlayerId();

        $this->game->applyGiveSymbols([$symbol], $currentPlayerId, $activePlayerId, 1000 + \KHEPRI_S_REBELLION_CURSE_CARD);

        if ($symbol === 4) {
            $this->game->updateKillPlayersScoreAux();
            $this->game->eliminatePlayers($currentPlayerId);
        }

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'end');
    }

    public function zombie(int $playerId, array $args): void {
        $canGive = $args['canGive'] ?? [];
        $available = array_keys(array_filter($canGive, fn($can) => $can));

        if (!empty($available)) {
            $choice = $this->getRandomZombieChoice($available);
            $this->actGiveSymbolToActivePlayer((int)$choice, $playerId, $args);
        } else {
            $this->gamestate->setPlayerNonMultiactive($playerId, 'end');
        }
    }
}
