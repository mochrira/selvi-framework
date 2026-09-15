<?php

declare(strict_types=1);

namespace Selvi\Database\Contracts;

/**
 * Konversi value dua arah antara database dan property model.
 *
 * Padanan CastsAttributes di Laravel: get() membaca value mentah dari database,
 * set() menyiapkan value untuk disimpan.
 *
 * Batas tanggung jawab: cast hanya mengubah value PHP menjadi value PHP (mis.
 * string datetime menjadi objek DateTime). Mengubah value menjadi literal SQL
 * tetap tugas SanitizerInterface milik driver.
 *
 * Cast harus stateless, karena satu instance dipakai bersama oleh semua baris
 * dan semua kolom yang memakai cast tersebut.
 *
 * @see \Selvi\Database\Casting\Converter pemakai kontrak ini di sisi framework
 */
interface CastInterface {

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
