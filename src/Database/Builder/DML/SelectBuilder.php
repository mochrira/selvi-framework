<?php 

namespace Selvi\Database\Builder\DML;

class SelectBuilder {

    /** @var list<array{
     *      table: string,
     *      column: string,
     *      alias?: string
     * }> $columns */
    public array $columns = [];

    function add(?string $table, string $column, ?string $alias = null): void {
        $this->columns[] = ['table' => $table, 'column' => $column, 'alias' => $alias];
    }

    /** @return array<string> */
    function toArray(): array {
        return $this->columns;
    }

    function build() {
        $sql = "SELECT ";
        if(empty($this->columns)) return "{$sql}*";
        return $sql . join(", ", array_map(function ($col) {
            $str = "{$col['column']}";
            if(isset($col['table'])) $str = "{$col['table']}.{$str}";
            if(isset($col['alias'])) $str .= " AS {$col['alias']}";
            return $str;
        }, $this->columns));
    }

}