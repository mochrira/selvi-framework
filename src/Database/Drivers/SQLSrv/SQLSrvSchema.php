<?php 

namespace Selvi\Database\Drivers\SQLSrv;

use Selvi\Database\Drivers\SQLSrv\SQLSrvResult;
use Selvi\Database\Result;
use Selvi\Database\Schema;

class SQLSrvSchema implements Schema {

    private Array $config;
    private $instance;

    function __construct(Array $config)
    {
        $this->config = $config;
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

    function getSql(string $tbl): string
    {
        return "SELECT * FROM {$tbl}";
    }

    function query(string $sql): SQLSrvResult
    {
        $res = sqlsrv_query($this->instance, $sql, null, ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);
        return new SQLSrvResult($res);
    }

    private ?string $_select = null;

    function get(string $tbl): Result
    {
        $select = "*";
        if($this->_select != null) $select = $this->_select;
        $res = sqlsrv_query($this->instance,"SELECT {$this->_limit} {$select} FROM {$tbl} {$this->_where}", params: [], options: ['Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED]);
        return new SQLSrvResult($res);
    }

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
            $val = str_replace("\'", "\\\'", $val); // replace double quotes Qur"an => Qur\"an
            $val = "\'".$val."\'"; // add double quotes before and after "Qur\'an", "Qur\"an", "View\\Update"
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
    private ?string $_limit = null;

    function order(string $cols, ?string $param = "ASC"): Schema
    {
        return $this;
    }

    function limit(int $limit = null): Schema
    {
        if ($limit !== null) {
            $this->_limit = "TOP {$limit}";
        }
        return $this;
    }

    function offset(int $ofset = null): Schema
    {
        return $this;
    }

}