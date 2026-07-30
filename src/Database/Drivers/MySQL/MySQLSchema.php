<?php 

declare(strict_types=1);

namespace Selvi\Database\Drivers\MySQL;

use mysqli;
use mysqli_sql_exception;
use Selvi\Database\Schema;
use Selvi\Database\Result;
use Selvi\Exception\DatabaseException;

class MySQLSchema implements Schema {

    private Array | null $config;
    private mysqli $instance;
    private ?string $_select = null;
    private ?string $_where = null;
    private ?string $_order = null;
    private ?int $_offset = 0;
    private ?int $_limit = -1;
    private ?string $_join = null;
    private ?string $_group = null;
    private ?string $_modifyColumn = null;
    private ?string $_addColumn = null;
    private ?string $_dropColumn = null;
    private ?string $_dropPrimary = null;
    private ?string $_addPrimary = null;

    public function __construct(Array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    public function getConfig(): Array | null {
        return $this->config;
    }

    public function connect(): bool
    {
        try {
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
            return true;
        } catch(mysqli_sql_exception $e) {
            throw new DatabaseException($e->getMessage(), 500, $e->getSqlState());
        }
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
        try {
            $res = $this->instance->query($sql);
            if(is_bool($res)) return $res;
            return new MySQLResult($res);
        } catch(mysqli_sql_exception $e) {
            throw new DatabaseException($e->getMessage(), 500, $e->getSqlState(), $sql);
        }
    }

    public function getSql(string $table = null): string {
        $select = "SELECT *";
        if (($this->_select ?? '') !== '') $select = "SELECT {$this->_select}";

        $from = $table != null ? "FROM {$table}" : "";
        $join = $this->_join;
        $where = $this->_where;
        $group = $this->_group;
        $order = $this->_order;

        $limit = "";
        if($this->_limit !== null && $this->_limit > -1) {
            $limit = ($this->_limit > -1 ? "LIMIT ".$this->_limit : "").($this->_offset > 0 ? " OFFSET ".$this->_offset : "");
        }
        
        $sql = implode(" ", array_filter([$select, $from, $join, $where, $group, $order, $limit], function ($v) {
            return ($v ?? '') !== '';
        }));
        $this->reset();
        return $sql;
    }


    public function get(string $tbl = null): Result | bool
    {
        $sql = $this->getSql($tbl);
        return $this->query($sql);
    }

    public function select(string|array $cols): Schema
    {
        if(is_string($cols)) $this->_select = $cols;
        if(is_array($cols)) $this->_select = implode(",", $cols);
        return $this;
    }

    private function prepareValue(mixed $val): string {
        if(is_null($val)) return 'NULL';
        if(is_bool($val)) return ($val == true ? '1' : '0');
        if(is_int($val) || is_float($val)) return (string) $val;
        if(is_string($val)) {
            $val = str_replace("\\", "\\\\", $val);
            $val = str_replace("'", "\\'", $val);
            $val = str_replace("\"", "\\\"", $val);
            $val = "\"".$val."\"";
            return $val;
        }
        return (string) $val;
    }

    public function join(string $tbl, string $cond, ?string $direction = null): Schema {
        $str = "";
        $str .= (($this->_join ?? '') !== '' ? " " : "");
        $str .= ($direction != null ? $direction." " : "");
        $str .= "JOIN {$tbl} ON {$cond}";
        $this->_join .= $str;
        return $this;
    }

    public function innerJoin(string $tbl, string $cond): Schema {
        return $this->join($tbl, $cond, 'INNER');
    }

    public function leftJoin(string $tbl, string $cond): Schema {
        return $this->join($tbl, $cond, 'LEFT');
    }

    public function rightJoin(string $tbl, string $cond): Schema {
        return $this->join($tbl, $cond, 'RIGHT');
    }

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

    public function orWhere(string|array $orWhere): Schema {
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
        $this->_where .= (strlen($tmp) > 0 ? ($this->_where == null ? "WHERE" : " AND")." ({$tmp})" : "");
        return $this;
    }

    public function groupBy(mixed $group): Schema {
        $str = "GROUP BY ";
        if(is_string($group)) $str .= $group;
        if(is_array($group)) $str .= implode(",", $group);
        ($this->_group ?? '') !== '' ? $this->_group .= $str : $this->_group = $str;
        return $this;
    }

    public function order(string|array $order, ?string $direction = null): Schema
    {
        $tmp = '';
        if(is_array($order) && count($order) > 0) {
            $tmp .= implode(', ', array_map(function ($key, $value) {
                if(is_int($key)) return $value;
                return "$key $value";
            }, array_keys($order), $order));
        }

        if(is_string($order)) {
            if($direction !== null) {
                $tmp .= $order.' '.$direction;
            } else {
                $tmp .= $order;
            }
        }

        $this->_order .= ($tmp !== '') ? (($this->_order ?? '') !== '' ? ', '.$tmp : 'ORDER BY '.$tmp) : "";
        return $this;
    }

    public function limit(int $limit = null): Schema {
        $this->_limit = $limit;
        return $this;
    }

    public function offset(int $offset = null): Schema {
        $this->_offset = $offset;
        return $this;
    }

    public function prepareMigrationTables(): Result | bool {
        return $this->create('_migration', [
            'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
            'filename' => 'VARCHAR(150) NOT NULL',
            'direction' => 'VARCHAR(15) NOT NULL',
            'start' => 'INT NOT NULL',
            'finish' => 'INT NOT NULL',
            'output' => 'VARCHAR(15) NOT NULL',
            'dbuser' => 'VARCHAR(15) NOT NULL',
            'error_msg' => 'TEXT',
            'error_state' => 'VARCHAR(50)',
            'error_query' => 'TEXT'
        ]);
    }

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

    public function insert(string $table, array $data): Result | bool {
        $columns = [];
        $values = [];
        foreach($data as $c => $v){
            $columns[] = $c;
            $values[] = $this->prepareValue($v);
        }

        $col_str = implode(', ', $columns);
        $val_str = implode(', ', $values);
        $sql = "INSERT INTO {$table} ({$col_str}) VALUES ({$val_str})";
        $this->reset();
        return $this->query($sql);
    }

    public function update(string $tbl, array $data): Result | bool {
        $columns = [];
        foreach($data as $c => $v){
            $columns[] = "{$c} = " . $this->prepareValue($v);
        }
        $col_str = implode(", ", $columns);

        $where = $this->_where;
        if (($where ?? '') !== '') $where = " ".$where;

        $sql = "UPDATE {$tbl} SET {$col_str}{$where}";
        $this->reset();
        return $this->query($sql);
    }

    public function delete(string $tbl): Result | bool {
        $where = $this->_where;
        if (($where ?? '') !== '') $where = " ".$where;

        $sql = "DELETE FROM {$tbl}{$where}";
        $this->reset();
        return $this->query($sql); 
    }

    private function reset(): void {
        $this->_select = null;
        $this->_where = null;
        $this->_order = null;
        $this->_offset = 0;
        $this->_limit = -1;
        $this->_join = null;
        $this->_group = null;

        $this->_modifyColumn = null;
        $this->_addColumn = null;
        $this->_dropColumn = null;
        $this->_dropPrimary = null;
        $this->_addPrimary = null;
    }

    public function lastId(): int {
        return (int) $this->select('LAST_INSERT_ID() AS lastid')
            ->get()->row()->lastid;
    }

    public function startTransaction(): bool {
        return $this->query("START TRANSACTION");
    }

    public function commit(): bool {
        return $this->query("COMMIT");
    }

    public function rollback(): bool {
        return $this->query("ROLLBACK");
    }

    public function alter(string $table): Result | bool {
        $alter = "ALTER TABLE {$table}";
        $modifyColumn = $this->_modifyColumn;
        $addColumn = $this->_addColumn;
        $dropColumn = $this->_dropColumn;
        $dropPirmaryKey = $this->_dropPrimary;

        $addPrimary = $this->_addPrimary;
        $sql = implode(" ", array_filter([$alter, $modifyColumn, $addColumn, $dropPirmaryKey, $dropColumn, $addPrimary], function ($v) {
            return ($v ?? '') !== '';
        }));
        $this->reset();
        return $this->query($sql);
    }

    public function addColumnFirst(string $column, string $type): Schema {
        $this->addColumn($column, $type);
        $this->_addColumn .= " FIRST";
        return $this;
    }

    public function addColumnAfter(string $afterCol, string $column, string $type): Schema {
        $this->addColumn($column, $type);
        $this->_addColumn .= " AFTER {$afterCol}";
        return $this;
    }

    public function modifyColumn(string $column, string $type): Schema {
        $this->_modifyColumn = "MODIFY COLUMN {$column} {$type}";
        return $this;
    }

    public function addColumn(string $column, string $type): Schema {
        $this->_addColumn = "ADD {$column} {$type}";
        return $this;
    }

    public function dropColumn(string $column): Schema {
        $this->_dropColumn = "DROP COLUMN {$column}";
        return $this;
    }

    public function dropPrimary(): Schema {
        $this->_dropPrimary = "DROP PRIMARY KEY";
        return $this;
    }

    public function addPrimary(string $column, string $primary_name): Schema {
        $this->_addPrimary = "ADD CONSTRAINT {$primary_name} PRIMARY KEY ({$column})";
        return $this;
    }

    public function createIndex(string $table, string $index_name, array $cols): Result | bool {
        $column = implode(",", $cols);
        $sql = "CREATE INDEX {$index_name} ON {$table} ({$column});";
        return $this->query($sql);
    }

    public function dropIndex(string $table, string $index_name): Result | bool {
        $sql = "DROP INDEX {$index_name} ON {$table};";
        return $this->query($sql);
    }

    public function truncate(string $table): Result | bool {
        $sql = "TRUNCATE {$table}";
        return $this->query($sql);
    }

    public function rename(string $table, string $new_table): Result | bool {
        return $this->query('RENAME TABLE '.$table.' TO '.$new_table);
    }

    public function changeColumn(string $table, string $oldCol, string $newCol, string $type): Result | bool {
        return $this->query('ALTER TABLE '.$table.' CHANGE COLUMN '.$oldCol.' '.$newCol.' '.$type);
    }

    public function createDatabase(string $name): Result|bool {
        return $this->query('CREATE DATABASE '.$name.';');
    }

    public function dropDatabase(string $name): Result|bool {
        return $this->query('DROP DATABASE '.$name.';');
    }

}