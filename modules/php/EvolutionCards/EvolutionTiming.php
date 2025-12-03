<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

enum EvolutionTiming {
    case BEFORE_START_PERMANENT;
    case BEFORE_START_TEMPORARY;
    case BEFORE_RESOLVE_DICE_MULTI_OTHERS;
    case DURING_RESOLVE_DICE_ACTIVE;
    case DURING_RESOLVE_DICE_MULTI_OTHERS;
    case BEFORE_ENTERING_TOKYO;
    case AFTER_ENTERING_TOKYO;
    case AFTER_NOT_ENTERING_TOKYO;
    case WHEN_CARD_IS_BOUGHT;
    case BEFORE_END_MULTI;
    case BEFORE_END_ACTIVE;
    case TO_HEAL;
}
