<?php

namespace Selvi\Model\Attributes;

use Attribute;
use Override;
use Selvi\Data\DataRow;
use Selvi\Model;
use Selvi\Model\PropertyAttribute;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo extends PropertyAttribute {

    /** @param class-string<Model> $model */
    public function __construct(
        public string $model,
        public string $foreignKey,
        public ?string $ownerKey = null
    ) { }

    #[Override]
    public function assign(object $target, DataRow $row): void {
        $child = $row->child($this->propertyName());
        if(!$child->hasAny($child->prefix())) return;

        $target->{$this->propertyName()} = $this->propertyType()::of(
            $child->item(),
            $child->prefix()
        );
    }

}
