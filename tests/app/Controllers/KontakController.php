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
        // $attributes = Kontak::attributes();
        // var_dump($attributes);

        // $attributes = Grup::attributes();
        // var_dump($attributes);

        // $properties = Kontak::properties();
        // var_dump($properties);

        // $properties = Grup::properties();
        // var_dump($properties);

        // $columns = Kontak::columns();
        // var_dump($columns);

        // $columns = Grup::columns();
        // var_dump($columns);

        // $relations = Kontak::relations();
        // var_dump($relations);

        // $property = Kontak::relation('grup');
        // var_dump($property);

        // $properties = Kontak::properties();
        // var_dump($properties);

        // die();

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

        $grup = DB::table('grup')->getSql();
        $query = DB::table('kontak')->innerJoin("({$grup}) grupSql", "grupSql.id_grup = kontak.id_grup")->get()->rows();
        var_dump($query);
        die();

        $count = Kontak::count($filter);
        $data = Kontak::with('grup')->all(function (QueryBuilder $query) use ($filter) {
            $filter($query);

            $limit = $this->request->get('limit') ?? 30;
            if ($limit !== null && is_numeric($limit)) {
                $query->limit((int)$limit);
            }

            $offset = $this->request->get('offset') ?? 0;
            if ($offset !== null && is_numeric($offset)) {
                $query->offset((int)$offset);
            }

            echo $query->getSql()."\n\n";
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
        $kontak->idGrup = $data['idGrup'];
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