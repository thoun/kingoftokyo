<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Game;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Damage;

use const Bga\Games\KingOfTokyo\FLUXLING_WICKEDNESS_TILE;

class ThrowDice extends GameState {
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: \ST_PLAYER_THROW_DICE,
            type: StateType::ACTIVE_PLAYER,
            name: 'throwDice',
            description: clienttranslate('${actplayer} can reroll dice or resolve dice'),
            descriptionMyTurn: clienttranslate('${you} can reroll dice or resolve dice'),
        );
    }

    public function getArgs(int $activePlayerId): array {
        $dice = $this->game->getPlayerRolledDice($activePlayerId, true, true, true);

        $throwNumber = intval($this->game->getGameStateValue('throwNumber'));
        $maxThrowNumber = $this->game->getRollNumber($activePlayerId);

        $hasEnergyDrink = $this->game->countCardOfType($activePlayerId, ENERGY_DRINK_CARD) > 0; // Energy drink
        $playerEnergy = null;
        if ($hasEnergyDrink) {
            $playerEnergy = $this->game->getPlayerEnergy($activePlayerId);
        }

        $hasBackgroundDweller = $this->game->countCardOfType($activePlayerId, BACKGROUND_DWELLER_CARD) > 0; // Background Dweller
        $hasDice3 = null;
        if ($hasBackgroundDweller) {
            $hasDice3 = $this->game->getFirst3Die($activePlayerId) != null;
        }

        $smokeCloudsTokens = 0;
        $smokeCloudCards = $this->game->getCardsOfType($activePlayerId, SMOKE_CLOUD_CARD); // Smoke Cloud
        foreach($smokeCloudCards as $smokeCloudCard) {
            $smokeCloudsTokens += $smokeCloudCard->tokens;
        }
        $hasSmokeCloud = $smokeCloudsTokens > 0;
        $hasCultist = $this->game->cthulhuExpansion->isActive() && $this->game->cthulhuExpansion->getPlayerCultists($activePlayerId) > 0;

        $isBeastForm = $this->game->isMutantEvolutionVariant() && $this->game->isBeastForm($activePlayerId);
        $canUseBeastForm = false;
        if ($isBeastForm) {
            $canUseBeastForm = !$this->game->isUsedCard($this->game->getFormCard($activePlayerId)->id);
        }

        $opponentsOrbOfDooms = 0;
        $playersIds = $this->game->getOtherPlayersIds($activePlayerId);
        foreach($playersIds as $pId) {
            $countOrbOfDoom = $this->game->countCardOfType($pId, ORB_OF_DOM_CARD);
            $opponentsOrbOfDooms += $countOrbOfDoom;
        }

        $usedCardIds = $this->game->getUsedCard();
        $intergalacticGeniusEvolutions = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($activePlayerId, INTERGALACTIC_GENIUS_EVOLUTION, true, true);
        $intergalacticGeniusEvolution = Arrays::find($intergalacticGeniusEvolutions, fn($evolution) => !in_array(3000 + $evolution->id, $usedCardIds));

        $hasActions = $throwNumber < $maxThrowNumber 
            || ($hasEnergyDrink && $playerEnergy >= 1) 
            || $hasDice3 
            || $hasSmokeCloud 
            || $hasCultist
            || ($isBeastForm && $canUseBeastForm)
            || $intergalacticGeniusEvolution !== null;

        $selectableDice = $this->game->getSelectableDice($dice, false, true);
    
        // return values:
        return [
            'throwNumber' => $throwNumber,
            'maxThrowNumber' => $maxThrowNumber,
            'dice' => $dice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $this->game->canHealWithDice($activePlayerId),
            'frozenFaces' => $this->game->frozenFaces($activePlayerId),
            'energyDrink' => [
                'hasCard' => $hasEnergyDrink,
                'playerEnergy' => $playerEnergy,
            ],
            'rethrow3' => [
                'hasCard' => $hasBackgroundDweller,
                'hasDice3' => $hasDice3,
            ],
            'rerollDie' => [
                'isBeastForm' => $isBeastForm,
                'canUseBeastForm' => $canUseBeastForm,
            ],
            'hasSmokeCloud' => $hasSmokeCloud,
            'hasCultist' => $hasCultist,
            'hasActions' => $hasActions,
            'opponentsOrbOfDooms' => $opponentsOrbOfDooms,
            'rerollAllEnergy' => $intergalacticGeniusEvolution !== null,
        ];
    }
    
    public function onEnteringState() {
        // disabled so player can see last roll
        /*if ($this->autoSkipImpossibleActions() && !$this->argThrowDice()['hasActions']) {
            // skip state
            $this->actGoToChangeDie();
        }*/
    }

    #[PossibleAction]
    public function actRethrow(
        #[IntArrayParam(name: 'diceIds')] array $diceIds,
        int $currentPlayerId,
    ): void {
        $throwNumber = (int)$this->game->getGameStateValue('throwNumber');
        $maxThrowNumber = $this->game->getRollNumber($currentPlayerId);

        if ($throwNumber >= $maxThrowNumber) {
            throw new \BgaUserException("You can't throw dices (max throw)");
        }

        $diceIds = array_map('intval', $diceIds);
        if (empty($diceIds)) {
            throw new \BgaUserException('No selected dice to throw');
        }

        $this->rethrowDice($diceIds);
    }

    #[PossibleAction]
    public function actRerollDie(
        #[IntParam(name: 'id')] int $dieId,
        #[IntArrayParam(name: 'diceIds')] array $diceIds,
        int $currentPlayerId,
    ): void {
        $die = $this->game->getDieById($dieId);
        if ($die === null) {
            throw new \BgaUserException('No die');
        }

        $formCard = $this->game->getFormCard($currentPlayerId);
        $this->game->setUsedCard($formCard->id);

        $diceIds = array_map('intval', $diceIds);
        $this->game->applyRerollDie($currentPlayerId, $die, $diceIds, \FORM_CARD);
    }

    #[PossibleAction]
    public function actRethrow3(
        #[IntArrayParam(name: 'diceIds')] array $diceIds,
        int $currentPlayerId,
    ): void {
        $die = $this->game->getFirst3Die($currentPlayerId);
        if ($die === null) {
            throw new \BgaUserException('No 3 die');
        }

        $diceIds = array_map('intval', $diceIds);
        $this->game->applyRerollDie($currentPlayerId, $die, $diceIds, \BACKGROUND_DWELLER_CARD);
    }

    #[PossibleAction]
    public function actGoToChangeDie(int $currentPlayerId): int {
        $this->game->fixDices();

        $intervention = $this->game->getChangeActivePlayerDieIntervention($currentPlayerId);
        if ($intervention !== null) {
            $this->game->setGlobalVariable(\CHANGE_ACTIVE_PLAYER_DIE_INTERVENTION, $intervention);
            return \ST_MULTIPLAYER_CHANGE_ACTIVE_PLAYER_DIE;
        }

        return \ST_PLAYER_CHANGE_DIE;
    }

    #[PossibleAction]
    public function actBuyEnergyDrink(
        #[IntArrayParam(name: 'diceIds')] array $diceIds,
        int $currentPlayerId,
    ): void {
        if ($this->game->getPlayerEnergy($currentPlayerId) < 1) {
            throw new \BgaUserException('Not enough energy');
        }

        $cardCount = $this->game->countCardOfType($currentPlayerId, \ENERGY_DRINK_CARD);
        if ($cardCount === 0) {
            throw new \BgaUserException('No Energy Drink card');
        }

        $diceIds = array_map('intval', $diceIds);

        $this->game->applyLoseEnergyIgnoreCards($currentPlayerId, 1, 0);

        $extraRolls = (int)$this->game->getGameStateValue(\EXTRA_ROLLS) + 1;
        $this->game->setGameStateValue(\EXTRA_ROLLS, $extraRolls);

        $this->rethrowDice($diceIds);
    }

    #[PossibleAction]
    public function actUseSmokeCloud(
        #[IntArrayParam(name: 'diceIds')] array $diceIds,
        int $currentPlayerId,
    ): void {
        $cards = $this->game->getCardsOfType($currentPlayerId, \SMOKE_CLOUD_CARD);
        if (count($cards) === 0) {
            throw new \BgaUserException('No Smoke Cloud card');
        }

        $card = Arrays::find(
            $cards,
            fn($icard) => $icard->type == FLUXLING_WICKEDNESS_TILE && $icard->tokens > 0,
        )
            ?? Arrays::find(
                $cards,
                fn($icard) => $icard->type == \MIMIC_CARD && $icard->tokens > 0,
            )
            ?? $cards[0];

        if ($card->tokens < 1) {
            throw new \BgaUserException('Not enough token');
        }

        $diceIds = array_map('intval', $diceIds);

        $tokensOnCard = $card->tokens - 1;
        if ($card->type == FLUXLING_WICKEDNESS_TILE) {
            $card->id = $card->id - 2000;
            $this->game->wickednessExpansion->setTileTokens($currentPlayerId, $card, $tokensOnCard);
        } else {
            $this->game->setCardTokens($currentPlayerId, $card, $tokensOnCard);
        }

        if (
            $tokensOnCard <= 0
            && $card->type != \MIMIC_CARD
            && $card->type != FLUXLING_WICKEDNESS_TILE
        ) {
            $this->game->removeCard($currentPlayerId, $card);
        }

        $extraRolls = (int)$this->game->getGameStateValue(\EXTRA_ROLLS) + 1;
        $this->game->setGameStateValue(\EXTRA_ROLLS, $extraRolls);

        $this->game->notify->all(
            'log',
            clienttranslate('${player_name} uses ${card_name} to gain 1 extra roll'),
            [
                'playerId' => $currentPlayerId,
                'player_name' => $this->game->getPlayerNameById($currentPlayerId),
                'card_name' => \SMOKE_CLOUD_CARD,
            ],
        );

        $this->rethrowDice($diceIds);
    }

    #[PossibleAction]
    public function actUseCultist(
        #[IntArrayParam(name: 'diceIds')] array $diceIds,
        int $currentPlayerId,
    ): void {
        if ($this->game->cthulhuExpansion->getPlayerCultists($currentPlayerId) == 0) {
            throw new \BgaUserException('No cultist');
        }

        $diceIds = array_map('intval', $diceIds);

        $this->game->cthulhuExpansion->applyLoseCultist(
            $currentPlayerId,
            clienttranslate('${player_name} use a Cultist to gain 1 extra roll'),
        );
        $this->game->incStat(1, 'cultistReroll', $currentPlayerId);

        $extraRolls = (int)$this->game->getGameStateValue(\EXTRA_ROLLS) + 1;
        $this->game->setGameStateValue(\EXTRA_ROLLS, $extraRolls);

        $this->rethrowDice($diceIds);
    }
  	
    function rethrowDice(array $diceIds) {
        $diceCount = count($diceIds);

        $playerId = (int)$this->game->getActivePlayerId();
        $this->game->DbQuery("UPDATE dice SET `locked` = true, `rolled` = false");
        $this->game->DbQuery("UPDATE dice SET `locked` = false, `rolled` = true where `dice_id` IN (".implode(',', $diceIds).")");

        $this->game->incStat($diceCount, 'rethrownDice', $playerId);

        $this->game->throwDice($playerId, false);

        $throwNumber = intval($this->game->getGameStateValue('throwNumber')) + 1;
        $this->game->setGameStateValue('throwNumber', $throwNumber);

        $args = $this->getArgs($playerId);
        $damages = [];
        if ($args['opponentsOrbOfDooms'] > 0) {
            $playersIds = $this->game->getOtherPlayersIds($playerId);
            foreach($playersIds as $pId) {
                $countOrbOfDoom = $this->game->countCardOfType($pId, ORB_OF_DOM_CARD);
                if ($countOrbOfDoom > 0) {
                    $damages[] = new Damage($playerId, $countOrbOfDoom, $pId, ORB_OF_DOM_CARD);
                }
            }
        }

        $this->game->goToState(ST_PLAYER_THROW_DICE, $damages);
    }

    #[PossibleAction]
    function actUseIntergalacticGenius(
        #[IntArrayParam(name: 'diceIds')] array $diceIds,
        int $activePlayerId
    )  {
        $usedCardIds = $this->game->getUsedCard();
        $intergalacticGeniusEvolutions = $this->game->powerUpExpansion->evolutionCards->getPlayerVirtualByType($activePlayerId, INTERGALACTIC_GENIUS_EVOLUTION, true, true);
        $intergalacticGeniusEvolution = Arrays::find($intergalacticGeniusEvolutions, fn($evolution) => !in_array(3000 + $evolution->id, $usedCardIds));
        
        if (!$intergalacticGeniusEvolution) {
            throw new \BgaUserException("You can't play Intergalactic Genius without this Evolution.");
        }

        $this->game->DbQuery("UPDATE dice SET `locked` = false, `rolled` = false");
        if (count($diceIds) > 0) {
            $this->game->DbQuery("UPDATE dice SET `locked` = true where `dice_id` IN (".implode(',', $diceIds).")");
        }
        
        /** @disregard */
        $intergalacticGeniusEvolution->applyEffect(new Context($this->game, $activePlayerId));

        return ThrowDice::class;
    }

    public function zombie(int $playerId) {
        return $this->actGoToChangeDie($playerId);
    }
}
