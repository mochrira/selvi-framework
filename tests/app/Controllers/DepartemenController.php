<?php 

namespace Selvi\Tests\Controllers;

use DateTime;
use Selvi\Database\DB;
use Selvi\Input\Request;

class DepartemenController {

    function __construct(
        private Request $request
    ) { }

    function result() {
        $query = DB::connection('main')->table('departemen')
            ->leftJoin('kontak', 'kontak.id_departemen = departemen.id_departemen')
            ->select(
                'departemen.id_departemen',
                'departemen.nm_departemen',
                'COUNT(kontak.id_kontak) AS jml_kontak'
            )
            ->group(
                'departemen.id_departemen'
            );

        $search = $this->request->get('search') ?? null;
        if($search != null) {
            $query->where([['departemen.nm_departemen', 'LIKE', "%{$search}%"]]);
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
        //     $idDepartemen = $this->request->get('idDepartemen');
        //     if ($idDepartemen !== null && $idDepartemen !== '') {
        //         $query->where([
        //             ['departemen.id_departemen', '=', (int)$idDepartemen]
        //         ]);
        //     }

        //     $search = $this->request->get('search');
        //     if ($search !== null && $search !== '') {
        //         $query->where(function (WhereBuilder $builder) use ($search) {
        //             $builder->orWhere([
        //                 ['departemen.nm_departemen', 'LIKE', '%'.$search.'%']
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

    function row(string $idDepartemen) {
        // $data = Kontak::find((int)$idDepartemen);
        // if($data == null) throw new Exception("Kontak tidak ditemukan", "departemen/not-found", 404);
        $data = DB::table('departemen')->where([['id_departemen', $idDepartemen]])->get()->row();
        return \jsonResponse((array)$data, 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $idDepartemen = DB::table('departemen')->insert([
            'nm_departemen' => $data['nmKontak'],
            'id_departemen' => $data['idDepartemen'],
            'id_unit' => $data['idUnit'],
            'id_profesi' => $data['idProfesi'],
            'created_at' => new DateTime()
        ]);
        return \jsonResponse(['idDepartemen' => $idDepartemen], 201);
    }

    function update(string $idDepartemen) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        DB::table('departemen')->where([['id_departemen', $idDepartemen]])->update([
            'nm_departemen' => $data['nmKontak'],
            'id_departemen' => $data['idDepartemen'],
            'id_unit' => $data['idUnit'],
            'id_profesi' => $data['idProfesi']
        ]);
        // $departemen = Kontak::find((int)$idDepartemen);
        // if ($departemen === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);

        // $departemen->nmKontak = $data['nmKontak'];
        // $departemen->idDepartemen = $data['idDepartemen'];
        // $departemen->update();
        return \jsonResponse(null, 204);
    }

    function delete(string $idDepartemen) {
        DB::table('departemen')->where([['id_departemen', $idDepartemen]])->delete();
        // $departemen = Kontak::find((int)$idDepartemen);
        // if ($departemen === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);

        // $departemen->delete();
        return \jsonResponse(null, 204);
    }

}