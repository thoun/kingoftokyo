<?php

namespace KOT\States;

require_once(__DIR__.'/Objects/player.php');
require_once(__DIR__.'/Objects/player-intervention.php');
require_once(__DIR__.'/Objects/damage.php');

use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;
use Bga\Games\KingOfTokyo\WickednessTiles\WickednessTile;
use KOT\Objects\Player;
use KOT\Objects\CancelDamageIntervention;
use KOT\Objects\Damage;

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

    function array_find_index(array $array, callable $fn) {
        foreach ($array as $index => $value) {
            if($fn($value)) {
                return $index;
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

    function isTwoPlayersVariant() {
        return $this->tableOptions->get(TWO_PLAYERS_VARIANT_OPTION) === 2 && $this->getPlayersNumber() == 2;
    }

    function isHalloweenExpansion() {
        return $this->tableOptions->get(HALLOWEEN_EXPANSION_OPTION) === 2;
    }

    function isMutantEvolutionVariant() {
        return $this->tableOptions->get(MUTANT_EVOLUTION_VARIANT_OPTION) === 2;
    }

    function isDarkEdition() {
        return !$this->isOrigins() && $this->tableOptions->get(DARK_EDITION_OPTION) > 1;
    }

    function isOrigins() {
        return $this->tableOptions->get(ORIGINS_OPTION) > 1;
    }

    function releaseDatePassed(string $activationDateStr, int $hourShift) { // 1 for paris winter time, 2 for paris summer time
        $currentdate = new \DateTimeImmutable();
        $activationdate = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $activationDateStr.'+00:00')->sub(new \DateInterval("PT{$hourShift}H")); // "2021-12-30T21:41:00+00:00"
        $diff = $currentdate->diff($activationdate);
        return $diff->invert;
    }

    function autoSkipImpossibleActions() {
        return $this->tableOptions->isTurnBased() || $this->tableOptions->get(AUTO_SKIP_OPTION) === 2;
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
        return min(20, intval($this->getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId")));
    }

    function getPlayerHealth(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_health FROM player where `player_id` = $playerId"));
    }

    function getPlayerEnergy(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_energy FROM player where `player_id` = $playerId"));
    }

    function getPlayerPotentialEnergy(int $playerId) {
        $potentialEnergy = $this->getPlayerEnergy($playerId);
        if ($this->cthulhuExpansion->isActive()) {
            $cultists = $this->cthulhuExpansion->getPlayerCultists($playerId);
            if ($cultists > 0) {
                $countFriendOfChildren = $this->countCardOfType($playerId, FRIEND_OF_CHILDREN_CARD);
                $potentialEnergy += (1 + $countFriendOfChildren) * $cultists;
            }
        }
        return $potentialEnergy;
    }

    function getPlayerPoisonTokens(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_poison_tokens FROM player where `player_id` = $playerId"));
    }

    function getPlayerShrinkRayTokens(int $playerId) {
        return intval($this->getUniqueValueFromDB("SELECT player_shrink_ray_tokens FROM player where `player_id` = $playerId"));
    }

    function getRollNumber(int $playerId) {
        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

        $ignisFatus = 0;
        if ($isPowerUpExpansion) {
            $blizzardOwner = $this->isEvolutionOnTable(BLIZZARD_EVOLUTION);
            if ($blizzardOwner != null && $blizzardOwner != $playerId) {
                return 1;
            }

            if ($this->countEvolutionOfType($playerId, CAT_NIP_EVOLUTION) > 0) {  
                return 1;
            }        

            // ignis fatus
            if ($this->getGiftEvolutionOfType($playerId, IGNIS_FATUS_EVOLUTION) !== null) {
                $ignisFatus = 1;
            }
        }

        // giant brain
        $countGiantBrain = $this->countCardOfType($playerId, GIANT_BRAIN_CARD);
        // statue of libery
        $countStatueOfLiberty = $this->countCardOfType($playerId, STATUE_OF_LIBERTY_CARD);
        // energy drink
        $extraRolls = intval($this->getGameStateValue(EXTRA_ROLLS));
        $wickenessTilesInc = $this->wickednessExpansion->isActive() ? $this->wickednessTiles->onIncDieRollCount(new Context($this, currentPlayerId: $playerId)) : 0;

        $removedDieByBuriedInSand = false;
        $falseBlessing = 0;
        if ($this->anubisExpansion->isActive()) {
            $cardType = $this->anubisExpansion->getCurseCardType();

            if ($cardType == BURIED_IN_SAND_CURSE_CARD) {
                $dieOfFate = $this->anubisExpansion->getDieOfFate();

                if ($dieOfFate->value != 4) {
                    $removedDieByBuriedInSand = true;
                }
            }

            if ($cardType == FALSE_BLESSING_CURSE_CARD) {
                $falseBlessing = 1;
            }
        }

        $rollNumber = 3 + $countGiantBrain + $countStatueOfLiberty + $extraRolls + $wickenessTilesInc + $falseBlessing - $ignisFatus;
        if ($rollNumber > 1 && $removedDieByBuriedInSand) {
            $rollNumber--;
        }
        return $rollNumber;
    }

    function getPlayerMaxHealth(int $playerId) {
        $add = 0;
        $remove = 0;

        if ($this->wickednessExpansion->isActive()) {
            $add += $this->wickednessTiles->onIncMaxHealth(new Context($this, currentPlayerId: $playerId));
        }
        
        $add += 2 * $this->countCardOfType($playerId, EVEN_BIGGER_CARD);

        if ($this->isZombified($playerId)) {
            $add += 2;
        }
        if ($this->powerUpExpansion->isActive()) {
            $add += 2 * $this->countEvolutionOfType($playerId, EATER_OF_SOULS_EVOLUTION);
        }

        if ($this->anubisExpansion->isActive() && $this->anubisExpansion->getCurseCardType() == BOW_BEFORE_RA_CURSE_CARD) {
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
        $this->DbQuery("UPDATE player SET player_location = $location, player_turn_entered_tokyo = 1 where `player_id` = $playerId");
        if ($this->isTwoPlayersVariant()) {

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
            if ($this->canGainPoints($playerId) === null) {
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

        $countHungryUrbavore = $this->countCardOfType($playerId, HUNGRY_URBAVORE_CARD);
        if ($countHungryUrbavore > 0) {
            $this->applyGetPoints($playerId, $countHungryUrbavore, HUNGRY_URBAVORE_CARD);
        }

        if ($this->wickednessExpansion->isActive()) {
            $this->wickednessTiles->onEnteringTokyo(new Context(
                $this, 
                currentPlayerId: $playerId,
            ));
        }

        if ($this->powerUpExpansion->isActive()) {
            $countBlackDiamond = $this->countEvolutionOfType($playerId, BLACK_DIAMOND_EVOLUTION);
            if ($countBlackDiamond > 0) {
                $this->applyGetPoints($playerId, $countBlackDiamond, 3000 + BLACK_DIAMOND_EVOLUTION);
            }
            $countIAmTheKing = $this->countEvolutionOfType($playerId, I_AM_THE_KING_EVOLUTION);
            if ($countIAmTheKing > 0) {
                $this->applyGetPoints($playerId, $countIAmTheKing, 3000 + I_AM_THE_KING_EVOLUTION);
            }
            $countEaterOfSouls = $this->countEvolutionOfType($playerId, EATER_OF_SOULS_EVOLUTION);
            if ($countEaterOfSouls > 0) {
                $this->applyGetHealth($playerId, $countEaterOfSouls, 3000 + EATER_OF_SOULS_EVOLUTION, $playerId);
            }
            $countNightlife = $this->countEvolutionOfType($playerId, NIGHTLIFE_EVOLUTION);
            if ($countNightlife > 0) {
                $this->applyGetHealth($playerId, $countNightlife, 3000 + NIGHTLIFE_EVOLUTION, $playerId);
            }

            $evolutions = $this->getEvolutionCardsByLocation('discard'.$playerId, null, HEART_OF_THE_RABBIT_EVOLUTION);
            if (count($evolutions) > 0) {
                foreach($evolutions as $evolution) {
                    if ($evolution->tokens > 0) {
                        $this->getEvolutionFromDiscard($playerId, $evolution->id);
                    }
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

        // if the player left with autoleave, we automatically suppose he wants to use Jets if he have one
        if ($useCard === null && $this->countCardOfType($playerId, JETS_CARD) > 0) {
            $useCard = JETS_CARD;
        }

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

        if ($this->powerUpExpansion->isActive()) {
            $twasBeautyKilledTheBeastCards = $this->getEvolutionsOfType($playerId, TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION);
            if (count($twasBeautyKilledTheBeastCards) > 0 && !$this->getPlayer($playerId)->eliminated) {
                $this->applyLeaveWithTwasBeautyKilledTheBeast($playerId, $twasBeautyKilledTheBeastCards);
            }

            $this->checkOnlyChestThumpingRemaining();
        }

        return true;
    }

    function checkOnlyChestThumpingRemaining() {
        if (intval($this->gamestate->state_id()) === ST_MULTIPLAYER_LEAVE_TOKYO) {
            $activePlayerList = $this->gamestate->getActivePlayerList();
            $activePlayerId = intval($this->getActivePlayerId());
            if (count($activePlayerList) == 1 && intval($activePlayerList[0]) == $activePlayerId && $this->countEvolutionOfType($activePlayerId, CHEST_THUMPING_EVOLUTION) > 0) {
                $argLeaveTokyo = $this->argLeaveTokyo();
                if (array_key_exists('smashedPlayersInTokyo', $argLeaveTokyo)) {
                    $smashedPlayersInTokyo = $argLeaveTokyo['smashedPlayersInTokyo'];
                    // if there is no remaining smashed player in tokyo, chest thumping player is deactivated
                    if (!$this->array_some($smashedPlayersInTokyo, fn($pId) => $this->inTokyo($pId))) {                    
                        $this->gamestate->setPlayerNonMultiactive($activePlayerList[0], 'resume');
                    }
                }
            }
        }
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

    function getOrderedPlayers(int $currentTurnPlayerId, bool $includeEliminated = false, bool $currentLast = false) {
        $players = $this->getPlayers($includeEliminated);

        $playerIndex = 0; 
        foreach($players as $player) {
            if ($player->id == $currentTurnPlayerId) {
                break;
            }
            $playerIndex++;
        }

        $orderedPlayers = $players;

        if ($currentLast) {
            if ($playerIndex < count($players) - 1) { // we start from $currentTurnPlayerId and then follow order
                $orderedPlayers = array_merge(array_slice($players, $playerIndex + 1), array_slice($players, 0, $playerIndex + 1));
            }
        } else {
            if ($playerIndex > 0) { // we start from $currentTurnPlayerId and then follow order
                $orderedPlayers = array_merge(array_slice($players, $playerIndex), array_slice($players, 0, $playerIndex));
            }
        }

        return $orderedPlayers;
    }

    function getOrderedPlayersIds(int $currentTurnPlayerId, bool $includeEliminated = false, bool $currentLast = false) {
        $orderedPlayers = $this->getOrderedPlayers($currentTurnPlayerId, $includeEliminated, $currentLast);
        return array_map(fn($player) => $player->id, $orderedPlayers);
    }
    
    function eliminatePlayers(int $currentTurnPlayerId) {
        $orderedPlayers = $this->getOrderedPlayers($currentTurnPlayerId, false);

        foreach($orderedPlayers as $player) {
            if ($player->health == 0 && !$player->eliminated && $this->countCardOfType($player->id, ZOMBIE_CARD) == 0) { // ignore players with Zombie
                
                $context = new Context($this, currentPlayerId: $player->id);
                $winOnEliminationWickednessTile = $this->wickednessExpansion->isActive() ? $this->wickednessTiles->winOnElimination($context) : null;

                if ($winOnEliminationWickednessTile !== null) {
                    $winOnEliminationWickednessTile->onTrigger($context);
                } else {
                    $this->eliminateAPlayer($player, $currentTurnPlayerId);
                }
            }
        }
    }

    function safeEliminatePlayer(int $playerId) {
        if ($this->getRemainingPlayers() > 1) {
            $this->eliminatePlayer($playerId); // no need for notif, framework does it
        } else {
            // if last player, we make a notification same as elimination
            // but we don't really eliminate him as the framework don't like it and game will end anyway
            $this->notifyAllPlayers('playerEliminated', '', [
                'who_quits' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
            ]);
        }
    }

    function eliminateAPlayer(object $player, int $currentTurnPlayerId) {
        if ($this->kingKongExpansion->isActive()) {
            $this->kingKongExpansion->onPlayerEliminated($player->id);
        }

        // if player is killing himself
        // in a game state, we can kill him, but else we have to wait the end of his turn
        $playerIsActivePlayer = in_array($player->id, $this->gamestate->getActivePlayerList());
        if ($player->id == $currentTurnPlayerId || $playerIsActivePlayer) {
            $this->asyncEliminatePlayer($player->id);
        } else {
            $scoreAux = intval($this->getGameStateValue(KILL_PLAYERS_SCORE_AUX)); 
            $this->DbQuery("UPDATE player SET `player_health` = 0, `player_score` = 0, `player_score_aux` = $scoreAux, player_location = 0 where `player_id` = $player->id");
            
            $this->safeEliminatePlayer($player->id);
        }

        $cards = $this->powerCards->getPlayer($player->id);
        $this->removeCards($player->id, $cards, true);
        if ($this->wickednessExpansion->isActive()) {
            $tiles = $this->wickednessTiles->getPlayerTiles($player->id);
            $this->wickednessExpansion->removeWickednessTiles($player->id, $tiles);
        }
        if ($this->powerUpExpansion->isActive()) {
            $cards = $this->getEvolutionCardsByLocation('hand', $player->id);
            $this->removeEvolutions($player->id, $cards, true);
            $cards = $this->getEvolutionCardsByLocation('table', $player->id);
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
    function applyGetPoints(int $playerId, int $points, int | PowerCard | CurseCard | WickednessTile | EvolutionCard $cardType) {
        $canGainPoints = $this->canGainPoints($playerId);
        if ($canGainPoints !== null) {
            return $canGainPoints;
        }

        $this->applyGetPointsIgnoreCards($playerId, $points, $cardType);

        if (gettype($cardType) !== 'integer' || $cardType != ASTRONAUT_CARD) { // to avoid infinite loop
            // Astronaut
            $this->applyAstronaut($playerId);
        }

        return null;
    }

    function applyGetPointsIgnoreCards(int $playerId, int $points, int | PowerCard | CurseCard | WickednessTile | EvolutionCard $cardType) {
        //$actualScore = $this->getPlayerScore($playerId);
        $actualScore = intval($this->getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId"));
        if ($actualScore == WIN_GAME) {
            return;
        } else {
            $actualScore = min(20, $actualScore);
        }

        $newScore = $points == WIN_GAME ? WIN_GAME : min(MAX_POINT, $actualScore + $points);
        $this->DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType instanceof PowerCard) {
            $cardType = $cardType->type;
        }
        if ($cardType instanceof CurseCard) {
            $cardType = 1000 + $cardType->type;
        }
        if ($cardType instanceof WickednessTile) {
            $cardType = 2000 + $cardType->type;
        }
        if ($cardType instanceof EvolutionCard) {
            $cardType = 3000 + $cardType->type;
        }
        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${delta_points} [Star] with ${card_name}');
            $this->notifyAllPlayers('points', $message, [
                'playerId' => $playerId,
                'player_name' => $this->getPlayerName($playerId),
                'points' => min(20, $newScore),
                'delta_points' => $points,
                'card_name' => $cardType == 0 ? null : $cardType,
            ]);
        }
    }

    function applyLosePoints(int $playerId, int $points, int | PowerCard | CurseCard | WickednessTile | EvolutionCard $cardType) {
        //$actualScore = $this->getPlayerScore($playerId);
        $actualScore = intval($this->getUniqueValueFromDB("SELECT player_score FROM player where `player_id` = $playerId"));
        if ($actualScore == WIN_GAME) {
            // can't lose points if the player made an action saying win the game
            return;
        }

        $newScore = max($actualScore - $points, 0);
        $this->DbQuery("UPDATE player SET `player_score` = $newScore where `player_id` = $playerId");

        if ($cardType instanceof PowerCard) {
            $cardType = $cardType->type;
        }
        if ($cardType instanceof CurseCard) {
            $cardType = 1000 + $cardType->type;
        }
        if ($cardType instanceof WickednessTile) {
            $cardType = 2000 + $cardType->type;
        }
        if ($cardType instanceof EvolutionCard) {
            $cardType = 3000 + $cardType->type;
        }
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

    function applyGetHealth(int $playerId, int $health, int | PowerCard | CurseCard | WickednessTile | EvolutionCard $cardType, int $healerId) {
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

    function applyGetHealthIgnoreCards(int $playerId, int $health, int | PowerCard | CurseCard | WickednessTile | EvolutionCard $cardType, int $healerId) {
        $maxHealth = $this->getPlayerMaxHealth($playerId);

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = min($actualHealth + $health, $maxHealth);

        if ($actualHealth == $newHealth) {
            return; // already at full life, no need for notif
        }
        $realDeltaHealth = max(0, $newHealth - $actualHealth);

        $this->DbQuery("UPDATE player SET `player_health` = $newHealth, `player_turn_gained_health` = `player_turn_gained_health` + $realDeltaHealth where `player_id` = $playerId");

        $this->incStat($health, 'heal', $playerId);

        if ($cardType instanceof PowerCard) {
            $cardType = $cardType->type;
        }
        if ($cardType instanceof CurseCard) {
            $cardType = 1000 + $cardType->type;
        }
        if ($cardType instanceof WickednessTile) {
            $cardType = 2000 + $cardType->type;
        }
        if ($cardType instanceof EvolutionCard) {
            $cardType = 3000 + $cardType->type;
        }
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

        if ($playerId == $healerId && $this->cybertoothExpansion->isActive()) {
            $this->cybertoothExpansion->onPlayerHealHimself($playerId);
        }
    }

    private function logDamageBlocked(int $playerId, int $cardType) {
        $this->notifyAllPlayers('damageBlockedLog', clienttranslate('${player_name} prevents damage with ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'card_name' => $cardType,
        ]);
    }

    function applyDamage(object &$damage) {
        $playerId = $damage->playerId;
        $damageDealerId = $damage->damageDealerId;

        $canLoseHealth = $this->canLoseHealth($playerId, $damage->damage);
        if ($canLoseHealth != null) {
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);

            $this->logDamageBlocked($playerId, $canLoseHealth);
            return; // player has golden scarab and cannot lose hearts
        }

        $actualHealth = $this->getPlayerHealth($playerId);

        $damagedPlayerInTokyoBeforeDamage = $this->inTokyo($playerId);

        $newHealth = $this->applyDamageIgnoreCards($damage);

        $isPowerUpExpansion = $this->powerUpExpansion->isActive();

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

            if ($playerId != $damageDealerId && $isPowerUpExpansion) {
                $scytheEvolutions = $this->getEvolutionsOfType($damageDealerId, SCYTHE_EVOLUTION);
                if (count($scytheEvolutions) > 0 && $this->getPlayer($playerId)->eliminated) {
                    foreach($scytheEvolutions as $scytheEvolution) {
                        $this->setEvolutionTokens($damageDealerId, $scytheEvolution, $scytheEvolution->tokens + 1);
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
        if (gettype($damage->cardType) == 'integer' && $damage->cardType == 0 && $newHealth < $actualHealth && $damageDealerId != 0 && $damageDealerId != $playerId && $this->powerUpExpansion->isActive()) {
            $countHeatVision = $this->countEvolutionOfType($playerId, HEAT_VISION_EVOLUTION);
            if ($countHeatVision > 0) {
                $this->applyLosePoints($damageDealerId, $countHeatVision, 3000 + HEAT_VISION_EVOLUTION);
            }
            $countTooCuteToSmash = $this->countEvolutionOfType($playerId, TOO_CUTE_TO_SMASH_EVOLUTION);
            if ($countTooCuteToSmash > 0) {
                $this->applyLosePoints($damageDealerId, $countTooCuteToSmash, 3000 + TOO_CUTE_TO_SMASH_EVOLUTION);
            }
            $countMandiblesOfDread = $this->countEvolutionOfType($damageDealerId, MANDIBLES_OF_DREAD_EVOLUTION);
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
        if ($countReflectiveHide > 0 && (gettype($damage->cardType) != 'integer' || $damage->cardType != REFLECTIVE_HIDE_CARD) && $damageDealerId > 0) { // we avoid infinite loop if mimicked, and reflective on poison tokens
            $reflectiveDamage = new Damage($damageDealerId, $countReflectiveHide, $playerId, REFLECTIVE_HIDE_CARD);
            $this->applyDamage($reflectiveDamage);
        }

        if ($isPowerUpExpansion && $damagedPlayerInTokyoBeforeDamage) { // player may be inside tokyo before damage and outside after (with It has a Child)
            $breathOfDoomEvolutions = $this->getEvolutionsOfType($damageDealerId, BREATH_OF_DOOM_EVOLUTION);
            if (count($breathOfDoomEvolutions) > 0) {
                $usedCards = $this->getUsedCard();
                if (!in_array(3000 + $breathOfDoomEvolutions[0]->id, $usedCards)) {
                    $outsideTokyoPlayersIds = $this->getPlayersIdsOutsideTokyo();
                    foreach ($outsideTokyoPlayersIds as $outsideTokyoPlayerId) {
                        if ($outsideTokyoPlayerId != $damageDealerId && $outsideTokyoPlayerId != $playerId) {
                            $breathOfDoomDamage = new Damage($outsideTokyoPlayerId, count($breathOfDoomEvolutions), $damageDealerId, 3000 + BREATH_OF_DOOM_EVOLUTION);
                            $this->applyDamage($breathOfDoomDamage);
                        }
                    }
                }
            }
            
            foreach ($breathOfDoomEvolutions as $breathOfDoomEvolution) {
                $this->setUsedCard(3000 + $breathOfDoomEvolution->id);
            }
        }

        // only smashes
        if (gettype($damage->cardType) == 'integer' && $damage->cardType == 0 && $damageDealerId != 0 && $playerId != 0) {
            $this->incStat($damage->damage, 'smashesGiven');
            $this->incStat($damage->damage, 'smashesGiven', $damageDealerId);
            $this->incStat($damage->damage, 'smashesReceived', $playerId);
        }
    }

    function applyDamageIgnoreCards(object &$damage) {
        $playerId = $damage->playerId;
        $health = $damage->damage;
        $damageDealerId = $damage->damageDealerId;
        $cardType = $damage->cardType;
        $smasherPoints = $damage->smasherPoints;

        if ($this->canLoseHealth($playerId, $health) != null) {
            $this->removePlayerFromSmashedPlayersInTokyo($playerId);
            return null; // player has wings and cannot lose hearts
        }

        $effectiveDamageDetail = $this->getEffectiveDamage($health, $playerId, $damageDealerId, $damage->clawDamage);
        $effectiveDamage = $effectiveDamageDetail->effectiveDamage;

        $actualHealth = $this->getPlayerHealth($playerId);
        $newHealth = max($actualHealth - $effectiveDamage, 0);

        $damage->effectiveDamage = $effectiveDamage;

        $this->DbQuery("UPDATE player SET `player_health` = $newHealth where `player_id` = $playerId");

        if ($damageDealerId > 0) {
            $this->incStat($effectiveDamage, 'damageDealt', $damageDealerId);
        }
        $this->incStat($effectiveDamage, 'damage', $playerId);

        if ($cardType instanceof CurseCard) {
            $cardType = 1000 + $cardType->type;
        }

        $message = $cardType <= 0 ? '' : clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}');
        $this->notifyAllPlayers('health', $message, [
            'playerId' => $playerId,
            'player_name' => $this->getPlayerName($playerId),
            'health' => $newHealth,
            'delta_health' => $effectiveDamage,
            'card_name' => $cardType == 0 ? null : $cardType,
        ]);

        foreach ($effectiveDamageDetail->logs as $log) {
            $this->notifyAllPlayers('log', $log->message, $log->args);
        }

        // Shrink Ray
        if ($damage->giveShrinkRayToken > 0) {
            $this->applyGetShrinkRayToken($playerId, $damage->giveShrinkRayToken);
        }

        // Poison Spit
        if ($damage->givePoisonSpitToken > 0) {
            $this->applyGetPoisonToken($playerId, $damage->givePoisonSpitToken);
        }

        if ($this->wickednessExpansion->isActive()) {
            $this->wickednessTiles->onApplyDamage(new Context(
                $this, 
                attackerPlayerId: $damageDealerId,
                targetPlayerId: $playerId,
                smasherPoints: $smasherPoints,
            ));
        }
        
        $clawDamages = $damage->clawDamage !== null ? 1 : 0;
        $this->DbQuery("INSERT INTO `turn_damages`(`from`, `to`, `damages`, `claw_damages`)  VALUES ($damageDealerId, $playerId, $effectiveDamage, $clawDamages) ON DUPLICATE KEY UPDATE `damages` = `damages` + $effectiveDamage, `claw_damages` = $clawDamages");

        // pirate
        $pirateCardCount = $this->countCardOfType($damageDealerId, PIRATE_CARD);
        if ($pirateCardCount > 0 && $this->getPlayerEnergy($playerId) >= 1) {
            $this->applyLoseEnergy($playerId, 1, PIRATE_CARD);
            $this->applyGetEnergy($damageDealerId, 1, PIRATE_CARD);
        }

        // must be done before player eliminations
        $finalRoarWillActivate = $this->getPlayerHealth($playerId) == 0 
            && $this->wickednessExpansion->isActive() 
            && $this->wickednessTiles->winOnElimination(new Context($this, currentPlayerId: $playerId)) !== null;

        if (!$finalRoarWillActivate) {
            if ($this->powerUpExpansion->isActive()) {
                if ($this->getPlayerHealth($playerId) == 0) {
                    $sonOfKongKikoEvolutions = $this->getEvolutionsOfType($playerId, SON_OF_KONG_KIKO_EVOLUTION, true, true);
                    if (count($sonOfKongKikoEvolutions) > 0) {
                         $sonOfKongKikoEvolutions[0]->applyEffect(new Context($this, $playerId));
                    }
                }

                if ($this->getPlayerHealth($playerId) == 0) {
                    $nineLivesEvolutions = $this->getEvolutionsOfType($playerId, NINE_LIVES_EVOLUTION, true, true);
                    if (count($nineLivesEvolutions) > 0) {
                        $nineLivesEvolutions[0]->applyEffect(new Context($this, $playerId));
                    }
                }
            }

            if ($this->countCardOfType($playerId, ZOMBIFY_CARD) > 0 && $this->getPlayerHealth($playerId) == 0 && !$this->getPlayer($playerId)->zombified) {
                $this->applyZombify($playerId);
            }
    
            if ($this->countCardOfType($playerId, IT_HAS_A_CHILD_CARD) > 0 && $this->getPlayerHealth($playerId) == 0) {
                // it has a child
                $this->applyItHasAChild($playerId);
            }
        }

        $this->eliminatePlayers($damageDealerId > 0 ? $damageDealerId : intval($this->getActivePlayerId()));

        return $newHealth;
    }

    function getPlayerGettingEnergyOrHeart(int $playerId) {
        $playerGettingEnergyOrHeart = $playerId;

        if ($this->anubisExpansion->isActive() && $this->anubisExpansion->getCurseCardType() == CONFUSED_SENSES_CURSE_CARD) {
            $dieOfFate = $this->anubisExpansion->getDieOfFate();
            if ($dieOfFate->value == 3 && $playerId == intval($this->getActivePlayerId())) {
                $playerIdWithGoldenScarab = $this->anubisExpansion->getPlayerIdWithGoldenScarab();
                if ($playerIdWithGoldenScarab != null) {
                    $playerGettingEnergyOrHeart = $playerIdWithGoldenScarab;
                }
            }
        }

        return $playerGettingEnergyOrHeart;
    }

    function applyGetEnergy(int $playerId, int $energy, int | PowerCard | CurseCard | WickednessTile | EvolutionCard $cardType) {
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

    function applyGetEnergyIgnoreCards(int $playerId, int $energy, int | PowerCard | CurseCard | WickednessTile | EvolutionCard $cardType) {
        $this->DbQuery("UPDATE player SET `player_energy` = `player_energy` + $energy, `player_turn_energy` = `player_turn_energy` + $energy where `player_id` = $playerId");
        
        if ($cardType instanceof PowerCard) {
            $cardType = $cardType->type;
        }
        if ($cardType instanceof CurseCard) {
            $cardType = 1000 + $cardType->type;
        }
        if ($cardType instanceof WickednessTile) {
            $cardType = 2000 + $cardType->type;
        }
        if ($cardType instanceof EvolutionCard) {
            $cardType = 3000 + $cardType->type;
        }
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

    function applyLoseEnergy(int $playerId, int $energy, int | PowerCard | CurseCard | EvolutionCard $cardType) {
        $this->applyLoseEnergyIgnoreCards($playerId, $energy, $cardType);
    }

    function applyLoseEnergyIgnoreCards(int $playerId, int $energy, int | PowerCard | CurseCard | EvolutionCard $cardType) {
        $actualEnergy = $this->getPlayerEnergy($playerId);
        $newEnergy = max($actualEnergy - $energy, 0);
        $this->DbQuery("UPDATE player SET `player_energy` = $newEnergy where `player_id` = $playerId");

        if ($cardType instanceof PowerCard) {
            $cardType = $cardType->type;
        }
        if ($cardType instanceof CurseCard) {
            $cardType = 1000 + $cardType->type;
        }
        if ($cardType instanceof EvolutionCard) {
            $cardType = 3000 + $cardType->type;
        }

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
        $playersIds = $this->getOrderedPlayersIds($damages[0]->damageDealerId, false, true);
        $playersIds = array_values(array_filter($playersIds, fn($playerId) => $this->array_some($damages, fn($damage) => $damage->playerId == $playerId)));
        $cancelDamageIntervention = new CancelDamageIntervention($playersIds, $damages, $damages);
        $cancelDamageIntervention->endState = $endStateId;

        $this->resolveRemainingDamages($cancelDamageIntervention);
    }

    function canDoIntervention(int $playerId, int $damage, int $damageDealerId, $clawDamage) {

        $canDo = $this->countCardOfType($playerId, CAMOUFLAGE_CARD) > 0 || 
            $this->countCardOfType($playerId, ROBOT_CARD) > 0 || 
            $this->countCardOfType($playerId, ELECTRIC_ARMOR_CARD) > 0 || 
            ($this->countCardOfType($playerId, WINGS_CARD) > 0 && $this->canLoseHealth($playerId, $damage) == null) ||
            ($this->powerUpExpansion->isActive() && ($this->countEvolutionOfType($playerId, DETACHABLE_TAIL_EVOLUTION, false, true) > 0 || $this->countEvolutionOfType($playerId, RABBIT_S_FOOT_EVOLUTION, false, true) > 0 || $this->countEvolutionOfType($playerId, SO_SMALL_EVOLUTION, true, true) > 0 || $this->countEvolutionOfType($playerId, TERROR_OF_THE_DEEP_EVOLUTION, true, true) > 0 || $this->countEvolutionOfType($playerId, CANDY_EVOLUTION, true, true) > 0)) ||
            $this->countUnusedCardOfType($playerId, SUPER_JUMP_CARD) > 0;

        if ($canDo) {
            return true;
        } else {
            $playerHealth = $this->getPlayerHealth($playerId);

            $totalDamage = $this->getEffectiveDamage($damage, $playerId, $damageDealerId, $clawDamage)->effectiveDamage;

            if ($playerHealth <= $totalDamage) {
                $rapidHealingHearts = $this->cancellableDamageWithRapidHealing($playerId);
                $superJumpHearts = $this->cancellableDamageWithSuperJump($playerId);
                $rapidHealingCultists = $this->cthulhuExpansion->isActive() ? $this->cthulhuExpansion->cancellableDamageWithCultists($playerId) : 0;
                $damageToCancelToSurvive = $this->getDamageToCancelToSurvive($totalDamage, $playerHealth);
                $healWithEvolutions = 0;
                if ($damageToCancelToSurvive > 0 && $this->powerUpExpansion->isActive()) {
                    foreach($this->EVOLUTIONS_TO_HEAL as $evolutionType => $amount) {
                        $count = $this->countEvolutionOfType($playerId, $evolutionType, false, true);
    
                        if ($count > 0) {
                            $healWithEvolutions += $count * ($amount === null ? 999 : $amount); 
                        } 
                    }
                }
                $canHeal = $rapidHealingHearts + $rapidHealingCultists + $superJumpHearts + $healWithEvolutions;
                if ($this->countCardOfType($playerId, REGENERATION_CARD)) {
                    $canHeal *= 2;
                }
                
                return $canHeal > 0 && $canHeal >= $damageToCancelToSurvive;
            } else {
                return false;
            }
        }
    }

    function resolveRemainingDamages(object $intervention, bool $endOfCurrentPlayer = false, bool $fromCancelDamageState = false) {
        // if there is no more player to handle, end this state
        if (count($intervention->remainingPlayersId) == 0) {
            if ($this->powerUpExpansion->isActive()) {
                $this->setDamageIntervention($intervention);
                $this->goToState(ST_MULTIPLAYER_AFTER_RESOLVE_DAMAGE);
                return;
            }

            $this->deleteGlobalVariable(CANCEL_DAMAGE_INTERVENTION.$this->getStackedStateSuffix());
            $this->goToState($intervention->endState);
            return;
        }

        $currentPlayerId = $intervention->remainingPlayersId[0];

        // if current player is already eliminated, we ignore it
        if ($endOfCurrentPlayer || $this->getPlayer($currentPlayerId)->eliminated) {
            array_shift($intervention->remainingPlayersId);
            $this->resolveRemainingDamages($intervention, $fromCancelDamageState);
            return;
        }

        $currentDamage = $currentPlayerId !== null ? 
            $this->array_find($intervention->damages, fn($damage) => $damage->playerId == $currentPlayerId) : null;

        // if player will block damage, or he can not block damage anymore, we apply damage and remove it from remainingPlayersId
        if ($currentDamage 
            && ($this->canLoseHealth($currentPlayerId, $currentDamage->remainingDamage) !== null
                || !$this->canDoIntervention($currentPlayerId, $currentDamage->remainingDamage, $currentDamage->damageDealerId, $currentDamage->clawDamage))
        ) {
            $this->applyDamages($intervention, $currentPlayerId);
            $this->resolveRemainingDamages($intervention, true, false);
            return;
        }

        // if we are still here, player have cards to cancel/reduce damage. We check if he have enough energy to use them
        if ($this->autoSkipImpossibleActions()) {
            $arg = $this->argCancelDamage($currentPlayerId, $intervention);
            if (!$arg['canDoAction'] || !$arg['canCancelDamage'] && $arg['damageToCancelToSurvive'] <= 0) {
                $this->applyDamages($intervention, $currentPlayerId);
                $this->resolveRemainingDamages($intervention, true, false);
                return;
            }
        }

        // if we are still here, no action has been done automatically, we activate the player so he can heal
        $this->setDamageIntervention($intervention);
        if (!$fromCancelDamageState) {
            $this->goToState(ST_MULTIPLAYER_CANCEL_DAMAGE);
        }
        return true;
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

    function damageDealtToAnotherPlayerThisTurn(int $playerId, int $toPlayerId) {
        return intval($this->getUniqueValueFromDB( "SELECT SUM(`damages`) FROM `turn_damages` WHERE `from` = $playerId and `to` = $toPlayerId"));
    }

    function playersWoundedByActivePlayerThisTurn(int $playerId) {
        $dbResults = $this->getCollectionFromDb("SELECT `to` FROM `turn_damages` WHERE `from` = $playerId AND `claw_damages` = 1");
        return array_map(fn($dbResult) => intval($dbResult['to']), array_values($dbResults));
    }

    function placeNewCardsOnTable() {
        $cards = [];
        
        for ($i=1; $i<=3; $i++) {
            $cards[] = $this->powerCards->pickCardForLocation('deck', 'table', $i);
        }

        return $cards;
    }

    function getPlayersIdsWithMaxColumn(string $column) {
        $sql = "SELECT player_id FROM player WHERE player_eliminated = 0 AND player_dead = 0 AND `$column` = (select max(`$column`) FROM player WHERE player_eliminated = 0 AND player_dead = 0) ORDER BY player_no";
        $dbResults = $this->getCollectionFromDb($sql);
        return array_map(fn($dbResult) => intval($dbResult['player_id']), array_values($dbResults));
    }

    function keepAndEvolutionCardsHaveEffect(): bool {
        if ($this->anubisExpansion->isActive()) {
            return $this->anubisExpansion->keepAndEvolutionCardsHaveEffect();
        }

        return true;
    }

    function changeAllPlayersMaxHealth(): void {
        $playerIds = $this->getPlayersIds();
        foreach($playerIds as $playerId) {
            $this->changeMaxHealth($playerId);
        }
    }
}
