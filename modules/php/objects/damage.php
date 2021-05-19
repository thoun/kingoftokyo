<?php
namespace KOT\Objects;

class Damage {
    public $playerId;
    public $damage;
    public $damageDealerId;
    public $cardType;
    public $ignoreCards;

    public function __construct(int $playerId, int $damage, int $damageDealerId, int $cardType, bool $ignoreCards = false) {
        $this->playerId = $playerId;
        $this->damage = $damage;
        $this->damageDealerId = $damageDealerId;
        $this->cardType = $cardType;
        $this->ignoreCards = $ignoreCards;
    }
}
?>