<?php 

namespace Selvi\Database\Contracts;

use Closure;

/**
 * Kontrak operasi DDL.
 *
 * Saat ini mencakup CREATE TABLE, DROP TABLE, RENAME TABLE, dan TRUNCATE. Operasi
 * ALTER (add/modify/drop/rename kolom) serta index dan foreign key akan menyusul.
 * GrammarInterface tetap kontrak terpisah karena perannya berbeda:
 * SchemaBuilderInterface adalah API yang dipanggil pengguna, sedangkan Grammar
 * mengonsumsi struktur lewat BlueprintInterface.
 *
 * Catatan eksekusi: DDL tidak transaksional di MySQL — setiap statement langsung
 * di-commit, sehingga operasi yang gagal di tengah bisa meninggalkan struktur
 * setengah jadi. Untuk sekarang itu dibiarkan apa adanya; jalankan migrasi pada
 * database yang sudah dibackup (lihat juga SchemaBuilder::execute()).
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

    /**
     * Mengganti nama tabel.
     *
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function rename(string $table, string $newTable): bool;

    /**
     * Mengosongkan tabel — seluruh baris dihapus, struktur tetap.
     *
     * @return bool true bila statement berhasil dieksekusi.
     */
    public function truncate(string $table): bool;

    /**
     * Mengubah struktur tabel yang sudah ada.
     *
     * Callback $definition menerima AlterBlueprint, mis.
     * $table->string('nm', 200)->nullable()->change() atau
     * $table->dropColumn('idGrup'). Operasi dijalankan dalam urutan pendaftarannya.
     *
     * @param Closure(AlterBlueprintInterface): void $definition
     * @return bool true bila seluruh statement berhasil dieksekusi.
     */
    public function alter(string $table, Closure $definition): bool;

}
