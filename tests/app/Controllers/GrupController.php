<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\Builder\DML\WhereBuilder;
use Selvi\Exception;
use Selvi\Exception\DatabaseException;
use Selvi\Input\Request;
use Selvi\Tests\Models\Grup;

class GrupController {

    function __construct(
        private Request $request
    ) { }

    function result() {
        $filter = function (QueryBuilder $query) {
            $search = $this->request->get('search');
            if ($search !== null && $search !== '') {
                $query->where(function (WhereBuilder $builder) use ($search) {
                    $builder->orWhere([
                        ['grup.nmGrup', 'LIKE', '%'.$search.'%']
                    ]);
                });
            }
        };

        $count = Grup::count($filter);
        $data = Grup::all(function (QueryBuilder $query) use ($filter) {
            $filter($query);

            $orderBy = $this->request->get('orderBy') ?? 'grup.id_grup';
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
        })->map(function (&$grup) {
            $grup->randomCode = md5($grup->nmGrup);
            return $grup;
        });

        return jsonResponse([
            'count' => $count,
            'data' => $data->toArray()
        ]);
    }

    function row(string $idGrup) {
        $data = Grup::find((int)$idGrup);
        if($data == null) throw new Exception("Grup tidak ditemukan", "grup/not-found", 404);
        return \jsonResponse($data->toArray(), 200);
    }

    function insert() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $grup = Grup::create([
            'nmGrup' => $data['nmGrup'],
        ]);
        return \jsonResponse(['idGrup' => $grup->idGrup], 201);
    }

    function update(string $idGrup) {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $grup = Grup::find((int)$idGrup);
        if ($grup === null) throw new Exception('Grup tidak ditemukan', 'grup/not-found', 404);

        $grup->nmGrup = $data['nmGrup'];
        $grup->update();
        return \jsonResponse(null, 204);
    }

    function delete(string $idGrup) {
        try {
            $grup = Grup::find((int)$idGrup);
            if ($grup === null) throw new Exception('Grup tidak ditemukan', 'grup/not-found', 404);
            
            $grup->delete();
            return \jsonResponse(null, 204);
        } catch(DatabaseException $e) {
            if($e->getState() == '23000') {
                throw new Exception('Grup sedang digunakan di data lain', 'grup/delete-fail', 409);
            }
            throw $e;
        }
    }

}