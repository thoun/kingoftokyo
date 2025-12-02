<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFramework\UserException;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;

class NeutralizingLook extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = TEMPORARY;
    }
    
    public function immediateEffect(Context $context) {
        $activePlayerId = (int)$context->game->getActivePlayerId();
        if($context->currentPlayerId === $activePlayerId) {
            throw new UserException('You cannot use this Evolution on yourself');
        }
        $dice = $context->game->getPlayerRolledDice($activePlayerId, false, false, false);
        $diceToDiscard = Arrays::filter($dice, fn($die) => $die->type == 0 && $die->value == 6 && !$die->discarded);
        if (count($diceToDiscard) === 0) {
            return;
        }

        $context->game->DbQuery("UPDATE dice SET `discarded` = true WHERE `dice_id` IN (".implode(',', Arrays::pluck($diceToDiscard, 'id')).")");
        foreach ($diceToDiscard as &$die) {
            $die->discarded = true;
        }

        $context->game->notify->all("discardedDice", clienttranslate('Dice ${dieFace} are discarded'), [
            'dice' => $diceToDiscard,
            'dieFace' => $context->game->getDieFaceLogName(6, 0),
        ]);
    }
}
