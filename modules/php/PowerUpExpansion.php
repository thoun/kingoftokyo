<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

class PowerUpExpansion {
    public EvolutionCardManager $evolutionCards;

    function __construct(
        protected Game $game,
    ) {
        $this->evolutionCards = new EvolutionCardManager($game);
    }

    public function initDb() {
        $this->evolutionCards->initDb();
    }

    public function setup(array $affectedPlayersMonsters) {
        $this->evolutionCards->setup($affectedPlayersMonsters);
    }

    public function isActive(): bool {
        return !$this->game->isOrigins() && $this->game->tableOptions->get(POWERUP_EXPANSION_OPTION) >= 2;
    }
    
    function isPowerUpMutantEvolution(): bool {
        return $this->isActive() && $this->game->tableOptions->get(POWERUP_EXPANSION_OPTION) === 3;
    }
}
