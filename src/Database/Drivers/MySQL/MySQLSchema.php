<?php 

namespace Selvi\Database\Drivers\MySQL;

use mysqli;
use Selvi\Database\Schema;
use Selvi\Database\Result;
use Selvi\Exception;

class MySQLSchema implements Schema {

    private Array | null $config;
    private mysqli $instance;

    public function __construct(Array $config)
    {
        $this->config = $config;
    }

    public function getConfig(): Array | null {
        return $this->config;
    }

    public function connect(): bool
    {
        if(!isset($this->instance)) {
            $this->instance = new mysqli(
                $this->config['host'], 
                $this->config['username'], 
                $this->config['password'], 
                $this->config['database'] ?? null, 
                $this->config['port'] ?? null, 
                $this->config['socket'] ?? null
            );
        }
        return ($this->instance->connect_errno > 0) ? false : true;
    }

    public function disconnect(): bool
    {
        if(isset($this->instance)) {
            return $this->instance->close();
        }
        return false;
    }

    public function select_db(string $db): bool {
        return $this->instance->select_db($db);
    }

    public function error(): mixed {
        return $this->instance->error;
    }

    public function query(string $sql): Result | bool {
        $res = $this->instance->query($sql);
        if (is_bool($res)) {
            if ($res ===  false) {
                throw new Exception($this->error(), 'db/query-error');
            }
            return $res;
        }
        return new MySQLResult($res);
    }

    function getSql(string $table): string {
        $select = "SELECT *";
        if(strlen($this->_select) > 0) $select = "SELECT {$this->_select}";

        $from = "FROM {$table}";
        $where = $this->_where;
        if(strlen($where) > 0) $where = $where;

        $order = $this->_order;
        $offset = $this->_offset;
        $limit = $this->_limit;

        $join = $this->_join;
        $group = $this->_group;
        
        $query = implode(" ", array_filter([$select, $from, $join, $where, $group, $order, $limit, $offset], function ($v) {
            return strlen($v) > 0;
        }));
        return $query;
    }


    public function get(string $tbl): Result
    {
        $sql = $this->getSql($tbl);
        $res = $this->instance->query($sql);
        $this->reset();
        return new MySQLResult($res);
    }

    private ?string $_select = null;

    public function select(string|array $cols): Schema
    {
        if(is_string($cols)) $this->_select = $cols;
        if(is_array($cols)) $this->_select = implode(",", $cols);
        return $this;
    }

    private function prepareValue($val): string {
        if(is_null($val)) return 'NULL';
        if(is_bool($val)) return ($val == true ? '1' : '0');
        if(is_string($val)) {
            $val = str_replace("\\", "\\\\", $val); // replace backslash View\Update => View\\Update
            $val = str_replace("'", "\\'", $val); // replace single quotes Qur'an => Qur\'an
            $val = str_replace("\"", "\\\"", $val); // replace double quotes Qur"an => Qur\"an
            $val = "\"".$val."\""; // add double quotes before and after "Qur\'an", "Qur\"an", "View\\Update"
            return $val;
        }
        return $val;
    }

    private ?string $_join = "";

    function join(string $tbl, string $cond, string $direction = null): Schema {
        $str = "";
        $str .= (strlen($this->_join) > 0 ? " " : "");
        $str .= ($direction != null ? $direction." " : "");
        $str .= "JOIN {$tbl} ON {$cond}";
        $this->_join .= $str;
        return $this;
    }

    function innerJoin(string $tbl, string $cond): Schema {
        return $this->join($tbl, $cond, 'INNER');
    }

    function leftJoin(string $tbl, string $cond): Schema {
        return $this->join($tbl, $cond, 'LEFT');
    }

    function rightJoin(string $tbl, string $cond): Schema {
        return $this->join($tbl, $cond, 'RIGHT');
    }

    private ?string $_where = "";

