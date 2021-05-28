define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.kingoftokyo", ebg.core.gamegui, {
        constructor: () => {
            this.kot = new KingOfTokyo(this);
        },
        setup: (gamedatas) => this.kot.setup(gamedatas),
        onEnteringState: (stateName: string, args: any) => this.kot.onEnteringState(stateName, args),
        onLeavingState: (stateName: string ) => this.kot.onLeavingState(stateName),
        onUpdateActionButtons: (stateName: string, args: any) => this.kot.onUpdateActionButtons(stateName, args),
        setupNotifications: () => this.kot.setupNotifications(),
        format_string_recursive: (log: string, args: any) => this.kot.format_string_recursive(log, args),
    });             
});