<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;

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

    function applyChooseEvolutionCard(int $playerId, int $id, bool $init): void {
        $topCards = $this->game->pickEvolutionCards($playerId);
        $card = Arrays::find($topCards, fn($topCard) => $topCard->id == $id);
        if ($card == null) {
            throw new \BgaUserException('Evolution card not available');
        }
        $otherCard = Arrays::find($topCards, fn($topCard) => $topCard->id != $id);

        $this->evolutionCards->moveItem($card, 'hand', $playerId);
        $this->evolutionCards->moveItem($otherCard, 'discard'.$playerId);

        $this->game->incStat(1, 'picked'.$this->game->EVOLUTION_CARDS_TYPES_FOR_STATS[$this->game->EVOLUTION_CARDS_TYPES[$card->type]], $playerId);

        $message = $init ? '' : clienttranslate('${player_name} ends his rolls with at least 3 [diceHeart] and takes a new Evolution card');
        $this->game->notifNewEvolutionCard($playerId, $card, $message);
        
    }

    function applyPlayEvolution(int $playerId, EvolutionCard $card): void {
        $countMothershipSupportBefore = $this->game->countEvolutionOfType($playerId, MOTHERSHIP_SUPPORT_EVOLUTION);

        $this->evolutionCards->moveItem($card, 'table', $playerId);

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

    function checkCanPlayEvolution(int $cardType, int $playerId) {
        $stateId = $this->game->gamestate->getCurrentMainStateId();

        if ($stateId < 17) {
            throw new \BgaUserException(clienttranslate("You can only play evolution cards when the game is started"));
        }

        // cards to player before starting turn
        if (in_array($cardType, $this->game->EVOLUTION_TO_PLAY_BEFORE_START_TEMPORARY) && $stateId != ST_PLAYER_BEFORE_START_TURN) {
            throw new \BgaUserException(clienttranslate("You can only play this evolution card before starting turn"));
        }

        if (in_array($cardType, $this->game->EVOLUTION_TO_PLAY_BEFORE_RESOLVE_DICE) && $stateId != ST_PLAYER_BEFORE_RESOLVE_DICE) {
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

        if ($this->game->EVOLUTION_CARDS_TYPES[$cardType] == 3) {
            throw new \BgaUserException(/*clienttranslateTODOPUHA*/("You can only play this evolution now, you'll be asked to use it when you wound a Monster"));
        }

        switch($cardType) {
            case NINE_LIVES_EVOLUTION:
            case SIMIAN_SCAMPER_EVOLUTION:
            case DETACHABLE_TAIL_EVOLUTION:
            case RABBIT_S_FOOT_EVOLUTION:
                throw new \BgaUserException(clienttranslate("You can't play this Evolution now, you'll be asked to use it when you'll take damage"));
            case SAURIAN_ADAPTABILITY_EVOLUTION:
                $message = $stateId === ST_PLAYER_CHANGE_DIE ? 
                    clienttranslate("Click on a die face you want to change") :
                    clienttranslate("You can't play this Evolution now, you'll be asked to use it when you change your dice result");
                throw new \BgaUserException($message);
            case FELINE_MOTOR_EVOLUTION:
                $startedTurnInTokyo = $this->game->getGlobalVariable(STARTED_TURN_IN_TOKYO, true);
                if (in_array($playerId, $startedTurnInTokyo)) {
                    throw new \BgaUserException(clienttranslate("You started your turn in Tokyo"));
                }
                break;
            case TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION:
            case EATS_SHOOTS_AND_LEAVES_EVOLUTION:
                if (!$this->game->inTokyo($playerId)) {
                    throw new \BgaUserException(clienttranslate("You can play this Evolution only if you are in Tokyo"));
                }
                break;
            case JUNGLE_FRENZY_EVOLUTION:
                if ($playerId != intval($this->game->getActivePlayerId())) {
                    throw new \BgaUserException(clienttranslate("You must play this Evolution during your turn"));
                }
                if ($this->game->inTokyo($playerId)) {
                    throw new \BgaUserException(clienttranslate("You can play this Evolution only if you are not in Tokyo"));
                }
                if (!$this->game->isDamageDealtThisTurn($playerId)) {
                    throw new \BgaUserException(clienttranslate("You didn't deal damage to a player in Tokyo"));
                }
                break;
            case TUNE_UP_EVOLUTION:
                if ($this->game->inTokyo($playerId)) {
                    throw new \BgaUserException(clienttranslate("You can play this Evolution only if you are not in Tokyo"));
                }
                break;
            case BLIZZARD_EVOLUTION:
                if ($playerId != intval($this->game->getActivePlayerId())) {
                    throw new \BgaUserException(clienttranslate("You must play this Evolution during your turn"));
                }
                break;
            case ICY_REFLECTION_EVOLUTION:
                $playersIds = $this->game->getPlayersIds();
                $canPlayIcyReflection = false;
                foreach($playersIds as $playerId) {
                    $evolutions = $this->game->getEvolutionCardsByLocation('table', $playerId);
                    if (Arrays::some($evolutions, fn($evolution) => $evolution->type != ICY_REFLECTION_EVOLUTION && $this->game->EVOLUTION_CARDS_TYPES[$evolution->type] == 1)) {
                        $canPlayIcyReflection = true; // if there is a permanent evolution card in table
                    }
                }
                if (!$canPlayIcyReflection) {
                    throw new \BgaUserException(clienttranslate("You can only play this evolution card when there is another permanent Evolution on the table"));
                }
                break;
        }
    }
}
