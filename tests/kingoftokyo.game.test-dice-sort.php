<?php
define("APP_GAMEMODULE_PATH", "../misc/"); // include path to stubs, which defines "table.game.php" and other classes
require_once ('../kingoftokyo.game.php');

use KOT\Objects\Dice;

class KingOfTokyoTestDiceSort extends KingOfTokyoPowerUp { // this is your game class defined in ggg.game.php
    function __construct() {
        // parent::__construct();
        include '../material.inc.php';// this is how this normally included, from constructor
    }

    function getTestDice(bool $addType1, bool $addType2) {
        $dice = [
            new Dice(['dice_value' => 6]),
            new Dice(['dice_value' => 5]),
            new Dice(['dice_value' => 1]),
            new Dice(['dice_value' => 3]),
            new Dice(['dice_value' => 6]),
            new Dice(['dice_value' => 2]),
        ];

        if ($addType2) {
            $dice[] = new Dice(['dice_value' => 1, 'type' => 2]);
            $dice[] = new Dice(['dice_value' => 3]);
        }

        if ($addType1) {
            $dice[] = new Dice(['dice_value' => 1, 'type' => 1]);
            $dice[] = new Dice(['dice_value' => 3]);
        }

        return $dice;
    }

    // class tests
    function testType0() {
        $dice = $this->getTestDice(false, false);
        echo 'dice before = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";

        usort($dice, "static::sortDieFunction");

        echo 'dice after = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";
    }

    // class tests
    function testType1() {
        $dice = $this->getTestDice(true, false);
        echo 'dice before = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";

        usort($dice, "static::sortDieFunction");

        echo 'dice after = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";
    }

    // class tests
    function testType2() {
        $dice = $this->getTestDice(false, true);
        echo 'dice before = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";

        usort($dice, "static::sortDieFunction");

        echo 'dice after = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";
    }

    // class tests
    function testType12() {
        $dice = $this->getTestDice(true, true);
        echo 'dice before = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";

        usort($dice, "static::sortDieFunction");

        echo 'dice after = '.json_encode($dice, JSON_PRETTY_PRINT)."\n";
    }

    function testAll() {
        $this->testType0();
        $this->testType1();
        $this->testType2();
        $this->testType12();
    }
}

$test1 = new KingOfTokyoTestDiceSort();
$test1->testAll();