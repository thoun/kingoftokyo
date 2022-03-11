<?php
namespace KOT\Objects;


class Question {
    public string $code;
    public string $description;
    public string $descriptionmyturn;
    public array $playersIds;
    public int $stateIdAfter;
    public $args;

    public function __construct(
        string $code,
        string $description,
        string $descriptionmyturn,
        array $playersIds,
        int $stateIdAfter,
        $args = null
    ) {
        $this->code = $code;
        $this->description = $description;
        $this->descriptionmyturn = $descriptionmyturn;
        $this->playersIds = $playersIds;
        $this->stateIdAfter = $stateIdAfter;
        $this->args = $args;
    } 
}
?>