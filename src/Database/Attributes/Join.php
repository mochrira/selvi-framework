<?php 

namespace Selvi\Database\Attributes;

#[\Attribute]
class Join {

    public function __construct(
        public string $type,
        public string $foreign
    ) { }

}