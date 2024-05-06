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
        return $this->db->get('grup')->result();
    }

    function insert(array $data) {
        return $this->db->insert('grup', $data);
    }

}