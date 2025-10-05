<?php

namespace KOT\States;

use Bga\Games\KingOfTokyo\Objects\Context;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

trait WickednessTilesActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
   
    private function removeFirstTakeWickednessTileLevel(int $playerId) {
        $levels = json_decode($this->getUniqueValueFromDB("SELECT player_take_wickedness_tiles FROM `player` where `player_id` = $playerId"), true);
        array_shift($levels);
        $levelsJson = json_encode($levels);
        $this->DbQuery("UPDATE player SET `player_take_wickedness_tiles` = '$levelsJson' where `player_id` = $playerId");
    }
  	
    public function actTakeWickednessTile(int $id) {
        $playerId = $this->getCurrentPlayerId();

        $level = $this->wickednessExpansion->canTakeWickednessTile($playerId);
        $tile = $this->wickednessTiles->getItemById($id);

        $tableTiles = $this->wickednessTiles->getTable($level);
        if (!$this->array_some($tableTiles, fn($t) => $t->id === $tile->id)) {
            throw new \BgaUserException("This tile is not on the table");
        }

        $this->wickednessTiles->moveItem($tile, 'hand', $playerId);

        $this->notifyAllPlayers("takeWickednessTile", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'tile' => $tile,
            'card_name' => 2000 + $tile->type,
            'level' => $level,
        ]);

        $this->incStat(1, 'wickednessTilesTaken', $playerId);

        $this->removeFirstTakeWickednessTileLevel($playerId);

        $damages = $this->wickednessTiles->immediateEffect($tile, new Context($this, currentPlayerId: $playerId));

        $mimic = false;
        if ($tile->type === FLUXLING_WICKEDNESS_TILE) {
            $countAvailableCardsForMimic = 0;

            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $pId) {
                $cardsOfPlayer = $this->powerCards->getPlayer($pId);
                $countAvailableCardsForMimic += count(array_values(array_filter($cardsOfPlayer, fn($card) => $card->type != MIMIC_CARD && $card->type < 100)));
            }

            if ($countAvailableCardsForMimic > 0) {
                $mimic = true;
            }
        }

        $stateAfter = $this->redirectAfterResolveNumberDice();
        if ($mimic) {
            $this->goToMimicSelection($playerId, FLUXLING_WICKEDNESS_TILE, $stateAfter);
        } else {
            $this->goToState($stateAfter, $damages);
        }
    }
  	
    public function actSkipTakeWickednessTile() {
        $playerId = $this->getCurrentPlayerId();

        $this->removeFirstTakeWickednessTileLevel($playerId);

        $this->goToState($this->redirectAfterResolveNumberDice(true));
    }

    function actChooseMimickedCardWickednessTile(int $mimickedCardId) {
        $playerId = $this->getActivePlayerId();

        $card = $this->powerCards->getItemById($mimickedCardId);        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->type == MIMIC_CARD) {
            throw new \BgaUserException("You cannot mimic Mimic cards");
        }

        $this->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $playerId, $mimickedCardId);

        $this->goToState($this->redirectAfterResolveNumberDice());
    }

    function actChangeMimickedCardWickednessTile(int $mimickedCardId) {
        $playerId = $this->getActivePlayerId();

        $card = $this->powerCards->getItemById($mimickedCardId);        
        if ($card->type > 100) {
            throw new \BgaUserException("You can only mimic Keep cards");
        }
        if ($card->type == MIMIC_CARD) {
            throw new \BgaUserException("You cannot mimic Mimic cards");
        }

        $this->setMimickedCardId(FLUXLING_WICKEDNESS_TILE, $playerId, $mimickedCardId);

        $this->jumpToState($this->redirectAfterChangeMimickWickednessTile($playerId));
    }

    function actSkipChangeMimickedCardWickednessTile() {
        $playerId = $this->getActivePlayerId();

        $this->jumpToState($this->redirectAfterChangeMimickWickednessTile($playerId));
    }    
}
