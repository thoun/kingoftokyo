<?php
namespace KOT\Objects;

class Damage {
    public $playerId;
    public $damage;
    public $damageDealerId;
    public $cardType;
    public $ignoreCards;
    public $giveShrinkRayToken;
    public $givePoisonSpitToken;

    public function __construct(int $playerId, int $damage, int $damageDealerId, int $cardType, bool $ignoreCards = false, bool $giveShrinkRayToken = false, bool $givePoisonSpitToken = false) {
        $this->playerId = $playerId;
        $this->damage = $damage;
        $this->damageDealerId = $damageDealerId;
        $this->cardType = $cardType;
        $this->ignoreCards = $ignoreCards;
        $this->giveShrinkRayToken = $giveShrinkRayToken;
        $this->givePoisonSpitToken = $givePoisonSpitToken;
    }
}
?>