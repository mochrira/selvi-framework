<?php 

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class Kontak extends Model {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function result($where = [], $orWhere = []) {
        // echo $this->db->select([
        //     'kontak.idKontak',
        //     'kontak.nmKontak',
        //     'grup.nmGrup'
        // ])
        // ->where($where)->orWhere($orWhere)
        // ->innerJoin('grup', 'grup.idGrup = kontak.idGrup')
        // ->getSql('kontak');
        return $this->db->select([
            'kontak.idKontak',
            'kontak.nmKontak',
            'grup.nmGrup'
        ])
        ->where($where)->orWhere($orWhere)
        ->innerJoin('grup', 'grup.idGrup = kontak.idGrup')
        ->get('kontak')->result();
    }

    function row (Array $where){
        return $this->db->select([
            'kontak.idKontak',
            'kontak.nmKontak',
            'grup.nmGrup'
        ])
        ->innerJoin('grup','grup.idGrup = kontak.idGrup')
        ->where($where)->get("kontak")->row();
    }

    function insert(array $data) {
        return $this->db->insert('kontak', $data);
    }

    function update(Array $where, Array $data) {
        return $this->db->where($where)->update("kontak", $data);
    }

    function delete (Array $where) {
        return $this->db->where($where)->delete("kontak");
    }

}