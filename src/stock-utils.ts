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