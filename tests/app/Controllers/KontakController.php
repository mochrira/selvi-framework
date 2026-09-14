<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Database\Builder\WhereBuilder;
use Selvi\Database\Manager;
use Selvi\DB;
use Selvi\Exception;
use Selvi\Input\Request;

class KontakController {

    function __construct(
        private Request $request
    ) { }

    function result() {
        $query = DB::table('kontak')
            ->select(['kontak.idKontak', 'kontak.nmKontak', 'kontak.idGrup']);

        // 1. Raw string condition
        $query->where('kontak.idKontak > 0');

        // 2. Condition NULL (IS NOT NULL)
        $query->where([
            ['kontak.nmKontak', 'IS NOT', null]
        ]);

        // 3. Condition array [column, operator, value] jika ada idGrup
        $idGrup = $this->request->get('idGrup');
        if ($idGrup !== null && $idGrup !== '') {
            $query->where([
                ['kontak.idGrup', '=', (int)$idGrup]
            ]);
        }

        // 4. Nested condition dengan WhereBuilder dan orWhere
        $search = $this->request->get('search');
        if ($search !== null && $search !== '') {
            $query->where(function (WhereBuilder $builder) use ($search) {
                $builder->orWhere([
                    ['kontak.nmKontak', 'LIKE', '%'.$search.'%']
                ]);
            });
        }

        $data = $query->get()->result();
        return \jsonResponse($data, 200);
    }

    function row(string $idKontak) {
        $data = DB::table('kontak')
            ->where([
                ['kontak.idKontak', '=', (int)$idKontak]
            ])
            ->get()
            ->row();

        if ($data === null) {
            throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);
        }

        return \jsonResponse((array)$data, 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $db = Manager::get('main');
        $db->insert('kontak', $data);
        $idKontak = $db->lastId();
        return \jsonResponse(['idKontak' => $idKontak], 201);
    }

    function update(string $idKontak) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $db = Manager::get('main');
        $db->where([['kontak.idKontak', (int)$idKontak]])->update('kontak', $data);
        return \jsonResponse(null, 204);
    }

    function delete(string $idKontak) {
        $db = Manager::get('main');
        $db->where([['kontak.idKontak', (int)$idKontak]])->delete('kontak');
        return \jsonResponse(null, 204);
    }

}