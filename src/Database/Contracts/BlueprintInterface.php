<?php 

namespace Selvi\Database\Contracts;

/**
 * Kontrak baca struktur CREATE TABLE untuk Grammar.
 *
 * Sama seperti QueryBuilderInterface pada DML: Grammar hanya MEMBACA, tidak boleh
 * memutasi. Struktur kolom yang dikembalikan getColumns() sudah kanonik dan netral
 * driver — tipe semantik seperti "integer"/"string" beserta flag nullable, key,
 * dan auto_increment — sehingga setiap Grammar mengonsumsi pola yang sama dan
 * hanya berbeda saat menerjemahkannya ke dialek masing-masing.
 *
 * @see \Selvi\Database\Builder\DDL\Clauses\ColumnClause bentuk tiap node kolom.
 */
interface BlueprintInterface {

    /**
     * Nama tabel yang dibuat.
     */
    public function getTable(): string;

    /**
     * Daftar kolom dalam bentuk kanonik.
     *
     * Setiap elemen berbentuk:
     *
     *     [
     *         'name' => 'idKontak', 'type' => 'integer',
     *         'length' => null, 'precision' => null, 'scale' => null,
     *         'nullable' => false, 'default' => null,
     *         'key' => true, 'auto_increment' => true, 'unique' => false,
     *     ]
     *
     * Semua key selalu ada supaya Grammar tidak perlu menebak nilai defaultnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getColumns(): array;

    /**
     * Apakah statement memakai "IF NOT EXISTS".
     */
    public function hasIfNotExists(): bool;

}
