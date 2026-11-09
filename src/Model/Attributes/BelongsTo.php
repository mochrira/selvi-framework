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

        // Kolom kunci milik relasi dipakai untuk memastikan kolom hasil join
        // benar-benar ada. Pemeriksaan prefix saja keliru untuk relasi ke
        // dirinya sendiri (mis. properti `parent` dengan kolom `parent_id`),
        // karena kolom foreign key ikut cocok dengan prefix `parent_`.
        $keyColumn = $this->model::getKeyColumn();
        if($keyColumn !== null) {
            if(!$child->has($keyColumn->name)) return;
            if($child->get($keyColumn->name) === null) return;
        }
        elseif(!$child->hasAny($child->prefix())) {
            return;
        }

        $target->{$this->propertyName()} = $this->propertyType()::of(
            $child->item(),
            $child->prefix()
        );
    }

}
