<?php

namespace KOT\States;

require_once(__DIR__.'/objects/dice.php');
require_once(__DIR__.'/objects/card.php');
require_once(__DIR__.'/objects/player.php');

use KOT\Objects\Card;
use KOT\Objects\Dice;
use KOT\Objects\Player;

trait UtilTrait {

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function setGlobalVariable(string $name, $obj) {
        $jsonObj = json_encode($obj);
        self::DbQuery("INSERT INTO `global_variables`(`name`, `value`)  VALUES ('$name', '$jsonObj') ON DUPLICATE KEY UPDATE `value` = '$jsonObj'");
    }

    function getGlobalVariable(string $name, $asArray = null) {
        $json_obj = self::getUniqueValueFromDB("SELECT `value` FROM `global_variables` where `name` = '$name'");
        if ($json_obj) {
            return json_decode($json_obj, $asArray);
        } else {
            return null;
        }
    }

    function removeGlobalVariable(string $name) {
        self::DbQuery("DELETE FROM `global_variables` where `name` = '$name'");
    }

    function getMaxPlayerScore() {
        return intval(self::getUniqueValueFromDB("SELECT max(player_score) FROM player"));
    }

    function getPlayerName(int $playerId) {
        return self::getUniqueValueFromDb("SELECT player_name FROM player WHERE player_id = $playerId");
    }

    function getPlayerScore(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId"));
    }

    function getPlayerHealth(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_health FROM player where `player_id` = $playerId"));
    }

    function getPlayerEnergy(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_energy FROM player where `player_id` = $playerId"));
    }

    function getPlayerPoisonTokens(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_poison_tokens FROM player where `player_id` = $playerId"));
    }

    function getPlayerShrinkRayTokens(int $playerId) {
        return intval(self::getUniqueValueFromDB("SELECT player_shrink_ray_tokens FROM player where `player_id` = $playerId"));
    }

    function getThrowNumber(int $playerId) {
        // giant brain
        $countGiantBrain = $this->countCardOfType($playerId, 18);
        // energy drink
        $energyDrinks = intval(self::getGameStateValue('energyDrinks'));
        return 3 + $countGiantBrain + $energyDrinks;
    }

    function getPlayerMaxHealth(int $playerId) {
        // even bigger
        $countEvenBigger = $this->countCardOfType($playerId, 12);
        return 10 + (2 * $countEvenBigger);
    }

    function getRemainingPlayers() {
        return intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_eliminated = 0"));
    }

    function tokyoBayUsed() {
        return $this->getRemainingPlayers() > 4;
    }

    function isTokyoEmpty(bool $bay) {
        $location = $bay ? 2 : 1;
        $players = intval(self::getUniqueValueFromDB( "SELECT count(*) FROM player WHERE player_location = $location"));
        return $players == 0;
    }

    function moveToTokyo(int $playerId, bool $bay) {
        $location = $bay ? 2 : 1;
        $incScore = 1;
        self::DbQuery("UPDATE player SET player_score = player_score + $incScore, player_location = $location where `player_id` = $playerId");

        $locationName = $bay ? _('Tokyo Bay') : _('Tokyo City');
        self::notifyAllPlayers("playerEntersTokyo", clienttranslate('${player_name} enters ${locationName} and wins 1 [Star]'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'location' => $location,
            'locationName' => $locationName,
            'points' => $this->getPlayerScore($playerId),
        ]);
    }

    function leaveTokyo($playerId) {

        self::DbQuery("UPDATE player SET player_location = 0 where `player_id` = $playerId");

        self::notifyAllPlayers("leaveTokyo", clienttranslate('${player_name} chooses to leave Tokyo'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
        ]);
    }

    function moveFromTokyoBayToCity($playerId) {
        $location = 1;

        self::DbQuery("UPDATE player SET player_location =  $location where `player_id` = $playerId");

        $locationName = _('Tokyo City');
        self::notifyAllPlayers("playerEntersTokyo", clienttranslate('${player_name} enters ${locationName} !'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'location' => $location,
            'locationName' => $locationName,
            'points' => $this->getPlayerScore($playerId),
        ]);
    }

    function inTokyo(int $playerId) {
        $location = intval(self::getUniqueValueFromDB( "SELECT player_location FROM player WHERE player_id = $playerId"));
        return $location > 0;
    }

    // get players ids

    private function getPlayersIdsFromLocation(bool $inside) {
        $sign = $inside ? '>' : '=';
        $sql = "SELECT player_id FROM player WHERE player_location $sign 0 AND player_eliminated = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    function getPlayerIdInTokyoCity() {
        $sql = "SELECT player_id FROM player WHERE player_location = 1 AND player_eliminated = 0 ORDER BY player_no";
        return intval(self::getUniqueValueFromDB($sql));
    }

    function getPlayerIdInTokyoBay() {
        $sql = "SELECT player_id FROM player WHERE player_location = 2 AND player_eliminated = 0 ORDER BY player_no";
        return intval(self::getUniqueValueFromDB($sql));
    }

