<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Table;
use Selvi\Database\Builder\WhereBuilder;
use Selvi\DB;
use Selvi\Tests\Models\KontakModel;
use Selvi\Input\Request;
use Selvi\Exception;
use Selvi\Tests\Models\Kontak;

class KontakController {

    private Request $request;
    private KontakModel $kontakModel;

    function __construct() {
        $this->request = inject(Request::class);
        $this->kontakModel = inject(KontakModel::class);
     }

    function result() {
        // $result = DB::table('kontak')
        // ->where('kontak.idKontak = 1')
        // ->where([
        //     'kontak.idKontak = 1',
        //     ['kontak.nmKontak', '!=', 'Moch. Rizal Rachmadani'],
        //     ['kontak.nmKontak', 'IS', NULL]
        // ])
        // ->where(function(WhereBuilder $builder) {
        //     $builder->orWhere([
        //         ['kontak.nmKontak', 'LIKE', '%Rizal%'],
        //         ['kontak.nmKontak', 'LIKE', '%Selvi%']
        //     ]);
        // })
        // ->get()->result();

        // var_dump($result);
        // die();

        $data = DB::table('kontak')
            ->where('kontak.idKontak = 1')
            ->where(function (WhereBuilder $builder) {
                $builder->orWhere([
                    ['kontak.nmKontak', 'LIKE', '%Rizal%'],
                    ['kontak.nmKontak', 'LIKE', '%Selvi%']
                ]);
            })->get()->result();
        var_dump($data);
        die();
        // $where = [];
        // $orWhere = [];
        // $order = [];

        // $idGrup = $this->request->get('idGrup');
        // if($idGrup != null) {
        //     $where[] = ['grup.idGrup', $idGrup];
        // }

        // $search = $this->request->get('search');
        // if($search != null) {
        //     $orWhere[] = ['kontak.nmKontak', 'LIKE', '%'.$search.'%']; 
        // }

        // $sort = $this->request->get('order');
        // if($sort != null) {
        //     foreach(explode(',', $sort) as $val) {
        //         list($field, $direction) = explode(':', $val);
        //         $order[$field] = $direction;
        //     }
        // }

        // $offset = $this->request->get('offset') ?? 0;
        // $limit = $this->request->get('limit') ?? -1;

        // $data = $this->kontakModel->result($where, $orWhere, $order, $offset, $limit);
        // $count = $this->kontakModel->count($where, $orWhere);

        // if ($count === 0) {
        //     throw new Exception('Data tidak ditemukan', 'data/not-found', 404);
        // }

        // return \jsonResponse([
        //     'data' => $data,
        //     'count' => $count
        // ], 200);
    }

    function row(String $id) {
        $data = $this->kontakModel->row([['kontak.idKontak',$id]]);
        if ($data === null) {
            throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);
        }
        return \jsonResponse((array)$data, 200);
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