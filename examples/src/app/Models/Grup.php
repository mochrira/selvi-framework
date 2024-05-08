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
        ->get("grup")->result();
    }

    function insert(array $data) {
        if($this->db->insert("grup", $data) !== false) {
            return $this->db->lastId();
        }
        return false;
    }

    function row (Array $where){
        return $this->db->select([
            'grup.*',
            'COUNT(kontak.idKontak) as jmlKontak'
        ])
        ->innerJoin('kontak','kontak.idGrup = grup.idGrup')
        ->where($where)->get("grup")->row();
    }

    function update(Array $where, Array $data) {
        return $this->db->where($where)->update("grup", $data);
    }

    function delete (Array $where) {
        return $this->db->where($where)->delete("grup");
    }

}