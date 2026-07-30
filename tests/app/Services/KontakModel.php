<?php 

namespace Selvi\Tests\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;

class KontakModel {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function count(array $where = [], array $orWhere = []) : int {
        $config = $this->db->getConfig();
        $driver = $config['driver'];
        return $this->db->select(($driver == 'mysql' ? 'IFNULL' : 'ISNULL').'(COUNT(kontak.idKontak), 0) AS jmlKontak')
            ->innerJoin('grup', 'grup.idGrup = kontak.idGrup')
            ->where($where)->orWhere($orWhere)
            ->get('kontak')->row()->jmlKontak;
    }

    function result(array $where = [], array $orWhere = [], array $order = [], int $offset = 0, int $limit = -1) : array {
        return $this->db->select([
            'kontak.idKontak',
            'kontak.nmKontak',
            'grup.nmGrup'
        ])
        ->where($where)->orWhere($orWhere)
        ->offset($offset)->limit($limit)->order($order)
        ->innerJoin('grup', 'grup.idGrup = kontak.idGrup')
        ->get('kontak')->result();
    }

    function row(array $where) {
        return $this->db->select([
            'kontak.idKontak',
            'kontak.nmKontak',
            'grup.nmGrup'
        ])
        ->innerJoin('grup','grup.idGrup = kontak.idGrup')
        ->where($where)->get("kontak")->row();
    }

    function insert(array $data): bool | int {
        if($this->db->insert('kontak', $data)) {
            return $this->db->lastId();
        }
        return false;
    }

    function update(array $where, array $data): bool {
        return $this->db->where($where)->update("kontak", $data);
    }

    function delete (array $where): bool {
        return $this->db->where($where)->delete("kontak");
    }

}