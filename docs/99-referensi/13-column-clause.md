---
id: ref-column-clause
title: Selvi\Database\Builder\DDL\Clauses\ColumnClause
type: referensi
class: Selvi\Database\Builder\DDL\Clauses\ColumnClause
tags: [database, ddl, kolom, modifier, default, nullable, auto-increment]
prerequisites: [database-skema-tabel]
verified: 2026-10-29
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Builder\DDL\Clauses\ColumnClause`

Nilai satu kolom. Instance dikembalikan oleh method tipe
[`Blueprint`](11-blueprint.md) dan [`AlterBlueprint`](12-alter-blueprint.md),
lalu modifier dirangkai di atasnya. Kelasnya `final` dan konstruktornya privat;
tidak dibuat langsung oleh kode aplikasi.

Aturan yang berlaku:

- `toArray()` selalu memuat semua kunci (`name`, `type`, `length`, `precision`,
  `scale`, `nullable`, `default`, `key`, `auto_increment`, `unique`), sehingga
  Grammar tidak perlu menebak nilai yang tidak diisi.
- `operation()` dan `position()` sengaja **tidak** ikut ke `toArray()`;
  `AlterBlueprint` mengambilnya terpisah.
- `after()` dan `first()` saling eksklusif: memanggil salah satunya membatalkan
  yang lain.
- Nama kolom atau tipe kosong ditolak saat pembuatan.
- Nilai `default()` disanitasi driver saat dirender:
  `default(true)` menjadi `DEFAULT 1`, `default('aktif')` menjadi
  `DEFAULT 'aktif'`, `default(null)` tidak menghasilkan klausa `DEFAULT`.

Pemakaian ada di [skema tabel](../02-database/02-skema-tabel.md).

## Konstanta

| Konstanta | Nilai | Arti |
|---|---|---|
| `OPERATION_ADD` | `'add'` | kolom dicatat sebagai `ADD COLUMN` |
| `OPERATION_MODIFY` | `'modify'` | kolom dicatat sebagai `MODIFY COLUMN` |

## Method statis

### `ColumnClause::make($name, $type, $options)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom; dipangkas, tidak boleh kosong |
| `$type` | `string` | wajib | tipe semantik (`integer`, `string`, dst.) |
| `$options` | `array` | `[]` | nilai awal `optional` seperti `length`, `precision`, `scale`, `nullable` |

**Kembalian:** instance `ColumnClause`.

Dipakai internal oleh `Blueprint::add()` dan `AlterBlueprint::add()`.

## Method instance

### `ColumnClause::name()`

**Kembalian:** `string` — nama kolom.

### `ColumnClause::key($flag)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$flag` | `bool` | `true` | menandai `PRIMARY KEY` |

**Kembalian:** instance `ColumnClause`.

### `ColumnClause::autoIncrement($flag)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$flag` | `bool` | `true` | menandai `AUTO_INCREMENT` |

**Kembalian:** instance `ColumnClause`.

### `ColumnClause::nullable($flag)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$flag` | `bool` | `true` | `true` menghilangkan `NOT NULL` |

**Kembalian:** instance `ColumnClause`.

### `ColumnClause::default($value)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$value` | `mixed` | wajib | nilai default; `null` berarti tanpa klausa `DEFAULT` |

**Kembalian:** instance `ColumnClause`.

### `ColumnClause::unique($flag)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$flag` | `bool` | `true` | menandai `UNIQUE` |

**Kembalian:** instance `ColumnClause`.

### `ColumnClause::length($length)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$length` | `int` | wajib | panjang `VARCHAR` pengganti nilai awal |

**Kembalian:** instance `ColumnClause`.

### `ColumnClause::change($flag)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$flag` | `bool` | `true` | `true` → `modify`, `false` → `add` |

**Kembalian:** instance `ColumnClause`.

Hanya bermakna di dalam `alter()`. MySQL menulis ulang definisi kolom, jadi
semua atribut harus disebut lagi; yang tidak disebut akan hilang.

### `ColumnClause::operation()`

**Kembalian:** `string` — `add` atau `modify`.

### `ColumnClause::after($column)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$column` | `string` | wajib | nama kolom acuan; tidak boleh kosong |

**Kembalian:** instance `ColumnClause`.

Hanya berpengaruh di `alter()`. Memanggilnya membatalkan `first()`.

### `ColumnClause::first()`

**Kembalian:** instance `ColumnClause`.

Hanya berpengaruh di `alter()`. Memanggilnya membatalkan `after()`.

### `ColumnClause::position()`

**Kembalian:** `array|null` — `['first' => true]`, `['after' => 'x']`, atau
`null` bila posisi tidak diminta.

### `ColumnClause::toArray()`

**Kembalian:** `array<string, mixed>` — struktur kanonik kolom tanpa
`operation` dan `position`.

## Sumber

- `src/Database/Builder/DDL/Clauses/ColumnClause.php`
- `src/Database/Builder/DDL/HasColumnTypes.php` — pemanggil `make()`
- `src/Database/Drivers/MySQL/MySQLGrammar.php` — pemakai `toArray()`
- `tests/02.02-skema-tabel/index.php` — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 29 Oktober 2026 lewat `http://localhost:8089/02.02-skema-tabel/`:

| Kasus | Hasil |
|---|---|
| `key()` + `autoIncrement()` | `INT NOT NULL AUTO_INCREMENT PRIMARY KEY` |
| `nullable()` | klausa `NOT NULL` hilang |
| `default(true)`, `default(0)` | `DEFAULT 1`, `DEFAULT 0` |
| `unique()` lewat `AlterBlueprint::unique()` | `ADD UNIQUE INDEX` terbentuk |
| `length()` | `VARCHAR(30)` menggantikan panjang awal |
| `change()` | `MODIFY COLUMN` |
| `after()`, `first()` | posisi kolom sesuai |

Belum diuji secara langsung: `name()`, `operation()`, `position()`, dan
`toArray()` (dipakai tidak langsung oleh Grammar), serta `make()` dengan nama
kosong. Semuanya dijelaskan dari kode sumber.
