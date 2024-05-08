<?php 

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class Produk extends Model {
    
    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function row(Array $where) {
        return $this->db->where($where)->get("produk")->row();
    }

    function result() {
        return $this->db->get("produk")->result();
    }

    function insert(Array $data) {
        if($this->db->insert("produk", $data) !== false) {
            return $this->db->lastId();
        }
        return false;
    }

    function update(Array $where, Array $data) {
        return $this->db->where($where)->update("produk",$data);
    }

    function delete(Array $where){
        return $this->db->where($where)->delete("produk");    
    }
}