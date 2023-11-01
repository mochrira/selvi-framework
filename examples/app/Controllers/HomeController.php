<?php 

namespace App\Controllers;

use Selvi\Exception;
use Selvi\Database\Manager;

class HomeController {

    function __construct() {
        Manager::add(json_decode(file_get_contents(BASEPATH.'/private/.DBCONFIG'), true), 'main');
        $this->db = Manager::get('main');
    }

    function index() {
        // $this->db->insert('kontak', [
        //     'nmKontak' => "Qur\"an",
        //     'noHp' => "View\Update",
        //     'penjualan' => 15.84,
        //     'tunai' => false
        // ]);

        $query = $this->db->where([['kontak.nmKontak', 'LIKE', "%Qur\"an%"]])->get('kontak');
        return jsonResponse($query->result());
    }

}