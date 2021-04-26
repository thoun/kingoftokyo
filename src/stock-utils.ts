function moveToAnotherStock(sourceStock: Stock, destinationStock: Stock, uniqueId: number, cardId: string) {
    if (sourceStock === destinationStock) {
        return;
    }
    
    const sourceStockItemId = `${sourceStock.container_div.id}_item_${cardId}`;
    if (document.getElementById(sourceStockItemId)) {        
        destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
        sourceStock.removeFromStockById(cardId);
    } else {
        console.warn(`${sourceStockItemId} not found in `, sourceStock);
        destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
    }
}
    
function setupCards(stocks: Stock[]) {

    stocks.forEach(stock => {
        const keepcardsurl = `${g_gamethemeurl}img/cards0.jpg`;
        for(let id=1; id<=48; id++) {  // keep
            stock.addItemType(id, id, keepcardsurl, id);
        }

        const discardcardsurl = `${g_gamethemeurl}img/cards1.jpg`;
        for(let id=101; id<=118; id++) {  // keep
            stock.addItemType(id, id, discardcardsurl, id);
        }
    });
} 

function setupNewCard(card_div: HTMLDivElement, card_type_id: number, card_id: string) {
    const type = card_type_id < 100 ? _('Keep') : _('Discard');
    const name = 'Name';
    card_div.innerHTML = `
    <div class="name-wrapper">
        <div class="outline">${name}</div>
        <div class="text">${name}</div>
    </div>
    <div class="type-wrapper ${ card_type_id < 100 ? 'keep' : 'discard'}">
        <div class="outline">${type}</div>
        <div class="text">${type}</div>
    </div>
    <div class="description-wrapper">
        description
    </div>
    `;
}