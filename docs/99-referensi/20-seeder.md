---
id: ref-seeder
title: Selvi\Database\Seeder
type: referensi
class: Selvi\Database\Seeder
tags: [database, seeder, cli, data-awal, migration-log, skip]
prerequisites: [database-seeder]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Seeder`

Perintah seeder dan registry path seed. Nama perintah berasal dari
`#[AsCommand('db:seed')]`.

Pemakaian ada di [seeder](../04-database/06-seeder.md).

## Method statis

### `Seeder::add($connection, $path)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string` | wajib | nama koneksi |
| `$path` | `string` | wajib | folder berisi file seed |

**Kembalian:** `void`.

### `Seeder::addAll($connection, $paths)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string` | wajib | nama koneksi |
| `$paths` | `string[]` | wajib | daftar folder |

**Kembalian:** `void`.

## Method instance

### `Seeder::up($connection, $step, $logger)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string` | wajib | nama koneksi |
| `$step` | `int` | `-1` | jumlah file; `-1` berarti semua |
| `$logger` | `?callable` | `null` | `fn(string $msg, string $status, string $type): void` |

**Kembalian:** `void`.

Seeder tidak punya method `down()`.

## Perintah CLI

```sh
php console.php db:seed <koneksi> [--step=N] [--no-interaction]
```

| Argumen/opsi | Arti |
|---|---|
| `koneksi` | nama koneksi yang sudah didaftarkan |
| `--step`, `-s` | batas jumlah file |
| `--no-interaction` | lewati konfirmasi `y/N` |

## Perilaku eksekusi

- File diurutkan berdasarkan `basename` menaik.
- File di-`include` (bukan `include_once`).
- File harus mengembalikan `Closure` dengan parameter pertama `Schema`; tidak
  ada parameter arah.
- Riwayat ditulis ke tabel `_migration` dengan `direction = seed`.
- Skip bila record terakhir untuk file itu berstatus `success`.
- `DatabaseException` dicatat lengkap lalu batch lanjut; error lain dilaporkan
  lewat logger.

Pesan penting:

| Kondisi | Pesan |
|---|---|
| path belum ada | `Belum ada path seeder yang didaftarkan untuk koneksi 'x'. Panggil Seeder::add().` |
| file bukan closure | `File seed X harus mengembalikan closure.` |
| parameter salah | `File seed X harus menerima Selvi\\Database\\Schema pada parameter pertamanya.` |

## Sumber

- [src/Database/Seeder.php](../../src/Database/Seeder.php)
- [src/Database/MigrationLog.php](../../src/Database/MigrationLog.php)
- [src/Database/Schema.php](../../src/Database/Schema.php)
- [tests/04.06-seeder/console.php](../../tests/04.06-seeder/console.php) dan [tests/04.06-seeder/seeds/](../../tests/04.06-seeder/seeds) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat CLI container (PHP 8.4.14, MariaDB 10.7.8):

| Kasus | Hasil |
|---|---|
| `db:seed demo_seed --no-interaction` | `[success:seed]`, dua baris masuk |
| Seed kedua | `[skipped:seed]`, jumlah tetap `2` |
| koneksi tanpa path | pesan path belum didaftarkan |
| record `_migration` | `direction = seed`, `output = success` |

Belum diuji: `--step`, konfirmasi interaktif, kegagalan SQL yang menulis kolom
error, dan `addAll()` dengan beberapa folder.
