<?php 

namespace Selvi\Database\Drivers\SQLSrv;

use Selvi\Database\Drivers\SQLSrv\SQLSrvResult;
use Selvi\Database\Result;
use Selvi\Database\Schema;
use Selvi\Exception\DatabaseException;

class SQLSrvSchema implements Schema {

    private Array | null $config;
    private $instance;
    private ?string $_select = null;
    private ?string $_where = null;
    private ?string $_order = null;
    private ?string $_offset = null;
    private ?string $_limit = null;
    private ?string $_join = null;
    private ?string $_group = null;
    private ?string $_modifyColumn = null;
    private ?string $_addColumn = null;
    private ?string $_dropColumn = null;
    private ?string $_dropPrimary = null;
    private ?string $_addPrimary = null;

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

        sqlsrv_configure('WarningsReturnAsErrors', 0);
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
                $error = $this->error()[0];
                throw new DatabaseException($error['message'], 500, $error['SQLSTATE'], $sql);
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

    public function select(string|array $cols): Schema
    {
        if(is_string($cols)) $this->_select = $cols;
        if(is_array($cols)) $this->_select = implode(", ", $cols);
        return $this;
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
        $this->_where .= (strlen($tmp) > 0 ? ($this->_where == null ? "WHERE" : " AND")." ({$tmp})" : "");
        return $this;
    }

    function limit(int $limit): Schema
    {
        $this->_limit = "FETCH NEXT {$limit} ROWS ONLY";
        return $this;
    }

    function offset(int $offset): Schema
    {
        $this->_offset = "OFFSET {$offset} ROWS";
        return $this;
    }

    function order(string|array $order, ?string $direction = null): Schema
    {
        $tmp = "";
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

        $this->_order .= (strlen($tmp) > 0) ? (strlen($this->_order) > 0 ? ', '.$tmp : 'ORDER BY '.$tmp) : "";
        return $this;
    }

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
        $this->_select = null;
        $this->_where = null;
        $this->_order = null;
        $this->_offset = null;
        $this->_limit = null;
        $this->_join = null;
        $this->_group = null;

        $this->_modifyColumn = null;
        $this->_addColumn = null;
        $this->_dropColumn = null;
        $this->_dropPrimary = null;
        $this->_addPrimary = null;
    }

    public function lastId(): int {
        return $this->select('@@IDENTITY AS lastid')
            ->get()->row()->lastid;
    }

    function getSql(string $table = null): string {
        $select = "SELECT *";
        if(strlen($this->_select) > 0) $select = "SELECT {$this->_select}";

        $from = $table != null ? "FROM {$table}" : "";
        $where = $this->_where;

        $order = $this->_order;
        if(strlen($order) > 0) {
            if(strlen($this->_offset) > 0) $order .= " ".$this->_offset;
            if(strlen($this->_limit) > 0) $order .= " ".$this->_limit;
        }

        $join = $this->_join;
        $group = $this->_group;
        $query = implode(" ", array_filter([$select, $from, $join, $where, $group, $order], function ($v) {
            return strlen($v) > 0;
        }));
        $this->reset();
        return $query;
    }

    function get(string $table = null): Result
    {
        $sql = $this->getSql($table);
        return $this->query($sql);
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
        $result = $this->query($sql);
        if($result !== false) return true;
        return false;
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

    function groupBy(mixed $group): Schema {
        $str = "GROUP BY ";
        if(is_string($group)) $str .= $group;
        if(is_array($group)) $str .= implode(",", $group);
        (strlen($this->_group) > 0) ? $this->_group .= $str : $this->_group = $str;
        return $this;
    }

    function startTransaction(): bool {
        return sqlsrv_begin_transaction($this->instance);
    }

    function commit(): bool {
        return sqlsrv_commit($this->instance);
    }

    function rollback(): bool {
        return sqlsrv_rollback($this->instance);
    }

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
        $this->_where .= (strlen($tmp) > 0 ? ($this->_where == null ? "WHERE" : " AND")." ({$tmp})" : "");
        return $this;
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
        $this->reset();
        return $this->query($sql);
    }

    function modifyColumn(string $column, string $type): Schema {
        $this->_modifyColumn = "ALTER COLUMN {$column} {$type}";
        return $this;
    }


    function addColumn(string $column, string $type): Schema {
        $this->_addColumn = "ADD {$column} {$type}";
        return $this;
    }


    function dropColumn(string $column): Schema {
        $this->_dropColumn = "DROP COLUMN {$column}";
        return $this;
    }

    function dropPrimary(): Schema {
        $this->_dropPrimary = "DROP CONSTRAINT";
        return $this;
    }

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

    function rename(string $table, string $new_table): Result | bool {
        return $this->query('sp_rename '.$table.', '.$new_table);
    }

}