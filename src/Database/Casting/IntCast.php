<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

use Selvi\Database\Contracts\CastInterface;

/**
 * Cast property bertipe int.
 *
 * Tidak punya serialize(): value int sudah berbentuk akhir, jadi output-nya
 * memakai passthrough di Converter.
 */
final class IntCast implements CastInterface {

    public function get(mixed $value): mixed {
        return $value === null ? null : (int) $value;
    }

    public function set(mixed $value): mixed {
        return $value;
    }

}
