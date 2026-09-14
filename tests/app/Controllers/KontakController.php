<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Database\Builder\ModelQuery;
use Selvi\Database\Builder\QueryBuilder;
use Selvi\Database\Builder\WhereBuilder;
use Selvi\Database\Manager;
use Selvi\DB;
use Selvi\Exception;
use Selvi\Input\Request;
use Selvi\Tests\Models\Kontak;

class KontakController {

    function __construct(
        private Request $request
    ) { }

    function result() {
        $filter = function (QueryBuilder $query) {
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
                        ['kontak.nmKontak', 'LIKE', '%'.$search.'%'],
                        ['grup.nmGrup', 'LIKE', '%'.$search.'%']
                    ]);
                });
            }
        };

        $count = Kontak::with('grup')->count($filter);
        $data = Kontak::with('grup')->all(function (QueryBuilder $query) use ($filter) {
            $filter($query);

            // 5. Order / Sorting
            $orderBy = $this->request->get('orderBy') ?? 'kontak.idKontak';
            $sortBy = $this->request->get('sortBy') ?? 'DESC';
            $query->orderBy($orderBy, $sortBy);

            // 6. Limit & Offset
            $limit = $this->request->get('limit');
            if ($limit !== null && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            $offset = $this->request->get('offset');
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
        $data = Kontak::with('grup')->find((int)$idKontak);
        return \jsonResponse($data->toArray(), 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];

        // Mentah

        // DB::table('kontak')->insert([
        //     'nmKontak' => $data['nmKontak'],
        //     'idGrup' => $data['idGrup']
        // ]);

        // tanpa objek

        // $idKontak = Kontak::query()->insert([
        //     'nmKontak' => $data['nmKontak'],
        //     'idGrup' => $data['idGrup']
        // ]);

        $kontak = Kontak::create([
            'nmKontak' => $data['nmKontak'],
            'idGrup' => $data['idGrup']
        ]);

        // Uji fresh(): baca ulang dari DB, sekaligus memuat relasi lewat with()
        $kontak = $kontak->fresh(fn(ModelQuery $query) => $query->with('grup'));

        if($kontak === null) {
            throw new Exception('Kontak tidak lagi ditemukan', 'data/not-found', 404);
        }

        return \jsonResponse($kontak->toArray(), 201);
    }

    function update(string $idKontak) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];

        // Versi 1 (Mentah / DBAL):
        // DB::table('kontak')->where([['kontak.idKontak', '=', (int)$idKontak]])->update([
        //     'nmKontak' => $data['nmKontak'],
        //     'idGrup' => $data['idGrup']
        // ]);

        // Versi 2 (Tanpa Objek / ModelQuery):
        // Kontak::query()->where([['kontak.idKontak', '=', (int)$idKontak]])->update([
        //     'nmKontak' => $data['nmKontak'],
        //     'idGrup' => $data['idGrup']
        // ]);

        // Versi 3 (Objek Model / Active Record Instance):
        $kontak = Kontak::find((int)$idKontak);
        if ($kontak === null) {
            throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);
        }

        $kontak->nmKontak = $data['nmKontak'];
        $kontak->idGrup = (int)$data['idGrup'];
        $kontak->update();

        return \jsonResponse(null, 204);
    }

    function delete(string $idKontak) {
        // Versi 1 (Mentah / DBAL):
        // DB::table('kontak')->where([['kontak.idKontak', '=', (int)$idKontak]])->delete();

        // Versi 2 (Tanpa Objek / ModelQuery):
        // Kontak::query()->where([['kontak.idKontak', '=', (int)$idKontak]])->delete();

        // Versi 3 (Objek Model / Active Record Instance):
        $kontak = Kontak::find((int)$idKontak);
        if ($kontak === null) {
            throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);
        }
        $kontak->delete();

        return \jsonResponse(null, 204);
    }

}