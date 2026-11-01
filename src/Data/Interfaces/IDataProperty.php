<?php 

namespace Selvi\Data\Interfaces;

use Selvi\Data\DataRow;

interface IDataProperty {
    
    public function setPropertyName(string $name): void;
    public function propertyName(): string;

    public function setPropertyType(string $type, bool $allowsNull): void;
    public function propertyType(): string;
    public function allowsNull(): bool;

    public function assign(object $target, DataRow $row): void;

}