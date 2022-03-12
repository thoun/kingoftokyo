<?php
namespace KOT\Objects;


class CardBeingBought {
    public int $cardId;
    public int $playerId;
    public int $from;
    public bool $allowed = true;

    public function __construct(
        int $cardId,
        int $playerId,
        int $from
    ) {
        $this->cardId = $cardId;
        $this->playerId = $playerId;
        $this->from = $from;
    } 
}
?>