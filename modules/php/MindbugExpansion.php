<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFramework\Components\Counters\PlayerCounter;
use Bga\GameFramework\NotificationMessage;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\EvolutionCards\EvolutionCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use Bga\Games\KingOfTokyo\PowerCards\PowerCard;
use KOT\Objects\Damage;
use KOT\Objects\Question;

use const Bga\Games\KingOfTokyo\PowerCards\FRENZY;
use const Bga\Games\KingOfTokyo\PowerCards\HUNTER;
use const Bga\Games\KingOfTokyo\PowerCards\POISON;
use const Bga\Games\KingOfTokyo\PowerCards\SNEAKY;
use const Bga\Games\KingOfTokyo\PowerCards\TOUGH;

class MindbugExpansion {
    public PlayerCounter $mindbugTokens;

    function __construct(
        protected Game $game,
    ) {
        $this->mindbugTokens = $game->counterFactory->createPlayerCounter('mindbugTokens');
    }

    public function isActive(): bool {
        return $this->game->tableOptions->get(MINDBUG_EXPANSION_OPTION) > 0;
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

        $this->game->notify->all("mindbugPlayer", clienttranslate('${player_name} mindbugs ${player_name2}!'), [
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
        $consumableCards = Arrays::filter($playerCards, fn($card) => $card->type >= 400 && $card->type < 500 && !$card->activated);
        $usedCards = $this->game->getUsedCard();
        $consumableCards = Arrays::filter($consumableCards, fn($card) => !in_array($card->id, $usedCards));
        if ($keywords !== null) {
            $consumableCards = Arrays::filter($consumableCards, fn($card) => $card->mindbugKeywords !== null && Arrays::some($card->mindbugKeywords, fn($keyword) => in_array($keyword, $keywords)));
        }
        return $consumableCards;
    }

    /**
     * @return EvolutionCard[]
     */
    public function getConsumableEvolutions(int $playerId, ?array $keywords): array {
        $playerEvolutionsInHand = $this->game->powerUpExpansion->evolutionCards->getPlayerReal($playerId, false, true);
        $consumableEvolutions = Arrays::filter($playerEvolutionsInHand, fn($evolution) => $evolution->mindbugKeywords !== null && !$evolution->activated);
        if ($keywords !== null) {
            $consumableEvolutions = Arrays::filter($consumableEvolutions, fn($card) => $card->mindbugKeywords !== null && Arrays::some($card->mindbugKeywords, fn($keyword) => in_array($keyword, $keywords)));
        }
        return $consumableEvolutions;
    }

    /**
     * @return Damage[]
     */
    public function activateConsumable(int $id, string $keyword, int $playerId, array $keywords): void {
        $consumableCards = $this->game->mindbugExpansion->getConsumableCards($playerId, $keywords);
        $card = Arrays::find($consumableCards, fn($c) => $c->id === $id);
        if (!$card) {
            throw new \BgaUserException(clienttranslate('You cannot activate this keyword at this moment of the game'));
        }
        if (!in_array($keyword, $card->mindbugKeywords)) {
            throw new \BgaUserException("This card doesn't have the keyword $keyword");
        }

        $activatedFrenzyCards = $keyword === FRENZY ? $this->getActivatedCards($playerId, FRENZY) : [];

        $this->game->powerCards->activateKeyword($card, $keyword);
        $this->game->notify->all('activatedKeywordCard', clienttranslate('${player_name} activates ${keyword} card ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'card' => $card,
            'keyword' => $keyword,
            'card_name' => $card->type,
            'i18n' => ['keyword'],
        ]);

        switch ($keyword) {
            case HUNTER: $this->activateHunter($playerId, $card); break;
            case SNEAKY: $this->activateSneaky($playerId, $card); break;
            case POISON: $this->activatePoison($playerId, $card); break;
            case TOUGH: $this->activateTough($playerId, $card); break;
            case FRENZY: $this->activateFrenzy($activatedFrenzyCards); break;
        }
    }

    /**
     * @return Damage[]
     */
    public function activateConsumableEvolution(int $id, string $keyword, int $playerId, array $keywords): void {
        $playerEvolutionsInHand = $this->game->powerUpExpansion->evolutionCards->getPlayerReal($playerId, false, true);
        $consumableEvolutions = Arrays::filter($playerEvolutionsInHand, fn($evolution) => $evolution->mindbugKeywords !== null);
        $card = Arrays::find($consumableEvolutions, fn($c) => $c->id === $id);
        if (!$card) {
            throw new \BgaUserException(clienttranslate('You cannot activate this keyword at this moment of the game'));
        }
        if (!in_array($keyword, $card->mindbugKeywords)) {
            throw new \BgaUserException("This card doesn't have the keyword $keyword");
        }

        $activatedFrenzyCards = $keyword === FRENZY ? $this->getActivatedCards($playerId, FRENZY) : [];

        $this->game->playEvolutionToTable($playerId, $card);

        $this->game->powerUpExpansion->evolutionCards->activateKeyword($card, $keyword);
        $this->game->notify->all('activatedKeywordEvolution', clienttranslate('${player_name} activates ${keyword} Evolution ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'card' => $card,
            'keyword' => $keyword,
            'card_name' => 3000 + $card->type,
            'i18n' => ['keyword'],
        ]);

        switch ($keyword) {
            case HUNTER: $this->activateHunter($playerId, $card); break;
            case SNEAKY: $this->activateSneaky($playerId, $card); break;
            case POISON: $this->activatePoison($playerId, $card); break;
            case TOUGH: $this->activateTough($playerId, $card); break;
            case FRENZY: $this->activateFrenzy($activatedFrenzyCards); break;
        }
    }

    /**
     * @return PowerCard[]
     */
    public function getActivatedPowerCards(int $playerId, ?string $keyword = null): array {
        $playerCards = $this->game->powerCards->getPlayerReal($playerId);
        $cards = Arrays::filter($playerCards, fn($card) => $card->type >= 400 && $card->type < 500 && $card->activated);
        if ($keyword) {
            $cards = Arrays::filter($cards, fn($card) => $card->activated->keyword === $keyword);
        }
        return $cards;
    }

    /**
     * @return EvolutionCards[]
     */
    public function getActivatedEvolutionCards(int $playerId, ?string $keyword = null): array {
        $playerEvolutions = $this->game->powerUpExpansion->evolutionCards->getPlayerReal($playerId, true, false);
        $cards = Arrays::filter($playerEvolutions, fn($evolution) => $evolution->activated);
        if ($keyword) {
            $cards = Arrays::filter($cards, fn($card) => $card->activated->keyword === $keyword);
        }
        return $cards;
    }

    /**
     * @return (PowerCard | EvolutionCards)[]
     */
    public function getActivatedCards(int $playerId, ?string $keyword = null): array {
        return array_merge(
            $this->getActivatedPowerCards($playerId, $keyword),
            $this->getActivatedEvolutionCards($playerId, $keyword),
        );
    }

    public function cleanActivatedCards(int $playerId, ?string $keyword = null): void {
        $cards = $this->getActivatedPowerCards($playerId, $keyword);
        foreach ($cards as $card) {
            $card->activated = null;
            $this->game->powerCards->updateCard($card, ['activated']);
            $this->game->removeCard($playerId, $card);
        }
        $evolutions = $this->getActivatedEvolutionCards($playerId, $keyword);
        foreach ($evolutions as $evolution) {
            $evolution->activated = null;
            $this->game->powerUpExpansion->evolutionCards->updateCard($evolution, ['activated']);
            $this->game->removeEvolution($playerId, $evolution);
        }
    }

    private function activateHunter(int $playerId, PowerCard | EvolutionCard $card): void {
        $question = new Question(
            'Hunter',
            clienttranslate('${actplayer} must choose an opponent to target with ${card_name}'),
            clienttranslate('${you} must choose an opponent to target with ${card_name}'),
            [$playerId],
            -1,
            [
                'playerIds' => $this->game->getOtherPlayersIds($playerId),
                '_args' => [
                    'card_name' => $card instanceof EvolutionCard ? 3000 + $card->type : $card->type,
                ],
                'cardId' => $card instanceof EvolutionCard ? null : $card->id,
                'evolutionId' => $card instanceof EvolutionCard ? $card->id : null,
            ],
            cardId: $card instanceof EvolutionCard ? null : $card->id,
            evolutionId: $card instanceof EvolutionCard ? $card->id : null,
        );
        $this->game->setQuestion($question);
        $this->game->gamestate->setPlayersMultiactive([$playerId], 'next', true);

        $this->game->goToState(\ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    private function activateSneaky(int $playerId, PowerCard | EvolutionCard $card): void {
        if (method_exists($card, 'immediateEffect')) {
            /** @disregard */
            $card->immediateEffect(new Context($this->game, $playerId));
        }
    }

    private function activatePoison(int $playerId, PowerCard | EvolutionCard $card): void {
        /** @var CancelDamageIntervention */
        $intervention = $this->game->getDamageIntervention();
        $damage = Arrays::find($intervention->damages, fn($d) => $d->playerId == $playerId);
        $theoricalLostHearts = $damage->damage;
        $newDamage = new Damage($damage->damageDealerId, $damage->damage, $playerId, $card);
        $this->game->addDamagesToCancelDamageIntervention($intervention, [$newDamage]);

        /** @disregard */
        $newDamages = $card->applyEffect(new Context($this->game, $playerId, keyword: POISON, lostHearts: $theoricalLostHearts, attackerPlayerId: $damage->damageDealerId));
        if (gettype($newDamages) === 'array') {
            $this->game->addDamagesToCancelDamageIntervention($intervention, $newDamages);
        }

        $this->game->resolveRemainingDamages($intervention, false, true);
    }

    private function activateTough(int $playerId, PowerCard | EvolutionCard $card): void {
        /** @var CancelDamageIntervention */
        $intervention = $this->game->getDamageIntervention();
        $damage = Arrays::find($intervention->damages, fn($d) => $d->playerId == $playerId);
        $theoricalLostHearts = $damage->damage;
        $this->game->reduceInterventionDamages($playerId, $intervention, -1);

        /** @disregard */
        $newDamages = $card->applyEffect(new Context($this->game, $playerId, keyword: TOUGH, lostHearts: $theoricalLostHearts));
        if (gettype($newDamages) === 'array') {
            $this->game->addDamagesToCancelDamageIntervention($intervention, $newDamages);
        }
        
        $this->game->resolveRemainingDamages($intervention, false, true);
    }

    private function activateFrenzy(array $activatedFrenzyCards): void {
        if (!empty($activatedFrenzyCards)) {
            throw new \BgaSystemException('Already a frenzy turn awaiting');
        }
    }

    /**
     * @return Damage[]
     */
    public function applyEndFrenzy(int $playerId): array {
        $damages = [];
        $activatedFrenzyCards = $this->getActivatedCards($playerId, FRENZY);
        if (!empty($activatedFrenzyCards)) {
            $card = $activatedFrenzyCards[0];
            /** @disregard */
            $newDamages = $card->applyEffect(new Context($this->game, $playerId, keyword: FRENZY));
            if (gettype($newDamages) === 'array') {
                $damages = array_merge($damages, $newDamages);
            }
        }
        $this->cleanActivatedCards($playerId, FRENZY);
        return $damages;
    }
}
