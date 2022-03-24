# What is this project ? 
This project is an adaptation for BoardGameArena of game King of Tokyo edited by Iello.
You can play here : https://boardgamearena.com

# How to install the auto-build stack

## Install builders
Intall node/npm then `npm i` on the root folder to get builders.

## Auto build JS and CSS files
In VS Code, add extension https://marketplace.visualstudio.com/items?itemName=emeraldwalk.RunOnSave and then add to config.json extension part :
```json
        "commands": [
            {
                "match": ".*\\.ts$",
                "isAsync": true,
                "cmd": "npm run build:ts"
            },
            {
                "match": ".*\\.scss$",
                "isAsync": true,
                "cmd": "npm run build:scss"
            }
        ]
    }
```
If you use it for another game, replace `kingoftokyo` mentions on package.json `build:scss` script and on tsconfig.json `files` property.

## Auto-upload builded files
Also add one auto-FTP upload extension (for example https://marketplace.visualstudio.com/items?itemName=lukasz-wronski.ftp-sync) and configure it. The extension will detected modified files in the workspace, including builded ones, and upload them to remote server.

## Hint
Make sure ftp-sync.json and node_modules are in .gitignore

# How to start PHP unit test
go on tests dir and start execute file, for example `php ./kingoftokyo.game.test-dice-sort.php`

# TODO
add animation for smashes even if no smash dice (poison quills)
slide energy cubes from battery monster

TODO check psychic probe with Background Dweller allowing to reroll a 3 that's not PB die => /bug?id=51953
TODOWI find an icon for wickedness
TODODE add new images
TODODE handle cards cost differences
TODODE handle cards color/style differences
TODODE handle it has a child text difference
TODOPU stats

log no energy/heart/points won

## PU Evolutions to play a timing :
when reaching 0 :
 - 25 Nine lives (Cyber Kitty)
at start turn :
 - 28 herbe à chat (Cyber Kitty) (before first roll?)
 - 36 mise à jour (meka dragon)
 - 54 laser insatiable (alienoid)
 - 70 réserve de bambous (pandakai)
 - 87 épée énergétique (cyber bunny)
 - 95 temple englouti (Kraken)
before resolving :
 - 92 grande marée (kraken)
after resolving
 - 34 analyse impitoyable (meka dragon) (or everytime ?)
when smashing :
 - 33 mecha blast (meka dragon) (can it also apply with bought cards or another evolution ?)
when tokyo monster is damaged by this player
 - 29 jouer avec sa nourriture (Cyber Kitty)
during move phase (after leaving, before entering) :
 - 30 propulseur félin (Cyber Kitty)
 - 57 ruée du grand singe (The King)
after move phase active player turn :
 - 59 gare au gorille (The King)
 - 66 panda guerilla (Pandakai)
 - 90 vague dévastatrice (Kraken)
during buy phase
 - 67 coup de bambou (Pandakai)
at the end of active player turn :
 - 44 vague de froid (space penguin) (or everytime ?)
 - 45 pris dans la glace (space penguin) (or everytime ?)
 - 46 blizzard (space penguin) (or everytime in player's turn ?)
 - 51 convertisseur d'énergie (alienoid) (or everytime ?)
 - 81 coup de génie (cyber bunny) (or everytime ?)
 - 91 adorateurs cultistes (Kraken) (or everytime ?)

Demander à Jurica de figer en Arène :
 Mutant Evolutions variant (Transformation card)

Forum :
[b]“Nature vs. Machine: the Comeback!” event is now available in King of Tokyo ![/b]

This option adds the Berserk die, allowing you to play with an offensive extra die! Roll 4 or more claw to activate Berserk mode, and you will keep it until you heal yourself.

[img]https://www.zicbook.com/cdn-bga/march.jpg[/img]

If you want to play with “Nature vs. Machine: the Comeback!” event :
[list]
[b]Automatic mode :[/b] On the game settings (gear icon on the lobby), in “Nature vs. Machine: the Comeback!” Event section, set "Enabled" to "I'm okay playing with". [i]If you want to play only “Nature vs. Machine: the Comeback!” Event, set "Disabled" to "I never want to play with". You can also pick intermediate values to play mostly with “Nature vs. Machine: the Comeback!” Event.[/i]
[/list]
[list]
[b]Manual mode :[/b] set “Nature vs. Machine: the Comeback!” Event to "Enabled" when creating the table.
[/list]

But thats not all! There is also the [b]Mutant Evolution variant[/b] from Cybertooth Monster Pack.
Change to beast form to have one extra die roll, or stay in biped form to be able to buy Power cards.
This one will not be activated by default, but you can activate it by following above instructions, but for "Mutant Evolution variant" option.

As you can see in the picture, there will be more surprises for King of Tokyo! What do you think will be next?

Have fun playing this new version of King of Tokyo!