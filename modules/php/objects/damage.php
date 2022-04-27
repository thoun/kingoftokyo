<?php
namespace KOT\Objects;

class Damage {
    public int $playerId;
    public int $damage;
    public int $damageDealerId;
    public int $cardType; // 0 : smash, -1: skull dice, -card : unlogged card effect
    public int $giveShrinkRayToken;
    public int $givePoisonSpitToken;
    public /*int|null*/ $smasherPoints; // only set when damage with smashes, null otherwise
    public /*int*/ $initialDamage; // set when created, then it doesn't change
    public /*int*/ $remainingDamage; // calculated from initialDamage, can be reduced by Camouflage, Robot, ...
    public /*int*/ $effectiveDamage = 0; // calculated from remainingDamage, if > 0, add Devil, ... Only set when applied

    public function __construct(int $playerId, int $damageAmount, int $damageDealerId, int $cardType, int $giveShrinkRayToken = 0, int $givePoisonSpitToken = 0, /*int|null*/ $smasherPoints = null) {
        $this->playerId = $playerId;
        $this->damage = $damageAmount;
        $this->damageDealerId = $damageDealerId;
        $this->cardType = $cardType;
        $this->giveShrinkRayToken = $giveShrinkRayToken;
        $this->givePoisonSpitToken = $givePoisonSpitToken;
        $this->smasherPoints = $smasherPoints;
        
        $this->initialDamage = $damageAmount;
        $this->remainingDamage = $damageAmount;
    }

    public static function clone(/*Damage*/object $d) {
        return new Damage($d->playerId, $d->damage, $d->damageDealerId, $d->cardType, $d->giveShrinkRayToken, $d->givePoisonSpitToken, $d->smasherPoints);
    }
}
?>