<?php

namespace Selvi\Database\Builder\DML;

use Closure;
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

    private WhereBuilder $whereBuilder;
    private SelectBuilder $selectBuilder;

    function __construct() {
        $this->join = new JoinBuilder();
        $this->group = new GroupBuilder();
        $this->order = new OrderBuilder();
        
        $this->whereBuilder = new WhereBuilder();
        $this->selectBuilder = new SelectBuilder();
    }

    public function connection(): IConnection {
        if ($this->connectionInjected !== null) return $this->connectionInjected;
        $db = $this->connectionName !== '' ? Manager::get($this->connectionName) : Manager::default();
        if ($db === null) throw new RuntimeException('Koneksi database belum diatur.');
        return $db;
    }

    public function useConnection(string | IConnection $connection): static {
        if ($connection instanceof IConnection) {
            $this->connectionInjected = $connection;
            return $this;
        }
        $this->connectionName = $connection;
        return $this;
    }

    public function table(string $name): static {
        $this->table = $name;
        return $this;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function select(mixed ...$columns): static {
        $this->selectBuilder->multi($columns);
        return $this;
    }

    public function selectColumns(array $columns): static {
        $this->selectBuilder->multi($columns);
        return $this;
    }

    public function selectUsing(Closure $input): static {
        $input($this->selectBuilder);
        return $this;
    }

    public function getSelectBuilder(): SelectBuilder {
        return $this->selectBuilder;
    }

    public function getSql(): string {
        $db = $this->connection();
        $grammar = $db->grammar();
        return $grammar->compileSelect($this);
    }

    public function get(): IResult {
        $db = $this->connection();
        $grammar = $db->grammar();
        $sql = $grammar->compileSelect($this);
        return $db->query($sql);
    }

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

    public function where(mixed $input): static {
        $this->whereBuilder->where($input);
        return $this;
    }

    public function orWhere(mixed $input): static {
        $this->whereBuilder->orWhere($input);
        return $this;
    }

    public function getWhereClauses(): array {
        return $this->whereBuilder->toArray();
    }

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





    

    private JoinBuilder $join;

    private GroupBuilder $group;

    private OrderBuilder $order;

    

    private ?int $limit = null;

    private ?int $offset = null;

    







    
    public function joins(): array {
        return $this->join->toArray();
    }

    public function groups(): array {
        return $this->group->toArray();
    }

    public function orders(): array {
        return $this->order->toArray();
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function getOffset(): ?int {
        return $this->offset;
    }



    

    /**
     * @param JoinBuilder|string $input
     */
    public function join(mixed $input, ?string $on = null): static {
        $this->join->join($input, $on);
        return $this;
    }

    public function leftJoin(string $table, string $on): static {
        $this->join->leftJoin($table, $on);
        return $this;
    }

    public function innerJoin(string $table, string $on): static {
        $this->join->innerJoin($table, $on);
        return $this;
    }

    public function rightJoin(string $table, string $on): static {
        $this->join->rightJoin($table, $on);
        return $this;
    }

    public function groupBy(...$columns): static {
        $this->group->group(...$columns);
        return $this;
    }

    public function group(...$columns): static {
        $this->group->group(...$columns);
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

    public function orderBy(string $column, string $direction = 'ASC'): static {
        $this->order->orderBy($column, $direction);
        return $this;
    }

    public function order(mixed ...$args): static {
        $this->order->order(...$args);
        return $this;
    }





    

    
}
