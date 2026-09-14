<?php 

namespace Selvi\Database\Contracts;

/**
 * Menerjemahkan isi QueryBuilder menjadi SQL sesuai dialek driver.
 *
 * Kontrak penting:
 * - Grammar hanya MEMBACA builder (lihat QueryBuilderInterface). Compile tidak
 *   boleh mengubah state builder, supaya bisa dipanggil berulang dengan hasil sama.
 * - Grammar menerima struktur WHERE yang sudah kanonik lewat $builder->wheres(),
 *   sehingga setiap driver mengonsumsi pola yang sama dan hanya berbeda saat render.
 * - Output saat ini berupa string SQL tanpa parameter binding, jadi setiap value
 *   harus dikonversi menjadi literal oleh SanitizerInterface milik driver.
 *
 * @see \Selvi\Database\Contracts\QueryBuilderInterface
 * @see \Selvi\Database\Builder\DML\Clauses\WhereClause
 */
interface GrammarInterface {

    /**
     * Menghasilkan satu statement SELECT lengkap untuk driver ini.
     */
    function compileSelect(QueryBuilderInterface $builder): string;

    /**
     * Menghasilkan satu statement INSERT lengkap (mendukung baris tunggal maupun batch/bulk).
     *
     * @param string $table Nama tabel target
     * @param array<int, string> $columns Daftar nama kolom
     * @param array<int, array<string, mixed>> $rows Baris-baris data yang sudah dinormalkan
     */
    function compileInsert(string $table, array $columns, array $rows): string;

    /**
     * Menghasilkan satu statement UPDATE lengkap (wajib memiliki kondisi WHERE).
     *
     * @param string $table Nama tabel target
     * @param array<string, mixed> $values Pasangan nama kolom => nilai baru
     * @param array $wheres AST kondisi WHERE
     */
    function compileUpdate(string $table, array $values, array $wheres): string;

    /**
     * Menghasilkan satu statement DELETE lengkap (wajib memiliki kondisi WHERE).
     *
     * @param string $table Nama tabel target
     * @param array $wheres AST kondisi WHERE
     */
    function compileDelete(string $table, array $wheres): string;

    /**
     * Menghasilkan satu statement CREATE TABLE lengkap untuk driver ini.
     *
     * Struktur kolom sudah kanonik lewat BlueprintInterface: tipe semantik seperti
     * "integer"/"string" plus flag nullable/key/auto_increment. Menerjemahkannya ke
     * dialek driver — termasuk hal yang tidak seragam antar driver seperti
     * AUTO_INCREMENT vs IDENTITY, atau IF NOT EXISTS yang tidak ada di SQL Server —
     * adalah tugas Grammar.
     */
    function compileCreateTable(BlueprintInterface $blueprint): string;

    /**
     * Menghasilkan satu statement DROP TABLE lengkap untuk driver ini.
     *
     * Driver yang tidak mengenal "IF EXISTS" menanganinya di sini — misalnya
     * SQL Server perlu membungkusnya dengan IF OBJECT_ID(..., 'U') IS NOT NULL.
     */
    function compileDropTable(string $table, bool $ifExists): string;

    /**
     * Menghasilkan satu statement RENAME TABLE lengkap untuk driver ini.
     *
     * SQL Server tidak punya DDL rename dan memakai sp_rename, jadi bentuk
     * statement-nya memang berbeda antar dialek meski hasilnya sama.
     */
    function compileRenameTable(string $from, string $to): string;

    /**
     * Menghasilkan satu statement TRUNCATE TABLE lengkap untuk driver ini.
     */
    function compileTruncateTable(string $table): string;

    /**
     * Menghasilkan statement ALTER TABLE untuk driver ini.
     *
     * Tipe kembaliannya string|array karena tidak semua driver bisa mewujudkan
     * banyak perubahan sekaligus dalam satu statement — SQL Server, misalnya,
     * butuh satu ALTER TABLE per kolom dan memakai sp_rename untuk mengganti nama
     * kolom. SchemaBuilder yang mengeksekusi hasilnya satu per satu, berurutan.
     *
     * Operasi yang tidak didukung driver bersangkutan cukup diabaikan.
     *
     * @return string|string[]
     */
    function compileAlterTable(AlterBlueprintInterface $blueprint): string|array;

}