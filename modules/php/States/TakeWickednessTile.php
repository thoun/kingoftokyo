<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;

use const Bga\Games\KingOfTokyo\FINAL_PUSH_WICKEDNESS_TILE;
use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

class TakeWickednessTile extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_TAKE_WICKEDNESS_TILE,
            type: StateType::ACTIVE_PLAYER,
            name: 'takeWickednessTile',
            description: clienttranslate('${actplayer} can take a wickedness tile'),
            descriptionMyTurn: clienttranslate('${you} can take a wickedness tile'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $level = $this->game->wickednessExpansion->canTakeWickednessTile($activePlayerId);
        $tableTiles = $this->game->wickednessTiles->getTable($level);

        $dice = $this->game->getPlayerRolledDice($activePlayerId, false, false, false);
        $canHealWithDice = $this->game->canHealWithDice($activePlayerId);
    
        return [
            'level' => $level,
            'canTake' => count($tableTiles) > 0,

            'dice' => $dice,
            'canHealWithDice' => $canHealWithDice,
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'noExtraTurnWarning' => $this->game->mindbugExpansion->canGetExtraTurn() ? [] : [FINAL_PUSH_WICKEDNESS_TILE],
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        // if player is dead async, he can't buy or sell
        if ($this->game->getPlayer($activePlayerId)->eliminated) {
            return $this->actSkipTakeWickednessTile($activePlayerId);
        }

        if ($this->game->autoSkipImpossibleActions()) {      
            $level = $this->game->wickednessExpansion->canTakeWickednessTile($activePlayerId);
            $tableTiles = $this->game->wickednessTiles->getTable($level);
        
            if (count($tableTiles) == 0) {
                return $this->actSkipTakeWickednessTile($activePlayerId);
            }
        }
    }

    #[PossibleAction]
    public function actTakeWickednessTile(int $id, int $currentPlayerId) {
        $level = $this->game->wickednessExpansion->canTakeWickednessTile($currentPlayerId);
        $tile = $this->game->wickednessTiles->getItemById($id);

        $tableTiles = $this->game->wickednessTiles->getTable($level);
        if (!Arrays::some($tableTiles, fn($t) => $t->id === $tile->id)) {
            throw new \BgaUserException("This tile is not on the table");
        }

        $this->game->wickednessTiles->moveItem($tile, 'hand', $currentPlayerId);

        $this->notify->all("takeWickednessTile", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $currentPlayerId,
            'player_name' => $this->game->getPlayerNameById($currentPlayerId),
            'tile' => $tile,
            'card_name' => 2000 + $tile->type,
            'level' => $level,
        ]);

        $this->game->incStat(1, 'wickednessTilesTaken', $currentPlayerId);

        $this->removeFirstTakeWickednessTileLevel($currentPlayerId);

        $damages = $this->game->wickednessTiles->immediateEffect($tile, new Context($this->game, currentPlayerId: $currentPlayerId));

        $mimic = false;
        if ($tile->type === FLUXLING_WICKEDNESS_TILE) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->game->getPlayersIds();
            foreach($playersIds as $pId) {
                $cardsOfPlayer = $this->game->powerCards->getPlayer($pId);
                $countAvailableCardsForMimic += count(array_values(array_filter($cardsOfPlayer, fn($card) => $card->type != MIMIC_CARD && $card->type < 100)));
            }

            if ($countAvailableCardsForMimic > 0) {
                $mimic = true;
            }
        }

        $stateAfter = $this->game->redirectAfterResolveNumberDice();
        if ($mimic) {
            $this->game->goToMimicSelection($currentPlayerId, FLUXLING_WICKEDNESS_TILE, $stateAfter);
        } else {
            $this->game->goToState($stateAfter, $damages);
        }
    }
  	
    #[PossibleAction]
    public function actSkipTakeWickednessTile(int $currentPlayerId) {
        $this->removeFirstTakeWickednessTileLevel($currentPlayerId);

        $this->game->goToState($this->game->redirectAfterResolveNumberDice(true));
    }

    public function zombie(int $playerId) {
        $level = $this->game->wickednessExpansion->canTakeWickednessTile($playerId);
        $tableTiles = $this->game->wickednessTiles->getTable($level);
        $zombieChoice = $this->getRandomZombieChoice(Arrays::pluck($tableTiles, 'id'));
        return $this->actTakeWickednessTile($zombieChoice, $playerId);
    }
   
    private function removeFirstTakeWickednessTileLevel(int $playerId) {
        $levels = json_decode($this->game->getUniqueValueFromDB("SELECT player_take_wickedness_tiles FROM `player` where `player_id` = $playerId"), true);
        array_shift($levels);
        $levelsJson = json_encode($levels);
        $this->game->DbQuery("UPDATE player SET `player_take_wickedness_tiles` = '$levelsJson' where `player_id` = $playerId");
    }
}

