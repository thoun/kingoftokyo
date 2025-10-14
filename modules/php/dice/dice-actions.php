<?php

namespace KOT\States;

/**
 * @mixin \Bga\Games\KingOfTokyo\Game
 */
trait DiceActionTrait {

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in kingoftokyo.action.php)
    */
  	
    public function applyRerollDie(int $playerId, object $die, array $diceIds, int $cardName) {
        $this->DbQuery("UPDATE dice SET `locked` = false");
        if (count($diceIds) > 0) {
            $this->DbQuery("UPDATE dice SET `locked` = true where `dice_id` IN (".implode(',', $diceIds).")");
        }

        $oldValue = $die->value;
        $newValue = bga_rand(1, 6);
        $die->value = $newValue;
        $this->DbQuery("UPDATE dice SET `rolled` = false where `dice_id` <> ".$die->id);
        $this->DbQuery("UPDATE dice SET `dice_value` = $newValue, `rolled` = true where `dice_id` = ".$die->id);

        if (!$this->canRerollSymbol($playerId, getDieFace($die))) {
            $die->locked = true;
            $this->DbQuery( "UPDATE dice SET `locked` = true where `dice_id` = ".$die->id );
        }

        $message = clienttranslate('${player_name} uses ${card_name} and rolled ${die_face_before} to ${die_face_after}');
        $this->notify->all('rethrow3', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerNameById($playerId),
            'card_name' => $cardName,
            'dieId' => $die->id,
            'die_face_before' => $this->getDieFaceLogName($oldValue, 0),
            'die_face_after' => $this->getDieFaceLogName($newValue, 0),
        ]);

        $this->goToState(ST_PLAYER_THROW_DICE);
    }

}
