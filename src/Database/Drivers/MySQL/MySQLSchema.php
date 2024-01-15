<?php 

namespace Selvi\Database\Drivers\MySQL;

use mysqli;
use Selvi\Database\Schema;
use Selvi\Database\Result;

class MySQLSchema implements Schema {

    private Array $config;
    private mysqli $instance;
    private ?string $_select = null;
    private ?string $_where = null;

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
        $select = "SELECT " . (is_null($this->_select) ?  "*" : $this->_select);
        $res = $this->instance->query("{$select} FROM {$tbl}");
        return new MySQLResult($res);
    }

    public function select(string | array $cols): Schema
    {
        if (is_string($cols)) $this->_select = $cols;
        if (is_array($cols)) $this->_select = implode(',', $cols); 
        return $this;
    }

    public function where(string $where): Schema
    {
        return $this;
    }

}