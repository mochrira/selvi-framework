<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

use BackedEnum;
use ReflectionEnum;

/**
 * Cast property bertipe BackedEnum.
 *
 * Nilai dari database dikonversi mengikuti backing type enum (int atau string),
 * karena driver umumnya mengembalikan semua kolom sebagai string.
 *
 * serialize() mengembalikan value enum (scalar), supaya hasil toArray() sama
 * dengan bentuk yang disimpan di database.
 */
final class EnumCast implements CastInterface, SerializesCastInterface {

    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(
        private string $enum
    ) { }

    public function get(mixed $value): mixed {
        $enum = $this->enum;

        if($value === null || $value instanceof $enum) return $value;

        $backing = (new ReflectionEnum($enum))->getBackingType()?->getName();

        return $enum::from($backing === 'int' ? (int) $value : (string) $value);
    }

    public function set(mixed $value): mixed {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    public function serialize(mixed $value): mixed {
        return $this->set($value);
    }

}
