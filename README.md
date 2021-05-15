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
check all TOCHECK with editor
Stack notifications for dice + card effects to make them the right order
Make animations from cards when cards add smashes/energy/health during dice count ?
roll wheels after or during animation
Fix dice position when clicking them
Add option to leave tokyo as soon as possible
Add checkActions & more security (reroll 3 have dice3, ...)
Freeze animations on roll 3
Add a oncePerTurnCardUsed array in globol variables reseted at stPlayerStart
split states : stResolveHeartNoAction & stResolveHeartAction, do one of them
mimic : apply mimicked card effect on choose/change