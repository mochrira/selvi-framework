<?php 

namespace Selvi\Model\Contracts;

use Selvi\Model;
use Selvi\Database\Builder\DML\QueryBuilder;

interface IRelationAttribute extends IPropertyAttribute {

    /** @param array<string> $columns */
    public function build(QueryBuilder $builder, array &$columns): void;
    public function serialize(object $row): ?Model;
    public function toArray(array &$result, mixed $value): void;

}