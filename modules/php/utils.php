<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player.php');
require_once(__DIR__.'/objects/player-intervention.php');

use KOT\Objects\Player;
use KOT\Objects\CancelDamageIntervention;

trait UtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function array_find(array $array, callable $fn) {
        foreach ($array as $value) {
            if($fn($value)) {
                return $value;
            }
        }
        return null;
    }

    function array_some(array $array, callable $fn) {
        foreach ($array as $value) {
            if($fn($value)) {
                return true;
            }
        }
        return false;
    }
        
    function array_every(array $array, callable $fn) {
        foreach ($array as $value) {
            if(!$fn($value)) {
                return false;
            }
        }
        return true;
    }

    function isTurnBased() {
        return intval($this->gamestate->table_globals[200]) >= 10;
    }

    function isTwoPlayersVariant() {
        return intval($this->getGameStateValue(TWO_PLAYERS_VARIANT_OPTION)) === 2 && $this->getPlayersNumber() == 2;
    }

    function isHalloweenExpansion() {
        return intval($this->getGameStateValue(HALLOWEEN_EXPANSION_OPTION)) === 2;
    }

    function isKingKongExpansion() {
        return intval($this->getGameStateValue(KINGKONG_EXPANSION_OPTION)) === 2;
    }

    function isCybertoothExpansion() {
        return intval($this->getGameStateValue(CYBERTOOTH_EXPANSION_OPTION)) === 2;
    }

    function isMutantEvolutionVariant() {
        return intval($this->getGameStateValue(MUTANT_EVOLUTION_VARIANT_OPTION)) === 2;
    }

    function isCthulhuExpansion() {
        return intval($this->getGameStateValue(CTHULHU_EXPANSION_OPTION)) === 2;
    }

    function isAnubisExpansion() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval($this->getGameStateValue(ANUBIS_EXPANSION_OPTION)) === 2;
    }

    function isWickednessExpansion() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval($this->getGameStateValue(WICKEDNESS_EXPANSION_OPTION)) > 1 || $this->isDarkEdition();
    }

    function isPowerUpExpansion() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval($this->getGameStateValue(POWERUP_EXPANSION_OPTION)) >= 2;
    }

    function isPowerUpMutantEvolution() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval($this->getGameStateValue(POWERUP_EXPANSION_OPTION)) === 3;
    }

    function isDarkEdition() {
        return /*$this->getBgaEnvironment() == 'studio' ||*/ intval($this->getGameStateValue(DARK_EDITION_OPTION)) > 1;
    }

    function releaseDatePassed(string $activationDateStr, int $hourShift) { // 1 for paris winter time, 2 for paris summer time
        $currentdate = new \DateTimeImmutable();
        $activationdate = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $activationDateStr.'+00:00')->sub(new \DateInterval("PT${hourShift}H")); // "2021-12-30T21:41:00+00:00"
        $diff = $currentdate->diff($activationdate);
        return $diff->invert;
    }

    function autoSkipImpossibleActions() {
        return $this->isTurnBased() || intval($this->getGameStateValue(AUTO_SKIP_OPTION)) === 2;
    }

    function setGlobalVariable(string $name, /*object|array*/ $obj) {
        /*if ($obj == null) {
            throw new \Error('Global Variable null');
        }*/
        $jsonObj = json_encode($obj);
        $this->DbQuery("INSERT INTO `global_variables`(`name`, `value`)  VALUES ('$name', '$jsonObj') ON DUPLICATE KEY UPDATE `value` = '$jsonObj'");
    }

    function getGlobalVariable(string $name, $asArray = null) {
        $json_obj = $this->getUniqueValueFromDB("SELECT `value` FROM `global_variables` where `name` = '$name'");
        if ($json_obj) {
            $object = json_decode($json_obj, $asArray);
            return $object;
        } else {
            return null;
        }
    }

    function isUsedCard(int $cardId) {
        $cardsIds = $this->getUsedCard();
        return in_array($cardId, $cardsIds);
    }

    function countUsedCard(int $cardId) {
        $cardsIds = $this->getUsedCard();
        return count(array_filter($cardsIds, fn($id) => $id == $cardId));
    }

    function setUsedCard(int $cardId) {
        $cardsIds = $this->getUsedCard();
        $cardsIds[] = $cardId;
        $this->setGlobalVariable(USED_CARDS, $cardsIds);
    }

    function getUsedCard() {
        return $this->getGlobalVariable(USED_CARDS, true);
    }

    function resetUsedCards() {
        $this->setGlobalVariable(USED_CARDS, []);
    }

    function deleteGlobalVariable(string $name) {
        $this->DbQuery("DELETE FROM `global_variables` where `name` = '$name'");
    }

    function getMaxPlayerScore() {
        return intval($this->getUniqueValueFromDB("SELECT max(player_score) FROM player"));
    }

    function getPlayerName(int $playerId) {
        return $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $playerId");
    }

    function getPlayerScore(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId"));
    }

    function getPlayerHealth(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_health FROM player where `player_id` = $playerId"));
    }

    function getPlayerEnergy(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_energy FROM player where `player_id` = $playerId"));
    }

    function getPlayerPoisonTokens(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_poison_tokens FROM player where `player_id` = $playerId"));
    }

    function getPlayerShrinkRayTokens(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_shrink_ray_tokens FROM player where `player_id` = $playerId"));
    }

    function getRollNumber(int $playerId) {
        if ($this->isPowerUpExpansion()) {
            $blizzardOwner = $this->isEvolutionOnTable(BLIZZARD_EVOLUTION);
            if ($blizzardOwner != null && $blizzardOwner != $playerId) {
                return 1;
            }

            if ($this->countEvolutionOfType($playerId, CAT_NIP_EVOLUTION) > 0) {  
                return 1;
            }
        }

        // giant brain
        $countGiantBrain = $this->countCardOfType($playerId, GIANT_BRAIN_CARD);
        // statue of libery
        $countStatueOfLiberty = $this->countCardOfType($playerId, STATUE_OF_LIBERTY_CARD);
        // energy drink
        $extraRolls = intval($this->getGameStateValue(EXTRA_ROLLS));
        $deviousTile = ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, DEVIOUS_WICKEDNESS_TILE)) ? 1 : 0;

        $removedDieByBuriedInSand = false;
        $falseBlessing = 0;
        if ($this->isAnubisExpansion()) {
            $cardType = $this->getCurseCardType();

            if ($cardType == BURIED_IN_SAND_CURSE_CARD) {
                $dieOfFate = $this->getDieOfFate();

                if ($dieOfFate->value != 4) {
                    $removedDieByBuriedInSand = true;
                }
            }

            if ($cardType == FALSE_BLESSING_CURSE_CARD) {
                $falseBlessing = 1;
            }
        }

        $rollNumber = 3 + $countGiantBrain + $countStatueOfLiberty + $extraRolls + $deviousTile + $falseBlessing;
        if ($rollNumber > 1 && $removedDieByBuriedInSand) {
            $rollNumber--;
        }
        return $rollNumber;
    }

    function getPlayerMaxHealth(int $playerId) {
        $add = 0;
        $remove = 0;
        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, FULL_REGENERATION_WICKEDNESS_TILE)) {
            $add += 2;
        }
        
        $add += 2 * $this->countCardOfType($playerId, EVEN_BIGGER_CARD);

        if ($this->isZombified($playerId)) {
            $add += 2;
        }
        if ($this->isPowerUpExpansion()) {
            $add += 2 * $this->countEvolutionOfType($playerId, EATER_OF_SOULS_EVOLUTION);
        }

        if ($this->isAnubisExpansion() && $this->getCurseCardType() == BOW_BEFORE_RA_CURSE_CARD) {
            $remove += 2;
        }

        return min(12, 10 + $add - $remove);
    }

    function isZombified(int $playerId) {
        return $this->isDarkEdition() && boolval($this->getUniqueValueFromDB( "SELECT player_zombified FROM player WHERE player_id = $playerId"));
    }

    function getRemainingPlayers() {
        return intval($this->getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_eliminated = 0 AND player_dead = 0"));
    }

    function tokyoBayUsed() {
        return $this->getRemainingPlayers() > 4;
    }

    function isTokyoEmpty(bool $bay) {
        $location = $bay ? 2 : 1;
        $players = intval($this->getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_location = $location"));
        return $players == 0;
    }
        
    function tokyoHasFreeSpot() {
        return $this->isTokyoEmpty(false) || ($this->tokyoBayUsed() && $this->isTokyoEmpty(true));
    }


    function moveToTokyo(int $playerId, bool $bay) {
        if (!$this->canEnterTokyo($playerId)) {
            return;
        }

        $location = $bay ? 2 : 1;
        $message = null;
        $incEnergy = 0;
        $incScore = 0;
        $playerGettingEnergy = null;
        if ($this->isTwoPlayersVariant()) {
            $this->DbQuery("UPDATE player SET player_location = $location where `player_id` = $playerId");

            $playerGettingEnergy = $this->getPlayerGettingEnergyOrHeart($playerId);

            if ($this->canGainEnergy($playerGettingEnergy)) {
                $incEnergy = 1;
                $message = $playerId == $playerGettingEnergy ?
                    clienttranslate('${player_name} enters ${locationName} and gains 1 [Energy]') :
                    clienttranslate('${player_name} enters ${locationName}');
            } else {
                $message = clienttranslate('${player_name} enters ${locationName}');
            }
        } else {
            $this->DbQuery("UPDATE player SET player_location = $location where `player_id` = $playerId");
            if ($this->canGainPoints($playerId)) {
                $incScore = 1;
                $message = clienttranslate('${player_name} enters ${locationName} and gains 1 [Star]');
            } else {
                $message = clienttranslate('${player_name} enters ${locationName}');
            }
        }

        $locationName = $bay ? _('Tokyo Bay') : _('Tokyo City');
        $this->notifyAllPlayers("playerEntersTokyo", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'location' => $location,
            'locationName' => $locationName,
        ]);

        if ($incEnergy > 0) {
            $this->applyGetEnergy($playerGettingEnergy, $incEnergy, 0);
        }
        if ($incScore > 0) {
            $this->applyGetPoints($playerId, $incScore, 0);
        }

        $this->incStat(1, 'tokyoEnters', $playerId);

        if ($this->isWickednessExpansion() && $this->gotWickednessTile($playerId, DEFENDER_OF_TOKYO_WICKEDNESS_TILE)) {
            $this->applyDefenderOfTokyo($playerId, 2000 + DEFENDER_OF_TOKYO_WICKEDNESS_TILE, 1);
        }

        if ($this->isPowerUpExpansion()) {
            $countBlackDiamond = $this->countEvolutionOfType($playerId, BLACK_DIAMOND_EVOLUTION);
            if ($countBlackDiamond > 0) {
                $this->applyGetPoints($playerId, $countBlackDiamond, 3000 + BLACK_DIAMOND_EVOLUTION);
            }
            $countIAmTheKing = $this->countEvolutionOfType($playerId, I_AM_THE_KING_EVOLUTION);
            if ($countIAmTheKing) {
                $this->applyGetPoints($playerId, $countIAmTheKing, 3000 + I_AM_THE_KING_EVOLUTION);
            }
            $countEaterOfSouls = $this->countEvolutionOfType($playerId, EATER_OF_SOULS_EVOLUTION);
            if ($countEaterOfSouls) {
                $this->applyGetHealth($playerId, $countEaterOfSouls, 3000 + EATER_OF_SOULS_EVOLUTION, $playerId);
            }

            $evolutions = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsOfTypeInLocation(HEART_OF_THE_RABBIT_EVOLUTION, null, 'discard'.$playerId));
            if (count($evolutions) > 0) {
                foreach($evolutions as $evolution) {
                    $this->getEvolutionFromDiscard($playerId, $evolution->id);
                }
            }
        }
    }

    function moveToTokyoFreeSpot(int $playerId) {
        if ($this->isTokyoEmpty(false)) {
            $this->moveToTokyo($playerId, false);
        } else if ($this->tokyoBayUsed() && $this->isTokyoEmpty(true)) {
            $this->moveToTokyo($playerId, true);
        }
    }

    function leaveTokyo(int $playerId, /*int | null*/ $useCard = null) {

        $this->DbQuery("UPDATE player SET player_location = 0, `leave_tokyo_under` = null, `stay_tokyo_over` = null where `player_id` = $playerId");

        $this->notifyAllPlayers("leaveTokyo", clienttranslate('${player_name} leaves Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
        $this->notifyPlayer($playerId, 'updateLeaveTokyoUnder', '', [
            'under' => 0,
        ]);
        $this->notifyPlayer($playerId, 'updateStayTokyoOver', '', [
            'over' => 0,
        ]);

        $jetsDamages = $this->getGlobalVariable(JETS_DAMAGES);
        if ($jetsDamages != null && $useCard != null && in_array($useCard, [24, 3042])) {
            $jetsDamages = array_values(array_filter($jetsDamages, fn($damage) => $damage->playerId != $playerId));
            $this->setGlobalVariable(JETS_DAMAGES, $jetsDamages);

            if ($useCard == 3042) {
                $card = $this->getEvolutionsOfType($playerId, SIMIAN_SCAMPER_EVOLUTION, true, true)[0];
                $this->playEvolutionToTable($playerId, $card);
            }
        }

        $this->incStat(1, 'tokyoLeaves', $playerId);

        if ($this->isPowerUpExpansion()) {
            $twasBeautyKilledTheBeastCards = $this->getEvolutionsOfType($playerId, TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION);
            if (count($twasBeautyKilledTheBeastCards) > 0 && !$this->getPlayer($playerId)->eliminated) {
                $this->applyLeaveWithTwasBeautyKilledTheBeast($playerId, $twasBeautyKilledTheBeastCards);
            }
        }

        return true;
    }

    function moveFromTokyoBayToCity(int $playerId) {
        $location = 1;

        $this->DbQuery("UPDATE player SET player_location =  $location where `player_id` = $playerId");

        $locationName = _('Tokyo City');
        $this->notifyAllPlayers("playerEntersTokyo", clienttranslate('${player_name} enters ${locationName} !'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'location' => $location,
            'locationName' => $locationName,
        ]);
    }

    function inTokyo(int $playerId) {
        $location = intval($this->getUniqueValueFromDB( "SELECT player_location FROM player WHERE player_id = $playerId"));
        return $location > 0;
    }

    // get players ids

    private function getPlayersIdsFromLocation(bool $inside) {
        $sign = $inside ? '>' : '=';
        $sql = "SELECT player_id FROM player WHERE player_location $sign 0 AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
    }

    function getPlayerIdInTokyoCity() {
        $sql = "SELECT player_id FROM player WHERE player_location = 1 AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        return intval($this->getUniqueValueFromDB($sql));
    }

    function getPlayerIdInTokyoBay() {
        $sql = "SELECT player_id FROM player WHERE player_location = 2 AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        return intval($this->getUniqueValueFromDB($sql));
    }

    function getPlayersIdsInTokyo() {
        return $this->getPlayersIdsFromLocation(true);
    }

    function getPlayersIdsOutsideTokyo() {
        return $this->getPlayersIdsFromLocation(false);
    }

    function getPlayersIds(bool $includeEliminated = false) {        
        $sql = "SELECT player_id FROM player";
        if (!$includeEliminated) {
            $sql .= " WHERE player_eliminated = 0 AND player_dead = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
    }

    function getOtherPlayersIds(int $playerId) {
        $sql = "SELECT player_id FROM player WHERE player_id <> $playerId AND player_eliminated = 0 AND player_dead = 0 ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
    }  

    function getNonZombiePlayersIds() {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 AND player_dead = 0 AND player_zombie = 0 ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
    }

    function getPlayer(int $id) {
        $sql = "SELECT * FROM player WHERE player_id = $id";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => new Player($dbResult), array_values($dbResults))[0];
    }

    // get players    
    function getPlayers(bool $includeEliminated = false) {
        $sql = "SELECT * FROM player";
        if (!$includeEliminated) {
            $sql .= " WHERE player_eliminated = 0 AND player_dead = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => new Player($dbResult), array_values($dbResults));
    }

    // get other players    
    function getOtherPlayers(int $playerId, bool $includeEliminated = false) {
        $sql = "SELECT * FROM player WHERE player_id <> $playerId";
        if (!$includeEliminated) {
            $sql .= " AND player_eliminated = 0 AND player_dead = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => new Player($dbResult), array_values($dbResults));
    }

    function getOrderedPlayers(int $currentTurnPlayerId, bool $includeEliminated = false) {
        $players = $this->getPlayers($includeEliminated);

        $playerIndex = 0; 
        foreach($players as $player) {
            if ($player->id == $currentTurnPlayerId) {
                break;
            }
            $playerIndex++;
        }

        $orderedPlayers = $players;
        if ($playerIndex > 0) { // we start from $currentTurnPlayerId and then follow order
            $orderedPlayers = array_merge(array_slice($players, $playerIndex), array_slice($players, 0, $playerIndex));
        }

        return $orderedPlayers;
    }
    
    function eliminatePlayers(int $currentTurnPlayerId) {
        $orderedPlayers = $this->getOrderedPlayers($currentTurnPlayerId, false);

        foreach($orderedPlayers as $player) {
            if ($player->health == 0 && !$player->eliminated) {
                // Final Roar
                if ($this->isWickednessExpansion() && $this->gotWickednessTile($player->id, FINAL_ROAR_WICKEDNESS_TILE) && $player->score >= 16) {
                    $this->applyGetPoints($player->id, MAX_POINT - $player->score, 2000 + FINAL_ROAR_WICKEDNESS_TILE);
                } else {
                    // Zombie
                    $countZombie = $this->countCardOfType($player->id, ZOMBIE_CARD);
                    if ($countZombie == 0) {
                        $this->eliminateAPlayer($player, $currentTurnPlayerId);
                    }
                }
            }
        }
    }

    function eliminateAPlayer(object $player, int $currentTurnPlayerId) {
        if ($this->isKingKongExpansion()) {
            // Tokyo Tower levels go back to the table
            $levels = $this->getTokyoTowerLevels($player->id);
            foreach($levels as $level) {
                $this->changeTokyoTowerOwner(0, $level);
            }
        }

        // if player is killing himself
        // in a game state, we can kill him, but else we have to wait the end of his turn
        $playerIsActivePlayer = in_array($player->id, $this->gamestate->getActivePlayerList());
        if ($player->id == $currentTurnPlayerId || $playerIsActivePlayer) {
            $this->asyncEliminatePlayer($player->id);
        } else {
            $scoreAux = intval($this->getGameStateValue(KILL_PLAYERS_SCORE_AUX)); 
            $this->DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, `player_score_aux` = $scoreAux, player_location = 0 where `player_id` = $player->id");
            if ($this->getRemainingPlayers() > 1) {
                $this->eliminatePlayer($player->id); // no need for notif, framework does it
            } else {
                // if last player, we make a notification same as elimination
                // but we don't really eliminate him as the framework don't like it and game will end anyway
                $this->notifyAllPlayers('playerEliminated', '', [
                    'who_quits' => $player->id,
                    'player_name' => $this->getPlayerName($player->id),
                ]);
            }
        }

        $cards = $this->getCardsFromDb($this->cards->getCardsInLocation('hand', $player->id));
        $this->removeCards($player->id, $cards, true);
        if ($this->isPowerUpExpansion()) {            
            $cards = $this->getEvolutionCardsFromDb($this->evolutionCards->getCardsInLocation('table', $player->id));
            $this->removeEvolutions($player->id, $cards, true);
        }
        
        // if player is playing in multipleactiveplayer
        if ($playerIsActivePlayer) {
            $this->gamestate->setPlayerNonMultiactive($player->id, 'stay');
        }

        if (!$this->isTokyoEmpty(true) && !$this->tokyoBayUsed()) { // 5 players to 4, Tokyo Bay got a player but it shouldn't, so player is moved
            if ($this->isTokyoEmpty(false)) {
                $this->moveFromTokyoBayToCity($this->getPlayerIdInTokyoBay());
            } else {
                $this->leaveTokyo($this->getPlayerIdInTokyoBay());
            }
        }
    }

    function removePlayerFromSmashedPlayersInTokyo(int $playerId) {
        $playersIds = $this->getGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, true);

        if ($playersIds != null && in_array($playerId, $playersIds)) {
            $playersIds = array_filter($playersIds, fn($id) => $id != $playerId);

            $this->setGlobalVariable(SMASHED_PLAYERS_IN_TOKYO, $playersIds);
        }
    }

    // $cardType = 0 => notification with no message
    // $cardType = -1 => no notification

    function applyGetPoints(int $playerId, int $points, int $cardType) {
        if (!$this->canGainPoints($playerId)) {
            return;
        }

        $this->applyGetPointsIgnoreCards($playerId, $points, $cardType);

        if ($cardType != ASTRONAUT_CARD) { // to avoid infinite loop
            // Astronaut
            $this->applyAstronaut($playerId);
        }
    }

    function applyGetPointsIgnoreCards(int $playerId, int $points, int $cardType) {
        $actualScore = $this->getPlayerScore($playerId);
        $newScore = min(MAX_POINT, $actualScore + $points);
        $this->DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${delta_points} [Star] with ${card_name}');
            $this->notifyAllPlayers('points', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'points' => $newScore,
                'delta_points' => $points,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }
    }

    function applyLosePoints(int $playerId, int $points, int $cardType) {
        $actualScore = $this->getPlayerScore($playerId);
        $newScore = max($actualScore - $points, 0);
        $this->DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} loses ${delta_points} [Star] with ${card_name}');
            $this->notifyAllPlayers('points', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'points' => $newScore,
                'delta_points' => $points,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }
    }

    function applyGetHealth(int $playerId, int $health, int $cardType, int $healerId) {
        if (!$this->canGainHealth($playerId)) {
            return;
        }
        
        $playerGettingHealth = $this->getPlayerGettingEnergyOrHeart($playerId);

        $this->applyGetHealthIgnoreCards($playerGettingHealth, $health, $cardType, $healerId);
        
        // regeneration
        $countRegeneration = $this->countCardOfType($playerId, REGENERATION_CARD);
        if ($countRegeneration > 0) {
            $this->applyGetHealthIgnoreCards($playerGettingHealth, $countRegeneration, REGENERATION_CARD, $playerId);
        }

        return $playerGettingHealth;
    }

    function applyGetHealthIgnoreCards(int $playerId, int $health, int $cardType, int $healerId) {
        $maxHealth = $this->getPlayerMaxHealth($playerId);

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = min($actualHealth + $health, $maxHealth);

        if ($actualHealth == $newHealth) {
            return; // already at full life, no need for notif
        }
        $realDeltaHealth = $newHealth - $actualHealth;

        $this->DbQuery("UPDATE player SET `player_health` = $newHealth, `player_turn_health` = `player_turn_health` + $realDeltaHealth where `player_id` = $playerId");

        $this->incStat($health, 'heal', $playerId);

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${delta_health} [Heart] with ${card_name}');
            $this->notifyAllPlayers('health', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'health' => $newHealth,
                'delta_health' => $health,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }

        if ($playerId == $healerId && $this->isCybertoothExpansion() && $this->isPlayerBerserk($playerId)) {
            $this->setPlayerBerserk($playerId, false);
        }
    }

    private function logDamageBlocked(int $playerId, int $cardType) {
        $this->notifyAllPlayers('damageBlockedLog', clienttranslate('${player_name} prevents damage with ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $cardType,
        ]);
    }

    function applyDamage(int $playerId, int $health, int $damageDealerId, int $cardType, int $activePlayerId, int $giveShrinkRayToken, int $givePoisonSpitToken, /*int|null*/ $smasherPoints) {
        $canLoseHealth = $this->canLoseHealth($playerId, $health);
        if ($canLoseHealth != null) {
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);

            $this->logDamageBlocked($playerId, $canLoseHealth);
            return; // player has golden scarab and cannot lose hearts
        }

        $actualHealth = $this->getPlayerHealth($playerId);

        $newHealth = $this->applyDamageIgnoreCards($playerId, $health, $damageDealerId, $cardType, $activePlayerId, $giveShrinkRayToken, $givePoisonSpitToken,  $smasherPoints);

        $isPowerUpExpansion = $this->isPowerUpExpansion();

        if ($newHealth == 0 && $actualHealth > 0) {
            // eater of the dead 
            $playersIds = $this->getPlayersIds();
            foreach($playersIds as $pId) {
                $countEaterOfTheDead = $this->countCardOfType($pId, EATER_OF_THE_DEAD_CARD);
                if ($countEaterOfTheDead > 0) {
                    $this->applyGetPoints($pId, 3 * $countEaterOfTheDead, EATER_OF_THE_DEAD_CARD);
                }

                if ($playerId != $pId && $isPowerUpExpansion) {
                    $countProgrammedToDestroy = $this->countEvolutionOfType($pId, PROGRAMMED_TO_DESTROY_EVOLUTION);
                    if ($countProgrammedToDestroy > 0) {
                        $this->applyGetPoints($pId, 3 * $countProgrammedToDestroy, 3000 + PROGRAMMED_TO_DESTROY_EVOLUTION);
                        $this->applyGetEnergy($pId, 2 * $countProgrammedToDestroy, 3000 + PROGRAMMED_TO_DESTROY_EVOLUTION);
                    }
                }
            }
        }

        if ($actualHealth - $newHealth >= 2) {
            // we're only making it stronger          
            $countWereOnlyMakingItStronger = $this->countCardOfType($playerId, WE_RE_ONLY_MAKING_IT_STRONGER_CARD);
            if ($countWereOnlyMakingItStronger > 0) {
                $this->applyGetEnergy($playerId, $countWereOnlyMakingItStronger, WE_RE_ONLY_MAKING_IT_STRONGER_CARD);
            }
        }

        // only smashes
        if ($cardType == 0 && $newHealth < $actualHealth && $damageDealerId != 0 && $damageDealerId != $playerId && $this->isPowerUpExpansion()) {
            $countHeatVision = $this->countEvolutionOfType($playerId, HEAT_VISION_EVOLUTION);
            if ($countHeatVision > 0) {
                $this->applyLosePoints($damageDealerId, $countHeatVision, 3000 + HEAT_VISION_EVOLUTION);
            }
            $countTooCuteToSmash = $this->countEvolutionOfType($playerId, TOO_CUTE_TO_SMASH_EVOLUTION);
            if ($countTooCuteToSmash > 0) {
                $this->applyLosePoints($damageDealerId, $countTooCuteToSmash, 3000 + TOO_CUTE_TO_SMASH_EVOLUTION);
            }
            $countMandiblesOfDread = $this->countEvolutionOfType($playerId, MANDIBLES_OF_DREAD_EVOLUTION);
            if ($countMandiblesOfDread > 0) {
                $this->applyLosePoints($playerId, $countMandiblesOfDread, 3000 + MANDIBLES_OF_DREAD_EVOLUTION);
            }
            $alphaMaleEvolutions = $this->getEvolutionsOfType($damageDealerId, ALPHA_MALE_EVOLUTION);
            if (count($alphaMaleEvolutions) > 0 && !$this->isUsedCard(3000 + $alphaMaleEvolutions[0]->id)) {
                $this->applyGetPoints($damageDealerId, count($alphaMaleEvolutions), 3000 + ALPHA_MALE_EVOLUTION);
                $this->setUsedCard(3000 + $alphaMaleEvolutions[0]->id);
            }
        }

        $countReflectiveHide = $this->countCardOfType($playerId, REFLECTIVE_HIDE_CARD);
        if ($countReflectiveHide > 0) {
            $this->applyDamage($damageDealerId, $countReflectiveHide, $playerId, 2000 + REFLECTIVE_HIDE_CARD, $activePlayerId, 0, 0, null);
        }

        if ($isPowerUpExpansion && $this->inTokyo($playerId)) {
            $countBreathOfDoom = $this->countEvolutionOfType($damageDealerId, BREATH_OF_DOOM_EVOLUTION);
            if ($countBreathOfDoom > 0) {
                $outsideTokyoPlayersIds = $this->getPlayersIdsOutsideTokyo();
                foreach ($outsideTokyoPlayersIds as $outsideTokyoPlayerId) {
                    if ($outsideTokyoPlayerId != $damageDealerId) {
                        $this->applyDamage($outsideTokyoPlayerId, $countBreathOfDoom, $damageDealerId, 3000 + BREATH_OF_DOOM_EVOLUTION, $damageDealerId, 0, 0, null);
                    }
                }
            }            
        }
    }

    function applyDamageIgnoreCards(int $playerId, int $health, int $damageDealerId, int $cardType, int $activePlayerId, int $giveShrinkRayToken, int $givePoisonSpitToken, /*int|null*/ $smasherPoints) {
        if ($this->canLoseHealth($playerId, $health) != null) {
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            return null; // player has wings and cannot lose hearts
        }

        $isPowerUpExpansion = $this->isPowerUpExpansion();

        // devil
        $devil = $activePlayerId == $damageDealerId && $playerId != $damageDealerId && $this->countCardOfType($damageDealerId, DEVIL_CARD) > 0;
        if ($devil) {
            $health++;
        }

        $countTargetAcquired = 0;
        if ($isPowerUpExpansion && $playerId == intval($this->getGameStateValue(TARGETED_PLAYER))) {
            $countTargetAcquired = $this->countEvolutionOfType($damageDealerId, TARGET_ACQUIRED_EVOLUTION);
            $health += $countTargetAcquired;
        }
        $countClawsOfSteel = 0;
        if ($isPowerUpExpansion && $health >= 3) {
            $countClawsOfSteel = $this->countEvolutionOfType($damageDealerId, CLAWS_OF_STEEL_EVOLUTION);
            $health += $countClawsOfSteel;
        }

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = max($actualHealth - $health, 0);

        $this->DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $playerId");

        if ($damageDealerId > 0) {
            $this->incStat($health, 'damageDealt', $damageDealerId);
        }
        $this->incStat($health, 'damage', $playerId);

        $message = $cardType <= 0 ? '' : clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}');
        $this->notifyAllPlayers('health', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'health' => $newHealth,
            'delta_health' => $health,
            'card_name' => $cardType == 0 ? null : $cardType,
        ]);

        if ($devil) {
            $this->notifyAllPlayers('log', clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'delta_health' => 1,
                'card_name' => DEVIL_CARD,
            ]);
        }
        if ($countTargetAcquired) {
            $this->notifyAllPlayers('log', clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'delta_health' => $countTargetAcquired,
                'card_name' => 3000 + TARGET_ACQUIRED_EVOLUTION,
            ]);
        }
        if ($countClawsOfSteel) {
            $this->notifyAllPlayers('log', clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}'), [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'delta_health' => $countClawsOfSteel,
                'card_name' => 3000 + CLAWS_OF_STEEL_EVOLUTION,
            ]);
        }

        // Shrink Ray
        if ($giveShrinkRayToken > 0) {
            $this->applyGetShrinkRayToken($playerId, $giveShrinkRayToken);
        }

        // Poison Spit
        if ($givePoisonSpitToken > 0) {
            $this->applyGetPoisonToken($playerId, $givePoisonSpitToken);
        }

        if ($smasherPoints !== null && $this->isWickednessExpansion() && $this->gotWickednessTile($damageDealerId, UNDERDOG_WICKEDNESS_TILE) && $this->getPlayerScore($playerId) > $smasherPoints) {
            $this->applyLosePoints($playerId, 1, 2000 + UNDERDOG_WICKEDNESS_TILE);
            $this->applyGetPoints($damageDealerId, 1, 2000 + UNDERDOG_WICKEDNESS_TILE);
        }
            
        $this->DbQuery("INSERT INTO `turn_damages`(`from`, `to`, `damages`)  VALUES ($damageDealerId, $playerId, $health) ON DUPLICATE KEY UPDATE `damages` = `damages` + $health");

        // pirate
        $pirateCardCount = $this->countCardOfType($damageDealerId, PIRATE_CARD);
        if ($pirateCardCount > 0 && $this->getPlayerEnergy($playerId) >= 1) {
            $this->applyLoseEnergy($playerId, 1, PIRATE_CARD);
            $this->applyGetEnergy($damageDealerId, 1, PIRATE_CARD);
        }

        // must be done before player eliminations
        $finalRoarWillActivate = $this->getPlayerHealth($playerId) == 0 
            && $this->isWickednessExpansion() 
            && $this->gotWickednessTile($playerId, FINAL_ROAR_WICKEDNESS_TILE) 
            && $this->getPlayerScore($playerId) >= 16;

        if (!$finalRoarWillActivate) {
            if ($this->isPowerUpExpansion() && $this->countEvolutionOfType($playerId, NINE_LIVES_EVOLUTION, true, true) > 0) {
                $this->applyNineLives($playerId);
            }

            if ($this->countCardOfType($playerId, ZOMBIFY_CARD) > 0 && $this->getPlayerHealth($playerId) == 0) {
                $this->applyZombify($playerId);
            }
    
            if ($this->countCardOfType($playerId, IT_HAS_A_CHILD_CARD) > 0 && $this->getPlayerHealth($playerId) == 0) {
                // it has a child
                $this->applyItHasAChild($playerId);
            }
        }

        $this->eliminatePlayers($activePlayerId);

        return $newHealth;
    }

    function getPlayerGettingEnergyOrHeart(int $playerId) {
        $playerGettingEnergyOrHeart = $playerId;

        if ($this->isAnubisExpansion() && $this->getCurseCardType() == CONFUSED_SENSES_CURSE_CARD) {
            $dieOfFate = $this->getDieOfFate();
            if ($dieOfFate->value == 3 && $playerId == intval($this->getActivePlayerId())) {
                $playerIdWithGoldenScarab = $this->getPlayerIdWithGoldenScarab();
                if ($playerIdWithGoldenScarab != null) {
                    $playerGettingEnergyOrHeart = $playerIdWithGoldenScarab;
                }
            }
        }

        return $playerGettingEnergyOrHeart;
    }

    function applyGetEnergy(int $playerId, int $energy, int $cardType) {
        if (!$this->canGainEnergy($playerId)) {
            return;
        }

        $playerGettingEnergy = $this->getPlayerGettingEnergyOrHeart($playerId);

        $this->applyGetEnergyIgnoreCards($playerGettingEnergy, $energy, $cardType);

        // friend of children
        $countFriendOfChildren = $this->countCardOfType($playerId, FRIEND_OF_CHILDREN_CARD);
        if ($countFriendOfChildren > 0) {
            $this->applyGetEnergyIgnoreCards($playerGettingEnergy, $countFriendOfChildren, FRIEND_OF_CHILDREN_CARD);
        }

        return $playerGettingEnergy;
    }

    function applyGetEnergyIgnoreCards(int $playerId, int $energy, int $cardType) {
        $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` + $energy, `player_turn_energy` = `player_turn_energy` + $energy where `player_id` = $playerId");
        
        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${delta_energy} [Energy] with ${card_name}');
            $this->notifyAllPlayers('energy', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'energy' => $this->getPlayerEnergy($playerId),
                'delta_energy' => $energy,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }

        $this->incStat($energy, 'wonEnergyCubes', $playerId);
    }

    function applyLoseEnergy(int $playerId, int $energy, int $cardType) {
        $this->applyLoseEnergyIgnoreCards($playerId, $energy, $cardType);
    }

    function applyLoseEnergyIgnoreCards(int $playerId, int $energy, int $cardType) {
        $actualEnergy = $this->getPlayerEnergy($playerId);
        $newEnergy = max($actualEnergy - $energy, 0);
        $this->DbQuery("UPDATE player SET `player_energy` = $newEnergy where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} loses ${delta_energy} [Energy] with ${card_name}');
            $this->notifyAllPlayers('energy', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'energy' => $newEnergy,
                'delta_energy' => $energy,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }
    }

    function applyGetShrinkRayToken(int $playerId, int $deltaTokens) {
        $this->DbQuery("UPDATE player SET `player_shrink_ray_tokens` = `player_shrink_ray_tokens` + $deltaTokens where `player_id` = $playerId");

        $message = clienttranslate('${player_name} gets ${delta_tokens} Shrink Ray token with ${card_name}');
        $this->notifyAllPlayers('shrinkRayToken', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'delta_tokens' => $deltaTokens,
            'card_name' => SHRINK_RAY_CARD,
            'tokens' => $this->getPlayerShrinkRayTokens($playerId),
        ]);
    }

    function applyGetPoisonToken(int $playerId, int $deltaTokens) {
        $this->DbQuery("UPDATE player SET `player_poison_tokens` = `player_poison_tokens` + $deltaTokens where `player_id` = $playerId");

        $message = clienttranslate('${player_name} gets ${delta_tokens} Poison token with ${card_name}');
        $this->notifyAllPlayers('poisonToken', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'delta_tokens' => $deltaTokens,
            'card_name' => POISON_SPIT_CARD,
            'tokens' => $this->getPlayerPoisonTokens($playerId),
        ]);
    }

    function removeShrinkRayToken(int $playerId, int $deltaTokens = 1) {
        $actualTokens = $this->getPlayerShrinkRayTokens($playerId);
        $newTokens = max($actualTokens - $deltaTokens, 0);

        $this->DbQuery("UPDATE player SET `player_shrink_ray_tokens` = $newTokens where `player_id` = $playerId");

        $this->notifyAllPlayers('removeShrinkRayToken', '', [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'deltaTokens' => $deltaTokens,
            'tokens' => $newTokens,
        ]);
    }
    

    function removePoisonToken(int $playerId, int $deltaTokens = 1) {
        $actualTokens = $this->getPlayerPoisonTokens($playerId);
        $newTokens = max($actualTokens - $deltaTokens, 0);

        $this->DbQuery("UPDATE player SET `player_poison_tokens` = $newTokens where `player_id` = $playerId");

        $this->notifyAllPlayers('removePoisonToken', '', [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'deltaTokens' => $deltaTokens,
            'tokens' => $newTokens,
        ]);
    }

    function resolveDamages(array $damages, int $endStateId) {
        $cancellableDamages = [];
        $playersIds = [];
        foreach ($damages as $damage) {
            if ($this->countCardOfType($damage->playerId, HIBERNATION_CARD) > 0) {
                // if hibernation, player takes no damage
            } else if (CancelDamageIntervention::canDoIntervention($this, $damage->playerId, $damage->damage, $damage->damageDealerId)) {
                $cancellableDamages[] = $damage;
                if (!in_array($damage->playerId, $playersIds)) {
                    $playersIds[] = $damage->playerId;
                }
            } else {
                $activePlayerId = $this->getActivePlayerId();
                $this->applyDamage($damage->playerId, $damage->damage, $damage->damageDealerId, $damage->cardType, $activePlayerId, $damage->giveShrinkRayToken, $damage->givePoisonSpitToken, $damage->smasherPoints);
            }
        }

        if (count($cancellableDamages) > 0) {
            $cancelDamageIntervention = new CancelDamageIntervention($playersIds, $cancellableDamages, $damages);
            $cancelDamageIntervention->endState = $endStateId;
            $this->setDamageIntervention($cancelDamageIntervention);
            $this->goToState(ST_MULTIPLAYER_CANCEL_DAMAGE);
        } else {
            if ($this->isPowerUpExpansion()) {
                $cancelDamageIntervention = new CancelDamageIntervention([], [], $damages);
                $cancelDamageIntervention->endState = $endStateId;
                $this->setDamageIntervention($cancelDamageIntervention);
                $this->goToState(ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE);
            } else {
                $this->goToState($endStateId);
            }
        }
    }

    function jumpToState(int $stateId) {
        $state = $this->gamestate->state();
        // we redirect only if game is not ended
        if ($state['name'] != 'gameEnd') {
            $this->gamestate->jumpToState($stateId);
        }
    }

    function updateKillPlayersScoreAux() {
        $eliminatedPlayersCount = intval($this->getUniqueValueFromDB("select count(*) from player where player_eliminated > 0 or player_dead > 0"));
        $this->setGameStateValue(KILL_PLAYERS_SCORE_AUX, $eliminatedPlayersCount + 1);
    }

    function getDamageTakenThisTurn(int $playerId) {
        return intval($this->getUniqueValueFromDB( "SELECT SUM(`damages`) FROM `turn_damages` WHERE `to` = $playerId"));
    }

    function isDamageTakenThisTurn(int $playerId) {
        return $this->getDamageTakenThisTurn($playerId) > 0;
    }

    function isDamageDealtThisTurn(int $playerId) {
        return intval($this->getUniqueValueFromDB( "SELECT SUM(`damages`) FROM `turn_damages` WHERE `from` = $playerId")) > 0;
    }

    function isDamageDealtToOthersThisTurn(int $playerId) {
        return intval($this->getUniqueValueFromDB( "SELECT SUM(`damages`) FROM `turn_damages` WHERE `from` = $playerId and `to` <> $playerId")) > 0;
    }

    function playersWoundedByActivePlayerThisTurn(int $playerId) {
        $dbResults = $this->getCollectionFromDb("SELECT `to` FROM `turn_damages` WHERE `from` = $playerId");
        return array_map(fn($dbResult) => intval($dbResult['to']), array_values($dbResults));
    }

    function placeNewCardsOnTable() {
        $cards = [];
        
        for ($i=1; $i<=3; $i++) {
            $cards[] = $this->getCardFromDb($this->cards->pickCardForLocation('deck', 'table', $i));
        }

        return $cards;
    }

    function getTokyoTowerLevels(int $playerId) {
        $dbResults = $this->getCollectionFromDb("SELECT `level` FROM `tokyo_tower` WHERE `owner` = $playerId order by `level`");
        return array_map(fn($dbResult) => intval($dbResult['level']), array_values($dbResults));
    }

    function changeTokyoTowerOwner(int $playerId, int $level) {
        $this->DbQuery("UPDATE `tokyo_tower` SET  `owner` = $playerId where `level` = $level");

        $message = $playerId == 0 ? '' : clienttranslate('${player_name} claims Tokyo Tower level ${level}');
        $this->notifyAllPlayers("changeTokyoTowerOwner", $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'level' => $level,
        ]);

        if ($playerId > 0) {
            $this->incStat(1, 'tokyoTowerLevel'.$level.'claimed', $playerId);
        }
    }

    function getPlayersIdsWithMaxColumn(string $column) {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 AND player_dead = 0 AND `$column` = (select max(`$column`) FROM player WHERE player_eliminated = 0 AND player_dead = 0) ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
    }
}
