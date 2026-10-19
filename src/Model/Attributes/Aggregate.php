<?php 

namespace Selvi\Model\Attributes;

use Selvi\Database\Contracts\CastInterface;
use Selvi\Model\Base\PropertyAttribute;
use Selvi\Model\Interfaces\IAggregateAttribute;

#[\Attribute]
class Aggregate extends PropertyAttribute implements IAggregateAttribute {

    public function __construct(
        public string $query,
        public string $key,
        public string $column,
        public ?CastInterface $cast = null
    ) { }

}