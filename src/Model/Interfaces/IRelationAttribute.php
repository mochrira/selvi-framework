<?php 

namespace Selvi\Model\Interfaces;

use Selvi\Model;

interface IRelationAttribute extends IPropertyAttribute {

    /** @var class-string<Model> */
    public string $model { get; set; }
    public string $foreignKey { get; set; }

    public ?string $key { get; set; }
    public ?bool $includeIfNull { get; set; }

}