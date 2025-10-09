<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class RerollDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_REROLL_DICE,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'rerollDice',
            description: clienttranslate('${player_name} can reroll two dice'),
            descriptionMyTurn: clienttranslate('${you} can reroll two dice'),
            transitions: [
                'end' => \ST_RESOLVE_DICE,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $activePlayerDice = $this->game->getPlayerRolledDice($activePlayerId, true, true, false);
        $selectableDice = $this->game->getSelectableDice($activePlayerDice, false, false);

        $playerId = $this->game->anubisExpansion->getRerollDicePlayerId();

        $diceCount = count(array_filter($activePlayerDice, fn($die) => $die->type < 2));

        $forceRerollTwoDice = $this->game->anubisExpansion->getCurseCardType() == FALSE_BLESSING_CURSE_CARD;
        $min = min($forceRerollTwoDice ? 2 : 0, $diceCount);
        $max = min(2, $diceCount);

        return [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'dice' => $activePlayerDice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'min' => $min,
            'max' => $max,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args): void {
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            $this->game->goToState(\ST_RESOLVE_DICE);
            return;
        }

        $playerId = $this->game->anubisExpansion->getRerollDicePlayerId();
        if ($playerId === null || $this->game->getPlayer($playerId)->eliminated) {
            $this->game->goToState(\ST_RESOLVE_DICE);
            return;
        }

        $selectableDice = $args['selectableDice'] ?? [];
        if (empty($selectableDice)) {
            $this->game->goToState(\ST_RESOLVE_DICE);
            return;
        }

        $this->gamestate->setPlayersMultiactive([$playerId], 'end', true);
    }

    #[PossibleAction]
    public function actRerollDice(
        #[IntArrayParam(name: 'ids')] array $diceIds,
        int $currentPlayerId, 
        array $args,
    ): void {
        $min = (int)($args['min'] ?? 0);
        $max = (int)($args['max'] ?? 0);

        $diceIds = array_values(array_unique(array_map('intval', $diceIds)));

        if (count($diceIds) < $min) {
            throw new \BgaUserException(\clienttranslate('You must select more dice.'));
        }
        if (count($diceIds) > $max) {
            throw new \BgaUserException(\clienttranslate('You selected too many dice.'));
        }

        if (count($diceIds) === 0) {
            $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'end');
            return;
        }

        $allDiceIds = array_filter(
            array_map(
                fn($die) => property_exists($die, 'id') ? (int)$die->id : null,
                $args['dice'] ?? []
            ),
            fn($id) => $id !== null,
        );

        $selectableIds = array_filter(
            array_map(
                fn($die) => property_exists($die, 'id') ? (int)$die->id : null,
                $args['selectableDice'] ?? []
            ),
            fn($id) => $id !== null,
        );

        foreach ($diceIds as $dieId) {
            if (!in_array($dieId, $allDiceIds, true)) {
                throw new \BgaUserException(\clienttranslate('You cannot reroll this die.'));
            }
            if (!in_array($dieId, $selectableIds, true)) {
                throw new \BgaUserException(\clienttranslate('You cannot reroll this die.'));
            }
        }

        $activePlayerId = (int)$this->game->getActivePlayerId();

        foreach ($diceIds as $dieId) {
            $die = $this->game->getDieById($dieId);
            if ($die === null) {
                throw new \BgaUserException(\clienttranslate('Die not found.'));
            }

            $value = bga_rand(1, 6);
            $this->game->DbQuery("UPDATE dice SET `rolled` = false WHERE `dice_id` <> $dieId");
            $this->game->DbQuery("UPDATE dice SET `dice_value` = $value, `rolled` = true WHERE `dice_id` = $dieId");

            $this->game->notify->all('changeDie', clienttranslate('${player_name} forces ${player_name2} to reroll ${die_face_before} die and obtains ${die_face_after}'), [
                'playerId' => $currentPlayerId,
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'player_name2' => $this->game->getPlayerNameById($activePlayerId),
                'dieId' => $die->id,
                'toValue' => $value,
                'roll' => true,
                'die_face_before' => $this->game->getDieFaceLogName($die->value, $die->type),
                'die_face_after' => $this->game->getDieFaceLogName($value, $die->type),
            ]);
        }

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'end');
    }

    public function zombie(int $playerId, array $args): void {
        $selectableDice = $args['selectableDice'] ?? [];

        if (!empty($selectableDice)) {
            $dieIds = array_filter(
                array_map(
                    fn($die) => property_exists($die, 'id') ? (int)$die->id : null,
                    $selectableDice
                ),
                fn($id) => $id !== null,
            );

            if (!empty($dieIds)) {
                $min = (int)($args['min'] ?? 0);
                $max = (int)($args['max'] ?? count($dieIds));
                $rerollCount = max($min, min($max, count($dieIds)));
                if ($rerollCount > 0) {
                    shuffle($dieIds);
                    $selected = array_slice($dieIds, 0, $rerollCount);
                    $this->actRerollDice($selected, $playerId, $args);
                    return;
                }
            } else {
                $this->gamestate->setPlayerNonMultiactive($playerId, 'end');
                return;
            }
        }

        $this->gamestate->setPlayerNonMultiactive($playerId, 'end');
    }
}
