<?php

namespace KOT\States;

require_once(__DIR__.'/../Objects/dice.php');
require_once(__DIR__.'/../Objects/player-intervention.php');
require_once(__DIR__.'/../Objects/damage.php');

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\MindbugExpansion;
use Bga\Games\KingOfTokyo\Objects\AddSmashTokens;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerUpExpansion;
use KOT\Objects\Dice;
use KOT\Objects\ChangeActivePlayerDieIntervention;
use KOT\Objects\ClawDamage;
use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\PowerCards\HUNTER;

/**
 * @mixin \Bga\Games\KingOfTokyo\Game
 */
trait DiceUtilTrait {
    public PowerUpExpansion $powerUpExpansion;
    public MindbugExpansion $mindbugExpansion;

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

    public function getPlayerRolledDice(int $playerId, bool $includeBerserkDie, bool $includeDieOfFate, bool $setCanReroll) {
        $dice = $this->getDice($this->getDiceNumber($playerId));
        $dice = Arrays::filter($dice, fn($die) => !$die->discarded);

        if ($includeBerserkDie && $this->cybertoothExpansion->isActive() && $this->cybertoothExpansion->isPlayerBerserk($playerId)) {
            $dice = array_merge($dice, $this->getDiceByType(1)); // type 1 at the end
        }

        if ($setCanReroll && $this->anubisExpansion->isActive()) {
            foreach ($dice as &$die) {
                $symbol = getDieFace($die);

                $stateId = intval($this->gamestate->state_id());
                $isChangeActivePlayerDie = $stateId == ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE;

                $die->canReroll = $this->canRerollSymbol($playerId, $symbol, $isChangeActivePlayerDie);
            }
        }

        if ($setCanReroll) {
            $encaseInIceDieId = intval($this->getGameStateValue(ENCASED_IN_ICE_DIE_ID));
            foreach ($dice as &$die) { // TODOPU filter if cannot lock berserk
                if ($die->id == $encaseInIceDieId) {
                    $die->canReroll = false;
                }
            }
        }

        if ($includeDieOfFate && $this->anubisExpansion->isActive() && intval($this->getGameStateValue(BUILDERS_UPRISING_EXTRA_TURN)) != 2) {
            $dice = array_merge($this->getDiceByType(2), $dice); // type 2 at the start
        }

        return $dice;
    }

