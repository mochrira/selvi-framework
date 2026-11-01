---
id: database-menulis-data
title: Menulis Data
type: panduan
tags: [database, dml, insert, update, delete, where, multi-row]
prerequisites: [database-membaca-data]
verified: 2026-11-01
applies_to: Selvi 3.x
---

# Menulis Data

Operasi tulis memakai `QueryBuilder` yang sama dengan pembacaan:
`insert()`, `update()`, dan `delete()`. `UPDATE` dan `DELETE` menolak berjalan
tanpa kondisi `WHERE`.

## Prasyarat

- [Membaca data](03-membaca-data.md) sudah dipahami.
- Koneksi `main` terdaftar.

## 1. Insert satu baris

```php
use Selvi\Database\DB;

$id = DB::table('demo_tulis')->insert([
    'nama' => 'A',
    'nilai' => 1,
]);
```

`insert()` mengembalikan hasil `lastId()` — id dari baris yang baru dibuat.

## 2. Insert banyak baris

```php
$id = DB::table('demo_tulis')->insert([
    ['nama' => 'B', 'nilai' => 2],
    ['nama' => 'C', 'nilai' => null],
]);
```

Satu statement `INSERT` dengan beberapa baris. Hati-hati: `insert()` tetap
mengembalikan `lastId()`, dan pada MySQL nilainya adalah **id pertama** dari
batch, bukan id baris terakhir. Pada pengujian, baris mendapat id `2` dan `3`,
sedangkan `insert()` mengembalikan `2`.

Kolom yang tidak ada di salah satu baris diisi `NULL`.

## 3. Update

```php
$berhasil = DB::table('demo_tulis')
    ->where([['id', '=', 1]])
    ->update(['nama' => 'A diubah', 'nilai' => 10]);
```

**Kembalian:** `bool` — `true` bila query berhasil dijalankan. Nilai ini bukan
jumlah baris yang berubah.

Tanpa `WHERE`, builder melempar `LogicException`:

```text
Operasi UPDATE memerlukan setidaknya satu kondisi WHERE untuk mencegah perubahan data massal secara tidak sengaja.
```

## 4. Delete

```php
$berhasil = DB::table('demo_tulis')
    ->where([['id', '=', 2]])
    ->delete();
```

**Kembalian:** `bool`. Tanpa `WHERE`:

```text
Operasi DELETE memerlukan setidaknya satu kondisi WHERE untuk mencegah penghapusan data massal secara tidak sengaja.
```

## 5. Siklus lengkap

```php
use Selvi\Database\Builder\DDL\Blueprint;
use Selvi\Database\DB;

$schema = DB::schema('main');
$schema->drop('demo_tulis', true);
$schema->create('demo_tulis', function (Blueprint $table) {
    $table->integer('id')->key()->autoIncrement();
    $table->string('nama', 50);
    $table->integer('nilai')->nullable();
});

$insertId = DB::table('demo_tulis')->insert(['nama' => 'A', 'nilai' => 1]);
$multiId = DB::table('demo_tulis')->insert([
    ['nama' => 'B', 'nilai' => 2],
    ['nama' => 'C', 'nilai' => null],
]);

DB::table('demo_tulis')->where([['id', '=', $insertId]])
    ->update(['nama' => 'A diubah', 'nilai' => 10]);
DB::table('demo_tulis')->where([['id', '=', $multiId]])->delete();

$rows = DB::table('demo_tulis')->orderBy('id')->get()->rows();
```

Hasil:

```json
{
    "insert_id": 1,
    "multi_insert_id": 2,
    "update": true,
    "delete": true,
    "rows": [
        { "id": "1", "nama": "A diubah", "nilai": "10" },
        { "id": "3", "nama": "C", "nilai": null }
    ]
}
```

Baris id `2` terhapus karena `delete()` memakai id pertama batch. Ini contoh
kenapa hasil `insert()` multi-baris tidak boleh dipakai sebagai id baris
terakhir.

## 6. Transaksi

`QueryBuilder` tidak membungkus operasi dalam transaksi otomatis. Untuk beberapa
operasi yang harus atomik, ambil koneksi dan kelola transaksi manual:

```php
use Selvi\Database\Manager;

$db = Manager::get('main');
$db->startTransaction();
try {
    DB::table('demo_tulis')->insert(['nama' => 'D', 'nilai' => 4]);
    DB::table('demo_tulis')->where([['nama', '=', 'A diubah']])
        ->update(['nilai' => 11]);
    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}
```

Detail transaksi ada di [koneksi](01-koneksi.md).

## Referensi API

- [`QueryBuilder`](../99-referensi/16-query-builder.md) — `insert()`,
  `update()`, `delete()`, dan method `where`.
- [`Schema`](../99-referensi/18-schema.md) — pembuatan tabel untuk contoh.
- [`Hasil Query`](../99-referensi/17-hasil-query.md) — membaca hasil setelah
  operasi tulis.

## Kesalahan umum

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Data insert tidak boleh kosong.` | `insert([])` | kirim minimal satu kolom |
| `Nama tabel untuk insert tidak boleh kosong.` | `DB::table('')` | isi nama tabel |
| `Data update tidak boleh kosong.` | `update([])` | kirim minimal satu kolom |
| `Operasi UPDATE/DELETE memerlukan ... WHERE` | lupa `where()` | tambahkan kondisi |
| Semua baris terhapus | kondisi `WHERE` terlalu luas | periksa operator dan nilai |
| Nilai string mengandung tanda kutip | disanitasi oleh `MySQLSanitizer` | tidak perlu escape manual |

## Sumber

- `src/Database/Builder/DML/QueryBuilder.php` — `insert()`, `update()`,
  `delete()`, dan pengaman WHERE
- `src/Database/Builder/DML/InsertBuilder.php` — normalisasi single/multi row
- `src/Database/Builder/DML/UpdateBuilder.php`
- `src/Database/Drivers/MySQL/MySQLGrammar.php` — kompilasi INSERT/UPDATE/DELETE
- `src/Database/Drivers/MySQL/MySQLSanitizer.php` — literal value
- `tests/04.04-menulis-data/index.php` — aplikasi uji halaman ini

## Status verifikasi

> Catatan pengujian pada lingkungan tertentu, bukan spesifikasi API. Perilaku
> yang mengikat ada di bagian sebelumnya.

Diuji 1 November 2026 lewat `http://localhost:8089/04.04-menulis-data/`
(MariaDB 10.7.8, PHP 8.4.14):

| Endpoint | Hasil |
|---|---|
| `GET /04.04-menulis-data/` | `200`, insert id `1`, id batch `2`, update/delete `true`, tersisa dua baris |
| `GET /04.04-menulis-data/tanpa-where` | `400`, `LogicException` pesan UPDATE |
| `GET /04.04-menulis-data/tanpa-where-delete` | `400`, `LogicException` pesan DELETE |

Pengujian memakai tabel sementara `demo_tulis` yang dibuat dan dihapus di
endpoint `/`. Belum diuji: insert dengan kolom berbeda antar baris, dan
transaksi pada halaman ini (transaksi diuji di [koneksi](01-koneksi.md)).
