<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFramework\UserException;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;

use function KOT\States\getDieFace;

class EnergyDevourer extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    /*public function applyeffect(Context $context) {
        if ($context->game->getPlayerEnergy($context->currentPlayerId) < 1) {
            throw new UserException("Not enough energy");
        }
        $context->game->applyLoseEnergy($context->currentPlayerId, 1, 0);

        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, false, false, true);

        $diceToReroll = array_slice(Arrays::filter($dice, fn($die) => $context->game->getDiceFaceType($die) === 41), 0, 1);

        foreach ($diceToReroll as &$die) {
            $oldValue = $die->value;
            $newValue = 6;
            $die->value = $newValue;
            $context->game->DbQuery("UPDATE dice SET `dice_value` = $newValue where `dice_id` = ".$die->id);

            if (!$context->game->canRerollSymbol($context->currentPlayerId, getDieFace($die))) {
                $die->locked = true;
                $context->game->DbQuery( "UPDATE dice SET `locked` = true where `dice_id` = ".$die->id );
            }

            $message = clienttranslate('${player_name} uses ${card_name} and changed ${die_face_before} to ${die_face_after}');
            $context->game->notify->all('rethrow3', $message, [
                'playerId' => $context->currentPlayerId,
                'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
                'card_name' => 3000 + $this->type,
                'dieId' => $die->id,
                'die_face_before' => $context->game->getDieFaceLogName($oldValue, 0),
                'die_face_after' => $context->game->getDieFaceLogName($newValue, 0),
            ]);

        }

        $context->game->setUsedCard(3000 + $this->id);
    }*/
}
