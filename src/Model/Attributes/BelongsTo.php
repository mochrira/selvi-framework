<?php

namespace Selvi\Model\Attributes;

use Override;
use Selvi\Model;
use Selvi\Model\Base\PropertyAttribute;
use Selvi\Model\Interfaces\IRelationAttribute;

#[\Attribute]
class BelongsTo extends PropertyAttribute implements IRelationAttribute {

    /** @param class-string<Model> $model */
    public function __construct(
        public string $model,
        public string $foreignKey,
        
        public ?string $key = null,
        public ?bool $includeIfNull = false
    ) { }

}
