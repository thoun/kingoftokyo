<?php
namespace Bga\Games\KingOfTokyo\Objects;

class ActivatedConsumableKeyword {
    public function __construct(
        public ?int $activePlayerId = null,
        public array $cardIds = [],
        public ?int $targetPlayerId = null,
    ) {
    }
}
?>