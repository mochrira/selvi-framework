<?php 

namespace Selvi\Model\Contracts;

interface IPropertyAttribute extends IAttribute {
    
    public function setPropertyName(string $name): void;
    public function propertyName(): string;

    public function setPropertyType(string $type): void;
    public function propertyType(): string;

}