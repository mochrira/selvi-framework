<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DDL;

use Selvi\Database\Builder\DDL\Clauses\ColumnClause;

/**
 * Kosakata tipe kolom yang dipakai bersama Blueprint (CREATE) dan AlterBlueprint
 * (ALTER).
 *
 * Semua method di sini hanya menerjemahkan nama + tipe semantik menjadi
 * ColumnClause, lalu menyerahkannya ke add() milik kelas yang memakai trait ini.
 * Di situlah bedanya: Blueprint mencatatnya sebagai kolom tabel, AlterBlueprint
 * mencatatnya sebagai operasi add/modify.
 *
 * Menambah tipe kolom baru cukup di satu tempat ini, sehingga CREATE dan ALTER
 * tidak mungkin punya daftar tipe yang berbeda.
 */
trait HasColumnTypes {

    /**
     * Mencatat satu kolom, lalu mengembalikan ColumnClause-nya supaya modifier
     * bisa dirantai.
     *
     * @param array<string, mixed> $options Nilai awal length/precision/scale/nullable.
     */
    abstract protected function add(string $name, string $type, array $options = []): ColumnClause;

    public function integer(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'integer', ['nullable' => $nullable]);
    }

    public function bigInteger(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'bigInteger', ['nullable' => $nullable]);
    }

    public function string(string $name, int $length = 255, bool $nullable = false): ColumnClause {
        return $this->add($name, 'string', ['length' => $length, 'nullable' => $nullable]);
    }

    public function text(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'text', ['nullable' => $nullable]);
    }

    public function boolean(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'boolean', ['nullable' => $nullable]);
    }

    public function decimal(string $name, int $precision = 10, int $scale = 0, bool $nullable = false): ColumnClause {
        return $this->add($name, 'decimal', [
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => $nullable,
        ]);
    }

    public function float(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'float', ['nullable' => $nullable]);
    }

    public function date(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'date', ['nullable' => $nullable]);
    }

    public function datetime(string $name, bool $nullable = false): ColumnClause {
        return $this->add($name, 'datetime', ['nullable' => $nullable]);
    }

}
