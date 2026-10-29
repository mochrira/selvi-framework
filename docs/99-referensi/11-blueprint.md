---
id: ref-blueprint
title: Selvi\Database\Builder\DDL\Blueprint
type: referensi
class: Selvi\Database\Builder\DDL\Blueprint
tags: [database, ddl, kolom, tipe-kolom, create-table, if-not-exists]
prerequisites: [database-skema-tabel]
verified: 2026-10-29
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Builder\DDL\Blueprint`

Kolektor definisi tabel untuk `SchemaBuilder::create()`. Instance dibuat oleh
`SchemaBuilder` lalu diteruskan ke closure; kode aplikasi tidak membuatnya
sendiri.

Aturan yang berlaku:

- Urutan kolom pada `CREATE TABLE` mengikuti urutan pemanggilan.
- Nama kolom kosong atau duplikat melempar `InvalidArgumentException`.
- Method tipe kolom berasal dari trait `HasColumnTypes`, yang juga dipakai
  `AlterBlueprint`, sehingga daftar tipe kedua kelas selalu sama.
- Setiap method tipe mengembalikan
  [`ColumnClause`](13-column-clause.md) agar modifier dapat dirangkai.
- `after()` dan `first()` tidak berpengaruh di sini; keduanya hanya dipakai
  `alter()`.

Pemakaian ada di [skema tabel](../02-database/02-skema-tabel.md).

## Method instance

### `Blueprint::__construct($table, $ifNotExists)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$table` | `string` | wajib | nama tabel |
| `$ifNotExists` | `bool` | `true` | menambahkan `IF NOT EXISTS` pada `CREATE TABLE` |

Dipakai internal oleh `SchemaBuilder`; tidak perlu dibuat manual.

**Kembalian:** instance `Blueprint`.

### `Blueprint::ifNotExists($flag)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$flag` | `bool` | `true` | `false` menghilangkan `IF NOT EXISTS` |

**Kembalian:** instance `Blueprint`.

### `Blueprint::integer($name, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `INT`.

### `Blueprint::bigInteger($name, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `BIGINT`.

### `Blueprint::string($name, $length, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$length` | `int` | `255` | panjang `VARCHAR` |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `VARCHAR(n)`.

### `Blueprint::text($name, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `TEXT`.

### `Blueprint::boolean($name, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `TINYINT(1)`.

### `Blueprint::decimal($name, $precision, $scale, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$precision` | `int` | `10` | jumlah digit total |
| `$scale` | `int` | `0` | jumlah digit di belakang koma |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `DECIMAL(p, s)`.

### `Blueprint::float($name, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `DOUBLE`.

### `Blueprint::date($name, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `DATE`.

### `Blueprint::datetime($name, $nullable)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama kolom |
| `$nullable` | `bool` | `false` | `true` menghilangkan `NOT NULL` |

**Kembalian:** `ColumnClause`. MySQL: `DATETIME`.

### `Blueprint::getTable()`

Tidak ada parameter.

**Kembalian:** `string` — nama tabel. Dipakai Grammar, bukan kode aplikasi.

### `Blueprint::hasIfNotExists()`

Tidak ada parameter.

**Kembalian:** `bool`. Dipakai Grammar.

### `Blueprint::getColumns()`

Tidak ada parameter.

**Kembalian:** `array<int, array<string, mixed>>` — struktur kanonik setiap
kolom hasil `ColumnClause::toArray()`. Dipakai Grammar.

## Sumber

- `src/Database/Builder/DDL/Blueprint.php`
- `src/Database/Builder/DDL/HasColumnTypes.php` — method tipe kolom
- `src/Database/Builder/DDL/Clauses/ColumnClause.php`
- `src/Database/Interfaces/IBlueprint.php` — kontrak untuk Grammar
- `tests/02.02-skema-tabel/index.php` — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 29 Oktober 2026 lewat `GET /02.02-skema-tabel/tipe` dan `/jalankan`:

| Kasus | Hasil |
|---|---|
| kesembilan method tipe kolom | menghasilkan `INT`, `BIGINT`, `VARCHAR(40)`, `TEXT`, `TINYINT(1)`, `DECIMAL(12, 2)`, `DOUBLE`, `DATE`, `DATETIME` |
| `length(30)` setelah `string(..., 10)` | `VARCHAR(30)` |
| `ifNotExists(false)` | `CREATE TABLE demo_tipe_2 (...)` tanpa `IF NOT EXISTS` |
| `nullable()` | kolom tanpa `NOT NULL` |
| `getTable()`, `hasIfNotExists()`, `getColumns()` | terpakai tidak langsung oleh `compileCreate()` |

Belum diuji: `InvalidArgumentException` untuk kolom duplikat. Perilaku itu
disimpulkan dari kode.
