<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Counters;

/**
 * Represents a game counter that is stored in DB. For example, the number of rounds.
 */
class GlobalCounter {
    protected static ?bool $tableExists = null;

    /**
     * Instanciate the counter. Must be called during game `__construct`.
     * 
     * @param Table $game the Game class
     * @param string $type the name of the counter. 'player_score' or 'player_score_aux' for the special counters.
     * @param ?int $min the minimal value, default 0.
     * @param ?int $max the maximal value, default unset (null).
     * @param int $defaultValue the default value, default 0.
     */
    function __construct(protected \Bga\GameFramework\Table $game, protected string $type, private ?int $min = 0, protected ?int $max = null, protected int $defaultValue = 0) {
    }

    /**
     * Initialize the DB elements. Must be called during game `setupNewGame`.
     */
    public function initDb() {
        if (self::$tableExists === null) {
            /** @disregard */
            self::$tableExists = (bool)\APP_DbObject::getUniqueValueFromDB("SHOW TABLES LIKE 'bga_global_counters'");
        }
        if (!self::$tableExists) {
            $sql = <<<SQL
                CREATE TABLE IF NOT EXISTS `bga_global_counters` (
                    `name` varchar(50) NOT NULL,
                    `value` int(11) NOT NULL,
                    PRIMARY KEY (`player_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            SQL;
            /** @disregard */
            \APP_DbObject::DbQuery( $sql );

            self::$tableExists = true;
        }

        /** @disregard */
        \APP_DbObject::DbQuery("INSERT INTO `bga_global_counters` (`name`, `value`) VALUES  ('`{$this->type}`', {$this->defaultValue})");
    }

    /**
     * Returns the current value of the counter.
     * 
     * @return int the value
     */
    function get(): int {
        /** @disregard */
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT `value` FROM `bga_player_counters` WHERE `name` = '{$this->type}'");
    }

    /**
     * Set the value of the counter, and send a notif to update the value on the front side.
     * 
     * @param int $value the new value
     * @param ?string $message the next notif to send to the front. Empty for no log, null for no notif at all (the front will not be updated).
     * @param array $customArgs the additional args to add to the notification message. `type`, `value` and `oldValue` are sent by default.
     * @return int the new value
     * @throws BgaSystemException if the value is outside the min/max
     */
    function set(int $value, ?string $message = '', array $customArgs = []): int {
        if ($this->min !== null && $value < $this->min) {
            throw new \BgaSystemException("The counter value cannot be under {$this->min} (global counter: {$this->type}, value: {$value}, min: {$this->min})");
        }
        if ($this->max !== null && $value > $this->max) {
            throw new \BgaSystemException("The counter value cannot be over {$this->max} (global counter: {$this->type}, value: {$value}, max: {$this->max})");
        }

        $before = $this->get();

        /** @disregard */
        \APP_DbObject::DbQuery("UPDATE `bga_global_counters` SET `value` = $value WHERE `name` = '{$this->type}'");

        $notifArgs = [ // $customArgs before, + doesn't erase
            'type' => $this->type,
            'value' => $value,
            'oldValue' => $before,
        ];

        $args = $customArgs + $notifArgs; // $customArgs before, + doesn't erase;
            
        $this->game->notify->all('setGlobalCounter', $message, $args);

        return $value;
    }

    /**
     * Increment the value of the counter, and send a notif to update the value on the front side.
     * 
     * Note: if the inc is 0, no notif will be sent.
     * 
     * @param int $inc the value to add to the current value
     * @param ?string $message the next notif to send to the front. Empty for no log, null for no notif at all (the front will not be updated).
     * @param array $customArgs the additional args to add to the notification message. `type`, `value`, `oldValue`, `inc`, `absInc` are sent by default.
     * @return int the new value
     * @throws BgaSystemException if the value is outside the min/max
     */
    function inc(int $inc, ?string $message = '', array $customArgs = []): int {
        $before = $this->get();
        if ($inc === 0) {
            // no change, no need to notif
            return $before;
        } else {
            return $this->set($before + $inc, $message, ['inc' => $inc, 'absInc' => abs($inc), ] + $customArgs);
        } 
    }

    /**
     * Updates the result object, to be used in the `getAllDatas` function.
     * 
     * @param array $result the object to update.
     * @param ?string $fieldName the field name to set in $result, if different than the counter `type`.
     */
    public function fillResult(array &$result, ?string $fieldName = null) {
        $value = $this->get();

        $result[$fieldName ?? $this->type] = $value;
    }
}
