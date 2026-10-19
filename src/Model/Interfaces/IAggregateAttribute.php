<?php 

namespace Selvi\Model\Interfaces;

interface IAggregateAttribute extends IPropertyAttribute {

    /** @var callable-string */
    public string $query { get; set; }
    public string $foreignKey { get; set; }
    public ?string $queryKey { get; set; }

}