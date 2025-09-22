<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Counters;

/**
 * Represents a player counter that is stored in DB, one value for each player. For example, the money the player have.
 */
class PlayerCounter {
    protected static ?bool $tableExists = null;

    /**
     * Instanciate the counter. Must be called during game `__construct`.
     * 
     * @param Table $game the Game class
     * @param string $type the name of the counter. Set to 'player_score' / 'player_score_aux' to update the values used by the framework for the scoring.
     * @param ?int $min the minimal value, default 0.
     * @param ?int $max the maximal value, default unset (null).
     * @param int $defaultValue the default value, default 0.
     */
    public function __construct(protected \Bga\GameFramework\Table $game, protected string $type, protected ?int $min = 0, protected ?int $max = null, protected int $defaultValue = 0) {
    }

    protected function isPlayerTableField(): bool {
        return in_array($this->type, ['player_score', 'player_score_aux']);
    }

    protected function getTable(): string {
        return $this->isPlayerTableField() ? 'player' : 'bga_player_counters';
    }

    /**
     * Initialize the DB elements. Must be called during game `setupNewGame`.
     */
    public function initDb(array $playersIds) {
        if ($this->isPlayerTableField()) {
            return;
        }

        if (self::$tableExists === null) {
            /** @disregard */
            self::$tableExists = (bool)\APP_DbObject::getUniqueValueFromDB("SHOW TABLES LIKE 'bga_player_counters'");
        }
        if (!self::$tableExists) {
            $sql = <<<SQL
                CREATE TABLE IF NOT EXISTS `bga_player_counters` (
                    `player_id` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`player_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            SQL;
            /** @disregard */
            \APP_DbObject::DbQuery( $sql );

            if (count($playersIds) > 0) {
                /** @disregard */
                \APP_DbObject::DbQuery(
                    sprintf(
                        "INSERT INTO `bga_player_counters` (`player_id`) VALUES %s",
                        implode(",", array_map(fn($playerId) => "($playerId)", $playersIds))
                    )
                );
            }

            self::$tableExists = true;
        }

        /** @disregard */
        \APP_DbObject::DbQuery("ALTER TABLE `bga_player_counters` ADD `{$this->type}` INT NOT NULL DEFAULT {$this->defaultValue}");
    }

    /**
     * Returns the current value of the counter.
     * 
     * @param int $playerId the player id
     * @return int the value
     */
    public function get(int $playerId): int {
        /** @disregard */
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT `{$this->type}` FROM `{$this->getTable()}` WHERE `player_id` = $playerId");
    }

    /**
     * Set the value of the counter, and send a notif to update the value on the front side.
     * 
     * @param int $playerId the player id
     * @param int $value the new value
     * @param ?string $message the next notif to send to the front. Empty for no log, null for no notif at all (the front will not be updated).
     * @param array $customArgs the additional args to add to the notification message. `type`, `value` and `oldValue` are sent by default.
     * @return int the new value
     * @throws BgaSystemException if the value is outside the min/max
     */
    public function set(int $playerId, int $value, ?string $message = '', array $customArgs = []): int {
        if ($this->min !== null && $value < $this->min) {
            throw new \BgaSystemException("The counter value cannot be under {$this->min} (player counter: {$this->type}, value: {$value}, min: {$this->min})");
        }
        if ($this->max !== null && $value > $this->max) {
            throw new \BgaSystemException("The counter value cannot be over {$this->max} (player counter: {$this->type}, value: {$value}, max: {$this->max})");
        }

        $before = $this->get($playerId);

        /** @disregard */
        \APP_DbObject::DbQuery("UPDATE `{$this->getTable()}` SET `{$this->type}` = $value WHERE `player_id` = $playerId");

        if ($message !== null) {
            $notifArgs = [ // $customArgs before, + doesn't erase
                'playerId' => $playerId,
                'type' => $this->type,
                'value' => $value,
                'oldValue' => $before,
            ];

            if ($message !== null && str_contains($message, '${player_name}')) {
                $notifArgs['player_name'] = $this->game->getPlayerNameById($playerId);
            }

            $args = $customArgs + $notifArgs; // $customArgs before, + doesn't erase;
                
            $this->game->notify->all('setPlayerCounter', $message, $args);
        }

        return $value;
    }

    /**
     * Increment the value of the counter, and send a notif to update the value on the front side.
     * 
     * Note: if the inc is 0, no notif will be sent.
     * 
     * @param int $playerId the player id
     * @param int $inc the value to add to the current value
     * @param ?string $message the next notif to send to the front. Empty for no log, null for no notif at all (the front will not be updated).
     * @param array $customArgs the additional args to add to the notification message. `type`, `value`, `oldValue`, `inc`, `absInc` are sent by default.
     * @return int the new value
     * @throws BgaSystemException if the value is outside the min/max
     */
    public function inc(int $playerId, int $inc, ?string $message = '', array $customArgs = []): int {
        $before = $this->get($playerId);
        if ($inc === 0) {
            // no change, no need to notif
            return $before;
        } else {
            return $this->set($playerId, $before + $inc, $message, ['inc' => $inc, 'absInc' => abs($inc), ] + $customArgs);
        } 
    }

    /**
     * Return the lowest value.
     * 
     * @return int the lowest value
     */
    public function getMin(): int {
        /** @disregard */
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT MIN(`".$this->type."`) FROM `{$this->getTable()}`");
    }

    /**
     * Return the highest value.
     * 
     * @return int the highest value
     */
    public function getMax(): int {
        /** @disregard */
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT MAX(`".$this->type."`) FROM `{$this->getTable()}`");
    }
    
    /**
     * Return the values for each player, as an associative array $playerId => $value.
     * 
     * @return array<int, int> the values
     */
    public function getAll(): array {
        /** @disregard */
        $values = \APP_DbObject::getCollectionFromDB("SELECT `player_id`, `{$this->type}` FROM `{$this->getTable()}`", true);
        return array_map(fn($val) => (int)$val, $values);
    }

    /**
     * Set the value of the counter for all the players, and send a notif to update the value on the front side.
     * 
     * @param int $value the new value
     * @param ?string $message the next notif to send to the front. Empty for no log, null for no notif at all (the front will not be updated).
     * @param array $customArgs the additional args to add to the notification message. `type`, `value` and `oldValue` are sent by default.
     * @return int the new value
     * @throws BgaSystemException if the value is outside the min/max
     */
    public function setAll(int $value, ?string $message = '', array $customArgs = []): int {
        if ($this->min !== null && $value < $this->min) {
            throw new \BgaSystemException("The counter value cannot be under {$this->min} (player counter: {$this->type}, value: {$value}, min: {$this->min})");
        }
        if ($this->max !== null && $value > $this->max) {
            throw new \BgaSystemException("The counter value cannot be over {$this->max} (player counter: {$this->type}, value: {$value}, max: {$this->max})");
        }

        /** @disregard */
        \APP_DbObject::DbQuery("UPDATE `{$this->getTable()}` SET `{$this->type}` = $value");

        if ($message !== null) {
            $args = $customArgs + [ // $customArgs before, + doesn't erase
                'type' => $this->type,
                'value' => $value,
            ];
                
            $this->game->notify->all('setPlayerCounterAll', $message, $args);
        }

        return $value;
    }

    /**
     * Updates the result object, to be used in the `getAllDatas` function.
     * Will set the value on each $result["players"] sub-array.
     * 
     * @param array $result the object to update.
     * @param ?string $fieldName the field name to set in $result["players"], if different than the counter `type`.
     */
    public function fillResult(array &$result, ?string $fieldName = null) {
        $values = $this->getAll();

        foreach ($result["players"] as $playerId => &$player) {
            $player[$fieldName ?? $this->type] = $values[$playerId];
        }
    }
}
