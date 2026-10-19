<?php 

namespace Selvi\Tests\Controllers;

use DateTime;
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\Builder\DML\WhereBuilder;
use Selvi\Database\Builder\DML\WithBuilder;
use Selvi\Database\DB;
use Selvi\Exception;
use Selvi\Input\Request;
use Selvi\Tests\Models\Kontak;

class KontakController {

    function __construct(
        private Request $request
    ) { }

    function result() {
        /** Mode direct to driver */
        // $query = DB::connection('main')->table('kontak');

        // $search = $this->request->get('search') ?? null;
        // if($search != null) {
        //     $query->where([['kontak.nm_kontak', 'LIKE', "%{$search}%"]]);
        // }

        // $order = $this->request->get('order') ?? null;
        // if($order != null) {
        //     foreach(explode(',', $order) as $str) {
        //         $clause = explode(':', $str);
        //         $query->order($clause[0], $clause[1]);
        //     }
        // }
        
        // $limit = $this->request->get('limit') ?? 30;
        // $offset = $this->request->get('offset') ?? 0;
        // $query->limit($limit)->offset($offset);

        // $data = $query->get()->rows();
        // return jsonResponse($data);

        /** Mode pake model */
        $filter = function (QueryBuilder $query) {
            $idGrup = $this->request->get('idGrup');
            if ($idGrup !== null && $idGrup !== '') {
                $query->where([
                    ['kontak.id_grup', '=', (int)$idGrup]
                ]);
            }

            $search = $this->request->get('search');
            if ($search !== null && $search !== '') {
                $query->where(function (WhereBuilder $builder) use ($search) {
                    $builder->orWhere([
                        ['kontak.nm_kontak', 'LIKE', '%'.$search.'%']
                    ]);
                });
            }
        };

        $count = Kontak::count($filter);
        $data = Kontak::with('departemen')
            ->with('unit', fn(WithBuilder $builder) => $builder
                ->with('departemen'))
            ->with('profesi', fn(WithBuilder $builder) => $builder
                ->with('unit', fn(WithBuilder $builder) => $builder
                    ->with('departemen'))
            )->all(function (QueryBuilder $query) use ($filter) {
            $filter($query);

            $limit = $this->request->get('limit') ?? 30;
            if ($limit !== null && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            $offset = $this->request->get('offset') ?? 0;
            if ($offset !== null && is_numeric($offset)) {
                $query->offset((int)$offset);
            }

        });

        return jsonResponse([
            'count' => $count,
            'data' => $data->toArray()
        ]);
    }

    function row(string $idKontak) {
        $data = Kontak::find((int)$idKontak);
        if($data == null) throw new Exception("Kontak tidak ditemukan", "kontak/not-found", 404);
        // $data = DB::table('kontak')->where([['id_kontak', $idKontak]])->get()->row();
        return \jsonResponse($data->toArray(), 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        // $idKontak = DB::table('kontak')->insert([
        //     'nm_kontak' => $data['nmKontak'],
        //     'id_departemen' => $data['idDepartemen'],
        //     'id_unit' => $data['idUnit'],
        //     'id_profesi' => $data['idProfesi'],
        //     'created_at' => new DateTime()
        // ]);
        // return \jsonResponse(['idKontak' => $idKontak], 201);

        $kontak = Kontak::create([
            'nm_kontak' => $data['nmKontak'],
            'id_departemen' => $data['idDepartemen'],
            'id_unit' => $data['idUnit'],
            'id_profesi' => $data['idProfesi'],
            'created_at' => new DateTime()
        ]);
        return \jsonResponse(['idKontak' => $kontak->idKontak], 201);
    }

    function update(string $idKontak) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];

        // DB::table('kontak')->where([['id_kontak', $idKontak]])->update([
        //     'nm_kontak' => $data['nmKontak'],
        //     'id_departemen' => $data['idDepartemen'],
        //     'id_unit' => $data['idUnit'],
        //     'id_profesi' => $data['idProfesi']
        // ]);
        // return \jsonResponse(null, 204);
        
        $kontak = Kontak::find((int)$idKontak);
        if ($kontak === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);
        $kontak->update([
            'nm_kontak' => $data['nmKontak'],
            'id_departemen' => $data['idDepartemen'],
            'id_unit' => $data['idUnit'],
            'id_profesi' => $data['idProfesi']
        ]);
        return \jsonResponse(null, 204);
    }

    function delete(string $idKontak) {
        // DB::table('kontak')->where([['id_kontak', $idKontak]])->delete();
        $kontak = Kontak::find((int)$idKontak);
        if ($kontak === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);
        $kontak->delete();
        return \jsonResponse(null, 204);
    }

}