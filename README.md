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

# TODO
add animation for smashes even if no smash dice (poison quills)
slide energy cubes from battery monster
add english rules in wiki
when locking some dice then reroll 3s with background dweller without rethrowing, locked dice just before this action are lost (not important)

2p variant + Friend of Children
if a player is smashed and shoulg get a token, but escapes damage with wings / camouflage, does he still gets the token ? considered yes

does herbivore applies on turn it's bought ? considered yes

5-6 players : if a player leaves Tokyo city but the other one chooses to stay in Tokyo Bay, does smashing player takes the empty spot, or is Tokyo Bay player moved to City before other player enters ? (then he would enter in Tokyo Bay) Considered take leaver's place

Remove log for dead people