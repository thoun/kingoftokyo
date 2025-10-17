<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\ACTIVATED_HUNTER_CARDS;
use const Bga\Games\KingOfTokyo\PowerCards\HUNTER;

class ResolveSkullDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_SKULL_DICE,
            type: StateType::GAME,
        );
    }

    public function onEnteringState(int $activePlayerId) {
        // in case the player rolled no claw, clean for unreliable targeting
        $activatedHunters = $this->game->mindbugExpansion->getActivatedHunters($activePlayerId);
        if (count($activatedHunters) > 0) {
            foreach ($activatedHunters as $activatedHunter) {
                $card = $this->game->powerCards->getItemById($activatedHunter->cardId);
                $card->applyEffect(new Context($this->game, $activePlayerId, targetPlayerId: $activatedHunter->targetPlayerId, keyword: HUNTER, lostHearts: 0));
            }
            $this->globals->delete(ACTIVATED_HUNTER_CARDS);
        }
        
        $pickEvolutionCards = false;
        if ($this->game->powerUpExpansion->isActive()) {
            $dice = $this->game->getPlayerRolledDice($activePlayerId, true, true, false);
            $diceCounts = $this->game->getRolledDiceCounts($activePlayerId, $dice, false);
            $pickEvolutionCards = $diceCounts[4] >= 3;
        }

        $nextState = $pickEvolutionCards ? \ST_CHOOSE_EVOLUTION_CARD : intval($this->game->getGameStateValue(\STATE_AFTER_RESOLVE));

        if ($this->game->countCardOfType($activePlayerId, \HIBERNATION_CARD) > 0) {
            $this->gamestate->jumpToState($nextState);
            return;
        }

        $diceCounts = $this->game->getGlobalVariable(\DICE_COUNTS, true);

        $damages = [];

        if ($this->game->cybertoothExpansion->isActive() && $diceCounts[7] > 0) {
            $damages[] = new Damage($activePlayerId, $diceCounts[7], $activePlayerId, -1);
        }

        if ($this->game->anubisExpansion->isActive()) {
            $curseCardType = $this->game->anubisExpansion->getCurseCardType();

            if ($curseCardType === \FALSE_BLESSING_CURSE_CARD) {
                $dice = $this->game->getPlayerRolledDice($activePlayerId, true, false, false);
                $diceFaces = [];
                foreach ($dice as $die) {
                    if ($die->type === 0 || $die->type === 1) {
                        $diceFaces[$this->game->getDiceFaceType($die)] = true;
                    }
                }

                $facesCount = count(array_keys($diceFaces));

                $damages[] = new Damage($activePlayerId, $facesCount, 0, 1000 + \FALSE_BLESSING_CURSE_CARD);
            }
        }

        $this->game->goToState($nextState, $damages);
    }
}

