<?php 

namespace Selvi\Model\Attributes;

use Selvi\Model\Base\PropertyAttribute;

#[\Attribute]
class Aggregate extends PropertyAttribute{

    /** @param callable-string $query */
    public function __construct(
        public string $query,
        public string $foreignKey,
        public string $queryKey
    ) { }

}