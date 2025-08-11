<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

class AnubisExpansion {

    function __construct(
        protected $game,
    ) {}

    public function isActive(): bool {
        return $this->game->tableOptions->get(ANUBIS_EXPANSION_OPTION) === 2;
    }
}
