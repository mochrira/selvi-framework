---
id: ref-query-builder
title: Selvi\Database\Builder\DML\QueryBuilder
type: referensi
class: Selvi\Database\Builder\DML\QueryBuilder
tags: [database, dml, query-builder, select, where, join, insert, update, delete]
prerequisites: [database-membaca-data]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Referensi: `Selvi\Database\Builder\DML\QueryBuilder`

Builder DML untuk satu tabel. Instance dibuat lewat
[`DB::table()`](09-db.md) atau `DB::connection()->table()`.

Aturan yang berlaku:

- Nama koneksi baru di-resolve saat `getSql()`/`get()`/operasi tulis dipanggil.
  Bila tidak ada koneksi default, muncul
  `RuntimeException: Koneksi database belum diatur.`
- `update()` dan `delete()` menolak berjalan tanpa `WHERE` (`LogicException`).
- Nilai kondisi, insert, dan update disanitasi oleh `MySQLSanitizer`. Raw where
  (`where('...')`) tidak disanitasi.
- `offset(0)` tidak dirender ke SQL (`LIMIT` tanpa `OFFSET`).

Pemakaian ada di [membaca data](../04-database/03-membaca-data.md) dan
[menulis data](../04-database/04-menulis-data.md).

## Method instance — koneksi dan tabel

### `QueryBuilder::useConnection($connection)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$connection` | `string \| IConnection` | wajib | nama koneksi atau objek koneksi |

**Kembalian:** instance builder.

### `QueryBuilder::connection()`

**Kembalian:** `Selvi\Database\Interfaces\IConnection`.

### `QueryBuilder::table($name)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama tabel, boleh berupa subquery ber-alias |

**Kembalian:** instance builder.

### `QueryBuilder::getTable()`

**Kembalian:** `string`.

## Method instance — SELECT

### `QueryBuilder::select(...$columns)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `...$columns` | `mixed` | wajib | string, `['kolom', 'alias']`, `['tabel', 'kolom', 'alias']`, atau node dari [`SelectNode`](30-select-node.md) |

**Kembalian:** instance builder.

`select()` tetap variadic. Kolom, array alias, dan node fungsi boleh dicampur
dalam satu pemanggilan:

```php
->select(
    'id_kontak',
    ['nm_kontak', 'nama'],
    SelectNode::function('COALESCE', [
        SelectNode::column('nm_kontak'),
        SelectNode::literal('Tanpa Nama'),
    ], 'nama_tampil')
)
```

Bentuk tiga elemen `['tabel', 'kolom', 'alias']` dipakai untuk join.

### `QueryBuilder::selectFunction($name, $arguments, $alias)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$name` | `string` | wajib | nama fungsi SQL; divalidasi sebagai identifier |
| `$arguments` | `array` | `[]` | list node `SelectNode` |
| `$alias` | `?string` | `null` | alias hasil fungsi |

**Kembalian:** instance builder.

Menambahkan satu fungsi per pemanggilan, misalnya:

```php
->selectFunction('COALESCE', [
    SelectNode::column('nm_kontak'),
    SelectNode::literal('Tanpa Nama'),
], 'nama')
->selectFunction('COUNT', [SelectNode::star()], 'total')
```

### `QueryBuilder::selectRaw($sql, $alias)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$sql` | `string` | wajib | SQL mentah; tidak disanitasi |
| `$alias` | `?string` | `null` | alias |

**Kembalian:** instance builder.

Escape hatch eksplisit, misalnya `selectRaw('COUNT(*)', 'total')`. Jangan
memasukkan input user ke sini.

### `QueryBuilder::selectColumns($columns)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$columns` | `array` | wajib | daftar kolom dengan format yang sama seperti `select()` |

**Kembalian:** instance builder.

### `QueryBuilder::selectUsing($input)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$input` | `Closure(SelectBuilder): void` | wajib | callback konfigurasi select |

**Kembalian:** instance builder.

### `QueryBuilder::getSelectBuilder()`

**Kembalian:** `SelectBuilder`.

### `QueryBuilder::getSql()`

Mengompilasi query tanpa eksekusi.

**Kembalian:** `string`.

### `QueryBuilder::get()`

Mengeksekusi SELECT.

**Kembalian:** `Selvi\Database\Interfaces\IResult`.

## Method instance — WHERE

### `QueryBuilder::where($input)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$input` | `Closure(WhereBuilder): void \| array \| string` | wajib | kondisi |

**Kembalian:** instance builder.

Bentuk yang didukung:

- `[['kolom', 'operator', 'nilai']]`
- `[['kolom', 'nilai']]` → operator `=`
- `['raw sql', 'raw sql']`
- `'raw sql'`
- `Closure(WhereBuilder)` untuk grup bertingkat

