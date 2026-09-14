<?php 

namespace Selvi\Database\Contracts;

use Closure;

/**
 * Kontrak operasi DDL.
 *
 * Untuk sementara hanya mencakup CREATE TABLE; drop/alter/rename/truncate akan
 * menyusul. GrammarInterface tetap kontrak terpisah karena perannya berbeda:
 * SchemaBuilderInterface adalah API yang dipanggil pengguna, sedangkan Grammar
 * mengonsumsi struktur lewat BlueprintInterface.
 *
 * @see \Selvi\Database\Contracts\BlueprintInterface
 */
interface SchemaBuilderInterface {

    /**
     * Membuat tabel baru.
     *
     * Callback $definition menerima Blueprint dan dipakai untuk mendefinisikan
     * kolom, mis. $table->integer('id')->key()->autoIncrement().
     *
     * @param ?Closure(BlueprintInterface): void $definition
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function create(string $table, ?Closure $definition = null): bool;

    /**
     * Menghapus tabel — pasangan create(), biasanya dipakai pada arah 'down'.
     *
     * $ifExists default true supaya aman dijalankan berulang, sejalan dengan
     * CREATE TABLE IF NOT EXISTS. Bagaimana 'IF EXISTS' diungkapkan tetap urusan
     * Grammar, karena tidak semua driver memilikinya (SQL Server memakai
     * IF OBJECT_ID(...) IS NOT NULL).
     *
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function drop(string $table, bool $ifExists = true): bool;

}
