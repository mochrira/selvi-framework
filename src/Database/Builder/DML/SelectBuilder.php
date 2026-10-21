<?php 

namespace Selvi\Database\Builder\DML;

class SelectBuilder {

    /** @var list<array{table:?string,column:string,alias:?string}> $columns */
    private array $columns = [];

    public function basic(string $column, ?string $alias = null): static {
        $this->columns[] = ['column' => $column, 'alias' => $alias];
        return $this;
    }

    public function withTable(string $table, string $column, ?string $alias = null): static {
        $this->columns[] = ['table' => $table, 'column' => $column, 'alias' => $alias];
        return $this;
    }

    public function multi(array $columns): static {
        foreach($columns as $column) {
            if(is_array($column)) {
                if(count($column) == 2) {
                    $this->basic($column[0], $column[1]);
                }
                if(count($column) == 3) {
                    $this->withTable($column[0], $column[1], $column[2]);
                }
            }
            if(is_string($column)) {
                $this->basic($column);
            }
        }
        return $this;
    }

    /** @var list<array{
     *      table: ?string,
     *      column: string,
     *      alias: ?string
     * }> $columns */
    public function toArray(): array {
        return $this->columns;
    }

}