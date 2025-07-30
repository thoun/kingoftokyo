<?php
namespace Bga\Games\KingOfTokyo\Objects;

class Context {
    public function __construct(
        public ?int $currentPlayerId = null
    ) {
    } 
}
?>