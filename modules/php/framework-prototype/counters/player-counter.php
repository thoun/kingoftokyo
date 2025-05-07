<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Counters;

class PlayerCounter {
    protected static ?bool $tableExists = null;

    public function __construct(private $game, private string $dbField, private ?string $type = null, private ?int $min = null, private ?int $max = null, private int $defaultValue = 0) {
        $this->type = $type ?? $dbField;
    }

    protected function isPlayerTableField(): bool {
        return in_array($this->dbField, ['player_score', 'player_score_aux']);
    }

    protected function getTable(): string {
        return $this->isPlayerTableField() ? 'player' : 'bga_player_counters';
    }

    public function initDb(array $playersIds) {
        if ($this->isPlayerTableField()) {
            return;
        }

        if (self::$tableExists === null) {
            self::$tableExists = (bool)\APP_DbObject::getUniqueValueFromDB("SHOW TABLES LIKE 'bga_player_counters'");
        }
        if (!self::$tableExists) {
            $sql = <<<SQL
                CREATE TABLE IF NOT EXISTS `bga_player_counters` (
                    `player_id` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`player_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
            SQL;
            \APP_DbObject::DbQuery( $sql );

            if (count($playersIds) > 0) {
                \APP_DbObject::DbQuery(
                    sprintf(
                        "INSERT INTO `bga_player_counters` (`player_id`) VALUES %s",
                        implode(",", array_map(fn($playerId) => "($playerId)", $playersIds))
                    )
                );
            }

            self::$tableExists = true;
        }

        \APP_DbObject::DbQuery("ALTER TABLE `bga_player_counters` ADD `{$this->dbField}` INT NOT NULL DEFAULT {$this->defaultValue}");
    }

    public function get(int $playerId): int {
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT `{$this->dbField}` FROM `{$this->getTable()}` WHERE `player_id` = $playerId");
    }

    public function set(int $playerId, int $value, ?string $message = '', array $customArgs = []): int {
        $before = $this->get($playerId);
        $after = $value;
        if ($this->min !== null) {
            $after = max($this->min, $after);
        }
        if ($this->max !== null) {
            $after = min($this->max, $after);
        }

        \APP_DbObject::DbQuery("UPDATE `{$this->getTable()}` SET `{$this->dbField}` = $after WHERE `player_id` = $playerId");

        if ($message !== null) {
            $args = $customArgs + [ // $customArgs before, + doesn't erase
                'playerId' => $playerId,
                'player_name' => $this->game->getPlayerNameById($playerId),
                'type' => $this->type, // for logs
                'value_before' => $before, // for logs
                'value' => $after, // for logs
                'data' => [
                    'type' => $this->type,
                    'value_before' => $before,
                    'value' => $after,
                ]
            ];
                
            $this->game->notify->all('setPlayerCounter', $message, $args);
        }

        return $after;
    }

    public function inc(int $playerId, int $inc, ?string $message = '', array $customArgs = []): int {
        $before = $this->get($playerId);
        if ($inc === 0) {
            // no change, no need to notif
            return $before;
        } else {
            return $this->set($playerId, $before + $inc, $message, ['inc' => $inc, 'abs_inc' => abs($inc), ] + $customArgs);
        } 
    }

    public function getMin(): int {
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT MIN(`".$this->dbField."`) FROM `{$this->getTable()}`");
    }
    public function getMax(): int {
        return (int)\APP_DbObject::getUniqueValueFromDB("SELECT MAX(`".$this->dbField."`) FROM `{$this->getTable()}`");
    }

    public function fillResult(array &$result, ?string $fieldName = null) {
        $values = \APP_DbObject::getCollectionFromDB("SELECT `player_id`, `{$this->dbField}` FROM `{$this->getTable()}`", true);

        foreach ($result["players"] as $playerId => &$player) {
            $player[$fieldName ?? $this->type] = (int)$values[$playerId];
        }
    }
}
