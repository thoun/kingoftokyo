<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo;

use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\CurseCards\CurseCard;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Dice;

class AnubisExpansion {
	public CurseCardManager $curseCards;

    function __construct(
        protected Game $game,
    ) {		
        $this->curseCards = new CurseCardManager($game);
    }

    public function initDb(): void {
        $this->curseCards->initDb();
    }

    public function isActive(): bool {
        return $this->game->tableOptions->get(ANUBIS_EXPANSION_OPTION) === 2;
    }

    public function setup(array $players): void {
        $this->game->DbQuery("INSERT INTO dice (`dice_value`, `type`) VALUES (0, 2)");

        $lastPlayer = array_key_last($players);
        $this->game->setGameStateInitialValue(PLAYER_WITH_GOLDEN_SCARAB, $lastPlayer);
        $this->curseCards->setup();
        $this->curseCards->immediateEffect($this->curseCards->getCurrent(), new Context($this->game));
    }

    public function fillResult(array &$result): void {
        $result['playerWithGoldenScarab'] = $this->getPlayerIdWithGoldenScarab(true);
        $result['curseCard'] = $this->curseCards->getCurrent();
        $result['hiddenCurseCardCount'] = $this->curseCards->countCardsInLocation('deck');
        $result['visibleCurseCardCount'] = $this->curseCards->countCardsInLocation('table') + $this->curseCards->countCardsInLocation('discard');
        $result['topCurseDeckCard'] = $this->curseCards->getTopDeck();
    }

    public function getDieOfFate(): ?Dice {
        return $this->game->getDiceByType(2)[0];
    }

    public function snakeEffectDiscardKeepCard(int $playerId): ?int {
        $cards = $this->game->powerCards->getPlayerReal($playerId);
        $keepCards = Arrays::filter($cards, fn($card) => $card->type < 100);
        $count = count($keepCards);
        if ($count > 1) {
            return ST_PLAYER_DISCARD_KEEP_CARD;
        } else if ($count === 1) {
            $this->applyDiscardKeepCard($playerId, $keepCards[0]);
        }
        return null;
    }

    public function getCurseCardType(): int {
        return $this->curseCards->getCurrent()->type;
    }

    public function changeGoldenScarabOwner(int $playerId): void {
        $previousOwner = $this->getPlayerIdWithGoldenScarab(true);

        if ($previousOwner == $playerId) {
            return;
        }

        $this->game->setGameStateValue(PLAYER_WITH_GOLDEN_SCARAB, $playerId);

        $this->game->notify->all('changeGoldenScarabOwner', clienttranslate('${player_name} gets Golden Scarab'), [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'previousOwner' => $previousOwner,
        ]);
    }

    public function getPlayerIdWithGoldenScarab(bool $ignoreElimination = false): ?int {
        $playerId = intval($this->game->getGameStateValue(PLAYER_WITH_GOLDEN_SCARAB));

        if ($playerId == 0 || (!$ignoreElimination && $this->game->getPlayer($playerId)->eliminated)) {
            return null;
        }

        return $playerId;
    }


    public function keepAndEvolutionCardsHaveEffect(): bool {
        $curseCardType = $this->getCurseCardType();

        return $curseCardType != GAZE_OF_THE_SPHINX_CURSE_CARD;
    }

