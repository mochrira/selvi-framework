---
id: database-membaca-data
title: Membaca Data
type: panduan
tags: [database, dml, select, where, join, group, order, limit, result]
prerequisites: [database-skema-tabel]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Membaca Data

`DB::table()` membuat `QueryBuilder` untuk satu tabel. Semua method DML
(`select`, `where`, `join`, `group`, `order`, `limit`) merangkai AST, lalu
`get()` mengubahnya menjadi SQL dan mengeksekusinya.

## Jawaban singkat

```php
$rows = DB::table('kontak')
    ->select('id_kontak', 'nm_kontak')
    ->where([['nm_kontak', 'LIKE', '%John%']])
    ->orderBy('id_kontak')
    ->limit(10)
    ->get()
    ->rows();   // list<stdClass>

$satu = DB::table('kontak')->where([['id_kontak', '=', 5]])->get()->row();
```

## Prasyarat

- [Koneksi database](01-koneksi.md) sudah terdaftar.
- Tabel sudah dibuat, misalnya lewat [skema tabel](02-skema-tabel.md).
- Contoh di halaman ini memakai koneksi `main`.

## 1. Query dasar

```php
use Selvi\Database\DB;

$rows = DB::table('kontak')
    ->select('id_kontak', 'nm_kontak', 'id_departemen')
    ->orderBy('id_kontak')
    ->limit(3)
    ->get()
    ->rows();

return jsonResponse(['data' => $rows]);
```

Hasil:

```json
{
    "data": [
        { "id_kontak": "5", "nm_kontak": "John Doe", "id_departemen": "1" },
        { "id_kontak": "6", "nm_kontak": "John Doe", "id_departemen": "1" },
        { "id_kontak": "7", "nm_kontak": "John Doe", "id_departemen": "1" }
    ]
}
```

Driver MySQL mengembalikan kolom sebagai string lewat `mysqli`. Konversi ke
tipe PHP dilakukan saat data dipetakan ke DTO atau model.

## 2. Melihat SQL tanpa eksekusi

`getSql()` membutuhkan koneksi untuk memilih grammar, tetapi tidak menjalankan
query:

```php
$sql = DB::table('kontak')
    ->leftJoin('departemen', 'departemen.id_departemen = kontak.id_departemen')
    ->select(
        'kontak.id_departemen AS id_departemen',
        ['COUNT(kontak.id_kontak)', 'jumlah'])
    ->where([['kontak.id_departemen', '!=', null]])
    ->group('kontak.id_departemen')
    ->orderBy('kontak.id_departemen', 'DESC')
    ->limit(2)
    ->offset(0)
    ->getSql();
```

Hasil:

```text
SELECT kontak.id_departemen AS id_departemen, COUNT(kontak.id_kontak) AS jumlah FROM kontak LEFT JOIN departemen ON departemen.id_departemen = kontak.id_departemen WHERE kontak.id_departemen IS NOT NULL GROUP BY kontak.id_departemen ORDER BY kontak.id_departemen DESC LIMIT 2
```

`offset(0)` tidak ditulis ke SQL. `where` dengan value `null` berubah menjadi
`IS NULL` / `IS NOT NULL`.

## 3. Bentuk `select`

| Bentuk | Contoh | Hasil |
|---|---|---|
| string | `select('nm_kontak')` | `nm_kontak` |
| alias | `select(['COUNT(*)', 'total'])` | `COUNT(*) AS total` |
| tabel + kolom | `select(['kontak', 'id_kontak', 'id'])` | `kontak.id_kontak AS id` |
| banyak argumen | `select('a', 'b')` | dua kolom |
| node fungsi | `select(SelectNode::function(...))` | `COALESCE(...) AS ...` |
| fungsi via method | `selectFunction('NOW')` | `NOW()` |
| raw eksplisit | `selectRaw('COUNT(*)', 'total')` | `COUNT(*) AS total` |

`select()` tetap variadic: kolom, array alias, dan node fungsi boleh dicampur
dalam satu pemanggilan.

### Fungsi SQL

```php
use Selvi\Database\Builder\DML\Clauses\SelectNode;

$rows = DB::table('kontak')
    ->select(
        'id_kontak',
        SelectNode::function('COALESCE', [
            SelectNode::column('nm_kontak'),
            SelectNode::literal('Tanpa Nama'),
        ], 'nama'),
        SelectNode::function('CONCAT', [
            SelectNode::column('id_kontak'),
            SelectNode::literal('#'),
            SelectNode::column('nm_kontak'),
        ], 'label'))
    ->selectFunction('NOW', alias: 'sekarang')
    ->where([['id_kontak', '=', 5]])
    ->get()
    ->rows();
```

Hasil: satu baris dengan `nama` `John Doe`, `label` `5#John Doe`, dan
`sekarang` berisi waktu server.

SQL yang dihasilkan:

```sql
SELECT id_kontak, COALESCE(nm_kontak, 'Tanpa Nama') AS nama, CONCAT(id_kontak, '#', nm_kontak) AS label, NOW() AS sekarang FROM kontak WHERE id_kontak = 5
```

