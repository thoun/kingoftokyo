<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;

class YinYang extends EvolutionCard {
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    function applyEffect(Context $context) {
        $dice = $context->game->getPlayerRolledDice($context->currentPlayerId, false, false, false);
        $YIN_YANG_OTHER_FACE = [
            1 => 3,
            2 => 4,
            3 => 1,
            4 => 2,
            5 => 6,
            6 => 5,
        ];

        $idToValue = [];
        $dieFacesBefore = '';
        $dieFacesAfter = '';

        foreach ($dice as $die) {
            $otherFace = $YIN_YANG_OTHER_FACE[$die->value];
            $context->game->DbQuery("UPDATE dice SET `rolled` = false, `dice_value` = ".$otherFace." where `dice_id` = ".$die->id);

            $idToValue[$die->id] = $otherFace;
            $dieFacesBefore .= $context->game->getDieFaceLogName($die->value, $die->type);
            $dieFacesAfter .= $context->game->getDieFaceLogName($otherFace, $die->type);
        }

        $message = clienttranslate('${player_name} uses ${card_name} and change ${die_face_before} to ${die_face_after}');
        $context->game->notify->all("changeDice", $message, [
            'playerId' => $context->currentPlayerId,
            'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
            'card_name' => 3000 + YIN_YANG_EVOLUTION,
            'dieIdsToValues' => $idToValue,
            'canHealWithDice' => $context->game->canHealWithDice($context->currentPlayerId),
            'frozenFaces' => $context->game->frozenFaces($context->currentPlayerId),
            'die_face_before' => $dieFacesBefore,
            'die_face_after' => $dieFacesAfter,
        ]);
    }
}
