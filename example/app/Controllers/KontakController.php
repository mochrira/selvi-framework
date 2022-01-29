<?php 

namespace App\Controllers;

use Selvi\Controller;
use Selvi\Exception;
use App\Models\Kontak;

class KontakController extends Controller { 
    
    function __construct() {
        $this->load(Kontak::class, 'Kontak');
    }

    function rowException($idKontak) {
        $data = $this->Kontak->row([['idKontak', $idKontak]]);
        if(!$data) {
            Throw new Exception('Kontak not found', 'kontak/not-found', 404);
        }
        return $data;
    }

    function result() {
        $order = [];
        $sort = $this->input->get('sort');
        if($sort !== null) {
            $order = \buildOrder($sort);
        }

        $orWhere = [];
        $search = $this->input->get('search');
        if($search !== null) {
            $orWhere = \buildSearch(['nmKontak'], $search);
        }

        $limit = $this->input->get('limit') ?? -1;
        $offset = $this->input->get('offset') ?? 0;
        $where = [];

        return jsonResponse([
            'data' => $this->Kontak->result($where, $orWhere, $order, $limit, $offset),
            'count' => $this->Kontak->count($where, $orWhere)
        ]);
    }

    function row() {
        $idKontak = $this->uri->segment(2);
        $data = $this->rowException($idKontak);
        return jsonResponse($data);
    }

    function insert() {
        $data = json_decode($this->input->raw(), true);
        $idKontak = $this->Kontak->insert($data);
        if($idKontak === false) {
            Throw new Exception('Failed to insert', 'kontak/insert-failed');
        }
        return jsonResponse(['idKontak' => $idKontak], 201);
    }

    function update() {
        $idKontak = $this->uri->segment(2);
        $this->rowException($idKontak);
        $data = json_decode($this->input->raw(), true);
        if(!$this->Kontak->update([['idKontak', $idKontak]], $data)) {
            Throw new Exception('Failed to update', 'kontak/update-failed');
        }
        return response('', 204);
    }

    function delete() {
        $idKontak = $this->uri->segment(2);
        $this->rowException($idKontak);
        if(!$this->Kontak->delete([['idKontak', $idKontak]])) {
            Throw new Exception('Failed to delete', 'kontak/delete-failed');
        }
        return response('', 204);
    }

}