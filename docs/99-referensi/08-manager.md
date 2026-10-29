---
id: ref-manager
title: Selvi\Database\Manager
type: referensi
class: Selvi\Database\Manager
tags: [database, koneksi, registry, manager, mysql]
prerequisites: [database-koneksi]
verified: 2026-10-29
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Manager`

Registry koneksi database. Seluruh state berupa properti statis, jadi koneksi
yang didaftarkan berlaku untuk seluruh proses. Pemakaian sehari-hari ada di
[koneksi](../02-database/01-koneksi.md).

Aturan yang berlaku:

- Koneksi dibuat saat `add()` dipanggil (`MySQLConnection::__construct()`
  langsung memanggil `connect()`), bukan saat query pertama.
- Nama yang sudah terdaftar tidak ditimpa; `add()` kedua dengan nama yang sama
  diabaikan tanpa error.
- Driver yang terdaftar hanya `mysql`. Nilai `driver` lain melempar
  `InvalidArgumentException`.
- `default()` mengembalikan koneksi **pertama** yang didaftarkan, bukan koneksi
  bernama `main` secara khusus.

## Method statis

### `Manager::add($name, $config)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama koneksi; dipakai lagi oleh `DB::connection()`, `#[Table(schema: ...)]`, dan `db:migrate` |
| `$config` | `array` | wajib | `host`, `username`, `password`, `database`; `driver` (default `mysql`), `port`, `socket` opsional |

**Kembalian:** `void`.

Melempar `InvalidArgumentException` bila driver belum punya implementasi, dan
`Selvi\Exception\DatabaseException` bila koneksi gagal dibuka.

### `Manager::get($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama koneksi yang sudah terdaftar |

**Kembalian:** `Selvi\Database\Interfaces\IConnection`.

Melempar `InvalidArgumentException: Koneksi 'x' tidak terdaftar.` bila nama belum
didaftarkan.

### `Manager::default()`

Tidak ada parameter.

**Kembalian:** `IConnection|null` — koneksi pertama yang terdaftar, atau `null`
bila belum ada koneksi.

### `Manager::has($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama koneksi yang dicek |

**Kembalian:** `bool`.

## Sumber

- `src/Database/Manager.php`
- `src/Database/Drivers/MySQL/MySQLConnection.php` — pembuatan koneksi
- `tests/02.01-koneksi/index.php` — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 29 Oktober 2026 lewat `http://localhost:8089/02.01-koneksi/`:

| Kasus | Hasil |
|---|---|
| `add('main', ...)` lalu `get('main')` | koneksi terbuka, `getConfig()` terbaca |
| `has('main')` | `true` |
| `get('lain')` | `InvalidArgumentException: Koneksi 'lain' tidak terdaftar.` |

Belum diuji: `default()` dengan lebih dari satu koneksi, penolakan driver selain
`mysql`, dan perilaku `add()` yang tidak menimpa nama terdaftar. Ketiganya
disimpulkan dari kode sumber.
