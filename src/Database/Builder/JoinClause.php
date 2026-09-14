<?php

declare(strict_types=1);

namespace Selvi\Database\Builder;

use InvalidArgumentException;

/**
 * Value object untuk SATU JOIN.
 *
 * Sederhana dan sengaja tidak tahu cara merender SQL. Isinya hanya:
 * - arah JOIN (LEFT / INNER / RIGHT),
 * - nama tabel yang di-join,
 * - klausa ON sebagai string mentah.
 *
 * Struktur kanonik yang dihasilkan toArray():
 *
 *     ['join' => 'LEFT', 'table' => 'another', 'on' => 'another.colA = table.colA']
 *
 * Penulisan `LEFT JOIN another ON ...` adalah tugas Grammar tiap driver.
 */
final class JoinClause {

    public const JOIN_LEFT  = 'LEFT';
    public const JOIN_INNER = 'INNER';
    public const JOIN_RIGHT = 'RIGHT';

    private readonly string $join;
    private readonly string $table;
    private readonly string $on;

    private function __construct(string $join, string $table, string $on) {
        $this->join = $join;
        $this->table = $table;
        $this->on = $on;
    }

    /**
     * LEFT JOIN.
     */
    public static function left(string $table, string $on): self
    {
        return self::create(self::JOIN_LEFT, $table, $on);
    }

    /**
     * INNER JOIN.
     */
    public static function inner(string $table, string $on): self
    {
        return self::create(self::JOIN_INNER, $table, $on);
    }

    /**
     * RIGHT JOIN.
     */
    public static function right(string $table, string $on): self
    {
        return self::create(self::JOIN_RIGHT, $table, $on);
    }

    public function join(): string
    {
        return $this->join;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function on(): string
    {
        return $this->on;
    }

    public function toArray(): array
    {
        return [
            'join'  => $this->join,
            'table' => $this->table,
            'on'    => $this->on,
        ];
    }

    private static function create(string $join, string $table, string $on): self
    {
        return new self($join, self::normalizeTable($table), self::normalizeOn($on));
    }

    private static function normalizeTable(string $table): string
    {
        $table = trim($table);

        if ($table === '') {
            throw new InvalidArgumentException('Nama tabel join tidak boleh kosong.');
        }

        return $table;
    }

    private static function normalizeOn(string $on): string
    {
        $on = trim($on);

        if ($on === '') {
            throw new InvalidArgumentException('Klausa ON join tidak boleh kosong.');
        }

        return $on;
    }
}
