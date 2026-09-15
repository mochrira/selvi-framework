<?php 

namespace Selvi\Tests\Controllers;

use DateTime;
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\Builder\DML\WhereBuilder;
use Selvi\Exception;
use Selvi\Input\Request;
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
                    ['kontak.idGrup', '=', (int)$idGrup]
                ]);
            }

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

            $orderBy = $this->request->get('orderBy') ?? 'kontak.id_kontak';
            $sortBy = $this->request->get('sortBy') ?? 'DESC';
            $query->orderBy($orderBy, $sortBy);

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
        if($data == null) throw new Exception("Kontak tidak ditemukan", "kontak/not-found", 404);
        return \jsonResponse($data->toArray(), 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $kontak = Kontak::create([
            'nmKontak' => $data['nmKontak'],
            'idGrup' => $data['idGrup'],
            'createdAt' => new DateTime()
        ]);
        return \jsonResponse(['id_kontak' => $kontak->idKontak], 201);
    }

    function update(string $idKontak) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $kontak = Kontak::find((int)$idKontak);
        if ($kontak === null) throw new Exception('Kontak tidak ditemukan', 'data/not-found', 404);

        $kontak->nmKontak = $data['nmKontak'];
        $kontak->idGrup = (int)$data['idGrup'];
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