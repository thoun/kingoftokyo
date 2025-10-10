<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveHeartDiceAction extends GameState {
    public array $possibleActions = [
        'actApplyHeartDieChoices',
    ];

    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_HEART_DICE_ACTION,
            type: StateType::ACTIVE_PLAYER,
            name: 'resolveHeartDiceAction',
            description: clienttranslate('${actplayer} can select effect of [diceHeart] dice'),
            descriptionMyTurn: clienttranslate('${you} can select effect of [diceHeart] dice'),
            transitions: [
                'next' => \ST_RESOLVE_ENERGY_DICE,
            ],
        );
    }

    public function getArgs(int $activePlayerId): array {
        $diceCounts = $this->game->getGlobalVariable(DICE_COUNTS, true);

        $diceCount = $this->game->addHighTideDice($activePlayerId, $diceCounts[4]);

        if ($diceCount > $diceCounts[4]) {
            $diceCounts[4] = $diceCount;
            $this->game->setGlobalVariable(DICE_COUNTS, $diceCounts);
        }

        if ($diceCount > 0) {
            $dice = $this->game->getPlayerRolledDice($activePlayerId, false, false, false);
    
            $selectHeartDiceUseArg = $this->game->getSelectHeartDiceUse($activePlayerId);  

            $canHealWithDice = $this->game->canHealWithDice($activePlayerId);

            $canSelectHeartDiceUse = $selectHeartDiceUseArg['hasHealingRay'] || (($selectHeartDiceUseArg['shrinkRayTokens'] > 0 || $selectHeartDiceUseArg['poisonTokens'] > 0) && $canHealWithDice);

            if (!$canSelectHeartDiceUse) {
                return [ '_no_notify' => true ];
            }

            return [
                'dice' => $dice,
                'canHealWithDice' => $canHealWithDice,
                'frozenFaces' => $this->game->frozenFaces($activePlayerId),
                '_no_notify' => false 
            ];
        }
        return [ '_no_notify' => true ];
    }

    public function onEnteringState(int $activePlayerId): void {
        $diceCounts = $this->game->getGlobalVariable(\DICE_COUNTS, true);

        $diceCount = $this->game->addHighTideDice($activePlayerId, $diceCounts[4]);

        if ($diceCount > $diceCounts[4]) {
            $diceCounts[4] = $diceCount;
            $this->game->setGlobalVariable(\DICE_COUNTS, $diceCounts);
        }
    }
}

