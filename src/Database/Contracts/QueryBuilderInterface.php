<?php 

namespace Selvi\Database\Contracts;

/**
 * Kontrak baca QueryBuilder untuk Grammar.
 *
 * Grammar hanya membaca struktur query dari sini; QueryBuilder tetap bertanggung
 * jawab menyusunnya. Anggota tulis (table(), select(), where(), orWhere(), get())
 * sengaja tidak dideklarasikan supaya Grammar tidak bisa memutasi state builder.
 *
 * Struktur WHERE (wheres()) dan JOIN (joins()) yang dikembalikan sudah kanonik
 * dan netral driver, jadi setiap Grammar mengonsumsi pola yang sama dan hanya
 * berbeda saat render.
 *
 * @see \Selvi\Database\Builder\WhereClause bentuk lengkap tiap node dan aturan normalisasinya.
 * @see \Selvi\Database\Builder\JoinClause bentuk kanonik daftar join.
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

    /**
     * Struktur kanonik (AST) daftar JOIN. Array kosong berarti tanpa join.
     *
     * Setiap elemen adalah satu join, misalnya:
     *
     *     ['join' => 'LEFT', 'table' => 'another', 'on' => 'another.colA = table.colA']
     *
     * @see \Selvi\Database\Builder\JoinClause bentuk node dan aturan normalisasinya.
     */
    public function joins(): array;

    /**
     * Daftar kolom GROUP BY. Array kosong berarti tanpa grouping.
     *
     * @return string[]
     */
    public function groups(): array;

    /**
     * Nilai LIMIT query, atau null jika tidak dibatasi.
     */
    public function getLimit(): ?int;

    /**
     * Nilai OFFSET query, atau null jika tidak ada offset.
     */
    public function getOffset(): ?int;

}