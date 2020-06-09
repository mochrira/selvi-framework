<?php 

namespace Selvi\Database;
use Selvi\Database\QueryResult;
use Selvi\Exception;
use Selvi\Database\Migration;
use Selvi\Factory;

use mysqli;

class Schema {

    private $db;    
    private $lastquery;
    private $config;
    private $migration_paths = [];

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
        $result = call_user_func_array([__NAMESPACE__.'\QueryBuilder', $name], $args);
        if(empty($result)) {
            return $this;
        }
        return $this->query($result);
    }

    public function getConfig() {
        return $this->config;
    }

    public function getlastid() {
        $row = $this->select('LAST_INSERT_ID() AS lastid')->get()->row();
        return $row->lastid;
    }

    public function query($sql) {
        $this->lastquery = $sql;
        $query = $this->db->query($sql);
        if(is_bool($query)) {
            if($query === false) {
                throw new Exception($this->error(), 'db/query-error');
            }
            return $query;
        }
        return new QueryResult($query);
    }

    public function getlastquery() {
        return $this->lastquery;
    }

    public function error() {
        return $this->db->error;
    }

    public function addMigration($path) {
        if(!$this->migration_paths[$path]) {
            $this->migration_paths[$path];
        }
    }

    public function getMigrationPath() {
        return $this->migration_paths;
    }

}