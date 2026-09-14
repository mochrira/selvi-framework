<?php 

namespace Selvi\Database\Builder;

use Selvi\Database\Contracts\QueryBuilderInterface;
use Selvi\Database\Contracts\ResultInterface;
use Selvi\Database\Contracts\SchemaInterface;
use Selvi\Database\Manager;

class QueryBuilder implements QueryBuilderInterface {

    private $connection = '';

    private string $table = '';

    private array $columns = [];

    private WhereBuilder $where;

    private JoinBuilder $join;

    function __construct() {
        $this->where = new WhereBuilder();
        $this->join = new JoinBuilder();
    }

    public function connection(string $name): QueryBuilder {
        $this->connection = $name;
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

    public function db(): SchemaInterface {
        if(!empty($this->connection)) return Manager::get($this->connection);
        return Manager::default();
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

    public function get(): ResultInterface {
        $db = $this->db();
        $grammar = $db->grammar();
        $sql = $grammar->compileSelect($this);
        return $db->query($sql);
    }

}