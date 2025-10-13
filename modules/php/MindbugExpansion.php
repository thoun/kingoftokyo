<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFramework\Components\Counters\PlayerCounter;
use Bga\GameFramework\NotificationMessage;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\ActivatedConsumableKeyword;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;
use KOT\Objects\Damage;
use KOT\Objects\Question;

use const Bga\Games\KingOfTokyo\PowerCards\FRENZY;
use const Bga\Games\KingOfTokyo\PowerCards\HUNTER;
use const Bga\Games\KingOfTokyo\PowerCards\POISON;
use const Bga\Games\KingOfTokyo\PowerCards\SNEAKY;
use const Bga\Games\KingOfTokyo\PowerCards\TOUGH;

const ACTIVATED_HUNTER_CARDS = 'ACTIVATED_HUNTER_CARDS';
const ACTIVATED_SNEAKY_CARDS = 'ACTIVATED_SNEAKY_CARDS';
const ACTIVATED_FRENZY_CARDS = 'ACTIVATED_FRENZY_CARDS';

class MindbugExpansion {
    public PlayerCounter $mindbugTokens;

    function __construct(
        protected Game $game,
    ) {
        $this->mindbugTokens = $game->counterFactory->createPlayerCounter('mindbugTokens');
    }

    public function isActive(): bool {
        return $this->game->tableOptions->get(MINDBUG_EXPANSION_OPTION) > 0 || Game::getBgaEnvironment() === 'studio';
    }

    public function getMindbugCardsSetting() {
        if (!$this->isActive()) {
            return 0;
        }
        return $this->game->tableOptions->get(MINDBUG_CARDS_OPTION) ?? 0; 
    }

    public function initDb(array $playerIds): void {
        $this->mindbugTokens->initDb($playerIds);  
    }

    // only called if expansion isActive
    public function setup(): void {
        $this->mindbugTokens->setAll(1);
    }

    public function fillResult(array &$result): void {
        $this->mindbugTokens->fillResult($result);

        $result['mindbug'] = null;
        $mindbuggedPlayerId = $this->game->globals->get(MINDBUGGED_PLAYER);
        if ($mindbuggedPlayerId !== null) {
            $result['mindbug'] = [
                'activePlayerId' => (int)$this->game->getActivePlayerId(),
                'mindbuggedPlayerId' => $mindbuggedPlayerId,
            ];
        }
    }

    public function setMindbuggedPlayer(int $newActivePlayerId, ?int $mindbuggedPlayerId): void {
        $this->game->gamestate->changeActivePlayer($newActivePlayerId);

        $this->game->globals->set(MINDBUGGED_PLAYER, $mindbuggedPlayerId);

        $this->game->notify->all("mindbugPlayer", /*TODOMB clienttranslate*/('${player_name} mindbugs ${player_name2}!'), [
            'activePlayerId' => $newActivePlayerId,
            'mindbuggedPlayerId' => $mindbuggedPlayerId,
            'player_name' => $this->game->getPlayerNameById($newActivePlayerId),
            'player_name2' => $this->game->getPlayerNameById($mindbuggedPlayerId),
        ]);
    }

    public function getMindbuggedPlayer(): ?int {
        return $this->game->globals->get(MINDBUGGED_PLAYER);
    }

    public function canGetExtraTurn(): bool {
        return $this->getMindbuggedPlayer() === null;
    }

    public function getPlayersThatCanMindbug(int $activePlayerId): array {
        if (!$this->isActive()) {
            return [
            'canMindbug' => [],
            'canUseToken' => [],
            'canUseEvasiveMindbug' => [],
        ];
        }
        
        $otherPlayerIds = $this->game->getOtherPlayersIds($activePlayerId);
        $mindbugTokens = $this->mindbugTokens->getAll();

        $canUseEvasiveMindbug = [];
        $canUseToken = Arrays::filter($otherPlayerIds, fn($playerId) => $mindbugTokens[$playerId] > 0);

        foreach ($otherPlayerIds as $playerId) {
            $countEvasiveMindbug = $this->game->countCardOfType($playerId, EVASIVE_MINDBUG_CARD);
            if ($countEvasiveMindbug > 0) {
                $canUseEvasiveMindbug[] = $playerId;
            }
        }

        return [
            'canMindbug' => Arrays::unique(array_merge($canUseToken, $canUseEvasiveMindbug)),
            'canUseToken' => $canUseToken,
            'canUseEvasiveMindbug' => $canUseEvasiveMindbug,
        ];
    }

