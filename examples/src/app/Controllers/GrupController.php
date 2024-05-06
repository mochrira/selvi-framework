<?php 

namespace App\Controllers;

use App\Models\Grup;
use Selvi\Request;

class GrupController {

    function __construct(
        private Grup $Grup
    ) {}

    function result() {
        $data = $this->Grup->result();
        return jsonResponse($data, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        if($this->Grup->insert($data) === true) 
            return response(null, 201);
    }

}