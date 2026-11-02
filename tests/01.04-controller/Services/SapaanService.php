<?php

declare(strict_types=1);

namespace App\Services;

class SapaanService {

    public function pesan(string $nama): string {
        return "Halo, {$nama}!";
    }

}
