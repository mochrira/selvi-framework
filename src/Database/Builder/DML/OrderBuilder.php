<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML;

use InvalidArgumentException;
use Selvi\Database\Builder\DML\Clauses\OrderClause;

class OrderBuilder {

    /**
     * @var OrderClause[]
     */
    protected array $clauses = [];

    /**
     * Menambahkan klausa order dengan format fleksibel.
     *
     * Contoh pemanggilan:
     * - order('nama', 'DESC')
     * - order(['nama' => 'DESC', 'id' => 'ASC'])
     * - order(['nama DESC', 'id ASC'])
     * - order('nama DESC, id ASC')
     */
    public function order(mixed ...$args): static
    {
        if (empty($args)) {
            return $this;
        }

        // Dua argumen: order('column', 'DESC')
        if (count($args) === 2 && is_string($args[0]) && is_string($args[1])) {
            return $this->orderBy($args[0], $args[1]);
        }

        $input = $args[0];

        // String tunggal: order('a') atau order('a DESC, b ASC')
        if (is_string($input)) {
            foreach (explode(',', $input) as $part) {
                $part = trim($part);
                if ($part === '') continue;

                $tokens = preg_split('/\s+/', $part);
                $col = $tokens[0];
                $dir = $tokens[1] ?? 'ASC';
                $this->orderBy($col, $dir);
            }
            return $this;
        }

        // Array: order(['a' => 'DESC']) atau order(['a DESC', 'b'])
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                if (is_string($key)) {
                    // ['a' => 'DESC']
                    $this->orderBy($key, (string)$value);
                } elseif (is_string($value)) {
                    // ['a DESC', 'b']
                    $tokens = preg_split('/\s+/', trim($value));
                    $col = $tokens[0];
                    $dir = $tokens[1] ?? 'ASC';
                    $this->orderBy($col, $dir);
                } else {
                    throw new InvalidArgumentException('Format elemen order dalam array tidak valid.');
                }
            }
            return $this;
        }

        throw new InvalidArgumentException('Format order tidak dikenali.');
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->clauses[] = OrderClause::create($column, $direction);
        return $this;
    }

    public function hasOrders(): bool
    {
        return !empty($this->clauses);
    }

    /**
     * Struktur kanonik (AST) dari seluruh order.
     *
     * @return array<int, array{column: string, direction: string}>
     */
    public function toArray(): array
    {
        $nodes = [];

        foreach ($this->clauses as $clause) {
            $nodes[] = $clause->toArray();
        }

        return $nodes;
    }

}
