<?php
namespace KOT\Objects;

class WickednessTile {
    public $id;
    public $location;
    public $location_arg;
    public $type;
    public $side; // 0 front, 1 back
    public $tokens;
    public $mimicType;

    public function __construct($dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = intval($dbCard['type']);
        $this->side = $this->type >= 100 ? 1 : 0;
        $this->tokens = intval($dbCard['type_arg']);
    } 
}
?>