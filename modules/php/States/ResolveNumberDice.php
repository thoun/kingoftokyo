<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\PowerCards\SNEAKY;

class ResolveNumberDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_NUMBER_DICE,
            type: StateType::GAME,
            name: 'resolveNumberDice',
        );
    }

    public function getArgs(int $activePlayerId): array {
        return [
            'dice' => $this->game->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        if ($this->game->countCardOfType($activePlayerId, \HIBERNATION_CARD) > 0) {
            return $this->game->redirectAfterResolveNumberDice();
        }

        $diceCounts = $this->game->getGlobalVariable(\DICE_COUNTS, true);

        $gainedPoints = 0;
        for ($diceFace = 1; $diceFace <= 3; $diceFace++) {
            $diceCount = $diceCounts[$diceFace];
            $gainedPoints += max(0, $diceFace + $diceCount - 3);
            $redirected = $this->game->resolveNumberDice($activePlayerId, $diceFace, $diceCount);
            if ($redirected) {
                return null;
            }
        }

        $damages = [];
        if ($gainedPoints > 0) {
            $activatedSneaky = $this->game->mindbugExpansion->getActivatedSneaky($activePlayerId);
            if ($activatedSneaky) {
                // TODOMB TODOCHECK if multiple Sneaky activated, do we multiply?
                $otherPlayersIds = $this->game->getOtherPlayersIds($activePlayerId);
                foreach ($otherPlayersIds as $otherPlayerId) {
                    $this->game->applyLosePoints($otherPlayerId, $gainedPoints, $this->game->powerCards->getItemById($activatedSneaky->cardIds[0]));
                }

                $hunterConsumables = $this->game->powerCards->getItemsByIds($activatedSneaky->cardIds);
                foreach ($hunterConsumables as $card) {
                    $newDamages = $card->applyEffect(new Context($this->game, $activePlayerId, keyword: SNEAKY, lostPoints: $gainedPoints));
                    if (gettype($newDamages) === 'array') {
                        $damages = array_merge($damages, $newDamages);
                    }
                }
            }
        }

        $this->game->goToState($this->game->redirectAfterResolveNumberDice(), $damages);
    }
}

