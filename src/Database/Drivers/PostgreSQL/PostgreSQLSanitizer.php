<?php

declare(strict_types=1);

namespace Selvi\Database\Drivers\PostgreSQL;

use BackedEnum;
use DateTimeInterface;
use InvalidArgumentException;
use Selvi\Database\Interfaces\ISanitizer;
use Stringable;

/**
 * Sanitizer value untuk driver PostgreSQL.
 *
 * Dua perbedaan penting dibanding MySQL:
 * - boolean menjadi TRUE/FALSE, karena PostgreSQL tidak melakukan cast implisit
 *   dari integer ke boolean;
 * - backslash TIDAK di-escape, karena `standard_conforming_strings` membuatnya
 *   karakter biasa. Meng-escape backslash justru mengubah data.
 */
class PostgreSQLSanitizer implements ISanitizer {

    public function sanitize(mixed $value): string {
        if($value === null) return 'NULL';
        if(is_bool($value)) return $value ? 'TRUE' : 'FALSE';
        if(is_int($value)) return (string) $value;
        if(is_float($value)) return $this->sanitizeFloat($value);
        if($value instanceof BackedEnum) return $this->sanitize($value->value);
        if($value instanceof DateTimeInterface) return $this->quote($value->format('Y-m-d H:i:s'));
        if(is_string($value)) return $this->quote($value);
        if($value instanceof Stringable) return $this->quote((string) $value);

        throw new InvalidArgumentException(
            'Tipe value tidak dapat dikonversi ke literal PostgreSQL: ' . get_debug_type($value)
        );
    }

    private function sanitizeFloat(float $value): string {
        if(is_nan($value) || is_infinite($value)) {
            throw new InvalidArgumentException(
                'Value float non-finite (NAN/INF) tidak dapat dikonversi ke literal PostgreSQL.'
            );
        }
        return (string) $value;
    }

    private function quote(string $value): string {
        return "'" . str_replace("'", "''", $value) . "'";
    }

}
