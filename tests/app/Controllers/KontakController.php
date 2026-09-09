<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Input\Request;
use Selvi\Tests\Models\Kontak;

class KontakController {

    private Request $request;

    function __construct() {
        $this->request = inject(Request::class);
     }

    function result() {
        $limit = $this->request->get('limit') !== null ? (int) $this->request->get('limit') : null;
        $offset = $this->request->get('offset') !== null ? (int) $this->request->get('offset') : null;

        $kontaks = Kontak::all(function ($query) use ($limit, $offset) {
            if ($limit !== null) $query->limit($limit);
            if ($offset !== null) $query->offset($offset);
            return $query;
        });

        $data = array_map(fn($item) => $item->toArray(), $kontaks);
        return \jsonResponse($data, 200);
    }

    function row(String $id) {
        $row = Kontak::find((int) $id);
        if (!$row) return \jsonResponse(['message' => 'Kontak not found'], 404);
        return \jsonResponse($row->toArray(), 200);
    }

    function insert() {
        $data = json_decode($this->request->raw(), true) ?? [];
        $kontak = new Kontak();
        $kontak->nmKontak = $data['nmKontak'] ?? "Halo";
        $kontak->idGrup = $data['idGrup'] ?? null;
        $kontak->create();
        return \jsonResponse(['idKontak' => $kontak->idKontak], 201);
    }

    function update(String $id) {
        $data = json_decode($this->request->raw(), true) ?? [];
        $kontak = Kontak::find((int) $id);
        if (!$kontak) return \jsonResponse(['message' => 'Kontak not found'], 404);

        if (isset($data['nmKontak'])) $kontak->nmKontak = $data['nmKontak'];
        if (isset($data['idGrup'])) $kontak->idGrup = $data['idGrup'];
        
        $kontak->update();
        return \jsonResponse(null, 204);
    }

    function delete(String $id) {
        $kontak = Kontak::find((int) $id);
        if (!$kontak) return \jsonResponse(['message' => 'Kontak not found'], 404);

        $kontak->delete();
        return \jsonResponse(null, 204);
    }

}