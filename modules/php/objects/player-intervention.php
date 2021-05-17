<?php
namespace KOT\Objects;

// a Player Intervention is when a player who is not the active player, can do one action during active player (choosing to stay/leave in tokyo is not in this case as they can choose both at the same time, here it's one then the other)
class PlayerIntervention {
    public $state;
    public $nextState = 'keep'; // keep (current player continues) / next (intervention player) / or leaving transition
    public $endState = 'end';
    public $remainingPlayersId; // first is current one

    public function __construct(int $state, array $remainingPlayersId) {
        $this->remainingPlayersId = $remainingPlayersId;
    } 
}

class OpportunistIntervention extends PlayerIntervention {
    public $revealedCardsIds;

    public function __construct(array $remainingPlayersId, array $revealedCardsIds) {
        parent::__construct(ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD, $remainingPlayersId);

        $this->revealedCardsIds = $revealedCardsIds;
    } 
}

class PsychicProbeIntervention extends PlayerIntervention {
    public $activePlayerId;
    public $cards;

    public function __construct(array $remainingPlayersId, int $activePlayerId, array $cards) {
        parent::__construct(ST_MULTIPLAYER_PSYCHIC_PROBE_ROLL_DIE, $remainingPlayersId);

        $this->activePlayerId = $activePlayerId;
        $this->cards = $cards;
    } 
}

class CancelDamageIntervention extends PlayerIntervention {
    public $damage;
    public $damageDealerId;
    public $cardType;
    public $playersUsedDices = [];

    public function __construct(array $remainingPlayersId, int $damage, int $damageDealerId, int $cardType) {
        parent::__construct(ST_MULTIPLAYER_CANCEL_DAMAGE, $remainingPlayersId);

        $this->damage = $damage;
        $this->damageDealerId = $damageDealerId;
        $this->cardType = $cardType;
    } 
}
?>