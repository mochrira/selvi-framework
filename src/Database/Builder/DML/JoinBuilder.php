<?php

namespace Selvi\Database\Builder\DML;

use LogicException;
use Selvi\Database\Builder\DML\Clauses\JoinClause;

class JoinBuilder {

    /**
     * @var JoinClause[]
     */
    protected array $clauses = [];

    /**
     * @param JoinBuilder|JoinClause|string $input
     */
    public function join(mixed $input, ?string $on = null): static
    {
        if($input instanceof self) {
            foreach($input->clauses as $clause) {
                $this->clauses[] = $clause;
            }
            return $this;
        }

        if($input instanceof JoinClause) {
            $this->clauses[] = $input;
            return $this;
        }

        if($on == null) throw new LogicException("Klausa join harus memiliki pernyataan relasi `on`");
        return $this->leftJoin($input, $on);
    }

    public function leftJoin(string $table, string $on): static
    {
        $this->clauses[] = JoinClause::left($table, $on);
        return $this;
    }

    public function innerJoin(string $table, string $on): static
    {
        $this->clauses[] = JoinClause::inner($table, $on);
        return $this;
    }

    public function rightJoin(string $table, string $on): static
    {
        $this->clauses[] = JoinClause::right($table, $on);
        return $this;
    }

    public function hasJoins(): bool
    {
        return !empty($this->clauses);
    }

    /**
     * Struktur kanonik (AST) dari seluruh join.
     */
    public function toArray(): array
    {
        $nodes = [];

        foreach ($this->clauses as $clause) {
            $nodes[] = $clause->toArray();
        }

        return $nodes;
    }

}
