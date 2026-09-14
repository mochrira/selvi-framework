<?php 

namespace Selvi\Database\Builder;

use RuntimeException;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Contracts\QueryBuilderInterface;
use Selvi\Database\Contracts\ResultInterface;
use Selvi\Database\DatabaseManager;

class QueryBuilder implements QueryBuilderInterface {

    private string $connectionName = '';

    private ?ConnectionInterface $injected = null;

    private string $table = '';

    private array $columns = [];

    private WhereBuilder $where;

    private JoinBuilder $join;

    private GroupBuilder $group;

    private OrderBuilder $order;

    private ?int $limit = null;

    private ?int $offset = null;

    function __construct() {
        $this->where = new WhereBuilder();
        $this->join = new JoinBuilder();
        $this->group = new GroupBuilder();
        $this->order = new OrderBuilder();
    }

    /**
     * Menentukan koneksi: nama yang terdaftar di DatabaseManager, atau objek
     * ConnectionInterface langsung bila ingin disuntikkan.
     */
    public function useConnection(string|ConnectionInterface $connection): static {
        if($connection instanceof ConnectionInterface) {
            $this->injected = $connection;
        } else {
            $this->connectionName = $connection;
        }

        return $this;
    }

    public function table(string $name): QueryBuilder {
        $this->table = $name;
        return $this;
    }

    public function select(array $columns) : QueryBuilder {
        $this->columns = $columns;
        return $this;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function getColumns(): array {
        return $this->columns;
    }

    public function wheres(): array {
        return $this->where->toArray();
    }

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


    public function connection(): ConnectionInterface {
        if($this->injected !== null) return $this->injected;

        $db = $this->connectionName !== '' ? DatabaseManager::get($this->connectionName) : DatabaseManager::default();

        if($db === null) {
            throw new RuntimeException('Koneksi database belum diatur.');
        }

        return $db;
    }

    public function where($input): static {
        $this->where->where($input);
        return $this;
    }

    public function orWhere($input): static {
        $this->where->orWhere($input);
        return $this;
    }

    public function join(string $table, string $on): static {
        $this->join->join($table, $on);
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

    public function get(): ResultInterface {
        $db = $this->connection();
        $grammar = $db->grammar();
        $sql = $grammar->compileSelect($this);
        return $db->query($sql);
    }

    public function insert(array $values): int|string {
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

    public function update(array $values): bool {
        if (!$this->where->hasWheres()) {
            throw new \LogicException('Operasi UPDATE memerlukan setidaknya satu kondisi WHERE untuk mencegah perubahan data massal secara tidak sengaja.');
        }

        $updateBuilder = new UpdateBuilder($values);
        $db = $this->connection();
        $sql = $db->grammar()->compileUpdate(
            $this->table,
            $updateBuilder->getValues(),
            $this->wheres()
        );
        return $db->query($sql) !== false;
    }

    public function delete(): bool {
        if (!$this->where->hasWheres()) {
            throw new \LogicException('Operasi DELETE memerlukan setidaknya satu kondisi WHERE untuk mencegah penghapusan data massal secara tidak sengaja.');
        }

        $db = $this->connection();
        $sql = $db->grammar()->compileDelete($this->table, $this->wheres());
        return $db->query($sql) !== false;
    }

}