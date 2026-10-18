<?php

namespace Selvi\Model\Attributes;

use Selvi\Model\Base\PropertyAttribute;

#[\Attribute]
class NotMapped extends PropertyAttribute {

    public function __construct(
        public bool $includeIfNull = true
    ) { }

}
