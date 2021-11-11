<?php

namespace KOT\States;

trait WickednessTilesActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
   
  	
    public function takeWickednessTile(int $id) {
        $this->checkAction('takeWickednessTile');

        $playerId = self::getCurrentPlayerId();

        $level = $this->canTakeWickednessTile($playerId);
        $tile = $this->getWickednessTileFromDb($this->wickednessTiles->getCard($id));
        $this->wickednessTiles->moveCard($id, 'hand', $playerId);

        self::notifyAllPlayers("takeWickednessTile", clienttranslate('${player_name} takes ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'tile' => $tile,
            'card_name' => 2000 + $tile->type,
            'level' => $level,
        ]);

        self::incStat(1, 'wickednessTilesTaken', $playerId);

        self::DbQuery("UPDATE player SET `player_take_wickedness_tile` = 0 where `player_id` = $playerId");

        $this->redirectToResolveHeart();
    }
  	
    public function skipTakeWickednessTile() {
        $this->checkAction('skipTakeWickednessTile');

        $playerId = self::getCurrentPlayerId();

        self::DbQuery("UPDATE player SET `player_take_wickedness_tile` = 0 where `player_id` = $playerId");

        $this->redirectToResolveHeart();
    }
}
