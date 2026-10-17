<?php 

namespace Selvi\Tests\Controllers;

use DateTime;
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\Builder\DML\WhereBuilder;
use Selvi\Database\DB;
use Selvi\Exception;
use Selvi\Input\Request;
use Selvi\Model\Attributes\Table;
use Selvi\Tests\Models\Grup;
use Selvi\Tests\Models\Kontak;

class KontakController {

    function __construct(
        private Request $request
    ) { }

    function result() {
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
            ->with('unit', fn($b) => $b
                ->with('departemen'))
            ->with('profesi', fn($b) => $b
                ->with('unit', fn($b) => $b
                    ->with('departemen'))
            )
            ->all(function (QueryBuilder $query) use ($filter) {
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
        return \jsonResponse($data->toArray(), 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $kontak = Kontak::create([
            'nm_kontak' => $data['nmKontak'],
            'id_grup' => $data['idGrup'],
            'created_at' => new DateTime()
        ]);
        return \jsonResponse(['id_kontak' => $kontak->idKontak], 201);
    }

    function update(string $idKontak) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $kontak = Kontak::find((int)$idKontak);
        if ($kontak === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);

        $kontak->nmKontak = $data['nmKontak'];
        $kontak->idDepartemen = $data['idDepartemen'];
        $kontak->update();
        return \jsonResponse(null, 204);
    }

    function delete(string $idKontak) {
        $kontak = Kontak::find((int)$idKontak);
        if ($kontak === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);

        $kontak->delete();
        return \jsonResponse(null, 204);
    }

}