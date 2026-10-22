<?php

declare(strict_types=1);

namespace Selvi\Database\Interfaces;

/**
 * Serialisasi value untuk toArray()/JSON.
 */
interface ISerializesCast {

    /**
     * model -> output (toArray()/JSON).
     */
    public function serialize(mixed $value): mixed;

}
