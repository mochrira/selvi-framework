<?php 

namespace Selvi\Tests\Controllers;

use DateTime;
use Selvi\Database\DB;
use Selvi\Input\Request;

class GrupController {

    function __construct(
        private Request $request
    ) { }

    function result() {

        $query = DB::connection('main')->table('grup');

        $search = $this->request->get('search') ?? null;
        if($search != null) {
            $query->where([['grup.nm_grup', 'LIKE', "%{$search}%"]]);
        }

        $order = $this->request->get('order') ?? null;
        if($order != null) {
            foreach(explode(',', $order) as $str) {
                $clause = explode(':', $str);
                $query->order($clause[0], $clause[1]);
            }
        }
        
        $limit = $this->request->get('limit') ?? 30;
        $offset = $this->request->get('offset') ?? 0;
        $query->limit($limit)->offset($offset);

        $data = $query->get()->rows();
        return jsonResponse($data);

        // $filter = function (QueryBuilder $query) {
        //     $idGrup = $this->request->get('idGrup');
        //     if ($idGrup !== null && $idGrup !== '') {
        //         $query->where([
        //             ['grup.id_grup', '=', (int)$idGrup]
        //         ]);
        //     }

        //     $search = $this->request->get('search');
        //     if ($search !== null && $search !== '') {
        //         $query->where(function (WhereBuilder $builder) use ($search) {
        //             $builder->orWhere([
        //                 ['grup.nm_grup', 'LIKE', '%'.$search.'%']
        //             ]);
        //         });
        //     }
        // };

        // $count = Kontak::count($filter);
        // $data = Kontak::with('departemen')
        //     ->with('unit', fn($b) => $b
        //         ->with('departemen'))
        //     ->with('profesi', fn($b) => $b
        //         ->with('unit', fn($b) => $b
        //             ->with('departemen'))
        //     )
        // ->all(function (QueryBuilder $query) use ($filter) {
        //     $filter($query);

        //     $limit = $this->request->get('limit') ?? 30;
        //     if ($limit !== null && is_numeric($limit)) {
        //         $query->limit((int)$limit);
        //     }

        //     $offset = $this->request->get('offset') ?? 0;
        //     if ($offset !== null && is_numeric($offset)) {
        //         $query->offset((int)$offset);
        //     }

        //     echo $query->getSql();
        //     echo "\n";
        // });

        // return jsonResponse([
        //     'count' => $count,
        //     'data' => $data->toArray()
        // ]);
    }

    function row(string $idGrup) {
        // $data = Kontak::find((int)$idGrup);
        // if($data == null) throw new Exception("Kontak tidak ditemukan", "grup/not-found", 404);
        $data = DB::table('grup')->where([['id_grup', $idGrup]])->get()->row();
        return \jsonResponse((array)$data, 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $idGrup = DB::table('grup')->insert([
            'nm_grup' => $data['nmKontak'],
            'id_departemen' => $data['idDepartemen'],
            'id_unit' => $data['idUnit'],
            'id_profesi' => $data['idProfesi'],
            'created_at' => new DateTime()
        ]);
        return \jsonResponse(['idGrup' => $idGrup], 201);
    }

    function update(string $idGrup) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        DB::table('grup')->where([['id_grup', $idGrup]])->update([
            'nm_grup' => $data['nmKontak'],
            'id_departemen' => $data['idDepartemen'],
            'id_unit' => $data['idUnit'],
            'id_profesi' => $data['idProfesi']
        ]);
        // $grup = Kontak::find((int)$idGrup);
        // if ($grup === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);

        // $grup->nmKontak = $data['nmKontak'];
        // $grup->idDepartemen = $data['idDepartemen'];
        // $grup->update();
        return \jsonResponse(null, 204);
    }

    function delete(string $idGrup) {
        DB::table('grup')->where([['id_grup', $idGrup]])->delete();
        // $grup = Kontak::find((int)$idGrup);
        // if ($grup === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);

        // $grup->delete();
        return \jsonResponse(null, 204);
    }

}