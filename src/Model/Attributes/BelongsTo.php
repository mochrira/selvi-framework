<?php

namespace Selvi\Model\Attributes;

use Override;
use Selvi\Model;
use Selvi\Model\Contracts\IRelationAttribute;

#[\Attribute]
class BelongsTo implements IRelationAttribute {

    /**
     * @param class-string<Model> $model
     */
    public function __construct(
        public string $model,
        public string $foreignKey,
        public ?string $ownerKey = null,
        public bool $includeIfNull = false
    ) { }

    /** @var class-string<Model> */
    private string $owner;
    private string $propertyName;
    private string $propertyType;
    private string $prefix;

    #[\Override]
    public function setOwner(string $owner): void {
        $this->owner = $owner;
    }

    #[\Override]
    public function setPropertyName(string $name): void {
        $this->propertyName = $name;
    }

    #[\Override]
    public function setPropertyType(string $type): void {
        $this->propertyType = $type;
    }

    #[\Override]
    public function owner(): string {
        return $this->owner;
    }

    #[\Override]
    public function propertyName(): string {
        return $this->propertyName;
    }

    #[\Override]
    public function propertyType(): string {
        return $this->propertyType;
    }

    #[Override]
    public function setPrefix(string $prefix): void {
        $this->prefix = $prefix;
    }

    #[Override]
    public function serialize(object $obj, ?string $prefix = null): Model {
        var_dump($this->model);
        var_dump($prefix ?? $this->prefix);
        return $this->model::of($obj, $prefix ?? $this->prefix);
    }
}
