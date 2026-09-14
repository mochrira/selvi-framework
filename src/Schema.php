<?php

declare(strict_types=1);

namespace Selvi;

use Closure;
use Selvi\Database\Builder\DDL\SchemaBuilder;
use Selvi\Database\Builder\DML\QueryBuilder;
use Selvi\Database\Contracts\ConnectionInterface;

/**
 * Handle database yang sudah terikat pada satu koneksi.
 *
 * Bentuk "siap pakai" dari SchemaBuilder: pemanggil tidak perlu menyebut koneksi
 * lagi di setiap operasi.
 *
 * Di dalam file migrasi, objek ini sudah terisi koneksi sesuai nama yang
 * dicantumkan saat perintah dijalankan — `db:migrate main up` berarti Schema
 * memakai koneksi 'main':
 *
 *     return function (Schema $schema, string $direction) {
 *
 *         if($direction === 'up') {
 *             $schema->create('kontak', function (Blueprint $table) {
 *                 $table->integer('idKontak')->key()->autoIncrement();
 *             });
 *         }
 *
 *         if($direction === 'down') {
 *             $schema->drop('kontak');
 *         }
 *     };
 *
 * Objek ini juga bisa dipakai di luar migrasi: `(new Schema('main'))->create(...)`.
 *
 * DDL yang tersedia: create(), drop(), rename(), truncate(), alter(). Operasi lain
 * (index, primary key, foreign key) belum ada — sementara pakai connection().
 *
 * @see \Selvi\Database\Builder\DDL\SchemaBuilder
 */
class Schema {

    private SchemaBuilder $builder;

    /**
     * @param string|ConnectionInterface $connection Nama koneksi di DatabaseManager,
     *                                               atau objek koneksi langsung.
     *                                               Kosong berarti koneksi default.
     */
    public function __construct(string|ConnectionInterface $connection = '') {
        $this->builder = (new SchemaBuilder())->useConnection($connection);
    }

    /**
     * Mengganti koneksi yang dipakai.
     */
    public function useConnection(string|ConnectionInterface $connection): static {
        $this->builder->useConnection($connection);
        return $this;
    }

    /**
     * Primitif koneksi: query mentah, transaksi, getConfig, dll.
     *
     * Ini juga jalur darurat untuk operasi DDL yang belum punya API di sini
     * (alter kolom, index, foreign key), dengan konsekuensi SQL-nya spesifik driver. 
     */
    public function connection(): ConnectionInterface {
        return $this->builder->connection();
    }

    /**
     * Membuat tabel baru.
     *
     * @param ?Closure(Blueprint): void $definition
     */
    public function create(string $table, ?Closure $definition = null): bool {
        return $this->builder->create($table, $definition);
    }

    /**
     * Menghapus tabel. Pasangan create(), biasanya dipakai pada arah 'down'.
     * $ifExists default true supaya aman dijalankan berulang.
     */
    public function drop(string $table, bool $ifExists = true): bool {
        return $this->builder->drop($table, $ifExists);
    }

    /**
     * Mengganti nama tabel.
     */
    public function rename(string $table, string $newTable): bool {
        return $this->builder->rename($table, $newTable);
    }

    /**
     * Mengosongkan tabel — seluruh baris dihapus, struktur tetap.
     */
    public function truncate(string $table): bool {
        return $this->builder->truncate($table);
    }

    /**
     * Mengubah struktur tabel yang sudah ada.
     *
     *     $schema->alter('kontak', function (AlterBlueprint $table) {
     *         $table->integer('umur')->nullable();
     *         $table->string('nmKontak', 200)->nullable()->change();
     *         $table->renameColumn('nmKontak', 'nama');
     *         $table->dropColumn('idGrup');
     *     });
     */
    public function alter(string $table, Closure $definition): bool {
        return $this->builder->alter($table, $definition);
    }

    /**
     * DML (SELECT/INSERT/UPDATE/DELETE) pada koneksi ini.
     *
     * Dipakai untuk migrasi data, mis. $schema->table('grup')->insert([...]).
     */
    public function table(string $name): QueryBuilder {
        return DB::table($name)->useConnection($this->connection());
    }

}
