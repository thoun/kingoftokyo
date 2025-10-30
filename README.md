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

log no energy/heart/points won

Questions to Florent :
Parasitic Tentacles : can buy Keep cards => can buy Power cards : are costumes considered Power cards ?
Rooting for the Underdog : At the end of your turn => At the end of a turn => the turn of every monster, or just ours ?
It has a child : and continue playing outside Tokyo. => and continue playing. Does the player now stays in Tokyo ? adapt Dark Edition text as well ?
Alien Origin : Buying cards => Buying Power cards => does it still applies to costumes ?



private alpha with Goku_2020 dc_existential