<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class PrecisionFieldSupport extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }

    public function immediateEffect(Context $context) {
        $topCard = $context->game->powerCards->getTopDeckCard(false);

        if ($topCard->type > 100) {

            $context->game->notify->all('log500', clienttranslate('${player_name} draws ${card_name}. This card is discarded as it is not a Keep card.'), [
                'playerId' => $context->currentPlayerId,
                'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
                'card_name' => $topCard->type,
            ]);
            $context->game->powerCards->moveItem($topCard, 'discard');
            $this->immediateEffect($context);

        } else if ($context->game->powerCards->getCardBaseCost($topCard->type) > 4) {

            $context->game->notify->all('log500', clienttranslate('${player_name} draws ${card_name}. This card is discarded as it costs more than 4[Energy].'), [
                'playerId' => $context->currentPlayerId,
                'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
                'card_name' => $topCard->type,
            ]);
            $context->game->powerCards->moveItem($topCard, 'discard');
            $this->immediateEffect($context);

        } else {
            $context->game->drawCard($context->currentPlayerId);
        }
    }
}