Aturan:

- `SelectNode::literal()` wajib dipakai untuk nilai literal; string biasa
  dianggap nama kolom.
- Literal disanitasi `MySQLSanitizer`; `raw` tidak disanitasi.
- Nama fungsi divalidasi sebagai identifier; nama tidak valid melempar
  `InvalidArgumentException`.
- `selectFunction()` menambahkan satu fungsi per pemanggilan. Untuk beberapa
  fungsi sekaligus, gunakan node di `select()` atau chain.
- `selectRaw()` adalah escape hatch eksplisit, misalnya untuk `COUNT(*)`.

Contoh agregat dan fungsi bertingkat:

```php
DB::table('kontak')
    ->select('id_departemen')
    ->selectFunction('COUNT', [SelectNode::star()], 'total')
    ->selectFunction('MAX', [SelectNode::column('id_kontak')], 'maks')
    ->group('id_departemen')
    ->get();
```

```sql
SELECT id_departemen, COUNT(*) AS total, MAX(id_kontak) AS maks FROM kontak GROUP BY id_departemen
```

`COALESCE(IFNULL(nm_kontak, ''), 'Tanpa Nama')` juga didukung karena argumen
fungsi boleh berupa node fungsi lain.

`selectUsing()` memberi akses langsung ke `SelectBuilder` bila perlu
`withTable()` atau `addNode()`.

## 4. Bentuk `where`

```php
DB::table('kontak')->where([['id_kontak', '>', 6]]);       // operator
DB::table('kontak')->where([['id_departemen', '=', null]]); // IS NULL
DB::table('kontak')->where('nm_kontak LIKE "%John%"');      // raw
DB::table('kontak')->where(function ($builder) {            // nested
    $builder->orWhere([['id_kontak', '=', 5]]);
    $builder->orWhere([['id_kontak', '=', 9]]);
});
```

Hasil pengujian:

| Kasus | Jumlah baris |
|---|---|
| `id_kontak > 6` | `3` |
| `id_departemen IS NULL` | `0` (semua baris terisi) |
| raw `nm_kontak LIKE "%John%"` | `5` |
| nested `id = 5 OR id = 9` | `2` |

Aturan:

- Satu kondisi boleh `['kolom', 'operator', 'nilai']` atau `['kolom', 'nilai']`
  (operator dianggap `=`).
- `null` dengan operator `=`/`IS` menjadi `IS NULL`; dengan `<>`/`IS NOT`
  menjadi `IS NOT NULL`.
- Closure membuat grup `( ... )`; `orWhere()` mengubah penghubung kondisi
  berikutnya menjadi `OR`.
- **Nilai** disanitasi menjadi literal oleh `MySQLSanitizer`. **Raw string**
  tidak disanitasi, jadi jangan menyisipkan input user ke raw where.

## 5. Join, group, dan agregat

```php
$rows = DB::table('kontak')
    ->leftJoin('departemen', 'departemen.id_departemen = kontak.id_departemen')
    ->select(
        'kontak.id_departemen AS id_departemen',
        'departemen.nm_departemen AS nm_departemen',
        ['COUNT(kontak.id_kontak)', 'jumlah'])
    ->group('kontak.id_departemen', 'departemen.nm_departemen')
    ->orderBy('kontak.id_departemen')
    ->get()
    ->rows();
```

Hasil pada data uji:

```json
{
    "data": [
        {
            "id_departemen": "1",
            "nm_departemen": "Programmer",
            "jumlah": "5"
        }
    ]
}
```

Tersedia `leftJoin()`, `innerJoin()`, `rightJoin()`, `join()`, dan
`joinUsing()`.

## 6. Order, limit, offset

```php
$rows = DB::table('kontak')
    ->select('id_kontak')
    ->orderBy('id_kontak', 'DESC')
    ->limit(2)
    ->offset(1)
    ->get()
    ->rows();
```

Hasil: `{"id":["8","7"]}`.

`orderBy()` menerima arah `ASC`/`DESC`. `order()` menerima beberapa bentuk
sekaligus (`'kolom DESC'`, `['kolom', 'ASC']`, `['kolom' => 'DESC']`).

## 7. Membaca hasil

`get()` mengembalikan objek `IResult`:

```php
$result = DB::table('kontak')->where([['id_kontak', '=', 5]])->get();

$result->num_rows(); // 1
$result->row();      // stdClass baris pertama, atau null
$result->rows();     // list<stdClass> semua baris
```

`row()` dan `rows()` memakai `data_seek(0)` sebelum membaca, sehingga aman
dipanggil berulang. Method `result()` masih ada tetapi sudah deprecated; pakai
`rows()`.

`Manager::get('main')->query(...)` mengembalikan `bool` untuk statement yang
tidak menghasilkan result set (mis. `SET`, `UPDATE`).

## Referensi API

- [`QueryBuilder`](../99-referensi/16-query-builder.md) — seluruh method DML.
- [`IResult`/`MySQLResult`](../99-referensi/17-hasil-query.md) — `rows()`,
  `row()`, `num_rows()`.
