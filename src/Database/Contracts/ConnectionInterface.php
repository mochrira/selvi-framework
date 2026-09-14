<?php 

namespace Selvi\Database\Contracts;

/**
 * Kontrak minimum sebuah koneksi database.
 *
 * Hanya memuat primitif yang dibutuhkan lapisan builder: menyediakan Grammar
 * untuk menyusun SQL, mengeksekusi SQL mentah, mengambil last insert id, serta
 * mengelola koneksi dan transaksi.
 *
 * Kemampuan query-building (select/where/join/...) dan sanitizer() sengaja TIDAK
 * ada di sini. Dengan begitu QueryBuilder dan Grammar cukup bergantung pada
 * permukaan yang sempit ini.
 */
interface ConnectionInterface {

    /**
     * Grammar milik driver ini, dipakai builder untuk menyusun SQL.
     */
    public function grammar() : GrammarInterface;

    /**
     * Mengeksekusi SQL mentah dan mengembalikan hasilnya.
     */
    public function query(string $sql) : ResultInterface | bool;

    /**
     * Nilai AUTO_INCREMENT terakhir pada koneksi ini.
     */
    public function lastId() : int;

    public function connect() : bool;

    public function disconnect() : bool;

    /**
     * Konfigurasi koneksi, atau null bila belum diatur.
     */
    public function getConfig() : array | null;

    public function startTransaction() : bool;

    public function commit() : bool;

    public function rollback() : bool;

}
