<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * KingOfTokyo implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * KingOfTokyo game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->cardsCosts = [
  /*
KEEP

Acid Attack	6	Keep	Deal 1 extra damage each turn (even when you don't otherwise attack).
Alien Metabolism	3	Keep	Buying cards costs you 1 less [Energy].
Alpha Monster	5	Keep	Gain 1[Star] when you attack.
Armor Plating	4	Keep	Ignore damage of 1.
Background Dweller	4	Keep	You can always reroll any [3] you have.
Burrowing	5	Keep	Deal 1 extra damage on Tokyo. Deal 1 damage when yielding Tokyo to the monster taking it.
Camouflage	3	Keep	If you take damage roll a die for each damage point. On a [Heart] you do not take that damage point.
Complete Destruction	3	Keep	If you roll [1][2][3][Heart][Attack][Energy] gain 9[Star] in addition to the regular results.
Dedicated News Team	3	Keep	Gain 1[Star] whenever you buy a card.
Eater of the Dead	4	Keep	Gain 3[Star] every time a monster's [Heart] goes to 0.
Energy Hoarder	3	Keep	You gain 1[Star] for every 6[Energy] you have at the end of your turn.
Even Bigger	4	Keep	Your maximum [Heart] is increased by 2. Gain 2[Heart] when you get this card.
Extra Head (x2)	7	Keep	You get 1 extra die.
Fire Breathing	4	Keep	Your neighbors take 1 extra damage when you deal damage
Freeze Time	5	Keep	On a turn where you score [1][1][1], you can take another turn with one less die.
Friend of Children	3	Keep	When you gain any [Energy] gain 1 extra [Energy].
Giant Brain	5	Keep	You have one extra reroll each turn.
Gourmet	4	Keep	When scoring [1][1][1] gain 2 extra [Star].
Healing Ray	4	Keep	You can heal other monsters with your [Heart] results. They must pay you 2[Energy] for each damage you heal (or their remaining [Energy] if they haven't got enough.
Herbivore	5	Keep	Gain 1[Star] on your turn if you don't damage anyone.
Herd Culler	3	Keep	You can change one of your dice to a [1] each turn.
It Has a Child	7	Keep	If you are eliminated discard all your cards and lose all your [Star], Heal to 10[Heart] and start again.
Jets	5	Keep	You suffer no damage when yielding Tokyo.
Made in a Lab	2	Keep	When purchasing cards you can peek at and purchase the top card of the deck.
Metamorph	3	Keep	At the end of your turn you can discard any keep cards you have to receive the [Energy] they were purchased for.
Mimic	8	Keep	Choose a card any monster has in play and put a mimic counter on it. This card counts as a duplicate of that card as if it just had been bought. Spend 1[Energy] at the start of your turn to change the power you are mimicking.
Monster Batteries	2	Keep	When you purchase this put as many [Energy] as you want on it from your reserve. Match this from the bank. At the start of each turn take 2[Energy] off and add them to your reserve. When there are no [Energy] left discard this card.
Nova Breath	7	Keep	Your attacks damage all other monsters.
Omnivore	4	Keep	Once each turn you can score [1][2][3] for 2[Star]. You can use these dice in other combinations.
Opportunist	3	Keep	Whenever a new card is revealed you have the option of purchasing it as soon as it is revealed.
Parasitic Tentacles	4	Keep	You can purchase cards from other monsters. Pay them the [Energy] cost.
Plot Twist	3	Keep	Change one die to any result. Discard when used.
Poison Quills	3	Keep	When you score [2][2][2] also deal 2 damage.
Poison Spit	4	Keep	When you deal damage to monsters give them a poison counter. Monsters take 1 damage for each poison counter they have at the end of their turn. You can get rid of a poison counter with a [Heart] (that [Heart] doesn't heal a damage also).
Psychic Probe	3	Keep	You can reroll a die of each other monster once each turn. If the reroll is [Heart] discard this card.
Rapid Healing	3	Keep	Spend 2[Energy] at any time to heal 1 damage.
Regeneration	4	Keep	When you heal, heal 1 extra damage.
Rooting for the Underdog	3	Keep	At the end of a turn when you have the fewest [Star] gain 1 [Star].
Shrink Ray	6	Keep	When you deal damage to monsters give them a shrink counter. A monster rolls one less die for each shrink counter. You can get rid of a shrink counter with a [Heart] (that [Heart] doesn't heal a damage also).
Smoke Cloud	4	Keep	This card starts with 3 charges. Spend a charge for an extra reroll. Discard this card when all charges are spent.
Solar Powered	2	Keep	At the end of your turn gain 1[Energy] if you have no [Energy].
Spiked Tail	5	Keep	When you attack deal 1 extra damage.
Stretchy	3	Keep	You can spend 2[Energy] to change one of your dice to any result.
Telepath	4	Keep	Spend 1[Energy] to get 1 extra reroll.
Urbavore	4	Keep	Gain 1 extra [Star] when beginning the turn in Tokyo. Deal 1 extra damage when dealing any damage from Tokyo.
We're Only Making It Stronger	3	Keep	When you lose 2[Heart] or more gain 1[Energy].
Wings	6	Keep	Spend 2[Energy] to negate damage to you for a turn.
Cannibalistic	5	Keep	When you do damage gain 1[Heart].
Intimidating Roar	3	Keep	The monsters in Tokyo must yield if you damage them.
Monster Sidekick	4	Keep	If someone kills you, Go back to 10[Heart] and lose all your [Star]. If either of you or your killer win, or all other players are eliminated then you both win. If your killer is eliminated then you are also. If you are eliminated a second time this card has no effect.
Reflective Hide	6	Keep	If you suffer damage the monster that inflicted the damage suffers 1 as well.
Sleep Walker	3	Keep	Spend 3[Energy] to gain 1[Star].
Super Jump	4	Keep	Once each turn you may spend 1[Energy] to negate 1 damage you are receiving.
Throw a Tanker	4	Keep	On a turn you deal 3 or more damage gain 2[Star].
Thunder Stomp	3	Keep	If you score 4[Star] in a turn, all players roll one less die until your next turn.
Unstable DNA	3	Keep	If you yield Tokyo you can take any card the recipient has and give him this card.

*/
// discard
101 => 5,
102 => 4,
103 => 3,
104 => 5,
105 => 8,
106 => 7,
107 => 7,
108 => 3,
109 => 7,
110 => 6,
111 => 3,
112 => 4,
113 => 5,
114 => 3,
115 => 6,
116 => 6,
117 => 4,
118 => 6,
119 => 6,
120 => 2,
];
