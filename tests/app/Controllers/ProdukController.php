<?php

namespace Selvi\Tests\Controllers;

use Selvi\Tests\Models\ProdukModel;
use Selvi\Input\Request;

class ProdukController {

    function __construct(
        private ProdukModel $ProdukModel
    ) { }

    function result() {
        $result = $this->ProdukModel->result();
        return \jsonResponse($result, 200);
    }

    function row(String $idProduk) {
        $result = $this->ProdukModel->row([['produk.idProduk', $idProduk]]);
        return \jsonResponse($result, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(),true);
        $idProduk = $this->ProdukModel->insert($data);
        return \jsonResponse(['idProduk' => $idProduk] , 201);
    }

    function update(Request $request, String $idProduk) {
        $data = json_decode($request->raw(), true);
        $this->ProdukModel->update([['produk.idProduk', $idProduk]],$data);
        return \jsonResponse(null, 200);
    }

    function delete() {
        $this->ProdukModel->delete([['produk.idProduk']]);
        return \jsonResponse(null, 200);
        
    }

}