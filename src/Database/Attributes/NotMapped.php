<?php 

namespace Selvi\Database\Attributes;

#[\Attribute]
class NotMapped {

    public function __construct(
        public bool $includeIfNull = true
    ) { }

}