<?php 

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class Grup extends Model {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('mysql');
    }

    function result() {
        return $this->db->select([
        'grup.*',
        'IFNULL(COUNT(kontak.idKontak), 0) AS jmlKontak'
        ])
        ->leftJoin('kontak','kontak.idGrup = grup.idGrup')
        ->groupBy("grup.nmGrup")
        ->get("grup")->result();
    }

    function insert(array $data) {
        return $this->db->insert('grup', $data);
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