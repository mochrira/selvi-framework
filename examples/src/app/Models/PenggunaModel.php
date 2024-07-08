<?php 

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;

class PenggunaModel {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function count($where = [], $orWhere = []) {
        return $this->db->select('COUNT(pengguna.idPengguna) as jmlPengguna')
            ->where($where)->orWhere($orWhere)
            ->get('pengguna')->row()->jmlPengguna;
    }

    function result($where = [], $orWhere = [], $order = [], $limit = -1, $offset = 0) {
        if($limit > -1) {
            $this->db->limit($limit)->offset($offset);
        }
        return $this->db->where($where)->orWhere($orWhere)
            ->order($order)->get('pengguna')->result();
    }

    function row($where) {
        return $this->db->where($where)->get('pengguna')->row();
    }

    function insert($data) {
        if($this->db->insert('pengguna', $data)) {
            return $this->db->lastId();
        }
        return false;
    }

    function update($where, $data) {
        return $this->db->where($where)->update('pengguna', $data);
    }

    function delete($where) {
        return $this->db->where($where)->delete('pengguna');
    }

}