<?php 

namespace Selvi\Database\Attributes;

#[\Attribute]
class Column {

    public function __construct(
        public string $name,
        public bool $key = false
    ) { }

}