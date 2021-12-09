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
TODOAN activate stats
TODOCY add stats
TODOCY check what happens if healed by Healing Ray
TODOCY confirm forbidden heal & heal on tokens shouldn't remove berserk
TODOCY confirm rolling 4 claws & a heart still activate berserk
TODOCY confirm double smash/double energy can be used to gain cultists
TODOME add stats
TODOME flip cards (remove 302)
TODO check psychic probe with Background Dweller allowing to reroll a 3 that's not PB die => /bug?id=51953
