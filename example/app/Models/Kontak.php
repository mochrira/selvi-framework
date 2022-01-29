<?php 


namespace App\Models;
use Selvi\Database\Manager;

class Kontak {

    private $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function count($where = [], $orWhere = []) {
        return $this->db->select('COUNT(kontak.idKontak) as jmlKontak')
            ->where($where)->orWhere($orWhere)
            ->get('kontak')->row()->jmlKontak;
    }

    function result($where = [], $orWhere = [], $order = [], $limit = -1, $offset = 0) {
        $tmp = [];
        $i = 2022; $j = 7;
        while($i <= 2023 && (($i < 2023 && $j <= 12) || ($i == 2023 && $j <= 6))) {
            $tmp[] = '(SELECT '.$j.' AS bulan, '.$i.' AS tahun)';
            if($j == 12) { $j = 1; $i++; } else { $j++; }
        }
        $queryBulan = join(' UNION ', $tmp);

        if($limit > -1) {
            $this->db->limit($limit)->offset($offset);
        }
        return $this->db->where($where)->orWhere($orWhere)
            ->join('('.$queryBulan.') queryBulan')
            ->order($order)->get('kontak')->result();
    }

    function row($where) {
        return $this->db->where($where)->get('kontak')->row();
    }

    function insert($data) {
        $insert = $this->db->insert('kontak', $data);
        if($insert === true) {
            return $this->db->getlastid();
        }
        return false;
    }

    function update($where, $data) {
        return $this->db->where($where)->update('kontak', $data);
    }

    function delete($where) {
        return $this->db->where($where)->delete('kontak');
    }

}