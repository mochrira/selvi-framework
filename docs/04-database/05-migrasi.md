---
id: database-migrasi
title: Migrasi
type: panduan
tags: [database, migrasi, cli, schema, migration-log, symfony-console]
prerequisites: [database-skema-tabel]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Migrasi

Migrasi adalah file PHP yang mengubah struktur database ke depan (`up`) dan
mengembalikannya (`down`). Selvi tidak memakai ORM atau diff schema: setiap file
menjalankan DDL sendiri lewat [`Schema`](../99-referensi/18-schema.md).

## Jawaban singkat

```php
return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('demo_migrasi', function (Blueprint $table) {
            $table->integer('id')->key()->autoIncrement();
        });
    }

    if($direction === 'down') {
        $schema->drop('demo_migrasi');
    }

};
```

Jalankan `php console.php db:migrate main up`; `down` membatalkan.

## Prasyarat

- [Koneksi database](01-koneksi.md) dan [skema tabel](02-skema-tabel.md) sudah
  dipahami.
- `symfony/console` tersedia (sudah menjadi dependency framework).
- Folder migrasi dan tabel riwayat `_migration` dapat ditulis.

## 1. Mendaftarkan koneksi dan path

```php
use Selvi\Database\Manager;
use Selvi\Database\Migration;
use Selvi\Env;

Manager::add('main', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);

Migration::add('main', __DIR__.'/app/Migrations');
```

`Migration::add()` mendaftarkan satu path; `Migration::addAll()` menerima array
path. Beberapa path untuk koneksi yang sama digabung dan diurutkan berdasarkan
nama file.

## 2. File migrasi

Nama file menentukan urutan: `YYYYMMDD_NN_konteks.php`, misalnya
`20260101_01_create_demo_migrasi.php`.

```php
<?php

use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\Schema;

return function (Schema $schema, string $direction) {

    if($direction === 'up') {
        $schema->create('demo_migrasi', function (Blueprint $table) {
            $table->integer('id')->key()->autoIncrement();
            $table->string('nama', 50);
        });
    }

    if($direction === 'down') {
        $schema->drop('demo_migrasi');
    }

};
```

Aturan:

- File harus **mengembalikan closure**.
- Parameter pertama wajib bertipe `Selvi\Database\Schema`.
- Parameter kedua adalah arah (`up`/`down`); bebas diberi nama apa pun.
- `$schema` sudah terikat ke koneksi yang disebut saat perintah dijalankan.
- DDL yang tersedia sama dengan halaman [skema tabel](02-skema-tabel.md).

## 3. Perintah CLI

Buat bootstrap console:

```php
<?php

use Selvi\Database\Manager;
use Selvi\Database\Migration;
use Selvi\Env;
use Symfony\Component\Console\Application;

require __DIR__.'/../vendor/autoload.php';

define('BASEPATH', __DIR__);
Env::load(__DIR__.'/.ENV');

Manager::add('main', [
    'driver' => Env::get('DB_DRIVER', 'mysql'),
    'host' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'username' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASS'),
    'database' => Env::get('DB_NAME'),
]);

Migration::add('main', BASEPATH.'/app/Migrations');

$app = new Application('Selvi Commander', '1.0.0');
$app->addCommand(new Migration());
$app->run();
```

Nama perintah diambil dari attribute `#[AsCommand('db:migrate')]`:

```sh
php console.php db:migrate main up
php console.php db:migrate main down
```

| Argumen/opsi | Arti |
|---|---|
| `main` | nama koneksi yang terdaftar di `Manager` |
| `up` / `down` | arah migrasi |
| `--step=N` / `-s N` | jumlah file yang dijalankan |
| `--all` / `-a` | semua file (`step = -1`) |
| `--no-interaction` | lewati konfirmasi interaktif |

Tanpa `--step`, `up` menjalankan **semua** file yang belum tercatat sukses,
sedangkan `down` menjalankan **satu** file terakhir.

## 4. Output perintah migrasi

```text
[success:up] 20260101_01_create_demo_migrasi.php berhasil dijalankan
```

Menjalankan ulang perintah yang sama:

```text
[skipped:up] 20260101_01_create_demo_migrasi.php berhasil dijalankan pada 2026-11-01 11:12:34
```

Saat `down`:

```text
[success:down] 20260101_01_create_demo_migrasi.php berhasil dijalankan
```

