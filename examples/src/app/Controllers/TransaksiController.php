<?php

namespace App\Controllers;

use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Selvi\Database\Manager;
use Selvi\Exception;
use Selvi\Request;

class TransaksiController {

    function __construct(
        private Transaksi $Transaksi
    ){ }

    function row(String $idTransaksi) {

    }

    function result(){

    }

    function insert(Request $request, TransaksiDetail $TransaksiDetail, Manager $Manager){
        $data = json_decode($request->raw(), true);
        $detailTransaksi = $data['transaksiDetail'];
        unset($data['transaksiDetail']);
        $data['total'] = array_reduce($detailTransaksi, function($acc, $current){
            return $acc += $current['harga'] * $current['jumlah'];
        });

        try {
            $Manager::get("mysql")->startTransaction();
            $idTransaksi = $this->Transaksi->insert($data);

            foreach ($detailTransaksi as $detail) {
                $dataDetailTransaksi = [
                    'idTransaksi' => $idTransaksi,
                    'idProduk' => $detail['idProduk'],
                    'harga' => $detail['harga'],
                    'jumlah' => $detail['jumlah'],
                    'total' => $detail['harga'] * $detail['jumlah']
                ];
                $TransaksiDetail->insert($dataDetailTransaksi);
            }

            $Manager::get("mysql")->commit();

            return \jsonResponse(null, 201);
        } catch (Exception $e ) {
            $Manager::get("mysql")->rollback();
            throw $e;
        }
    }

    function update(Request $request, String $idTransaksi){

    }

    function delete(String $idTransaksi, TransaksiDetail $TransaksiDetail) {
        $this->Transaksi->delete([['transaksi.id', $idTransaksi]]);
        $TransaksiDetail->delete([['transaksiDetail.idTransaksi', $idTransaksi]]);
        return \jsonResponse(null, 200);
    }
}