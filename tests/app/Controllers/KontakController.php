<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Input\Request;
use Selvi\Tests\Models\Kontak;

class KontakController {

    private Request $request;

    function __construct() {
        $this->request = inject(Request::class);
     }

    function result() {
        
    }

    function row(String $id) {
        
    }

    function insert() {
        $data = json_decode($this->request->raw(), true);
        $kontak = new Kontak();
        $kontak->nmKontak = "Halo";
        $kontak->idGrup = 1;
        $kontak->create();
    }

    function update(String $id) {
       
    }

    function delete(String $id) {
        
    }

}