<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Item;

/**
 * @template T of object
 */
class CardManager extends ItemManager {

    /**
     * @param class-string<T> $className The Item object class. Defaults to stdClass.
     * @param ItemLocation[] $locations The different possible locations, used for autoReshuffle.
     */
    public function __construct(
        protected string $className = \stdClass::class,
        protected array $locations = [],
    ) {
        parent::__construct($className, $locations);

        foreach (['id', 'location', 'location_arg', 'order'] as $mandatoryKind) {
            if (!array_find($this->fields, fn($field) => $field->kind === $mandatoryKind)) {
                throw new ItemManagerConfigurationException("A mandatory #[ItemField(kind: '$mandatoryKind')] attribute is missing on a $className field");
            }
        }
    }

    /**
     * Shuffle the order of the items in a location.
     */
    public function shuffle(string $location, ?int $locationArg = null): void {
        $idField = $this->getItemFieldByKind('id');
        $locationField = $this->getItemFieldByKind('location');
        $locationArgField = $this->getItemFieldByKind('location_arg');
        $orderField = $this->getItemFieldByKind('order');

        $where = $this->db->sqlEqualValue($locationField, $location);
        if ($locationArg !== NULL) {
            $where .= " AND ".$this->db->sqlEqualValue($locationArgField, $locationArg);
        }
        $item_ids = $this->db->sqlGetList("`{$idField->dbField}`", $where);
        $item_ids = array_values(array_map(fn($dbObject) => intval($dbObject[$idField->dbField]), $item_ids));
        
        array_shuffle_bga_rand( $item_ids );
        
        foreach( $item_ids as $index => $item_id ) {
            $this->db->sqlUpdate(
                $this->db->sqlEqualValue($orderField, $index), 
                $this->db->sqlEqualValue($idField, $item_id)
            );
        }
    }

    public function moveAllItemsInLocation(?string $fromLocation, string $toLocation, ?int $toLocationArg = 0): void {
        $locationField = $this->getItemFieldByKind('location');
        $locationArgField = $this->getItemFieldByKind('location_arg');
        $orderField = $this->getItemFieldByKind('order');

        $update = $this->db->sqlEqualValue($locationField, $toLocation);
        if ($toLocationArg !== null) {
            $update .= ", ".$this->db->sqlEqualValue($locationArgField, $toLocationArg);
        }
        $update .= ", ".$this->db->sqlEqualValue($orderField, 0);
        $where = $fromLocation === null ? '1' : $this->db->sqlEqualValue($locationField, $fromLocation);
        $this->db->sqlUpdate($this->db->sqlEqualValue($locationField, $toLocation), $where);
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
    public function pickItemsForLocation(int $number, string $fromLocation, ?int $fromLocationArg = null, string $toLocation, int $toLocationArg = 0): array {
        $items = $this->getItemsInLocation($fromLocation, $fromLocationArg, reversed: true, limit: $number);
        if (count($items) < $number) {
            $itemLocation = array_find($this->locations, fn($location) => $location->name === $fromLocation);
            if ($itemLocation && $itemLocation->autoReshuffleFrom !== null) {
                // reshuffle
                $this->moveAllItemsInLocation($itemLocation->autoReshuffleFrom, $fromLocation);
                $this->shuffle($fromLocation);

                $items = array_merge($items, $this->getItemsInLocation($fromLocation, reversed: true, limit: ($number - count($items))));
            }
        }
        if (count($items) > 0) {
            $this->moveItems($items, $toLocation, $toLocationArg);
        }
        return $items;
    }

	/**
     * @return T|null An object of the type specified by $this->className, or null if no item is picked.
     */
    public function getItemOnTop(string $location, ?int $locationArg = null): ?object {
        $items = $this->getItemsOnTop(1, $location, $locationArg);
        return count($items) > 0 ? $items[0] : null;
	}

	/**
     * @return T[] An array of objects of the type specified by $this->className.
     */
    public function getItemsOnTop(int $number, string $location, ?int $locationArg = null): array {
        return $this->getItemsInLocation($location, $locationArg, true, $number);
	}
}
