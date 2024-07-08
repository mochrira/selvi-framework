<?php 

namespace App\Controllers;

use App\Models\GrupModel;
use Selvi\Request;

class GrupController {

    function __construct(
        private GrupModel $GrupModel
    ) {}

    function result() {
        $data = $this->GrupModel->result();
        return \jsonResponse($data, 200);
    }

    function row(string $id) {
        $data = $this->GrupModel->row([['grup.idGrup',$id]]);
        return \jsonResponse($data, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        $idGrup = $this->GrupModel->insert($data);
        return \jsonResponse(['idGrup' => $idGrup], 201);
    }

    function update(Request $request, string $id) {
        $data = json_decode($request->raw(), true);
        $this->GrupModel->update([['grup.idGrup' , $id]], $data);
        return \jsonResponse(null, 204);
    }

    function delete(string $id) {
        $this->GrupModel->delete([['grup.idGrup', $id]]);
        return \jsonResponse(null, 204);
    }

}