    function getPlayersIdsInTokyo() {
        return $this->getPlayersIdsFromLocation(true);
    }

    /*function getPlayersIdsOutsideTokyo() {
        return $this->getPlayersIdsFromLocation(false);
    }*/

    function getPlayersIds() {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    function getOtherPlayersIds(int $playerId) {
        $sql = "SELECT player_id FROM player WHERE player_id <> $playerId AND player_eliminated = 0 ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return intval($dbResult['player_id']); }, array_values($dbResults));
    }

    // get players    
    function getPlayers(bool $includeEliminated = false) {
        $sql = "SELECT * FROM player";
        if (!$includeEliminated) {
            $sql .= " WHERE player_eliminated = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return new Player($dbResult); }, array_values($dbResults));
    }

    // get other players    
    function getOtherPlayers(int $playerId, bool $includeEliminated = false) {
        $sql = "SELECT * FROM player WHERE player_id <> $playerId";
        if (!$includeEliminated) {
            $sql .= " AND player_eliminated = 0";
        }
        $sql .= " ORDER BY player_no";
        $dbResults = self::getCollectionFromDB($sql);
        return array_map(function($dbResult) { return new Player($dbResult); }, array_values($dbResults));
    }
    
    function eliminatePlayers(int $currentTurnPlayerId) {
        $players = $this->getPlayers(true);
        // TODO UnitTests

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

        $endGame = false;

        foreach($orderedPlayers as $player) {
            if ($player->health == 0 && !$player->eliminated) {
                $endGame = $this->eliminateAPlayer($player);
            }
        }

        return $endGame;
    }

    function eliminateAPlayer(object $player) { // return $endGame
        self::DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, player_location = 0 where `player_id` = $player->id");

        /* no need for notif, framework does it
        self::notifyAllPlayers("playerEliminated", clienttranslate('${player_name} is eliminated !'), [
            'playerId' => $player->id,
            'player_name' => $player->name,
        ]);*/

        $playersBeforeElimination = $this->getRemainingPlayers();

        $this->cards->moveAllCardsInLocation('hand', 'discard', $player->id);
        self::eliminatePlayer($player->id);

        if ($playersBeforeElimination == 5) { // 5 players to 4, clear Tokyo Bay
            if ($this->isTokyoEmpty(false) && !$this->isTokyoEmpty(true)) {
                $this->moveFromTokyoBayToCity($this->getPlayerIdInTokyoBay());
            }

            if (!$this->isTokyoEmpty(false) && !$this->isTokyoEmpty(true) && $playersBeforeElimination == 5) {
                $this->leaveTokyo($this->getPlayerIdInTokyoBay());
            }
        }

        return $this->getRemainingPlayers() <= 1;
    }

    // $cardType = 0 => notification with no message
    // $cardType = -1 => no notification

    function applyGetPoints(int $playerId, int $points, int $cardType) {
        $this->applyGetPointsIgnoreCards($playerId, $points, $cardType);
    }

    function applyGetPointsIgnoreCards(int $playerId, int $points, int $cardType) {
        $maxPoints = 20;

        $actualScore = $this->getPlayerScore($playerId);
        $newScore = min($actualScore + $points, $maxPoints);
        self::DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : _('${player_name} wins ${delta_points} [Star] with ${card_name}');
            self::notifyAllPlayers('points', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'points' => $newScore,
                'delta_points' => $points,
                'card_name' => $cardType == 0 ? null : $this->getCardName($cardType),
            ]);
        }
    }

