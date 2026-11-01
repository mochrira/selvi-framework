<?php 

namespace Selvi\Database\Builder\DML;

use Closure;
use InvalidArgumentException;
use ReflectionMethod;
use Selvi\Collection;
use Selvi\Database\Builder\DML\Clauses\SelectNode;
use Selvi\Database\Builder\DML\JoinBuilder;
use Selvi\Database\Builder\DML\WhereBuilder;
use Selvi\Database\Builder\DML\WithBuilder;
use Selvi\Database\DB;
use Selvi\Database\Interfaces\IQueryBuilder;
use Selvi\Model;
use Selvi\Model\Attributes\Aggregate;
use Selvi\Model\Attributes\BelongsTo;
use Selvi\Model\Attributes\Column;

/**
 * @template T of \Selvi\Model
 */
class ModelQuery {

    /** @var class-string<T> */
    private string $model;

    private WhereBuilder $whereBuilder;
    private JoinBuilder $joinBuilder;
    private OrderBuilder $orderBuilder;
    private WithBuilder $withBuilder;

    private string $prefix;

    private ?int $limit = null;
    private ?int $offset = null;

    /** @param class-string<T> $model */
    function __construct(
        string $model,
        ?string $prefix = "",
        ?Closure $withClosure = null
    ) {
        $this->model = $model;
        $this->prefix = $prefix;

        $this->joinBuilder = new JoinBuilder();
        $this->whereBuilder = new WhereBuilder();
        $this->orderBuilder = new OrderBuilder();

        $this->withBuilder = new WithBuilder();
        if($withClosure != null) $withClosure($this->withBuilder);
    }

    private function builder() {
        $schema = $this->model::getSchema();
        $table = $this->model::getTable();
        return DB::connection($schema)->table($table);
    }

    public function buildWith(IQueryBuilder $builder) {
        $table = $this->model::getTable();
        $loadedAggregatesQuery = [];
        $clauses = $this->withBuilder->clauses;
        foreach($clauses as $name => $closure) {
            $relation = $this->model::relation($name);
            if($relation instanceof BelongsTo) {

                /** @var ModelQuery */
                $childQuery = $relation->model::query(
                    prefix: $this->prefix.$name.'_',
                    withClosure: $closure
                );
                $childBuilder = $childQuery->build();
                
                $rightTable = $relation->model::getTable();
                $subQueryMode = !empty($relation->model::relations())
                    || $this->hasExpressionColumns($relation->model);
                if($subQueryMode) {
                    $rightTable = "({$childBuilder->getSql()})";
                }

                $rightTableAlias = "{$this->prefix}{$name}";

                /** @var Column */
                $rightKeyColumn = $relation->model::getKeyColumn();
                $rightKey = $rightKeyColumn->name;
                if($subQueryMode) {
                    $rightKey = "{$rightTableAlias}__{$rightKey}";
                }

                $builder->join("{$rightTable} {$rightTableAlias}", "{$rightTableAlias}.{$rightKey} = {$table}.{$relation->foreignKey}");

                $rightColumns = $childBuilder->getSelectBuilder()->toArray();
                foreach($rightColumns as $rightColumn) {
                    $col = $rightColumn[ $subQueryMode ? 'alias' : 'column' ];
                    $builder->select(["{$rightTableAlias}", "{$col}", $rightColumn['alias']]);
                }

                continue;
            }

            if($relation instanceof Aggregate) {
                $rightTableAlias = "{$this->prefix}{$relation->query}";

                if(!in_array($relation->query, $loadedAggregatesQuery)) {
                    /** @var IQueryBuilder */
                    $childBuilder = $this->model::{$relation->query}();
                    $rightTable = "({$childBuilder->getSql()})";
                    

                    $rightKey = $relation->key;
                    $leftTable = $this->model::getTable();

                    /** @var Column */
                    $leftKeyColumn = $this->model::getKeyColumn();
                    $leftKey = $leftKeyColumn->name;

                    $builder->leftJoin("{$rightTable} {$rightTableAlias}", "{$rightTableAlias}.{$rightKey} = {$leftTable}.{$leftKey}");
                    $loadedAggregatesQuery[] = $relation->query;
                }

                $colPrefix = "{$this->prefix}{$relation->query}_";
                $alias = "{$colPrefix}_{$relation->column}";

                if($relation->expression !== null) {
                    $node = $this->resolveExpression($relation->expression, [
                        'alias' => $alias,
                        'column' => $relation->column,
                        'source' => $rightTableAlias,
                        'prefix' => $this->prefix,
                        'model' => $this->model,
                    ]);
                    $builder->select($this->withAlias($node, $alias));
                    continue;
                }

                $builder->select(
                    ["{$rightTableAlias}", "{$relation->column}", $alias]
                );
                continue;
            }
        }
    }

