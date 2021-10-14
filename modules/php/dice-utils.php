<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/player-intervention.php');
require_once(__DIR__.'/objects/damage.php');

use KOT\Objects\Dice;
use KOT\Objects\PsychicProbeIntervention;
use KOT\Objects\Damage;

trait DiceUtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////
    
    function getDice(int $number) {
        $sql = "SELECT `dice_id`, `dice_value`, `extra`, `locked`, `rolled` FROM dice ORDER BY dice_id limit $number";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices));
    }

    function getDieById(int $id) {
        $sql = "SELECT `dice_id`, `dice_value`, `extra`, `locked`, `rolled` FROM dice WHERE `dice_id` = $id";
        $dbDices = self::getCollectionFromDB($sql);
        return array_map(function($dbDice) { return new Dice($dbDice); }, array_values($dbDices))[0];
    }

    function getFirst3Dice(int $number) {
        $dice = $this->getDice($number);
        foreach ($dice as $dice) {
            if ($dice->value === 3) {
                return $dice;
            }
        }
        return null;
    }

    public function throwDice(int $playerId, bool $firstRoll) {
        $dice = $this->getDice($this->getDiceNumber($playerId));

        self::DbQuery( "UPDATE dice SET `rolled` = false");

        $rolledDiceStr = '';
        $lockedDiceStr = '';
        $lockedDice = [];
        
        foreach ($dice as &$dice) {
            if ($dice->locked) {
                $lockedDice[] = $dice->value;
            } else {
                $dice->value = bga_rand(1, 6);
                self::DbQuery( "UPDATE dice SET `dice_value` = ".$dice->value.", `rolled` = true where `dice_id` = ".$dice->id );
                $rolledDiceStr .= $this->getDieFaceLogName($dice->value);
            }
        }

        if (!$this->getPlayer($playerId)->eliminated) {
            $message = null;
            if ($firstRoll) {
                $message = clienttranslate('${player_name} rolls dice ${rolledDice}');
            } else if (count($lockedDice) == 0) {
                $message = clienttranslate('${player_name} rerolls dice ${rolledDice}');
            } else {
                sort($lockedDice);
                foreach ($lockedDice as $lockedDie) {
                    $lockedDiceStr .= $this->getDieFaceLogName($lockedDie);
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

        return max(6 + $this->countExtraHead($playerId) - $remove, 0);
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
        }
    }

    function resolveHealthDice(int $playerId, int $diceCount) {
        if ($this->inTokyo($playerId)) {
            self::notifyAllPlayers( "resolveHealthDiceInTokyo", clienttranslate('${player_name} gains no [Heart] (player in Tokyo)'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
            ]);
        } else {
            $health = $this->getPlayerHealth($playerId);
            $maxHealth = $this->getPlayerMaxHealth($playerId);
            if ($health < $maxHealth) {
                $this->applyGetHealth($playerId, $diceCount, -1);
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

    
    function resolveSmashDice(int $playerId, int $diceCount) { // return nextState / null
        // Nova breath
        $countNovaBreath = $this->countCardOfType($playerId, NOVA_BREATH_CARD);

        $message = null;
        $smashedPlayersIds = null;
        $inTokyo = $this->inTokyo($playerId);
        $nextState = "enterTokyo";

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
        $giveShrinkRayToken = $this->countCardOfType($playerId, SHRINK_RAY_CARD) > 0;
        // Poison Spit
        $givePoisonSpitToken = $this->countCardOfType($playerId, POISON_SPIT_CARD) > 0;

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
                $jetsDamages[] = new Damage($smashedPlayerId, $damageAmount, $playerId, 0, false, $giveShrinkRayToken, $givePoisonSpitToken);
            } else {
                $damages[] = new Damage($smashedPlayerId, $damageAmount, $playerId, 0, false, $giveShrinkRayToken, $givePoisonSpitToken);
            }
        }

        if (count($smashedPlayersInTokyo) > 0) {
            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, $smashedPlayersInTokyo);
            $nextState = "smashes";
        } else {
            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, []);
        }

        $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);      

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

            // we add damage only if it's not already counted in smashed players
            if (array_search($damagePlayerId, $smashedPlayersIds) === false) {
                $damages[] = new Damage($damagePlayerId, $fireBreathingDamage, $damagePlayerId, 0, false, $giveShrinkRayToken, $givePoisonSpitToken);
            }
        }

        if (count($damages) > 0) {
            if ($this->resolveDamages($damages, $nextState)) {
                return null; // no redirect on stResolveSmashDice, handled by resolveDamages
            }
        }
        return $nextState;
    }

    function getChangeDieCards(int $playerId) {
        // Herd Culler
        $herdCullerCards = $this->getCardsOfType($playerId, HERD_CULLER_CARD);
        $availableHerdCullers = 0;
        $herdCullerCount = count($herdCullerCards);
        if ($herdCullerCount > 0) {
            $usedCards = $this->getUsedCard();
            foreach ($herdCullerCards as $herdCullerCard) {
                if (array_search($herdCullerCard->id, $usedCards) === false) {
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
            $dice = $this->getDice($this->getDiceNumber($playerId));
            $diceValues = array_map(function($idie) { return $idie->value; }, $dice);
            $diceCounts = [];
            for ($diceFace = 1; $diceFace <= 6; $diceFace++) {
                $diceCounts[$diceFace] = count(array_values(array_filter($diceValues, function($dice) use ($diceFace) { return $dice == $diceFace; })));
            }
            
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
                    if (array_search($psychicProbeCard->id, $usedCards) === false) {
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

    function getDieFaceLogName(int $number) {
        switch($number) {
            case 1: case 2: case 3: return "[dice$number]";
            case 4: return "[diceHeart]";
            case 5: return "[diceEnergy]";
            case 6: return "[diceSmash]";
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

    function getPsychicProbeIntervention(int $playerId) { // rturn null or PsychicProbeIntervention
        $playersWithPsychicProbe = $this->getPlayersWithPsychicProbe($playerId);

        if (count($playersWithPsychicProbe) > 0) {
            $cards = [];
            foreach ($playersWithPsychicProbe as $playerWithPsychicProbe) {
                $cards = array_merge($cards, $this->getCardsOfType($playerWithPsychicProbe, PSYCHIC_PROBE_CARD));
            }
            $psychicProbeIntervention = new PsychicProbeIntervention($playersWithPsychicProbe, $playerId, $cards);
            return $psychicProbeIntervention;
        }
        return null;
    }

}