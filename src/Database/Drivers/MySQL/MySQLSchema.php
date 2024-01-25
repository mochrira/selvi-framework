<?php 

namespace Selvi\Database\Drivers\MySQL;

use mysqli;
use Selvi\Database\Schema;
use Selvi\Database\Result;

class MySQLSchema implements Schema {

    private Array $config;
    private mysqli $instance;

    public function __construct(Array $config)
    {
        $this->config = $config;
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

    public function query(string $sql): Result
    {
        $res = $this->instance->query($sql);
        return new MySQLResult($res);
    }

    public function getSql(string $tbl): string
    {
        return "SELECT * FROM {$tbl}";
    }

    public function get(string $tbl): Result
    {
        $select = "*";
        if($this->_select != null) $select = $this->_select;

        $queryString = "SELECT {$select} FROM {$tbl} {$this->_where} {$this->_order} {$this->_limit}";
        if ($this->_limit !== null && $this->_offset !== null) $queryString .= " {$this->_offset}";
        $res = $this->instance->query($queryString);
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

    private ?string $_order = null;

    function order(string $cols, ?string $param = "ASC"): Schema
    {
        if ($cols !== null)  $this->_order = "ORDER BY {$cols} {$param}";
        return $this;
    }

    private ?string $_limit = null;

    function limit(int $limit = null): Schema
    {
        if ($limit !== null) $this->_limit = "LIMIT {$limit}";
        
        return $this;
    }

    private ?string $_offset = null;

    function offset(int $offset = null) : Schema {
        if ($offset !== null) $this->_offset = "OFFSET {$offset}";
        
        return $this;
    }

}