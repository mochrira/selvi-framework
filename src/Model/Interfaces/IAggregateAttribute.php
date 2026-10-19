<?php 

namespace Selvi\Model\Interfaces;

use Selvi\Database\Contracts\CastInterface;

interface IAggregateAttribute extends IPropertyAttribute {

    /** @var callable-string */
    public string $query { get; set; }
    public string $key { get; set; }
    public string $column { get; set; }
    public ?CastInterface $cast { get; set; }

}