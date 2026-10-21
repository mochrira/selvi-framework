<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML;

class OrderBuilder {

    /**
     * 
     * menyimpan klausa dengan bentuk
     * $clauses = [
     *      'kontak.id_grup ASC',
     *      'kontak.nm_kontak DESC',
     * ]
     * 
     * @var list<string>
     */
    private array $clauses = [];

    public function using(OrderBuilder $orderBuilder) {
        $this->clauses = [...$this->clauses, ...$orderBuilder->clauses];
        return $this;
    }

    public function basic(string $column, string $direction = 'ASC'): static {
        $this->clauses[] = "{$column} {$direction}";
        return $this;
    }

    public function raw(string $str): static {
        $this->clauses[] = $str;
        return $this;
    }

    public function multi(array $columns): static {
        if(array_is_list($columns)) {
            foreach($columns as $column) {
                if(is_string($column)) {
                    $this->raw($column);
                    continue;
                }

                if(is_array($column)) {
                    if(array_is_list($column)) {
                        $this->basic($column[0], $column[1]);
                        continue;
                    }

                    foreach($column as $key => $value) {
                        $this->basic($key, $value);
                    }
                }
            }
            return $this;
        }
        
        foreach($columns as $column => $direction) {
            $this->basic($column, $direction);
        }
        return $this;
    }

    public function hasOrders(): bool
    {
        return !empty($this->clauses);
    }

    /**
     * Struktur kanonik (AST) dari seluruh order.
     *
     * @return list<string>
     */
    public function toArray(): array {
        return $this->clauses;
    }

}
