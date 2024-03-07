<?php 

namespace App\Controllers;

use Selvi\Exception;
use Selvi\Database\Manager;

class HomeController {

    // private $db;

    function __construct() {
        // $this->db = Manager::add([
        //     'host' => 'mariadb.database',
        //     'username' => 'root',
        //     'password' => 'RDF?jq8eec',
        //     'database' => 'test'
        // ]);
    }

    function index() {
        // $this->db->where([['kontak.idKontak', 1]]);
        // $this->db->update('kontak', [
        //     'nmKontak' => "Yayasan Al Qur'an",
        //     'noHp' => 'App\Contact\Quran',
        // ]);
        //     'penjualan' => 15000.89
        return response('Halo');
    }

}