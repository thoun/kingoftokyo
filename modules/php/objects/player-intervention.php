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

class ChangeActivePlayerDieIntervention extends PlayerIntervention {
    public $activePlayerId;
    public $cards;
    public $lastRolledDie = null;

    public function __construct(array $remainingPlayersId, int $activePlayerId, array $cards) {
        parent::__construct(ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE, $remainingPlayersId);

        $this->activePlayerId = $activePlayerId;
        $this->cards = $cards;
    }
}

class PlayersUsedDice {
    public $dice; // Dice[]
    public $rolls = 0; // number
    public $maxRolls; // number

    public function __construct(array $dice, int $maxRolls) {
        $this->dice = $dice;
        $this->maxRolls = $maxRolls;
    }
}

class CancelDamageIntervention extends PlayerIntervention {
    public $damages;
    public $allDamages;
    public $playersUsedDice; // store playerId => PlayersUsedDice
    public $targetAcquiredAsked = false;
    public $lightningArmorAsked = false;

    public function __construct(array $remainingPlayersId, array $damages, array $allDamages) {
        parent::__construct(ST_MULTIPLAYER_CANCEL_DAMAGE, $remainingPlayersId);

        $this->damages = $damages;
        $this->allDamages = $allDamages;
        $this->playersUsedDice = new \stdClass();
    }

    public static function canDoIntervention(object $game, int $playerId, int $damage, int $damageDealerId) {
        $canDo = $game->countCardOfType($playerId, CAMOUFLAGE_CARD) > 0 || 
            $game->countCardOfType($playerId, ROBOT_CARD) > 0 || 
            ($game->countCardOfType($playerId, WINGS_CARD) > 0 && $game->canLoseHealth($playerId, $damage) == null) ||
            ($game->isPowerUpExpansion() && ($game->countEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, false, true) > 0 || $game->countEvolutionOfType($playerId, CYBER_BUNNY_3_EVOLUTION, false, true) > 0)) ||
            $game->countUnusedCardOfType($playerId, SUPER_JUMP_CARD) > 0;

        if ($canDo) {
            return true;
        } else {
            $playerHealth = $game->getPlayerHealth($playerId);

            $totalDamage = $damage;
            if ($game->countCardOfType($damageDealerId, DEVIL_CARD)) {
                $totalDamage += 1;
            }

            if ($playerHealth <= $totalDamage) {
                $rapidHealingHearts = $game->cancellableDamageWithRapidHealing($playerId);
                $superJumpHearts = $game->cancellableDamageWithSuperJump($playerId);
                $rapidHealingCultists = $game->isCthulhuExpansion() ? $game->cancellableDamageWithCultists($playerId) : 0;
                $damageToCancelToSurvive = $game->getDamageToCancelToSurvive($totalDamage, $playerHealth);
                $canHeal = $rapidHealingHearts + $rapidHealingCultists + $superJumpHearts;
                if ($game->countCardOfType($playerId, REGENERATION_CARD)) {
                    $canHeal *= 2;
                }
                
                return $canHeal > 0 && $canHeal >= $damageToCancelToSurvive;
            } else {
                return false;
            }
        }
    }
}
?>