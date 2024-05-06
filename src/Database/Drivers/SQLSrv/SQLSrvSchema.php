<?php 

namespace Selvi\Database\Drivers\SQLSrv;

use Selvi\Exception;
use Selvi\Database\Drivers\SQLSrv\SQLSrvResult;
use Selvi\Database\Result;
use Selvi\Database\Schema;

class SQLSrvSchema implements Schema {

    private Array | null $config;
    private $instance;

    function __construct(Array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    function getConfig(): Array | null{
        return $this->config;
    }

    function connect(): bool
    {
        $connString = $this->config['host'] . (isset($this->config['port']) ? ', ' . $this->config['port'] : '');
        $connInfo = [
            'UID' => $this->config['username'],
            'PWD' => $this->config['password']
        ];

        if(isset($this->config['TrustServerCertificate'])) $connInfo['TrustServerCertificate'] = $this->config['TrustServerCertificate'];
        if(isset($this->config['database'])) $connInfo['Database'] = $this->config['database'];

        $this->instance = sqlsrv_connect($connString, $connInfo);
        if(!$this->instance) {
            return false;
        }
        return true;
    }

    function disconnect(): bool
    {
        return sqlsrv_close($this->instance);
    }

    function select_db(string $db): bool
    {
        if($this->disconnect()) {
            $this->config['database'] = $db;
            return $this->connect();
        }
        return false;
    }

    public function prepareMigrationTables(): Result | bool {
        return $this->create('_migration', [
            'id' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'filename' => 'VARCHAR(150) NOT NULL',
            'direction' => 'VARCHAR(15) NOT NULL',
            'start' => 'INT NOT NULL',
            'finish' => 'INT NOT NULL',
            'output' => 'TEXT NOT NULL',
            'dbuser' => 'VARCHAR(15)'
        ]);
    }

    public function error(): mixed {
        return sqlsrv_errors();
    }

    function query(string $sql): SQLSrvResult | bool
    {
        $res = sqlsrv_query($this->instance, $sql, null, ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);
        if(is_bool($res)) {
            if($res === false) {
                $error = $this->error();
                throw new Exception($error[0]['message'],'db/query-error');
            }
            return $res;
        }
        return new SQLSrvResult($res);
    }

    private function prepareValue(mixed $val): string {
        if(is_null($val)) return 'NULL';
        if(is_bool($val)) return ($val == true ? '1' : '0');
        if(is_string($val)) {
            $val = str_replace("\\", "\\\\", $val); // replace backslash View\Update => View\\Update
            $val = str_replace("'", "\\'", $val); // replace single quotes Qur'an => Qur\'an
            $val = str_replace("\'", "\\\'", $val); // replace double quotes Qur"an => Qur\"an
            $val = "'".$val."'"; // add double quotes before and after "Qur\'an", "Qur\"an", "View\\Update"
            return $val;
        }
        return $val;
    }

    private ?string $_select = "";

    public function select(string|array $cols): Schema
    {
        if(is_string($cols)) $this->_select = $cols;
        if(is_array($cols)) $this->_select = implode(",", $cols);
        return $this;
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
        $this->_where .= (strlen($tmp) > 0 ? ($this->_where == null ? "WHERE" : " AND")." ({$tmp})" : "");
        return $this;
    }

    private ?string $_limit = "";

    function limit(int $limit): Schema
    {
        $this->_limit = "FETCH NEXT {$limit} ROWS ONLY";
        return $this;
    }

    private ?string $_offset = "OFFSET 0 ROWS";

    function offset(int $offset): Schema
    {
        $this->_offset = "OFFSET {$offset} ROWS";
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
                $this->_order .= $order. ' '.$direction;
            } else {
                $this->_order .= $order;
            }
        }

