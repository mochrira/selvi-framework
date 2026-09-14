<?php

namespace Selvi\Database\Builder\DDL;

use Closure;
use RuntimeException;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Contracts\SchemaBuilderInterface;
use Selvi\Database\DatabaseManager;

/**
 * Entry point DDL.
 *
 * Menjalankan callback definisi, lalu meminta Grammar menyusun SQL dan
 * mengeksekusinya lewat ConnectionInterface:
 *
 *     DB::schema()->create('kontak', function (Blueprint $table) {
 *         $table->integer('idKontak')->key()->autoIncrement();
 *         $table->string('nmKontak', 100)->nullable();
 *         $table->text('content')->nullable();
 *     });
 *
 * Bagian struktur ada di Blueprint; kelas ini tidak menyimpan definisi kolom.
 */
class SchemaBuilder implements SchemaBuilderInterface {

    private string $connectionName = '';

    private ?ConnectionInterface $injected = null;

    /**
     * Menentukan koneksi: nama yang terdaftar di DatabaseManager, atau objek
     * ConnectionInterface langsung bila ingin disuntikkan.
     */
    public function useConnection(string|ConnectionInterface $connection): static {
        if($connection instanceof ConnectionInterface) {
            $this->injected = $connection;
        } else {
            $this->connectionName = $connection;
        }

        return $this;
    }

    public function connection(): ConnectionInterface {
        if($this->injected !== null) return $this->injected;

        $db = $this->connectionName !== '' ? DatabaseManager::get($this->connectionName) : DatabaseManager::default();

        if($db === null) {
            throw new RuntimeException('Koneksi database belum diatur.');
        }

        return $db;
    }

    /**
     * Membuat tabel baru. Statement dijalankan langsung setelah callback selesai.
     */
    public function create(string $table, ?Closure $definition = null): bool {
        $blueprint = new Blueprint($table);

        if($definition !== null) {
            $definition($blueprint);
        }

        $connection = $this->connection();

        $sql = $connection->grammar()->compileCreateTable($blueprint);

        return $connection->query($sql) !== false;
    }

    /**
     * SQL yang akan dijalankan, tanpa mengeksekusinya.
     */
    public function compileCreate(string $table, ?Closure $definition = null): string {
        $blueprint = new Blueprint($table);

        if($definition !== null) {
            $definition($blueprint);
        }

        return $this->connection()->grammar()->compileCreateTable($blueprint);
    }

    /**
     * Menghapus tabel. Pasangan create(), dipakai untuk membalik migrasi.
     */
    public function drop(string $table, bool $ifExists = true): bool {
        $connection = $this->connection();

        $sql = $connection->grammar()->compileDropTable($table, $ifExists);

        return $connection->query($sql) !== false;
    }

    /**
     * SQL DROP yang akan dijalankan, tanpa mengeksekusinya.
     */
    public function compileDrop(string $table, bool $ifExists = true): string {
        return $this->connection()->grammar()->compileDropTable($table, $ifExists);
    }

}
