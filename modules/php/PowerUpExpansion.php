<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class PowerUpExpansion {
    public EvolutionCardManager $evolutionCards;

    function __construct(
        protected Game $game,
    ) {
        $this->evolutionCards = new EvolutionCardManager($game);
    }

    public function initDb() {
        $this->evolutionCards->initDb();
    }

    public function setup(array $affectedPlayersMonsters) {
        $this->evolutionCards->setup($affectedPlayersMonsters);
    }

    public function isActive(): bool {
        return !$this->game->isOrigins() && $this->game->tableOptions->get(POWERUP_EXPANSION_OPTION) >= 2;
    }
    
    function isPowerUpMutantEvolution(): bool {
        return $this->isActive() && $this->game->tableOptions->get(POWERUP_EXPANSION_OPTION) === 3;
    }

    function getMonstersWithPowerUpCards() {
        $monstersWithPowerUpCards = [1,2,3,4,5,6,/*TODOPUKK 11,*//*TODOPUCT 12,*/13,14,15/* TODOPUBG ,18*/];

        /* TODOPUHA if ($this->game->isHalloweenExpansion()) {
            $monstersWithPowerUpCards = array_merge($monstersWithPowerUpCards, [7, 8]);
        }*/

        if ($this->game->mindbugExpansion->isActive()) {
            $monstersWithPowerUpCards = array_merge($monstersWithPowerUpCards, [61, 62, 63]);
        }

        return $monstersWithPowerUpCards;
    }

    function applyChooseEvolutionCard(int $playerId, int $id, bool $init): void {
        $topCards = $this->pickEvolutionCards($playerId);
        $card = Arrays::find($topCards, fn($topCard) => $topCard->id == $id);
        if ($card == null) {
            throw new \BgaUserException('Evolution card not available');
        }
        $otherCard = Arrays::find($topCards, fn($topCard) => $topCard->id != $id);

        $this->evolutionCards->moveCard($card, 'hand', $playerId);
        $this->evolutionCards->moveCard($otherCard, 'discard'.$playerId);

        $this->game->incStat(1, 'picked'.$this->game->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->game->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);

        $message = $init ? '' : clienttranslate('${player_name} ends his rolls with at least 3 [diceHeart] and takes a new Evolution card');
        $this->game->notifNewEvolutionCard($playerId, $card, $message);
        
    }

    function applyPlayEvolution(int $playerId, EvolutionCard $card): void {
        $countMothershipSupportBefore = $this->game->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);

        $this->evolutionCards->moveCard($card, 'table', $playerId);

        $this->game->playEvolutionToTable($playerId, $card);
        
        $damages = $this->game->applyEvolutionEffects($card, $playerId);
        $this->game->updateCancelDamageIfNeeded($playerId);
        
        if (in_array($card->type, $this->game->AUTO_DISCARDED_EVOLUTIONS)) {
            $this->game->removeEvolution($playerId, $card, false, 5000);
        }
        
        $this->game->toggleMothershipSupport($playerId, $countMothershipSupportBefore);

        if ($damages != null && count($damages) > 0) {
            $this->game->addStackedState();
            $this->game->goToState(-1, $damages);
        }
    }

    function isGiftCardsInPlay() {
        $players = $this->game->getPlayers(true);
        return Arrays::some($players, fn($player) => in_array($player->monster % 100, [7, 8]));
    }

    function checkCanPlayEvolution(EvolutionCard $evolution, int $playerId) {
        $evolution->checkCanPlay(new Context($this->game, $playerId));
        $cardType = $evolution->type;
        $stateId = $this->game->gamestate->getCurrentMainStateId();

        if ($stateId < 17) {
            throw new \BgaUserException(clienttranslate("You can only play evolution cards when the game is started"));
        }

        // cards to player before starting turn
        if (in_array($cardType, $this->game->EVOLUTION_TO_PLAY_BEFORE_START_TEMPORARY) && $stateId != ST_PLAYER_BEFORE_START_TURN) {
            throw new \BgaUserException(clienttranslate("You can only play this evolution card before starting turn"));
        }

        if (in_array($cardType, $this->game->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE_MULTI_OTHERS) && $stateId != ST_PLAYER_BEFORE_RESOLVE_DICE_MULTI) {
            throw new \BgaUserException(clienttranslate("You can only play this evolution card when resolving dice"));
        }

        if (in_array($cardType, $this->game->EVOLUTION_TO_PLAY_DURING_RESOLVE_DICE) && $stateId != ST_PLAYER_DURING_RESOLVE_DICE) {
            throw new \BgaUserException(clienttranslate("You can only play this evolution card when resolving dice"));
        }

        if (in_array($cardType, $this->game->EVOLUTION_TO_PLAY_BEFORE_ENTERING_TOKYO) && $stateId != ST_MULTIPLAYER_BEFORE_ENTERING_TOKYO) {
            throw new \BgaUserException(clienttranslate("You can only play this evolution card before entering Tokyo"));
        }

        if (in_array($cardType, [...$this->game->EVOLUTION_TO_PLAY_AFTER_NOT_ENTERING_TOKYO, ...$this->game->EVOLUTION_TO_PLAY_AFTER_ENTERING_TOKYO]) && $stateId != ST_PLAYER_AFTER_ENTERING_TOKYO) {
            throw new \BgaUserException(clienttranslate("You can only play this evolution card after entering Tokyo"));
        }

        // cards to player when card is bought
        if (in_array($cardType, $this->game->EVOLUTION_TO_PLAY_WHEN_CARD_IS_BOUGHT) && $stateId != ST_MULTIPLAYER_WHEN_CARD_IS_BOUGHT) {
            $canPlay = $cardType == BAMBOOZLE_EVOLUTION && $playerId != intval($this->game->getActivePlayerId());
            if (!$canPlay) {
                throw new \BgaUserException(clienttranslate("You can only play this evolution card when another player is buying a card"));
            }
        }
    }

    function pickEvolutionCards(int $playerId, int $number = 2) {
        $remainingInDeck = $this->evolutionCards->countCardsInLocation('deck'.$playerId);
        if ($remainingInDeck >= $number) {
            return $this->game->getEvolutionCardsOnDeckTop($playerId, $number);
        } else {
            $cards = $this->game->getEvolutionCardsOnDeckTop($playerId, $remainingInDeck);

            $this->evolutionCards->moveAllCardsInLocation('discard'.$playerId, 'deck'.$playerId);
            $this->evolutionCards->shuffle('deck'.$playerId);

            $cards = array_merge(
                $cards,
                $this->game->getEvolutionCardsOnDeckTop($playerId, $number - $remainingInDeck)
            );
            return $cards;
        }
    }

    function drawEvolution(int $playerId, ?int $fromPlayerId = null) {
        $card = $this->pickEvolutionCards($fromPlayerId ?? $playerId, 1)[0];

        $this->evolutionCards->moveCard($card, 'hand', $playerId);

        $this->game->notifNewEvolutionCard($playerId, $card);

        $this->game->incStat(1, 'picked'.$this->game->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->game->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);
    }

    function getEvolutionFromDiscard(int $playerId, int $evolutionId) {
        $card = $this->evolutionCards->getCardById($evolutionId);

        $this->evolutionCards->moveCard($card, 'hand', $playerId);

        $this->game->notifNewEvolutionCard($playerId, $card);

        $this->game->incStat(1, 'picked'.$this->game->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->game->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);
    }

    function getHighlightedEvolutions(array $stepCardsTypes) {
        $cards = [];

        foreach ($stepCardsTypes as $stepCardsType) {
            $stepCards = $this->game->getEvolutionCardsByType($stepCardsType);
            foreach ($stepCards as $stepCard) {
                if ($stepCard->location == 'hand') {
                    $cards[] = $stepCard;
                }
            }
        }

        return $cards;
    }
}
