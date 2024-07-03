<?php

namespace App\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;
use Selvi\Model;

class TransaksiDetail {

    private Schema $db;

    function __construct() {
        $this->db = Manager::get('main');
    }

    function row(array $where) {
        return $this->db->where($where)->get("transaksiDetail")->row();
    }

    function result(){
        return $this->db->get("transaksiDetail")->result();
    }
    
    function insert(array $data) {
        return $this->db->insert("transaksiDetails" , $data);
    }

    function update(array $where, array $data){
        return $this->db->where($where)->update("transaksiDetail", $data);
    }

    function delete(array $where) {
        return $this->db->where($where)->delete("transaksiDetail");
    }
    
}