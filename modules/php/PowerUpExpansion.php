<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

class PowerUpExpansion {

    function __construct(
        protected Game $game,
    ) {}

    public function isActive(): bool {
        return !$this->game->isOrigins() && $this->game->tableOptions->get(POWERUP_EXPANSION_OPTION) >= 2;
    }
    
    function isPowerUpMutantEvolution(): bool {
        return $this->isActive() && $this->game->tableOptions->get(POWERUP_EXPANSION_OPTION) === 3;
    }
}
