<?php
namespace Bga\Games\KingOfTokyo\Objects;

class AddSmashTokens {
    public function __construct(
        public int $shrinkRay = 0,
        public int $poison = 0,
    ) {
    } 

    public function add(AddSmashTokens $other) {
        $this->shrinkRay += $other->shrinkRay;
        $this->poison += $other->poison;
    }
}
?>