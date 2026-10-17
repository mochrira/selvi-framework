<?php

namespace Selvi\Database\Builder\DML;

use Selvi\Model;
use Selvi\Collection;
use Selvi\Database\DB;
use Selvi\Model\Attributes\Column;

/**
 * @template T of \Selvi\Model
 */
class ModelQuery {

    /** @var class-string<T> */
    private string $model;

    private WhereBuilder $whereBuilder;
    private JoinBuilder $joinBuilder;

    /** @param class-string<T> $model */
    public function __construct(string $model) {
        $this->model = $model;
        $this->whereBuilder = new WhereBuilder();
        $this->joinBuilder = new JoinBuilder();
    }

    public function builder(): QueryBuilder {
        $builder = DB::connection($this->model::getSchema())
            ->table($this->model::getTable());
        return $builder;
    }

    /** @param callable(QueryBuilder): void $query */
    public function build(?callable $query = null) {
        $builder = $this->builder();

        $table = $this->model::getTable();
        /** @var Column $column */
        foreach($this->model::columns() as $column) {
            $builder->select("{$table}.{$column->name}");
        }

        if($this->whereBuilder->hasWheres()) {
            $builder->where($this->whereBuilder);
        }

        if($this->joinBuilder->hasJoins()) {
            $builder->join($this->joinBuilder);
        }

        if($query != null) $query($builder);
        return $builder;
    }

    /**
     * @param \Closure(QueryBuilder): void $query
     * @return Collection<T>
     */
    public function all(\Closure $query): Collection {
        $rows = $this->build($query)->get()->rows();
        return Collection::fromMap(fn($item) => $this->model::of($item), $rows);
    }

    /**
     * @param ?\Closure(QueryBuilder): void $query
     * @return T|null
     */
    public function first(?\Closure $query = null): ?Model {
        $builder = $this->build($query);
        $builder->limit(1);
        $row = $builder->get()->row();
        return $row != null ? $this->model::of($row) : null;
    }

    /** @param \Closure(QueryBuilder): void $query */
    public function count(\Closure $query): int {
        $table = $this->model::getTable();

        /** @var Column */
        $key = $this->model::getKey();

        $builder = $this->builder()->select("COUNT({$table}.{$key->name}) AS total");
        if($query != null) $query($builder);

        $row = $builder->get()->row();
        return $row != null ? (int)$row->total : 0;
    }

    /**
     * @param mixed $id
     * @return T|Collection<T>|null
     * @phpstan-return ($id is array ? Collection<T> : T|null)
     * @psalm-return ($id is array ? Collection<T> : T|null)
     */
    public function find(mixed $id) : Model | Collection | null {
        /** @var Column */
        $key = $this->model::getKey();

        if(is_array($id)) {
            if(empty($id)) return new Collection();

            return $this->all(function(QueryBuilder $query) use ($key, $id) {
                $query->where(function(WhereBuilder $builder) use ($key, $id) {
                    foreach($id as $value) {
                        $builder->orWhere([[$key->name, '=', $value]]);
                    }
                });
            });
        }

        return $this->where([[$key->name, '=', $id]])->first();
    }

    public function where(mixed $input): static {
        $this->whereBuilder->where($input);
        return $this;
    }

    public function orWhere(mixed $input): static {
        $this->whereBuilder->orWhere($input);
        return $this;
    }

    public function join(string $table, string $on) {
        $this->joinBuilder->join($table, $on);
        return $this;
    }

    public function innerJoin(string $table, string $on) {
        $this->joinBuilder->innerJoin($table, $on);
        return $this;
    }

    public function leftJoin(string $table, string $on) {
        $this->joinBuilder->leftJoin($table, $on);
        return $this;
    }

    public function rightJoin(string $table, string $on) {
        $this->joinBuilder->rightJoin($table, $on);
        return $this;
    }

    /**
     * @param array<string, mixed> $data Data berkunci nama kolom.
     */
    public function insert(array $data) : int | string {
        $id = $this->builder()->insert($data);
        /** @var Column */
        $key = $this->model::getKey();
        return $data[$key->name] ?? $id;
    }

    /**
     * @param array<string, mixed> $data Data berkunci nama kolom.
     */
    public function update(array $data): bool {
        $builder = $this->builder();
        if ($this->whereBuilder->hasWheres()) 
            $builder->where($this->whereBuilder);
        return $builder->update($data);
    }

    public function delete(): bool {
        $builder = $this->builder();
        if ($this->whereBuilder->hasWheres())
            $builder->where($this->whereBuilder);
        return $builder->delete();
    }

}
