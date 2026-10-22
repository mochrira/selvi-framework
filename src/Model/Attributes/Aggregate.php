<?php 

namespace Selvi\Model\Attributes;

use Attribute;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class Aggregate extends PropertyAttribute {

    public function __construct(
        public string $query,
        public string $key,
        public string $column,
        public ?ICast $cast = null,
        public ?ISerializesCast $serialize = null
    ) { 
        $this->name = $column;
    }

}