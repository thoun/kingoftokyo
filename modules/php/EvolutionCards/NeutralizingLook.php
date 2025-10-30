<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;

class NeutralizingLook extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }
    
    public function immediateEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice((int)$context->game->getActivePlayerId(), false, false, false);
        $countBefore = count($dice);
        $dice = Arrays::filter($dice, fn($die) => $die->value == 6);
        if (count($dice) === 0) {
            return;
        }
        $removed = $countBefore - count($dice);

        $diceCounts = $context->game->getGlobalVariable(DICE_COUNTS, true);
        $diceCounts[6] -= $removed;
        $context->game->setGlobalVariable(DICE_COUNTS, $diceCounts);

        $dice = Arrays::filter($dice, fn($die) => !$die->discarded);
        $context->game->DbQuery("UPDATE dice SET `discarded` = true WHERE `dice_id` IN (".implode(',', Arrays::pluck($dice, 'id')).")");
        foreach ($dice as &$die) {
            $die->discarded = true;
        }

        $context->game->notify->all("discardedDice", clienttranslate('Dice ${dieFace} are discarded'), [
            'dice' => $dice,
            'dieFace' => $context->game->getDieFaceLogName(6, 0),
        ]);
    }
}