    function applyGetMindbugTokens(int $playerId, int $tokens, int | PowerCard | EvolutionCard $cardType) {
        if ($cardType instanceof PowerCard) {
            $cardType = $cardType->type;
        }
        if ($cardType instanceof EvolutionCard) {
            $cardType = 3000 + $cardType->type;
        }
        if ($cardType >= 0) {
            $message = $cardType == 0 ? '' : clienttranslate('${player_name} gains ${inc} Mindbug token(s) with ${card_name}');
            $this->mindbugTokens->inc($playerId, $tokens, new NotificationMessage($message, [
                'card_name' => $cardType == 0 ? null : $cardType,
            ]));
        }
    }

    /**
     * @return PowerCard[]
     */
    public function getConsumableCards(int $playerId, ?array $keywords): array {
        $playerCards = $this->game->powerCards->getPlayerReal($playerId);
        $consumableCards = Arrays::filter($playerCards, fn($card) => $card->type >= 400 && $card->type < 500);
        if ($keywords !== null) {
            $consumableCards = Arrays::filter($consumableCards, fn($card) => $card->mindbugKeywords !== null && Arrays::some($card->mindbugKeywords, fn($keyword) => in_array($keyword, $keywords)));
        }
        return $consumableCards;
    }

    public function activateConsumable(int $id, string $keyword, int $playerId, array $keywords) {
        $consumableCards = $this->game->mindbugExpansion->getConsumableCards($playerId, $keywords);
        $card = Arrays::find($consumableCards, fn($c) => $c->id === $id);
        if (!$card) {
            throw new \BgaUserException(clienttranslate('You cannot activate this keyword at this moment of the game'));
        }
        if (!in_array($keyword, $card->mindbugKeywords)) {
            throw new \BgaUserException("This card doesn't have the keyword $keyword");
        }

        switch ($keyword) {
            case HUNTER: $this->activateHunter($playerId, $card); break;
            case SNEAKY: $this->activateSneaky($playerId, $card); break;
            case POISON: $this->activatePoison($playerId, $card); break;
            case TOUGH: $this->activateTough($playerId, $card); break;
            case FRENZY: $this->activateFrenzy($playerId, $card); break;
            default: throw new \BgaSystemException("Invalid keyword");
        }
    }

    public function setHunterTargetId(int $targetPlayerId) {
        $activatedHunterCards = $this->game->globals->get(ACTIVATED_HUNTER_CARDS, class: ActivatedConsumableKeyword::class);
        $activatedHunterCards->targetPlayerId = $targetPlayerId;
        $this->game->globals->set(ACTIVATED_HUNTER_CARDS, $activatedHunterCards);
    }

    public function getActivatedHunter(int $playerId) {
        $activatedHunterCards = $this->game->globals->get(ACTIVATED_HUNTER_CARDS, class: ActivatedConsumableKeyword::class);
        if ($activatedHunterCards && $activatedHunterCards->activePlayerId !== $playerId) {
            return null;
        }
        return $activatedHunterCards;
    }

    public function getActivatedSneaky(int $playerId) {
        $activatedSneakyCards = $this->game->globals->get(ACTIVATED_SNEAKY_CARDS, class: ActivatedConsumableKeyword::class);
        if ($activatedSneakyCards && $activatedSneakyCards->activePlayerId !== $playerId) {
            return null;
        }
        return $activatedSneakyCards;
    }

    public function getActivatedFrenzy(int $playerId) {
        $activatedFrenzyCards = $this->game->globals->get(ACTIVATED_FRENZY_CARDS, class: ActivatedConsumableKeyword::class);
        if ($activatedFrenzyCards && $activatedFrenzyCards->activePlayerId !== $playerId) {
            return null;
        }
        return $activatedFrenzyCards;
    }

