<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFrameworkPrototype\Counters\PlayerCounter;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use BgaUserException;

class MindbugExpansion {
    public PlayerCounter $mindbugTokens;

    function __construct(
        protected Game $game,
    ) {
        $this->mindbugTokens = new PlayerCounter($game, 'mindbugTokens');
    }

    public function isActive(): bool {
        return Game::getBgaEnvironment() === 'studio'; // TODOMB $this->tableOptions->get(MINDBUG_OPTION) > 0;
    }

    public function initDb(array $playerIds): void {
        $this->mindbugTokens->initDb($playerIds);  
    }

    // only called if expansion isActive
    public function setup(): void {
        $this->mindbugTokens->setAll(1);
    }

    public function fillResult(array &$result): void {
        $this->mindbugTokens->fillResult($result);

        $result['mindbug'] = null;
        $mindbuggedPlayerId = $this->game->globals->get(MINDBUGGED_PLAYER);
        if ($mindbuggedPlayerId !== null) {
            $result['mindbug'] = [
                'activePlayerId' => (int)$this->game->getActivePlayerId(),
                'mindbuggedPlayerId' => $mindbuggedPlayerId,
            ];
        }
    }

    public function setMindbuggedPlayer(int $newActivePlayerId, ?int $mindbuggedPlayerId): void {
        $this->game->gamestate->changeActivePlayer($newActivePlayerId);

        $this->game->globals->set(MINDBUGGED_PLAYER, $mindbuggedPlayerId);

        $this->game->notify->all("mindbugPlayer", /*TODOMB clienttranslate*/('${player_name} mindbugs ${player_name2}!'), [
            'activePlayerId' => $newActivePlayerId,
            'mindbuggedPlayerId' => $mindbuggedPlayerId,
            'player_name' => $this->game->getPlayerNameById($newActivePlayerId),
            'player_name2' => $this->game->getPlayerNameById($mindbuggedPlayerId),
        ]);
    }

    public function getMindbuggedPlayer(): ?int {
        return $this->game->globals->get(MINDBUGGED_PLAYER);
    }

    public function canGetExtraTurn(): bool {
        return $this->getMindbuggedPlayer() === null;
    }

    public function getPlayersThatCanMindbug(int $activePlayerId): array {
        if (!$this->isActive()) {
            return [];
        }
        
        $otherPlayerIds = $this->game->getOtherPlayersIds($activePlayerId);
        $mindbugTokens = $this->mindbugTokens->getAll();

        return Arrays::filter($otherPlayerIds, fn($playerId) => $mindbugTokens[$playerId] > 0);
    }
}
