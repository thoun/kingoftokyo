<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

use const Bga\Games\KingOfTokyo\PowerCards\HUNTER;
use const Bga\Games\KingOfTokyo\PowerCards\SNEAKY;

class BeforeStartTurn extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_BEFORE_START_TURN,
            type: StateType::ACTIVE_PLAYER,
            name: 'beforeStartTurn',
            description: clienttranslate('${actplayer} may activate an Evolution card'),
            descriptionMyTurn: clienttranslate('${you} may activate an Evolution card'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        $playerCards = $this->game->powerCards->getPlayerReal($activePlayerId);
        $consumableCards = Arrays::filter($playerCards, fn($card) => $card->mindbugKeywords !== null && Arrays::some($card->mindbugKeywords, fn($keyword) => in_array($keyword, [HUNTER, SNEAKY])));

        $highlighted = [];
        if ($isPowerUpExpansion) {
            $highlighted = $this->game->getHighlightedEvolutions($this->game->EVOLUTION_TO_PLAY_BEFORE_START);
        }

        return [
            'highlighted' => $highlighted,
            'consumableCards' => $consumableCards,
        ];
    }

    public function onEnteringState(int $activePlayerId, array $args) {
        $this->game->DbQuery("DELETE FROM `turn_damages`");
        $this->game->DbQuery("UPDATE `player` SET `player_turn_energy` = 0, `player_turn_health` = 0, `player_turn_gained_health` = 0, `player_turn_entered_tokyo` = 0");
        $this->game->setGameStateValue(\EXTRA_ROLLS, 0);
        $this->game->setGameStateValue(\PSYCHIC_PROBE_ROLLED_A_3, 0);
        $this->game->setGameStateValue(\SKIP_BUY_PHASE, 0);
        $this->game->setGameStateValue(\CLOWN_ACTIVATED, 0);
        $this->game->setGameStateValue(\CHEERLEADER_SUPPORT, 0);
        $this->game->setGameStateValue(\RAGING_FLOOD_EXTRA_DIE, 0);
        $this->game->setGameStateValue(\RAGING_FLOOD_EXTRA_DIE_SELECTED, 0);
        $this->game->setGlobalVariable(\MADE_IN_A_LAB, []);
        $this->game->resetUsedCards();
        $this->game->setGlobalVariable(\USED_WINGS, []);
        $this->game->setGlobalVariable(\UNSTABLE_DNA_PLAYERS, []);
        $this->game->setGlobalVariable(\CARD_BEING_BOUGHT, null);
        $this->game->setGlobalVariable(\STARTED_TURN_IN_TOKYO, $this->game->getPlayersIdsInTokyo());

        $isPowerUpExpansion = $this->game->powerUpExpansion->isActive();

        if ($isPowerUpExpansion) {
            $blizzardCards = $this->game->getEvolutionsOfType($activePlayerId, \BLIZZARD_EVOLUTION);
            if (count($blizzardCards) > 0) {
                $this->game->removeEvolutions($activePlayerId, $blizzardCards);
            }

            $player = $this->game->getPlayer($activePlayerId);
            if ($player->askPlayEvolution == 2) {
                $this->game->applyAskPlayEvolution($activePlayerId, 0);
            }
        }

        $canPlayConsumableCard = count($args['consumableCards']) > 0;

        if (!$canPlayConsumableCard && (!$isPowerUpExpansion || count($this->game->getPlayersIdsWhoCouldPlayEvolutions([$activePlayerId], $this->game->EVOLUTION_TO_PLAY_BEFORE_START)) == 0)) {
            $this->game->goToState($this->game->redirectAfterBeforeStartTurn());
        }
    }

    #[PossibleAction]
    public function actSkipBeforeStartTurn(int $currentPlayerId) {
        $this->game->goToState($this->game->redirectAfterBeforeStartTurn());
    }

    public function zombie(int $playerId) {
        $this->actSkipBeforeStartTurn($playerId);
    }
}

