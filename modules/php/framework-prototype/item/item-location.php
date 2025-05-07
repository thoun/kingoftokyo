<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Item;

class ItemLocation {
    public function __construct(
        public string $name,
        public bool $ordered = true,
        public string|ItemLocation|null $autoReshuffleFrom = null,
    ) {
    }
}