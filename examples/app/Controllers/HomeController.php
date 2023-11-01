<?php 

namespace App\Controllers;

use Selvi\Controller;
use Selvi\Database\Manager;

class HomeController extends Controller {

    function __construct() {
        $this->db = Manager::add([
            'host' => 'mariadb.database',
            'username' => 'root',
            'password' => 'RDF?jq8eec',
            'database' => 'test'
        ]);
    }

    function index() {
        $this->db->insert('kontak', [
            'nmKontak' => "Yayasan Al Qur'an",
            'noHp' => 'App\Contact\Quran',
            'penjualan' => 15000.89,
            'tunai' => true
        ]);
        $idKontak = $this->db->getlastid();

        $this->db->where([['kontak.idKontak', 1]]);
        $this->db->update('kontak', [
            'nmKontak' => "Yayasan Al Qur'annul Kariim",
            'noHp' => 'App\Contact\Quran',
            'penjualan' => 15000.89,
            'tunai' => true
        ]);

        $this->db->where([['kontak.idKontak', $idKontak]]);
        $this->db->delete('kontak');

        $this->db->where([['kontak.idKontak', 1]]);
        $query = $this->db->get('kontak');
        var_dump($query->result());
        die();

        
        return jsonResponse();
    }
    
}