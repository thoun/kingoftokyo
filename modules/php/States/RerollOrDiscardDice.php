<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class RerollOrDiscardDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_REROLL_OR_DISCARD_DICE,
            type: StateType::ACTIVE_PLAYER,
            name: 'rerollOrDiscardDie',
            description: clienttranslate('${actplayer} can reroll or discard a die'),
            descriptionMyTurn: clienttranslate('${you} can reroll or discard a die (select action then die)'),
            transitions: [
                'next' => \ST_RESOLVE_DICE,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $activePlayerDice = $this->game->getPlayerRolledDice($activePlayerId, true, true, false);
        $selectableDice = $this->game->getSelectableDice($activePlayerDice, false, false);
        
        return [
            'dice' => $activePlayerDice,
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'selectableDice' => $selectableDice,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            $this->game->setGameStateValue(\FALSE_BLESSING_USED_DIE, 0);
            return \ST_RESOLVE_DICE;
        }

        $selectableDice = $args['selectableDice'] ?? [];
        if (empty($selectableDice)) {
            $this->game->setGameStateValue(\FALSE_BLESSING_USED_DIE, 0);
            return \ST_RESOLVE_DICE;
        }

        return null;
    }

    #[PossibleAction]
    public function actFalseBlessingReroll(
        #[IntParam(name: 'id')] int $dieId,
        int $currentPlayerId,
    ) {
        $this->ensureDifferentDie($dieId);

        $die = $this->validateSelectableDie($dieId);

        $value = bga_rand(1, 6);
        $this->game->DbQuery("UPDATE dice SET `rolled` = false WHERE `dice_id` <> $dieId");
        $this->game->DbQuery("UPDATE dice SET `dice_value` = $value, `rolled` = true WHERE `dice_id` = $dieId");

        $this->game->notify->all(
            'changeDie',
            clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}'),
            [
                'playerId' => $currentPlayerId,
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'card_name' => 1000 + \FALSE_BLESSING_CURSE_CARD,
                'dieId' => $die->id,
                'toValue' => $value,
                'roll' => true,
                'die_face_before' => $this->game->getDieFaceLogName($die->value, $die->type),
                'die_face_after' => $this->game->getDieFaceLogName($value, $die->type),
            ],
        );

        return $this->finishAction($dieId);
    }

    #[PossibleAction]
    public function actFalseBlessingDiscard(
        #[IntParam(name: 'id')] int $dieId,
    ) {
        $this->ensureDifferentDie($dieId);
        $this->validateSelectableDie($dieId);

        $this->game->anubisExpansion->applyDiscardDie($dieId);

        return $this->finishAction($dieId);
    }

    #[PossibleAction]
    public function actFalseBlessingSkip() {
        $this->game->setGameStateValue(\FALSE_BLESSING_USED_DIE, 0);
        return \ST_RESOLVE_DICE;
    }

    public function zombie(int $playerId) {
        return $this->actFalseBlessingSkip();
    }

    private function validateSelectableDie(int $dieId): object {
        $args = $this->getArgs((int)$this->game->getActivePlayerId());
        $selectableDice = $args['selectableDice'] ?? [];

        foreach ($selectableDice as $die) {
            if (property_exists($die, 'id') && (int)$die->id === $dieId) {
                return $die;
            }
        }

        throw new \BgaUserException(\clienttranslate('You cannot select this die.'));
    }

    private function ensureDifferentDie(int $dieId): void {
        $usedDie = (int)$this->game->getGameStateValue(\FALSE_BLESSING_USED_DIE);
        if ($usedDie !== 0 && $usedDie === $dieId) {
            throw new \BgaUserException(\clienttranslate('You already made an action for this die'));
        }
    }

    private function finishAction(int $dieId) {
        $usedDie = (int)$this->game->getGameStateValue(\FALSE_BLESSING_USED_DIE);
        if ($usedDie === 0) {
            $this->game->setGameStateValue(\FALSE_BLESSING_USED_DIE, $dieId);
            return null;
        } else {
            $this->game->setGameStateValue(\FALSE_BLESSING_USED_DIE, 0);
            return \ST_RESOLVE_DICE;
        }
    }
}
