<?php
namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use \Bga\GameFrameworkPrototype\Item\Item;
use \Bga\GameFrameworkPrototype\Item\ItemField;
use Bga\Games\KingOfTokyo\Objects\Context;

const PERMANENT = 1;
const TEMPORARY = 2;
const GIFT = 3;

#[Item('evolution_card')]
class EvolutionCard {
    #[ItemField(kind: 'id', dbField: 'card_id')]
    public int $id;
    #[ItemField(kind: 'location', dbField: 'card_location')]
    public string $location;
    #[ItemField(kind: 'location_arg', dbField: 'card_location_arg')]
    public ?int $location_arg;
    #[ItemField(dbField: 'card_type')]
    public int $type;
    #[ItemField(dbField: 'card_type_arg')]
    public int $tokens = 0;
    #[ItemField(kind: 'order')]
    public ?int $order;
    
    #[ItemField(dbField: 'owner_id')]
    public ?int $ownerId;

    // permanent, temporary or gift
    public int $evolutionType;

    public ?array $mindbugKeywords = null;

    // if this card is virtual, indicates the id of the icy reflection evolution. It's id will be negative the real card id.
    public ?int $mimickingEvolutionId = null;
    
    public static function createBackCard(int $id) {
        $public = new EvolutionCard();
        $public->id = $id;
        $public->location = '';
        $public->location_arg = 0;
        $public->type = 0;
        $public->tokens = 0;
        $public->ownerId = 0;

        return $public;
    }

    public function checkCanPlay(Context $context) {
        if ($this->mindbugKeywords !== null) {
            throw new \BgaUserException(clienttranslate("You can only play this evolution card by playing the associated keyword"));
        }

        if ($context->game->EVOLUTION_CARDS_TYPES[$this->type] == 3) {
            throw new \BgaUserException(/*clienttranslateTODOPUHA*/("You can only play this evolution now, you'll be asked to use it when you wound a Monster"));
        }

        switch($this->type) {
            // TODO in those classes, override checkCanPlay to also check the specific of this card
            case NINE_LIVES_EVOLUTION:
            case SIMIAN_SCAMPER_EVOLUTION:
            case DETACHABLE_TAIL_EVOLUTION:
            case RABBIT_S_FOOT_EVOLUTION:
                throw new \BgaUserException(clienttranslate("You can't play this Evolution now, you'll be asked to use it when you'll take damage"));
            case SAURIAN_ADAPTABILITY_EVOLUTION:
                $message = $context->game->gamestate->getCurrentMainStateId() === ST_PLAYER_CHANGE_DIE ? 
                    clienttranslate("Click on a die face you want to change") :
                    clienttranslate("You can't play this Evolution now, you'll be asked to use it when you change your dice result");
                throw new \BgaUserException($message);
            case FELINE_MOTOR_EVOLUTION:
                $startedTurnInTokyo = $context->game->getGlobalVariable(STARTED_TURN_IN_TOKYO, true);
                if (in_array($context->currentPlayerId, $startedTurnInTokyo)) {
                    throw new \BgaUserException(clienttranslate("You started your turn in Tokyo"));
                }
                break;
            case TWAS_BEAUTY_KILLED_THE_BEAST_EVOLUTION:
            case EATS_SHOOTS_AND_LEAVES_EVOLUTION:
                if (!$context->game->inTokyo($context->currentPlayerId)) {
                    throw new \BgaUserException(clienttranslate("You can play this Evolution only if you are in Tokyo"));
                }
                break;
            case JUNGLE_FRENZY_EVOLUTION:
                if ($context->currentPlayerId != intval($context->game->getActivePlayerId())) {
                    throw new \BgaUserException(clienttranslate("You must play this Evolution during your turn"));
                }
                if ($context->game->inTokyo($context->currentPlayerId)) {
                    throw new \BgaUserException(clienttranslate("You can play this Evolution only if you are not in Tokyo"));
                }
                if (!$context->game->isDamageDealtThisTurn($context->currentPlayerId)) {
                    throw new \BgaUserException(clienttranslate("You didn't deal damage to a player in Tokyo"));
                }
                break;
            case TUNE_UP_EVOLUTION:
                if ($context->game->inTokyo($context->currentPlayerId)) {
                    throw new \BgaUserException(clienttranslate("You can play this Evolution only if you are not in Tokyo"));
                }
                break;
            case BLIZZARD_EVOLUTION:
                if ($context->currentPlayerId != intval($context->game->getActivePlayerId())) {
                    throw new \BgaUserException(clienttranslate("You must play this Evolution during your turn"));
                }
                break;
            case ICY_REFLECTION_EVOLUTION:
                $playersIds = $context->game->getPlayersIds();
                $canPlayIcyReflection = false;
                foreach($playersIds as $context->currentPlayerId) {
                    $evolutions = $context->game->getEvolutionCardsByLocation('table', $context->currentPlayerId);
                    if (Arrays::some($evolutions, fn($evolution) => $evolution->type != ICY_REFLECTION_EVOLUTION && $context->game->EVOLUTION_CARDS_TYPES[$evolution->type] == 1)) {
                        $canPlayIcyReflection = true; // if there is a permanent evolution card in table
                    }
                }
                if (!$canPlayIcyReflection) {
                    throw new \BgaUserException(clienttranslate("You can only play this evolution card when there is another permanent Evolution on the table"));
                }
                break;
        }
    }
}
?>