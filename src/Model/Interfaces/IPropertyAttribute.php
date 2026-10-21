<?php 

namespace Selvi\Model\Interfaces;

use Selvi\Model;

interface IPropertyAttribute extends IAttribute {
    
    /** @param class-string<Model> $name*/
    public function setPropertyName(string $name): void;
    public function propertyName(): string;

    public function setPropertyType(string $name, bool $allowsNull): void;
    public function propertyType(): string;
    public function allowsNull(): bool;

}