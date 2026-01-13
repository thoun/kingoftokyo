<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Item;

/**
 * @template T of object
 */
class CardManager extends ItemManager {
    protected ItemManager $itemManager;

    /**
     * @param class-string<T> $className The Item object class. Defaults to stdClass.
     * @param ItemLocation[] $locations The different possible locations, used for autoReshuffle.
     */
    public function __construct(
        string $className = \stdClass::class,
        array $locations = [],
    ) {
        $this->itemManager = new class($className, $this) extends ItemManager {
            public function __construct(string $className, private CardManager $manager) {
                parent::__construct($className);
            }

            public function getClassName(?array $dbItem): ?string {
                return $this->manager->getClassName($dbItem);
            }
        };

        $this->itemManager->addLocations($locations);
    }

    /**
     * Create the DB table. 
     * Should be called at the beginning of Game::setupNewGame.
     */
    public function initDb(): void {
        $this->itemManager->initDb();
    }

    /**
     * Create new items in the DB.
     * 
     * $itemsTypes should be like this :
     * [
     *   ['location' => 'deck', 'type' => 0, 'item_nbr' => 2],
     *   ['location' => 'table', 'locationArg' => 1, 'type' => 1, 'flipped' => true],
     * ]
     * 
     * item_nbr is a special field, that will create this number of items. If unset, defaults to 1.
     * 
     * @param array[] $itemsTypes An array of arrays, where each inner array follows the described structure.
     */
    public function createCards(array $cardTypes): void {
        $this->itemManager->createItems($cardTypes);
    }

    /**
     * Shuffle the order of the items in a location.
     */
    public function shuffle(string $location, ?int $locationArg = null): void {
        $this->itemManager->shuffle($location, $locationArg);
    }

    public function moveAllCardsInLocation(?string $fromLocation, string $toLocation, ?int $toLocationArg = 0): void {        
        $this->itemManager->moveAllItemsInLocation($fromLocation, $toLocation, $toLocationArg);
    }

    /**
     * Pick an item from a location into another location.
     *
     * @param string $fromLocation location to pick the item
     * @param int|null $fromLocationArg locationArg to pick the item
     * @param string $toLocation location to put the picked item
     * @param int $toLocationArg locationArg to put the picked item
     * 
     * @return T|null An object of the type specified by $this->className, or null if no item is picked.
     */
    public function pickCardForLocation(string $fromLocation, ?int $fromLocationArg = null, string $toLocation, int $toLocationArg = 0): ?object {
        return $this->itemManager->pickItemForLocation($fromLocation, $fromLocationArg, $toLocation, $toLocationArg);
    }

    /**
     * Pick items from a location into another location.
     *
     * @param int $number the nbumber of items to pick
     * @param string $fromLocation location to pick the items
     * @param int|null $fromLocationArg locationArg to pick the items
     * @param string $toLocation location to put the picked items
     * @param int $toLocationArg locationArg to put the picked items
     * 
     * @return T[] An array of objects of the type specified by $this->className.
     */
    public function pickCardsForLocation(int $number, string $fromLocation, ?int $fromLocationArg = null, string $toLocation, int $toLocationArg = 0): array {
        return $this->itemManager->pickItemsForLocation($number, $fromLocation, $fromLocationArg, $toLocation, $toLocationArg);
    }

    public function setCardOrder(object $item, int $order): void {
        $this->itemManager->setItemOrder($item, $order);
    }

    public function moveCard(object $item, string $toLocation, int $toLocationArg = 0, ?int $order = null): void {
        $this->itemManager->moveItem($item, $toLocation, $toLocationArg, $order);
    }

    public function moveCards(array $items, string $toLocation, int $toLocationArg = 0): void {
        $this->itemManager->moveItems($items, $toLocation, $toLocationArg);
    }

    public function moveCardKeepOrder(object $item, string $toLocation, int $toLocationArg = 0): void {
        $this->itemManager->moveItemKeepOrder($item, $toLocation, $toLocationArg);
    }

    public function moveCardsKeepOrder(array $items, string $toLocation, int $toLocationArg = 0): void {
        $this->itemManager->moveItemsKeepOrder($items, $toLocation, $toLocationArg);
    }

    /**
     * @return T[] An array of objects of the type specified by $this->className.
     */
    public function getCardsByFieldName(string $fieldName, array $values, ?int $limit = null): array {
        return $this->itemManager->getItemsByFieldName($fieldName, $values, $limit);
    }

    /**
     * @return T[] An array of objects of the type specified by $this->className.
     */
    public function getCardsByField(ItemField $field, array $values, ?int $limit = null): array {
        return $this->itemManager->getItemsByField($field, $values, $limit);
    }

    /**
     * @return T|null An object of the type specified by $this->className, or null if no item is picked.
     */
    public function getCardById(int $id): ?object {
        return $this->itemManager->getItemById($id);
    }

    public function getCardsByIds(array $ids): array {
        return $this->itemManager->getItemsByIds($ids);
    }

    public function countCardsInLocation(string $location, ?int $locationArg = null): int {
        return $this->itemManager->countItemsInLocation($location, $locationArg);
    }

    public function getMaxOrderInLocation(string $location, ?int $locationArg = null): int {
        return $this->itemManager->getMaxOrderInLocation($location, $locationArg);
    }

    /**
     * @return T[] An array of objects of the type specified by $this->className.
     */
    public function getCardsInLocation(string $location, ?int $locationArg = null, bool $reversed = false, ?int $limit = null, ?string $sortByField = null): array {
        return $this->itemManager->getItemsInLocation($location, $locationArg, $reversed, $limit, $sortByField);
    }

    /**
     * @return T[] An array of objects of the type specified by $this->className.
     */
    public function getAllCards(?int $limit = null): array {
        return $this->itemManager->getAllItems($limit);
    }

	/**
     * @return T|null An object of the type specified by $this->className, or null if no item is picked.
     */
    public function getCardOnTop(string $location, ?int $locationArg = null): ?object {
        return $this->itemManager->getItemOnTop($location, $locationArg);
	}

	/**
     * @return T[] An array of objects of the type specified by $this->className.
     */
    public function getCardsOnTop(int $number, string $location, ?int $locationArg = null): array {
        return $this->itemManager->getItemsOnTop($number, $location, $locationArg);
	}

    /**
     * Update the DB value based on the Item fields.
     * Set $fields to set which fields to update (all if null)
     */
    public function updateCard(object $item, array|string|null $fields = null): void {
        $this->itemManager->updateItem($item, $fields);
    }

    /**
     * Update the DB value based on the Item fields.
     * Set $fields to set which fields to update (all if null)
     */
    public function updateAllCards(string $fieldName, mixed $value): void {
        $this->itemManager->updateAllItems($fieldName, $value);
    }

    public function getCardField(string $name): ?ItemField {
        return $this->itemManager->getItemField($name);
    }

    public function getCardFieldByKind(string $kind): ?ItemField {
        return $this->itemManager->getItemFieldByKind($kind);
    }

    public function getClassName(?array $dbItem): ?string {
        return null;
    }

    /**
     * @return T|null An object of the type specified by $this->className, or null if no item is picked.
     */
    public function getCardFromDb(?array $dbItem): ?object {
        return $this->itemManager->getItemFromDb($dbItem);
    }
}
