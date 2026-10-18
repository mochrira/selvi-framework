<?php 

namespace Selvi\Database\Interfaces;

interface IGrammar {

    /** === Start of Evaluated */

    /**
     * Menerjemahkan query builder menjadi SQL untuk pengambilan data ke database
     * 
     * @param IQueryBuilder $builder Instance query builder yang akan diterjemahkan
     * @return string SQL yang dihasilkan
     */

    function compileSelect(IQueryBuilder $builder): string;

    /**
     * Menerjemahkan parameter menjadi SQL untuk memasukkan data ke database
     * 
     * @param string $table Nama tabel target
     * @param array<int, string> $columns Daftar nama kolom
     * @param array<int, array<string, mixed>> $rows Baris-baris data yang sudah dinormalkan
     */
    function compileInsert(string $table, array $columns, array $rows): string;

    /**
     * Menerjemahkan parameter menjadi SQL untuk mengupdate data ke database
     * 
     * @param string $table Nama tabel target
     * @param array<string, mixed> $values Pasangan nama kolom => nilai baru
     * @param array $wheres AST kondisi WHERE
     */
    function compileUpdate(string $table, array $values, array $wheres): string;

    /**
     * Menerjemahkan parameter menjadi SQL untuk menghapus data ke database
     * 
     * @param string $table Nama tabel target
     * @param array $wheres AST kondisi WHERE
    */
    function compileDelete(string $table, array $wheres): string;
    
    /** == End of Evaluated */


    function compileCreateTable(IBlueprint $blueprint): string;
    function compileDropTable(string $table, bool $ifExists): string;
    function compileRenameTable(string $from, string $to): string;
    function compileTruncateTable(string $table): string;

    /**
     * @return string|string[]
     */
    function compileAlterTable(IAlterBlueprint $blueprint): string|array;

}