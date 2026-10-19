<?php

namespace Selvi\Model\Attributes;

use Selvi\Model\Base\Attribute;

#[\Attribute]
class Table extends Attribute {

    public function __construct(
        public string $name,
        public string $schema
    ) { }

}