- [`SelectNode`](../99-referensi/30-select-node.md) — node kolom, literal,
  fungsi, dan raw.
- [`DB`](../99-referensi/09-db.md) — facade `table()` dan `connection()`.

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Koneksi database belum diatur.` | belum ada koneksi default | daftarkan lewat `Manager::add()` atau pakai `DB::connection('main')->table(...)` |
| `Table 'x' doesn't exist` | tabel belum dibuat / nama salah | jalankan migrasi atau cek nama tabel |
| `Unknown column 'x' in 'where clause'` | nama kolom salah, termasuk prefix tabel | gunakan nama kolom yang ada; kualifikasi `tabel.kolom` bila join |
| `Format where tidak valid` | item array bukan string/array kondisi | pakai `['kolom', 'operator', 'nilai']` |
| `Raw where tidak boleh kosong` | string `where('')` | isi SQL atau hapus pemanggilan |
| Semua nilai angka terbaca string | perilaku driver MySQL | konversi saat pemetaan DTO/model |
| `Nama fungsi SQL '...' tidak valid` | nama fungsi bukan identifier | pakai nama seperti `COALESCE`, tanpa spasi/simbol |
| `Argumen fungsi SQL harus berupa node select` | argumen bukan `SelectNode` | bungkus dengan `SelectNode::column()`/`literal()` |
| `Raw select tidak boleh kosong` | `selectRaw('')` | isi SQL atau hapus pemanggilan |

## Sumber

- [src/Database/Builder/DML/QueryBuilder.php](../../src/Database/Builder/DML/QueryBuilder.php) — rantai DML
- [src/Database/Builder/DML/WhereBuilder.php](../../src/Database/Builder/DML/WhereBuilder.php) dan
  `Clauses/WhereClause.php` — parsing kondisi
- [src/Database/Builder/DML/SelectBuilder.php](../../src/Database/Builder/DML/SelectBuilder.php), `JoinBuilder.php`,
  `GroupBuilder.php`, `OrderBuilder.php`
- [src/Database/Builder/DML/Clauses/SelectNode.php](../../src/Database/Builder/DML/Clauses/SelectNode.php) — node AST select
- [src/Database/Interfaces/IQueryBuilder.php](../../src/Database/Interfaces/IQueryBuilder.php) — `selectFunction()`/`selectRaw()`
- [src/Database/Drivers/MySQL/MySQLGrammar.php](../../src/Database/Drivers/MySQL/MySQLGrammar.php) — kompilasi SQL
- [src/Database/Drivers/MySQL/MySQLResult.php](../../src/Database/Drivers/MySQL/MySQLResult.php) — pembacaan hasil
- [tests/04.03-membaca-data/index.php](../../tests/04.03-membaca-data/index.php) — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/04.03-membaca-data/`
(MariaDB 10.7.8, PHP 8.4.14):

| Endpoint | Hasil |
|---|---|
| `GET /04.03-membaca-data/` | `200`, tiga baris `kontak` terurut |
| `GET /04.03-membaca-data/sql` | `200`, SQL dengan `LEFT JOIN`, `IS NOT NULL`, `GROUP BY`, `ORDER BY`, `LIMIT` |
| `GET /04.03-membaca-data/row` | `200`, `num_rows` `1`, `row` berisi satu objek |
| `GET /04.03-membaca-data/row-kosong` | `200`, `num_rows` `0`, `row` bernilai `null` |
| `GET /04.03-membaca-data/where` | `200`, `operator` `3`, `null` `0`, `raw` `5`, `nested` `2` |
| `GET /04.03-membaca-data/join` | `200`, satu grup `Programmer` dengan `jumlah` `5` |
| `GET /04.03-membaca-data/order-limit` | `200`, `["8","7"]` |
| `GET /04.03-membaca-data/hasil-bool` | `200`, `query('SET ...')` mengembalikan `bool` `true` |
| `GET /04.03-membaca-data/fungsi` | `200`, `COALESCE`, `CONCAT`, `NOW` terisi |
| `GET /04.03-membaca-data/fungsi-agregat` | `200`, `total` `5`, `maks` `9` |
| `GET /04.03-membaca-data/fungsi-bertumpuk` | `200`, `COALESCE(IFNULL(...))` berhasil |
| `GET /04.03-membaca-data/fungsi-sql` | `200`, SQL fungsi sesuai |
| `GET /04.03-membaca-data/fungsi-null` | `200`, literal `null` menjadi `NULL` |
| `GET /04.03-membaca-data/fungsi-raw` | `200`, `COUNT(*) AS total` |
| `GET /04.03-membaca-data/fungsi-invalid` | `400`, nama fungsi tidak valid |

Belum diuji: `rightJoin()`, `join()`, `joinUsing()`, `selectUsing()`, `order()`
dengan bentuk campuran, fungsi dengan argumen alias, dan node `raw` di dalam
argumen fungsi bertingkat.
