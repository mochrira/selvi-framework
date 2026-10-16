<?php 

namespace Selvi\Database\Builder\DML;

class SelectBuilder {

    /** @var array<string> */
    private array $columns = [];

    function add(string $column): void {
        $this->columns[] = $column;
    }

    /** @return array<string> */
    function toArray(): array {
        return $this->columns;
    }

}