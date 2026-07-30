<?php 

namespace Selvi\Tests\Controllers;

use Selvi\Tests\Models\Grup;
use Selvi\Input\Request;

class GrupController {

    function result() {
        return \jsonResponse(Grup::all(), 200);
    }

    function row(string $id) {
        return \jsonResponse(Grup::find($id), 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(), true);
        $grup = Grup::create($data);
        return \jsonResponse(['idGrup' => $grup->idGrup], 201);
    }

    function update(Request $request, string $id) {
        $data = json_decode($request->raw(), true);
        $grup = Grup::find($id);
        $grup->update($data);
        return \jsonResponse(null, 204);
    }

    function delete(string $id) {
        $grup = Grup::find($id);
        $grup->delete();
        return \jsonResponse(null, 204);
    }

}