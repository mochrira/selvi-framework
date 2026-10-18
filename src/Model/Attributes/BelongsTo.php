<?php

namespace Selvi\Model\Attributes;

use Override;
use Selvi\Model;
use Selvi\Model\Base\PropertyAttribute;
use Selvi\Model\Base\RelationAttribute;
use Selvi\Model\Contracts\IRelationAttribute;

#[\Attribute]
class BelongsTo extends PropertyAttribute implements IRelationAttribute {

    /** @param class-string<Model> $useModel */
    public function __construct(
        public string $foreignKey,

        public ?string $useModel = null,
        public ?string $useQuery = null,
        
        public ?string $key = null,
        public ?bool $includeIfNull = false
    ) { }

}
