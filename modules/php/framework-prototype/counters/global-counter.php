<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Counters;

class GlobalCounter {
    protected static ?bool $tableExists = null;

    function __construct(private $game, private string $dbField, private ?string $type = null, private ?int $min = null, private ?int $max = null, private int $defaultValue = 0) {
        $this->type = $type ?? $dbField;
    }

    public function initDb() {
        if (self::$tableExists === null) {
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
            \APP_DbObject::DbQuery( $sql );

            self::$tableExists = true;
        }

        \APP_DbObject::DbQuery("INSERT INTO `bga_global_counters` (`name`, `value`) VALUES  ('`{$this->dbField}`', {$this->defaultValue})");
    }

    function get(): int {
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT `value` FROM `bga_player_counters` WHERE `name` = '{$this->dbField}'");
    }

    function set(int $value, ?string $message = '', array $customArgs = []): int {
        $before = $this->get();
        $after = $value;
        if ($this->min !== null) {
            $after = max($this->min, $after);
        }
        if ($this->max !== null) {
            $after = min($this->max, $after);
        }

        \APP_DbObject::DbQuery("UPDATE `bga_global_counters` SET `value` = $after WHERE `name` = '{$this->dbField}'");

        $args = $customArgs + [ // $customArgs before, + doesn't erase
            'type' => $this->type, // for logs
            'value_before' => $before, // for logs
            'value' => $after, // for logs
            'data' => [
                'type' => $this->type,
                'value_before' => $before,
                'value' => $after,
            ]
        ];
            
        $this->game->notify->all('setGlobalCounter', $message, $args);

        return $after;
    }

    function inc(int $inc, ?string $message = '', array $customArgs = []): int {
        $before = $this->get();
        if ($inc === 0) {
            // no change, no need to notif
            return $before;
        } else {
            return $this->set($before + $inc, $message, ['inc' => $inc, 'abs_inc' => abs($inc), ] + $customArgs);
        } 
    }
}
