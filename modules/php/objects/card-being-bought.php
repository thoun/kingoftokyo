<?php
namespace KOT\Objects;


class CardBeingBought {
    public int $cardId;
    public int $playerId;
    public int $from;
    public int $cost;
    public bool $useSuperiorAlienTechnology;
    public bool $allowed = true;

    public function __construct(
        int $cardId,
        int $playerId,
        int $from,
        int $cost,
        bool $useSuperiorAlienTechnology
    ) {
        $this->cardId = $cardId;
        $this->playerId = $playerId;
        $this->from = $from;
        $this->cost = $cost;
        $this->useSuperiorAlienTechnology = $useSuperiorAlienTechnology;
    } 
}
?>