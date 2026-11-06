---
id: database-seeder
title: Seeder
type: panduan
tags: [database, seeder, cli, data-awal, migration-log, symfony-console]
prerequisites: [database-migrasi]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Seeder

Seeder mengisi data awal ke tabel yang sudah ada. Berbeda dari migrasi, seeder
tidak punya arah `down`: satu file seed hanya berjalan sekali.

## Jawaban singkat

```php
return function (Schema $schema) {

    $schema->table('demo_seed')->insert([
        ['nama' => 'Seed A', 'nilai' => 1],
        ['nama' => 'Seed B', 'nilai' => 2],
    ]);

};
```

Jalankan `php console.php db:seed main`. Seed yang sudah tercatat di log
di-skip, jadi aman dijalankan berulang.

## Prasyarat

- [Migrasi](05-migrasi.md) sudah dipahami dan tabel target sudah dibuat.
- Koneksi database terdaftar.

## 1. Mendaftarkan path seeder

```php
use Selvi\Database\Manager;
use Selvi\Database\Seeder;
use Selvi\Env;

Manager::add('main', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);

Seeder::add('main', __DIR__.'/app/Seeders');
```

`Seeder::addAll()` menerima beberapa path sekaligus.

## 2. File seed

Nama file mengikuti pola `YYYYMMDD_NN_konteks.php`. File menerima `Schema` tanpa
parameter arah:

```php
<?php

use Selvi\Database\Schema;

return function (Schema $schema) {

    $schema->table('demo_seed')->insert([
        ['nama' => 'Seed A', 'nilai' => 1],
        ['nama' => 'Seed B', 'nilai' => 2],
    ]);

};
```

Aturan:

- File harus mengembalikan closure.
- Parameter pertama wajib bertipe `Schema`.
- Tabel target harus sudah ada; seeder tidak membuat struktur.
- Insert, update, dan delete memakai `QueryBuilder` yang sama seperti halaman
  [menulis data](04-menulis-data.md).

## 3. Perintah CLI

Tambahkan perintah ke console:

```php
use Selvi\Database\Seeder;
use Symfony\Component\Console\Application;

$app = new Application('Selvi Commander', '1.0.0');
$app->addCommand(new Seeder());
$app->run();
```

Nama perintah berasal dari `#[AsCommand('db:seed')]`:

```sh
php console.php db:seed main
php console.php db:seed main --step=1
```

| Argumen/opsi | Arti |
|---|---|
| `main` | nama koneksi |
| `--step=N` / `-s N` | jumlah file seed yang dijalankan |
| `--no-interaction` | lewati konfirmasi |

Tidak ada arah `down`. Untuk mengulang seed, hapus record `_migration` untuk
file tersebut (dan bersihkan tabelnya bila perlu), lalu jalankan lagi.

## 4. Output perintah seeder

```text
[success:seed] 20260101_01_demo_seed.php berhasil dijalankan
```

Menjalankan ulang:

```text
[skipped:seed] 20260101_01_demo_seed.php berhasil dijalankan pada 2026-11-01 11:12:45
```

Riwayat seed memakai tabel yang sama dengan migrasi, dengan `direction = seed`.
Skip terjadi bila record terakhir file itu berstatus `success`.

## 5. Memanggil dari kode

```php
$seeder = new Seeder();
$seeder->up('main');
```

`up()` menerima `?callable $logger` dengan signature
`function (string $msg, string $status, string $type): void`.

## Referensi API

- [`Seeder`](../99-referensi/20-seeder.md) — `add()`, `addAll()`, `up()`, dan
  opsi CLI.
- [`Schema`](../99-referensi/18-schema.md) — handle yang diterima file seed.
- [`Migration`](../99-referensi/19-migration.md) — tabel riwayat yang dipakai
  bersama.

## Kesalahan umum

| Pesan | Penyebab | Perbaikan |
|---|---|---|
| `Belum ada path seeder yang didaftarkan untuk koneksi 'main'. Panggil Seeder::add().` | path belum didaftarkan | panggil `Seeder::add()` saat bootstrap |
| `File seed X harus mengembalikan closure.` | file tidak `return function (...) {}` | kembalikan closure |
| `File seed X harus menerima Selvi\Database\Schema pada parameter pertamanya.` | type hint salah | beri `Schema` |
| `Table 'x' doesn't exist` | tabel belum dimigrasikan | jalankan `db:migrate` lebih dulu |
| Seed kedua tidak menambah data | file sudah tercatat `success` | hapus record `_migration` jika memang ingin mengulang |

## Sumber

- [src/Database/Seeder.php](../../src/Database/Seeder.php) — perintah `db:seed`, pemilihan file, skip, logger
- [src/Database/MigrationLog.php](../../src/Database/MigrationLog.php) — tabel `_migration` bersama
- [src/Database/Schema.php](../../src/Database/Schema.php) — handle koneksi untuk file seed
- [tests/04.06-seeder/console.php](../../tests/04.06-seeder/console.php), [tests/04.06-seeder/seeds/](../../tests/04.06-seeder/seeds), dan
  [tests/04.06-seeder/index.php](../../tests/04.06-seeder/index.php) — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat container `selvi_test_dev` (PHP 8.4.14) dengan
koneksi uji `demo_seed`:

| Kasus | Hasil |
|---|---|
| `GET /04.06-seeder/siapkan` | `200`, tabel `demo_seed` dibuat dan log lama dihapus |
| `db:seed demo_seed --no-interaction` | `[success:seed]`, dua baris masuk |
| `db:seed demo_seed --no-interaction` kedua | `[skipped:seed]`, jumlah tetap `2` |
| `db:seed main --no-interaction` tanpa path | `Belum ada path seeder yang didaftarkan...` |
| Record `_migration` | `direction` `seed`, `output` `success` |

Belum diuji: `--step`, kegagalan SQL yang dicatat ke kolom error, dan logger
kustom lewat pemanggilan `up()` dari kode.
