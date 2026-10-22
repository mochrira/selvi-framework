<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

use Selvi\Database\Interfaces\ICast;

/**
 * Cast property bertipe float.
 */
final class FloatCast implements ICast {

    public function get(mixed $value): mixed {
        return $value === null ? null : (float) $value;
    }

    public function set(mixed $value): mixed {
        return $value;
    }

}
