<?php

namespace Selvi\Database\Builder\DML;

use Closure;
use Override;
use RuntimeException;
use Selvi\Database\Interfaces\IQueryBuilder;
use Selvi\Database\Interfaces\IConnection;
use Selvi\Database\Interfaces\IResult;
use Selvi\Database\Manager;

class QueryBuilder implements IQueryBuilder {

    /**
     * Start of Evaluated
     */

    private string $connectionName = '';
    private ?IConnection $connectionInjected = null;

    private string $table = '';

    private SelectBuilder $selectBuilder;
    private WhereBuilder $whereBuilder;
    private JoinBuilder $joinBuilder;
    private GroupBuilder $groupBuilder;
    private OrderBuilder $orderBuilder;

    private ?int $limit = null;
    private ?int $offset = null;

    function __construct() {
        $this->selectBuilder = new SelectBuilder();
        $this->whereBuilder = new WhereBuilder();
        $this->joinBuilder = new JoinBuilder();
        $this->groupBuilder = new GroupBuilder();
        $this->orderBuilder = new OrderBuilder();
    }

    #[Override]
    public function connection(): IConnection {
        if ($this->connectionInjected !== null) return $this->connectionInjected;
        $db = $this->connectionName !== '' ? Manager::get($this->connectionName) : Manager::default();
        if ($db === null) throw new RuntimeException('Koneksi database belum diatur.');
        return $db;
    }

    #[Override]
    public function useConnection(string | IConnection $connection): static {
        if ($connection instanceof IConnection) {
            $this->connectionInjected = $connection;
            return $this;
        }
        $this->connectionName = $connection;
        return $this;
    }

    #[Override]
    public function table(string $name): static {
        $this->table = $name;
        return $this;
    }

    #[Override]
    public function getTable(): string {
        return $this->table;
    }

    #[Override]
    public function select(mixed ...$columns): static {
        $this->selectBuilder->multi($columns);
        return $this;
    }

    #[Override]
    public function selectColumns(array $columns): static {
        $this->selectBuilder->multi($columns);
        return $this;
    }

    #[Override]
    public function selectUsing(Closure $input): static {
        $input($this->selectBuilder);
        return $this;
    }

    #[Override]
    public function getSelectBuilder(): SelectBuilder {
        return $this->selectBuilder;
    }

    #[Override]
    public function getSql(): string {
        $db = $this->connection();
        $grammar = $db->grammar();
        return $grammar->compileSelect($this);
    }

    #[Override]
    public function get(): IResult {
        $db = $this->connection();
        $grammar = $db->grammar();
        $sql = $grammar->compileSelect($this);
        return $db->query($sql);
    }

    #[Override]
    public function where(mixed $input): static {
        $this->whereBuilder->where($input);
        return $this;
    }

    #[Override]
    public function orWhere(mixed $input): static {
        $this->whereBuilder->orWhere($input);
        return $this;
    }

    #[Override]
    public function getWhereClauses(): array {
        return $this->whereBuilder->toArray();
    }

    #[Override]
    public function join(mixed $input, ?string $on = null): static {
        $this->joinBuilder->join($input, $on);
        return $this;
    }

    #[Override]
    public function joinUsing(Closure $input): static {
        $this->joinBuilder->join($input);
        return $this;
    }

    #[Override]
    public function leftJoin(string $table, string $on): static {
        $this->joinBuilder->leftJoin($table, $on);
        return $this;
    }

    #[Override]
    public function innerJoin(string $table, string $on): static {
        $this->joinBuilder->innerJoin($table, $on);
        return $this;
    }

    #[Override]
    public function rightJoin(string $table, string $on): static {
        $this->joinBuilder->rightJoin($table, $on);
        return $this;
    }

    #[Override]
    public function getJoinBuilder(): JoinBuilder {
        return $this->joinBuilder;
    }

    #[Override]
    public function group(...$columns): static {
        $this->groupBuilder->group(...$columns);
        return $this;
    }

    #[Override]
    public function getGroupBuilder(): GroupBuilder {
        return $this->groupBuilder;
    }

    #[Override]
    public function limit(?int $limit): static {
        $this->limit = $limit;
        return $this;
    }

    #[Override]
    public function getLimit(): ?int {
        return $this->limit;
    }

    #[Override]
    public function offset(?int $offset): static {
        $this->offset = $offset;
        return $this;
    }

    #[Override]
    public function getOffset(): ?int {
        return $this->offset;
    }

    #[Override]
    public function order(mixed ...$args): static {
        $this->orderBuilder->order(...$args);
        return $this;
    }

    #[Override]
    public function orderBy(string $column, string $direction = 'ASC'): static {
        $this->orderBuilder->orderBy($column, $direction);
        return $this;
    }

    #[Override]
    public function getOrderBuilder(): OrderBuilder {
        return $this->orderBuilder;
    }

    #[Override]
    public function insert(array $values): int | string {
        $insertBuilder = new InsertBuilder($this->table, $values);
        $db = $this->connection();
        $sql = $db->grammar()->compileInsert(
            $insertBuilder->getTable(),
            $insertBuilder->getColumns(),
            $insertBuilder->getRows()
        );
        $db->query($sql);
        return $db->lastId();
    }
    
    #[Override]
    public function update(array $values): bool {
        if (!$this->whereBuilder->hasWheres()) {
            throw new \LogicException('Operasi UPDATE memerlukan setidaknya satu kondisi WHERE untuk mencegah perubahan data massal secara tidak sengaja.');
        }

        $updateBuilder = new UpdateBuilder($values);
        $db = $this->connection();
        $sql = $db->grammar()->compileUpdate(
            $this->table,
            $updateBuilder->getValues(),
            $this->getWhereClauses()
        );
        return $db->query($sql) !== false;
    }

    #[Override]
    public function delete(): bool {
        if (!$this->whereBuilder->hasWheres()) {
            throw new \LogicException('Operasi DELETE memerlukan setidaknya satu kondisi WHERE untuk mencegah penghapusan data massal secara tidak sengaja.');
        }

        $db = $this->connection();
        $sql = $db->grammar()->compileDelete($this->table, $this->getWhereClauses());
        return $db->query($sql) !== false;
    }

    /**
     * End of Evaluated
     */
}
