<?php 

namespace Selvi\Model\Contracts;

use Selvi\Model;
use Selvi\Database\Builder\DML\QueryBuilder;

interface IRelationAttribute extends IPropertyAttribute {

    /** @var class-string<Model> */
    public string $model { get; set; }
    public string $foreignKey { get; set; }

    public function setPrefix(string $prefix): void;
    public function serialize(object $obj, ?string $prefix = null): Model;

}