    public function throwDice(int $playerId, bool $firstRoll) {
        $dice = $this->getPlayerRolledDice($playerId, true, true, true);

        $this->DbQuery( "UPDATE dice SET `rolled` = false");

        $lockedDice = [];
        $rolledDice = [];

        $encaseInIceDieId = intval($this->getGameStateValue(ENCASED_IN_ICE_DIE_ID));
        
        foreach ($dice as &$die) {
            if ($die->locked || $die->id == $encaseInIceDieId) {
                $lockedDice[] = $die;
            } else {
                $facesNumber = $die->type == 2 ? 4 : 6;
                $die->value = bga_rand(1, $facesNumber);
                $this->DbQuery( "UPDATE dice SET `dice_value` = ".$die->value.", `rolled` = true where `dice_id` = ".$die->id );

                $rolledDice[] = $die;
            }

            if (!$this->canRerollSymbol($playerId, getDieFace($die)) || $die->id == $encaseInIceDieId) {
                $die->locked = true;
                $this->DbQuery( "UPDATE dice SET `locked` = true where `dice_id` = ".$die->id );
            }
        }

        if (!$this->getPlayer($playerId)->eliminated) {
            $message = null;

            $rolledDiceStr = '';
            $lockedDiceStr = '';

            usort($rolledDice, [Game::class, 'sortDieFunction']);
            foreach ($rolledDice as $rolledDie) {
                $rolledDiceStr .= $this->getDieFaceLogName($rolledDie->value, $rolledDie->type);
            }

            if ($firstRoll) {
                $message = clienttranslate('${player_name} rolls dice ${rolledDice}');
            } else if (count($lockedDice) == 0) {
                $message = clienttranslate('${player_name} rerolls dice ${rolledDice}');
            } else {
                usort($lockedDice, [Game::class, 'sortDieFunction']);
                foreach ($lockedDice as $lockedDie) {
                    $lockedDiceStr .= $this->getDieFaceLogName($lockedDie->value, $lockedDie->type);
                }

                $message = clienttranslate('${player_name} keeps ${lockedDice} and rerolls dice ${rolledDice}');
            }

            $this->notify->all("diceLog", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
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

        $add = $this->countCardOfType($playerId, EXTRA_HEAD_1_CARD) + $this->countCardOfType($playerId, EXTRA_HEAD_2_CARD) + $this->countCardOfType($playerId, NATURAL_SELECTION_CARD);
        $remove = intval($this->getGameStateValue(FREEZE_TIME_CURRENT_TURN)) + $this->getPlayerShrinkRayTokens($playerId);

        if ($this->wickednessExpansion->isActive()) {
            $add += $this->wickednessTiles->onIncDieCount(new Context($this, currentPlayerId: $playerId));
        }

        if ($this->anubisExpansion->isActive()) {
            $curseCardType = $this->anubisExpansion->getCurseCardType();

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

        if ($this->powerUpExpansion->isActive()) {
            $coldWaveOwner = $this->isEvolutionOnTable(COLD_WAVE_EVOLUTION);
            if ($coldWaveOwner != null && $coldWaveOwner != $playerId) {
                $remove++;
            }   

            if ($this->powerUpExpansion->evolutionCards->getGiftEvolutionOfType($playerId, I_LIVE_UNDER_YOUR_BED_EVOLUTION) !== null) {
                $remove++;
            }
        }

        return max($this->getBaseDice($playerId) + $add - $remove, 0);
    }

    function resolveNumberDice(int $playerId, int $number, int $diceCount) {
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();
        $scaryFaceEvolutions = $this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, \SCARY_FACE_EVOLUTION, true, false);
        foreach ($scaryFaceEvolutions as $scaryFaceEvolution) {
            /** @disregard */
            $scaryFaceEvolution->applyEffect(new Context($this, $playerId));
        }

        // number
        if ($diceCount >= 3) {
            $points = $number + $diceCount - 3;
            if ($isPowerUpExpansion) {
                if ($this->countEvolutionOfType($playerId, CAT_NIP_EVOLUTION) > 0) {  
                    $points *= 2;
                }

                if ($number === 1) {
                    $trickOrThreatEvolutions = $this->getEvolutionsOfType($playerId, TRICK_OR_THREAT_EVOLUTION);
                    if (count($trickOrThreatEvolutions) > 0) {
                        $this->applyTrickOrThreat($playerId, $trickOrThreatEvolutions[0]);
                        return true;
                    }
                }
            }

            $canGetPoints = $this->applyGetPoints($playerId, $points, -1);

            if ($canGetPoints === null) {
                $this->incStat($points, 'pointsWonWith'.$number.'Dice', $playerId);

                $this->notify->all( "resolveNumberDice", clienttranslate('${player_name} gains ${deltaPoints}[Star] with ${dice_value} dice'), [
                    'playerId' => $playerId,
                    'player_name' => $this->getPlayerNameById($playerId),
                    'deltaPoints' => $points,
                    'points' => $this->getPlayerScore($playerId),
                    'diceValue' => $number,
                    'dice_value' => "[dice$number]",
                ]);
            } else {
                $this->notify->all("log", clienttranslate('${player_name} gains no [Star] with ${dice_value} dice because of ${card_name}'), [
                    'playerId' => $playerId,
                    'player_name' => $this->getPlayerNameById($playerId),
                    'card_name' => $canGetPoints,
                    'diceValue' => $number,
                    'dice_value' => "[dice$number]",
                ]);
            }

            if ($number == 1) {
                // gourmet
                $countGourmet = $this->countCardOfType($playerId, GOURMET_CARD);
                if ($countGourmet > 0) {
                    $this->applyGetPoints($playerId, 2 * $countGourmet, GOURMET_CARD);
                }

                // Freeze Time
                $countFreezeTime = $this->countCardOfType($playerId, FREEZE_TIME_CARD);
                if ($countFreezeTime > 0 && $this->mindbugExpansion->canGetExtraTurn()) {
                    $this->incGameStateValue(FREEZE_TIME_MAX_TURNS, 1);
                }
                
                // Skulking
                if ($this->wickednessExpansion->isActive()) {
                    $this->wickednessTiles->onResolvingDieSymbol(new Context($this, currentPlayerId: $playerId, dieSymbol: $number, dieCount: $diceCount));
                }
            }
            

            if (($number == 1 || $number == 2) && $this->wickednessExpansion->isActive()) {
                $wickednessPoints = (3 - $number) * floor($diceCount / 3);
                $this->wickednessExpansion->applyGetWickedness($playerId, $wickednessPoints);
            }
        }

        if ($diceCount >= 1 && $number == 1 && $isPowerUpExpansion) {
            $countMouseHunter = $this->countEvolutionOfType($playerId, MOUSE_HUNTER_EVOLUTION);
            if ($countMouseHunter > 0) {
                $this->applyGetPoints($playerId, $countMouseHunter, 3000 + MOUSE_HUNTER_EVOLUTION);
            }

            if ($this->inTokyo($playerId)) {
                $countClimbTokyoTower = $this->countEvolutionOfType($playerId, CLIMB_TOKYO_TOWER_EVOLUTION);
                if ($countClimbTokyoTower > 0) {
                    if ($diceCount >= 6) {
                        $this->applyGetPointsIgnoreCards($playerId, WIN_GAME, 0);
            
                        $this->notify->all("climbTokyoTower", /*client TODOPUKKtranslate*/('${player_name} rolled [dice1][dice1][dice1][dice1][dice1][dice1] and wins the game with ${card_name}'), [
                            'playerId' => $playerId,
                            'player_name' => $this->getPlayerNameById($playerId),
                            'card_name' => 3000 + CLIMB_TOKYO_TOWER_EVOLUTION,
                        ]);
                    } else {
                        $this->applyGetPoints($playerId, $countClimbTokyoTower * $diceCount, 3000 + CLIMB_TOKYO_TOWER_EVOLUTION);
                    }
                }
            }
        }

        return false;
    }

    function resolveHealthDice(int $playerId, int $diceCount) { 
        $thinkingFaceEvolutions = $this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, \THINKING_FACE_EVOLUTION, true, false);
        foreach ($thinkingFaceEvolutions as $thinkingFaceEvolution) {
            /** @disregard */
            $thinkingFaceEvolution->applyEffect(new Context($this, $playerId));
        }

        if ($this->powerUpExpansion->isActive()) {
            if ($this->countEvolutionOfType($playerId, CAT_NIP_EVOLUTION) > 0) {  
                $diceCount *= 2;
            }
        }

        if (!$this->canHealWithDice($playerId)) {
            $message = clienttranslate('${player_name} gains no [Heart] (player in Tokyo)');
            if ($this->anubisExpansion->isActive() && $this->anubisExpansion->getCurseCardType() == RESURRECTION_OF_OSIRIS_CURSE_CARD) {
                $message = clienttranslate('${player_name} gains no [Heart] (player outside Tokyo)');
            }

            $this->notify->all( "resolveHealthDiceInTokyo",$message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
            ]);
        } else {

            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth && $this->canGainHealth($playerId)) {
                $playerGettingHealth = $this->getPlayerGettingEnergyOrHeart($playerId);

                $this->notify->all( "resolveHealthDice", clienttranslate('${player_name} gains ${deltaHealth} [Heart]'), [
                    'playerId' => $playerGettingHealth,
                    'player_name' => $this->getPlayerNameById($playerGettingHealth),
                    'deltaHealth' => $diceCount,
                ]);

                $this->applyGetHealth($playerGettingHealth, $diceCount, 0, $playerId);
            }
        }

