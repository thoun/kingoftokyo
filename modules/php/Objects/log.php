<?php
namespace KOT\Objects;

class Log {
    public string $message;
    public array $args;

    public function __construct(
        string $message,
        array $args
    ) {
        $this->message = $message;
        $this->args = $args;
    } 
}

class LoseHealthLog extends Log {
    public string $message;
    public array $args;

    public function __construct($game, int $playerId, int $deltaHealth, int $logCardName) {
        parent::__construct(
            clienttranslate('${player_name} loses ${delta_health} [Heart] with ${card_name}'), 
            [
                'playerId' => $playerId,
                'player_name' => $game->getPlayerNameById($playerId),
                'delta_health' => $deltaHealth,
                'card_name' => $logCardName,
            ]
        );
    } 
}

?>