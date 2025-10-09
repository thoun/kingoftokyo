<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

class StrangeDesign extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [TOUGH];
    }
}
