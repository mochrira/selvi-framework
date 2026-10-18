<?php 

namespace Selvi\Model\Interface;

use Selvi\Model;

interface IRelationAttribute extends IPropertyAttribute {

    public string $foreignKey { get; set; }
    
    /** @var class-string<Model> */
    public ?string $useModel { get; set; }
    public ?string $useQuery { get; set; }

    public ?string $key { get; set; }
    public ?bool $includeIfNull { get; set; }

}