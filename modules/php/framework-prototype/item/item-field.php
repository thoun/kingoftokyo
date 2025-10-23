<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Item;

/**
 * A description of an item field. Used to map to and from the DB, and to generate the DB table.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ItemField {
    /**
     * The name of the field.
     */
    public string $name;

    /**
     * The type of the data stored in that field.
     * Must be in ['bool', 'int', 'float', 'double', 'string', 'json'].
     */
    public string $type;

    /**
     * The name of the DB column.
     */
    public string $dbField;

    /**
     * The class of the object.
     * Only used if the type is json and a specific class has been set as a type.
     */
    public ?string $class = null;

    public function __construct(
        /**
         * Set to identify mandatory field. Keep it null for standard (custom) fields.
         * 
         * Mandatory fields are :
         * ItemField(kind: 'id')
         * ItemField(kind: 'location')
         * ItemField(kind: 'location_arg')
         * ItemField(kind: 'order')
         */
        public ?string $kind = null,

        /**
         * The type of the data stored in that field.
         * Must be in ['bool', 'int', 'float', 'double', 'string', 'json'].
         */
        ?string $type = null,

        /**
         * The name of the DB column, if different from the field name;
         */
        ?string $dbField = null,
    ) {
        if (isset($type)) {
            $this->type = $type;
        }
        if (isset($dbField)) {
            $this->dbField = $dbField;
        }
    }
}