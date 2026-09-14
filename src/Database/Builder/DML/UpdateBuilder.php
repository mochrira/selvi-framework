<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML;

use InvalidArgumentException;

/**
 * Pengelola dan normalisasi data untuk operasi UPDATE.
 *
 * Menerima array asosiatif: ['nmKontak' => 'Baru', 'idGrup' => 2]
 */
class UpdateBuilder {

    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function __construct(array $values)
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Data update tidak boleh kosong.');
        }

        $this->parse($values);
    }

    private function parse(array $values): void
    {
        $this->values = [];

        foreach ($values as $column => $value) {
            if (!is_string($column) || trim($column) === '') {
                throw new InvalidArgumentException('Key data update harus berupa string nama kolom.');
            }
            $this->values[trim($column)] = $value;
        }

        if (empty($this->values)) {
            throw new InvalidArgumentException('Data update tidak berisi kolom yang valid.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        return array_keys($this->values);
    }

    /**
     * Struktur kanonik (AST) dari data update.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }

}
