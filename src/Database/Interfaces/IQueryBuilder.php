<?php

namespace Selvi\Database\Interfaces;

use Closure;
use Selvi\Database\Builder\DML\InsertBuilder;
use Selvi\Database\Builder\DML\SelectBuilder;

interface IQueryBuilder {

    /** == Start of Evaluated */

    public function connection(): IConnection;
    public function useConnection(string | IConnection $connection): static;

    public function table(string $name): static;
    public function getTable(): string;

    public function select(mixed ...$columns): static;
    public function selectColumns(array $columns): static;
    /** 
     * @param Closure(SelectBuilder) $callback
     * */
    public function selectUsing(Closure $callback): static;
    public function getSelectBuilder(): SelectBuilder;

    public function getSql(): string;
    public function get(): IResult;

    public function insert(array $values): int | string;

    /**
     * Contoh: 
     * 
     * where([['a', '=', 1], ['b', '>', 2], 'c IS NOT NULL'])
     * where(['a = 1', 'b = 2'])
     * where('a = 1')
     * where(function ($builder) {
     *  $builder->where(...);
     * })
     * 
     */
    public function where(mixed $input): static;

    /**
     * Contoh: 
     * 
     * orWhere([['a', '=', 1], ['b', '>', 2], 'c IS NOT NULL'])
     * orWhere(['a = 1', 'b = 2'])
     * orWhere('a = 1')
     * orWhere(function ($builder) {
     *  $builder->orWhere(...);
     * })
     * 
     */
    public function orWhere(mixed $input): static;
    public function getWhereClauses(): array;

    public function update(array $values): bool;
    public function delete(): bool;
    /** == End of Evaluated */

    public function joins(): array;
    public function groups(): array;
    public function getLimit(): ?int;
    public function getOffset(): ?int;
    public function orders(): array;
}
