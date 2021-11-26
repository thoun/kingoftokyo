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

TODOKK add stats
TODOAN add stats
TODOCY add stats
TODOCY check what happens if healed by Healing Ray
TODOCY confirm forbidden heal & heal on tokens shouldn't remove berserk
TODOCY confirm rolling 4 claws & a heart still activate berserk
TODOCY confirm double smash/double energy can be used to gain cultists
TODOME add stats
TODOME flip cards (remove 302)
TODO check psychic probe with Background Dweller allowing to reroll a 3 that's not PB die => /bug?id=51953

remaining Anubis :

After resolving the die of Fate,
the Monster with the Golden Scarab
can force you to reroll up to 2 dice
of his choice.

At the start of each turn,
the Monster with the Golden Scarab
must give 1 / / to the
Monster whose turn it is.

Take an extra die
and put it on the
face of your choice.

Draw a Power card.

Take the Golden
Scarab and give it to
the Monster of your
choice.

Choose up to 2 dice,
you can reroll
or discard each
of these dice.

Discard 1 die.

Discard a Keep card. (x2)

The Monster with
the Golden Scarab,
instead of you, gains
all and that you
should have gained
this turn.

Give any
combination
of 2 / /
to the Monster with
the Golden Scarab.

The player on your
left chooses two
of your dice.
Reroll these dice.

Discard 1 [dice1]