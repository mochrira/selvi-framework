---
id: ref-model-query
title: Selvi\Database\Builder\DML\ModelQuery
type: referensi
class: Selvi\Database\Builder\DML\ModelQuery
tags: [model, query, eager-loading, find, count, insert, update, delete]
prerequisites: [model]
verified: 2026-11-05
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Builder\DML\ModelQuery`

Query fluent untuk satu model. Instance dibuat lewat
[`Model::query()`](21-model.md) atau method statis `Model::with()`,
`Model::where()`, `Model::limit()`, dan `Model::offset()`.

Aturan yang berlaku:

- Kolom `SELECT` dibangun otomatis dari `Model::columns()` dengan alias
  `{prefix}{nama_kolom}`.
- `with()` menambahkan join/subquery, bukan query terpisah per baris.
- `update()`/`delete()` melempar `InvalidArgumentException` bila tidak ada
  `where`.
- `find()` dengan array id mengembalikan `Collection`; satu id mengembalikan
  model.

Pemakaian ada di [model](../03-model/01-model.md) dan
[relasi](../03-model/02-relasi.md).

## Method instance — query

### `ModelQuery::build($query)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$query` | `?Closure(IQueryBuilder): void` | `null` | kustomisasi builder sebelum eksekusi |

**Kembalian:** `IQueryBuilder` (QueryBuilder yang sudah diisi select, join,
where, order, limit).

### `ModelQuery::buildWith($builder)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$builder` | `IQueryBuilder` | wajib | builder tujuan |

**Kembalian:** `void`. Dipakai internal oleh `build()` untuk relasi.

### `ModelQuery::with($name, $closure)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama properti relasi |
| `$closure` | `?Closure(WithBuilder): void` | `null` | relasi anak |

**Kembalian:** instance `ModelQuery`.

### `ModelQuery::where($input)`

**Kembalian:** instance `ModelQuery`. Bentuk input sama dengan
[`QueryBuilder::where()`](16-query-builder.md).

### `ModelQuery::limit($limit)` / `ModelQuery::offset($offset)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$limit`/`$offset` | `?int` | wajib | `null` menghapus nilai |

**Kembalian:** instance `ModelQuery`.

### `ModelQuery::order(...$args)`

Menerima bentuk yang sama dengan
[`QueryBuilder::order()`](16-query-builder.md).

**Kembalian:** instance `ModelQuery`.

### `ModelQuery::orderUsing($input)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$input` | `Closure(OrderBuilder): void` | wajib | callback order |

**Kembalian:** instance `ModelQuery`.

### `ModelQuery::orderBy($column, $direction)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$column` | `string` | wajib | nama kolom |
| `$direction` | `string` | `'ASC'` | `ASC` atau `DESC` |

**Kembalian:** instance `ModelQuery`.

## Method instance — baca

### `ModelQuery::find($id, $query)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$id` | `mixed` | wajib | nilai key atau array nilai key |
| `$query` | `?Closure(IQueryBuilder): void` | `null` | kustomisasi query |

**Kembalian:** `Collection|Model|null`.

### `ModelQuery::all($query)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$query` | `?Closure(IQueryBuilder): void` | `null` | kustomisasi builder sebelum eksekusi |

**Kembalian:** `Collection<Model>`. Closure dijalankan setelah select, join,
dan where dari `ModelQuery` disusun, sehingga bisa menambahkan `orderBy()`,
`limit()`, atau `offset()`.

### `ModelQuery::first($query)`

Menambahkan `LIMIT 1`.

**Kembalian:** `?Model`.

### `ModelQuery::count($query)`

Membangun subquery dari query saat ini, lalu `COUNT(key)`.

**Kembalian:** `int`.

## Method instance — tulis

### `ModelQuery::insert($data)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$data` | `array<string, mixed>` | wajib | pasangan nama kolom → nilai |

**Kembalian:** `int|string` — nilai key (dari `$data[key]` bila ada, selain itu
hasil insert).

Hanya kolom yang terdaftar di model yang dikirim.

### `ModelQuery::update($data)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$data` | `array<string, mixed>` | wajib | pasangan kolom → nilai |

**Kembalian:** `bool`. Tanpa `where`:
`InvalidArgumentException: Operasi UPDATE membutuhkan setidaknya 1 klausa where`.

### `ModelQuery::delete()`

**Kembalian:** `bool`. Tanpa `where`:
`InvalidArgumentException: Operasi DELETE membutuhkan setidaknya 1 klausa where`.

## Sumber

- [src/Database/Builder/DML/ModelQuery.php](../../src/Database/Builder/DML/ModelQuery.php)
- [src/Model.php](../../src/Model.php) — pembuat `ModelQuery`
- [src/Database/Builder/DML/WithBuilder.php](../../src/Database/Builder/DML/WithBuilder.php)
- [tests/03.01-model/index.php](../../tests/03.01-model/index.php) dan [tests/03.02-relasi/index.php](../../tests/03.02-relasi/index.php) — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat aplikasi uji model/relasi:

| Kasus | Hasil |
|---|---|
| `orderBy()` + `limit()` | dua model terurut |
| `offset(1)` + `limit(2)` | id `6`, `7` |
| `find(5)` | satu model |
| `count()` | `5` |
| `all()` tanpa closure dan `all(fn($b) => $b->limit(2))` | seluruh baris dan dua baris |
| `with()` satu dan dua tingkat | relasi terisi |
| `insert`/`update`/`delete` lewat `Model` | berhasil |

Belum diuji: `find()` dengan array id, `order()` bentuk campuran,
`orderUsing()`, `buildWith()` langsung, dan guard `where` pada
`ModelQuery::update()`/`delete()`.
