<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

use const Bga\Games\KingOfTokyo\FINAL_PUSH_WICKEDNESS_TILE;
use const Bga\Games\KingOfTokyo\PowerCards\FRENZY;

class NextPlayer extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: ST_NEXT_PLAYER,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    function onEnteringState(int $activePlayerId) {
        $this->game->removeDiscardCards($activePlayerId);

        if (!$this->game->getPlayer($activePlayerId)->eliminated) {
            $this->game->applyEndOfEachMonsterCards();
        }

        // end of the extra turn with Builders' uprising (without die of fate)
        if (intval($this->game->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) == 2) {
            $this->game->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 0);
        } 
        $this->globals->delete(NEXT_POWER_CARD_COST_REDUCTION);

        $killPlayer = $this->game->killDeadPlayers();

        if ($killPlayer) {
            $this->game->setGameStateValue(FREEZE_TIME_CURRENT_TURN, 0);
            $this->game->setGameStateValue(FREEZE_TIME_MAX_TURNS, 0);
            $this->game->setGameStateValue(FRENZY_EXTRA_TURN, 0);
            $this->game->setGameStateValue(FINAL_PUSH_EXTRA_TURN, 0);
            $this->game->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 0);

            $activePlayerId = (int)$this->game->activateNextPlayer();
        } else {
            $anotherTimeWithCard = 0;

            $freezeTimeMaxTurns = intval($this->game->getGameStateValue(FREEZE_TIME_MAX_TURNS));
            $freezeTimeCurrentTurn = intval($this->game->getGameStateValue(FREEZE_TIME_CURRENT_TURN));

            $activatedFrenzys = $this->game->mindbugExpansion->getActivatedCards($activePlayerId, FRENZY);

            if ($anotherTimeWithCard == 0 && count($activatedFrenzys) > 0) { // extra turn for current player
                $cardId = $activatedFrenzys[0]->id;
                $card = $this->game->powerCards->getItemById($cardId);
                if ($card) {
                    $anotherTimeWithCard = $card->type; // FRENZY card
                }
            }

            if ($anotherTimeWithCard == 0 && intval($this->game->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = 1000 + BUILDERS_UPRISING_CURSE_CARD; // Builders' uprising
                $this->game->setGameStateValue(BUILDERS_UPRISING_EXTRA_TURN, 2); 
            }

            if ($anotherTimeWithCard == 0 && $freezeTimeMaxTurns > 0 && $freezeTimeCurrentTurn == $freezeTimeMaxTurns) {
                $this->game->setGameStateValue(FREEZE_TIME_CURRENT_TURN, 0);
                $this->game->setGameStateValue(FREEZE_TIME_MAX_TURNS, 0);
            } if ($freezeTimeCurrentTurn < $freezeTimeMaxTurns) { // extra turn for current player with one less die
                $anotherTimeWithCard = FREEZE_TIME_CARD;
                $this->game->incGameStateValue(FREEZE_TIME_CURRENT_TURN, 1);
            }

            if ($anotherTimeWithCard == 0 && intval($this->game->getGameStateValue(FINAL_PUSH_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = 2000 + FINAL_PUSH_WICKEDNESS_TILE; // Final push
                $this->game->setGameStateValue(FINAL_PUSH_EXTRA_TURN, 0); 
                $finalPushTile = $this->game->wickednessExpansion->getWickednessTileByType($activePlayerId, FINAL_PUSH_WICKEDNESS_TILE);
                $this->game->wickednessExpansion->removeWickednessTiles($activePlayerId, [$finalPushTile]);
            }

            if ($anotherTimeWithCard == 0 && intval($this->game->getGameStateValue(FRENZY_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = FRENZY_CARD; // Frenzy
                $this->game->setGameStateValue(FRENZY_EXTRA_TURN, 0);
            }

            if ($anotherTimeWithCard == 0 && intval($this->game->getGameStateValue(PANDA_EXPRESS_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = 3000 + PANDA_EXPRESS_EVOLUTION;
                $this->game->setGameStateValue(PANDA_EXPRESS_EXTRA_TURN, 0);
            }

            if ($anotherTimeWithCard == 0 && intval($this->game->getGameStateValue(JUNGLE_FRENZY_EXTRA_TURN)) == 1) { // extra turn for current player
                $anotherTimeWithCard = 3000 + JUNGLE_FRENZY_EVOLUTION;
                $this->game->setGameStateValue(JUNGLE_FRENZY_EXTRA_TURN, 0);

                $jungleFrenzyEvolutions = $this->game->getEvolutionsOfType($activePlayerId, JUNGLE_FRENZY_EVOLUTION);
                $this->game->removeEvolutions($activePlayerId, $jungleFrenzyEvolutions);
            }
            
            if ($anotherTimeWithCard > 0) {
                $this->notify->all('playAgain', clienttranslate('${player_name} takes another turn with ${card_name}'), [
                    'playerId' => $activePlayerId,
                    'player_name' => $this->game->getPlayerNameById($activePlayerId),
                    'card_name' => $anotherTimeWithCard,
                ]);
            } else {
                $activePlayerId = (int)$this->game->activateNextPlayer();
            }
        }

        if ($this->game->getRemainingPlayers() <= 1 || $this->game->getMaxPlayerScore() >= MAX_POINT) {
            return ST_END_SCORE;
        } else {
            $this->game->giveExtraTime($activePlayerId);

            return ST_PLAYER_BEFORE_START_TURN;
        }
    }
}