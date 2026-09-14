<?php

namespace Selvi\Database\Builder\DDL;

use Closure;
use InvalidArgumentException;
use RuntimeException;
use Selvi\Database\Contracts\ConnectionInterface;
use Selvi\Database\Contracts\SchemaBuilderInterface;
use Selvi\Database\Manager;

/**
 * Entry point DDL.
 *
 * Menjalankan callback definisi, lalu meminta Grammar menyusun SQL dan
 * mengeksekusinya lewat ConnectionInterface:
 *
 *     (new Selvi\Database\Schema('main'))->create('kontak', function (Blueprint $table) {
 *         $table->integer('idKontak')->key()->autoIncrement();
 *         $table->string('nmKontak', 100)->nullable();
 *         $table->text('content')->nullable();
 *     });
 *
 * Bagian struktur ada di Blueprint; kelas ini tidak menyimpan definisi kolom.
 *
 * Semua operasi bermuara pada satu jalur eksekusi — lihat execute() — sehingga
 * penanganan multi-statement hanya ada di satu tempat.
 */
class SchemaBuilder implements SchemaBuilderInterface {

    private string $connectionName = '';

    private ?ConnectionInterface $injected = null;

    /**
     * Menentukan koneksi: nama yang terdaftar di Manager, atau objek
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

        $db = $this->connectionName !== '' ? Manager::get($this->connectionName) : Manager::default();

        if($db === null) {
            throw new RuntimeException('Koneksi database belum diatur.');
        }

        return $db;
    }

    /**
     * Membuat tabel baru. Statement dijalankan langsung setelah callback selesai.
     */
    public function create(string $table, ?Closure $definition = null): bool {
        return $this->execute($this->compileCreate($table, $definition));
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
        return $this->execute($this->compileDrop($table, $ifExists));
    }

    /**
     * SQL DROP yang akan dijalankan, tanpa mengeksekusinya.
     */
    public function compileDrop(string $table, bool $ifExists = true): string {
        return $this->connection()->grammar()->compileDropTable($table, $ifExists);
    }

    /**
     * Mengganti nama tabel.
     */
    public function rename(string $table, string $newTable): bool {
        return $this->execute($this->compileRename($table, $newTable));
    }

    /**
     * SQL RENAME yang akan dijalankan, tanpa mengeksekusinya.
     */
    public function compileRename(string $table, string $newTable): string {
        return $this->connection()->grammar()->compileRenameTable($table, $newTable);
    }

    /**
     * Mengosongkan tabel — seluruh baris dihapus, struktur tetap.
     */
    public function truncate(string $table): bool {
        return $this->execute($this->compileTruncate($table));
    }

    /**
     * SQL TRUNCATE yang akan dijalankan, tanpa mengeksekusinya.
     */
    public function compileTruncate(string $table): string {
        return $this->connection()->grammar()->compileTruncateTable($table);
    }

    /**
     * Mengubah struktur tabel yang sudah ada.
     */
    public function alter(string $table, Closure $definition): bool {
        return $this->execute($this->compileAlter($table, $definition));
    }

    /**
     * SQL ALTER yang akan dijalankan, tanpa mengeksekusinya.
     *
     * Definisi yang tidak menghasilkan operasi apa pun ditolak di sini, karena
     * ALTER TABLE tanpa perubahan bukan statement yang valid.
     *
     * @return string|string[]
     */
    public function compileAlter(string $table, Closure $definition): string|array {
        $blueprint = new AlterBlueprint($table);
        $definition($blueprint);

        if(empty($blueprint->getOperations())) {
            throw new InvalidArgumentException("Definisi alter untuk tabel {$table} tidak memiliki operasi apa pun.");
        }

        return $this->connection()->grammar()->compileAlterTable($blueprint);
    }

    /**
     * Mengeksekusi SQL yang sudah dirender Grammar.
     *
     * Umumnya satu string, tetapi untuk operasi yang di sebagian driver butuh
     * beberapa statement sekaligus (mis. ALTER TABLE di SQL Server) Grammar boleh
     * mengembalikan array. Semuanya dijalankan berurutan dan BERHENTI pada
     * kegagalan pertama, supaya statement berikutnya tidak dijalankan di atas
     * struktur yang belum jadi.
     *
     * @param string|string[] $sql
     */
    private function execute(string|array $sql): bool {
        foreach((array) $sql as $statement) {
            if($this->connection()->query($statement) === false) return false;
        }

        return true;
    }

}
