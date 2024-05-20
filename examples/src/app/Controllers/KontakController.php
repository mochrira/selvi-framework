<?php 

namespace App\Controllers;

use App\Models\Kontak;
use Selvi\Request;

class KontakController {

    function __construct(
        private Kontak $Kontak
    ) { }

    function result(Request $request) {
        $where = [];
        $orWhere = [];
        $order = [];

        $idGrup = $request->get('idGrup');
        if($idGrup != null) {
            $where[] = ['grup.idGrup', $idGrup];
        }

        $search = $request->get('search');
        if($search != null) {
            $orWhere[] = ['kontak.nmKontak', 'LIKE', '%'.$search.'%']; 
        }

        $sort = $request->get('order');
        if($sort != null) {
            foreach(explode(',', $sort) as $val) {
                list($field, $direction) = explode(':', $val);
                $order[$field] = $direction;
            }
        }

        $offset = $request->get('offset') ?? 0;
        $limit = $request->get('limit') ?? -1;

        $data = $this->Kontak->result($where, $orWhere, $order, $offset, $limit);
        $count = $this->Kontak->count($where, $orWhere);
        return \jsonResponse([
            'data' => $data,
            'count' => $count
        ], 200);
    }

    function row(String $id) {
        $data = $this->Kontak->row([['kontak.idKontak',$id]]);
        return \jsonResponse($data, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        $idKontak = $this->Kontak->insert($data);
        return \jsonResponse(['idKontak' => $idKontak], 201);
    }

    function update(Request $request, String $id) {
        $data = json_decode($request->raw(), true);
        $this->Kontak->update([['kontak.idKontak', $id]], $data);
        return \jsonResponse(null, 204);
    }

    function delete(String $id) {
        $this->Kontak->delete([['kontak.idKontak', $id]]);
        return \jsonResponse(null, 204);
    }

}