    private function activateHunter(int $playerId, PowerCard $card) {
        $activatedHunterCards = $this->game->globals->get(ACTIVATED_HUNTER_CARDS, class: ActivatedConsumableKeyword::class);
        if (!$activatedHunterCards) {
            $activatedHunterCards = new ActivatedConsumableKeyword($playerId, [$card->id]);
            $this->game->globals->set(ACTIVATED_HUNTER_CARDS, $activatedHunterCards);

            $question = new Question(
                'Hunter',
                clienttranslate('${actplayer} must choose an opponent to target'),
                clienttranslate('${you} must choose an opponent to target'),
                [$playerId],
                -1,
                [
                    'playerIds' => $this->game->getOtherPlayersIds($playerId),
                ]
            );
            $this->game->setQuestion($question);
            $this->game->gamestate->setPlayersMultiactive([$playerId], 'next', true);

            $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
            return;
        } else {
            $activatedHunterCards->cardIds[] = $card->id;
            $this->game->globals->set(ACTIVATED_HUNTER_CARDS, $activatedHunterCards);
        }
    }

    private function activateSneaky(int $playerId, PowerCard $card) {
        $activatedSneakyCards = $this->game->globals->get(ACTIVATED_SNEAKY_CARDS, class: ActivatedConsumableKeyword::class);
        if (!$activatedSneakyCards) {
            $activatedSneakyCards = new ActivatedConsumableKeyword($playerId, [$card->id]);
        } else {
            $activatedSneakyCards->cardIds[] = $card->id;
        }
        $this->game->globals->set(ACTIVATED_SNEAKY_CARDS, $activatedSneakyCards);
    }

    private function activatePoison(int $playerId, PowerCard $card) {
        $intervention = $this->game->getDamageIntervention();
        $damage = Arrays::find($intervention->damages, fn($d) => $d->playerId == $playerId);
        $theoricalLostHearts = $damage->damage;
        $newDamage = new Damage($damage->damageDealerId, $damage->damage, $playerId, $card);
        $intervention->damages[] = $newDamage;
        $intervention->allDamages[] = $newDamage;
        $intervention->remainingPlayersIds[] = $damage->damageDealerId;
        $this->game->resolveRemainingDamages($intervention, false, false);

        /** @disregard */
        $newDamages = $card->applyEffect(new Context($this->game, $playerId, keyword: POISON, lostHearts: $theoricalLostHearts, attackerPlayerId: $damage->damageDealerId));
        if (gettype($newDamages) === 'array') {
            // TODOMB add $newDamages
        }
    }

    private function activateTough(int $playerId, PowerCard $card) {
        $intervention = $this->game->getDamageIntervention();
        $damage = Arrays::find($intervention->damages, fn($d) => $d->playerId == $playerId);
        $theoricalLostHearts = $damage->damage;
        $this->game->reduceInterventionDamages($playerId, $intervention, -1);
        $this->game->resolveRemainingDamages($intervention, true, false);

        /** @disregard */
        $newDamages = $card->applyEffect(new Context($this->game, $playerId, keyword: TOUGH, lostHearts: $theoricalLostHearts));
        if (gettype($newDamages) === 'array') {
            // TODOMB add $newDamages
        }
    }

    private function activateFrenzy(int $playerId, PowerCard $card) {
        $activatedFrenzyCards = $this->game->globals->get(ACTIVATED_FRENZY_CARDS, class: ActivatedConsumableKeyword::class);
        if (!$activatedFrenzyCards) {
            $activatedFrenzyCards = new ActivatedConsumableKeyword($playerId, [$card->id]);
        } else {
            // TODOMB throw exception
        }
        $this->game->globals->set(ACTIVATED_FRENZY_CARDS, $activatedFrenzyCards);
    }

    public function applyEndFrenzy(int $playerId) {
        $damages = [];
        $activatedFrenzy = $this->getActivatedFrenzy($playerId);
        if ($activatedFrenzy) {
            $card = $this->game->powerCards->getItemById($activatedFrenzy->cardIds[0]);
            if ($card) {
                $newDamages = $card->applyEffect(new Context($this->game, $playerId, keyword: FRENZY));
                if (gettype($newDamages) === 'array') {
                    $damages = array_merge($damages, $newDamages);
                }
            }
        }
        $this->game->globals->delete(ACTIVATED_FRENZY_CARDS);
        return $damages;
    }
}
