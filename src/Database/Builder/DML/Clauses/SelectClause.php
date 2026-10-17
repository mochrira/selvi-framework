<?php 

namespace Selvi\Database\Builder\DML\Clauses;

final class SelectClause {

    public string $column;
    public ?string $alias = null;

    function __construct(
        string $column,
        ?string $alias = null
    ) {
        $this->column = $column;
        $this->alias = $alias;
    }

}