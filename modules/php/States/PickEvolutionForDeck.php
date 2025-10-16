<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;

class PickEvolutionForDeck extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_MULTIPLAYER_PICK_EVOLUTION_DECK,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: 'pickEvolutionForDeck',
            description: clienttranslate('Players must pick an Evolution for their deck'),
            descriptionMyTurn: clienttranslate('${you} must pick an Evolution for your deck'),
            transitions: [
                'next' => \ST_NEXT_PICK_EVOLUTION_DECK,
            ],
        );
    }

    public function getArgs(): array {
        $turn = intval($this->game->getGameStateValue(MUTANT_EVOLUTION_TURN));
        $playersIds = $this->game->getPlayersIds();
        $privateArgs = [];
        foreach($playersIds as $index => $playerId) {
            $chooseCardIn = $this->game->getEvolutionCardsByLocation('mutant'.(($index + $turn) % count($playersIds)));
            $inDeck = $this->game->getEvolutionCardsByLocation('deck'.$playerId);
            $privateArgs[$playerId] = [
                'chooseCardIn' => $chooseCardIn,
                'inDeck' => $inDeck,
            ];
        }

        return [
            '_private' => $privateArgs,
        ];
    }

    public function onEnteringState() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    #[PossibleAction]
    public function actPickEvolutionForDeck(
        int $currentPlayerId,
        #[IntParam(name: 'id')] int $cardId,
    ) {
        $card = $this->game->powerUpExpansion->evolutionCards->getItemById($cardId);

        if (strpos($card->location, 'mutant') !== 0) {
            throw new \BgaUserException('Card is not selectable');
        }

        $this->game->powerUpExpansion->evolutionCards->moveItem($card, 'deck'.$currentPlayerId);

        $this->notify->player($currentPlayerId, 'evolutionPickedForDeck', '', [
            'card' => $card,
        ]);

        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, 'next');
    }

    public function zombie(int $playerId, array $args) {
        $zombieChoice = $this->getRandomZombieChoice(Arrays::pluck($args['_private'][$playerId]['chooseCardIn'], 'id'));
        return $this->actPickEvolutionForDeck($playerId, $zombieChoice);
    }
}
