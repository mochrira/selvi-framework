<?php

namespace Selvi\Database\Interfaces;

use Closure;
use Selvi\Database\Builder\DML\GroupBuilder;
use Selvi\Database\Builder\DML\JoinBuilder;
use Selvi\Database\Builder\DML\OrderBuilder;
use Selvi\Database\Builder\DML\SelectBuilder;

interface IQueryBuilder {

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

    public function join(mixed $input, ?string $on = null): static;
    public function joinUsing(Closure $input): static;
    public function leftJoin(string $table, string $on): static;
    public function innerJoin(string $table, string $on): static;
    public function rightJoin(string $table, string $on): static;
    public function getJoinBuilder(): JoinBuilder;

    /**
     * Contoh: 
     * 
     * group('a')
     * group('a, b')
     * group('a', 'b')
     * group(['a', 'b'])
     */
    public function group(mixed ...$columns) : static;
    public function getGroupBuilder(): GroupBuilder;

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

    public function limit(?int $limit): static;
    public function getLimit(): ?int;
    public function offset(?int $offset): static;
    public function getOffset(): ?int;

    public function order(mixed ...$args): static;
    
    /** @param Closure(OrderBuilder) : void $input */
    public function orderUsing(Closure $input): static;
    public function orderBy(string $column, string $direction = 'ASC'): static;
    public function getOrderBuilder(): OrderBuilder;

    public function insert(array $values): int | string;
    public function update(array $values): bool;
    public function delete(): bool;
}
