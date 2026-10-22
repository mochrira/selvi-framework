<?php 

namespace Selvi\Data;

use Attribute;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class DataProperty extends PropertyAttribute {

    public function __construct(
        public string $name,
        public ?string $prefix = null,
        public ?bool $optional = false,
        public ?ICast $cast = null,
        public ?ISerializesCast $serialize = null
    ) { }


}