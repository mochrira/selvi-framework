---
id: ref-model
title: Selvi\Model
type: referensi
class: Selvi\Model
tags: [model, database, crud, query, relations, toarraydb]
prerequisites: [model]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Model`

Basis model berbasis attribute. Mewarisi
[`DataObject`](14-data-object.md), sehingga `of()` dan `toArray()` memakai
aturan DTO.

Aturan yang berlaku:

- `#[Table]` wajib; `getTable()`/`getSchema()` melempar
  `InvalidArgumentException` bila tidak ada.
- Metadata attribute dan properti di-cache per kelas.
- `create()`, `update()`, dan `delete()` memakai kolom bertanda `key: true`.
- `update()` dan `delete()` instance memakai `toArrayDb()` dan key model.

Pemakaian ada di [model](../03-model/01-model.md),
[relasi](../03-model/02-relasi.md), dan
[konversi value](../03-model/03-konversi-value.md).

## Method statis — metadata

### `Model::attributes()`

**Kembalian:** `array<string, object>` — attribute level kelas (mis. `Table`),
dikunci nama kelas attribute.

### `Model::attribute($classpath)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$classpath` | `string` | wajib | nama kelas attribute |

**Kembalian:** `?object`.

### `Model::properties()`

**Kembalian:** `array<string, IPropertyAttribute>` — semua properti berattribute,
dikunci nama properti.

### `Model::relations()`

**Kembalian:** `array<string, BelongsTo|Aggregate>`.

### `Model::relation($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama properti relasi |

**Kembalian:** `BelongsTo|Aggregate|null`.

### `Model::columns()`

**Kembalian:** `array<string, Column>`.

### `Model::getTable()`

**Kembalian:** `string` — nama tabel dari `#[Table]`.

### `Model::getSchema()`

**Kembalian:** `string` — nama koneksi dari `#[Table]`.

### `Model::getKeyColumn()`

**Kembalian:** `?Column` — kolom pertama bertanda `key: true`.

## Method statis — query

### `Model::query($prefix, $withClosure)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$prefix` | `string` | `''` | prefix kolom untuk query bersarang |
| `$withClosure` | `?Closure(WithBuilder): void` | `null` | relasi awal |

**Kembalian:** `ModelQuery<static>`.

### `Model::with($name, $closure)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama relasi |
| `$closure` | `?Closure(WithBuilder): void` | `null` | relasi anak |

**Kembalian:** `ModelQuery<static>`.

### `Model::where($input)`

**Kembalian:** `ModelQuery<static>`. Bentuk input sama dengan
[`QueryBuilder::where()`](16-query-builder.md).

### `Model::limit($limit)` / `Model::offset($offset)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$limit`/`$offset` | `?int` | wajib | `null` menghapus nilai |

**Kembalian:** `ModelQuery<static>`.

### `Model::find($id, $query)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$id` | `int\|string` | wajib | nilai kolom key |
| `$query` | `?Closure(IQueryBuilder): void` | `null` | kustomisasi query |

**Kembalian:** `Collection<static>|static|null`. Array id menghasilkan
`Collection`.

### `Model::first($query)`

**Kembalian:** `?static`.

### `Model::all()`

**Kembalian:** `Collection<static>`.

### `Model::count($query)`

**Kembalian:** `int`.

## Method statis — tulis

### `Model::create($data)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$data` | `array<string, mixed>` | wajib | pasangan **nama kolom** → nilai |

**Kembalian:** `static` dengan properti key terisi.

Semua kolom non-key harus ada di `$data`; key diisi dari hasil insert.

## Method instance

### `Model::toArrayDb()`

**Kembalian:** `array<string, scalar>` — kunci nama kolom, nilai sudah
di-`Converter::set()`.

Properti yang tidak di-`isset()` dan kolom nullable tetap dikirim sebagai
`null`.

### `Model::update()`

Memakai key model sebagai `WHERE`.

**Kembalian:** `bool`.

### `Model::delete()`

Memakai key model sebagai `WHERE`.

**Kembalian:** `bool`.

## Sumber

- `src/Model.php`
- `src/Data/DataObject.php` — `of()`/`toArray()`
- `src/Model/Attribute.php`, `PropertyAttribute.php`
- `src/Database/Builder/DML/ModelQuery.php`
- `tests/03.01-model/index.php` — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `tests/03.01-model` dan `tests/03.02-relasi`:

| Kasus | Hasil |
|---|---|
| `find()`, `all()`, `first()`, `count()` | sesuai data |
| `query()->orderBy()->limit()` | dua model terurut |
| `offset(1)->limit(2)` | id `6`, `7` |
| `create()`/`update()`/`delete()` | berhasil, model hilang setelah delete |
| `toArrayDb()` | kunci nama kolom, `DateTime` diformat |
| `relations()`/`relation()` | tiga relasi, `BelongsTo` |

Belum diuji: `attributes()`/`attribute()` langsung, `limit`/`offset` dengan
`null`, dan `find()` dengan array id.
