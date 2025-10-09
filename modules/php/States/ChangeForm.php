<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\KingOfTokyo\Game;

class ChangeForm extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_CHANGE_FORM,
            type: StateType::ACTIVE_PLAYER,
            name: 'changeForm',
            description: clienttranslate('${actplayer} can change form'),
            descriptionMyTurn: clienttranslate('${you} can change form'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $isBeastForm = $this->game->isBeastForm($activePlayerId);
        $otherForm = $isBeastForm ? clienttranslate('Biped form') : clienttranslate('Beast form');
        $canChangeForm = $this->game->getPlayerEnergy($activePlayerId) >= 1;

        return [
            'canChangeForm' => $canChangeForm,
            'otherForm' => $otherForm,
        ];
    }

    public function onEnteringState(int $activePlayerId) {
        if (($this->game->autoSkipImpossibleActions() && $this->game->getPlayerEnergy($activePlayerId) < 1) || $this->game->isSureWin($activePlayerId)) {
            return \ST_PLAYER_BUY_CARD;
        }
    }

    #[PossibleAction]
    public function actChangeForm(int $activePlayerId) {
        if ($this->game->getPlayerEnergy($activePlayerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        $isBeastForm = !$this->game->isBeastForm($activePlayerId);
        $this->setBeastForm($activePlayerId, $isBeastForm);

        $this->game->DbQuery("UPDATE player SET `player_energy` = `player_energy` - 1 where `player_id` = $activePlayerId");

        $message = clienttranslate('${player_name} changes form to ${newForm}');
        $newForm = $isBeastForm ? clienttranslate('Beast form') : clienttranslate('Biped form');
        $this->notify->all('changeForm', $message, [
            'playerId' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card' => $this->game->getFormCard($activePlayerId),
            'energy' => $this->game->getPlayerEnergy($activePlayerId),
            'newForm' => $newForm,
            'i18n' => ['newForm'],
        ]);

        $this->game->incStat(1, 'formChanged', $activePlayerId);

        return \ST_PLAYER_BUY_CARD;
    }
  	
    #[PossibleAction]
    public function actSkipChangeForm() {
        return \ST_PLAYER_BUY_CARD;
    }  

    public function zombie(int $playerId) {
        return $this->actSkipChangeForm();
    }

    private function setBeastForm(int $playerId, bool $beast) {
        $formCard = $this->game->getFormCard($playerId);
        $side = $beast ? 1 : 0;
        $this->game->DbQuery("UPDATE `card` SET `card_type_arg` = $side where `card_id` = ".$formCard->id);
    }
}
