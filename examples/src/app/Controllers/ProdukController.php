<?php

namespace App\Controllers;

use App\Models\Produk;
use Selvi\Request;

class ProdukController {

    function __construct(
        private Produk $Produk
    ) { }

    function result() {
        $result = $this->Produk->result();
        return \jsonResponse($result, 200);
    }

    function row(String $idProduk) {
        $result = $this->Produk->row([['produk.idProduk', $idProduk]]);
        return \jsonResponse($result, 200);
    }

    function insert(Request $request) {
        $data = json_decode($request->raw(),true);
        $idProduk = $this->Produk->insert($data);
        return \jsonResponse(['idProduk' => $idProduk] , 201);
    }

    function update(Request $request, String $idProduk) {
        $data = json_decode($request->raw(), true);
        $this->Produk->update([['produk.idProduk', $idProduk]],$data);
        return \jsonResponse(null, 200);
    }

    function delete() {
        $this->Produk->delete([['produk.idProduk']]);
        return \jsonResponse(null, 200);
        
    }

}