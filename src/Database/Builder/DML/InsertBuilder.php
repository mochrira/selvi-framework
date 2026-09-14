<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DML;

use InvalidArgumentException;

/**
 * Pengelola dan normalisasi data untuk operasi INSERT.
 *
 * Mendukung format:
 * - Single row: ['nmKontak' => 'John', 'idGrup' => 1]
 * - Multi rows: [['nmKontak' => 'John', 'idGrup' => 1], ['nmKontak' => 'Jane', 'idGrup' => 2]]
 */
class InsertBuilder {

    private string $table;

    /**
     * @var array<int, string>
     */
    private array $columns = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $rows = [];

    public function __construct(string $table, array $values)
    {
        $this->table = trim($table);
        if ($this->table === '') {
            throw new InvalidArgumentException('Nama tabel untuk insert tidak boleh kosong.');
        }

        if (empty($values)) {
            throw new InvalidArgumentException('Data insert tidak boleh kosong.');
        }

        $this->parse($values);
    }

    private function parse(array $values): void
    {
        // Deteksi apakah single row (array asosiatif) atau multi row (array of array)
        $isSingleRow = false;
        foreach (array_keys($values) as $key) {
            if (is_string($key)) {
                $isSingleRow = true;
                break;
            }
        }

        $rawRows = $isSingleRow ? [$values] : array_values($values);

        // Kumpulkan union dari semua nama kolom
        $columnsMap = [];
        foreach ($rawRows as $row) {
            if (!is_array($row) || empty($row)) {
                throw new InvalidArgumentException('Setiap baris data insert harus berupa array asosiatif.');
            }
            foreach (array_keys($row) as $column) {
                $columnsMap[(string)$column] = true;
            }
        }

        $this->columns = array_keys($columnsMap);

        // Normalkan setiap baris agar memiliki seluruh key kolom (default null jika missing)
        $this->rows = [];
        foreach ($rawRows as $row) {
            $normalizedRow = [];
            foreach ($this->columns as $column) {
                $normalizedRow[$column] = $row[$column] ?? null;
            }
            $this->rows[] = $normalizedRow;
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Struktur kanonik (AST) dari data insert.
     *
     * @return array{table: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'table'   => $this->table,
            'columns' => $this->columns,
            'rows'    => $this->rows,
        ];
    }

}
