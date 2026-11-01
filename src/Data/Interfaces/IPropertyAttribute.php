<?php 

namespace Selvi\Data\Interfaces;

interface IPropertyAttribute {
    
    public function setPropertyName(string $name): void;
    public function propertyName(): string;

    public function setPropertyType(string $type, bool $allowsNull): void;
    public function propertyType(): string;
    public function allowsNull(): bool;

}