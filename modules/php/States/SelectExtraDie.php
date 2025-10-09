<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class SelectExtraDie extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_SELECT_EXTRA_DIE,
            type: StateType::ACTIVE_PLAYER,
            name: 'selectExtraDie',
            description: clienttranslate('${actplayer} must select the face of the extra die'),
            descriptionMyTurn: clienttranslate('${you} must select the face of the extra die'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        return [
            'dice' => $this->game->getPlayerRolledDice($activePlayerId, true, true, false),
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
        ];
    }

    #[PossibleAction]
    function actSelectExtraDie(int $face, int $activePlayerId) {
        $this->game->setGameStateValue(RAGING_FLOOD_EXTRA_DIE_SELECTED, 1);

        $dice = $this->game->getPlayerRolledDice($activePlayerId, false, false, false);
        $die = end($dice);
        $dieId = $die->id; 
        $this->game->DbQuery("UPDATE dice SET `dice_value` = $face WHERE dice_id = $dieId");

        $this->notify->all("selectExtraDie", clienttranslate('${player_name} choses ${die_face} as the extra die'), [
            'playerId' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'die_face' => $this->game->getDieFaceLogName($face, $die->type),
        ]);

        return \ST_RESOLVE_DICE;
    }

    public function zombie(int $playerId) {
        if (boolval($this->game->getGameStateValue(\RAGING_FLOOD_EXTRA_DIE_SELECTED))) {
            return \ST_RESOLVE_DICE;
        }

        $dice = $this->game->getPlayerRolledDice($playerId, false, false, false);
        if (!empty($dice)) {
            $face = bga_rand(1, 6);
            return $this->actSelectExtraDie($face, $playerId);
        }

        return \ST_RESOLVE_DICE;
    }
}

