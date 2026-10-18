<?php

namespace Selvi\Model\Attributes;

use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute]
class Column extends PropertyAttribute {

    public function __construct(
        public string $name,
        public bool $key = false,
        public bool $nullable = false,
        public ?CastInterface $cast = null,
        public ?SerializesCastInterface $serialize = null
    ) { }

}
