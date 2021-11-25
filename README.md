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

Group message :
Battle of the gods (part I) event for King of Tokyo is now activated !
It adds Cultists and 2 bonus monsters !
All details here : <todo lien forum>

Forum post :
[OFFICIAL] Battle of the gods (part I) event

[b]Battle of the gods (part I) event is now available in King of Tokyo ![/b]

This option adds Cultists. After resolving your dice, if you rolled four identical faces, take a Cultist tile. At any time, you can discard one of your Cultist tiles to gain either: 1 heart, 1 energy, or one extra Roll.

[img]<TODO lien image>[/img]

If you want to play with Battle of the gods (part I) event :
[list]
[b]Automatic mode :[/b] On the game settings (gear icon on the lobby), in "Battle of the gods (part I) event" section, set "Enabled" to "I'm okay playing with". [i]If you want to play only Battle of the gods (part I) Event, set "Disabled" to "I never want to play with". You can also pick intermediate values to play mostly with Battle of the gods (part I) Event.[/i]
[/list]
[list]
[b]Manual mode :[/b] set "Battle of the gods (part I) event" to "Enabled" when creating the table.
[/list]

The 2 new monsters added with Battle of the gods (part I) event are also available even outside of Battle of the gods (part I) event ("Bonus monsters" option).

Have fun !