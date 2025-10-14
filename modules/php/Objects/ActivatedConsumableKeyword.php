<?php
namespace Bga\Games\KingOfTokyo\Objects;

class ActivatedConsumableKeyword {
    public function __construct(
        public int $activePlayerId,
        public int $cardId,
        public ?int $targetPlayerId = null,
    ) {
    }
}
?>