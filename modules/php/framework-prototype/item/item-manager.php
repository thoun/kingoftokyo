<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Item;

use function Bga\Games\KingOfTokyo\debug;

function array_find(array $array, callable $fn) {
    foreach ($array as $value) {
        if($fn($value)) {
            return $value;
        }
    }
    return null;
}
function array_shuffle_bga_rand(array &$array): void {
    $n = count($array);
    for ($i = $n - 1; $i > 0; $i--) {
        // Use bga_rand instead of rand()
        $j = bga_rand(0, $i);
        // Swap elements at indices $i and $j
        [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
    }
}

class ItemManagerConfigurationException extends \BgaSystemException {}

class ItemManagerDbService {
    public function __construct(
        protected string $tableName,
    ) {}

    public function sqlCreate(string $columns) {
        /** @disregard */
        \APP_DbObject::DbQuery("CREATE TABLE `{$this->tableName}` ($columns) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
    }

    public function sqlInsert(string $columns, string $values) {
        /** @disregard */
        \APP_DbObject::DbQuery("INSERT INTO `{$this->tableName}` ($columns) VALUES $values");
    }

    public function sqlUpdate(string $updates, string $condition) {
        /** @disregard */
        \APP_DbObject::DbQuery("UPDATE `{$this->tableName}` SET $updates WHERE $condition");
    }

    public function sqlGetValue(string $column, string $condition) {
        /** @disregard */
        return \APP_DbObject::getUniqueValueFromDB("SELECT $column FROM `{$this->tableName}` WHERE $condition");
    }

    public function sqlGetList(string $columns = '*', ?string $condition = null, ?string $orderBy = null, ?int $limit = null) {
        $sql = "SELECT $columns FROM `{$this->tableName}`";
        if ($condition !== null) {
            $sql .= " WHERE $condition";
        }
        if ($orderBy !== null) {
            $sql .= " ORDER BY $orderBy";
        }
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }
        /** @disregard */
        return \APP_DbObject::getCollectionFromDb($sql);
    }

    public function sqlEqual(ItemField $field, mixed $item): string {
        return $this->sqlEqualValue($field, $item->{$field->name});
    }

    public function sqlEqualValue(ItemField $field, mixed $value): string {
        return "`{$field->dbField}` = ".$this->getSqlValue($field, $value);
    }

    public function sqlGreaterValue(ItemField $field, mixed $value, bool $orEqual = false): string {
        $operator = $orEqual ? '>=' : '>';
        return "`{$field->dbField}` $operator ".$this->getSqlValue($field, $value);
    }

    public function sqlLowerValue(ItemField $field, mixed $value, bool $orEqual = false): string {
        $operator = $orEqual ? '<=' : '<';
        return "`{$field->dbField}` $operator ".$this->getSqlValue($field, $value);
    }

    public function sqlInValues(ItemField $field, array $values): string {
        if (count($values) === 1) {
            return $this->sqlEqualValue($field, $values[0]);
        }

        return "`{$field->dbField}` IN (".implode(',', array_map(fn($value) => $this->getSqlValue($field, $value), $values)).")";
    }

    public function getSqlValue(ItemField $field, mixed $value): string {
        $sqlValueStr = 'NULL';
        if ($value !== null) {
            if ($field->type === 'bool') {
                $sqlValueStr = $value ? '1' : '0';
            } else if ($field->type === 'json') {
                $jsonObj = $this->objectToJson($value);
                $escapedJson = str_replace('\\"', '\\\\"', str_replace("'", "\'", $jsonObj));
                $sqlValueStr = "'{$escapedJson}'";
            } else {
                $sqlValueStr = "'{$value}'"; // TODO escape '
            }
        }
        return $sqlValueStr;
    }

    public function getValueFromSql(ItemField $field, ?string $sqlValue): mixed {
        $value = null;
        if ($sqlValue !== null) {
            if ($field->type === 'bool') {
                $value = boolval($sqlValue);
            } else if ($field->type === 'int') {
                $value = intval($sqlValue);
            } else if ($field->type === 'json') {
                $value = $this->jsonToObject($sqlValue);
            } else {
                $value = $sqlValue;
            }
        }
        return $value;
    }

    /**
     * When an associative array is encoded then decoded from json, it is transformed as an object.
     * We add a flag to mark it as an associative array so we can fix the type after decoding. Recursive for multi-level cases.
     */
    private function setAssociativeArrayFlag(mixed &$value): void {
        if (is_array($value)) {
            foreach ($value as $arrKey => &$arrValue) {
                $this->setAssociativeArrayFlag($arrValue);
            }

            if (!array_is_list($value)) {
                $value['___bga_associative_array_flag'] = true;
            }
        } else if (is_object($value)) {
            foreach ($value as $arrKey => &$arrValue) {
                $this->setAssociativeArrayFlag($arrValue);
            }
        }
    }

    /**
     * When an associative array is encoded then decoded from json, it is transformed as an object.
     * We change the object to array if the flag is present, and when remove the flag. Recursive for multi-level cases.
     */
    private function applyAssociativeArrayFlag(mixed &$value): void {
        if (is_array($value)) {
            foreach ($value as $arrKey => &$arrValue) {
                $this->applyAssociativeArrayFlag($arrValue);
            }
        } else if (is_object($value)) {
            foreach ($value as $arrKey => &$arrValue) {
                $this->applyAssociativeArrayFlag($arrValue);
            }

            if (property_exists($value, '___bga_associative_array_flag')) {
                $value = (array)$value;
                unset($value['___bga_associative_array_flag']);
            }
        }
    }

    /**
     * Transforms an object to JSON. Adds a special flag to remember associative arrays.
     */
    protected function objectToJson(mixed $obj): string {
        $this->setAssociativeArrayFlag($obj);
        return json_encode($obj);
    }

    /**
     * Transforms a JSON to an object. If a special flag is detected, transforms the object to an associative array.
     */
    protected function jsonToObject(string $json_obj): mixed {
        $object = json_decode($json_obj);
        $this->applyAssociativeArrayFlag($object);
        return $object;
    }
}

class ItemManager {
    protected ItemManagerDbService $db;

    /**
     * The DB table name.
     */
    protected string $tableName;

    /**
     * The Item object fields
     */
    protected array $fields = []; // array of ItemField

    public function __construct(
        /**
         * The Item Object class
         */
        protected string $className = \stdClass::class,

        /**
         * The different possible locations. Used for autoResuffle
         */
        protected array $locations, // array of ItemLocation
    ) {
        $this->readTableName();
        $this->readFields();

        foreach (['id', 'location', 'location_arg', 'order'] as $mandatoryKind) {
            if (!array_find($this->fields, fn($field) => $field->kind === $mandatoryKind)) {
                throw new ItemManagerConfigurationException("A mandatory #[ItemField(kind: '$mandatoryKind')] attribute is missing on a $className field");
            }
        }

        $this->db = new ItemManagerDbService($this->tableName);
    }

    /**
     * Read the DB table name with #[Item]
     */
    protected function readTableName() {
        $reflectionClass = new \ReflectionClass($this->className);
        $attributes = $reflectionClass->getAttributes(Item::class);
        if (empty($attributes)) {
            throw new ItemManagerConfigurationException("#[Item] attribute is not set on {$this->className}");
        }
        
        $attributeInstance = $attributes[0]->newInstance();
        $this->tableName = $attributeInstance->tableName;

        if (empty($this->tableName)) {
            throw new ItemManagerConfigurationException("#[Item] tableName is empty on {$this->className}");
        }
    }

    /**
     * Read the fiels of the item with #[ItemField]
     */
    protected function readFields() {
        $reflectionClass = new \ReflectionClass($this->className);
        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(ItemField::class);
            if (!empty($attributes)) {
                $attributeInstance = $attributes[0]->newInstance();
                $attributeInstance->name = $property->getName();
                if (empty($attributeInstance->dbField)) {
                    $attributeInstance->dbField = $attributeInstance->name;
                }
                if (empty($attributeInstance->type)) {
                    $attributeInstance->type = $property->getType()->getName();
                }
                $this->fields[] = $attributeInstance;
            }
        }
    }

    /**
     * Create the DB table. 
     * Should be called at the beginning of Game::setupNewGame.
     */
    public function initDb() {
        $columns = "";
        $idFieldName = null;

        foreach ($this->fields as &$field) {
            if (!in_array(strtolower($field->type), ['bool', 'int', 'float', 'double', 'string', 'json'])) {
                $field->type = 'string'; // trigger an exception instead?
            }

            $sqlType = match($field->type) {
                'bool' => 'TINYINT',
                'int' => 'INT',
                'float' => 'FLOAT',
                'double' => 'DOUBLE',
                'string' => 'VARCHAR(256)', // force to allow 2bits for char length (> 255)
                'json' => 'JSON',
            };

            $columns .= "`{$field->dbField}` $sqlType";
            if ($field->kind === 'id') {
                $idFieldName = $field->dbField;
                if ($field->type === 'int') {
                    $columns .= " AUTO_INCREMENT";
                }
            }
            $columns .= ", ";
        }
        $columns .= "PRIMARY KEY (`{$idFieldName}`)";
        $this->db->sqlCreate(
            $columns
        );
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
     */
    public function createItems(array $itemsTypes): void {
        $columns = [];
        foreach ($this->fields as &$field) {
            if ($field->kind !== 'id' || $field->type !== 'int') { // to ignore id autoincrement
                $columns[] = "`{$field->dbField}`";
            }
        }

        $values = [];
	    foreach( $itemsTypes as $itemtype ) {
            $value = [];

            foreach ($this->fields as &$field) {
                if ($field->kind !== 'id' || $field->type !== 'int') { // to ignore id autoincrement
                    $value[] = $this->db->getSqlValue($field, $itemtype[$field->name] ?? null);
                }
            }

            $valueStr = "(".implode( ",", $value ).")";
            $itemNumber = $itemtype['item_nbr'] ?? 1;

	        for( $i=0; $i < $itemNumber; $i++ ) {                
	            $values[] = $valueStr;
	        }
	    }

		if (count($values) == 0) {
            return; // Avoid SQL error if items list is empty
        }
	    
	    // Shuffle values to avoid that items of the same ID have always the same type
	    array_shuffle_bga_rand($values);

        $this->db->sqlInsert(
            implode(',', $columns), 
            implode( ",", $values)
        );
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

        $update = $this->db->sqlEqualValue($locationField, $toLocation);
        if ($toLocationArg !== null) {
            $update .= ", ".$this->db->sqlEqualValue($locationArgField, $toLocationArg);
        }
        $where = $fromLocation === null ? '1' : $this->db->sqlEqualValue($locationField, $fromLocation);
        $this->db->sqlUpdate($this->db->sqlEqualValue($locationField, $toLocation), $where);
    }

    public function pickItemForLocation(string $fromLocation, ?int $fromLocationArg = null, string $toLocation, int $toLocationArg = 0): mixed {
        $items = $this->pickItemsForLocation(1, $fromLocation, $fromLocationArg, $toLocation, $toLocationArg);
        return count($items) > 0 ? $items[0] : null;
    }

    public function pickItemsForLocation(int $number, string $fromLocation, ?int $fromLocationArg = null, string $toLocation, int $toLocationArg = 0): array {
        $items = $this->getItemsInLocation($fromLocation, $fromLocationArg, reversed: true, limit: $number);
        if (count($items) < $number) {
            $itemLocation = array_find($this->locations, fn($location) => $location->name === $fromLocation);
            if ($itemLocation->autoReshuffleFrom !== null) {
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

    public function setItemOrder(mixed $item, int $order): void {
        $orderField = $this->getItemFieldByKind('order');

        $item->{$orderField->name} = $order;
        $this->updateItem($item, [$orderField->name]);
    }

    public function moveItem(mixed $item, string $toLocation, int $toLocationArg = 0, ?int $order = null): void {
        $locationField = $this->getItemFieldByKind('location');
        $locationArgField = $this->getItemFieldByKind('location_arg');
        $orderField = $this->getItemFieldByKind('order');
        
        $item->{$locationField->name} = $toLocation;
        $item->{$locationArgField->name} = $toLocationArg;
        if ($order !== null) {
            $where = $this->db->sqlEqualValue($locationField, $item->{$locationField->name})." AND ".$this->db->sqlEqualValue($locationArgField, $item->{$locationArgField->name})." AND ".$this->db->sqlEqualValue($orderField, $order);
            if (intval($this->db->sqlGetValue("count(*)", $where)) > 0) {
                // there is already an item with the specified order in this location & location_arg
                // update all orders >= $order
                $update = "`{$orderField->dbField}` = `{$orderField->dbField}` + 1";
                $this->db->sqlUpdate($update, $this->db->sqlEqualValue($locationField, $item->{$locationField->name})." AND ".$this->db->sqlEqualValue($locationArgField, $item->{$locationArgField->name})." AND ".$this->db->sqlGreaterValue($orderField, $order, orEqual: true));
            }

            $item->{$orderField->name} = $order;
        } else {
            $item->{$orderField->name} = $this->countItemsInLocation($toLocation, $toLocationArg) > 0 ? ($this->getMaxOrderInLocation($toLocation, $toLocationArg) + 1) : 0;
        }
        $this->updateItem($item, [$locationField->name, $locationArgField->name, $orderField->name]);
    }

    public function moveItems(array $items, string $toLocation, int $toLocationArg = 0): void {
        foreach ($items as $item) {
            $this->moveItem($item, $toLocation, $toLocationArg);
        }
    }

    public function moveItemKeepOrder(mixed $item, string $toLocation, int $toLocationArg = 0): void {
        $this->moveItemsKeepOrder([$item], $toLocation, $toLocationArg);
    }

    public function moveItemsKeepOrder(array $items, string $toLocation, int $toLocationArg = 0): void {
        if (count($items) === 0) {
            return;
        }

        $idField = $this->getItemFieldByKind('id');
        $locationField = $this->getItemFieldByKind('location');
        $locationArgField = $this->getItemFieldByKind('location_arg');

        foreach ($items as &$item) {
            $item->{$locationField->name} = $toLocation;
            $item->{$locationArgField->name} = $toLocationArg;
        }
        $updates = $this->db->sqlEqualValue($locationField, $toLocation) . ', ' . $this->db->sqlEqualValue($locationArgField, $toLocationArg);
        $this->db->sqlUpdate($updates, $this->db->sqlInValues($idField, array_map(fn($item) => $item->{$idField->name}, $items)));
    }

    public function getItemsByFieldName(string $fieldName, array $values, ?int $limit = null): array {
        $field = array_find($this->fields, fn($f) => $f->name === $fieldName);
        return $this->getItemsByField($field, $values, $limit);
    }

    public function getItemsByField(ItemField $field, array $values, ?int $limit = null): array {
        if (count($values) === 0) {
            return [];
        }

        $where = $this->db->sqlInValues($field, $values);
        $dbResults = $this->db->sqlGetList("*", $where, limit: $limit);
        return array_map(fn($dbItem) => $this->getItemFromDb($dbItem), array_values($dbResults));
    }

    public function getItemById(int $id): mixed {
        $items = $this->getItemsByIds([$id]);
        return count($items) > 0 ? $items[0] : null;
    }

    public function getItemsByIds(array $ids): array {
        $idField = $this->getItemFieldByKind('id');

        return $this->getItemsByField($idField, $ids);
    }

    public function countItemsInLocation(string $location, ?int $locationArg = null): int {
        $locationField = $this->getItemFieldByKind('location');
        $locationArgField = $this->getItemFieldByKind('location_arg');

        $where = $this->db->sqlEqualValue($locationField, $location);
        if ($locationArg !== null) {
            $where .= " AND ".$this->db->sqlEqualValue($locationArgField, $locationArg);
        }
        $dbResult = $this->db->sqlGetValue("count(*)", $where);
        return (int)$dbResult;
    }

    public function getMaxOrderInLocation(string $location, ?int $locationArg = null): int {
        $locationField = $this->getItemFieldByKind('location');
        $locationArgField = $this->getItemFieldByKind('location_arg');
        $orderField = $this->getItemFieldByKind('order');

        $where = $this->db->sqlEqualValue($locationField, $location);
        if ($locationArg !== null) {
            $where .= " AND ".$this->db->sqlEqualValue($locationArgField, $locationArg);
        }
        $dbResult = $this->db->sqlGetValue("max(`{$orderField->dbField}`)", $where);
        return (int)$dbResult;
    }

    public function getItemsInLocation(string $location, ?int $locationArg = null, bool $reversed = false, ?int $limit = null): array {
        $locationField = $this->getItemFieldByKind('location');
        $locationArgField = $this->getItemFieldByKind('location_arg');
        $orderField = $this->getItemFieldByKind('order');

        $where = $this->db->sqlEqualValue($locationField, $location);
        if ($locationArg !== null) {
            $where .= " AND ".$this->db->sqlEqualValue($locationArgField, $locationArg);
        }
        $orderBy = "`{$orderField->dbField}` ".($reversed ? 'DESC' : 'ASC');
        $dbResults = $this->db->sqlGetList("*", $where, orderBy: $orderBy, limit: $limit);
        return array_map(fn($dbItem) => $this->getItemFromDb($dbItem), array_values($dbResults));
    }

    public function getAllItems(?int $limit = null): array {
        $dbResults = $this->db->sqlGetList("*", limit: $limit);
        return array_map(fn($dbItem) => $this->getItemFromDb($dbItem), array_values($dbResults));
    }

	public function getItemOnTop(string $location, ?int $locationArg = null): mixed {
        $items = $this->getItemsOnTop(1, $location, $locationArg);
        return count($items) > 0 ? $items[0] : null;
	}

	public function getItemsOnTop(int $number, string $location, ?int $locationArg = null): array {
        return $this->getItemsInLocation($location, $locationArg, true, $number);
	}

    /**
     * Update the DB value based on the Item fields.
     * Set $fields to set which fields to update (all if null)
     */
    public function updateItem(mixed $item, ?array $fields = null) {
        $idField = $this->getItemFieldByKind('id');

        $changes = [];

        foreach ($this->fields as &$field) {
            $toUpdate = $fields === null || in_array($field->name, $fields);
            if ($toUpdate && $field->kind !== 'id') {
                $changes[] = $this->db->sqlEqual($field, $item);
            }
        }

        if (count($changes) === 0) {
            return;
        }

        $this->db->sqlUpdate(implode(",", $changes), $this->db->sqlEqual($idField, $item));
    }

    protected function getItemField(string $name): ?ItemField {
        return array_find($this->fields, fn($field) => $field->name === $name);
    }

    protected function getItemFieldByKind(string $kind): ?ItemField {
        return array_find($this->fields, fn($field) => $field->kind === $kind);
    }

    protected function getClassName(?array $dbItem): ?string {
        return $this->className;
    }

    protected function getItemFromDb(?array $dbItem): mixed {
        if (!$dbItem) {
            return null;
        }

        $className = $this->getClassName($dbItem) ?? $this->className;
        $item = new $className();

        foreach ($this->fields as &$field) {
            $item->{$field->name} = $this->db->getValueFromSql($field, $dbItem[$field->dbField] ?? null);
        }
        
        if (method_exists($item, 'setup')) {
            $item->setup($dbItem);
        }

        return $item;
    }
}
