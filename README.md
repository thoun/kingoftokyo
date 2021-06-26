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
sometimes a shadow stays with dice (a 7 ghost die)
can background dweller be used with camouflage rolls ? or after psychic probe, by active player ? or on a psychic probe roll, if player with psychic probe also got background dweller ?
make heart animations go to position/sr tokens or healed players with healing ray
make a tutorial
add english rules in wiki
invisible dice : same bug for every player
after buying a card, argBuyCard is not refreshed
when locking some dice then reroll 3s with background dweller without rethrowing, locked dice just before this action are lost (not important)

Quelques questions à propose de la carte Background Dweller/Ninja Urbain)
Est-ce que le joueur peut l'utiliser pour un lancer de dés de la carte Camouflage ?
Est-ce qu'un joueur peut la jouer après Psychic Probe, si le résultat est un 3 ?
Est-ce qu(un) joueur qui possède à la fois Background Dweller et Psychic Probe peut relancer un 3 qu'il a obtenu avec Psychic Probe ? 