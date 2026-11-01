---
id: ref-migration
title: Selvi\Database\Migration
type: referensi
class: Selvi\Database\Migration
tags: [database, migrasi, cli, symfony-console, migration-log, skip]
prerequisites: [database-migrasi]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Migration`

Perintah migrasi dan registry path migrasi. Nama perintah berasal dari
`#[AsCommand('db:migrate')]`; nama lain bisa diberikan lewat konstruktor.

Pemakaian ada di [migrasi](../04-database/05-migrasi.md).

## Method statis

### `Migration::add($connection, $path)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string` | wajib | nama koneksi |
| `$path` | `string` | wajib | folder berisi file migrasi |

**Kembalian:** `void`. Path untuk koneksi yang sama digabung.

### `Migration::addAll($connection, $paths)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string` | wajib | nama koneksi |
| `$paths` | `string[]` | wajib | daftar folder |

**Kembalian:** `void`.

## Method instance

### `Migration::up($connection, $step, $logger)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string` | wajib | nama koneksi |
| `$step` | `int` | `-1` | jumlah file; `-1` berarti semua |
| `$logger` | `?callable` | `null` | `fn(string $msg, string $status, string $type): void` |

**Kembalian:** `void`.

### `Migration::down($connection, $step, $logger)`

Sama seperti `up()`, tetapi default `$step = 1` dan file diurutkan menurun.

**Kembalian:** `void`.

## Perintah CLI

```sh
php console.php db:migrate <koneksi> <up|down> [--step=N] [--all] [--no-interaction]
```

| Argumen/opsi | Arti |
|---|---|
| `koneksi` | nama koneksi yang sudah didaftarkan |
| `up`/`down` | arah migrasi |
| `--step`, `-s` | batas jumlah file |
| `--all`, `-a` | setara `--step=-1` |
| `--no-interaction` | lewati konfirmasi `y/N` |

Default: `up` tanpa `--step` menjalankan semua file; `down` menjalankan satu
file terakhir.

## Perilaku eksekusi

- File diurutkan berdasarkan `basename` menaik untuk `up`, menurun untuk `down`.
- Setiap file di-`include` (bukan `include_once`), jadi closure selalu segar.
- File harus mengembalikan `Closure` dengan parameter pertama `Schema`.
- Skip bila record terakhir file itu punya arah sama dan `output = success`.
- `DatabaseException` dicatat ke `_migration` (`error_msg`, `error_state`,
  `error_query`) lalu batch lanjut ke file berikutnya.
- Error non-`DatabaseException` (mis. file tidak mengembalikan closure atau
  tipe parameter salah) juga ditangkap `migrate()` dan hanya dilaporkan lewat
  logger; **exit code perintah tetap `0`**.
- `execute()` mengembalikan `Command::FAILURE` untuk error yang terjadi sebelum
  `migrate()`, misalnya arah tidak valid atau path belum terdaftar.

Pesan penting:

| Kondisi | Pesan |
|---|---|
| arah salah | `Arah migrasi 'x' tidak dikenali. Gunakan 'up' atau 'down'.` |
| path belum ada | `Belum ada path migrasi yang didaftarkan untuk koneksi 'x'. Panggil Migration::add().` |
| file bukan closure | `File migrasi X harus mengembalikan closure.` |
| parameter salah | `File migrasi X harus menerima Selvi\\Database\\Schema pada parameter pertamanya.` |

## Sumber

- `src/Database/Migration.php`
- `src/Database/MigrationLog.php`
- `src/Database/Schema.php`
- `tests/04.05-migrasi/console.php` dan `tests/04.05-migrasi/migrations/` —
  aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat CLI container (PHP 8.4.14, MariaDB 10.7.8):

| Kasus | Hasil |
|---|---|
| `up --all --no-interaction` | `[success:up]`, tabel dibuat |
| `up` kedua | `[skipped:up]` |
| `down --all --no-interaction` | `[success:down]`, tabel dihapus |
| arah `sideways` | pesan arah tidak dikenali |
| koneksi tanpa path | pesan path belum didaftarkan |
| file migrasi dengan parameter salah | `[failed:up] File migrasi ... harus menerima Selvi\Database\Schema...`; exit code `0` |
| record `_migration` | `direction` dan `output` sesuai |

Belum diuji: `--step`, konfirmasi interaktif, kegagalan SQL yang menulis kolom
error, dan `addAll()` dengan beberapa folder.
