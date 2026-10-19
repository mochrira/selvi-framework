<?php 

namespace Selvi\Model\Attributes;

use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;
use Selvi\Model\Base\PropertyAttribute;
use Selvi\Model\Interfaces\IAggregateAttribute;

#[\Attribute]
class Aggregate extends PropertyAttribute implements IAggregateAttribute {

    public function __construct(
        public string $query,
        public string $key,
        public string $column,
        public bool $includeIfNull = false,
        public ?CastInterface $cast = null,
        public ?SerializesCastInterface $serialize = null
    ) { }

}