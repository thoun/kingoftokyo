<?php
namespace KOT\Objects;

class Damage {
    public int $playerId;
    public int $damage;
    public int $damageDealerId;
    public int $cardType;
    public int $giveShrinkRayToken;
    public int $givePoisonSpitToken;

    public function __construct(int $playerId, int $damage, int $damageDealerId, int $cardType, int $giveShrinkRayToken = 0, int $givePoisonSpitToken = 0) {
        $this->playerId = $playerId;
        $this->damage = $damage;
        $this->damageDealerId = $damageDealerId;
        $this->cardType = $cardType;
        $this->giveShrinkRayToken = $giveShrinkRayToken;
        $this->givePoisonSpitToken = $givePoisonSpitToken;
    }

    public static function clone(/*Damage*/object $d) {
        return new Damage($d->playerId, $d->damage, $d->damageDealerId, $d->cardType, $d->giveShrinkRayToken, $d->givePoisonSpitToken);
    }
}
?>