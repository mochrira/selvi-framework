<?php

use Selvi\Database\Builder\DDL\AlterBlueprint;
use Selvi\Schema;

/**
 * Menyelaraskan tabel kontak dengan model Selvi\Tests\Models\Kontak.
 *
 * Model punya properti yang belum ada di tabel — tabelnya dibuat oleh
 * 20240505_01_init.php sebelum properti itu ditambahkan:
 *
 *     #[Column('alamat')]
 *     public ?string $alamat;
 *
 * Kolom lain sudah cocok dengan model dan tidak perlu disentuh:
 *
 *     idKontak   int      -> INT NOT NULL PRIMARY KEY AUTO_INCREMENT
 *     nmKontak   ?string  -> VARCHAR(150) NULL
 *     idGrup     ?int     -> INT NULL
 *
 * Panjang kolom tidak bisa dibaca dari model (properti hanya menyebut ?string),
 * jadi dipakai default string() yaitu VARCHAR(255). Posisi kolom selalu di
 * belakang karena hint posisi (AFTER) belum didukung SchemaBuilder — secara
 * fungsional tidak berpengaruh, hanya urutan tampilannya berbeda dari model.
 *
 * Menjalankan:
 *   php console.php db:migrate main up
 *   php console.php db:migrate main down
 */
return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->string('alamat')->nullable();
        });
    }

    if($direction === 'down') {
        $schema->alter('kontak', function (AlterBlueprint $table) {
            $table->dropColumn('alamat');
        });
    }

};
