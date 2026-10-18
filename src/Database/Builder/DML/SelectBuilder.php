<?php 

namespace Selvi\Database\Builder\DML;

class SelectBuilder {

    /** @var list<string> $columns */
    private array $columns = [];
    private ?string $raw = null;

    public function basic(string $column, ?string $alias = null): static {
        $this->columns[] = $column . ($alias != null ? "AS {$alias}": "");
        return $this;
    }

    public function multi(array $columns): static {
        foreach($columns as $column) {
            if(is_array($column)) {
                $this->basic($column[0], $column[1]);
            }
            if(is_string($column)) {
                $this->basic($column);
            }
        }
        return $this;
    }

    /** @return list<string> */
    public function getColumns(): array {
        return $this->columns;
    }

    public function build() {
        $sql = "SELECT ";
        if(empty($this->columns) && !isset($this->raw)) return "{$sql}*";
        if(isset($this->raw)) return $sql . $this->raw;
        return $sql . join(", ", $this->columns);
    }

}