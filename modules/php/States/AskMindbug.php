<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Components\Counters\OutOfRangeCounterException;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class AskMindbug extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_MULTIPLAYER_ASK_MINDBUG,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,

            description: /*TODOMB clienttranslate*/('Player with Mindbug tokens can mindbug ${player_name}'),
            descriptionMyTurn: /*TODOMB clienttranslate*/('${you} can mindbug ${player_name}'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $result = $this->game->mindbugExpansion->getPlayersThatCanMindbug($activePlayerId);
        $canMindbug = $result['canMindbug'];
        $canUseToken = $result['canUseToken'];
        $canUseEvasiveMindbug = $result['canUseEvasiveMindbug'];

        return [
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'canMindbug' => $canMindbug,
            'canUseToken' => $canUseToken,
            'canUseEvasiveMindbug' => $canUseEvasiveMindbug,
            '_no_notify' => count($canMindbug) === 0,
        ];
    }

    function onEnteringState(array $args) {
        if ($args['_no_notify']) {
            return ResolveDieOfFate::class;
        } else {
            $this->game->gamestate->setPlayersMultiactive($args['canMindbug'], ResolveDieOfFate::class, true);
        }
    }
     
    #[PossibleAction]
    public function actMindbug(bool $useEvasiveMindbug, int $currentPlayerId) {
        if ($useEvasiveMindbug) {
            $mindbuggedPlayerId = (int)$this->game->getActivePlayerId();
            $evasiveMindbugCard = null;
            foreach ($this->game->getCardsOfType($currentPlayerId, EVASIVE_MINDBUG_CARD) as $card) {
                if ($card->type === EVASIVE_MINDBUG_CARD) {
                    $evasiveMindbugCard = $card;
                    break;
                }
            }

            if ($evasiveMindbugCard == null) {
                throw new \BgaUserException('No Evasive Mindbug card');
            }

            $this->game->powerCards->moveItem($evasiveMindbugCard, 'hand', $mindbuggedPlayerId);

            $this->game->notify->all("buyCard", clienttranslate('${player_name2} gives ${card_name} to ${player_name}'), [
                'playerId' => $mindbuggedPlayerId,
                'player_name' => $this->game->getPlayerNameById($mindbuggedPlayerId),
                'mindbuggedPlayerId' => $currentPlayerId,
                'player_name2' => $this->game->getPlayerNameById($currentPlayerId),
                'card' => $evasiveMindbugCard,
                'card_name' => EVASIVE_MINDBUG_CARD,
            ]);
        } else {
            try {
                $this->game->mindbugExpansion->mindbugTokens->inc($currentPlayerId, -1);
            } catch (OutOfRangeCounterException $e) { 
                throw new \BgaUserException('No Mindbug tokens');
            }
        }

        $this->game->mindbugExpansion->setMindbuggedPlayer($currentPlayerId, (int)$this->game->getActivePlayerId());

        // first to click has the power!
        return ResolveDieOfFate::class;
    }

    #[PossibleAction]
    public function actPassMindbug(int $currentPlayerId) {
        $this->game->gamestate->setPlayerNonMultiactive($currentPlayerId, ResolveDieOfFate::class);
    }

    public function zombie(int $playerId) {
        return $this->actPassMindbug($playerId);
    }
}
