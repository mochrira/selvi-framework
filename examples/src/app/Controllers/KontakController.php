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

    function row(String $id) {
        $data = $this->Kontak->row([['kontak.idKontak',$id]]);
        return \jsonResponse($data, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        if($this->Kontak->insert($data) === true)
            return response(null, 201);
    }

    function update(Request $request, String $id) {
        $data = json_decode($request->raw(), true);
        $this->Kontak->update([['kontak.idKontak' , $id]], $data);
        return \jsonResponse(null, 200);
    }

    function delete(String $id) {
        $this->Kontak->delete([['kontak.idKontak', $id]]);
        return \jsonResponse(null, 200);
    }


}