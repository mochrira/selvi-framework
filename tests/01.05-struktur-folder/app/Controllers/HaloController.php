<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SapaanService;

class HaloController {

    public function __construct(private SapaanService $sapaan) { }

    public function index() {
        return response('Halo dari index.php');
    }

    public function sapa(string $nama) {
        return response($this->sapaan->pesan($nama));
    }

}
