<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use KOT\Objects\OpportunistIntervention;
use KOT\Objects\Question;

class BuyCard extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_BUY_CARD,
            type: StateType::ACTIVE_PLAYER,
            name: 'buyCard',
            description: clienttranslate('${actplayer} can buy a card'),
            descriptionMyTurn: clienttranslate('${you} can buy a card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        return $this->game->getArgBuyCard($activePlayerId, true);
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        $this->game->deleteGlobalVariable(\OPPORTUNIST_INTERVENTION);

        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            return $this->actEndTurn($activePlayerId);
        }

        $buyArgs = $args ?: $this->getArgs($activePlayerId);

        $shouldSkip =
            $this->game->countCardOfType($activePlayerId, \HIBERNATION_CARD) > 0
            || (bool)$this->game->getGameStateValue(\SKIP_BUY_PHASE)
            || ($this->game->autoSkipImpossibleActions() && empty($buyArgs['canBuyOrNenew']))
            || $this->game->isSureWin($activePlayerId)
            || ($this->game->isMutantEvolutionVariant() && $this->game->isBeastForm($activePlayerId));

        if ($shouldSkip) {
            if (!empty($buyArgs['canSell'])) {
                return $this->actGoToSellCard($activePlayerId);
            } else {
                return $this->actEndTurn($activePlayerId);
            }
        }
    }

    #[PossibleAction]
    function actBuyCard(int $id, int $from, bool $useSuperiorAlienTechnology = false, bool $useBobbingForApples = false) {
        return $this->game->actBuyCard($id, $from, $useSuperiorAlienTechnology, $useBobbingForApples);
    }

    #[PossibleAction]
    function actRenewPowerCards(?int $cardType, int $activePlayerId) {
        if ($cardType == 3024) {
            $adaptiveTechnologyCards = $this->game->getEvolutionsOfType($activePlayerId, ADAPTING_TECHNOLOGY_EVOLUTION, true, true);

            if (count($adaptiveTechnologyCards) == 0) {
                throw new \BgaUserException('No matching card');
            }

            // we use in priority Icy Reflection
            $adaptiveTechnologyCard = Arrays::find($adaptiveTechnologyCards, fn($card) => $card->type == ICY_REFLECTION_EVOLUTION);
            if ($adaptiveTechnologyCard === null) {
                $adaptiveTechnologyCard = $adaptiveTechnologyCards[0];
            }

            if ($adaptiveTechnologyCard->location === 'hand') {
                $this->game->powerUpExpansion->applyPlayEvolution($activePlayerId, $adaptiveTechnologyCard);
                $this->game->applyEvolutionEffects($adaptiveTechnologyCard, $activePlayerId);
                $adaptiveTechnologyCard = $this->game->powerUpExpansion->evolutionCards->getCardById($adaptiveTechnologyCard->id);
            }
            $tokens = $adaptiveTechnologyCard->tokens - 1;
            $this->game->setEvolutionTokens($activePlayerId, $adaptiveTechnologyCard, $tokens);
            if ($tokens == 0) {
                $this->game->removeEvolution($activePlayerId, $adaptiveTechnologyCard);
            }
        } else {
            if ($this->game->getPlayerEnergy($activePlayerId) < 2) {
                throw new \BgaUserException('Not enough energy');
            }

            $cost = 2;
            $this->game->DbQuery("UPDATE player SET `player_energy` = `player_energy` - $cost where `player_id` = $activePlayerId");
        }

        $this->game->removeDiscardCards($activePlayerId);

        $this->game->powerCards->moveAllCardsInLocation('table', 'discard');
        $cards = $this->game->placeNewCardsOnTable();

        $notifArgs = [
            'playerId' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'cards' => $cards,
            'energy' => $this->game->getPlayerEnergy($activePlayerId),
            'deckCardsCount' => $this->game->powerCards->getDeckCount(),
            'topDeckCard' => $this->game->powerCards->getTopDeckCard(),
        ];        
        if ($cardType == 3024) {
            $this->notify->all("renewCards", /*client TODOPUtranslate(*/'${player_name} renews visible cards using ${card_name}'/*)*/, 
                array_merge($notifArgs, ['card_name' => 3000 + ADAPTING_TECHNOLOGY_EVOLUTION]));
        } else {
            $this->notify->all("renewCards", clienttranslate('${player_name} renews visible cards'), 
                $notifArgs);
        }

        $playersWithOpportunist = $this->game->getPlayersWithOpportunist($activePlayerId);

        if (count($playersWithOpportunist) > 0) {
            $renewedCardsIds = array_map(fn($card) => $card->id, $cards);
            $opportunistIntervention = new OpportunistIntervention($playersWithOpportunist, $renewedCardsIds);
            $this->game->setGlobalVariable(OPPORTUNIST_INTERVENTION, $opportunistIntervention);
            return \ST_MULTIPLAYER_OPPORTUNIST_BUY_CARD;
        } else {
            return BuyCard::class;
        }
    }

    #[PossibleAction]
    function actGoToSellCard(int $activePlayerId) {
        $this->game->removeDiscardCards($activePlayerId);

        return SellCard::class;
    }

    #[PossibleAction]
    function actEndTurn(int $activePlayerId) {
        $this->game->removeDiscardCards($activePlayerId);

        $damages = $this->game->mindbugExpansion->applyEndFrenzy($activePlayerId);
        $this->game->goToState($this->game->redirectAfterSellCard(), $damages);
    }
  	
    #[PossibleAction]
    public function actUseMiraculousCatch(int $activePlayerId) {
        $evolution = $this->game->getFirstUnusedEvolution($activePlayerId, MIRACULOUS_CATCH_EVOLUTION, true, true);
        if ($evolution === null) {
            throw new \BgaUserException("No unused Miraculous catch");
        }
        if ($this->game->powerCards->countCardsInLocation('discard') === 0) {
            throw new \BgaUserException("No cards in discard pile");
        }
        
        if ($evolution->location === 'hand') {
            $this->game->powerUpExpansion->applyPlayEvolution($activePlayerId, $evolution);
        }

        $this->game->powerCards->shuffle('discard');
        $card = $this->game->powerCards->getCardOnTopOldOrder('discard');

        $cost = $this->game->getCardCost($activePlayerId, $card->type) - 1;
        $canUseSuperiorAlienTechnology = 
            $card->type < 100 && 
            $this->game->countEvolutionOfType($activePlayerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION, true, true) > 0 && 
            count($this->game->getSuperiorAlienTechnologyTokens($activePlayerId)) < 3 * $this->game->countEvolutionOfType($activePlayerId, SUPERIOR_ALIEN_TECHNOLOGY_EVOLUTION);

        $question = new Question(
            'MiraculousCatch',
            clienttranslate('${actplayer} can buy ${card_name} from the discard pile for 1[Energy] less'),
            clienttranslate('${you} can buy ${card_name} from the discard pile for 1[Energy] less'),
            [$activePlayerId],
            ST_QUESTIONS_BEFORE_START_TURN,
            [
                'card' => $card,
                'cost' => $cost,
                'costSuperiorAlienTechnology' => $canUseSuperiorAlienTechnology ? ceil($cost / 2) : null,
                '_args' => [
                    'card_name' => $card->type,
                ],
            ],
            evolutionId: $evolution->id,
        );
        $this->game->setQuestion($question);
        $this->gamestate->setPlayersMultiactive([$activePlayerId], 'next', true);

        $this->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function zombie(int $playerId) {
        $this->actEndTurn($playerId);
    }
}
