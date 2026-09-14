<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DDL\Clauses;

use InvalidArgumentException;

/**
 * Value object untuk SATU kolom pada CREATE TABLE.
 *
 * Berbeda dari WhereClause yang readonly, kelas ini memang MUTABLE: modifier
 * seperti key()/autoIncrement()/nullable() dipasang setelah objeknya dibuat,
 * karena itu cara pemakaiannya:
 *
 *     $table->integer('idKontak')->key()->autoIncrement();
 *
 * Mutasi hanya boleh dilakukan pemanggil di dalam closure Blueprint. Grammar
 * tidak pernah menyentuh objek ini — ia hanya membaca toArray() lewat
 * BlueprintInterface, jadi Grammar tetap tidak bisa memutasi struktur.
 *
 * Struktur kanonik yang dihasilkan toArray():
 *
 *     [
 *         'name' => 'idKontak', 'type' => 'integer',
 *         'length' => null, 'precision' => null, 'scale' => null,
 *         'nullable' => false, 'default' => null,
 *         'key' => true, 'auto_increment' => true, 'unique' => false,
 *     ]
 *
 * Semua key selalu ada supaya Grammar tidak perlu menebak defaultnya.
 *
 * 'type' adalah kosakata semantik (integer, string, text, ...), bukan tipe SQL.
 * Penerjemahan ke dialek driver adalah tugas Grammar masing-masing.
 */
final class ColumnClause {

    private function __construct(
        private readonly string $name,
        private readonly string $type,
        private array $options = []
    ) { }

    public static function make(string $name, string $type, array $options = []): self
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Nama kolom tidak boleh kosong.');
        }

        $type = trim($type);

        if ($type === '') {
            throw new InvalidArgumentException('Tipe kolom tidak boleh kosong.');
        }

        return new self($name, $type, $options);
    }

    /**
     * Nama kolom.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Menandai kolom sebagai primary key.
     */
    public function key(bool $flag = true): static
    {
        $this->options['key'] = $flag;
        return $this;
    }

    /**
     * Menandai kolom sebagai auto increment (AUTO_INCREMENT / IDENTITY).
     */
    public function autoIncrement(bool $flag = true): static
    {
        $this->options['auto_increment'] = $flag;
        return $this;
    }

    public function nullable(bool $flag = true): static
    {
        $this->options['nullable'] = $flag;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->options['default'] = $value;
        return $this;
    }

    public function unique(bool $flag = true): static
    {
        $this->options['unique'] = $flag;
        return $this;
    }

    public function length(int $length): static
    {
        $this->options['length'] = $length;
        return $this;
    }

    /**
     * Struktur kanonik node kolom ini.
     */
    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'type'           => $this->type,
            'length'         => $this->options['length'] ?? null,
            'precision'      => $this->options['precision'] ?? null,
            'scale'          => $this->options['scale'] ?? null,
            'nullable'       => $this->options['nullable'] ?? false,
            'default'        => $this->options['default'] ?? null,
            'key'            => $this->options['key'] ?? false,
            'auto_increment' => $this->options['auto_increment'] ?? false,
            'unique'         => $this->options['unique'] ?? false,
        ];
    }
}
