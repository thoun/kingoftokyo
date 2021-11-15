<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Dice;
use KOT\Objects\ChangeActivePlayerDieIntervention;
use KOT\Objects\Damage;

trait DiceUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    static function getDiceFaceType(object $die) { // return face type * 10 + number of symbols on face
        if ($die->type == 0) {
            return $die->value * 10 + 1; 
        } else if ($die->type == 1) {
            switch($die->value) {
                case 1: return 51; 
                case 2: return 52;
                case 3: return 61;
                case 4: return 61;
                case 5: return 62;
                case 6: return 71;
            }
        }
        return null;
    }

    static function sortDieFunction($a, $b) { 
        $aFaceType = static::getDiceFaceType($a);
        $bFaceType = static::getDiceFaceType($b);
        
        // if die type 2, always at the beginning of the array
        if ($a->type == 2) {
            return -1;
        } else if ($b->type == 2) {
            return 1;
        }

        // sort by face type
        if ($aFaceType === $bFaceType) {
            return $a->type - $b->type; // if same face type, die type 1 after die type 0
        } else {
            return $aFaceType - $bFaceType;
        }
    }
    
    function getDice(int $number) {
        $sql = "SELECT * FROM dice ORDER BY dice_id limit $number";
        // TODOAN TODOCY $sql = "SELECT * FROM dice where `type` = 0 ORDER BY dice_id limit $number";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices));
    }

    function getDieById(int $id) {
        $sql = "SELECT * FROM dice WHERE `dice_id` = $id";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices))[0];
    }

    function getFirst3Die(int $playerId) {
        $dice = $this->getDice($this->getDiceNumber($playerId));
        foreach ($dice as $die) {
            if ($die->value === 3) {
                return $die;
            }
        }
        return null;
    }

    function getDiceByType(int $type) {
        $sql = "SELECT * FROM dice WHERE `type` = $type";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices));
    }

    private function getPlayerRolledDice(int $playerId, bool $includeBerserkDie, bool $includeDieOfFate, bool $setCanReroll) {
        $dice = $this->getDice($this->getDiceNumber($playerId));

        if ($includeBerserkDie && $this->isCybertoothExpansion() && $this->isPlayerBerserk($playerId)) {
            $dice = array_merge($dice, $this->getDiceByType(1)); // type 1 at the end
        }

        if ($setCanReroll && $this->isAnubisExpansion()) {
            foreach ($dice as &$die) {
                $symbol = getDieFace($die);
                $die->canReroll = $this->canRerollSymbol($playerId, $symbol);
            }
        }

        if ($includeDieOfFate && $this->isAnubisExpansion()) {
            $dice = array_merge($this->getDiceByType(2), $dice); // type 2 at the start
        }

        return $dice;
    }

    public function throwDice(int $playerId, bool $firstRoll) {
        $dice = $this->getPlayerRolledDice($playerId, true, true, true);

        self::DbQuery( "UPDATE dice SET `rolled` = false");

        $lockedDice = [];
        $rolledDice = [];
        
        foreach ($dice as &$die) {
            if ($die->locked) {
                $lockedDice[] = $die;
            } else {
                $facesNumber = $die->type == 2 ? 4 : 6;
                $die->value = bga_rand(1, $facesNumber);
                self::DbQuery( "UPDATE dice SET `dice_value` = ".$die->value.", `rolled` = true where `dice_id` = ".$die->id );

                $rolledDice[] = $die;
            }

            if (!$this->canRerollSymbol($playerId, getDieFace($die))) {
                $die->locked = true;
                self::DbQuery( "UPDATE dice SET `locked` = true where `dice_id` = ".$die->id );
            }
        }

        if (!$this->getPlayer($playerId)->eliminated) {
            $message = null;

            $rolledDiceStr = '';
            $lockedDiceStr = '';

            usort($rolledDice, "static::sortDieFunction");
            foreach ($rolledDice as $rolledDie) {
                $rolledDiceStr .= $this->getDieFaceLogName($rolledDie->value, $rolledDie->type);
            }

            if ($firstRoll) {
                $message = clienttranslate('${player_name} rolls dice ${rolledDice}');
            } else if (count($lockedDice) == 0) {
                $message = clienttranslate('${player_name} rerolls dice ${rolledDice}');
            } else {
                usort($lockedDice, "static::sortDieFunction");
                foreach ($lockedDice as $lockedDie) {
                    $lockedDiceStr .= $this->getDieFaceLogName($lockedDie->value, $lockedDie->type);
                }

                $message = clienttranslate('${player_name} keeps ${lockedDice} and rerolls dice ${rolledDice}');
            }

            self::notifyAllPlayers("diceLog", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'rolledDice' => $rolledDiceStr,
                'lockedDice' => $lockedDiceStr,
            ]);
        }
    }

    function fixDices() {
        self::DbQuery( "UPDATE dice SET `rolled` = false");
    }

    function getDiceNumber(int $playerId) {
        $remove = intval($this->getGameStateValue(FREEZE_TIME_CURRENT_TURN)) + $this->getPlayerShrinkRayTokens($playerId);

        $add = $this->countExtraHead($playerId);
        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == RAGING_FLOOD_CURSE_CARD) {
                $remove++;
            }
    
            if ($curseCardType == FALSE_BLESSING_CURSE_CARD) {
                $add += 2;
            }
        }

        return max(6 + $add - $remove, 0);
    }

    function resolveNumberDice(int $playerId, int $number, int $diceCount) {
        // number
        if ($diceCount >= 3) {
            $points = $number + $diceCount - 3;

            $this->applyGetPoints($playerId, $points, -1);

            self::incStat($points, 'pointsWonWith'.$number.'Dice', $playerId);

            self::notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} gains ${deltaPoints}[Star] with ${dice_value} dice'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'deltaPoints' => $points,
                'points' => $this->getPlayerScore($playerId),
                'diceValue' => $number,
                'dice_value' => "[dice$number]",
            ]);

            if ($number == 1) {
                // gourmet
                $countGourmet = $this->countCardOfType($playerId, GOURMET_CARD);
                if ($countGourmet > 0) {
                    $this->applyGetPoints($playerId, 2 * $countGourmet, GOURMET_CARD);
                }

                // Freeze Time
                $countFreezeTime = $this->countCardOfType($playerId, FREEZE_TIME_CARD);
                if ($countFreezeTime > 0) {
                    $this->incGameStateValue(FREEZE_TIME_MAX_TURNS, 1);
                }
                
            }
            

            if (($number == 1 || $number == 2) && $this->isWickednessExpansion()) {
                $this->applyGetWickedness($playerId, 3 - $number);
            }
        }
    }

    function resolveHealthDice(int $playerId, int $diceCount) {
        if (!$this->canHealWithDice($playerId)) { // TODOAN change message
            self::notifyAllPlayers( "resolveHealthDiceInTokyo", clienttranslate('${player_name} gains no [Heart] (player in Tokyo)'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
            ]);
        } else {
            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth) {
                $this->applyGetHealth($playerId, $diceCount, -1, $playerId);
                $newHealth = $this->getPlayerHealth($playerId);

                self::notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} gains ${deltaHealth} [Heart]'), [
                    'playerId' => $playerId,
                    'player_name' => $this->getPlayerName($playerId),
                    'health' => $newHealth,
                    'deltaHealth' => $diceCount,
                ]);
            }
        }
    }

    function resolveEnergyDice(int $playerId, int $diceCount) {
        $this->applyGetEnergy($playerId, $diceCount, -1);

        self::notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} gains ${deltaEnergy} [Energy]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'deltaEnergy' => $diceCount,
            'energy' => $this->getPlayerEnergy($playerId),
        ]);
    }

    
    function resolveSmashDice(int $playerId, int $diceCount) { // return nextState / null // TODOCY return redirects
        // Nova breath
        $countNovaBreath = $this->countCardOfType($playerId, NOVA_BREATH_CARD);

        $message = null;
        $smashedPlayersIds = null;
        $inTokyo = $this->inTokyo($playerId);
        $nextState = "enterTokyo";
        $nextStateId = ST_ENTER_TOKYO_APPLY_BURROWING;

        $damages = [];

        if ($countNovaBreath) {
            $message = clienttranslate('${player_name} smashes all other Monsters with ${number} [diceSmash]');
            $smashedPlayersIds = $this->getOtherPlayersIds($playerId);
        } else {
            $smashTokyo = !$inTokyo;
            $message = $smashTokyo ? 
                clienttranslate('${player_name} smashes Monsters in Tokyo with ${number} [diceSmash]') :
                clienttranslate('${player_name} smashes Monsters outside Tokyo with ${number} [diceSmash]');
            $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);
        }

        // Shrink Ray
        $giveShrinkRayToken = $this->countCardOfType($playerId, SHRINK_RAY_CARD);
        // Poison Spit
        $givePoisonSpitToken = $this->countCardOfType($playerId, POISON_SPIT_CARD);

        $fireBreathingDamages = $this->getGlobalVariable(FIRE_BREATHING_DAMAGES, true);

        $jetsDamages = [];
        $smashedPlayersInTokyo = [];
        foreach($smashedPlayersIds as $smashedPlayerId) {
            $smashedPlayerIsInTokyo = $this->inTokyo($smashedPlayerId);
            if ($smashedPlayerIsInTokyo) {
                $smashedPlayersInTokyo[] = $smashedPlayerId;
            }

            $fireBreathingDamage = array_key_exists($smashedPlayerId, $fireBreathingDamages) ? $fireBreathingDamages[$smashedPlayerId] : 0;
            $damageAmount = $diceCount + $fireBreathingDamage;

            // Jets
            $countJets = $this->countCardOfType($smashedPlayerId, JETS_CARD);

            if ($countJets > 0 && $smashedPlayerIsInTokyo) {                
                $jetsDamages[] = new Damage($smashedPlayerId, $damageAmount, $playerId, 0, $giveShrinkRayToken, $givePoisonSpitToken);
            } else {
                $damages[] = new Damage($smashedPlayerId, $damageAmount, $playerId, 0, $giveShrinkRayToken, $givePoisonSpitToken);
            }
        }

        if (count($smashedPlayersInTokyo) > 0) {
            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, $smashedPlayersInTokyo);
            $nextState = "smashes";
            $nextStateId = ST_MULTIPLAYER_LEAVE_TOKYO;
        } else {
            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, []);
        }

        $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);
        self::setGameStateValue(STATE_AFTER_RESOLVE, $nextStateId);

        self::notifyAllPlayers("resolveSmashDice", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'number' => $diceCount,
            'smashedPlayersIds' => $smashedPlayersIds,
        ]);

        // Alpha Monster
        $countAlphaMonster = $this->countCardOfType($playerId, ALPHA_MONSTER_CARD);
        if ($countAlphaMonster > 0) {
            $this->applyGetPoints($playerId, $countAlphaMonster, ALPHA_MONSTER_CARD);
        }

        // fire breathing
        foreach ($fireBreathingDamages as $damagePlayerId => $fireBreathingDamage) {
            self::notifyAllPlayers("fireBreathingExtraDamage", clienttranslate('${player_name} loses ${number} extra [Heart] with ${card_name}'), [
                'playerId' => $damagePlayerId,
                'player_name' => $this->getPlayerName($damagePlayerId),
                'number' => 1,
                'card_name' => FIRE_BREATHING_CARD,
            ]);

            // we add damage only if it's not already counted in smashed players (without tokens)
            if (!in_array($damagePlayerId, $smashedPlayersIds)) {
                $damages[] = new Damage($damagePlayerId, $fireBreathingDamage, $damagePlayerId, 0, 0, 0);
            }
        }

        if (count($damages) > 0) {
            if ($this->resolveDamages($damages, $nextState)) {
                return null; // no redirect on stResolveSmashDice, handled by resolveDamages
            }
        }
        return $nextState;

        /* TODOCY $redirects = false;
        if (count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, ST_RESOLVE_SKULL_DICE);
        }
        return $redirects;*/
    }

    function getChangeDieCards(int $playerId) {
        // Herd Culler
        $herdCullerCards = $this->getCardsOfType($playerId, HERD_CULLER_CARD);
        $availableHerdCullers = 0;
        $herdCullerCount = count($herdCullerCards);
        if ($herdCullerCount > 0) {
            $usedCards = $this->getUsedCard();
            foreach ($herdCullerCards as $herdCullerCard) {
                if (!in_array($herdCullerCard->id, $usedCards)) {
                    $availableHerdCullers++;
                }
            }
        }
        $hasHerdCuller = $herdCullerCount > 0 && $availableHerdCullers > 0;
        // Plot Twist
        $hasPlotTwist = $this->countCardOfType($playerId, PLOT_TWIST_CARD) > 0;
        // Stretchy
        $hasStretchy = $this->countCardOfType($playerId, STRETCHY_CARD) > 0 && $this->getPlayerEnergy($playerId) >= 2;

        $hasClown = intval(self::getGameStateValue(CLOWN_ACTIVATED)) == 1;
        // Clown
        if (!$hasClown && $this->countCardOfType($playerId, CLOWN_CARD) > 0) {
            $dice = $this->getPlayerRolledDice($playerId, true, false, false); 
            $diceCounts = $this->getRolledDiceCounts($playerId, $dice, false);
            if ($diceCounts[1] >= 1 && $diceCounts[2] >= 1 && $diceCounts[3] >= 1 && $diceCounts[4] >= 1 && $diceCounts[5] >= 1 && $diceCounts[6] >= 1) { // dice 1-2-3 check with previous if
                self::setGameStateValue(CLOWN_ACTIVATED, 1);
                $hasClown = true;
            }
        }
        
        return [
            'hasHerdCuller' => $hasHerdCuller,
            'hasPlotTwist' => $hasPlotTwist,
            'hasStretchy' => $hasStretchy,
            'hasClown' => $hasClown,
        ];
    }

    function canChangeDie(array $cards) {
        return $cards['hasHerdCuller'] || $cards['hasPlotTwist'] || $cards['hasStretchy'] || $cards['hasClown'];
    }

    function getSelectHeartDiceUse(int $playerId) {        
        // Healing Ray
        $countHealingRay = $this->countCardOfType($playerId, HEALING_RAY_CARD);
        $healablePlayers = [];
        if ($countHealingRay > 0) {
            $otherPlayers = $this->getOtherPlayers($playerId);
    
            foreach($otherPlayers as $otherPlayer) {
                $missingHearts = $this->getPlayerMaxHealth($otherPlayer->id) - $this->getPlayerHealth($otherPlayer->id);

                if ($missingHearts > 0) {
                    $playerHealInformations = new \stdClass();
                    $playerHealInformations->id = $otherPlayer->id;
                    $playerHealInformations->name = $otherPlayer->name;
                    $playerHealInformations->color = $otherPlayer->color;
                    $playerHealInformations->energy = $otherPlayer->energy;
                    $playerHealInformations->missingHearts = $missingHearts;

                    $healablePlayers[] = $playerHealInformations;
                }
            }
        }

        return [
            'hasHealingRay' => $countHealingRay > 0,
            'healablePlayers' => $healablePlayers,
            'shrinkRayTokens' => $this->getPlayerShrinkRayTokens($playerId),
            'poisonTokens' => $this->getPlayerPoisonTokens($playerId),
        ];
    }

    

    function getPlayersWithPsychicProbe(int $playerId) {
        $orderedPlayers = $this->getOrderedPlayers($playerId);
        $psychicProbePlayerIds = [];

        foreach($orderedPlayers as $player) {
            if ($player->id != $playerId) {

                $psychicProbeCards = $this->getCardsOfType($player->id, PSYCHIC_PROBE_CARD);
                $unusedPsychicProbeCards = 0;
                $usedCards = $this->getUsedCard();
                foreach($psychicProbeCards as $psychicProbeCard) {
                    if (!in_array($psychicProbeCard->id, $usedCards)) {
                        $unusedPsychicProbeCards++;
                    }
                }
                if ($unusedPsychicProbeCards > 0) {
                    $psychicProbePlayerIds[] = $player->id;
                }            
            }
        }

        return $psychicProbePlayerIds;
    }

    function getPsychicProbeInterventionEndState($intervention) {
        $canChangeWithCards = $this->canChangeDie($this->getChangeDieCards($intervention->activePlayerId));
        $canRetrow3 = intval(self::getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0 && $this->countCardOfType($intervention->activePlayerId, BACKGROUND_DWELLER_CARD) > 0;
        $backToChangeDie = $canChangeWithCards || $canRetrow3;
        return $backToChangeDie ? 'endAndChangeDieAgain' : 'end';
    }

    function getDieFaceLogName(int $face, int $type) {
        if ($type == 0) {
            switch($face) {
                case 1: case 2: case 3: return "[dice$face]";
                case 4: return "[diceHeart]";
                case 5: return "[diceEnergy]";
                case 6: return "[diceSmash]";
            }
        } else if ($type == 1) {
            switch($face) {
                case 1:  return "[berserkDieEnergy]";
                case 2: return "[berserkDieDoubleEnergy]";
                case 3: case 4: return "[berserkDieSmash]";
                case 5: return "[berserkDieDoubleSmash]";
                case 6: return "[berserkDieSkull]";
            }
        } else if ($type == 2) {
            switch($face) {
                case 1:  return "[dieFateEye]";
                case 2: return "[dieFateRiver]";
                case 3: return "[dieFateSnake]";
                case 4: return "[dieFateAnkh]";
            }
        }
    }
  	
    function rethrowDice(string $diceIds) {
        $playerId = self::getActivePlayerId();
        self::DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");
        self::DbQuery("UPDATE dice SET `locked` = false, `rolled` = true where `dice_id` IN ($diceIds)");

        $diceCount = count(explode(',', $diceIds));
        self::incStat($diceCount, 'rethrownDice', $playerId);

        $this->throwDice($playerId, false);

        $throwNumber = intval(self::getGameStateValue('throwNumber')) + 1;
        self::setGameStateValue('throwNumber', $throwNumber);

        $this->gamestate->nextState('rethrow');
    }

    function getChangeActivePlayerDieIntervention(int $playerId) { // return null or ChangeActivePlayerDieIntervention
        $playersWithPsychicProbe = $this->getPlayersWithPsychicProbe($playerId);
        $playersWithActivatedWitch = [];
        
        $witchCards = $this->getCardsFromDb($this->cards->getCardsOfType(WITCH_CARD));
        $witchCard = null;
        if (count($witchCards) > 0) {
            $witchCard = $witchCards[0];
        
            if ($witchCard->location == 'hand') {
                $witchPlayerId = intval($witchCard->location_arg);

                if ($this->willBeWounded($witchPlayerId, $playerId)) {
                    $playersWithActivatedWitch[] = $witchPlayerId;
                }
            }
        }

        $playersWithChangeActivePlayerDieCard = array_unique(array_merge($playersWithPsychicProbe, $playersWithActivatedWitch), SORT_REGULAR);
        if (count($playersWithChangeActivePlayerDieCard) > 0) {
            $cards = [];
            foreach ($playersWithPsychicProbe as $playerWithPsychicProbe) {
                $cards = array_merge($cards, $this->getCardsOfType($playerWithPsychicProbe, PSYCHIC_PROBE_CARD));
            }
            foreach ($playersWithActivatedWitch as $playerWithActivatedWitch) {
                $cards = array_merge($cards, $this->getCardsOfType($playerWithActivatedWitch, WITCH_CARD));
            }
            $changeActivePlayerDieIntervention = new ChangeActivePlayerDieIntervention($playersWithChangeActivePlayerDieCard, $playerId, $cards);
            return $changeActivePlayerDieIntervention;
        }
        return null;
    }

    function addSmashesFromCards(int $playerId, array $diceCounts, bool $playerInTokyo) {
        $addedSmashes = 0;
        $cardsAddingSmashes = [];

        // cheerleader
        if (intval(self::getGameStateValue(CHEERLEADER_SUPPORT)) == 1) {
            $addedSmashes += 1;
            $cardsAddingSmashes[] = CHEERLEADER_CARD;
        }

        // acid attack
        $countAcidAttack = $this->countCardOfType($playerId, ACID_ATTACK_CARD);
        if ($countAcidAttack > 0) {
            $addedSmashes += $countAcidAttack;

            for ($i=0; $i<$countAcidAttack; $i++) { $cardsAddingSmashes[] = ACID_ATTACK_CARD; }
        }

        // burrowing
        if ($playerInTokyo) {
            $countBurrowing = $this->countCardOfType($playerId, BURROWING_CARD);
            if ($countBurrowing > 0) {
                $addedSmashes += $countBurrowing;

                for ($i=0; $i<$countBurrowing; $i++) { $cardsAddingSmashes[] = BURROWING_CARD; }
            }
        }

        // poison quills
        if ($diceCounts[2] >= 3) {
            $countPoisonQuills = $this->countCardOfType($playerId, POISON_QUILLS_CARD);
            if ($countPoisonQuills > 0) {
                $addedSmashes += 2 * $countPoisonQuills;
                
                for ($i=0; $i<$countPoisonQuills; $i++) { $cardsAddingSmashes[] = POISON_QUILLS_CARD; }
            }
        }

        if ($diceCounts[6] >= 1) {
            // spiked tail
            $countSpikedTail = $this->countCardOfType($playerId, SPIKED_TAIL_CARD);
            if ($countSpikedTail > 0) {
                $addedSmashes += $countSpikedTail;
                
                for ($i=0; $i<$countSpikedTail; $i++) { $cardsAddingSmashes[] = SPIKED_TAIL_CARD; }
            }

            // urbavore
            if ($playerInTokyo) {
                $countUrbavore = $this->countCardOfType($playerId, URBAVORE_CARD);
                if ($countUrbavore > 0) {
                    $addedSmashes += $countUrbavore;
                
                    for ($i=0; $i<$countUrbavore; $i++) { $cardsAddingSmashes[] = URBAVORE_CARD; }
                }
            }

            // antimatter beam (must be last)
            if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, ANTIMATTER_BEAM_WICKEDNESS_TILE)) {
                $addedSmashes += ($diceCounts[6] + $addedSmashes);
                $cardsAddingSmashes[] = 2000 + ANTIMATTER_BEAM_WICKEDNESS_TILE;
            }
        }

        $detail = new \stdClass();
        $detail->addedSmashes = $addedSmashes;
        $detail->cardsAddingSmashes = $cardsAddingSmashes;
        return $detail;
    }

    function getUnusedChangeActivePlayerDieCards(int $playerId) {
        $psychicProbeCards = $this->getCardsOfType($playerId, PSYCHIC_PROBE_CARD);
        $witchCards = $this->getCardsOfType($playerId, WITCH_CARD);
        if (count($witchCards) > 0 && !$this->willBeWounded($playerId, $this->getActivePlayerId())) {
            $witchCards = [];
        }
        
        $usedCards = $this->getUsedCard();
        $unusedCards = [];

        // witch first if available
        foreach($witchCards as $witchCard) {
            if (!in_array($witchCard->id, $usedCards)) {
                $unusedCards[] = $witchCard;
            }
        }
        // then psychic probe
        // we want only one psychicProbe, event if player got 2
        $psychicProbeCards = array_slice($psychicProbeCards, 0, 1);

        foreach($psychicProbeCards as $psychicProbeCard) {
            if (!in_array($psychicProbeCard->id, $usedCards)) {
                $unusedCards[] = $psychicProbeCard;
            }
        }

        return $unusedCards;
    }

    function getNewTokyoTowerLevel(int $playerId) {
        $levels = $this->getTokyoTowerLevels($playerId);
        $newLevel = 1;
        for ($i=1; $i<3;$i++) {
            if (in_array($newLevel, $levels)) {
                $newLevel++;
            }
        }

        $this->changeTokyoTowerOwner($playerId, $newLevel);

        if ($newLevel === 3) {
            $playerScore = $this->getPlayerScore($playerId);
            $this->applyGetPoints($playerId, MAX_POINT - $playerScore, 0);
            
            self::notifyAllPlayers("fullTokyoTower", /*client TODOKK translate(*/'${player_name} claims Tokyo Tower top level and wins the game'/*)*/, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
            ]);
        }
    }

    function getRolledDiceCounts(int $playerId, array $dice, $ignoreCanUseFace = true) {
        $diceCounts = [0,0,0,0,0,0,0];

        foreach($dice as $die) {
            if (($die->type === 0 || $die->type === 1) && ($ignoreCanUseFace || $this->canUseFace($playerId, $this->getDiceFaceType($die)))) {
                if ($die->type === 0) {                
                    $diceCounts[$die->value] += 1;
                } else if ($die->type === 1) {
                    switch($die->value) {
                        case 1: 
                            $diceCounts[5] += 1;
                            break;
                        case 2: 
                            $diceCounts[5] += 2;
                            break;
                        case 3: case 4: 
                            $diceCounts[6] += 1;
                            break;
                        case 5: 
                            $diceCounts[6] += 2;
                            break;
                        case 6: 
                            $diceCounts[7] = 1;
                            break;
                    }
                }
            }
        }

        return $diceCounts;
    }

    function getDieOfFate() {
        return $this->getDiceByType(2)[0];
    }

    function applyAnkhEffect(int $playerId) {
        $cardType = $this->getCurseCardType();
        $logCardType = 1000 + $cardType;

        switch($cardType) {
            // TODOAN
            case PHARAONIC_EGO_CURSE_CARD:
                $this->leaveTokyo($playerId);
                $this->addLeaverWithBurrowing($playerId);
                break;
            case ISIS_S_DISGRACE_CURSE_CARD: 
            case THOT_S_BLINDNESS_CURSE_CARD: 
            case TUTANKHAMUN_S_CURSE_CURSE_CARD: 
            case FORBIDDEN_LIBRARY_CURSE_CARD: 
            case CONFUSED_SENSES_CURSE_CARD: 
            case PHARAONIC_SKIN_CURSE_CARD:
                $this->changeGoldenScarabOwner($playerId);
                break;
            case SET_S_STORM_CURSE_CARD:
            case BOW_BEFORE_RA_CURSE_CARD: 
            case ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                $this->applyGetHealth($playerId, 2, $logCardType, $playerId);
                break;
            case VENGEANCE_OF_HORUS_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                $this->applyGetPoints($playerId, $rolledSmashes, $logCardType);
                break;
            case ORDEAL_OF_THE_WEALTHY_CURSE_CARD:
                $this->applyGetPoints($playerId, 2, $logCardType);
                break;
            case ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD:
                $this->applyGetEnergy($playerId, 2, $logCardType);
                break;
            case RESURRECTION_OF_OSIRIS_CURSE_CARD:
                return $this->replacePlayersInTokyo($playerId);
            case GAZE_OF_THE_SPHINX_CURSE_CARD:
                $this->applyGetEnergy($playerId, 3, $logCardType);
                break;
            case SCRIBE_S_PERSEVERANCE_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolled1s = $diceCounts[1];
                $this->applyGetEnergy($playerId, $rolled1s, $logCardType);
                break;
        }
    }
    
    function applySnakeEffect(int $playerId) { // return damages
        $cardType = $this->getCurseCardType();
        $logCardType = 1000 + $cardType;

        switch($cardType) {
            // TODOAN
            case PHARAONIC_EGO_CURSE_CARD:
                return $this->replacePlayersInTokyo($playerId);
            case ISIS_S_DISGRACE_CURSE_CARD: 
            case SET_S_STORM_CURSE_CARD:
                return [new Damage($playerId, 1, $playerId, $logCardType)];
            case THOT_S_BLINDNESS_CURSE_CARD: 
                $this->applyLoseEnergy($playerId, 2, $logCardType);
                break;
            case TUTANKHAMUN_S_CURSE_CURSE_CARD: 
                $this->applyLosePoints($playerId, 2, $logCardType);
                break;
            case HOTEP_S_PEACE_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                $this->applyLoseEnergy($playerId, $rolledSmashes, $logCardType);
                break;
            case BUILDERS_UPRISING_CURSE_CARD: 
                $this->applyLosePoints($playerId, 2, $logCardType);
                break;
            case BOW_BEFORE_RA_CURSE_CARD:
                return [new Damage($playerId, 2, $playerId, $logCardType)];
            case VENGEANCE_OF_HORUS_CURSE_CARD:
                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
                $rolledSmashes = $diceCounts[6];
                return [new Damage($playerId, $rolledSmashes, $playerId, $logCardType)];
            case ORDEAL_OF_THE_MIGHTY_CURSE_CARD:
                $playersIds = $this->getPlayersIdsWithMaxColumn('player_health');
                $damages = [];
                foreach ($playersIds as $pId) {
                    $damages[] = new Damage($pId, 1, $playerId, $logCardType); // TODOAN TOCHECK confirm the player is the damage dealer ? or 0 ?
                }
                return $damages;
            case ORDEAL_OF_THE_WEALTHY_CURSE_CARD:
                $playersIds = $this->getPlayersIdsWithMaxColumn('player_score');
                foreach ($playersIds as $pId) {
                    $this->applyLosePoints($pId, 1, $logCardType);
                }
                break;
            case ORDEAL_OF_THE_SPIRITUAL_CURSE_CARD:
                $playersIds = $this->getPlayersIdsWithMaxColumn('player_energy');
                foreach ($playersIds as $pId) {
                    $this->applyLoseEnergy($pId, 1, $logCardType);
                }
                break;
            case RESURRECTION_OF_OSIRIS_CURSE_CARD:
                $this->leaveTokyo($playerId);
                $this->addLeaverWithBurrowing($playerId);
                break;
            case KHEPRI_S_REBELLION_CURSE_CARD:
                $this->changeGoldenScarabOwner($playerId);
                break;
            case GAZE_OF_THE_SPHINX_CURSE_CARD:
                $this->applyLoseEnergy($playerId, 3, $logCardType);
                break;
        }

        return null;
    }
}
