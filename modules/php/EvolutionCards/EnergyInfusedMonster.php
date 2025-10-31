<?php
declare(strict_types=1);

namespace Bga\Games\KingOfTokyo\EvolutionCards;

use Bga\GameFramework\UserException;
use Bga\GameFrameworkPrototype\Helpers\Arrays;
use Bga\Games\KingOfTokyo\Objects\Context;
use KOT\Objects\Question;

class EnergyInfusedMonster extends EvolutionCard
{
    public function __construct()
    {
        $this->evolutionType = PERMANENT;
    }

    public function applyEffect(Context $context) {
        $playerEnergy = $context->game->getPlayerEnergy($context->currentPlayerId);
        if ($playerEnergy < 1) {
            throw new UserException(clienttranslate('Not enough energy'));
        }
        $context->game->applyLoseEnergy($context->currentPlayerId, 1, 0);

        $activePlayerDice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, true, false);
        $selectableDice = $context->game->getSelectableDice($activePlayerDice, false, false);

        $playerId = $context->currentPlayerId;

        $diceCount = Arrays::count($activePlayerDice, fn($die) => $die->type < 2);
        if ($diceCount < 2) {
            throw new UserException("You don't have 2 dice to discard");
        }
        $min = min(2, $diceCount);
        $max = min(2, $diceCount);

        $args = [
            'playerId' => $playerId,
            'player_name' => $context->game->getPlayerNameById($playerId),
            'dice' => $activePlayerDice,
            'selectableDice' => $selectableDice,
            'canHealWithDice' => $context->game->canHealWithDice($context->currentPlayerId),
            'frozenFaces' => $context->game->frozenFaces($context->currentPlayerId),
            'min' => $min,
            'max' => $max,
        ];

        $question = new Question(
            'EnergyInfusedMonster',
            clienttranslate('${actplayer} must choose 2 dice to discard'),
            clienttranslate('${you} must choose 2 dice to discard'),
            [$context->currentPlayerId],
            -1,
            $args,
            evolutionId: $this->id,
        );
        $context->game->addStackedState();
        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function actAnswerEnergyInfusedMonster(
        Context $context,
        /*Question*/ $question,
        array $diceIds,
    ): void {
        $questionArgs = (array)$question->args;
        $min = (int)$questionArgs['min'];
        $max = (int)$questionArgs['max'];

        $context->game->setUsedCard(3000 + $this->id);

        $diceIds = array_values(array_unique(array_map('intval', $diceIds)));

        if (count($diceIds) < $min) {
            throw new UserException(\clienttranslate('You must select more dice.'));
        }
        if (count($diceIds) > $max) {
            throw new UserException(\clienttranslate('You selected too many dice.'));
        }

        if (count($diceIds) === 0) {
            $context->game->goToState(-1);
            return;
        }

        $activePlayerDice = $context->game->getPlayerRolledDice($context->currentPlayerId, true, true, false);
        $dice = Arrays::filter($activePlayerDice, fn($die) => in_array($die->id, $diceIds));
        $context->game->DbQuery("UPDATE dice SET `discarded` = true WHERE `dice_id` IN (".implode(',', Arrays::pluck($dice, 'id')).")");
        foreach ($dice as &$die) {
            $die->discarded = true;
        }

        $context->game->notify->all("discardedDice", clienttranslate('Dice ${dieFace} are discarded'), [
            'dice' => $dice,
            'dieFace' => implode('', Arrays::map($dice, fn($die) => $context->game->getDieFaceLogName($die->value, $die->type))),
        ]);

        $questionArgs['dice'] = Arrays::filter($questionArgs['dice'], fn($die) => !in_array($die->id, $diceIds));

        $question = new Question(
            'EnergyInfusedMonsterSelectExtraDie',
            clienttranslate('${actplayer} must select the face of the extra die'),
            clienttranslate('${you} must select the face of the extra die'),
            [$context->currentPlayerId],
            args: $questionArgs + ['discardedDice' => $dice],
            evolutionId: $this->id,
        );

        $context->game->setQuestion($question);
        $context->game->gamestate->setPlayersMultiactive([$context->currentPlayerId], 'next', true);

        $context->game->goToState(ST_MULTIPLAYER_ANSWER_QUESTION);
    }

    public function actAnswerEnergyInfusedMonsterSelectExtraDie(
        Context $context,
        /*Question*/ $question,
        int $face,
    ): void {
        $questionArgs = (array)$question->args;
        $die = $questionArgs['discardedDice'][0];
        $dieId = $die->id; 
        $context->game->DbQuery("UPDATE dice SET `discarded` = false, `dice_value` = $face WHERE dice_id = $dieId");

        $context->game->notify->all("selectExtraDie", clienttranslate('${player_name} choses ${die_face} as the extra die'), [
            'playerId' => $context->currentPlayerId,
            'player_name' => $context->game->getPlayerNameById($context->currentPlayerId),
            'die_face' => $context->game->getDieFaceLogName($face, $die->type),
        ]);

        $context->game->goToState(-1);
    }
}