Status yang mungkin muncul: `success`, `skipped`, `failed`, dengan format
`[status:arah]`.

## 5. Riwayat `_migration`

Setiap eksekusi dicatat di tabel `_migration` (dibuat otomatis dengan
`IF NOT EXISTS`). Kolomnya:

| Kolom | Isi |
|---|---|
| `filename` | nama file migrasi |
| `direction` | `up`, `down`, atau `seed` |
| `start`, `finish` | Unix timestamp |
| `output` | `success` atau `failed` |
| `dbuser` | username koneksi |
| `error_msg`, `error_state`, `error_query` | detail saat gagal |

Keputusan skip: file dilewati bila record **terakhir** untuk file itu memiliki
arah yang sama dan `output = success`.

Kegagalan `DatabaseException` dicatat lengkap lalu eksekusi **lanjut ke file
berikutnya**; satu file bermasalah tidak menghentikan batch. Error lain
(mis. file tidak mengembalikan closure) hanya dilaporkan lewat logger dan
perintah tetap keluar dengan kode `0`.

## 6. Memanggil dari kode

`up()` dan `down()` juga tersedia sebagai method:

```php
$migration = new Migration();
$migration->up('main');
$migration->down('main', step: 1);
```

Keduanya menerima `?callable $logger` dengan signature
`function (string $msg, string $status, string $type): void`.

## Referensi API

- [`Migration`](../99-referensi/19-migration.md) — `add()`, `addAll()`, `up()`,
  `down()`, nama perintah dan opsinya.
- [`Schema`](../99-referensi/18-schema.md) — handle DDL yang diterima file
  migrasi.
- [`SchemaBuilder`](../99-referensi/10-schema-builder.md) — eksekusi DDL.

## Kesalahan umum

| Pesan | Penyebab | Perbaikan |
|---|---|---|
| `Belum ada path migrasi yang didaftarkan untuk koneksi 'main'. Panggil Migration::add().` | path belum didaftarkan | panggil `Migration::add()` saat bootstrap |
| `Arah migrasi 'x' tidak dikenali. Gunakan 'up' atau 'down'.` | argumen arah salah | pakai `up` atau `down` |
| `File migrasi X harus mengembalikan closure.` | file tidak `return function (...) {}` | kembalikan closure |
| `File migrasi X harus menerima Selvi\Database\Schema pada parameter pertamanya.` | tipe parameter salah/tidak ada | beri type hint `Schema` |
| Migrasi tampak “berhasil” tetapi tabel tidak berubah | file melempar error non-DatabaseException dan hanya dilaporkan lewat logger | periksa output `failed` dan log |

## Sumber

- [src/Database/Migration.php](../../src/Database/Migration.php) — perintah `db:migrate`, pemilihan file, skip,
  logger
- [src/Database/MigrationLog.php](../../src/Database/MigrationLog.php) — tabel `_migration`
- [src/Database/Schema.php](../../src/Database/Schema.php) — handle koneksi untuk file migrasi
- [tests/04.05-migrasi/console.php](../../tests/04.05-migrasi/console.php), [tests/04.05-migrasi/migrations/](../../tests/04.05-migrasi/migrations), dan
  [tests/04.05-migrasi/index.php](../../tests/04.05-migrasi/index.php) — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat container `selvi_test_dev` (PHP 8.4.14) dengan
koneksi uji `demo_migrasi`:

| Kasus | Hasil |
|---|---|
| `db:migrate demo_migrasi up --all --no-interaction` | `[success:up]`; tabel `demo_migrasi` ada |
| Perintah `up` kedua | `[skipped:up]` |
| `db:migrate demo_migrasi down --all --no-interaction` | `[success:down]`; tabel hilang |
| `db:migrate demo_migrasi sideways` | `Arah migrasi 'sideways' tidak dikenali...` |
| `db:migrate main up` tanpa path | `Belum ada path migrasi yang didaftarkan...` |
| File migrasi dengan parameter salah | `[failed:up] ... harus menerima Selvi\Database\Schema...`, exit code `0` |
| Record terakhir `_migration` | `direction` `up`/`down`, `output` `success` |

Belum diuji: `--step`, kegagalan SQL yang dicatat ke kolom error, dan logger
kustom lewat pemanggilan `up()`/`down()` dari kode.
