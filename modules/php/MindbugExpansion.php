<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFramework\Components\Counters\PlayerCounter;
use Bga\GameFramework\NotificationMessage;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;

class MindbugExpansion {
    public PlayerCounter $mindbugTokens;

    function __construct(
        protected Game $game,
    ) {
        $this->mindbugTokens = $game->counterFactory->createPlayerCounter('mindbugTokens');
    }

    public function isActive(): bool {
        return Game::getBgaEnvironment() === 'studio'; // TODOMB $this->tableOptions->get(MINDBUG_OPTION) > 0;
    }

    public function getMindbugCardsSetting() {
        if (!$this->isActive()) {
            return 0;
        }
        return 1; // TODOMB $this->tableOptions->get(MINDBUG_CARDS_OPTION); 
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
            return [
            'canMindbug' => [],
            'canUseToken' => [],
            'canUseEvasiveMindbug' => [],
        ];
        }
        
        $otherPlayerIds = $this->game->getOtherPlayersIds($activePlayerId);
        $mindbugTokens = $this->mindbugTokens->getAll();

        $canUseEvasiveMindbug = [];
        $canUseToken = Arrays::filter($otherPlayerIds, fn($playerId) => $mindbugTokens[$playerId] > 0);

        foreach ($otherPlayerIds as $playerId) {
            $countEvasiveMindbug = $this->game->countCardOfType($playerId, EVASIVE_MINDBUG_CARD);
            if ($countEvasiveMindbug > 0) {
                $canUseEvasiveMindbug[] = $playerId;
            }
        }

        return [
            'canMindbug' => Arrays::unique(array_merge($canUseToken, $canUseEvasiveMindbug)),
            'canUseToken' => $canUseToken,
            'canUseEvasiveMindbug' => $canUseEvasiveMindbug,
        ];
    }

    function applyGetMindbugTokens(int $playerId, int $tokens, int | PowerCard | EvolutionCard $cardType) {
        if ($cardType instanceof PowerCard) {
            $cardType = $cardType->type;
        }
        if ($cardType instanceof EvolutionCard) {
            $cardType = 3000 + $cardType->type;
        }
        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${inc} Mindbug token(s) with ${card_name}');
            $this->mindbugTokens->inc($playerId, $tokens, new NotificationMessage($message, [
                'card_name' => $cardType == 0 ? null : $cardType,
            ]));
        }
    }
}
