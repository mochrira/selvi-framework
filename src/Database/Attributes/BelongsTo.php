<?php 

namespace Selvi\Database\Attributes;

#[\Attribute]
class BelongsTo {

    public function __construct(
        public string $model,
        public string $foreignKey,
        public ?string $ownerKey = null
    ) { }

}