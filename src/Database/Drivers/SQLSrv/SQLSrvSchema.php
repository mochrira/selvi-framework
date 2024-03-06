<?php 

namespace Selvi\Database\Drivers\SQLSrv;

use Exception;
use Selvi\Database\Drivers\SQLSrv\SQLSrvResult;
use Selvi\Database\Result;
use Selvi\Database\Schema;

class SQLSrvSchema implements Schema {

    private Array $config;
    private $instance;

    function __construct(Array $config)
    {
        $this->config = $config;
        $this->connect();
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

    public function prepareMigrationTables(): Result | bool
    {
        return $this->create('_migration', [
            'id' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'filename' => 'VARCHAR(150) NOT NULL',
            'direction' => 'VARCHAR(15) NOT NULL',
            'start' => 'INT NOT NULL',
            'finish' => 'INT NOT NULL',
            'output' => 'TEXT NOT NULL'
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
                throw new Exception($error[0]['message']);
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

    private ?string $_where = null;

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

    private ?string $_order = null;

    function order(string|array $cols, ?string $param = "ASC"): Schema
    {
        if($this->_order == null) $this->_order = '';
        $this->_order .= strlen($this->_order) > 0 ? ', ' : 'ORDER BY ';
        if(is_array($cols)) {
            if(array_is_list($cols)) {
                $this->_order .= implode(', ', $cols, array_keys($cols));
            } else {
                $this->_order .= implode(', ', array_map(function ($field) use ($cols) {
                    return $field.' '.$cols[$field];
                }, array_keys($cols)));
            }
        }
        if(is_string($cols)) {
            $this->_order .= $cols;
        }
        return $this;
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

        return "{$select}{$from}{$where}{$order}";
    }

    function get(string $table): Result
    {
        $res = sqlsrv_query($this->instance, $this->getSql($table), params: [], options: ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);
        return new SQLSrvResult($res);
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
        
        return $this->query($sql);
    }

    public function drop(string $table): Result|bool
    {
        $sql = "IF EXISTS (SELECT * FROM sysobjects WHERE ID = object_id(N'{$table}') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)\n";
        $sql .= "DROP TABLE {$table};";
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
        return $this->query($sql);
    }

}