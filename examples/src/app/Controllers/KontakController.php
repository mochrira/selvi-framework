<?php 

namespace App\Controllers;

use App\Models\KontakModel;
use Selvi\Input\Request;

class KontakController {

    private Request $request;
    private KontakModel $kontakModel;

    function __construct() {
        $this->request = inject(Request::class);
        $this->kontakModel = inject(KontakModel::class);
     }

    function result() {
        $where = [];
        $orWhere = [];
        $order = [];

        $idGrup = $this->request->get('idGrup');
        if($idGrup != null) {
            $where[] = ['grup.idGrup', $idGrup];
        }

        $search = $this->request->get('search');
        if($search != null) {
            $orWhere[] = ['kontak.nmKontak', 'LIKE', '%'.$search.'%']; 
        }

        $sort = $this->request->get('order');
        if($sort != null) {
            foreach(explode(',', $sort) as $val) {
                list($field, $direction) = explode(':', $val);
                $order[$field] = $direction;
            }
        }

        $offset = $this->request->get('offset') ?? 0;
        $limit = $this->request->get('limit') ?? -1;

        $data = $this->kontakModel->result($where, $orWhere, $order, $offset, $limit);
        $count = $this->kontakModel->count($where, $orWhere);
        return \jsonResponse([
            'data' => $data,
            'count' => $count
        ], 200);
    }

    function row(String $id) {
        $data = $this->kontakModel->row([['kontak.idKontak',$id]]);
        return \jsonResponse($data, 200);
    }

    function insert() {
        $data = json_decode($this->request->raw(), true);
        $idKontak = $this->kontakModel->insert($data);
        return \jsonResponse(['idKontak' => $idKontak], 201);
    }

    function update(String $id) {
        $data = json_decode($this->request->raw(), true);
        $this->kontakModel->update([['kontak.idKontak', $id]], $data);
        return \jsonResponse(null, 204);
    }

    function delete(String $id) {
        $this->kontakModel->delete([['kontak.idKontak', $id]]);
        return \jsonResponse(null, 204);
    }

}