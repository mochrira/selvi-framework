<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML\Clauses;

use InvalidArgumentException;

/**
 * Value object untuk SATU kondisi ORDER BY.
 *
 * Struktur kanonik yang dihasilkan toArray():
 *
 *     ['column' => 'kontak.nmKontak', 'direction' => 'ASC']
 *
 * Penulisan SQL adalah tugas Grammar tiap driver.
 */
final class OrderClause {

    public const DIRECTION_ASC  = 'ASC';
    public const DIRECTION_DESC = 'DESC';

    private readonly string $column;
    private readonly string $direction;

    private function __construct(string $column, string $direction) {
        $this->column = $column;
        $this->direction = $direction;
    }

    public static function asc(string $column): self
    {
        return self::create($column, self::DIRECTION_ASC);
    }

    public static function desc(string $column): self
    {
        return self::create($column, self::DIRECTION_DESC);
    }

    public static function create(string $column, string $direction = self::DIRECTION_ASC): self
    {
        return new self(self::normalizeColumn($column), self::normalizeDirection($direction));
    }

    public function column(): string
    {
        return $this->column;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function toArray(): array
    {
        return [
            'column'    => $this->column,
            'direction' => $this->direction,
        ];
    }

    private static function normalizeColumn(string $column): string
    {
        $column = trim($column);

        if ($column === '') {
            throw new InvalidArgumentException('Nama kolom order tidak boleh kosong.');
        }

        return $column;
    }

    private static function normalizeDirection(string $direction): string
    {
        $dir = strtoupper(trim($direction));

        if ($dir !== self::DIRECTION_ASC && $dir !== self::DIRECTION_DESC) {
            throw new InvalidArgumentException(
                "Arah order harus 'ASC' atau 'DESC', diberikan: '{$direction}'"
            );
        }

        return $dir;
    }

}
