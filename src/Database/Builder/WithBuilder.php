<?php

namespace Selvi\Database\Builder;

use Closure;

/**
 * Collector relasi untuk `with()`.
 *
 * Meniru pola WhereBuilder: closure menerima builder yang sama dan memanggil
 * method yang sama (`with()`) untuk relasi turunan. Jadi level berapa pun
 * memakai nama method yang konsisten.
 *
 *     Kontak::with('grup', function (WithBuilder $builder) {
 *         $builder->with('wilayah');
 *     });
 */
class WithBuilder {

    /**
     * @var WithClause[]
     */
    protected array $clauses = [];

    public function with(string $relation, ?Closure $nest = null): static
    {
        $child = null;
        if ($nest !== null) {
            $child = new static();
            $nest($child);
        }

        $this->clauses[] = WithClause::relation($relation, $child);
        return $this;
    }

    public function hasWiths(): bool
    {
        return !empty($this->clauses);
    }

    /**
     * Struktur kanonik (AST) dari seluruh relasi.
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