    private function removeCursePermanentEffectOnReplace() {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == BOW_BEFORE_RA_CURSE_CARD) {
            $this->game->changeAllPlayersMaxHealth();
        }
    }

    public function applyDiscardDie(int $dieId): void {
        $this->game->DbQuery("UPDATE dice SET `discarded` = true WHERE `dice_id` = $dieId");

        $die = $this->game->getDieById($dieId);

        $this->game->notify->all("discardedDie", clienttranslate('Die ${dieFace} is discarded'), [
            'die' => $die,
            'dieFace' => $this->game->getDieFaceLogName($die->value, $die->type),
        ]);
    }

    public function applyDiscardKeepCard(int $playerId, object $card): void {
        $this->game->notify->all("log", clienttranslate('${player_name} discards ${card_name}'), [
            'playerId' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'card_name' => $card->type,
        ]);
        
        $this->game->removeCard($playerId, $card);
    }

    public function getRerollDicePlayerId(): int {
        if ($this->getCurseCardType() == FALSE_BLESSING_CURSE_CARD) {
            // player on the left
            $playersIds = $this->game->getPlayersIds();
            $playerIndex = array_search($this->game->getActivePlayerId(), $playersIds);
            $playerCount = count($playersIds);
            
            $leftPlayerId = $playersIds[($playerIndex + 1) % $playerCount];
            return $leftPlayerId;
        } else {
            // player with golden scarab
            return $this->getPlayerIdWithGoldenScarab();
        }
    }

    public function canGainHealth(int $playerId): bool {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == ISIS_S_DISGRACE_CURSE_CARD && $playerId != $this->getPlayerIdWithGoldenScarab()) {
            return false;
        }
        return true;
    }

    function canLoseHealth(int $playerId): ?int {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == PHARAONIC_SKIN_CURSE_CARD) {
            if ($playerId == $this->getPlayerIdWithGoldenScarab()) {
                return 1000 + PHARAONIC_SKIN_CURSE_CARD;
            }
        }
        return null;
    }

    public function canGainEnergy(int $playerId): bool {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == THOT_S_BLINDNESS_CURSE_CARD) {
            if ($playerId != $this->getPlayerIdWithGoldenScarab()) {
                return false;
            }
        }
        return true;
    }

    public function canGainPoints(int $playerId): ?int {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == TUTANKHAMUN_S_CURSE_CURSE_CARD) {
            if ($playerId != $this->getPlayerIdWithGoldenScarab()) {
                return 1000 + TUTANKHAMUN_S_CURSE_CURSE_CARD;
            }
        }
        return null;
    }

    public function canEnterTokyo(): bool {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == PHARAONIC_EGO_CURSE_CARD) {
            $dieOfFate = $this->getDieOfFate();
            if ($dieOfFate->value == 4) {
                return false;
            }
        }

        if ($curseCardType == RESURRECTION_OF_OSIRIS_CURSE_CARD) {
            $dieOfFate = $this->getDieOfFate();
            if ($dieOfFate->value == 3) {
                return false;
            }
        }

        return true;
    }

    public function canYieldTokyo(): bool {
        $curseCardType = $this->getCurseCardType();

        return $curseCardType != PHARAONIC_EGO_CURSE_CARD;
    }

    public function canHealWithDice(bool $inTokyo): ?bool {
        $curseCardType = $this->getCurseCardType();

        if ($curseCardType == RESURRECTION_OF_OSIRIS_CURSE_CARD) {
            return $inTokyo;
        }

        return null;
    }

    public function canBuyPowerCard(int $playerId): bool {
        if ($this->getCurseCardType() == FORBIDDEN_LIBRARY_CURSE_CARD) {
            return $playerId == $this->getPlayerIdWithGoldenScarab();
        }
        return true;
    }
    
    private function changeCurseCard(int $playerId): CurseCard {
        $countRapidHealingBefore = 0;
        if ($playerId > 0) {
            $countRapidHealingBefore = $this->game->countCardOfType($playerId, RAPID_HEALING_CARD);
        }        

        $this->removeCursePermanentEffectOnReplace();

        $this->curseCards->moveAllCardsInLocation('table', 'discard');

        $card = $this->curseCards->pickCardForLocation('deck', null, 'table');

        $this->game->notify->all('changeCurseCard', clienttranslate('Die of fate is on [dieFateEye], Curse card is changed'), [
            'card' => $card,
            'hiddenCurseCardCount' => $this->curseCards->countCardsInLocation('deck'),
            'topCurseDeckCard' => $this->curseCards->getTopDeck(),
        ]);

        $this->curseCards->immediateEffect($card, new Context($this->game));
        
        if ($playerId > 0) {
            $this->game->toggleRapidHealing($playerId, $countRapidHealingBefore);
        }

        return $card;
    }

    public function resolveDieOfFate(int $playerId) {
        $dieOfFate = $this->getDieOfFate();

        $damagesOrState = null;
        $curseCard = $this->curseCards->getCurrent();
        switch($dieOfFate->value) {
            case 1: 
                $curseCard = $this->changeCurseCard($playerId);

                $this->game->incStat(1, 'dieOfFateEye', $playerId);
                break;
            case 2:
                $this->game->notify->all('dieOfFateResolution', clienttranslate('Die of fate is on [dieFateRiver], ${card_name} is kept (with no effect except permanent effect)'), [
                    'card_name' => 1000 + $curseCard->type,
                ]);

                $this->game->incStat(1, 'dieOfFateRiver', $playerId);
                break;
            case 3:
                $this->game->notify->all('dieOfFateResolution', clienttranslate('Die of fate is on [dieFateSnake], Snake effect of ${card_name} is applied'), [
                    'card_name' => 1000 + $curseCard->type,
                ]);

                $this->game->incStat(1, 'dieOfFateSnake', $playerId);

                $damagesOrState = $this->curseCards->applySnakeEffect($curseCard, new Context($this->game, currentPlayerId: $playerId));
                break;
            case 4:
                $this->game->notify->all('dieOfFateResolution', clienttranslate('Die of fate is on [dieFateAnkh], Ankh effect of ${card_name} is applied'), [
                   'card_name' => 1000 + $curseCard->type,
                ]);

                $this->game->incStat(1, 'dieOfFateAnkh', $playerId);

                $damagesOrState = $this->curseCards->applyAnkhEffect($curseCard, new Context($this->game, currentPlayerId: $playerId));
                break;
        }
        return $damagesOrState;
    }

    function argGiveSymbols(int $activePlayerId) {
        $MAPPING = [
            0 => 4,
            1 => 5,
            2 => 0,
        ];

        $resources = [
            $this->game->getPlayerHealth($activePlayerId),
            $this->game->getPlayerEnergy($activePlayerId),
            $this->game->getPlayerScore($activePlayerId),
        ];

        $combinations = [];

        $sum = array_reduce($resources, fn($carry, $item) => $carry + $item);

        // ($sum === 0) { => return empty array
        if ($sum === 1) {
            foreach($resources as $index => $resource) {
                if ($resource > 0) {
                    $combinations[] = [$MAPPING[$index]];
                }
            }
        } else {
            foreach ($resources as $index1 => $resource1) {
                if ($resource1 > 0) {
                    foreach($resources as $index2 => $resource2) {
                        if (($index1 == $index2 && $resource2 >= 2) || ($index2 > $index1 && $resource2 > 0)) {
                            $combinations[] = [$MAPPING[$index1], $MAPPING[$index2]];
                        }
                    }
                }
            }
        }

        return [
            'combinations' => $combinations,
        ];
    }
}
