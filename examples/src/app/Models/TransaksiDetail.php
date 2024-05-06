<?php

namespace App\Models;

use PO;
use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class TransaksiDetail extends Model {
    private Schema $db;

    function __construct() {
        $this->db = Manager::get('mysql');
    }

    function row(Array $where) {
        return $this->db->where($where)->get("transaksiDetail")->row();
    }

    function result(){
        return $this->db->get("transaksiDetail")->result();
    }
    
    function insert(Array $data) {
        return $this->db->insert("transaksiDetail" , $data);
    }

    function update(Array $where, Array $data){
        return $this->db->where($where)->update("transaksiDetail", $data);
    }

    function delete(Array $where) {
        return $this->db->where($where)->delete("transaksiDetail");
    }
    
}