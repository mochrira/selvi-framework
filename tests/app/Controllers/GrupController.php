<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Database\Builder\DML\WhereBuilder;
use Selvi\Database\Manager;
use Selvi\DB;
use Selvi\Exception;
use Selvi\Input\Request;

class GrupController {

    function result(Request $request) {
        $data = DB::table('grup')
            ->select(['grup.idGrup', 'grup.nmGrup'])
            ->where(function(WhereBuilder $builder) use ($request) {
                $search = $request->get('search');
                if($search) {
                    $builder->where([
                        ['grup.nmGrup', 'LIKE', '%'.$search.'%']
                    ]);
                }
            })
            ->get()
            ->result();
        return \jsonResponse($data, 200);
    }

    function row(string $idGrup) {
        $data = DB::table('grup')
            ->where([
                ['grup.idGrup', '=', (int)$idGrup]
            ])
            ->get()
            ->row();

        if ($data === null) {
            throw new Exception('Grup tidak ditemukan', 'data/not-found', 404);
        }

        return \jsonResponse((array)$data, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw() ?? '', true) ?? [];
        $db = Manager::get('main');
        $db->insert('grup', $data);
        $idGrup = $db->lastId();
        return \jsonResponse(['idGrup' => $idGrup], 201);
    }

    function update(Request $request, string $idGrup) {
        $data = json_decode($request->raw() ?? '', true) ?? [];
        $db = Manager::get('main');
        $db->where([['grup.idGrup', (int)$idGrup]])->update('grup', $data);
        return \jsonResponse(null, 204);
    }

    function delete(string $idGrup) {
        $db = Manager::get('main');
        $db->where([['grup.idGrup', (int)$idGrup]])->delete('grup');
        return \jsonResponse(null, 204);
    }

}