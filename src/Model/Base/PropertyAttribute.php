<?php

namespace Selvi\Model\Base;

use Override;
use Selvi\Model\Interfaces\IPropertyAttribute;

class PropertyAttribute extends Attribute implements IPropertyAttribute {

    public string $name;    

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
