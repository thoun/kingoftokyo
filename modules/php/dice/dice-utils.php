<?php

namespace KOT\States;

require_once(__DIR__.'/../objects/dice.php');
require_once(__DIR__.'/../objects/player-intervention.php');
require_once(__DIR__.'/../objects/damage.php');

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
        $sql = "SELECT * FROM dice where `type` = 0 ORDER BY dice_id limit $number";
        $dbDices = $this->getCollectionFromDb($sql);
        $dice = array_map(fn($dbDice) => new Dice($dbDice), array_values($dbDices));
        return array_values(array_filter($dice, fn($die) => !$die->discarded));
    }

    function getDieById(int $id) {
        $sql = "SELECT * FROM dice WHERE `dice_id` = $id";
        $dbDices = $this->getCollectionFromDb($sql);
        return array_map(fn($dbDice) => new Dice($dbDice), array_values($dbDices))[0];
    }

    function getFirstDieOfValue(int $playerId, int $value) {
        $dice = $this->getDice($this->getDiceNumber($playerId));
        foreach ($dice as $die) {
            if ($die->value === $value) {
                return $die;
            }
        }
        return null;
    }

    function getFirst3Die(int $playerId) {
        return $this->getFirstDieOfValue($playerId, 3);
    }

    function getDiceByType(int $type) {
        $sql = "SELECT * FROM dice WHERE `type` = $type";
        $dbDices = $this->getCollectionFromDb($sql);
        return array_map(fn($dbDice) => new Dice($dbDice), array_values($dbDices));
    }

    private function getPlayerRolledDice(int $playerId, bool $includeBerserkDie, bool $includeDieOfFate, bool $setCanReroll) {
        $dice = $this->getDice($this->getDiceNumber($playerId));

        if ($includeBerserkDie && $this->isCybertoothExpansion() && $this->isPlayerBerserk($playerId)) {
            $dice = array_merge($dice, $this->getDiceByType(1)); // type 1 at the end
        }

        if ($setCanReroll && $this->isAnubisExpansion()) {
            foreach ($dice as &$die) {
                $symbol = getDieFace($die);

                $stateId = intval($this->gamestate->state_id());
                $isChangeActivePlayerDie = $stateId == ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE;

                $die->canReroll = $this->canRerollSymbol($playerId, $symbol, $isChangeActivePlayerDie);
            }
        }

        if ($includeDieOfFate && $this->isAnubisExpansion() && intval($this->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) != 2) {
            $dice = array_merge($this->getDiceByType(2), $dice); // type 2 at the start
        }

        return $dice;
    }

    public function throwDice(int $playerId, bool $firstRoll) {
        $dice = $this->getPlayerRolledDice($playerId, true, true, true);

        $this->DbQuery( "UPDATE dice SET `rolled` = false");

        $lockedDice = [];
        $rolledDice = [];
        
        foreach ($dice as &$die) {
            if ($die->locked) {
                $lockedDice[] = $die;
            } else {
                $facesNumber = $die->type == 2 ? 4 : 6;
                $die->value = bga_rand(1, $facesNumber);
                $this->DbQuery( "UPDATE dice SET `dice_value` = ".$die->value.", `rolled` = true where `dice_id` = ".$die->id );

                $rolledDice[] = $die;
            }

            if (!$this->canRerollSymbol($playerId, getDieFace($die))) {
                $die->locked = true;
                $this->DbQuery( "UPDATE dice SET `locked` = true where `dice_id` = ".$die->id );
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

            $this->notifyAllPlayers("diceLog", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'rolledDice' => $rolledDiceStr,
                'lockedDice' => $lockedDiceStr,
            ]);
        }
    }

    function fixDices() {
        $this->DbQuery( "UPDATE dice SET `rolled` = false");
    }

    function getDiceNumber(int $playerId, $compute = false) {
        if (!$compute && $this->getGameStateValue(DICE_NUMBER) > 0) { // TODOAN/TODOCY remove second test
            return intval($this->getGameStateValue(DICE_NUMBER)) + intval($this->getGameStateValue(RAGING_FLOOD_EXTRA_DIE_SELECTED));
        }

        $add = $this->countCardOfType($playerId, EXTRA_HEAD_1_CARD) + $this->countCardOfType($playerId, EXTRA_HEAD_2_CARD);
        $remove = intval($this->getGameStateValue(FREEZE_TIME_CURRENT_TURN)) + $this->getPlayerShrinkRayTokens($playerId);

        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, CYBERBRAIN_WICKEDNESS_TILE)) {
            $add += 1;
        }

        if ($this->isAnubisExpansion()) {
            $curseCardType = $this->getCurseCardType();

            if ($curseCardType == RAGING_FLOOD_CURSE_CARD) {
                $remove++;
            }
    
            if ($curseCardType == FALSE_BLESSING_CURSE_CARD) {
                $add += 2;
            }
            if (boolval($this->getGameStateValue(RAGING_FLOOD_EXTRA_DIE_SELECTED))) {
                $add += 1;
            }
        }

        if ($this->isPowerUpExpansion()) {
            $coldWaveOwner = $this->isEvolutionOnTable(COLD_WAVE_EVOLUTION);
            if ($coldWaveOwner != null && $coldWaveOwner != $playerId) {
                $remove++;
            }          
        }

        return max(6 + $add - $remove, 0);
    }

    function resolveNumberDice(int $playerId, int $number, int $diceCount) {
        // number
        if ($diceCount >= 3) {
            $points = $number + $diceCount - 3;

            $this->applyGetPoints($playerId, $points, -1);

            $this->incStat($points, 'pointsWonWith'.$number.'Dice', $playerId);

            $this->notifyAllPlayers( "resolveNumberDice", clienttranslate('${player_name} gains ${deltaPoints}[Star] with ${dice_value} dice'), [
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
                
                // Skulking
                if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, SKULKING_WICKEDNESS_TILE)) {
                    $this->applyGetPoints($playerId, 1, 2000 + SKULKING_WICKEDNESS_TILE);
                }
            }
            

            if (($number == 1 || $number == 2) && $this->isWickednessExpansion()) {
                $this->applyGetWickedness($playerId, 3 - $number);
            }
        }

        if ($diceCount >= 1 && $number == 1 && $this->isPowerUpExpansion() && $this->hasEvolutionOfType($playerId, MOUSE_HUNTER_EVOLUTION)) {
            $this->applyGetPoints($playerId, 1, 3000 + MOUSE_HUNTER_EVOLUTION);
        }
    }

    function resolveHealthDice(int $playerId, int $diceCount) {
        if (!$this->canHealWithDice($playerId)) {
            $message = clienttranslate('${player_name} gains no [Heart] (player in Tokyo)');
            if ($this->isAnubisExpansion() && $this->getCurseCardType() == RESURRECTION_OF_OSIRIS_CURSE_CARD) {
                $message = clienttranslate('${player_name} gains no [Heart] (player outside Tokyo)');
            }

            $this->notifyAllPlayers( "resolveHealthDiceInTokyo",$message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
            ]);
        } else {

            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth && $this->canGainHealth($playerId)) {
                $playerGettingHealth = $this->getPlayerGettingEnergyOrHeart($playerId);

                $this->notifyAllPlayers( "resolveHealthDice", clienttranslate('${player_name} gains ${deltaHealth} [Heart]'), [
                    'playerId' => $playerGettingHealth,
                    'player_name' => $this->getPlayerName($playerGettingHealth),
                    'deltaHealth' => $diceCount,
                ]);

                $this->applyGetHealth($playerGettingHealth, $diceCount, 0, $playerId);
            }
        }

        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, SKYBEAM_WICKEDNESS_TILE)) {
            $this->applyGetHealth($playerId, $diceCount, 2000 + SKYBEAM_WICKEDNESS_TILE, $playerId);
        }
    }

    function resolveEnergyDice(int $playerId, int $diceCount) {
        if (!$this->canGainEnergy($playerId)) {
            return;
        }
        
        $playerGettingEnergy = $this->getPlayerGettingEnergyOrHeart($playerId);

        $this->notifyAllPlayers( "resolveEnergyDice", clienttranslate('${player_name} gains ${deltaEnergy} [Energy]'), [
            'playerId' => $playerGettingEnergy,
            'player_name' => $this->getPlayerName($playerGettingEnergy),
            'deltaEnergy' => $diceCount,
        ]);
        
        $this->applyGetEnergy($playerId, $diceCount, 0);

        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, SKYBEAM_WICKEDNESS_TILE)) {
            $this->applyGetEnergy($playerId, $diceCount, 2000 + SKYBEAM_WICKEDNESS_TILE);
        }
    }

    
    function resolveSmashDice(int $playerId, int $diceCount) { // return redirects

        // ony here and not in stResolveDice, so player can heal and then activate Berserk
        if ($diceCount >= 4 && $this->isCybertoothExpansion() && !$this->isPlayerBerserk($playerId) && $this->canUseFace($playerId, 6)) {
            $this->setPlayerBerserk($playerId, true);
        }

        // Nova breath
        $countNovaBreath = $this->countCardOfType($playerId, NOVA_BREATH_CARD);

        $message = null;
        $smashedPlayersIds = null;
        $inTokyo = $this->inTokyo($playerId);
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
        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, POISON_SPIT_WICKEDNESS_TILE)) {
            $givePoisonSpitToken += 1;
        }
        // Underdog
        $playerScore = null;
        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, UNDERDOG_WICKEDNESS_TILE)) {
            $playerScore = $this->getPlayerScore($playerId);
        }

        $fireBreathingDamages = $this->getGlobalVariable(FIRE_BREATHING_DAMAGES, true);

        $jetsDamages = [];
        $smashedPlayersInTokyo = [];
        $isPowerUpExpansion = $this->isPowerUpExpansion();
        foreach($smashedPlayersIds as $smashedPlayerId) {
            $smashedPlayerIsInTokyo = $this->inTokyo($smashedPlayerId);
            if ($smashedPlayerIsInTokyo) {
                $smashedPlayersInTokyo[] = $smashedPlayerId;
            }

            $fireBreathingDamage = array_key_exists($smashedPlayerId, $fireBreathingDamages) ? $fireBreathingDamages[$smashedPlayerId] : 0;
            $damageAmount = $diceCount + $fireBreathingDamage;

            // Jets
            $countJets = $this->countCardOfType($smashedPlayerId, JETS_CARD);
            $countSimianScamper = 0;
            if ($isPowerUpExpansion) {
                $countSimianScamper = $this->hasEvolutionOfType($smashedPlayerId, SIMIAN_SCAMPER_EVOLUTION, true, true) ? 1 : 0;
            }

            $newDamage = new Damage($smashedPlayerId, $damageAmount, $playerId, 0, $giveShrinkRayToken, $givePoisonSpitToken, $playerScore);
            if (($countJets > 0 || $countSimianScamper > 0) && $smashedPlayerIsInTokyo) {                
                $jetsDamages[] = $newDamage;
            } else {
                $damages[] = $newDamage;
            }
        }

        if (count($smashedPlayersInTokyo) > 0) {
            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, $smashedPlayersInTokyo);
            $nextStateId = ST_MULTIPLAYER_LEAVE_TOKYO;
        } else {
            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, []);
        }

        $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);
        $this->setGameStateValue(STATE_AFTER_RESOLVE, $nextStateId);

        $this->notifyAllPlayers("resolveSmashDice", $message, [
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
            $this->notifyAllPlayers("fireBreathingExtraDamage", clienttranslate('${player_name} loses ${number} extra [Heart] with ${card_name}'), [
                'playerId' => $damagePlayerId,
                'player_name' => $this->getPlayerName($damagePlayerId),
                'number' => 1,
                'card_name' => FIRE_BREATHING_CARD,
            ]);

            // we add damage only if it's not already counted in smashed players (without tokens)
            if (!in_array($damagePlayerId, $smashedPlayersIds)) {
                $damages[] = new Damage($damagePlayerId, $fireBreathingDamage, $playerId, 0, 0, 0);
            }
        }

        $redirects = false;
        if (count($damages) > 0) {
            $redirects = $this->resolveDamages($damages, ST_RESOLVE_SKULL_DICE);
        }
        return $redirects;
    }

    function getChangeDieCards(int $playerId) {
        // Herd Culler
        $hasHerdCuller = false;
        $herdCullerCards = $this->getCardsOfType($playerId, HERD_CULLER_CARD);
        if (count($herdCullerCards) > 0) {
            $usedCards = $this->getUsedCard();
            $availableHerdCullers = array_values(array_filter($herdCullerCards, fn($card) => !in_array($card->id, $usedCards)));
            $hasHerdCuller = count($availableHerdCullers) > 0;
        }
        // Plot Twist
        $hasPlotTwist = $this->countCardOfType($playerId, PLOT_TWIST_CARD) > 0;
        // Stretchy
        $potentialEnergy = $this->getPlayerEnergy($playerId);
        if ($this->isCthulhuExpansion()) {
            $potentialEnergy += $this->getPlayerCultists($playerId);
        }
        $hasStretchy = $this->countCardOfType($playerId, STRETCHY_CARD) > 0 && $potentialEnergy >= 2;

        $hasClown = boolval($this->getGameStateValue(CLOWN_ACTIVATED));
        // Clown
        if (!$hasClown && $this->countCardOfType($playerId, CLOWN_CARD) > 0) {
            $dice = $this->getPlayerRolledDice($playerId, false, false, false); 
            $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
            if ($diceCounts[1] >= 1 && $diceCounts[2] >= 1 && $diceCounts[3] >= 1 && $diceCounts[4] >= 1 && $diceCounts[5] >= 1 && $diceCounts[6] >= 1) {
                $this->setGameStateValue(CLOWN_ACTIVATED, 1);
                $hasClown = true;
            }
        }

        $isPowerUpExpansion = $this->isPowerUpExpansion();

        // Gamma Breath & Tail Sweep
        $hasGammaBreath = false;
        $hasTailSweep = false;
        if ($isPowerUpExpansion) {
            $gammaBreathCards = $this->getEvolutionsOfType($playerId, GAMMA_BREATH_EVOLUTION, true, true);
            if (count($gammaBreathCards) > 0) {
                $usedCards = $this->getUsedCard();
                $availableGammaBreath = array_values(array_filter($gammaBreathCards, fn($gammaBreathCard) => !in_array(3000 + $gammaBreathCard->id, $usedCards)));
                $hasGammaBreath = count($availableGammaBreath) > 0;
            }

            $tailSweepCards = $this->getEvolutionsOfType($playerId, TAIL_SWEEP_EVOLUTION, true, true);
            if (count($tailSweepCards) > 0) {
                $usedCards = $this->getUsedCard();
                $availableTailSweep = array_values(array_filter($tailSweepCards, fn($tailSweepCard) => !in_array(3000 + $tailSweepCard->id, $usedCards)));
                $hasTailSweep = count($availableTailSweep) > 0;
            }
        }

        // Saurian Adaptability
        $hasSaurianAdaptability = $isPowerUpExpansion && $this->hasEvolutionOfType($playerId, SAURIAN_ADAPTABILITY_EVOLUTION, false, true);

        // yin & yang
        $hasYinYang = $isPowerUpExpansion && $this->hasEvolutionOfType($playerId, YIN_YANG_EVOLUTION);
        
        return [
            'hasHerdCuller' => $hasHerdCuller,
            'hasPlotTwist' => $hasPlotTwist,
            'hasStretchy' => $hasStretchy,
            'hasClown' => $hasClown,
            'hasSaurianAdaptability' => $hasSaurianAdaptability,
            'hasGammaBreath' => $hasGammaBreath,
            'hasTailSweep' => $hasTailSweep,
            'hasYinYang' => $hasYinYang,
        ];
    }

    function canChangeDie(array $cards) {
        return $cards['hasHerdCuller'] 
            || $cards['hasPlotTwist'] 
            || $cards['hasStretchy'] 
            || $cards['hasClown'] 
            || $cards['hasSaurianAdaptability'] 
            || $cards['hasGammaBreath'] 
            || $cards['hasTailSweep'] 
            || $cards['hasYinYang'];
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
        $canRetrow3 = intval($this->getGameStateValue(PSYCHIC_PROBE_ROLLED_A_3)) > 0 && $this->countCardOfType($intervention->activePlayerId, BACKGROUND_DWELLER_CARD) > 0;
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
        if ($diceIds == '') {
            throw new \BgaUserException('No dice to reroll');
        }

        $playerId = $this->getActivePlayerId();
        $this->DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");
        $this->DbQuery("UPDATE dice SET `locked` = false, `rolled` = true where `dice_id` IN ($diceIds)");

        $diceCount = count(explode(',', $diceIds));
        $this->incStat($diceCount, 'rethrownDice', $playerId);

        $this->throwDice($playerId, false);

        $throwNumber = intval($this->getGameStateValue('throwNumber')) + 1;
        $this->setGameStateValue('throwNumber', $throwNumber);

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
        if (intval($this->getGameStateValue(CHEERLEADER_SUPPORT)) == 1) {
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

        // Meow Missle
        if ($diceCounts[1] >= 1 && $this->isPowerUpExpansion() && $this->hasEvolutionOfType($playerId, MEOW_MISSLE_EVOLUTION)) {
            $addedSmashes += 1;
            $cardsAddingSmashes[] = 3000 + MEOW_MISSLE_EVOLUTION;
        }

        if ($diceCounts[6] + $addedSmashes >= 1) {
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

            // Barbs
            if (($diceCounts[6] + $addedSmashes) >= 2 && $this->isWickednessExpansion() && $this->gotWickednessTile($playerId, BARBS_WICKEDNESS_TILE)) {
                $addedSmashes += 1;
                $cardsAddingSmashes[] = 2000 + BARBS_WICKEDNESS_TILE;
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
            
            $this->notifyAllPlayers("fullTokyoTower", clienttranslate('${player_name} claims Tokyo Tower top level and wins the game'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
            ]);
        }
    }

    function getRolledDiceCounts(int $playerId, array $dice, $ignoreCanUseFace = true) {
        $diceCounts = [0,0,0,0,0,0,0,0];

        foreach($dice as $die) {
            if (($die->type === 0 || $die->type === 1) && ($ignoreCanUseFace || $die->type !== 0 || $this->canUseFace($playerId, $die->value))) {
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

    function getSelectableDice(array $dice, bool $canReroll, bool $allowDieOfFate) {
        return array_values(array_filter($dice, function ($die) use ($canReroll, $allowDieOfFate) {
            $allowed = true;

            if (!$canReroll && !$die->canReroll) {
                $allowed = false;
            }

            if (!$allowDieOfFate && $die->type == 2) {
                $allowed = false;
            }

            return $allowed;
        }));
    }

    function canLeaveHibernation(int $playerId, $dice = null) {
        $countHibernation = $this->countCardOfType($playerId, HIBERNATION_CARD);
        if ($countHibernation == 0) {
            return false;
        }

        if ($dice == null) {
            $dice = $this->getPlayerRolledDice($playerId, true, true, false);
        }

        $diceCounts = $this->getRolledDiceCounts($playerId, $dice, false);

        foreach($diceCounts as $face => $number) {
            if ($face !== 4 && $face !== 5 && $number > 0) {
                return true;
            }
        }

        return false;
    }
}
