<?php
namespace Bga\Games\KingOfTokyo\Objects;

class ActivatedConsumableKeyword {
    public function __construct(
        public ?string $keyword = null,
        public ?int $targetPlayerId = null,
    ) {
    }
}
?>