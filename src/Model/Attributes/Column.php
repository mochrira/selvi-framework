<?php

namespace Selvi\Model\Attributes;

use Attribute;
use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class Column extends PropertyAttribute {

    public function __construct(
        public string $name,
        public bool $key = false,
        public ?CastInterface $cast = null,
        public ?SerializesCastInterface $serialize = null
    ) { }

}
