<?php 

namespace Selvi\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo {
    public function __construct(
        public string $related,
        public string $key,
    ) {}
}