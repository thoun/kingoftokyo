<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class StealCostumeCardOrGiveGiftEvolution extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_STEAL_COSTUME_CARD_OR_GIVE_GIFT_EVOLUTION,
            type: StateType::ACTIVE_PLAYER,
            name: 'stealCostumeCard',
            description: clienttranslate('${actplayer} can steal a Costume card'),
            descriptionMyTurn: clienttranslate('${you} can steal a Costume card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $diceCounts = $this->game->getGlobalVariable(DICE_COUNTS, true);

        $potentialEnergy = $this->game->getPlayerPotentialEnergy($activePlayerId);

        $tableCards = $this->game->powerCards->getTable();
        $disabledIds = array_map(fn($card) => $card->id, $tableCards); // can only take from other players, not table
        $cardsCosts = [];

        $woundedPlayersIds = array_values(array_filter($this->game->playersWoundedByActivePlayerThisTurn($activePlayerId), fn($pId) => $pId != $activePlayerId));
        $canBuyFromPlayers = false;
        $canStealCostumes = $diceCounts[6] >= 3 && $this->game->isHalloweenExpansion();
        if ($canStealCostumes) {
            
            $otherPlayersIds = $this->game->getOtherPlayersIds($activePlayerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $cardsOfPlayer = $this->game->powerCards->getPlayer($otherPlayerId);
                $isWoundedPlayer = in_array($otherPlayerId, $woundedPlayersIds);

                foreach ($cardsOfPlayer as $card) {
                    if ($isWoundedPlayer && $card->type > 200 && $card->type < 300) {
                        $cardsCosts[$card->id] = $this->game->getCardCost($activePlayerId, $card->type);

                        if ($cardsCosts[$card->id] <= $potentialEnergy) {
                            $canBuyFromPlayers = true;
                        }
                    } else {
                        $disabledIds[] = $card->id;
                    }
                }
            }
        }

        $tableGifts = [];
        $canGiveGift = false;
        $highlighted = [];
        if ($diceCounts[6] >= 1 && $this->game->powerUpExpansion->isActive() && $this->game->powerUpExpansion->isGiftCardsInPlay()) {
            $tableGifts = array_values(array_filter($this->game->getEvolutionCardsByLocation('table', $activePlayerId), fn($evolution) => $this->game->EVOLUTION_CARDS_TYPES[$evolution->type] == 3));
            $canGiveGift = count($tableGifts) > 0 || count($this->game->getPlayersIdsWhoCouldPlayEvolutions([$activePlayerId], $this->game->EVOLUTION_GIFTS)) > 0;
            $highlighted = $this->game->getHighlightedEvolutions($this->game->EVOLUTION_GIFTS);
        }

        return [
            'disabledIds' => $disabledIds,
            'woundedPlayersIds' => $woundedPlayersIds,
            'canStealCostumes' => $canStealCostumes,
            'canBuyFromPlayers' => $canBuyFromPlayers,
            'cardsCosts' => $cardsCosts,
            'canGiveGift' => $canGiveGift,
            'tableGifts' => $tableGifts,
            'highlighted' => $highlighted,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            return $this->game->redirectAfterStealCostume($activePlayerId);
        }

        $canBuyFromPlayers = (bool)($args['canBuyFromPlayers'] ?? false);
        $canGiveGift = (bool)($args['canGiveGift'] ?? false);

        if ($this->game->autoSkipImpossibleActions() && !$canBuyFromPlayers && !$canGiveGift) {
            return $this->game->redirectAfterStealCostume($activePlayerId);
        }

        return null;
    }

    #[PossibleAction]
    public function actStealCostumeCard(int $id, int $activePlayerId, array $args) {
        $card = $this->game->powerCards->getItemById($id);
        if (!$card) {
            throw new \BgaUserException('Invalid card id (stealCostumeCard)');
        }

        $from = $card->location_arg;

        if ($card->type < 200 || $card->type > 300) {
            throw new \BgaUserException('Not a Costume card');
        }

        $cost = $this->game->getCardCost($activePlayerId, $card->type);
        if (!$this->game->canAffordCard($activePlayerId, $card->type, $cost)) {
            throw new \BgaUserException('Not enough energy');
        }

        if (!$args['canStealCostumes']) {
            throw new \BgaUserException("You can't steal Costume cards");
        }
        if (in_array($id, $args['disabledIds'])) {
            throw new \BgaUserException("You can't steal this card");
        }

        if ($this->game->getPlayerEnergy($activePlayerId) < $cost) {
            throw new \BgaUserException('Not enough energy');
        }
        $this->game->DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $activePlayerId");

        $this->game->removeCard($from, $card, true, false, true);
        $this->game->powerCards->moveItem($card, 'hand', $activePlayerId);

        $this->notify->all("buyCard", clienttranslate('${player_name} buys ${card_name} from ${player_name2} and pays ${player_name2} ${cost} [energy]'), [
            'playerId' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card' => $card,
            'card_name' => $card->type,
            'newCard' => null,
            'energy' => $this->game->getPlayerEnergy($activePlayerId),
            'from' => $from,
            'player_name2' => $this->game->getPlayerNameById($from),   
            'cost' => $cost,
        ]);

        $this->game->applyGetEnergy($from, $cost, 0);

        // astronaut
        $this->game->applyAstronaut($activePlayerId);

        $this->game->incStat(1, 'costumeStolenCards', $activePlayerId);
     
        // no damage to handle on costume cards

        // if player steal Zombie, it can eliminate the previous owner
        $this->game->updateKillPlayersScoreAux();
        $this->game->eliminatePlayers($activePlayerId);

        $this->game->goToState(ST_PLAYER_STEAL_COSTUME_CARD_OR_GIVE_GIFT_EVOLUTION);
    }

    #[PossibleAction]
    public function actGiveGiftEvolution(int $id, int $toPlayerId, int $currentPlayerId) {
        $fromPlayerId = $currentPlayerId;
        $evolution = $this->game->getEvolutionCardById($id);

        $this->game->giveEvolution($fromPlayerId, $toPlayerId, $evolution);

        $this->game->goToState(ST_PLAYER_STEAL_COSTUME_CARD_OR_GIVE_GIFT_EVOLUTION);
    }

    #[PossibleAction]
    public function actEndStealCostume(int $activePlayerId) {
        $this->game->goToState($this->game->redirectAfterStealCostume($activePlayerId));
    }

    public function zombie(int $playerId) {
        return $this->actEndStealCostume($playerId);
    }
}
