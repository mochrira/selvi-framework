<?php 

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class Kontak extends Model {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('mysql');
    }

    function result() {
        return $this->db->select([
            'kontak.*',
            'grup.nmGrup'
        ])
        ->innerJoin('grup', 'grup.idGrup = kontak.idGrup')
        ->get('kontak')->result();
    }

    function insert(array $data) {
        return $this->db->insert('kontak', $data);
    }

}