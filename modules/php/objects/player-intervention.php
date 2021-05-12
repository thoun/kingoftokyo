<?php
namespace KOT\Objects;

// a Player Intervention is when a player who is not the active player, can do one action during active player (choosing to stay/leave in tokyo is not in this case as they can choose both at the same time, here it's one then the other)
class PlayerIntervention {
    public $currentPlayerId;
    public $remainingPlayersId;

    public function __construct(array $remainingPlayersId) {
        $this->remainingPlayersId = $remainingPlayersId;
    } 
}

class OpportunistIntervention extends PlayerIntervention {
    public $revealedCardsIds;

    public function __construct(array $remainingPlayersId, array $revealedCardsIds) {
        parent::__construct($remainingPlayersId);

        $this->revealedCardsIds = $revealedCardsIds;
    } 
}
?>