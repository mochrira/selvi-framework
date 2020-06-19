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

    function getTable() {
        return $this->table;
    }

    function getPrimary() {
        return $this->primary;
    }

    function buildWhere($where) {
        if(count($this->join)>0) {
            foreach($where as $index => $w) {
                if(!strpos($w[0], '.')) {
                    $where[$index][0] = $this->getTable().'.'.$where[$index][0];
                }
            }
        }
        return $where;
    }

    function result($filter = []) {
        return $this->db->where($this->buildWhere($filter))->select($this->selectable)->join($this->join)->get($this->table)->result();
    }

    function row($filter = []) {
        return $this->db->where($this->buildWhere($filter))->select($this->selectable)->join($this->join)->get($this->table)->row();
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
        return $this->db->where($this->buildWhere($filter))->update($this->table, $data);
    }

    function delete($filter) {
        return $this->db->where($this->buildWhere($filter))->delete($this->table);
    }

}