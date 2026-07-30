<?php

namespace Selvi\Tests\Models;

use Selvi\Database\Manager;
use Selvi\Database\Schema;

class TransaksiModel {
    
    private Schema $db;
    
    function __construct(){
        $this->db = Manager::get('main');
    } 

    function row(array $where) {
        return $this->db->select([
            'transaksi.*',
            'kontak.nmKontak'
        ])
        ->innerJoin("kontak", 'kontak.idKontak = transaksi.idKontak')
        ->where($where)
        ->get("transaksi")->row();
    }

    function Result() {
        return $this->db->select([
            'transaksi.*',
            'kontak.nmKontak'
        ])
        ->innerJoin("kontak", 'kontak.idKontak = transaksi.idKontak')
        ->get("transaksi")->result();
    }

    function insert(array $data) {
        $result = $this->db->insert("transaksi", $data);
        if ($result) return $this->db->lastId();
    }

    function update(array $where, array $data){
        return $this->db->where($where)->update("transaksi", $data);
    }

    function delete(array $where) {
        return $this->db->where($where)->delete("transaksi");
    }
}