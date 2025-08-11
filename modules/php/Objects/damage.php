<?php
namespace KOT\Objects;

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;

class ClawDamage {
    public int $smasherPoints;
    public int $shrinkRayTokens = 0;
    public int $poisonTokens = 0;
    public array $electricCarrotChoice = []; // key is player id, value is player choice : 4 = -1 heart, 5 = -1 energy

    public function __construct(int $smasherPoints, int $shrinkRayTokens = 0, int $poisonTokens = 0, array $electricCarrotChoice = []) {
        $this->smasherPoints = $smasherPoints;
        $this->shrinkRayTokens = $shrinkRayTokens;
        $this->poisonTokens = $poisonTokens;
        $this->electricCarrotChoice = $electricCarrotChoice;
    }
}

class Damage {
    public int $playerId;
    public int $damage;
    public int $damageDealerId;
    public int | CurseCard $cardType; // 0 : smash, -1: skull dice, -card : unlogged card effect
    public int $giveShrinkRayToken;
    public int $givePoisonSpitToken;
    public ?int $smasherPoints;
    public /*ClawDamage|null*/ $clawDamage = null; // only set when damage with claws, null otherwise
    public /*int*/ $initialDamage; // set when created, then it doesn't change
    public /*int*/ $remainingDamage; // calculated from initialDamage, can be reduced by Camouflage, Robot, ...
    public /*int*/ $effectiveDamage = 0; // calculated from remainingDamage, if > 0, add Devil, ... Only set when applied

    public function __construct(int $playerId, int $damageAmount, int $damageDealerId, int | CurseCard $cardType, $clawDamage = null) {
        $this->playerId = $playerId;
        $this->damage = $damageAmount;
        $this->damageDealerId = $damageDealerId;

        if ($cardType instanceof CurseCard) {
            $cardType = 1000 + $cardType->type;
        }
        if ($cardType instanceof WickednessTile) {
            $cardType = 2000 + $cardType->type;
        }
        $this->cardType = $cardType;
        $this->giveShrinkRayToken = $clawDamage !== null ? $clawDamage->shrinkRayTokens : 0;
        $this->givePoisonSpitToken = $clawDamage !== null ? $clawDamage->poisonTokens : 0;
        $this->smasherPoints = $clawDamage !== null ? $clawDamage->smasherPoints : null;
        $this->clawDamage = $clawDamage;
        
        $this->initialDamage = $damageAmount;
        $this->remainingDamage = $damageAmount;
    }

    public static function clone(/*Damage*/object $d) {
        return new Damage($d->playerId, $d->damage, $d->damageDealerId, $d->cardType, $d->clawDamage);
    }
}
?>