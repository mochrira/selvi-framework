---
id: ref-schema
title: Selvi\Database\Schema
type: referensi
class: Selvi\Database\Schema
tags: [database, schema, ddl, migration, create, alter, drop, table]
prerequisites: [database-migrasi]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Schema`

Handle DDL yang sudah terikat pada satu koneksi. Ini pembungkus tipis
[`SchemaBuilder`](10-schema-builder.md): pemanggil tidak perlu menyebut koneksi
di setiap operasi.

Di dalam file migrasi, objek ini dibuat otomatis dengan koneksi sesuai argumen
perintah `db:migrate`. Di luar migrasi:

```php
$schema = new Selvi\Database\Schema('main');
$schema->create('demo', function (Selvi\Database\Builder\DDL\Blueprint $table) {
    $table->integer('id')->key()->autoIncrement();
});
```

Pemakaian ada di [migrasi](../03-database/05-migrasi.md).

## Method instance

### `new Schema($connection)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string \| IConnection` | `''` | nama koneksi, objek koneksi, atau kosong untuk koneksi default |

### `Schema::useConnection($connection)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string \| IConnection` | wajib | koneksi baru |

**Kembalian:** instance `Schema`.

### `Schema::connection()`

**Kembalian:** `Selvi\Database\Interfaces\IConnection` — akses ke query mentah,
transaksi, dan `getConfig()`.

### `Schema::create($table, $definition)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel |
| `$definition` | `?Closure(Blueprint): void` | `null` | definisi kolom |

**Kembalian:** `bool`. SQL memakai `CREATE TABLE IF NOT EXISTS` secara default.

### `Schema::drop($table, $ifExists)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel |
| `$ifExists` | `bool` | `true` | tambahkan `IF EXISTS` |

**Kembalian:** `bool`.

### `Schema::rename($table, $newTable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama lama |
| `$newTable` | `string` | wajib | nama baru |

**Kembalian:** `bool`.

### `Schema::truncate($table)`

**Kembalian:** `bool`. Seluruh baris dihapus, struktur tetap.

### `Schema::alter($table, $definition)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel |
| `$definition` | `Closure(AlterBlueprint): void` | wajib | operasi alter |

**Kembalian:** `bool`. Detail operasi ada di
[`AlterBlueprint`](12-alter-blueprint.md).

### `Schema::table($name)`

**Kembalian:** `QueryBuilder` yang sudah memakai koneksi `Schema` ini.

Dipakai untuk DML di dalam migrasi/seed, misalnya
`$schema->table('grup')->insert([...])`.

## Sumber

- `src/Database/Schema.php`
- `src/Database/Builder/DDL/SchemaBuilder.php` — implementasi DDL
- `tests/03.05-migrasi/migrations/20260101_01_create_demo_migrasi.php` —
  pemakaian `create()`/`drop()` di file migrasi
- `tests/03.05-migrasi/index.php` — `new Schema('demo_migrasi')`

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat aplikasi uji migrasi (PHP 8.4.14, MariaDB 10.7.8):

| Kasus | Hasil |
|---|---|
| `create()` dari file migrasi | tabel `demo_migrasi` terbentuk |
| `drop()` dari file migrasi `down` | tabel hilang |
| `new Schema('demo_migrasi')` di luar migrasi | `drop()` berhasil |
| `table()` dipakai `MigrationLog` | insert riwayat berhasil |

`rename()`, `truncate()`, dan `alter()` diuji di aplikasi
[skema tabel](../03-database/02-skema-tabel.md) lewat `SchemaBuilder` (jalur
eksekusi yang sama). Belum diuji langsung lewat `Schema`.
