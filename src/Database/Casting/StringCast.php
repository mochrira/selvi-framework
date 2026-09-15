<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

/**
 * Cast property bertipe string.
 *
 * Berguna juga untuk kolom yang tipenya bukan string tetapi dimodelkan sebagai
 * string, mis. kolom DECIMAL atau BIGINT yang nilainya bisa melewati batas int
 * PHP. Untuk kasus itu, daftarkan cast ini secara eksplisit:
 *
 *     #[Column('total', cast: new StringCast())]
 */
final class StringCast implements CastInterface {

    public function get(mixed $value): mixed {
        return $value === null ? null : (string) $value;
    }

    public function set(mixed $value): mixed {
        return $value;
    }

}
