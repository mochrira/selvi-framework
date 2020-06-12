<?php

namespace Selvi;
use Selvi\Controller;
use Selvi\Database\Manager as Database;

class Model extends Controller {

    protected $db;
    protected $schema = 'default';
    protected $table = '';
    protected $primary = 'id';
    protected $increment = false;
    protected $selectable = null;
    protected $join = [];

    function __construct() {
        $this->db = Database::get($this->schema);
    }

    function getPrimary() {
        return $this->primary;
    }

    function result($filter = []) {
        return $this->db->where($filter)->select($this->selectable)->join($this->join)->get($this->table)->result();
    }

    function row($filter = []) {
        return $this->db->where($filter)->select($this->selectable)->join($this->join)->get($this->table)->row();
    }

    function insert($data) {
        if($this->db->insert($this->table, $data)) {
            if($this->increment) {
                return $this->db->getlastid();
            }
            return $data[$this->primary];
        }
        return false;
    }

    function update($filter, $data) {
        return $this->db->where($filter)->update($this->table, $data);
    }

    function delete($filter) {
        return $this->db->where($filter)->delete($this->table);
    }

}