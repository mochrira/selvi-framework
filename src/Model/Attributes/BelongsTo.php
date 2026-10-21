<?php

namespace Selvi\Model\Attributes;

use Attribute;
use Selvi\Tests\ModelNext;
use Selvi\Model\Base\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo extends PropertyAttribute {

    /** @param class-string<ModelNext> $model */
    public function __construct(
        public string $model,
        public string $foreignKey,
        public ?string $ownerKey = null
    ) { }

}
