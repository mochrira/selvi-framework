<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SapaanService;
use Selvi\Input\Request;

class HaloController {

    public function __construct(
        private Request $request,
        private SapaanService $sapaan
    ) { }

    public function index() {
        return response('Halo dari controller!');
    }

    public function sapa(string $nama) {
        return response($this->sapaan->pesan($nama));
    }

    public function cari() {
        $q = $this->request->get('q');
        return jsonResponse(['q' => $q]);
    }

    public function urut(string $pertama, string $kedua) {
        return response("{$pertama}|{$kedua}");
    }

    public function sapaSalah(string $nama) {
        return response("Halo, {$nama}!");
    }

    public function angka(string $id) {
        if (!ctype_digit($id)) {
            return response('ID harus angka', 400);
        }
        return jsonResponse(['id' => (int) $id]);
    }

    public function angkaTypeHint(int $id) {
        return jsonResponse(['id' => $id]);
    }

    public function tanpaReturn() {
        echo 'Halo tanpa return';
    }

}
