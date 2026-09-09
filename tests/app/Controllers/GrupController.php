<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Tests\Models\Grup;
use Selvi\Input\Request;

class GrupController {

    private Request $request;

    function __construct() {
        $this->request = inject(Request::class);
    }

    function result() {
        $limit = $this->request->get('limit') !== null ? (int) $this->request->get('limit') : null;
        $offset = $this->request->get('offset') !== null ? (int) $this->request->get('offset') : null;

        $grups = Grup::all(function ($query) use ($limit, $offset) {
            if ($limit !== null) $query->limit($limit);
            if ($offset !== null) $query->offset($offset);
            return $query;
        });

        $data = array_map(fn($item) => $item->toArray(), $grups);
        return \jsonResponse($data, 200);
    }

    function row(string $id) {
        $grup = Grup::find((int) $id);
        if (!$grup) return \jsonResponse(['message' => 'Grup not found'], 404);
        return \jsonResponse($grup->toArray(), 200);
    }

    function insert() {
        $data = json_decode($this->request->raw(), true) ?? [];
        $grup = new Grup();
        $grup->nmGrup = $data['nmGrup'] ?? "Grup Baru";
        $grup->create();
        return \jsonResponse(['idGrup' => $grup->idGrup], 201);
    }

    function update(string $id) {
        $data = json_decode($this->request->raw(), true) ?? [];
        $grup = Grup::find((int) $id);
        if (!$grup) return \jsonResponse(['message' => 'Grup not found'], 404);

        if (isset($data['nmGrup'])) $grup->nmGrup = $data['nmGrup'];
        $grup->update();
        return \jsonResponse(null, 204);
    }

    function delete(string $id) {
        $grup = Grup::find((int) $id);
        if (!$grup) return \jsonResponse(['message' => 'Grup not found'], 404);

        $grup->delete();
        return \jsonResponse(null, 204);
    }

}