        if ($this->wickednessExpansion->isActive()) {
            $this->wickednessTiles->onResolvingDieSymbol(new Context($this, currentPlayerId: $playerId, dieSymbol: 4, dieCount: $diceCount));
        }
    }

    function resolveEnergyDice(int $playerId, int $diceCount) {
        $hungryFaceEvolutions = $this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, \HUNGRY_FACE_EVOLUTION, true, false);
        foreach ($hungryFaceEvolutions as $hungryFaceEvolution) {
            /** @disregard */
            $hungryFaceEvolution->applyEffect(new Context($this, $playerId));
        }

        if (!$this->canGainEnergy($playerId)) {
            return;
        }

        if ($this->powerUpExpansion->isActive()) {
            if ($this->countEvolutionOfType($playerId, CAT_NIP_EVOLUTION) > 0) {  
                $diceCount *= 2;
            }
        }
        
        $playerGettingEnergy = $this->getPlayerGettingEnergyOrHeart($playerId);

        $this->notify->all( "resolveEnergyDice", clienttranslate('${player_name} gains ${deltaEnergy} [Energy]'), [
            'playerId' => $playerGettingEnergy,
            'player_name' => $this->getPlayerNameById($playerGettingEnergy),
            'deltaEnergy' => $diceCount,
        ]);
        
        $this->applyGetEnergy($playerId, $diceCount, 0);

        if ($this->wickednessExpansion->isActive()) {
            $this->wickednessTiles->onResolvingDieSymbol(new Context($this, currentPlayerId: $playerId, dieSymbol: 5, dieCount: $diceCount));
        }
    }

    
    function resolveSmashDice(int $playerId, int $diceCount, array $playersSmashesWithReducedDamage) {
        $funnyLookingButDangerousDamages = $this->getGlobalVariable(FUNNY_LOOKING_BUT_DANGEROUS_DAMAGES, true);
        $flamingAuraDamages = $this->getGlobalVariable(FLAMING_AURA_DAMAGES, true);
        $exoticArmsDamages = [];
        $smashedPlayersIds = [];
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

        // Nova breath
        $countNovaBreath = $this->countCardOfType($playerId, NOVA_BREATH_CARD);

        $message = null;
        $inTokyo = $this->inTokyo($playerId);
        $nextStateId = ST_ENTER_TOKYO_APPLY_BURROWING;

        $damages = [];
        $activatedHunters = $this->mindbugExpansion->getActivatedCards($playerId, HUNTER);

        if ($countNovaBreath) {
            $message = clienttranslate('${player_name} smashes all other Monsters with ${number} [diceSmash]');
            $smashedPlayersIds = $this->getOtherPlayersIds($playerId);
        } else if (count($activatedHunters) > 0) {
            $message = clienttranslate('${player_name} targeted Monster ${player_name2} with ${number} [diceSmash]');
            $smashedPlayersIds = Arrays::map($activatedHunters, fn($activatedHunter) => $activatedHunter->activated->targetPlayerId);
        } else {
            $smashTokyo = !$inTokyo;
            $message = $smashTokyo ? 
                clienttranslate('${player_name} smashes Monsters in Tokyo with ${number} [diceSmash]') :
                clienttranslate('${player_name} smashes Monsters outside Tokyo with ${number} [diceSmash]');
            $smashedPlayersIds = $this->getPlayersIdsFromLocation($smashTokyo);
        }

        $electricCarrot = false;

        if ($isPowerUpExpansion) {

            // Electric Carrot
            $electricCarrotEvolutions = $inTokyo && count($smashedPlayersIds) > 0 ? $this->powerUpExpansion->evolutionCards->getPlayerVirtualByType($playerId, ELECTRIC_CARROT_EVOLUTION, true, false) : [];
            if (count($electricCarrotEvolutions) > 0 && $this->getGlobalVariable(ELECTRIC_CARROT_CHOICES, true) === null) {
                /** @disregard */
                $electricCarrotEvolutions[0]->electricCarrotQuestion(new Context($this), $smashedPlayersIds);
                return;
            }

            $exoticArmsEvolutions = $this->getEvolutionsOfType($playerId, EXOTIC_ARMS_EVOLUTION);
            $usedExoticArms = array_values(array_filter($exoticArmsEvolutions, fn($evolution) => $evolution->tokens > 0));
            $countExoticArms = count($usedExoticArms);
            if ($countExoticArms > 0) {

                $dice = $this->getPlayerRolledDice($playerId, true, false, false);
                $rolledFaces = $this->getRolledDiceFaces($playerId, $dice, true);

                $extraDamage = $rolledFaces[61] >= 3;

                foreach ($usedExoticArms as $exoticArmsEvolution) {
                    $this->setEvolutionTokens($playerId, $exoticArmsEvolution, 0);
                }

                if ($extraDamage) {
                    $this->applyGetEnergy($playerId, 2 * $countExoticArms, 0);
                    foreach ($smashedPlayersIds as $smashedPlayerId) {
                        $exoticArmsDamages[$smashedPlayerId] = 2 * $countExoticArms;
                    }
                } else {
                    $exoticArmsDamages[$playerId] = 2 * $countExoticArms;
                }
            }
        }

        if ($diceCount > 0) {
            // only here (not in ResolveDice state) so player can heal and then activate Berserk
            if ($this->cybertoothExpansion->isActive()) {
                $this->cybertoothExpansion->onPlayerResolveSmashDice($playerId, $diceCount);
            }
            
            if ($isPowerUpExpansion) {
                if ($this->countEvolutionOfType($playerId, CAT_NIP_EVOLUTION) > 0) {  
                    $diceCount *= 2;
                }

                $underratedEvolutions = $this->getEvolutionsOfType($playerId, UNDERRATED_EVOLUTION);
                $diceCount += count($underratedEvolutions) * 2;

                if (count($underratedEvolutions) > 0) {
                    $this->removeEvolutions($playerId, $underratedEvolutions);
                }
            }

            $addSmashTokens = new AddSmashTokens(
                shrinkRay: $this->countCardOfType($playerId, SHRINK_RAY_CARD), // Shrink Ray
                poison: $this->countCardOfType($playerId, POISON_SPIT_CARD), // Poison Spit
            );

            if ($this->wickednessExpansion->isActive()) {
                $addSmashTokens->add(
                    $this->wickednessTiles->onAddingSmashTokens(new Context($this, currentPlayerId: $playerId))
                );
            }
            
            $playerScore = $this->getPlayerScore($playerId);

            $fireBreathingDamages = $this->getGlobalVariable(FIRE_BREATHING_DAMAGES, true);

            $electricCarrotChoices = $electricCarrot ? $this->getGlobalVariable(ELECTRIC_CARROT_CHOICES, true) : [];
            if ($electricCarrotChoices !== null) {
                $this->deleteGlobalVariable(ELECTRIC_CARROT_CHOICES);
            }

            $jetsDamages = [];
            $smashedPlayersInTokyo = [];
            foreach($smashedPlayersIds as $smashedPlayerId) {
                $smashedPlayerIsInTokyo = $this->inTokyo($smashedPlayerId);

                $damageAmount = $diceCount;

                $fireBreathingDamage = array_key_exists($smashedPlayerId, $fireBreathingDamages) ? $fireBreathingDamages[$smashedPlayerId] : 0;
                $damageAmount += $fireBreathingDamage;
                $funnyLookingButDangerousDamage = array_key_exists($smashedPlayerId, $funnyLookingButDangerousDamages) ? $funnyLookingButDangerousDamages[$smashedPlayerId] : 0;
                $damageAmount += $funnyLookingButDangerousDamage;
                $flamingAuraDamage = array_key_exists($smashedPlayerId, $flamingAuraDamages) ? $flamingAuraDamages[$smashedPlayerId] : 0;
                $damageAmount += $flamingAuraDamage;
                $exoticArmsDamage = array_key_exists($smashedPlayerId, $exoticArmsDamages) ? $exoticArmsDamages[$smashedPlayerId] : 0;
                $damageAmount += $exoticArmsDamage;

                if (array_key_exists($smashedPlayerId, $playersSmashesWithReducedDamage)) {
                    $damageAmount -= $playersSmashesWithReducedDamage[$smashedPlayerId];
                }
                if ($damageAmount <= 0) {
                    continue;
                }

                if ($smashedPlayerIsInTokyo) {
                    $smashedPlayersInTokyo[] = $smashedPlayerId;
                }

                // Jets
                $countJets = $this->countCardOfType($smashedPlayerId, JETS_CARD);
                $countSimianScamper = 0;
                if ($isPowerUpExpansion) {
                    $countSimianScamper = $this->countEvolutionOfType($smashedPlayerId, SIMIAN_SCAMPER_EVOLUTION, true, true);
                }

                $clawDamage = new ClawDamage($playerScore, $addSmashTokens->shrinkRay, $addSmashTokens->poison, $electricCarrotChoices);
                $newDamage = new Damage($smashedPlayerId, $damageAmount, $playerId, 0, $clawDamage);
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

            $this->notify->all("resolveSmashDice", $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'player_name2' => count($activatedHunters) > 0 ? implode(', ', Arrays::map($smashedPlayersIds, fn($smashedPlayerId) => $this->getPlayerNameById($smashedPlayerId))) : null,
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
                $this->notify->all("log", clienttranslate('${player_name} loses ${number} extra [Heart] with ${card_name}'), [
                    'playerId' => $damagePlayerId,
                    'player_name' => $this->getPlayerNameById($damagePlayerId),
                    'number' => $fireBreathingDamage,
                    'card_name' => FIRE_BREATHING_CARD,
                ]);

                // we add damage only if it's not already counted in smashed players (without tokens)
                if (!in_array($damagePlayerId, $smashedPlayersIds)) {
                    $damages[] = new Damage($damagePlayerId, $fireBreathingDamage, $playerId, -FIRE_BREATHING_CARD);
                }
            }
        } else {
            $this->setGameStateValue(STATE_AFTER_RESOLVE, ST_ENTER_TOKYO_APPLY_BURROWING);
            $smashedPlayersIds = [];
        }

        // funny looking but dangerous
        foreach ($funnyLookingButDangerousDamages as $damagePlayerId => $funnyLookingButDangerousDamage) {
            $this->notify->all("log", clienttranslate('${player_name} loses ${number} extra [Heart] with ${card_name}'), [
                'playerId' => $damagePlayerId,
                'player_name' => $this->getPlayerNameById($damagePlayerId),
                'number' => $funnyLookingButDangerousDamage,
                'card_name' => 3000 + FUNNY_LOOKING_BUT_DANGEROUS_EVOLUTION,
            ]);

            // we add damage only if it's not already counted in smashed players (without tokens)
            if (!in_array($damagePlayerId, $smashedPlayersIds)) {
                $damages[] = new Damage($damagePlayerId, $funnyLookingButDangerousDamage, $playerId, -(3000 + FUNNY_LOOKING_BUT_DANGEROUS_EVOLUTION));
            }
        }

        // flaming aura
        foreach ($flamingAuraDamages as $damagePlayerId => $flamingAuraDamage) {
            $this->notify->all("log", clienttranslate('${player_name} loses ${number} extra [Heart] with ${card_name}'), [
                'playerId' => $damagePlayerId,
                'player_name' => $this->getPlayerNameById($damagePlayerId),
                'number' => $flamingAuraDamage,
                'card_name' => FLAMING_AURA_CARD,
            ]);

            // we add damage only if it's not already counted in smashed players (without tokens)
            if (!in_array($damagePlayerId, $smashedPlayersIds)) {
                $damages[] = new Damage($damagePlayerId, $flamingAuraDamage, $playerId, -FLAMING_AURA_CARD);
            }
        }

        // exotic arms
        foreach ($exoticArmsDamages as $damagePlayerId => $exoticArmsDamage) {
            $this->notify->all("log", clienttranslate('${player_name} loses ${number} extra [Heart] with ${card_name}'), [
                'playerId' => $damagePlayerId,
                'player_name' => $this->getPlayerNameById($damagePlayerId),
                'number' => $exoticArmsDamage,
                'card_name' => 3000 + EXOTIC_ARMS_EVOLUTION,
            ]);

            // we add damage only if it's not already counted in smashed players (without tokens)
            if (!in_array($damagePlayerId, $smashedPlayersIds)) {
                $damages[] = new Damage($damagePlayerId, $exoticArmsDamage, $playerId, -(3000 + EXOTIC_ARMS_EVOLUTION));
            }
        }

        $this->goToState(ST_RESOLVE_SKULL_DICE, $damages);
    }

    function getChangeDieCards(int $playerId) {
        $dice = $this->getPlayerRolledDice($playerId, true, false, false); 
        $diceCounts = $this->getRolledDiceCounts($playerId, $dice, true);
        
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
        $potentialEnergy = $this->getPlayerPotentialEnergy($playerId);
        $hasStretchy = $this->countCardOfType($playerId, STRETCHY_CARD) > 0 && $potentialEnergy >= 2;

        // Biofuel
        $hasBiofuel = $this->countCardOfType($playerId, BIOFUEL_CARD) > 0 && $diceCounts[4] >= 1 && !$this->inTokyo($playerId);

        // Biofuel
        $hasShrinky = $this->countCardOfType($playerId, SHRINKY_CARD) > 0 && $diceCounts[2] >= 1;

        $hasClown = boolval($this->getGameStateValue(CLOWN_ACTIVATED));
        // Clown
        if (!$hasClown && $this->countCardOfType($playerId, CLOWN_CARD) > 0) {
            $detail = $this->addSmashesFromCards($playerId, $diceCounts, $this->inTokyo($playerId));

            $rolledFaces = $this->getRolledDiceFaces($playerId, $dice, true);
            if (
                $rolledFaces[11] >= 1 && 
                $rolledFaces[21] >= 1 && 
                $rolledFaces[31] >= 1 && 
                $rolledFaces[41] >= 1 && 
                $rolledFaces[51] >= 1 && 
                ($rolledFaces[61] >= 1 || $detail->addedSmashes > 0)
            ) { 
                $this->setGameStateValue(CLOWN_ACTIVATED, 1);
                $hasClown = true;
            }
        }
        // Sneaky Alloy
        $hasSneakyAlloy = false;
        $sneakyAlloyCards = $this->getCardsOfType($playerId, SNEAKY_ALLOY_CARD);
        if (count($sneakyAlloyCards) > 0) {
            $usedCards = $this->getUsedCard();
            $availableSneakyAlloys = array_values(array_filter($sneakyAlloyCards, fn($card) => in_array($card->id, $usedCards))); // using "used card" in reverse
            $hasSneakyAlloy = count($availableSneakyAlloys) > 0;
        }

        $isPowerUpExpansion = $this->powerUpExpansion->isActive();
        $playerEvolutions = [];
        // Gamma Breath & Tail Sweep
        $gammaBreathCardIds = [];
        $hasTailSweep = false;
        $hasTinyTail = false;
        $hasEnergyDevourer = false;
        $hasSaurianAdaptability = false;
        $hasYinYang = false;
        $energyInfusedMonster = null;
        if ($isPowerUpExpansion) {
            $playerEvolutions = $this->powerUpExpansion->evolutionCards->getPlayerVirtual($playerId, true, true, true);
            $usedCards = $this->getUsedCard();
            $gammaBreathCards = Arrays::filter($playerEvolutions, fn($evolution) => $evolution->type === GAMMA_BREATH_EVOLUTION && !in_array(3000 + $evolution->id, $usedCards));
            $gammaBreathCardIds = Arrays::pluck($gammaBreathCards, 'id');

            $tailSweepCards = Arrays::filter($playerEvolutions, fn($evolution) => $evolution->type === TAIL_SWEEP_EVOLUTION);
            if (count($tailSweepCards) > 0) {
                $usedCards = $this->getUsedCard();
                $availableTailSweep = array_values(array_filter($tailSweepCards, fn($tailSweepCard) => !in_array(3000 + $tailSweepCard->id, $usedCards)));
                $hasTailSweep = count($availableTailSweep) > 0;
            }

            $tinyTailCards = Arrays::filter($playerEvolutions, fn($evolution) => $evolution->type === TINY_TAIL_EVOLUTION);
            if (count($tinyTailCards) > 0) {
                $usedCards = $this->getUsedCard();
                $availableTinyTail = array_values(array_filter($tinyTailCards, fn($tinyTailCard) => $this->countUsedCard(3000 + $tinyTailCard->id) < 2));
                $hasTinyTail = count($availableTinyTail) > 0;
            }

            $energyDevourerCards = Arrays::filter($playerEvolutions, fn($evolution) => $evolution->type === ENERGY_DEVOURER_EVOLUTION);
            if ($potentialEnergy >= 1 && count($energyDevourerCards) > 0) {
                $usedCards = $this->getUsedCard();
                $availableEnergyDevourer = array_values(array_filter($energyDevourerCards, fn($energyDevourerCard) => $this->countUsedCard(3000 + $energyDevourerCard->id) < 1));
                $hasEnergyDevourer = count($availableEnergyDevourer) > 0;
            }

            // Saurian Adaptability
            $hasSaurianAdaptability = $this->countEvolutionOfType($playerId, SAURIAN_ADAPTABILITY_EVOLUTION, false, true) > 0;

            // yin & yang
            $hasYinYang = $this->countEvolutionOfType($playerId, YIN_YANG_EVOLUTION) > 0;

            // energy-infused monster
            $energyInfusedMonster = $this->getFirstUnusedEvolution($playerId, ENERGY_INFUSED_MONSTER_EVOLUTION);
        }
        
        return [
            'hasHerdCuller' => $hasHerdCuller,
            'hasPlotTwist' => $hasPlotTwist,
            'hasStretchy' => $hasStretchy,
            'hasClown' => $hasClown,
            'hasSneakyAlloy' => $hasSneakyAlloy,
            'hasSaurianAdaptability' => $hasSaurianAdaptability,
            'gammaBreathCardIds' => $gammaBreathCardIds,
            'hasTailSweep' => $hasTailSweep,
            'hasTinyTail' => $hasTinyTail,
            'hasEnergyDevourer' => $hasEnergyDevourer,
            'hasYinYang' => $hasYinYang,
            'hasBiofuel' => $hasBiofuel,
            'hasShrinky' => $hasShrinky,
            'hasEnergyInfusedMonster' => $energyInfusedMonster?->id,
        ];
    }

    function canChangeDie(array $cards) {
        return $cards['hasHerdCuller'] 
            || $cards['hasPlotTwist'] 
            || $cards['hasStretchy'] 
            || $cards['hasClown'] 
            || $cards['hasSneakyAlloy'] 
            || $cards['hasSaurianAdaptability'] 
            || count($cards['gammaBreathCardIds']) > 0
            || $cards['hasTailSweep'] 
            || $cards['hasTinyTail'] 
            || $cards['hasEnergyDevourer'] 
            || $cards['hasYinYang'] 
            || $cards['hasBiofuel'] 
            || $cards['hasShrinky'] 
            || $cards['hasEnergyInfusedMonster'];
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

    function getPlayersWithHeartOfTheRabbit(int $playerId) {
        $orderedPlayers = $this->getOrderedPlayers($playerId);
        $heartOfTheRabbitPlayerIds = [];

        foreach($orderedPlayers as $player) {
            if ($player->id != $playerId) {

                $heartOfTheRabbitEvolutions = $this->getEvolutionsOfType($player->id, HEART_OF_THE_RABBIT_EVOLUTION, false, true);
                $unusedHeartOfTheRabbitEvolutions = 0;
                $usedCards = $this->getUsedCard();
                foreach($heartOfTheRabbitEvolutions as $heartOfTheRabbitEvolution) {
                    if (!in_array($heartOfTheRabbitEvolution->id, $usedCards)) {
                        $unusedHeartOfTheRabbitEvolutions++;
                    }
                }
                if ($unusedHeartOfTheRabbitEvolutions > 0) {
                    $heartOfTheRabbitPlayerIds[] = $player->id;
                }            
            }
        }

        return $heartOfTheRabbitPlayerIds;
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

    function getChangeActivePlayerDieIntervention(int $playerId) { // return null or ChangeActivePlayerDieIntervention
        $playersWithPsychicProbe = $this->getPlayersWithPsychicProbe($playerId);
        $playersWithHeartOfTheRabbit = $this->getPlayersWithHeartOfTheRabbit($playerId);
        $playersWithActivatedWitch = [];
        
        $witchCards = $this->powerCards->getCardsOfType(WITCH_CARD);
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

        $playersWithChangeActivePlayerDieCard = array_unique(array_merge($playersWithPsychicProbe, $playersWithHeartOfTheRabbit, $playersWithActivatedWitch), SORT_REGULAR);
        if (count($playersWithChangeActivePlayerDieCard) > 0) {
            $cards = [];
            foreach ($playersWithPsychicProbe as $playerWithPsychicProbe) {
                $cards = array_merge($cards, $this->getCardsOfType($playerWithPsychicProbe, PSYCHIC_PROBE_CARD));
            }
            foreach ($playersWithActivatedWitch as $playerWithActivatedWitch) {
                $cards = array_merge($cards, $this->getCardsOfType($playerWithActivatedWitch, WITCH_CARD));
            }
            foreach ($playersWithHeartOfTheRabbit as $playerWithHeartOfTheRabbit) {
                $evolutions = $this->getEvolutionsOfType($playerWithHeartOfTheRabbit, HEART_OF_THE_RABBIT_EVOLUTION, false, true);
                $cards = array_merge($cards, $evolutions);
            }
            $changeActivePlayerDieIntervention = new ChangeActivePlayerDieIntervention($playersWithChangeActivePlayerDieCard, $playerId, $cards);
            return $changeActivePlayerDieIntervention;
        }
        return null;
    }

    function addSmashesFromCards(int $playerId, array $diceCounts, bool $playerInTokyo) {
        $addedSmashes = 0;
        $cardsAddingSmashes = [];
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

        // cheerleader
        if (intval($this->getGameStateValue(CHEERLEADER_SUPPORT)) == 1) {
            $addedSmashes += 1;
            $cardsAddingSmashes[] = CHEERLEADER_CARD;
        }

        // no brain
        $countNoBrain = $this->countCardOfType($playerId, NO_BRAIN_CARD);
        if ($countNoBrain > 0) {
            $addedSmashes += $countNoBrain;

            for ($i=0; $i<$countNoBrain; $i++) { $cardsAddingSmashes[] = NO_BRAIN_CARD; }
        }

        // Jet club
        if ($isPowerUpExpansion && $playerInTokyo) {
            $jetClubEvolutions = $this->getEvolutionsOfType($playerId, JET_CLUB_EVOLUTION);
            foreach($jetClubEvolutions as $jetClubEvolution) {
                $addedSmashes += 1;
                    
                $cardsAddingSmashes[] = 3000 + JET_CLUB_EVOLUTION;
            }
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

        $scytheEvolutions = $this->getEvolutionsOfType($playerId, SCYTHE_EVOLUTION);
        foreach($scytheEvolutions as $scytheEvolution) {
            $addedSmashes += $scytheEvolution->tokens;
                
            $cardsAddingSmashes[] = 3000 + SCYTHE_EVOLUTION;
        }

        // Meow Missle
        if ($diceCounts[1] >= 1 && $isPowerUpExpansion) {
            $countMeowMissle = $this->countEvolutionOfType($playerId, MEOW_MISSLE_EVOLUTION);
            if ($countMeowMissle > 0) {
                $addedSmashes += $countMeowMissle;
                
                for ($i=0; $i<$countMeowMissle; $i++) { $cardsAddingSmashes[] = 3000 + MEOW_MISSLE_EVOLUTION; }
            }
        }

        // Energy Sword
        if ($diceCounts[6] >= 1 && $isPowerUpExpansion) {
            $energySwordEvolutions = $this->getEvolutionsOfType($playerId, ENERGY_SWORD_EVOLUTION);
            $countEnergySword = count(array_filter($energySwordEvolutions, fn($evolution) => $evolution->tokens > 0));
            if ($countEnergySword > 0) {
                $addedSmashes += $countEnergySword;
                
                for ($i=0; $i<$countEnergySword; $i++) { $cardsAddingSmashes[] = 3000 + ENERGY_SWORD_EVOLUTION; }
            }
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

            // TODO migrate all of the above in their respective card class like AcidAttack/FollowTheCubes/Barbs
            [$addedByCards, $addingCards] = $this->powerCards->onAddSmashes(new Context(
                $this, 
                currentPlayerId: $playerId,
                dieSmashes: $diceCounts[6],
                addedSmashes: $addedSmashes,
            ));

            $addedSmashes += $addedByCards;
            $cardsAddingSmashes = array_merge($cardsAddingSmashes, $addingCards);

            if ($this->wickednessExpansion->isActive()) {
                [$addedByTiles, $addingTiles] = $this->wickednessTiles->onAddSmashes(new Context(
                    $this, 
                    currentPlayerId: $playerId,
                    dieSmashes: $diceCounts[6],
                    addedSmashes: $addedSmashes,
                ));

                $addedSmashes += $addedByTiles;
                $cardsAddingSmashes = array_merge($cardsAddingSmashes, $addingTiles);
            }

            if ($this->powerUpExpansion->isActive()) {
                [$addedByEvolutions, $addingEvolutions] = $this->powerUpExpansion->evolutionCards->onAddSmashes(new Context(
                    $this, 
                    currentPlayerId: $playerId,
                    dieSmashes: $diceCounts[6],
                    addedSmashes: $addedSmashes,
                ));

                $addedSmashes += $addedByEvolutions;
                $cardsAddingSmashes = array_merge($cardsAddingSmashes, $addingEvolutions);
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
        $heartOfTheRabbitEvolutions = $this->getEvolutionsOfType($playerId, HEART_OF_THE_RABBIT_EVOLUTION, false, true);
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
        // then heart of the rabbit
        foreach($heartOfTheRabbitEvolutions as $heartOfTheRabbitEvolution) {
            if (!in_array(3000 + $heartOfTheRabbitEvolution->id, $usedCards)) {
                $heartOfTheRabbitEvolution->id = 3000 + $heartOfTheRabbitEvolution->id;
                $heartOfTheRabbitEvolution->type = 3000 + $heartOfTheRabbitEvolution->type;
                $unusedCards[] = $heartOfTheRabbitEvolution;
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

    function getRolledDiceCounts(int $playerId, array $dice, $ignoreCanUseFace = true, $ignoreCanUseSymbol = true) {
        $diceCounts = [0,0,0,0,0,0,0,0];

        foreach($dice as $die) {
            if (
                ($die->type === 0 || $die->type === 1) && 
                ($ignoreCanUseFace || $die->type !== 0 || $this->canUseFace($playerId, $die->value)) &&
                ($ignoreCanUseSymbol || $this->canUseSymbol($playerId, floor(static::getDiceFaceType($die) / 10)))
            ) {
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

    function getRolledDiceFaces(int $playerId, array $dice, $ignoreCanUseFace = true) {
        $diceFaces = [];
        for ($symbolNumber=1; $symbolNumber<=2; $symbolNumber++) {
            for ($i=1; $i<=7; $i++) {
                $diceFaces[$i * 10 + $symbolNumber] = 0;
            }
        }

        foreach($dice as $die) {
            if (($die->type === 0 || $die->type === 1) && ($ignoreCanUseFace || $die->type !== 0 || $this->canUseFace($playerId, $die->value))) {
                if ($die->type === 0) {                
                    $diceFaces[10 * $die->value + 1] += 1;
                } else if ($die->type === 1) {
                    switch($die->value) {
                        case 1: 
                            $diceFaces[51] += 1;
                            break;
                        case 2: 
                            $diceFaces[52] += 1;
                            break;
                        case 3: case 4: 
                            $diceFaces[61] += 1;
                            break;
                        case 5: 
                            $diceFaces[62] += 1;
                            break;
                        case 6: 
                            $diceFaces[71] = 1;
                            break;
                    }
                }
            }
        }

        return $diceFaces;
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

    function canUsePlayWithYourFood(int $playerId, array $diceCounts) { // players concerned by the effect, null if can't be used
        if ($diceCounts[6] >= 2 && $this->powerUpExpansion->isActive() && $this->countEvolutionOfType($playerId, PLAY_WITH_YOUR_FOOD_EVOLUTION) > 0) {
            $otherPlayersIds = $this->getPlayersIds();
            $willBeWoundedIds = [];
            foreach ($otherPlayersIds as $otherPlayerId) {
                if ($this->inTokyo($otherPlayerId) && $this->willBeWounded($otherPlayerId, $playerId)) {
                    $willBeWoundedIds[] = $otherPlayerId;
                }
            }

            if (count($willBeWoundedIds) > 0) {
                return $willBeWoundedIds;
            } else {
                return null;
            }
        }

        return null;
    }
    
    public function startTurnInitDice() {
        $this->setGameStateValue('throwNumber', 1);

        $encaseInIceDieId = intval($this->getGameStateValue(ENCASED_IN_ICE_DIE_ID));
        $this->DbQuery("UPDATE dice SET `dice_value` = 0, `locked` = false, `rolled` = true, `discarded` = false WHERE `dice_id` <> $encaseInIceDieId");
    }
}
