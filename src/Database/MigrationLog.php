<?php

declare(strict_types=1);

namespace Selvi\Database;

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Exception\DatabaseException;
use stdClass;

/**
 * Satu-satunya kelas yang menyentuh tabel riwayat migrasi.
 *
 * Nama tabel dan seluruh kolomnya dipertahankan seperti bentuk aslinya supaya
 * riwayat migrasi yang sudah ada di aplikasi berjalan tetap terbaca, termasuk
 * kolom-kolom error yang hanya terisi saat sebuah file gagal.
 *
 * Dipakai bersama oleh Migration dan Seeder — seeder menulis ke
 * tabel yang sama dengan direction 'seed'.
 *
 * @see \Selvi\Database\Migration
 * @see \Selvi\Database\Seeder
 */
final class MigrationLog {

    /**
     * @param Schema $schema Handle koneksi yang dipakai menulis riwayat.
     * @param string $table Nama tabel riwayat.
     */
    public function __construct(
        private readonly Schema $schema,
        private readonly string $table = '_migration'
    ) { }

    /**
     * Memastikan tabel riwayat tersedia. Aman dipanggil berulang karena
     * Blueprint memakai IF NOT EXISTS secara default.
     */
    public function prepare(): bool {
        return $this->schema->create($this->table, function (Blueprint $table) {
            $table->integer('id')->key()->autoIncrement();
            $table->string('filename', 150);
            $table->string('direction', 15);
            $table->integer('start');
            $table->integer('finish');
            $table->string('output', 15);
            $table->string('dbuser', 15);
            $table->text('error_msg')->nullable();
            $table->string('error_state', 50)->nullable();
            $table->text('error_query')->nullable();
        });
    }

    /**
     * Record terakhir untuk sebuah file — opsional difilter arah migrasinya.
     *
     * Inilah dasar keputusan skip: bila record terakhir sudah sesuai arah dan
     * berstatus success, file dianggap selesai.
     */
    public function last(string $file, ?string $direction = null): ?stdClass {
        $builder = $this->schema->table($this->table)->where([['filename', $file]]);

        if($direction !== null) {
            $builder->where([['direction', $direction]]);
        }

        $row = $builder->orderBy('start', 'DESC')->limit(1)->offset(0)->get()->row();

        return $row instanceof stdClass ? $row : null;
    }

    /**
     * Mencatat hasil satu eksekusi migrasi.
     *
     * $error diisi hanya saat gagal; detailnya (state + SQL) diambil dari
     * DatabaseException supaya Command tidak perlu tahu bentuk kolomnya.
     */
    public function write(string $file, string $direction, int $start, string $status, ?DatabaseException $error = null): int|string {
        $config = $this->schema->connection()->getConfig() ?? [];

        return $this->schema->table($this->table)->insert([
            'filename'    => $file,
            'direction'   => $direction,
            'start'       => $start,
            'finish'      => time(),
            'output'      => $status,
            'dbuser'      => $config['username'] ?? '',
            'error_msg'   => $error?->getMessage(),
            'error_state' => $error?->getState(),
            'error_query' => $error?->getSql(),
        ]);
    }

}
