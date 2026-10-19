<?php

declare(strict_types=1);

namespace Selvi\Tests\Casting;

use Selvi\Database\Contracts\SerializesCastInterface;

final class ZeroIfNullCast implements SerializesCastInterface {

    public function serialize(mixed $value): mixed {
        return (!isset($value) ? 0 : $value);
    }

}
