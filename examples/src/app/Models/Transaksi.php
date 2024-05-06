<?php

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class Transaksi extends Model {
    private Schema $db;
    
    function __construct(){
        $this->db = Manager::get('mysql');
    } 

    function row(Array $where) {
        return $this->db->where($where)->get("transaksi")->row();
    }

    function Result() {
        return $this->db->get("transaksi")->result();
    }

    function insert(Array $data) {
        $result = $this->db->insert("transaksi", $data);
        if ($result) return $this->db->getLastId();
    }

    function update(Array $where, Array $data){
        return $this->db->where($where)->update("transaksi", $data);
    }

    function delete(Array $where) {
        return $this->db->where($where)->delete("transaksi");
    }
}