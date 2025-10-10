<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ResolveHeartDiceAction extends GameState {

    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_RESOLVE_HEART_DICE_ACTION,
            type: StateType::ACTIVE_PLAYER,
            name: 'resolveHeartDiceAction',
            description: clienttranslate('${actplayer} can select effect of [diceHeart] dice'),
            descriptionMyTurn: clienttranslate('${you} can select effect of [diceHeart] dice'),
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

            return $selectHeartDiceUseArg + [
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

    #[PossibleAction]
    function actApplyHeartDieChoices(#[JsonParam(associative: false)] array $heartDieChoices, int $activePlayerId) {
        $heal = 0;
        $removeShrinkRayToken = 0;
        $removePoisonToken = 0;
        $healPlayer = [];

        $healPlayerCount = 0;
        foreach ($heartDieChoices as $heartDieChoice) {
            if ($heartDieChoice->action == 'heal-player') {
                $healPlayerCount++;
            }
        }
        if ($healPlayerCount > 0 && $this->game->countCardOfType($activePlayerId, HEALING_RAY_CARD) == 0) {
            throw new \BgaUserException('No Healing Ray card');
        }
        
        foreach ($heartDieChoices as $heartDieChoice) {
            switch ($heartDieChoice->action) {
                case 'heal':
                    $heal++;
                    break;
                case 'shrink-ray':
                    $removeShrinkRayToken++;
                    break;
                case 'poison':
                    $removePoisonToken++;
                    break;
                case 'heal-player':
                    if (array_key_exists($heartDieChoice->playerId, $healPlayer)) {
                        $healPlayer[$heartDieChoice->playerId] = $healPlayer[$heartDieChoice->playerId] + 1;
                    } else {
                        $healPlayer[$heartDieChoice->playerId] = 1;
                    }
                    break;
            }
        }

        if ($heal > 0) {
            $this->game->resolveHealthDice($activePlayerId, $heal);
        }

        if ($removeShrinkRayToken > 0) {
            $this->game->removeShrinkRayToken($activePlayerId, $removeShrinkRayToken);
        }
        if ($removePoisonToken > 0) {
            $this->game->removePoisonToken($activePlayerId, $removePoisonToken);
        }

        foreach ($healPlayer as $healPlayerId => $healNumber) {
            $this->game->applyGetHealth($healPlayerId, $healNumber, 0, $activePlayerId);
            
            $playerEnergy = $this->game->getPlayerEnergy($healPlayerId);
            $theoricalEnergyLoss = $healNumber * 2;
            $energyLoss = min($playerEnergy, $theoricalEnergyLoss);
            
            $this->game->applyLoseEnergy($healPlayerId, $energyLoss, 0);
            $this->game->applyGetEnergy($activePlayerId, $energyLoss, 0);

            $this->notify->all("resolveHealingRay", clienttranslate('${player_name2} gains ${healNumber} [Heart] with ${card_name} and pays ${player_name} ${energy} [Energy]'), [
                'player_name' => $this->game->getPlayerNameById($activePlayerId),
                'player_name2' => $this->game->getPlayerNameById($healPlayerId),
                'energy' => $energyLoss,
                'healedPlayerId' => $healPlayerId,
                'healNumber' => $healNumber,
                'card_name' => HEALING_RAY_CARD
            ]);
        }

        return ST_RESOLVE_ENERGY_DICE;
    }

    function zombie(int $playerId) {
        // TODO
        return ST_RESOLVE_ENERGY_DICE;
    }
}

