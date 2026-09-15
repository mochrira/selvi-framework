<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

use Selvi\Contracts\Arrayable;

/**
 * Cast property bertipe array untuk kolom JSON.
 *
 * Sengaja TIDAK didaftarkan sebagai cast bawaan, karena kolom JSON butuh kontrak
 * eksplisit (null vs array kosong tidak bisa ditebak dari tipe kolom). Pemakaian:
 *
 *     #[Column('options', cast: new ArrayCast())]
 *     public ?array $options;
 *
 * JSON yang tidak valid menghasilkan null, bukan exception, karena rusaknya data
 * di database bukan kesalahan pemanggil.
 *
 * serialize() merapikan item yang Arrayable menjadi array, supaya isi kolom JSON
 * (mis. kumpulan value object) tetap rapi di toArray()/JSON.
 */
final class ArrayCast implements CastInterface, SerializesCastInterface {

    public function get(mixed $value): mixed {
        if($value === null || is_array($value)) return $value;

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function set(mixed $value): mixed {
        return is_array($value) ? json_encode($value) : $value;
    }

    public function serialize(mixed $value): mixed {
        if(!is_array($value)) return $value;

        return array_map(
            fn(mixed $item) => $item instanceof Arrayable ? $item->toArray() : $item,
            $value
        );
    }

}
