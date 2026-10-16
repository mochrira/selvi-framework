<?php 

namespace Selvi\Database\Builder\DML\Clauses;

use Selvi\Contracts\RelationInterface;

class RelationClause {

    private JoinClause $join;

    function __construct(
        public string $name
    ) { }

}