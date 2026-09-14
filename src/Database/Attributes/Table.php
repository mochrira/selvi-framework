<?php 

namespace Selvi\Database\Attributes;

#[\Attribute]
class Table {

    public function __construct(
        public string $name,
        public string $schema
    ) { }

}