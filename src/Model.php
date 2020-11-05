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
    protected $searchable = [];
    protected $join = [];
    protected $group = [];

    function __construct() {
        $this->db = Database::get($this->schema);
    }

    function getSchema() {
        return $this->db;
    }

    function getTable() {
        return $this->table;
    }

    function getPrimary() {
        return $this->primary;
    }

    function buildWhere($where) {
        foreach($where as $index => $w) {
            if(!strpos($w[0], '.')) {
                $where[$index][0] = $this->getTable().'.'.$where[$index][0];
            }
        }
        return $where;
    }

    function buildSearchable($q = null) {
        if($q !== null) {
            $where = $this->searchable;
            foreach($this->searchable as $index => $field) {
                if(!strpos($field, '.')) {
                    $where[] = [$this->getTable().'.'.$field, 'LIKE', '%'.$q.'%'];
                } else {
                    $where[] = [$field, 'LIKE', '%'.$q.'%'];
                }
            }
            return $where;
        } else {
            return [];
        }
    }

    function buildSort($order) {
        $sort = [];
        foreach($order as $k => $v) {
            if(!strpos($k, '.')) {
                $sort[$this->table.'.'.$k] = $v;
            } else {
                $sort[$k] = $v;
            }
        }
        return $sort;
    }

    function count($filter = [], $q = null) {
        $query = $this->db->where($this->buildWhere($filter))->orWhere($this->buildSearchable($q))
            ->select('COUNT('.$this->table.'.'.$this->primary.') AS jumlah')->join($this->join)->groupBy($this->group);
        $row = $query->get($this->table)->row();
        return $row->jumlah;
    }

    function result($filter = [], $q = null, $order = [], $limit = -1, $offset = 0) {
        $query = $this->db->where($this->buildWhere($filter))->orWhere($this->buildSearchable($q))
            ->select($this->selectable)->join($this->join)->order($this->buildSort($order))->groupBy($this->group);
        if($limit > -1) {
            $query->limit($limit)->offset($offset);
        }
        return $query->get($this->table)->result();
    }

    function row($filter = []) {
        return $this->db->where($this->buildWhere($filter))
            ->select($this->selectable)->join($this->join)->groupBy($this->group)->get($this->table)
            ->row();
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
        return $this->db->join($this->join)->where($this->buildWhere($filter))->delete($this->table);
    }

}