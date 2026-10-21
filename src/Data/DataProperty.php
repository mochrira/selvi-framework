<?php 

namespace Selvi\Data;

use Attribute;
use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class DataProperty extends PropertyAttribute {

    public function __construct(
        public ?string $name = null,
        public ?string $prefix = null,
        public ?bool $optional = false,
        public ?CastInterface $cast = null,
        public ?SerializesCastInterface $serialize = null
    ) { }


}