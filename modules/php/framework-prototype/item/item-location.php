<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Item;

class ItemLocation {
    public function __construct(
        public string $name,
        public string|ItemLocation|null $autoReshuffleFrom = null,
        public \Closure|array|string|null $autoReshuffleCallback = null,  /* = ?callable*/
    ) {
    }
}