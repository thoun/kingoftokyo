# TODO
Hi Jurica,
Can you ask art files for King of Tokyo ?

As cards have translated text on them, it would be nice to have :
 - cards art without text (but cost can be on it), and text apart.
 - or complete cards in English, in a format were I can split text from art (for example PSD file).

Other items I hope to find on art : monster boards, cardboard figures, tokyo board, tokens, dices & boards icons (ideally in vectorial/SVG format).

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