<?php

declare(strict_types=1);

namespace Selvi\Database\Builder;

use InvalidArgumentException;
use LogicException;

/**
 * Value object untuk SATU kondisi WHERE.
 *
 * Class ini sengaja TIDAK tahu cara merender SQL. Tugasnya hanya:
 * - memvalidasi input kondisi,
 * - menormalkan semantik yang sama untuk semua driver,
 * - mengeluarkan struktur kanonik (AST) lewat toArray().
 *
 * Normalisasi yang dilakukan di sini (berlaku untuk semua driver):
 * - operator di-uppercase, `!=` dan `<>` disatukan menjadi `<>`,
 * - value null dengan operator `=` / `IS` menjadi node `null` (`IS NULL`),
 * - value null dengan operator `!=` / `<>` / `IS NOT` menjadi node `null` (`IS NOT NULL`).
 *
 * Sisanya (identifier quoting, placeholder, value -> literal) adalah tugas
 * Grammar masing-masing driver.
 *
 * Shape AST yang dihasilkan toArray():
 *
 *     ['type' => 'basic',  'boolean' => 'AND', 'column' => 'a', 'operator' => '=',          'value' => 1]
 *     ['type' => 'null',   'boolean' => 'AND', 'column' => 'a', 'operator' => 'IS NOT NULL']
 *     ['type' => 'raw',    'boolean' => 'OR',  'sql' => 'b IS NOT NULL']
 *     ['type' => 'nested', 'boolean' => 'AND', 'wheres' => [ ... node rekursif ... ]]
 *
 * Catatan: `boolean` selalu terisi ('AND' / 'OR'), termasuk node pertama.
 * Grammar yang bertanggung jawab tidak menuliskan prefix pada node pertama.
 */
final class WhereClause {

    public const TYPE_BASIC  = 'basic';
    public const TYPE_NULL   = 'null';
    public const TYPE_RAW    = 'raw';
    public const TYPE_NESTED = 'nested';

    public const BOOLEAN_AND = 'AND';
    public const BOOLEAN_OR  = 'OR';

    private readonly string $type;
    private readonly string $boolean;
    private readonly ?string $column;
    private readonly ?string $operator;
    private readonly mixed $value;
    private readonly ?string $sql;
    private readonly ?WhereBuilder $builder;

    private function __construct(
        string $type,
        string $boolean,
        ?string $column = null,
        ?string $operator = null,
        mixed $value = null,
        ?string $sql = null,
        ?WhereBuilder $builder = null
    ) {
        $this->type = $type;
        $this->boolean = $boolean;
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
        $this->sql = $sql;
        $this->builder = $builder;
    }

    /**
     * Fragmen SQL mentah. Value tidak melalui sanitizer.
     */
    public static function raw(string $sql, string $boolean = self::BOOLEAN_AND): self
    {
        $sql = trim($sql);

        if ($sql === '') {
            throw new InvalidArgumentException('Raw where tidak boleh kosong.');
        }

        return new self(self::TYPE_RAW, self::normalizeBoolean($boolean), sql: $sql);
    }

    /**
     * Kondisi perbandingan [column, operator, value].
     *
     * Bila value null, otomatis menjadi node `null` (IS NULL / IS NOT NULL).
     */
    public static function basic(
        string $column,
        string $operator,
        mixed $value,
        string $boolean = self::BOOLEAN_AND
    ): self {
        $column = self::normalizeColumn($column);
        $operator = self::normalizeOperator($operator);

        if ($value === null) {
            if (in_array($operator, ['=', 'IS'], true)) {
                return self::isNull($column, $boolean);
            }

            if (in_array($operator, ['<>', 'IS NOT'], true)) {
                return self::isNull($column, $boolean, true);
            }
        }

        return new self(
            self::TYPE_BASIC,
            self::normalizeBoolean($boolean),
            column: $column,
            operator: $operator,
            value: $value
        );
    }

    /**
     * Pengecekan null: IS NULL, atau IS NOT NULL bila $not = true.
     */
    public static function isNull(
        string $column,
        string $boolean = self::BOOLEAN_AND,
        bool $not = false
    ): self {
        return new self(
            self::TYPE_NULL,
            self::normalizeBoolean($boolean),
            column: self::normalizeColumn($column),
            operator: $not ? 'IS NOT NULL' : 'IS NULL'
        );
    }

    /**
     * Grup kondisi bertingkat, dirender sebagai ( ... ).
     */
    public static function nested(
        WhereBuilder $builder,
        string $boolean = self::BOOLEAN_AND
    ): self {
        return new self(
            self::TYPE_NESTED,
            self::normalizeBoolean($boolean),
            builder: $builder
        );
    }

    /**
     * Membangun clause dari kondisi array pendek.
     *
     * Mendukung bentuk:
     * - [column, operator, value]
     * - [column, value]  (operator dianggap '=')
     *
     * @throws InvalidArgumentException bila bentuk kondisi tidak dikenali.
     */
    public static function fromCondition(array $cond, string $boolean = self::BOOLEAN_AND): self
    {
        $cond = array_values($cond);
        $count = count($cond);

        if ($count !== 2 && $count !== 3) {
            throw new InvalidArgumentException(
                'Kondisi array harus [col, op, val] atau [col, val].'
            );
        }

        if (!is_string($cond[0])) {
            throw new InvalidArgumentException('Kolom pada kondisi where harus berupa string.');
        }

        if ($count === 2) {
            return self::basic($cond[0], '=', $cond[1], $boolean);
        }

        if (!is_string($cond[1])) {
            throw new InvalidArgumentException('Operator pada kondisi where harus berupa string.');
        }

        return self::basic($cond[0], $cond[1], $cond[2], $boolean);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function boolean(): string
    {
        return $this->boolean;
    }

    /**
     * Struktur kanonik node ini. Node nested direkursi menjadi array biasa.
     */
    public function toArray(): array
    {
        $node = [
            'type'    => $this->type,
            'boolean' => $this->boolean,
        ];

        return match ($this->type) {
            self::TYPE_BASIC => $node + [
                'column'   => $this->column,
                'operator' => $this->operator,
                'value'    => $this->value,
            ],
            self::TYPE_NULL => $node + [
                'column'   => $this->column,
                'operator' => $this->operator,
            ],
            self::TYPE_RAW => $node + [
                'sql' => $this->sql,
            ],
            self::TYPE_NESTED => $node + [
                'wheres' => $this->builder->toArray(),
            ],
            default => throw new LogicException('Tipe clause tidak dikenal: ' . $this->type),
        };
    }

    private static function normalizeBoolean(string $boolean): string
    {
        $boolean = strtoupper(trim($boolean));

        if ($boolean !== self::BOOLEAN_AND && $boolean !== self::BOOLEAN_OR) {
            throw new InvalidArgumentException('Boolean where harus AND atau OR.');
        }

        return $boolean;
    }

    private static function normalizeColumn(string $column): string
    {
        $column = trim($column);

        if ($column === '') {
            throw new InvalidArgumentException('Kolom pada kondisi where tidak boleh kosong.');
        }

        return $column;
    }

    /**
     * Operator di-uppercase dan `!=` disatukan ke `<>` (bentuk ANSI).
     * Operator lain diteruskan apa adanya supaya LIKE / IN / BETWEEN tetap jalan.
     */
    private static function normalizeOperator(string $operator): string
    {
        $operator = strtoupper(trim($operator));

        if ($operator === '') {
            throw new InvalidArgumentException('Operator pada kondisi where tidak boleh kosong.');
        }

        return $operator === '!=' ? '<>' : $operator;
    }
}
