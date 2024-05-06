<?php 

namespace App\Controllers;

use App\Models\Kontak;
use Selvi\Request;

class KontakController {

    function __construct(
        private Kontak $Kontak
    ) { }

    function result() {
        $data = $this->Kontak->result();
        return \jsonResponse($data, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        if($this->Kontak->insert($data) === true)
            return response(null, 201);
    }

}