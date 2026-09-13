<?php

declare(strict_types=1);

namespace Selvi\Database\Drivers\MySQL;

use BackedEnum;
use DateTimeInterface;
use InvalidArgumentException;
use Selvi\Database\Contracts\SanitizerInterface;
use Stringable;

/**
 * Sanitizer value untuk driver MySQL.
 *
 * Semua value dikonversi menjadi literal SQL yang aman ditempel ke query:
 * string dibungkus tanda kutip tunggal, backslash di-escape, dan tanda kutip
 * tunggal di-double sehingga tidak bisa keluar dari literal.
 */
class MySQLSanitizer implements SanitizerInterface {

    public function sanitize(mixed $value): string {
        if($value === null) return 'NULL';
        if(is_bool($value)) return $value ? '1' : '0';
        if(is_int($value)) return (string) $value;
        if(is_float($value)) return $this->sanitizeFloat($value);
        if($value instanceof BackedEnum) return $this->sanitize($value->value);
        if($value instanceof DateTimeInterface) return $this->quote($value->format('Y-m-d H:i:s'));
        if(is_string($value)) return $this->quote($value);
        if($value instanceof Stringable) return $this->quote((string) $value);

        throw new InvalidArgumentException(
            'Tipe value tidak dapat dikonversi ke literal MySQL: ' . get_debug_type($value)
        );
    }

    private function sanitizeFloat(float $value): string {
        if(is_nan($value) || is_infinite($value)) {
            throw new InvalidArgumentException(
                'Value float non-finite (NAN/INF) tidak dapat dikonversi ke literal MySQL.'
            );
        }
        return (string) $value;
    }

    private function quote(string $value): string {
        $value = str_replace(["\\", "'"], ["\\\\", "''"], $value);
        return "'" . $value . "'";
    }

}
