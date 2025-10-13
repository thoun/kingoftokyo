<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;

use function KOT\States\getDieFace;

class IntergalacticGenius extends EvolutionCard
{
    public function applyeffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, true, true);

        $lockedDice = [];
        $rolledDice = [];

        $encaseInIceDieId = intval($context->game->getGameStateValue(ENCASED_IN_ICE_DIE_ID));
        
        foreach ($dice as &$die) {
            if ($context->game->getDiceFaceType($die) === 61) {
                if ($die->id == $encaseInIceDieId) {
                    $lockedDice[] = $die;
                } else {
                    $die->value = bga_rand(1, 6);
                    $context->game->DbQuery( "UPDATE dice SET `dice_value` = ".$die->value.", `rolled` = true where `dice_id` = ".$die->id );

                    $rolledDice[] = $die;
                }

                if (!$context->game->canRerollSymbol($context->currentPlayerId, getDieFace($die)) || $die->id == $encaseInIceDieId) {
                    $die->locked = true;
                    $context->game->DbQuery( "UPDATE dice SET `locked` = true where `dice_id` = ".$die->id );
                }
            } else {
                $lockedDice[] = $die;
            }
        }

        if (!$context->game->getPlayer($context->currentPlayerId)->eliminated) {
            $message = null;

            $rolledDiceStr = '';
            $lockedDiceStr = '';

            usort($rolledDice, [Game::class, 'sortDieFunction']);
            foreach ($rolledDice as $rolledDie) {
                $rolledDiceStr .= $context->game->getDieFaceLogName($rolledDie->value, $rolledDie->type);
            }

            if (count($lockedDice) == 0) {
                $message = clienttranslate('${player_name} rerolls dice ${rolledDice}');
            } else {
                usort($lockedDice, [Game::class, 'sortDieFunction']);
                foreach ($lockedDice as $lockedDie) {
                    $lockedDiceStr .= $context->game->getDieFaceLogName($lockedDie->value, $lockedDie->type);
                }

                $message = clienttranslate('${player_name} keeps ${lockedDice} and rerolls dice ${rolledDice}');
            }

            $context->game->notify->all("diceLog", $message, [
                'playerId' => $context->currentPlayerId,
                'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
                'rolledDice' => $rolledDiceStr,
                'lockedDice' => $lockedDiceStr,
            ]);
        }

        // TODOMB finish

        $context->game->setUsedCard(3000 + $this->id);
    }
}