    /**
     * @param ?Closure(IQueryBuilder): void $query
     */
    public function build(?Closure $query = null): IQueryBuilder {
        $builder = $this->builder();

        $table = $this->model::getTable();
        $columns = $this->model::columns();

        $colPrefix = $this->prefix != "" ? "{$this->prefix}_" : "";
        foreach($columns as $column) {
            $alias = $colPrefix.$column->name;

            if($column->expression !== null) {
                $node = $this->resolveExpression($column->expression, [
                    'alias' => $alias,
                    'column' => $column->name,
                    'source' => $table,
                    'prefix' => $this->prefix,
                    'model' => $this->model,
                ]);
                $builder->select($this->withAlias($node, $alias));
                continue;
            }

            $builder->select([
                $table, 
                $column->name, 
                $alias
            ]);
        }

        if($this->joinBuilder->hasJoins()) {
            $builder->join($this->joinBuilder);
        }

        if($this->whereBuilder->hasWheres()) {
            $builder->where($this->whereBuilder);
        }

        if($this->orderBuilder->hasOrders()) {
            $builder->order($this->orderBuilder);
        }

        if($this->withBuilder->hasClauses()) {
            $this->buildWith($builder);
        }

        $builder->limit($this->limit)->offset($this->offset);
        if($query) $query($builder);
        return $builder;
    }

    /**
     * @param ?Closure(WithBuilder) $closure
     */
    public function with(string $name, ?Closure $closure = null) {
        $this->withBuilder->with($name, $closure);
        return $this;
    }

    /**
     * @param Closure(WhereBuilder) | array<int, array | string> | string $input
     */
    public function where(mixed $input): static {
        $this->whereBuilder->where($input);
        return $this;
    }

    public function limit(?int $limit): static {
        $this->limit = $limit;
        return $this;
    }

    public function offset(?int $offset): static {
        $this->offset = $offset;
        return $this;
    }

    public function order(mixed ...$args): static {
        $this->orderBuilder->multi($args);
        return $this;
    }

