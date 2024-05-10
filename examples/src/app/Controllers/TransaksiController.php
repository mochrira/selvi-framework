<?php

namespace App\Controllers;

use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Selvi\Database\Manager;
use Selvi\Exception;
use Selvi\Request;

class TransaksiController {

    function __construct(
        private Transaksi $Transaksi,
        private TransaksiDetail $TransaksiDetail
    ){ }

    function row(String $idTransaksi) {
        $row =(array)$this->Transaksi->row([['transaksi.idTransaksi', $idTransaksi]]);
        $row['detail'] = $this->TransaksiDetail->result();
        return jsonResponse($row, 200);
    }

    function result(){
        $result =  $this->Transaksi->Result();
        return jsonResponse($result, 200);
    }

    function insert(Request $request, Manager $Manager){
        $data = json_decode($request->raw(), true);
        $detailTransaksi = $data['transaksiDetail'];
        unset($data['transaksiDetail']);
        $data['total'] = array_reduce($detailTransaksi, function($acc, $current){
            return $acc += $current['harga'] * $current['jumlah'];
        });

        try {
            $Manager::get("main")->startTransaction();
            $idTransaksi = $this->Transaksi->insert($data);

            foreach ($detailTransaksi as $detail) {
                $dataDetailTransaksi = [
                    'idTransaksi' => $idTransaksi,
                    'idProduk' => $detail['idProduk'],
                    'harga' => $detail['harga'],
                    'jumlah' => $detail['jumlah'],
                    'total' => $detail['harga'] * $detail['jumlah']
                ];
                $this->TransaksiDetail->insert($dataDetailTransaksi);
            }

            $Manager::get("main")->commit();

            return \jsonResponse(null, 201);
        } catch (Exception $e ) {
            $Manager::get("main")->rollback();
            throw $e;
        }
    }

    function update(Request $request, String $idTransaksi, Manager $manager){
        $data = json_decode($request->raw(), true);
        $detailTransaksi = $data['transaksiDetail'];
        unset($data['transaksiDetail']);
        $data['total'] = array_reduce($detailTransaksi, function($acc, $current){
            return $acc += $current['harga'] * $current['jumlah'];
        });

        try {
            $manager::get('main')->startTransaction();
            $this->TransaksiDetail->delete([['transaksiDetail.idTransaksi', $idTransaksi]]);

            $this->Transaksi->update([['transaksi.idTransaksi' , $idTransaksi]], $data);
            foreach ($detailTransaksi as $detail) {
                $dataDetailTransaksi = [
                    'idTransaksi' => $idTransaksi,
                    'idProduk' => $detail['idProduk'],
                    'harga' => $detail['harga'],
                    'jumlah' => $detail['jumlah'],
                    'total' => $detail['harga'] * $detail['jumlah']
                ];
                $this->TransaksiDetail->insert($dataDetailTransaksi);
            }
            
            $manager::get('main')->commit();
            return \jsonResponse(null, 200);

        } catch (Exception $e) {
            $manager::get('main')->rollback();
            throw $e;
        }
    }

    function delete(String $idTransaksi) {
        $this->Transaksi->delete([['transaksi.idTransaksi', $idTransaksi]]);
        $this->TransaksiDetail->delete([['transaksiDetail.idTransaksi', $idTransaksi]]);
        return \jsonResponse(null, 200);
    }
}