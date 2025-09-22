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

    public function argAskMindbug(): array {
        $activePlayerId = (int)$this->game->getActivePlayerId();
        $playerIds = $this->getPlayersThatCanMindbug($activePlayerId);

        return [
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'playerIds' => $playerIds,
            '_no_notify' => count($playerIds) === 0,
        ];
    }

    public function stAskMindbug(array $args): void {
        if ($args['_no_notify']) {
            $this->game->gamestate->nextState('end');
        } else {
            $this->game->gamestate->setPlayersMultiactive($args['playerIds'], 'end', true);
        }
    }
    
    public function actMindbug(int $currentPlayerId) {
        if ($this->mindbugTokens->get($currentPlayerId) < 1) {
            throw new BgaUserException('No Mindbug tokens');
        }
        $this->mindbugTokens->inc($currentPlayerId, -1);

        $this->setMindbuggedPlayer($currentPlayerId, (int)$this->game->getActivePlayerId());

        // first to click has the power!
        $this->game->gamestate->nextState('end');
    }
    public function actPassMindbug(int $currentPlayerId) {
        $this->game->gamestate->setPlayerNonMultiactive($currentPlayerId, 'end');
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

    public function stEndMindbug(int $activePlayerId) {
        $mindbuggedPlayerId = $this->getMindbuggedPlayer();
        $this->game->gamestate->changeActivePlayer($mindbuggedPlayerId);
        
        $this->game->globals->set(MINDBUGGED_PLAYER, null);

        $this->game->notify->all("mindbugPlayer", /*TODOMB clienttranslate*/('${player_name} finishes his mindbugged turn, ${player_name2} can start again his turn at the Roll dice phase'), [
            'activePlayerId' => $activePlayerId,
            'mindbuggedPlayerId' => null,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'player_name2' => $this->game->getPlayerNameById($mindbuggedPlayerId),
        ]);

        // reinit some stuff for the player 
        $this->game->startTurnInitDice();

        $this->game->goToState(ST_INITIAL_DICE_ROLL);
    }

    public function getMindbuggedPlayer(): ?int {
        return $this->game->globals->get(MINDBUGGED_PLAYER);
    }

    private function getPlayersThatCanMindbug(int $activePlayerId): array {
        if (!$this->isActive()) {
            return [];
        }
        
        $otherPlayerIds = $this->game->getOtherPlayersIds($activePlayerId);
        $mindbugTokens = $this->mindbugTokens->getAll();

        return Arrays::filter($otherPlayerIds, fn($playerId) => $mindbugTokens[$playerId] > 0);
    }
}
