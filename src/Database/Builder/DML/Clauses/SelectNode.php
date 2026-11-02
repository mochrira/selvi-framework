<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML\Clauses;

use InvalidArgumentException;

/**
 * Pabrik node AST untuk SELECT.
 *
 * Node yang dihasilkan adalah array biasa supaya Grammar tiap driver dapat
 * merendernya tanpa bergantung pada objek builder.
 */
final class SelectNode {

    public const TYPE_COLUMN = 'column';
    public const TYPE_FUNCTION = 'function';
    public const TYPE_LITERAL = 'literal';
    public const TYPE_RAW = 'raw';

    /**
     * @return array{type: string, table: ?string, column: string, alias: ?string}
     */
    public static function column(string $column, ?string $table = null, ?string $alias = null): array {
        $column = trim($column);
        if($column === '') {
            throw new InvalidArgumentException('Nama kolom select tidak boleh kosong.');
        }

        return [
            'type' => self::TYPE_COLUMN,
            'table' => $table,
            'column' => $column,
            'alias' => $alias,
        ];
    }

    /**
     * @param list<array> $arguments
     * @return array{type: string, name: string, arguments: array, alias: ?string}
     */
    public static function function(string $name, array $arguments = [], ?string $alias = null): array {
        $name = trim($name);
        if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Nama fungsi SQL '{$name}' tidak valid.");
        }

        foreach($arguments as $argument) {
            if(!is_array($argument)) {
                throw new InvalidArgumentException(
                    'Argumen fungsi SQL harus berupa node SelectNode.'
                );
            }
        }

        return [
            'type' => self::TYPE_FUNCTION,
            'name' => $name,
            'arguments' => array_values($arguments),
            'alias' => $alias,
        ];
    }

    /**
     * @return array{type: string, value: mixed, alias: ?string}
     */
    public static function literal(mixed $value, ?string $alias = null): array {
        return [
            'type' => self::TYPE_LITERAL,
            'value' => $value,
            'alias' => $alias,
        ];
    }

    /**
     * @return array{type: string, sql: string, alias: ?string}
     */
    public static function raw(string $sql, ?string $alias = null): array {
        $sql = trim($sql);
        if($sql === '') {
            throw new InvalidArgumentException('Raw select tidak boleh kosong.');
        }

        return [
            'type' => self::TYPE_RAW,
            'sql' => $sql,
            'alias' => $alias,
        ];
    }

    /**
     * Node untuk argumen COUNT(*).
     *
     * @return array{type: string, sql: string, alias: null}
     */
    public static function star(): array {
        return self::raw('*');
    }

}
