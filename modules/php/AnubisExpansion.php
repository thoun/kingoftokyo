<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

class AnubisExpansion {

    function __construct(
        protected Game $game,
    ) {}

    public function isActive(): bool {
        return $this->game->tableOptions->get(ANUBIS_EXPANSION_OPTION) === 2;
    }

    public function setup(): void {
        $this->game->DbQuery("INSERT INTO dice (`dice_value`, `type`) VALUES (0, 2)");
    }
}
