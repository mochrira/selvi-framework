<?php

namespace Selvi\Database\Builder\DML;

use Closure;
use Selvi\Model;
use Selvi\Collection;
use Selvi\Database\DB;
use Selvi\Database\Interfaces\IQueryBuilder;
use Selvi\Model\Attributes\Column;
use Selvi\Model\Interfaces\IRelationAttribute;

/**
 * @template T of \Selvi\Model
 */
class ModelQuery {

    private WhereBuilder $whereBuilder;
    private JoinBuilder $joinBuilder;
    private WithBuilder $withBuilder;

    /** @param class-string<T> $model */
    public function __construct(
        private string $model, 
        public array $paths = []
    ) {
        $this->paths = [...$paths, $model::getTable()];
        $this->whereBuilder = new WhereBuilder();
        $this->joinBuilder = new JoinBuilder();
        $this->withBuilder = new WithBuilder();
    }

    public function with(string $name, ?Closure $closure = null) {
        $this->withBuilder->with($name, $closure);
        return $this;
    }

    private function buildWith(IQueryBuilder $builder) {
        foreach($this->withBuilder->clauses as $name => $closure) {
            /** @var IRelationAttribute */
            $relation = $this->model::relation($name);

            $leftTable = $this->model::getTable();
            $leftKey = $relation->foreignKey;
            
            /** @var ModelQuery */
            $childQuery = $relation->model::query($this->paths);
            if($closure) $closure($childQuery->withBuilder);
            $childBuilder = $childQuery->build();
            
            $rightTable = $relation->model::getTable();
            $subQueryMode = !empty($relation->model::relations());
            if($subQueryMode) {
                $rightTable = "({$childBuilder->getSql()})";
            }

            $childPaths = $childQuery->paths;
            $rightTableAlias = join("_", $childPaths);
            
            /** @var Column */
            $rightKeyColumn = $relation->model::getKey();
            $rightKey = $rightKeyColumn->name;
            if($subQueryMode) {
                $rightKey = "{$rightTableAlias}__{$rightKey}";
            }

            $builder->leftJoin("{$rightTable} {$rightTableAlias}", "{$rightTableAlias}.{$rightKey} = {$leftTable}.{$leftKey}");

            $rightColumns = $childBuilder->getSelectBuilder()->toArray();
            foreach($rightColumns as $rightColumn) {
                $col = $rightColumn[ $subQueryMode ? 'alias' : 'column' ];
                $builder->select(
                    ["{$rightTableAlias}.{$col}", $rightColumn['alias']]
                );
            }
        }
    }

    public function builder(): IQueryBuilder {
        $builder = DB::connection($this->model::getSchema())
            ->table($this->model::getTable());
        return $builder;
    }

    /** @param callable(IQueryBuilder): void $query */
    public function build(?callable $query = null): IQueryBuilder {
        $builder = $this->builder();

        $table = $this->model::getTable();
        /** @var Column $column */
        foreach($this->model::columns() as $column) {
            $prefix = join("_", $this->paths);
            $builder->select([$table, "{$column->name}", "{$prefix}__{$column->name}"]);
        }

        if($this->whereBuilder->hasWheres()) {
            $builder->where($this->whereBuilder);
        }

        if($this->joinBuilder->hasJoins()) {
            $builder->join($this->joinBuilder);
        }

        if($this->withBuilder->hasClauses()) {
            $this->buildWith($builder);
        }

        if($query != null) $query($builder);
        return $builder;
    }

    /**
     * @param \Closure(IQueryBuilder): void $query
     * @return Collection<T>
     */
    public function all(\Closure $query): Collection {
        $builder = $this->build($query);
        $rows = $builder->get()->rows();
        return Collection::fromMap(fn($item) => $this->model::of($item, $this->paths, $this->withBuilder->clauses), $rows);
    }

    /**
     * @param ?\Closure(IQueryBuilder): void $query
     * @return T|null
     */
    public function first(?\Closure $query = null): ?Model {
        $builder = $this->build();
        $builder->limit(1);
        if($query != null) $query($builder);
        $row = $builder->get()->row();
        return $row != null ? $this->model::of($row, $this->paths, $this->withBuilder->clauses) : null;
    }

    /** @param \Closure(IQueryBuilder): void $query */
    public function count(\Closure $query): int {
        $table = $this->model::getTable();

        /** @var Column */
        $key = $this->model::getKey();

        $builder = $this->builder()->select(
            ["COUNT({$table}.{$key->name})", "total"]
        );
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

            return $this->all(function(IQueryBuilder $query) use ($key, $id) {
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
