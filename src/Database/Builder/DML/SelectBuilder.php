<?php 

namespace Selvi\Database\Builder\DML;

use Selvi\Database\Builder\DML\Clauses\SelectNode;

class SelectBuilder {

    /** @var list<array> $columns */
    private array $columns = [];

    public function basic(string $column, ?string $alias = null): static {
        return $this->addNode(SelectNode::column($column, null, $alias));
    }

    public function withTable(string $table, string $column, ?string $alias = null): static {
        return $this->addNode(SelectNode::column($column, $table, $alias));
    }

    /**
     * Menambahkan satu node fungsi SQL, mis. COALESCE, CONCAT, COUNT.
     *
     * @param list<array> $arguments
     */
    public function function(string $name, array $arguments = [], ?string $alias = null): static {
        return $this->addNode(SelectNode::function($name, $arguments, $alias));
    }

    public function raw(string $sql, ?string $alias = null): static {
        return $this->addNode(SelectNode::raw($sql, $alias));
    }

    public function addNode(array $node): static {
        $this->columns[] = $node;
        return $this;
    }

    public function multi(array $columns): static {
        foreach($columns as $column) {
            if(is_array($column)) {
                if(isset($column['type'])) {
                    $this->addNode($column);
                    continue;
                }

                if(isset($column['column'])) {
                    $this->addNode(SelectNode::column(
                        $column['column'],
                        $column['table'] ?? null,
                        $column['alias'] ?? null
                    ));
                    continue;
                }

                if(count($column) == 2) {
                    $this->basic($column[0], $column[1]);
                }
                if(count($column) == 3) {
                    $this->withTable($column[0], $column[1], $column[2]);
                }
                continue;
            }

            if(is_string($column)) {
                $this->basic($column);
            }
        }
        return $this;
    }

    /** @return list<array> */
    public function toArray(): array {
        return $this->columns;
    }

}