        return $this;
    }

    // private ?string $_group = "";

    // function groupBy(mixed $group): Schema {
    //     $str = "";
    //     if(is_string($group)) $str = $group;
    //     if(is_array($group)) $str = implode(", ", $group);
    //     $this->_group .= (strlen($this->_group) > 0) ? ", ".$str : "GROUP BY ".$str; 
    //     return $this;   
    // }

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

    private function reset() {
        $this->_select = '';
        $this->_where = '';
        $this->_order = '';
        $this->_offset = "OFFSET 0 ROWS";
        $this->_limit = '';
        $this->_join = '';
        $this->_group = '';
    }

    function getSql(string $table): string {
        $select = "SELECT *";
        if(strlen($this->_select) > 0) $select = "SELECT {$this->_select}";

        $from = " FROM {$table}";
        $where = $this->_where;
        if(strlen($where) > 0) $where = " ".$where;

        $order = $this->_order;
        if(strlen($order) > 0) {
            $order = " ".$order;
            if(strlen($this->_offset) > 0) $order .= " ".$this->_offset;
            if(strlen($this->_limit) > 0) $order .= " ".$this->_limit;
        }

        $join = $this->_join;
        $group = $this->_group;
        $query = implode(" ", [$select, $from, $join, $where, $group, $order]);
        $this->reset();
        return $query;
    }

    function get(string $table): Result
    {
        // $res = sqlsrv_query($this->instance, $this->getSql($table), params: [], options: ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);
        // return new SQLSrvResult($res);
        return $this->query($this->getSql($table));
    }

    public function create(string $table, array $columns): Result | bool{
        $sql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE ID = object_id(N'{$table}') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)\n";
        $sql .= "CREATE TABLE {$table} (";
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

    public function drop(string $table): Result|bool
    {
        $sql = "IF EXISTS (SELECT * FROM sysobjects WHERE ID = object_id(N'{$table}') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)\n";
        $sql .= "DROP TABLE {$table};";
        $this->reset();
        return $this->query($sql);
    }

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

    private ?string $_group = "";

    function groupBy(mixed $group): Schema {
        $str = "GROUP BY ";
        if(is_string($group)) $str .= $group;
        if(is_array($group)) $str .= implode(",", $group);
        (strlen($this->_group) > 0) ? $this->_group .= $str : $this->_group = $str;
        return $this;
    }

    // private ?string $_join = "";

    // function join(string $tbl, string $cond): Schema {
    //     $this->_join .= (strlen($this->_join) > 0 ? " " : "")."JOIN {$tbl} ON {$cond}";
    //     return $this;
    // }

    // function innerJoin(string $tbl, string $cond): Schema {
    //     $this->_join .= (strlen($this->_join) > 0 ? " " : "")."INNER JOIN {$tbl} ON {$cond}";
    //     return $this;
    // }

    // function leftJoin(string $tbl, string $cond): Schema {
    //     $this->_join .= (strlen($this->_join) > 0 ? " " : "")."LEFT JOIN {$tbl} ON {$cond}";
    //     return $this;
    // }

    function startTransaction(): bool {
        return $this->query("BEGIN TRANSACTION");
    }

    function commit(): bool {
        return $this->query("COMMIT");
    }

    function rollback(): bool {
        return $this->query("ROLLBACK");
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

    // ALTER TABLE
    function resetAltertable() {
        $this->_modifyColumn = "";
        $this->_addColumn = "";
        $this->_dropColumn = "";
        $this->_dropPrimary = "";
        $this->_addPrimary = "";
    }

    function getNamePrimaryKey(string $table){
        return $this->query("SELECT name FROM sys.key_constraints WHERE type = 'PK' AND OBJECT_NAME(parent_object_id) = N'{$table}';")->row();
    }

    function alter(string $table): Result | bool {
        $alter = "ALTER TABLE {$table}";
        $modifyColumn = $this->_modifyColumn;
        $addColumn = $this->_addColumn;
        $dropColumn = $this->_dropColumn;
        if(strlen($this->_dropPrimary > 0)) {
            $primaryName = $this->getNamePrimaryKey($table)->name;
            $dropPrimary = implode(" ", [$this->_dropPrimary, $primaryName]);
        }

        $addPrimary = $this->_addPrimary;
        $sql = implode(" ", [$alter, $modifyColumn, $addColumn, $dropColumn, $dropPrimary, $addPrimary]);
        $this->resetAltertable();
        return $this->query($sql);
    }
    private ?string $_modifyColumn = "";

    function modifyColumn(string $column, string $type): Schema {
        $this->_modifyColumn = "ALTER COLUMN {$column} {$type}";
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
        $this->_dropPrimary = "DROP CONSTRAINT";
        return $this;
    }

    private ?string $_addPrimary = "";

    function addPrimary(string $column, string $primary_name): Schema {
        $this->_addPrimary = "ADD CONSTRAINT {$primary_name} PRIMARY KEY CLUSTERED ({$column})";
        return $this;
    }

    function createIndex(string $table, string $index_name, array $cols): Result|bool {
        $column = implode(",", $cols);
        $sql = "CREATE CLUSTERED INDEX {$index_name} ON {$table} ({$column});";
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