<?php 

namespace Selvi\Database\Contracts;

/**
 * Kontrak baca QueryBuilder untuk Grammar.
 *
 * Grammar hanya membaca struktur query dari sini; QueryBuilder tetap bertanggung
 * jawab menyusunnya. Anggota tulis (table(), select(), where(), orWhere(), get())
 * sengaja tidak dideklarasikan supaya Grammar tidak bisa memutasi state builder.
 *
 * Struktur WHERE yang dikembalikan wheres() sudah kanonik dan netral driver,
 * jadi setiap Grammar mengonsumsi pola yang sama dan hanya berbeda saat render.
 *
 * @see \Selvi\Database\Builder\WhereClause bentuk lengkap tiap node dan aturan normalisasinya.
 */
interface QueryBuilderInterface {

    /**
     * Nama tabel sumber query (bagian FROM).
     */
    public function getTable(): string;

    /**
     * Daftar kolom untuk SELECT. Array kosong berarti semua kolom.
     */
    public function getColumns(): array;

    /**
     * Struktur kanonik (AST) kondisi WHERE. Array kosong berarti tanpa kondisi.
     *
     * Setiap elemen adalah satu node, misalnya:
     *
     *     ['type' => 'basic', 'boolean' => 'AND', 'column' => 'a', 'operator' => '=', 'value' => 1]
     *
     * Node bertipe nested menaruh node anaknya secara rekursif pada key 'wheres'.
     *
     * @see \Selvi\Database\Builder\WhereClause daftar tipe node dan aturan normalisasinya.
     */
    public function wheres(): array;

}