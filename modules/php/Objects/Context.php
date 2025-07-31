<?php
namespace Bga\Games\KingOfTokyo\Objects;

use Bga\Games\KingOfTokyo\Game;

class Context {
    public function __construct(
        public Game $game,
        public ?int $currentPlayerId = null,
        public ?bool $currentPlayerInTokyo = null,
        public ?int $dieSymbol = null,
        public ?int $dieCount = null,
        public ?int $attackerPlayerId = null,
        public ?int $targetPlayerId = null,
        public ?int $smasherPoints = null,
        public ?int $dieSmashes = null,
        public ?int $addedSmashes = null,
    ) {
    } 
}
?>