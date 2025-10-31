<?php
namespace KOT\Objects;


class Question {
    public function __construct(
        public string $code,
        public string $description,
        public string $descriptionmyturn,
        public array $playersIds,
        public int $stateIdAfter = -1,
        public ?array $args = null,  // if special array key "_args" is set, it's given to state args
        public ?int $cardId = null,
        public ?int $evolutionId = null,
    ) {
    } 
}
?>