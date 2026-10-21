<?php 

namespace Selvi\Model\Attributes;

use Attribute;
use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class Aggregate extends PropertyAttribute {

    public function __construct(
        public string $query,
        public string $key,
        public string $column,
        public ?CastInterface $cast = null,
        public ?SerializesCastInterface $serialize = null
    ) { 
        $this->name = $column;
    }

}