    public function where(string|array $where): Schema
    {
        $tmp = "";
        if(is_string($where)) $tmp = $where;
        if(is_array($where)) {
            foreach($where as $index => $w) {
                if ($index !== 0 ) $tmp .= " AND ";
                if(is_string($w)) $tmp .= $w;
                if(is_array($w)) {
                    if(count($w) == 2) $tmp .= "{$w[0]} = {$this->prepareValue($w[1])}";
                    if(count($w) == 3) $tmp .= "{$w[0]} {$w[1]} {$this->prepareValue($w[2])}";
                }
            }
        }
        $this->_where .= (strlen($tmp) > 0 ? ($this->_where == "" ? "WHERE" : " AND")." ({$tmp})" : "");
        return $this;
    }

    private ?string $_orWhere = null;
    function orWhere(string|array $orWhere): Schema {
        $tmp = "";
        if(is_string($orWhere)) $tmp = $orWhere;
        if(is_array($orWhere)) {
            foreach($orWhere as $index => $w) {
                if ($index !== 0 ) $tmp .= " OR ";
                if(is_string($w)) $tmp .= $w;
                if(is_array($w)) {
                    if(count($w) == 2) $tmp .= "{$w[0]} = {$this->prepareValue($w[1])}";
                    if(count($w) == 3) $tmp .= "{$w[0]} {$w[1]} {$this->prepareValue($w[2])}";
                }
            }
        }
        $this->_orWhere .= (strlen($tmp) > 0 ? ($this->_orWhere == null ? "WHERE" : " OR")." ({$tmp})" : "");
        return $this;
    }

    private ?string $_group = "";

    function groupBy(mixed $group): Schema {
        $str = "GROUP BY ";
        if(is_string($group)) $str .= $group;
        if(is_array($group)) $str .= implode(",", $group);
        (strlen($this->_group) > 0) ? $this->_group .= $str : $this->_group = $str;
        return $this;
    }

    private ?string $_order = "";

    function order(string|array $order, ?string $direction = null): Schema
    {
        $this->_order .= strlen($this->_order) > 0 ? ', ' : 'ORDER BY ';
        if(is_array($order)) {
            if(array_is_list($order)) {
                $this->_order .= implode(', ', $order, array_keys($order));
            } else {
                $this->_order .= implode(', ', array_map(function ($field) use ($order) {
                    return $field.' '.$order[$field];
                }, array_keys($order)));
            }
        }

        if(is_string($order)) {
            if($direction !== null) {
                $this->_order .= $order.' '.$direction;
            } else {
                $this->_order .= $order;
            }
        }

        return $this;
    }

    private ?string $_limit = null;

    function limit(int $limit = null): Schema {
        if ($limit !== null) $this->_limit = "LIMIT {$limit}";
        return $this;
    }

    private ?string $_offset = null;

    function offset(int $offset = null) : Schema {
        if ($offset !== null) $this->_offset = "OFFSET {$offset}";
        return $this;
    }

    public function prepareMigrationTables(): Result | bool {
        return $this->create('_migration', [
            'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
            'filename' => 'VARCHAR(150) NOT NULL',
            'direction' => 'VARCHAR(15) NOT NULL',
            'start' => 'INT NOT NULL',
            'finish' => 'INT NOT NULL',
            'output' => 'TEXT NOT NULL',
            'dbuser' => 'VARCHAR(15) NOT NULL'
        ]);
    }


    // create drop table