    /** @param Closure(OrderBuilder): void $input */
    public function orderUsing(Closure $input): static {
        $input($this->orderBuilder);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static {
        $this->orderBuilder->basic($column, $direction);
        return $this;
    }

    /** 
     * @param ?Closure(IQueryBuilder): void $query 
     * @return Collection | T | null 
     * */
    public function find(mixed $id, ?Closure $query = null): Collection | Model | null {
        $ids = is_array($id) ? $id : [$id];

        /** @var Column */
        $keyColumn = $this->model::getKeyColumn();
        $key = $keyColumn->name;

        $this->where(array_map(fn($id) => [$key, $id], $ids));
        if(count($ids) == 1) return $this->first($query);
        return $this->all($query);
    }

    /** 
     * @param ?Closure(IQueryBuilder): void $query
     * @return Collection<T> */
    public function all(?Closure $query = null): Collection {
        $rows = $this->build($query)->get()->rows(); 
        return Collection::fromMap(fn($row) => $this->model::of($row, $this->prefix), $rows);
    }

    /** 
     * @param ?Closure(IQueryBuilder): void $query
     * @return T | null */
    public function first(?Closure $query = null): ?Model {
        $builder = $this->build();
        $builder->limit(1);
        if($query) $query($builder);
        $row = $builder->get()->row();
        return isset($row) ? $this->model::of($row) : null;
    }

    /** @param \Closure(IQueryBuilder): void $query */
    public function count(?\Closure $query = null): int {
        $table = $this->model::getTable();

        /** @var Column */
        $key = $this->model::getKeyColumn();

        $builder = $this->build($query);
        $sql = $builder->getSql();
        
        $builder = DB::connection($this->model::getSchema())->table("({$sql}) count_{$table}")->select(
            ["COUNT(count_{$table}.{$key->name})", "total"]
        );

        $row = $builder->get()->row();
        return $row != null ? (int)$row->total : 0;
    }

    /** 
     * @param array<string, mixed> $data 
     * */
    public function insert(array $data): int | string {
        $prepared_data = [];

        /** @var list<Column> */
        $columns = $this->model::columns();
        foreach($columns as $column) {
            $prepared_data[$column->name] = $data[$column->name];
        }

        $id = $this->builder()->insert($prepared_data);

        /** @var Column */
        $keyColumn = $this->model::getKeyColumn();
        $key = $keyColumn->name;
        return $data[$key] ?? $id;
    }

    public function update(array $data): bool {
        if(!$this->whereBuilder->hasWheres())
            throw new InvalidArgumentException("Operasi UPDATE membutuhkan setidaknya 1 klausa where");
        return $this->builder()->where($this->whereBuilder)
            ->update($data);
    }

    public function delete(): bool {
        if(!$this->whereBuilder->hasWheres())
            throw new InvalidArgumentException("Operasi DELETE membutuhkan setidaknya 1 klausa where");
        return $this->builder()->where($this->whereBuilder)
            ->delete();
    }


    /**
     * Mengubah expression attribute menjadi node SELECT.
     *
     * String adalah nama static method resolver pada model. Array adalah node
     * SelectNode canonical. Node hasil resolver maupun array sama-sama
     * dikualifikasi dengan source.
     *
     * @param array{alias: string, column: string, source: string, prefix: string, model: class-string<Model>} $context
     */
    private function resolveExpression(array|string $expression, array $context): array {
        if(is_string($expression)) {
            $model = $context['model'];
            $method = $expression;

            if(!method_exists($model, $method)) {
                throw new InvalidArgumentException(
                    "Resolver '{$method}' tidak ditemukan pada {$model}."
                );
            }

            $reflection = new ReflectionMethod($model, $method);
            if(!$reflection->isStatic() || !$reflection->isPublic()) {
                throw new InvalidArgumentException(
                    "Resolver '{$method}' pada {$model} harus static dan public."
                );
            }

            $node = $model::$method($context);
            if(!is_array($node) || !isset($node['type'])) {
                throw new InvalidArgumentException(
                    "Resolver '{$method}' harus mengembalikan node SelectNode."
                );
            }

            $type = $node['type'];
            if(!is_string($type) || !in_array($type, [
                SelectNode::TYPE_COLUMN,
                SelectNode::TYPE_FUNCTION,
                SelectNode::TYPE_LITERAL,
                SelectNode::TYPE_RAW,
            ], true)) {
                throw new InvalidArgumentException(
                    "Resolver '{$method}' mengembalikan tipe node yang tidak dikenali."
                );
            }

            return $this->qualifyNode($node, $context['source']);
        }

        return $this->qualifyNode($expression, $context['source']);
    }

    /**
     * Mengisi tabel pada node kolom yang belum punya tabel.
     */
    private function qualifyNode(array $node, string $table): array {
        $type = $node['type'] ?? SelectNode::TYPE_COLUMN;

        if($type === SelectNode::TYPE_COLUMN) {
            if(empty($node['table'])) {
                $node['table'] = $table;
            }
            return $node;
        }

        if($type === SelectNode::TYPE_FUNCTION) {
            $arguments = [];
            foreach($node['arguments'] ?? [] as $argument) {
                if(!is_array($argument)) {
                    throw new InvalidArgumentException(
                        'Argumen expression harus berupa node SelectNode.'
                    );
                }
                $arguments[] = $this->qualifyNode($argument, $table);
            }
            $node['arguments'] = $arguments;
        }

        return $node;
    }

    private function withAlias(array $node, string $alias): array {
        $node['alias'] = $alias;
        return $node;
    }

    /**
     * @param class-string<Model> $model
     */
    private function hasExpressionColumns(string $model): bool {
        foreach($model::columns() as $column) {
            if($column->expression !== null) return true;
        }
        return false;
    }

}
