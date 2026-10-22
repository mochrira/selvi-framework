<?php

namespace Selvi\Model\Attributes;

use Attribute;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class Column extends PropertyAttribute {

    public function __construct(
        public string $name,
        public bool $key = false,
        public ?ICast $cast = null,
        public ?ISerializesCast $serialize = null
    ) { }

}
