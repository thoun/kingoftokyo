function formatTextIcons(rawText: string) {
    if (!rawText) {
        return '';
    }
    return rawText
        .replace(/\[Star\]/ig, '<span class="icon points"></span>')
        .replace(/\[Heart\]/ig, '<span class="icon health"></span>')
        .replace(/\[Energy\]/ig, '<span class="icon energy"></span>')
        .replace(/\[dice1\]/ig, '<span class="dice-icon dice1"></span>')
        .replace(/\[dice2\]/ig, '<span class="dice-icon dice2"></span>')
        .replace(/\[dice3\]/ig, '<span class="dice-icon dice3"></span>')
        .replace(/\[diceHeart\]/ig, '<span class="dice-icon dice4"></span>')
        .replace(/\[diceEnergy\]/ig, '<span class="dice-icon dice5"></span>')
        .replace(/\[diceSmash\]/ig, '<span class="dice-icon dice6"></span>')
        .replace(/\[dieFateEye\]/ig, '<span class="die-of-fate icon eye"></span>')
        .replace(/\[dieFateRiver\]/ig, '<span class="die-of-fate icon river"></span>')
        .replace(/\[dieFateSnake\]/ig, '<span class="die-of-fate icon snake"></span>')
        .replace(/\[dieFateAnkh\]/ig, '<span class="die-of-fate icon ankh"></span>')
        .replace(/\[keep\]/ig, `<span class="card-keep-text"><span class="outline">${_('Keep')}</span><span class="text">${_('Keep')}</span></span>`);
}