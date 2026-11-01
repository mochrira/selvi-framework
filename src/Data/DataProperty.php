<?php 

namespace Selvi\Data;

use Attribute;
use Override;
use Selvi\Data\Interfaces\IPropertyAttribute;
use Selvi\Database\Interfaces\ICast;
use Selvi\Database\Interfaces\ISerializesCast;

#[\Attribute(Attribute::TARGET_PROPERTY)]
class DataProperty implements IPropertyAttribute {

    public function __construct(
        public string $name = "",
        public ?string $prefix = null,
        public ?bool $optional = false,
        public ?ICast $cast = null,
        public ?ISerializesCast $serialize = null
    ) { }

    private string $propertyName;
    private string $propertyType;
    private bool $allowsNull;

    #[Override]
    public function setPropertyName(string $name): void {
        $this->propertyName = $name;
    }

    #[Override]
    public function setPropertyType(string $type, bool $allowsNull): void {
        $this->propertyType = $type;
        $this->allowsNull = $allowsNull;
    }

    #[Override]
    public function propertyName(): string
    {
        return $this->propertyName;
    }

    #[Override]
    public function propertyType(): string
    {
        return $this->propertyType;
    }

    #[Override]
    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }


}