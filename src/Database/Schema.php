<?php 

namespace Selvi\Database;
use Selvi\Database\QueryResult;
use Selvi\Exception;
use Selvi\Database\Migration;
use Selvi\Factory;
use Selvi\Cli;

use mysqli;

class Schema {

    private $db;
    private $lastquery;
    private $config;

    function __construct($config) {
        $this->config = $config;
        $this->db = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
        if($this->db->connect_error) {
            Throw new Exception('Gagal membuka koneksi database', 'db/failed-to-connect');
        }
    }

    public function __call($name, $args) {
        if(!method_exists(__NAMESPACE__.'\QueryBuilder', $name)) {
            Throw new Exception('Undefined function '.__CLASS__.'::'.$name, 'db/undefined-function');
        }
        $result = call_user_func(__NAMESPACE__.'\QueryBuilder::'.$name, ...$args);

        if(empty($result)) return $this;
        return $this->query($result['sql'], $result['args']);
    }

    public function getSql($tblName) {
        return \Selvi\Database\QueryBuilder::get($tblName);
    }

    public function getConfig() {
        return $this->config;
    }

    public function getlastid() {
        $row = $this->select('LAST_INSERT_ID() AS lastid')->get()->row();
        return $row->lastid;
    }

    public function query($sql, $args) {
        $this->lastquery = $sql;
        $types = implode("", array_map(function ($v) {
            if(is_numeric($v)) {
                if(is_int($v)) return 'i';
                return 'd';
            }
            return 's';
        }, $args));

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$args);
        $exec_result = $stmt->execute();
        
        if($exec_result === false) {
            $data = null;
            if(isset($this->config['debug']) && ($this->config['debug'] == true)) {
                $data['query'] = $this->lastquery;
            }
            throw new Exception($stmt->error, 'db/query-error', 500, $data);
        }

        $queryResult = $stmt->get_result();
        if($queryResult === false) return $exec_result;
        return new QueryResult($queryResult);
    }

    public function getlastquery() {
        return $this->lastquery;
    }

    public function error() {
        return $this->db->error;
    }

}