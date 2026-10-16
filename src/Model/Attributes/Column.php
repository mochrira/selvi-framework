<?php

namespace Selvi\Model\Attributes;

use Selvi\Model;
use Selvi\Model\Contracts\IPropertyAttribute;
use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;

#[\Attribute]
class Column implements IPropertyAttribute {

    public function __construct(
        public string $name,
        public bool $key = false,
        public bool $nullable = false,
        public ?CastInterface $cast = null,
        public ?SerializesCastInterface $serialize = null
    ) { }

    /** @var class-string<Model> */
    private string $owner;
    private string $propertyName;
    private string $propertyType;

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
}
