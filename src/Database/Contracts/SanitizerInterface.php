<?php

declare(strict_types=1);

namespace Selvi\Database\Contracts;

/**
 * Mengubah value PHP menjadi literal SQL sesuai dialek driver.
 *
 * Implementasi bertanggung jawab penuh atas keamanan: setiap value harus
 * di-escape/dibungkus sesuai aturan driver, tidak boleh ditempel apa adanya.
 */
interface SanitizerInterface {

    /**
     * Konversi satu value PHP menjadi literal SQL.
     *
     * Mapping yang diharapkan:
     * - null                        => NULL
     * - bool                        => 1 / 0
     * - int                         => literal angka (mis. 12)
     * - float                       => literal angka, pemisah titik (mis. 12.5)
     * - string                      => literal string ter-quote dan ter-escape
     * - \DateTimeInterface          => literal datetime driver (mis. 'Y-m-d H:i:s')
     * - \BackedEnum                 => value enum disanitasi seperti tipe aslinya
     * - object dengan __toString()  => diperlakukan sebagai string
     *
     * Untuk nilai majemuk (mis. IN (...) atau VALUES (...)) pemanggil yang
     * bertanggung jawab memecah array dan memanggil sanitize() per elemen.
     *
     * @throws \InvalidArgumentException jika tipe value tidak dapat dikonversi
     *         (mis. array atau object tanpa __toString()).
     */
    public function sanitize(mixed $value): string;

}
