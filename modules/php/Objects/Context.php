<?php
namespace Bga\Games\KingOfTokyo\Objects;

use Bga\Games\KingOfTokyo\Game;

class Context {
    public function __construct(
        public Game $game,
        public ?int $currentPlayerId = null,
        public ?int $dieSymbol = null,
        public ?int $dieCount = null,
    ) {
    } 
}
?>