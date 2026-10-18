<?php

namespace Selvi\Model\Base;

use Override;
use Selvi\Model\Contracts\IPropertyAttribute;

class PropertyAttribute extends Attribute implements IPropertyAttribute {

    private string $propertyName;
    private string $propertyType;

    #[Override]
    public function setPropertyName(string $name): void {
        $this->propertyName = $name;
    }

    #[Override]
    public function setPropertyType(string $type): void {
        $this->propertyType = $type;
    }

    #[Override]
    public function getPropertyName(): string {
        return $this->propertyName;
    }

    #[Override]
    public function getPropertyType(): string {
        return $this->propertyType;
    }
}
