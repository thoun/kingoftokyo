<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveSmashDiceAction extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_SMASH_DICE_ACTION,
            type: StateType::ACTIVE_PLAYER,
            name: 'resolveSmashDiceAction',
            description: clienttranslate('${actplayer} can select effect of [diceSmash] dice'),
            descriptionMyTurn: clienttranslate('${you} can select effect of [diceSmash] dice'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $diceCounts = $this->game->getGlobalVariable(DICE_COUNTS, true);
        $canUsePlayWithYourFood = $this->game->canUsePlayWithYourFood($activePlayerId, $diceCounts);

        if ($canUsePlayWithYourFood !== null) {
            $dice = $this->game->getPlayerRolledDice($activePlayerId, false, false, false);
            $canHealWithDice = $this->game->canHealWithDice($activePlayerId);

            if (!$canUsePlayWithYourFood) {
                return [ '_no_notify' => true ];
            }

            return [
                'dice' => $dice,
                'canHealWithDice' => $canHealWithDice,
                'frozenFaces' => $this->game->frozenFaces($activePlayerId),
                'canUsePlayWithYourFood' => true,
                'willBeWoundedIds' => $canUsePlayWithYourFood,
                '_no_notify' => false,
            ];
        }
        return [ '_no_notify' => true ];
    }

    public function onEnteringState(array $args) {
        if ($args['_no_notify']) {
            return ResolveSmashDice::class;
        }
    }

    #[PossibleAction]
    public function actApplySmashDieChoices(#[JsonParam(associative: false)] $smashDieChoices, int $activePlayerId) {
        $playersSmashesWithReducedDamage = [];

        foreach($smashDieChoices as $playerId => $smashDieChoice) {
            if ($smashDieChoice == 'steal') {
                $this->game->applyGiveSymbols([0, 5], $playerId, $activePlayerId, 3000 + PLAY_WITH_YOUR_FOOD_EVOLUTION);
                $playersSmashesWithReducedDamage[$playerId] = 2;
            }
        }

        $this->game->resolveSmashDiceState($playersSmashesWithReducedDamage);
    }
}