    function applyLosePoints(int $playerId, int $points, int $cardType) {
        $actualScore = $this->getPlayerScore($playerId);
        $newScore = max($actualScore - $points, 0);
        self::DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : _('${player_name} loses ${delta_points} [Star] with ${card_name}');
            self::notifyAllPlayers('points', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'points' => $newScore,
                'delta_points' => $points,
                'card_name' => $cardType == 0 ? null : $this->getCardName($cardType),
            ]);
        }
    }

    function applyGetHealth(int $playerId, int $health, int $cardType) {

        $this->applyGetHealthIgnoreCards($playerId, $health, $cardType);
        
        // regeneration
        $countRegeneration = $this->countCardOfType($playerId, 38);
        if ($countRegeneration > 0) {
            $this->applyGetHealthIgnoreCards($playerId, $countRegeneration, 38);
        }
    }

    function applyGetHealthIgnoreCards(int $playerId, int $health, int $cardType) {
        $maxHealth = $this->getPlayerMaxHealth($playerId);

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = min($actualHealth + $health, $maxHealth);
        self::DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : _('${player_name} wins ${delta_health} [Heart] with ${card_name}');
            self::notifyAllPlayers('health', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'health' => $newHealth,
                'delta_health' => $health,
                'card_name' => $cardType == 0 ? null : $this->getCardName($cardType),
            ]);
        }
    }

    function applyDamage(int $playerId, int $health, int $damageDealerId, int $cardType) {
        // Armor plating
        $countArmorPlating = $this->countCardOfType($playerId, 4);
        if ($countArmorPlating > 0 && $health == 1) {
            return;
        }

        $newHealth = $this->applyDamageIgnoreCards($playerId, $health, $damageDealerId, $cardType);

        if ($newHealth == 0) {
            // eater of the dead 
            $otherPlayersIds = $this->getOtherPlayersIds($playerId);
            foreach($otherPlayersIds as $otherPlayerId) {
                $countEaterOfTheDead = $this->countCardOfType($otherPlayerId, 10);
                if ($countEaterOfTheDead > 0) {
                    $this->applyGetPoints($otherPlayerId, 3 * $countEaterOfTheDead, 10);
                }
            }
        }

        if ($health >= 2) {
            // we're only making it stronger          
            $countWereOnlyMakingItStronger = $this->countCardOfType($playerId, 47);
            if ($countWereOnlyMakingItStronger > 0) {
                $this->applyGetEnergy($playerId, $countWereOnlyMakingItStronger, 47);
            }
        }

        if ($this->countCardOfType($playerId, 23) > 0 && $this->getPlayerHealth($playerId) == 0) {
            // it has a child
            $this->applyItHasAChild($playerId);
            // TODO make notifs for this happen after dice notifs
        }
    }

    function applyDamageIgnoreCards(int $playerId, int $health, int $damageDealerId, int $cardType) {

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = max($actualHealth - $health, 0);

        self::DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : _('${player_name} loses ${delta_health} [Heart] with ${card_name}');
            self::notifyAllPlayers('health', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'health' => $newHealth,
                'delta_health' => $health,
                'card_name' => $cardType == 0 ? null : $this->getCardName($cardType),
            ]);
        }

        if ($damageDealerId == self::getActivePlayerId()) {
            self::setGameStateValue('damageDoneByActivePlayer', 1);
        }

        return $newHealth;
    }

    function applyGetEnergy(int $playerId, int $energy, int $cardType) {
        $this->applyGetEnergyIgnoreCards($playerId, $energy, $cardType);

        // friend of children
        $countFriendOfChildren = $this->countCardOfType($playerId, 17);
        if ($countFriendOfChildren > 0) {
            $this->applyGetEnergyIgnoreCards($playerId, $countFriendOfChildren, 17);
        }
    }

    function applyGetEnergyIgnoreCards(int $playerId, int $energy, int $cardType) {
        self::DbQuery("UPDATE player SET `player_energy` = `player_energy` + $energy where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : _('${player_name} wins ${delta_energy} [Energy] with ${card_name}');
            self::notifyAllPlayers('energy', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'energy' => $this->getPlayerEnergy($playerId),
                'delta_energy' => $energy,
                'card_name' => $cardType == 0 ? null : $this->getCardName($cardType),
            ]);
        }
    }

    function applyLoseEnergy(int $playerId, int $energy, int $cardType) {
        $this->applyLoseEnergyIgnoreCards($playerId, $energy, $cardType);
    }

    function applyLoseEnergyIgnoreCards(int $playerId, int $energy, int $cardType) {
        $actualEnergy = $this->getPlayerEnergy($playerId);
        $newEnergy = max($actualEnergy - $energy, 0);
        self::DbQuery("UPDATE player SET `player_energy` = $newEnergy where `player_id` = $playerId");

        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : _('${player_name} loses ${delta_energy} [Energy] with ${card_name}');
            self::notifyAllPlayers('energy', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'energy' => $newEnergy,
                'delta_energy' => $energy,
                'card_name' => $cardType == 0 ? null : $this->getCardName($cardType),
            ]);
        }
    }

    function applyGetShrinkRayToken(int $playerId) {
        $deltaTokens = 1;
        self::DbQuery("UPDATE player SET `player_shrink_ray_tokens` = `player_shrink_ray_tokens` + $deltaTokens where `player_id` = $playerId");

        $message = _('${player_name} gets ${delta_tokens} Shrink Ray token with ${card_name}');
        self::notifyAllPlayers('shrinkRayToken', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'delta_tokens' => $deltaTokens,
            'card_name' => $this->getCardName(40),
            'tokens' => $this->getPlayerShrinkRayTokens($playerId),
        ]);
    }

    function applyGetPoisonToken(int $playerId) {
        $deltaTokens = 1;
        self::DbQuery("UPDATE player SET `player_poison_tokens` = `player_poison_tokens` + $deltaTokens where `player_id` = $playerId");

        $message = _('${player_name} gets ${delta_tokens} Poison token with ${card_name}');
        self::notifyAllPlayers('poisonToken', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'delta_tokens' => $deltaTokens,
            'card_name' => $this->getCardName(35),
            'tokens' => $this->getPlayerPoisonTokens($playerId),
        ]);
    }

    function isFewestStars(int $playerId) {
        $sql = "SELECT count(*) FROM `player` where `player_id` = $playerId AND `player_score` = (select min(`player_score`) from `player`) AND (SELECT count(*) FROM `player` where `player_score` = (select min(`player_score`) from `player`)) = 1";
        return intval(self::getUniqueValueFromDB($sql)) > 0;
    }
}
