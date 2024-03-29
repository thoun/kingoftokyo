function formatTextIcons(rawText: string) {
    if (!rawText) {
        return '';
    }
    return rawText
        .replace(/\[Star\]/ig, '<span class="icon points"></span>')
        .replace(/\[Heart\]/ig, '<span class="icon health"></span>')
        .replace(/\[Energy\]/ig, '<span class="icon energy"></span>')
        .replace(/\[Skull\]/ig, '<span class="icon dead"></span>')
        .replace(/\[dic?e1\]/ig, '<span class="dice-icon dice1"></span>')
        .replace(/\[dic?e2\]/ig, '<span class="dice-icon dice2"></span>')
        .replace(/\[dic?e3\]/ig, '<span class="dice-icon dice3"></span>')
        .replace(/\[dic?eHeart\]/ig, '<span class="dice-icon dice4"></span>')
        .replace(/\[dic?eEnergy\]/ig, '<span class="dice-icon dice5"></span>')
        .replace(/\[dic?eSmash\]/ig, '<span class="dice-icon dice6"></span>')
        .replace(/\[dic?eClaw\]/ig, '<span class="dice-icon dice6"></span>')
        .replace(/\[dieFateEye\]/ig, '<span class="dice-icon die-of-fate eye"></span>')
        .replace(/\[dieFateRiver\]/ig, '<span class="dice-icon die-of-fate river"></span>')
        .replace(/\[dieFateSnake\]/ig, '<span class="dice-icon die-of-fate snake"></span>')
        .replace(/\[dieFateAnkh\]/ig, '<span class="dice-icon die-of-fate ankh"></span>')
        .replace(/\[berserkDieEnergy\]/ig, '<span class="dice-icon berserk dice1"></span>')
        .replace(/\[berserkDieDoubleEnergy\]/ig, '<span class="dice-icon berserk dice2"></span>')
        .replace(/\[berserkDieSmash\]/ig, '<span class="dice-icon berserk dice3"></span>')
        .replace(/\[berserkDieDoubleSmash\]/ig, '<span class="dice-icon berserk dice5"></span>')
        .replace(/\[berserkDieSkull\]/ig, '<span class="dice-icon berserk dice6"></span>')
        .replace(/\[snowflakeToken\]/ig, '<span class="icy-reflection token"></span>')
        .replace(/\[ufoToken\]/ig, '<span class="ufo token"></span>')
        .replace(/\[alienoidToken\]/ig, '<span class="alienoid token"></span>')
        .replace(/\[targetToken\]/ig, '<span class="target token"></span>')

        .replace(/\[keep\]/ig, `<span class="card-keep-text"><span class="outline">${_('Keep')}</span><span class="text">${_('Keep')}</span></span>`)
        .replace(/\[discard\]/ig, `<span class="card-discard-text"><span class="outline">${_('Discard')}</span><span class="text">${_('Discard')}</span></span>`);
}