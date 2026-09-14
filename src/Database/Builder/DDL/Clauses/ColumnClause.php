<?php

declare(strict_types=1);

namespace Selvi\Database\Builder\DDL\Clauses;

use InvalidArgumentException;

/**
 * Value object untuk SATU kolom pada CREATE TABLE dan ALTER TABLE.
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
 *
 * Di dalam ALTER TABLE objek ini juga membawa OPERASI-nya (add atau modify) lewat
 * change(), dan POSISI-nya lewat after()/first(). Keduanya sengaja TIDAK ikut ke
 * toArray(): toArray() tetap murni definisi kolom, sehingga kontrak CREATE TABLE
 * tidak berubah sama sekali. Yang membacanya adalah AlterBlueprint, yang
 * menyusunnya menjadi node operasi.
 */
final class ColumnClause {

    /**
     * Operasi kolom di dalam ALTER TABLE: add untuk kolom baru, modify untuk
     * mendefinisikan ulang kolom yang sudah ada.
     */
    public const OPERATION_ADD = 'add';
    public const OPERATION_MODIFY = 'modify';

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
     * Mengubah peran kolom dari ADD menjadi MODIFY.
     *
     * Dipakai di dalam closure alter():
     *
     *     $table->string('nmKontak', 200)->nullable()->change();
     *
     * ATRIBUTNYA HARUS DISEBUT LENGKAP. MySQL mengganti definisi kolomnya, bukan
     * menambal satu atribut — atribut yang tidak disebut akan hilang. Ini berlaku
     * di semua driver, supaya maksudnya tidak berbeda-beda antar driver.
     */
    public function change(bool $flag = true): static
    {
        $this->options['operation'] = $flag ? self::OPERATION_MODIFY : self::OPERATION_ADD;
        return $this;
    }

    /**
     * Operasi kolom ini: add (default) atau modify.
     */
    public function operation(): string
    {
        return $this->options['operation'] ?? self::OPERATION_ADD;
    }

    /**
     * Menempatkan kolom SETELAH kolom lain.
     *
     *     $table->integer('umur')->after('nmKontak');
     *
     * Hanya berpengaruh di dalam alter(), baik untuk kolom baru maupun kolom yang
     * di-change(). create() mengabaikannya karena urutan kolom di CREATE TABLE
     * sudah ditentukan urutan pemanggilan. Driver yang tidak mengenal konsep
     * "kolom ke-n" (SQL Server, PostgreSQL) juga mengabaikannya.
     */
    public function after(string $column): static
    {
        $column = trim($column);

        if($column === '') {
            throw new InvalidArgumentException('Nama kolom acuan untuk after() tidak boleh kosong.');
        }

        $this->options['after'] = $column;
        unset($this->options['first']);

        return $this;
    }

    /**
     * Menempatkan kolom di posisi paling depan. Pasangan dari after().
     */
    public function first(): static
    {
        $this->options['first'] = true;
        unset($this->options['after']);

        return $this;
    }

    /**
     * Posisi kolom yang diminta, atau null bila tidak ditentukan.
     *
     *     ['first' => true]
     *     ['after' => 'nmKontak']
     *
     * Seperti operation(), nilai ini juga tidak ikut ke toArray().
     */
    public function position(): ?array
    {
        if($this->options['first'] ?? false) return ['first' => true];
        if(isset($this->options['after'])) return ['after' => $this->options['after']];

        return null;
    }

    /**
     * Struktur kanonik node kolom ini.
     *
     * Operation dan position sengaja tidak disertakan — lihat catatan di docblock
     * kelas.
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
