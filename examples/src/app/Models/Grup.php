<?php 

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class Grup extends Model {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function result() {
        $config = $this->db->getConfig();
        $driver = $config['driver'];

        $a = $this->db->select([
            'grup.idGrup',
            ($driver == 'sqlsrv' ? 'ISNULL' : 'IFNULL').'(COUNT(kontak.idKontak), 0) AS jmlKontak'
        ])
        ->leftJoin('kontak', 'kontak.idGrup = grup.idGrup')
        ->groupBy(['grup.idGrup'])->getSql('grup');

        return $this->db->select([
            'grup.*',
            'a.jmlKontak'
        ])
        ->leftJoin('('.$a.') a','a.idGrup = grup.idGrup')
        ->get("grup")->result();
    }

    function insert(array $data) {
        if($this->db->insert("grup", $data) !== false) {
            return $this->db->lastId();
        }
        return false;
    }

    function row (Array $where){
        $a = $this->db->select([
            'grup.idGrup',
            'COUNT(kontak.idKontak) AS jmlKontak'
        ])
        ->leftJoin('kontak', 'kontak.idGrup = grup.idGrup')
        ->groupBy(['grup.idGrup'])->getSql('grup');

        return $this->db->select([
            'grup.*',
            'a.jmlKontak'
        ])
        ->leftJoin('('.$a.') a','a.idGrup = grup.idGrup')
        ->where($where)->get("grup")->row();
    }

    function update(Array $where, Array $data) {
        return $this->db->where($where)->update("grup", $data);
    }

    function delete (Array $where) {
        return $this->db->where($where)->delete("grup");
    }

}