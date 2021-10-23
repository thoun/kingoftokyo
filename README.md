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

Forum post :

[OFFICIAL] Halloween event

[b]Halloween event is now available in King of Tokyo ![/b]

<image from gamedisplay1>

If you want to play with halloween event :
[list]
[b]Automatic mode :[/b] On the game settings (gear icon on the lobby), in "Halloween event" section, set "Enabled" to "I'm okay playing with". If you want to play only Halloween Event, set "Disabled to "I never want to play with".
[/list]
[list]
[b]Manual mode :[/b] set "Halloween event" to "Enabled" when creating the table.
[/list]

The 2 new monsters added with Halloween event are also available even outside of Halloween event ("Bonus monsters" option).

In your user preferences, you can also choose between halloween or standard background/dice. Automatic means it will set background/dice to match the game settings.

Have fun !

Add section in excel file :

Are smashes added with cards (acid attack, poison quills, ...) counted for the 3 smashes rule to steal Costume cards ? Yes
Does the player with Astronaut reaching 17⭐ needs to survive his turn ? Yes, same as when players reach 20⭐
Is Witch player applied if there is only 1 smash, and Witch player got Armor Plating ? No
With Robot, is it possible to lose part of hearts and part of energy ? Yes

Post message on Player's group :

Halloween event is now available in King of Tokyo !
If you want to know all the details about it, check forum post here : <form link>

Add links :
french : https://www.iello.fr/regles/KOT_HALLOWEEN_regles.pdf
english : https://boardgamegeek.com/file/download_redirect/b64f2377f43ca3ddc7dda74ac56720d94524a769cd00b283/KOT-Halloween-Rules.pdf