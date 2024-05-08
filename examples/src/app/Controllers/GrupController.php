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
        return \jsonResponse($data, 200);
    }

    function row(String $id) {
        $data = $this->Grup->row([['grup.idGrup',$id]]);
        return \jsonResponse($data, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        $idGrup = $this->Grup->insert($data);
        if($idGrup !== false) return \jsonResponse(['idGrup' => $idGrup], 201);
    }

    function update(Request $request, String $id) {
        $data = json_decode($request->raw(), true);
        $this->Grup->update([['grup.idGrup' , $id]], $data);
        return \jsonResponse(null, 200);
    }

    function delete(String $id) {
        $this->Grup->delete([['grup.idGrup', $id]]);
        return \jsonResponse(null, 200);
    }

}