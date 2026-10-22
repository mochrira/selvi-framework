<?php

declare(strict_types=1);

namespace Selvi\Database\Interfaces;

/**
 * Konversi value dua arah antara database dan property model.
 */
interface ICast {

    /**
     * DB -> model.
     *
     * Value null dilewatkan apa adanya; cast bawaan mengembalikan null tanpa
     * mengubahnya.
     */
    public function get(mixed $value): mixed;

    /**
     * model -> DB.
     *
     * Hasilnya value PHP biasa (mis. string '2026-09-14 09:00:00'), bukan
     * literal SQL.
     */
    public function set(mixed $value): mixed;

}
