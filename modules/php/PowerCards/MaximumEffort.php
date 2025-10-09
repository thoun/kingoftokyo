<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\PowerCards;

class MaximumEffort extends PowerCard
{
    public function __construct() {
        $this->mindbugKeywords = [FRENZY];
    }
}
