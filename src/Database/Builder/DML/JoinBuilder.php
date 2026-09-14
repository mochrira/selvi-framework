<?php

namespace Selvi\Database\Builder\DML;

use Selvi\Database\Builder\DML\Clauses\JoinClause;

class JoinBuilder {

    /**
     * @var JoinClause[]
     */
    protected array $clauses = [];

    /**
     * Default LEFT JOIN.
     */
    public function join(string $table, string $on): static
    {
        return $this->leftJoin($table, $on);
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
