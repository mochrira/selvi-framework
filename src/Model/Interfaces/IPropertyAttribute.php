<?php 

namespace Selvi\Model\Interfaces;

use Selvi\Model;

interface IPropertyAttribute extends IAttribute {
    
    /** @param class-string<Model> $name*/
    public function setPropertyName(string $name): void;

    /** @return class-string<Model> */
    public function getPropertyName(): string;

    public function setPropertyType(string $name): void;
    public function getPropertyType(): string;

}