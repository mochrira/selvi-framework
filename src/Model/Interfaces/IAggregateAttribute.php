<?php 

namespace Selvi\Model\Interfaces;

use Selvi\Database\Contracts\CastInterface;
use Selvi\Database\Contracts\SerializesCastInterface;

interface IAggregateAttribute extends IPropertyAttribute {

    /** @var callable-string */
    public string $query { get; set; }
    public string $key { get; set; }
    public string $column { get; set; }
    public bool $includeIfNull { get; set; }
    public ?CastInterface $cast { get; set; }
    public ?SerializesCastInterface $serialize { get; set; }

}