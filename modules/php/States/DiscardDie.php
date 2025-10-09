<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class DiscardDie extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_DISCARD_DIE,
            type: StateType::ACTIVE_PLAYER,
            name: 'discardDie',
            description: clienttranslate('${actplayer} must discard a die'),
            descriptionMyTurn: clienttranslate('${you} must discard a die (click on a die to discard it)'),
            transitions: [
                'next' => \ST_RESOLVE_DICE,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $dice = $this->game->getPlayerRolledDice($activePlayerId, true, true, false);
        $selectableDice = $this->game->getSelectableDice($dice, false, false);

        return [
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        $dice = $this->game->getPlayerRolledDice($activePlayerId, true, false, false);
        if (count($dice) === 0) {
            return \ST_RESOLVE_DICE;
        }

        if ($this->game->autoSkipImpossibleActions()) {
            if (empty($args['selectableDice'])) {
                return \ST_RESOLVE_DICE;
            }
        }
    }

    #[PossibleAction]
    public function actDiscardDie(
        #[IntParam(name: 'id')] int $dieId,
        array $args,
    ) {
        $selectableDice = $args['selectableDice'] ?? [];

        if (!empty($selectableDice)) {
            $selectableIds = array_filter(
                array_map(fn($die) => property_exists($die, 'id') ? (int)$die->id : null, $selectableDice),
                fn($id) => $id !== null,
            );

            if (!empty($selectableIds) && !in_array($dieId, $selectableIds, true)) {
                throw new \BgaUserException('You cannot discard this die');
            }
        }

        $this->game->anubisExpansion->applyDiscardDie($dieId);

        return \ST_RESOLVE_DICE;
    }

    public function zombie(int $playerId, array $args) {
        $selectableDice = $args['selectableDice'];
        if (!empty($selectableDice)) {
            $dieIds = array_filter(
                array_map(fn($die) => property_exists($die, 'id') ? (int)$die->id : null, $selectableDice),
                fn($id) => $id !== null,
            );
            if (!empty($dieIds)) {
                $choice = $this->getRandomZombieChoice($dieIds);
                return $this->actDiscardDie($choice, $args);
            }
        }

        return \ST_RESOLVE_DICE;
    }
}
