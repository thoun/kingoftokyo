<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class OpportunistBuyCard extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'opportunistBuyCard',
            description: clienttranslate('Player with Opportunist can buy revealed card'),
            descriptionMyTurn: clienttranslate('${you} can buy revealed card'),
            transitions: [
                'stay' => \ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD,
                'end' => \ST_PLAYER_BUY_CARD,
            ],
        );
    }

    public function getArgs(): array {
        $opportunistIntervention = $this->game->getGlobalVariable(OPPORTUNIST_INTERVENTION);

        $playerId = $opportunistIntervention && count($opportunistIntervention->remainingPlayersId) > 0 ? $opportunistIntervention->remainingPlayersId[0] : null;
        if ($playerId != null) {
            return $this->argOpportunistBuyCardWithPlayerId($playerId);
        } else {
            return [
                'canBuy' => false,
            ];
        }
    }

    public function onEnteringState(): void {
        if ($this->game->autoSkipImpossibleActions()) {
            $intervention = $this->game->getGlobalVariable(\OPPORTUNIST_INTERVENTION);
            if ($intervention !== null) {
                $remainingPlayersId = [];
                foreach ($intervention->remainingPlayersId as $playerId) {
                    if ($this->argOpportunistBuyCardWithPlayerId($playerId)['canBuy']) {
                        $remainingPlayersId[] = $playerId;
                    } else {
                        $this->game->removeDiscardCards($playerId);
                    }
                }
                $intervention->remainingPlayersId = $remainingPlayersId;
                $this->game->setGlobalVariable(\OPPORTUNIST_INTERVENTION, $intervention);
            }
        }

        $this->game->stIntervention(\OPPORTUNIST_INTERVENTION);
    }

    #[PossibleAction]
    function actBuyCard(int $id, bool $useSuperiorAlienTechnology = false, bool $useBobbingForApples = false) {
        return $this->game->actBuyCard($id, $useSuperiorAlienTechnology, $useBobbingForApples);
    }

    #[PossibleAction]
    function actOpportunistSkip(int $currentPlayerId) {
        $this->game->removeDiscardCards($currentPlayerId);

        $this->game->setInterventionNextState(OPPORTUNIST_INTERVENTION, 'next', ST_PLAYER_BUY_CARD);
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'stay');
    }

    public function zombie(int $playerId) {
        return $this->actOpportunistSkip($playerId);
    }

    function argOpportunistBuyCardWithPlayerId(int $playerId) {        
        $opportunistIntervention = $this->game->getGlobalVariable(OPPORTUNIST_INTERVENTION);
        $revealedCardsIds = $opportunistIntervention ? $opportunistIntervention->revealedCardsIds : [];
        $canBuy = false;
        $canBuyPowerCards = $this->game->canBuyPowerCard($playerId);

        $potentialEnergy = $this->game->getPlayerPotentialEnergy($playerId);

        $cards = $this->game->powerCards->getTable();
        $cardsCosts = [];
        
        $disabledIds = [];
        $warningIds = [];
        foreach ($cards as $card) {
            if (in_array($card->id, $revealedCardsIds)) {
                $cardsCosts[$card->id] = $this->game->getCardCost($playerId, $card->type);
                if ($canBuyPowerCards && $cardsCosts[$card->id] <= $potentialEnergy) {
                    $canBuy = true;
                }
                if (!$canBuyPowerCards) {
                    $disabledIds[] = $card->id;
                }

                $this->game->setWarningIcon($playerId, $warningIds, $card);
            } else {
                $disabledIds[] = $card->id;
            }
        }

        return [
            'disabledIds' => $disabledIds,
            'canBuy' => $canBuy,
            'cardsCosts' => $cardsCosts,
            'warningIds' => $warningIds,
            'noExtraTurnWarning' => $this->game->mindbugExpansion->canGetExtraTurn() ? [] : [FRENZY_CARD],
        ];
    }
}
