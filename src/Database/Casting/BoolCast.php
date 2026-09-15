<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

/**
 * Cast property bertipe bool.
 *
 * Driver MySQL dan SQL Server mengirim kolom boolean sebagai '0' / '1', dan PHP
 * sudah menganggap string '0' sebagai false, jadi cast (bool) biasa cukup.
 */
final class BoolCast implements CastInterface {

    public function get(mixed $value): mixed {
        return $value === null ? null : (bool) $value;
    }

    public function set(mixed $value): mixed {
        return $value;
    }

}
