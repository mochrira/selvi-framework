<?php

declare(strict_types=1);

namespace Selvi\Database\Casting;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Cast property bertipe DateTimeInterface.
 *
 * format dipakai untuk dua arah keluar: set() (disimpan ke database, dibaca lagi
 * oleh MySQLSanitizer) dan serialize() (output toArray/JSON). Bila output perlu
 * format berbeda, cukup kirim DateTimeCast lain sebagai serializer kolomnya:
 *
 *     #[Column('createdAt', cast: new DateTimeCast(), serialize: new DateTimeCast(format: DATE_ATOM))]
 *
 * Catatan: DateTimeInterface adalah interface sehingga tidak bisa diinstansiasi;
 * bila property bertipe tepat DateTimeInterface, hasilnya DateTimeImmutable.
 */
final class DateTimeCast implements CastInterface, SerializesCastInterface {

    /**
     * @param class-string<DateTimeInterface> $class
     */
    public function __construct(
        private string $class = DateTime::class,
        private string $format = 'Y-m-d H:i:s'
    ) { }

    public function get(mixed $value): mixed {
        if($value === null || $value instanceof DateTimeInterface) return $value;

        $class = $this->class === DateTimeInterface::class ? DateTimeImmutable::class : $this->class;

        return new $class((string) $value);
    }

    public function set(mixed $value): mixed {
        return $value instanceof DateTimeInterface ? $value->format($this->format) : $value;
    }

    public function serialize(mixed $value): mixed {
        return $this->set($value);
    }

}
