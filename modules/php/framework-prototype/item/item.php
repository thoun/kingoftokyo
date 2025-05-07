<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Item;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Item {
    public function __construct(
        /**
         * The name of DB table.
         */
        public string $tableName,
    ) {
    }
}