    public function create(string $table, array $columns): Result | bool {
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (";
        $names = array_keys($columns);

        $sqlCols = [];
        foreach($names as $name) {
            $sqlCols[] = "{$name} {$columns[$name]}";
        }

        $sql .= implode(", ", $sqlCols);
        $sql .= ");";
        $this->reset();
        return $this->query($sql);
    }

    
    public function drop(string $table): Result|bool {
        $sql = "DROP TABLE IF EXISTS {$table};";
        $this->reset();
        return $this->query($sql);
    }

    // insert update delete

    public function insert(string $table, array $data): Result | bool {
        $columns = [];
        $values = [];
        foreach($data as $c => $v){
            $columns[] = $c;
            $values[] = self::prepareValue($v);
        }

        $col_str = implode(', ', $columns);
        $val_str = implode(', ', $values);
        $sql = "INSERT INTO {$table} ({$col_str}) VALUES ({$val_str})";
        $this->reset();
        return $this->query($sql);
    }

    function update(string $tbl, array $data): Result | bool {
        $columns = [];
        foreach($data as $c => $v){
            $columns[] = "{$c} = " . $this->prepareValue($v);
        }
        $col_str = implode(", ", $columns);

        $where = $this->_where;
        if(strlen($where) > 0) $where = " ".$where;

        $sql = "UPDATE {$tbl} SET {$col_str}{$where}";
        $this->reset();
        return $this->query($sql);
    }

    function delete(string $tbl): Result | bool {
        $where = $this->_where;
        if(strlen($where) > 0) $where = " ".$where;

        $sql = "DELETE FROM {$tbl}{$where}";
        $this->reset();
        return $this->query($sql); 
    }

    private function reset() {
        $this->_select = '';
        $this->_where = '';
        $this->_order = '';
        $this->_offset = "OFFSET 0";
        $this->_limit = '';
        $this->_join = '';
        $this->_group = '';
    }

    function getLastId(){
        return $this->instance->insert_id;
    }

    // Transaction

    function startTransaction(): bool {
        return $this->query("START TRANSACTION");
    }

    function commit(): bool {
        return $this->query("COMMIT");
    }

    function rollback(): bool {
        return $this->query("ROLLBACK");
    }

    function alter(string $table): Result | bool {
        $alter = "ALTER TABLE {$table}";
        $modifyColumn = $this->_modifyColumn;
        $addColumn = $this->_addColumn;
        $dropColumn = $this->_dropColumn;
        $dropPirmaryKey = $this->_dropPrimary;

        $addPrimary = $this->_addPrimary;
        $sql = implode(" ", [$alter, $modifyColumn, $addColumn, $dropPirmaryKey, $dropColumn, $addPrimary]);
        $this->resetAltertable();
        return $this->query($sql);
    }

    function resetAltertable() {
        $this->_modifyColumn = "";
        $this->_addColumn = "";
        $this->_dropColumn = "";
        $this->_dropPrimary = "";
        $this->_addPrimary = "";
    }

    private ?string $_modifyColumn = "";

    function modifyColumn(string $column, string $type): Schema {
        $this->_modifyColumn = "MODIFY COLUMN {$column} {$type}";
        return $this;
    }

    private ?string $_addColumn = "";

    function addColumn(string $column, string $type): Schema {
        $this->_addColumn = "ADD {$column} {$type}";
        return $this;
    }

    private ?string $_dropColumn = "";

    function dropColumn(string $column): Schema {
        $this->_dropColumn = "DROP COLUMN {$column}";
        return $this;
    }

    private ?string $_dropPrimary = "";

    function dropPrimary(): Schema {
        $this->_dropPrimary = "DROP PRIMARY KEY";
        return $this;
    }

    private ?string $_addPrimary = "";

    function addPrimary(string $column, string $primary_name): Schema {
        $this->_addPrimary = "ADD CONSTRAINT {$primary_name} PRIMARY KEY ({$column})";
        return $this;
    }

    function createIndex(string $table, string $index_name, array $cols): Result|bool {
        $column = implode(",", $cols);
        $sql = "CREATE INDEX {$index_name} ON {$table} ({$column});";
        return $this->query($sql);
    }
    function dropIndex(string $table, string $index_name): Result|bool {
        $sql = "DROP INDEX {$index_name} ON {$table};";
        return $this->query($sql);
    }
    function truncate(string $table): Result|bool {
        $sql = "TRUNCATE TABLE {$table}";
        return $this->query($sql);
    }


}