`null` dengan operator `=`/`IS` menjadi `IS NULL`; dengan `<>`/`IS NOT` menjadi
`IS NOT NULL`.

### `QueryBuilder::orWhere($input)`

Sama seperti `where()`, tetapi kondisi berikutnya dihubungkan dengan `OR`.

**Kembalian:** instance builder.

### `QueryBuilder::getWhereClauses()`

**Kembalian:** `array` — AST kondisi.

## Method instance — JOIN

| Method | Parameter | Kembalian |
|---|---|---|
| `join($input, $on = null)` | `JoinBuilder\|JoinClause\|string`, `?string` | builder |
| `joinUsing($input)` | `Closure(JoinBuilder): void` | builder |
| `leftJoin($table, $on)` | `string`, `string` | builder |
| `innerJoin($table, $on)` | `string`, `string` | builder |
| `rightJoin($table, $on)` | `string`, `string` | builder |
| `getJoinBuilder()` | — | `JoinBuilder` |

`join('tabel', null)` melempar `LogicException`:

```text
Klausa join harus memiliki pernyataan relasi `on`
```

## Method instance — GROUP, ORDER, LIMIT

### `QueryBuilder::group(...$columns)`

Menerima `group('a')`, `group('a', 'b')`, `group('a, b')`, atau
`group(['a', 'b'])`.

**Kembalian:** instance builder.

### `QueryBuilder::order(...$args)`

Menerima beberapa bentuk: `'kolom DESC'`, `['kolom', 'ASC']`,
`['kolom' => 'DESC']`, atau objek `OrderBuilder`.

**Kembalian:** instance builder.

### `QueryBuilder::orderBy($column, $direction)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$column` | `string` | wajib | nama kolom |
| `$direction` | `string` | `'ASC'` | `ASC` atau `DESC` |

**Kembalian:** instance builder.

### `QueryBuilder::orderUsing($input)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$input` | `Closure(OrderBuilder): void` | wajib | callback konfigurasi order |

**Kembalian:** instance builder.

### `QueryBuilder::limit($limit)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$limit` | `?int` | wajib | `null` menghapus limit |

**Kembalian:** instance builder. **Getter:** `getLimit(): ?int`.

### `QueryBuilder::offset($offset)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$offset` | `?int` | wajib | `null` menghapus offset |

**Kembalian:** instance builder. **Getter:** `getOffset(): ?int`.

## Method instance — INSERT / UPDATE / DELETE

### `QueryBuilder::insert($values)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$values` | `array` | wajib | satu baris asosiatif atau list baris |

**Kembalian:** `int|string` — hasil `lastId()`.

Pada insert multi-baris MySQL, nilainya adalah **id pertama** batch. Data kosong
melempar `InvalidArgumentException: Data insert tidak boleh kosong.`

### `QueryBuilder::update($values)`

| Parameter | Tipe | Default | Keterangan |
|---|---|---|---|
| `$values` | `array` | wajib | pasangan kolom → nilai |

**Kembalian:** `bool`. Melempar `LogicException` bila tidak ada `WHERE`.

### `QueryBuilder::delete()`

**Kembalian:** `bool`. Melempar `LogicException` bila tidak ada `WHERE`.

## Sumber

- `src/Database/Builder/DML/QueryBuilder.php`
- `src/Database/Builder/DML/WhereBuilder.php`,
  `SelectBuilder.php`, `JoinBuilder.php`, `GroupBuilder.php`, `OrderBuilder.php`
- `src/Database/Builder/DML/InsertBuilder.php`, `UpdateBuilder.php`
- `src/Database/Interfaces/IQueryBuilder.php`
- `tests/04.03-membaca-data/index.php` dan
  `tests/04.04-menulis-data/index.php` — aplikasi uji

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat dua aplikasi uji DML (PHP 8.4.14):

| Kasus | Hasil |
|---|---|
| `select()` + `orderBy()` + `limit()` | tiga baris terurut |
| `leftJoin()` + `group()` + agregat | SQL dan hasil sesuai |
| `where()` operator/null/raw/nested | jumlah baris sesuai |
| `getSql()` | SQL lengkap tanpa eksekusi |
| `selectFunction` COALESCE/CONCAT/NOW | SQL dan nilai sesuai |
| `selectFunction` COUNT/MAX | agregat sesuai |
| `select` dengan node fungsi | tetap variadic dan campur kolom |
| `selectRaw('COUNT(*)')` | `COUNT(*) AS total` |
| nama fungsi tidak valid | `InvalidArgumentException` |
| `insert()` single dan multi | id `1`, id batch `2` |
| `update()`/`delete()` dengan WHERE | `true` |
| `update()`/`delete()` tanpa WHERE | `LogicException` |

Belum diuji: `rightJoin()`, `join()` dengan `JoinClause`, `joinUsing()`,
`selectUsing()`, `orderUsing()`, dan `selectColumns()`.
