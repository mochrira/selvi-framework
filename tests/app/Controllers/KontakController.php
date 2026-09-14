<?php 

namespace Selvi\Tests\Controllers;

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
        $result = Kontak::with('grup')->all();
        var_dump($result);
        die();


        $query = DB::table('kontak')
            ->innerJoin('grup', 'grup.idGrup = kontak.idGrup')
            ->select([
                'kontak.idKontak', 
                'kontak.nmKontak', 
                'kontak.idGrup',
                'grup.idGrup AS grup__idGrup',
                'grup.nmGrup AS grup__nmGrup'
            ]);

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
                    ['kontak.nmKontak', 'LIKE', '%'.$search.'%'],
                    ['grup.nmGrup', 'LIKE', '%'.$search.'%']
                ]);
            });
        }

        // 5. Order / Sorting
        // QueryBuilder mendukung berbagai format:
        // - Method orderBy: $query->orderBy('kontak.idKontak', 'DESC');
        // - Method order (string): $query->order('grup.nmGrup ASC, kontak.nmKontak ASC');
        // - Method order (assoc array): $query->order(['kontak.nmKontak' => 'ASC', 'kontak.idKontak' => 'DESC']);
        // - Method order (indexed array): $query->order(['kontak.nmKontak ASC', 'kontak.idKontak DESC']);
        $orderBy = $this->request->get('orderBy');
        $sortBy = $this->request->get('sortBy') ?? 'ASC';

        if (!empty($orderBy) && is_string($orderBy)) {
            // Dipakai oleh OpenAPI / Swagger UI (select box orderBy & sortBy)
            $query->orderBy($orderBy, $sortBy);
        } else {
            // Mendukung parameter order atau sort baik format string maupun array
            $sort = $this->request->get('order') ?? $this->request->get('sort');
            if (!empty($sort)) {
                if (is_array($sort)) {
                    // Contoh input via URL: ?order[kontak.nmKontak]=ASC
                    $query->order($sort);
                } elseif (is_string($sort)) {
                    // Contoh input via URL: ?sort=kontak.nmKontak:ASC atau ?order=kontak.nmKontak ASC, kontak.idKontak DESC
                    $query->order(str_replace(':', ' ', $sort));
                }
            } else {
                // Default sorting
                $query->orderBy('kontak.idKontak', 'DESC');
            }
        }

        // 6. Limit & Offset
        $limit = $this->request->get('limit');
        if ($limit !== null && is_numeric($limit)) {
            $query->limit((int)$limit);
        }

        $offset = $this->request->get('offset');
        if ($offset !== null && is_numeric($offset)) {
            $query->offset((int)$offset);
        }

        $data = $query->get()->result();
        return \jsonResponse($data, 200);
    }

    function row(string $idKontak) {
        $data = DB::table('kontak')
            ->innerJoin('grup', 'grup.idGrup = kontak.idGrup')
            ->select([
                'kontak.idKontak', 
                'kontak.nmKontak', 
                'kontak.idGrup',
                'grup.idGrup AS grup__idGrup',
                'grup.nmGrup AS grup__nmGrup'
            ])
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