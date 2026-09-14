<?php 

namespace Selvi\Database\Contracts;

/**
 * Kontrak baca struktur ALTER TABLE untuk Grammar.
 *
 * Sejajar dengan BlueprintInterface pada CREATE: Grammar hanya MEMBACA, dan
 * struktur yang diterimanya sudah kanonik serta netral driver. Berapa statement
 * yang dibutuhkan untuk mewujudkannya — satu (MySQL) atau beberapa (SQL Server) —
 * adalah keputusan Grammar, bukan builder.
 *
 * @see \Selvi\Database\Builder\DDL\AlterBlueprint
 */
interface AlterBlueprintInterface {

    /**
     * Nama tabel yang diubah.
     */
    public function getTable(): string;

    /**
     * Daftar operasi alter, DALAM URUTAN pemanggilan pemakai.
     *
     * Setiap node berbentuk salah satu dari:
     *
     *     ['operation' => 'add',    'column' => <struktur kolom>, 'position' => null|["first" => true]|["after" => "x"]]
     *     ['operation' => 'modify', 'column' => <struktur kolom>, 'position' => null|["first" => true]|["after" => "x"]]
     *     ['operation' => 'drop',   'name' => 'idGrup']
     *     ['operation' => 'rename', 'from' => 'nmKontak', 'to' => 'nama']
     *     ['operation' => 'index',  'name' => 'idx_nama', 'columns' => [...], 'unique' => false]
     *     ['operation' => 'dropIndex', 'name' => 'idx_nama']
     *     ['operation' => 'primary', 'name' => null|string, 'columns' => [...]]
     *     ['operation' => 'dropPrimary', 'name' => null|string]
     *     ['operation' => 'foreign', 'name' => 'fk_x', 'columns' => [...], 'table' => 'grup',
     *      'references' => [...], 'on_delete' => null|string, 'on_update' => null|string]
     *     ['operation' => 'dropForeign', 'name' => 'fk_x']
     *
     * <struktur kolom> sama persis dengan yang dipakai CREATE TABLE
     * (lihat BlueprintInterface::getColumns()), sehingga Grammar bisa memakai
     * ulang perender kolomnya — untuk 'modify' itu justru wajib, karena MySQL
     * menuntut definisi kolom lengkap.
     *
     * Operasi yang tidak didukung driver tertentu diabaikan oleh Grammar-nya
     * masing-masing, bukan ditolak di sini. Itu juga berlaku untuk 'position' pada
     * add/modify: ia hanya HINT letak kolom (FIRST / AFTER) yang dipahami MySQL,
     * dan driver tanpa konsep "kolom ke-n" cukup mengabaikannya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOperations(): array;

}
