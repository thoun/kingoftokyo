<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class Bamboozle extends EvolutionCard {
    public function immediateEffect(Context $context) {
        $cardBeingBought = $context->game->getGlobalVariable(CARD_BEING_BOUGHT);
        if (!$cardBeingBought) {
            $context->game->warn('playBamboozleEvolution cardBeingBought is null');
            $context->game->dump('$cardBeingBought', $cardBeingBought);
        }

        $cardBeingBought->allowed = false;
        $context->game->setGlobalVariable(CARD_BEING_BOUGHT, $cardBeingBought);

        if (!$cardBeingBought->playerId) {
            $context->game->warn('playBamboozleEvolution cardBeingBought playerId is null');
            $context->game->dump('$cardBeingBought', $cardBeingBought);
        }

        $buyCardArgs = $context->game->getArgBuyCard($cardBeingBought->playerId, false);
        $buyCardArgs['disabledIds'] = [...$buyCardArgs['disabledIds'], $cardBeingBought->cardId];

        $canBuyAnother = false;
        $playerEnergy = $context->game->getPlayerEnergy($cardBeingBought->playerId);
        foreach($buyCardArgs['cardsCosts'] as $cardId => $cost) {
            if (!in_array($cardId, $buyCardArgs['disabledIds']) && $cost <= $playerEnergy) {
                $canBuyAnother = true;
                break;
            }
        }

        if ($canBuyAnother) {

            $question = new Question(
                'Bamboozle',
                clienttranslate('${actplayer} must choose another card'),
                clienttranslate('${you} must choose another card'),
                [$context->currentPlayerId],
                ST_PLAYER_BUY_CARD,
                [ 
                    'cardBeingBought' => $cardBeingBought, 
                    'buyCardArgs' => $buyCardArgs,
                ]
            );
            $context->game->setQuestion($question);
            $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);
            $context->game->removeEvolution($context->currentPlayerId, $this);

            $context->game->jumpToState(ST_MULTIPLAYER_ANSWER_QUESTION);

        } else {
            $activePlayerId = (int)$context->game->getActivePlayerId();

            $forbiddenCard = $context->game->powerCards->getItemById($cardBeingBought->cardId);

            $context->game->notify->all('log', clienttranslate('${player_name} prevents ${player_name2} to buy ${card_name}. ${player_name2} is not forced to buy another card, as player energy is too low to buy another card. '), [
                'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
                'player_name2' => $context->game->getPlayerNameById($activePlayerId),
                'card_name' => $forbiddenCard->type,
            ]);
    
            $context->game->actSkipCardIsBought();
        }
    }
}
