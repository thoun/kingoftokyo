<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class GiveSymbols extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_GIVE_SYMBOLS,
            type: StateType::ACTIVE_PLAYER,
            name: 'giveSymbols',
            description: clienttranslate('${actplayer} must give 2[Heart]/[Energy]/[Star]'),
            descriptionMyTurn: clienttranslate('${you} must give 2[Heart]/[Energy]/[Star]'),
            transitions: [
                'next' => \ST_RESOLVE_DICE,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        return $this->game->anubisExpansion->argGiveSymbols($activePlayerId);
    }

    public function onEnteringState(int $activePlayerId) {
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            return \ST_RESOLVE_DICE;
        }

        $args = $this->getArgs($activePlayerId);
        $combinations = $args['combinations'] ?? [];

        if ($this->game->autoSkipImpossibleActions() && empty($combinations)) {
            return \ST_RESOLVE_DICE;
        }
    }

    #[PossibleAction]
    public function actGiveSymbols(
        int $currentPlayerId,
        #[IntArrayParam(name: 'symbols')] array $symbols,
    ) {
        $args = $this->getArgs($currentPlayerId);
        $combinations = $args['combinations'] ?? [];
        if (empty($combinations)) {
            throw new \BgaUserException(\clienttranslate('No symbols can be given.'));
        }

        $symbols = array_map('intval', $symbols);
        sort($symbols);

        $isValid = false;
        foreach ($combinations as $combination) {
            $combo = array_map('intval', $combination);
            sort($combo);
            if ($combo === $symbols) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            throw new \BgaUserException(\clienttranslate('Invalid symbol selection.'));
        }

        $playerWithGoldenScarab = $this->game->anubisExpansion->getPlayerIdWithGoldenScarab();
        if ($playerWithGoldenScarab === null) {
            throw new \BgaVisibleSystemException('No player has the Golden Scarab');
        }

        $this->game->applyGiveSymbols($symbols, $currentPlayerId, $playerWithGoldenScarab, 1000 + \PHARAONIC_SKIN_CURSE_CARD);

        if (in_array(4, $symbols, true)) {
            $this->game->updateKillPlayersScoreAux();
            $this->game->eliminatePlayers($currentPlayerId);
        }

        return \ST_RESOLVE_DICE;
    }

    public function zombie(int $playerId) {
        $args = $this->getArgs($playerId);
        $combinations = $args['combinations'] ?? [];

        if (!empty($combinations)) {
            $choice = $this->getRandomZombieChoice($combinations);
            $symbols = array_map('intval', $choice);
            return $this->actGiveSymbols($playerId, $symbols);
        }

        return \ST_RESOLVE_DICE;
    }
}

